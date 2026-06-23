<?php
/**
 * messages.php — Messaging routes
 * GET  /messages             — list conversations for logged-in user
 * GET  /messages/{conv_id}   — get messages in a conversation
 * POST /messages             — send a message (creates conversation if needed)
 */
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401); echo json_encode(['message' => 'Not logged in.']); exit;
}

$db     = getDB();
$userId = $_SESSION['user_id'];

// ── GET /messages — list all conversations ──────────────────
if ($method === 'GET' && !$id) {
    $stmt = $db->prepare("
        SELECT  cv.conversation_id,
                cv.created_at,
                CONCAT(u.first_name,' ',u.last_name) AS other_user_name,
                u.user_id                            AS other_user_id,
                (SELECT content  FROM messages m WHERE m.conversation_id = cv.conversation_id ORDER BY m.sent_at DESC LIMIT 1) AS last_message,
                (SELECT sent_at  FROM messages m WHERE m.conversation_id = cv.conversation_id ORDER BY m.sent_at DESC LIMIT 1) AS last_sent_at,
                (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = cv.conversation_id AND m.sender_id <> ? AND m.is_read = 0) AS unread_count
        FROM    conversations cv
        JOIN    users u ON u.user_id = IF(cv.user_a_id = ?, cv.user_b_id, cv.user_a_id)
        WHERE   cv.user_a_id = ? OR cv.user_b_id = ?
        ORDER BY last_sent_at DESC
    ");
    $stmt->bind_param('iiii', $userId, $userId, $userId, $userId);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));

// ── GET /messages/{conv_id} — get thread ────────────────────
} elseif ($method === 'GET' && $id) {
    // Verify user belongs to this conversation
    $check = $db->prepare('SELECT conversation_id FROM conversations WHERE conversation_id=? AND (user_a_id=? OR user_b_id=?)');
    $check->bind_param('iii', $id, $userId, $userId); $check->execute();
    if (!$check->get_result()->fetch_assoc()) {
        http_response_code(403); echo json_encode(['message' => 'Access denied.']); $db->close(); exit;
    }

    // Mark as read
    $markRead = $db->prepare('UPDATE messages SET is_read=1 WHERE conversation_id=? AND sender_id<>?');
    $markRead->bind_param('ii', $id, $userId); $markRead->execute();

    $stmt = $db->prepare("
        SELECT  m.message_id, m.content, m.sent_at, m.is_read,
                m.sender_id,
                CONCAT(u.first_name,' ',u.last_name) AS sender_name
        FROM    messages m
        JOIN    users u ON m.sender_id = u.user_id
        WHERE   m.conversation_id = ?
        ORDER BY m.sent_at ASC
    ");
    $stmt->bind_param('i', $id); $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));

// ── POST /messages — send a message ─────────────────────────
} elseif ($method === 'POST') {
    $body       = json_decode(file_get_contents('php://input'), true);
    $receiverId = intval($body['receiver_id'] ?? 0);
    $content    = trim($body['content'] ?? '');

    if (!$receiverId || !$content) {
        http_response_code(400); echo json_encode(['message' => 'receiver_id and content required.']); $db->close(); exit;
    }
    if ($receiverId === $userId) {
        http_response_code(400); echo json_encode(['message' => 'Cannot message yourself.']); $db->close(); exit;
    }

    // Enforce user_a < user_b for unique pair constraint
    $userA = min($userId, $receiverId);
    $userB = max($userId, $receiverId);

    // Get or create conversation
    $convStmt = $db->prepare('SELECT conversation_id FROM conversations WHERE user_a_id=? AND user_b_id=?');
    $convStmt->bind_param('ii', $userA, $userB); $convStmt->execute();
    $conv = $convStmt->get_result()->fetch_assoc();

    if (!$conv) {
        $ins = $db->prepare('INSERT INTO conversations (user_a_id, user_b_id) VALUES (?,?)');
        $ins->bind_param('ii', $userA, $userB); $ins->execute();
        $convId = $db->insert_id;
    } else {
        $convId = $conv['conversation_id'];
    }

    $msgStmt = $db->prepare('INSERT INTO messages (conversation_id, sender_id, content) VALUES (?,?,?)');
    $msgStmt->bind_param('iis', $convId, $userId, $content);
    $msgStmt->execute();
    http_response_code(201);
    echo json_encode(['message' => 'Message sent.', 'message_id' => $db->insert_id, 'conversation_id' => $convId]);

} else {
    http_response_code(405); echo json_encode(['error' => 'Method not allowed']);
}
$db->close();
?>
