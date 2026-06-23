<?php
require_once __DIR__ . '/../config/db.php';

$db = getDB();

// GET /reviews?service_id=X
if ($method === 'GET') {
    $serviceId = intval($_GET['service_id'] ?? 0);
    if (!$serviceId) { http_response_code(400); echo json_encode(['message' => 'service_id required.']); $db->close(); exit; }

    $stmt = $db->prepare(
        "SELECT r.rating, r.comment, r.created_at,
                CONCAT(u.first_name,' ',u.last_name) AS reviewer_name
         FROM reviews r
         JOIN bookings b ON r.booking_id = b.booking_id
         JOIN users u    ON r.reviewer_id = u.user_id
         WHERE b.service_id = ? ORDER BY r.created_at DESC"
    );
    $stmt->bind_param('i', $serviceId); $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));

// POST /reviews — customer reviews a completed booking
} elseif ($method === 'POST') {
    if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['message' => 'Not logged in.']); $db->close(); exit; }

    $body      = json_decode(file_get_contents('php://input'), true);
    $bookingId = intval($body['booking_id'] ?? 0);
    $rating    = intval($body['rating']     ?? 0);
    $comment   = trim($body['comment']      ?? '');
    $userId    = $_SESSION['user_id'];

    if (!$bookingId || $rating < 1 || $rating > 5 || !$comment) {
        http_response_code(400); echo json_encode(['message' => 'booking_id, rating (1-5), and comment required.']); $db->close(); exit;
    }

    // Verify booking is Completed and belongs to this customer
    $stmt = $db->prepare('SELECT booking_id FROM bookings WHERE booking_id=? AND customer_id=? AND booking_status="Completed"');
    $stmt->bind_param('ii', $bookingId, $userId); $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        http_response_code(403); echo json_encode(['message' => 'Booking not found or not completed.']); $db->close(); exit;
    }

    $stmt = $db->prepare('INSERT INTO reviews (booking_id,reviewer_id,rating,comment) VALUES (?,?,?,?)');
    $stmt->bind_param('iiis', $bookingId, $userId, $rating, $comment);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(['message' => 'Review submitted successfully.']);
    } else {
        http_response_code(409);
        echo json_encode(['message' => 'You have already reviewed this booking.']);
    }
} else {
    http_response_code(405); echo json_encode(['error' => 'Not allowed']);
}
$db->close();
?>
