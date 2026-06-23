<?php
/**
 * admin.php — Admin-only moderation routes
 * GET  /admin/stats    — platform statistics
 * GET  /admin/reports  — moderation queue
 * PUT  /admin/reports/{id} — resolve/dismiss report
 * PUT  /admin/users/{id}   — enable/disable user account
 */
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); echo json_encode(['message' => 'Admin access required.']); exit;
}

$db     = getDB();
$action = $segments[1] ?? '';
$itemId = $segments[2] ?? null;

// ── GET /admin/stats ─────────────────────────────────────────
if ($method === 'GET' && $action === 'stats') {
    $stats = [];
    $queries = [
        'total_users'    => "SELECT COUNT(*) AS n FROM users WHERE role <> 'admin'",
        'total_services' => "SELECT COUNT(*) AS n FROM services WHERE is_active = 1",
        'total_bookings' => "SELECT COUNT(*) AS n FROM bookings",
        'open_reports'   => "SELECT COUNT(*) AS n FROM reports WHERE status = 'Pending'",
        'total_reviews'  => "SELECT COUNT(*) AS n FROM reviews",
        'completed_bookings' => "SELECT COUNT(*) AS n FROM bookings WHERE booking_status = 'Completed'"
    ];
    foreach ($queries as $key => $sql) {
        $r = $db->query($sql)->fetch_assoc();
        $stats[$key] = $r['n'];
    }
    echo json_encode($stats);

// ── GET /admin/reports ───────────────────────────────────────
} elseif ($method === 'GET' && $action === 'reports') {
    $stmt = $db->prepare("
        SELECT  rp.report_id, rp.target_type, rp.target_id, rp.reason,
                rp.description, rp.status, rp.created_at,
                CONCAT(u.first_name,' ',u.last_name) AS reporter_name
        FROM    reports rp
        JOIN    users u ON rp.reporter_id = u.user_id
        ORDER BY rp.status ASC, rp.created_at DESC
    ");
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));

// ── GET /admin/users ─────────────────────────────────────────
} elseif ($method === 'GET' && $action === 'users') {
    $stmt = $db->prepare("
        SELECT user_id, first_name, last_name, email, role, is_active, created_at
        FROM users ORDER BY created_at DESC
    ");
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));

// ── PUT /admin/reports/{id} ──────────────────────────────────
} elseif ($method === 'PUT' && $action === 'reports' && $itemId) {
    $body   = json_decode(file_get_contents('php://input'), true);
    $status = $body['status'] ?? '';
    $adminId = $_SESSION['user_id'];

    if (!in_array($status, ['Resolved', 'Dismissed'])) {
        http_response_code(400); echo json_encode(['message' => 'Invalid status.']); $db->close(); exit;
    }

    // If resolved + target is a service, deactivate it
    if ($status === 'Resolved') {
        $rStmt = $db->prepare('SELECT target_type, target_id FROM reports WHERE report_id=?');
        $rStmt->bind_param('i', $itemId); $rStmt->execute();
        $rpt = $rStmt->get_result()->fetch_assoc();
        if ($rpt && $rpt['target_type'] === 'service') {
            $db->prepare('UPDATE services SET is_active=0 WHERE service_id=?')->bind_param('i', $rpt['target_id']);
        }
    }

    $stmt = $db->prepare('UPDATE reports SET status=?, reviewed_by=? WHERE report_id=?');
    $stmt->bind_param('sii', $status, $adminId, $itemId); $stmt->execute();
    echo json_encode(['message' => "Report $status.", 'affected' => $stmt->affected_rows]);

// ── PUT /admin/users/{id} ─────────────────────────────────────
} elseif ($method === 'PUT' && $action === 'users' && $itemId) {
    $body      = json_decode(file_get_contents('php://input'), true);
    $isActive  = isset($body['is_active']) ? (int)(bool)$body['is_active'] : null;

    if ($isActive === null) {
        http_response_code(400); echo json_encode(['message' => 'is_active required.']); $db->close(); exit;
    }

    $stmt = $db->prepare("UPDATE users SET is_active=? WHERE user_id=? AND role <> 'admin'");
    $stmt->bind_param('ii', $isActive, $itemId); $stmt->execute();
    echo json_encode(['message' => $isActive ? 'User enabled.' : 'User disabled.', 'affected' => $stmt->affected_rows]);

} else {
    http_response_code(404); echo json_encode(['error' => 'Admin route not found']);
}
$db->close();
?>
