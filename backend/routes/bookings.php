<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['message' => 'Not logged in.']); exit; }

$db     = getDB();
$userId = $_SESSION['user_id'];
$role   = $_SESSION['role'];

// GET /bookings — fetch bookings for logged-in user
if ($method === 'GET') {
    if ($role === 'customer') {
        $stmt = $db->prepare(
            "SELECT b.*, s.title AS service_title,
                    CONCAT(u.first_name,' ',u.last_name) AS provider_name
             FROM bookings b
             JOIN services s ON b.service_id = s.service_id
             JOIN users u    ON b.provider_id = u.user_id
             WHERE b.customer_id = ? ORDER BY b.booking_date DESC"
        );
    } else {
        $stmt = $db->prepare(
            "SELECT b.*, s.title AS service_title,
                    CONCAT(u.first_name,' ',u.last_name) AS customer_name
             FROM bookings b
             JOIN services s ON b.service_id = s.service_id
             JOIN users u    ON b.customer_id = u.user_id
             WHERE b.provider_id = ? ORDER BY b.booking_date DESC"
        );
    }
    $stmt->bind_param('i', $userId); $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));

// POST /bookings — customer books a service
} elseif ($method === 'POST') {
    if ($role !== 'customer') {
        http_response_code(403); echo json_encode(['message' => 'Only customers can book services.']); $db->close(); exit;
    }
    $body      = json_decode(file_get_contents('php://input'), true);
    $serviceId = intval($body['service_id'] ?? 0);
    $message   = trim($body['message'] ?? '');

    if (!$serviceId || !$message) {
        http_response_code(400); echo json_encode(['message' => 'service_id and message required.']); $db->close(); exit;
    }

    // Get provider_id and ensure customer isn't booking own service
    $stmt = $db->prepare('SELECT user_id FROM services WHERE service_id = ? AND is_active = 1');
    $stmt->bind_param('i', $serviceId); $stmt->execute();
    $svc = $stmt->get_result()->fetch_assoc();

    if (!$svc) { http_response_code(404); echo json_encode(['message' => 'Service not found.']); $db->close(); exit; }
    if ($svc['user_id'] == $userId) {
        http_response_code(400); echo json_encode(['message' => 'You cannot book your own service.']); $db->close(); exit;
    }

    $providerId = $svc['user_id'];
    $stmt = $db->prepare('INSERT INTO bookings (service_id,customer_id,provider_id,message) VALUES (?,?,?,?)');
    $stmt->bind_param('iiis', $serviceId, $userId, $providerId, $message);
    $stmt->execute();
    http_response_code(201);
    echo json_encode(['message' => 'Booking request submitted.', 'booking_id' => $db->insert_id]);

// PUT /bookings/{id} — provider accepts/rejects, or marks complete
} elseif ($method === 'PUT' && $id) {
    $body   = json_decode(file_get_contents('php://input'), true);
    $status = $body['booking_status'] ?? '';
    $allowed = ['Active','Rejected','Completed','Cancelled'];

    if (!in_array($status, $allowed)) {
        http_response_code(400); echo json_encode(['message' => 'Invalid status.']); $db->close(); exit;
    }

    $stmt = $db->prepare('UPDATE bookings SET booking_status=? WHERE booking_id=? AND (provider_id=? OR customer_id=?)');
    $stmt->bind_param('siii', $status, $id, $userId, $userId);
    $stmt->execute();
    echo json_encode(['message' => "Booking updated to $status.", 'affected' => $stmt->affected_rows]);

} else {
    http_response_code(405); echo json_encode(['error' => 'Not allowed']);
}
$db->close();
?>
