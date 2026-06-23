<?php
require_once __DIR__ . '/../config/db.php';

$action = $segments[1] ?? '';

if ($action === 'register' && $method === 'POST') {
    $body      = json_decode(file_get_contents('php://input'), true);
    $firstName = trim($body['firstName'] ?? '');
    $lastName  = trim($body['lastName']  ?? '');
    $email     = trim($body['email']     ?? '');
    $password  = $body['password']       ?? '';
    $role      = $body['role']           ?? '';

    if (!$firstName || !$lastName || !$email || !$password || !$role) {
        http_response_code(400); echo json_encode(['message' => 'All fields are required.']); exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400); echo json_encode(['message' => 'Invalid email address.']); exit;
    }
    if (strlen($password) < 8) {
        http_response_code(400); echo json_encode(['message' => 'Password must be at least 8 characters.']); exit;
    }
    if (!in_array($role, ['customer', 'provider'])) {
        http_response_code(400); echo json_encode(['message' => 'Invalid role.']); exit;
    }

    $db   = getDB();
    $stmt = $db->prepare('SELECT user_id FROM users WHERE email = ?');
    $stmt->bind_param('s', $email); $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        http_response_code(409); echo json_encode(['message' => 'Email already registered.']); $db->close(); exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare('INSERT INTO users (first_name,last_name,email,password_hash,role) VALUES (?,?,?,?,?)');
    $stmt->bind_param('sssss', $firstName, $lastName, $email, $hash, $role);

    if ($stmt->execute()) {
        $userId = $db->insert_id;
        $_SESSION['user_id'] = $userId; $_SESSION['role'] = $role; $_SESSION['name'] = $firstName;
        http_response_code(201);
        echo json_encode(['message' => 'Registration successful.',
            'user' => ['user_id' => $userId, 'first_name' => $firstName, 'role' => $role]]);
    } else {
        http_response_code(500); echo json_encode(['message' => 'Registration failed.']);
    }
    $db->close();

} elseif ($action === 'login' && $method === 'POST') {
    $body     = json_decode(file_get_contents('php://input'), true);
    $email    = trim($body['email']    ?? '');
    $password = $body['password']      ?? '';

    if (!$email || !$password) {
        http_response_code(400); echo json_encode(['message' => 'Email and password required.']); exit;
    }

    $db   = getDB();
    $stmt = $db->prepare('SELECT user_id,first_name,role,password_hash FROM users WHERE email = ?');
    $stmt->bind_param('s', $email); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row || !password_verify($password, $row['password_hash'])) {
        http_response_code(401); echo json_encode(['message' => 'Invalid email or password.']); $db->close(); exit;
    }

    $_SESSION['user_id'] = $row['user_id']; $_SESSION['role'] = $row['role']; $_SESSION['name'] = $row['first_name'];
    echo json_encode(['message' => 'Login successful.',
        'user' => ['user_id' => $row['user_id'], 'first_name' => $row['first_name'], 'role' => $row['role']]]);
    $db->close();

} elseif ($action === 'logout' && $method === 'POST') {
    session_destroy();
    echo json_encode(['message' => 'Logged out.']);

} elseif ($action === 'me' && $method === 'GET') {
    if (isset($_SESSION['user_id'])) {
        echo json_encode(['logged_in' => true,
            'user' => ['user_id' => $_SESSION['user_id'], 'role' => $_SESSION['role'], 'name' => $_SESSION['name']]]);
    } else {
        echo json_encode(['logged_in' => false]);
    }
} else {
    http_response_code(405); echo json_encode(['error' => 'Not allowed']);
}
?>
