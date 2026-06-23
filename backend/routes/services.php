<?php
/**
 * services.php — CRUD routes for service listings
 * GET    /services          — browse/search (public)
 * GET    /services/{id}     — single service detail (public)
 * POST   /services          — create listing (provider only)
 * PUT    /services/{id}     — update listing (owner only)
 * DELETE /services/{id}     — delete listing (owner only)
 */
require_once __DIR__ . '/../config/db.php';

$db = getDB();

// ── GET /services ──────────────────────────────────────────────
if ($method === 'GET' && !$id) {
    $search   = '%' . ($db->real_escape_string($_GET['query'] ?? '')) . '%';
    $category = $_GET['category'] ?? '';
    $maxPrice = floatval($_GET['maxPrice'] ?? 0);
    $sort     = $_GET['sort'] ?? 'rating';

    $orderBy = match($sort) {
        'price_asc' => 's.price ASC',
        'newest'    => 's.created_at DESC',
        default     => 'avg_rating DESC'
    };

    $sql = "
        SELECT  s.service_id,
                s.title,
                s.description,
                s.price,
                s.created_at,
                c.name                              AS category,
                CONCAT(u.first_name,' ',u.last_name) AS provider_name,
                u.user_id                           AS provider_id,
                COALESCE(AVG(r.rating), 0)          AS avg_rating,
                COUNT(r.review_id)                  AS review_count
        FROM    services s
        JOIN    users      u ON s.user_id     = u.user_id
        JOIN    categories c ON s.category_id = c.category_id
        LEFT JOIN bookings b ON b.service_id  = s.service_id
        LEFT JOIN reviews  r ON r.booking_id  = b.booking_id
        WHERE   s.is_active = 1
          AND   (s.title LIKE ? OR s.description LIKE ?)";

    $params = [$search, $search];
    $types  = 'ss';

    if ($category) {
        $sql    .= ' AND c.name = ?';
        $params[] = $category;
        $types   .= 's';
    }
    if ($maxPrice > 0) {
        $sql    .= ' AND s.price <= ?';
        $params[] = $maxPrice;
        $types   .= 'd';
    }

    $sql .= " GROUP BY s.service_id ORDER BY $orderBy";

    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));

// ── GET /services/{id} ────────────────────────────────────────
} elseif ($method === 'GET' && $id) {
    $stmt = $db->prepare("
        SELECT  s.service_id,
                s.title,
                s.description,
                s.price,
                s.created_at,
                c.name                              AS category,
                CONCAT(u.first_name,' ',u.last_name) AS provider_name,
                u.user_id                           AS provider_id,
                u.bio                               AS provider_bio,
                COALESCE(AVG(r.rating), 0)          AS avg_rating,
                COUNT(r.review_id)                  AS review_count
        FROM    services s
        JOIN    users      u ON s.user_id     = u.user_id
        JOIN    categories c ON s.category_id = c.category_id
        LEFT JOIN bookings b ON b.service_id  = s.service_id
        LEFT JOIN reviews  r ON r.booking_id  = b.booking_id
        WHERE   s.service_id = ? AND s.is_active = 1
        GROUP BY s.service_id
    ");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        // Also fetch reviews for this service
        $rStmt = $db->prepare("
            SELECT  r.rating, r.comment, r.created_at,
                    CONCAT(u.first_name,' ',u.last_name) AS reviewer_name
            FROM    reviews r
            JOIN    bookings b ON r.booking_id = b.booking_id
            JOIN    users    u ON r.reviewer_id = u.user_id
            WHERE   b.service_id = ?
            ORDER BY r.created_at DESC
        ");
        $rStmt->bind_param('i', $id);
        $rStmt->execute();
        $row['reviews'] = $rStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode($row);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Service not found']);
    }

// ── POST /services ────────────────────────────────────────────
} elseif ($method === 'POST') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'provider') {
        http_response_code(403);
        echo json_encode(['message' => 'Providers only.']);
        $db->close(); exit;
    }
    $body        = json_decode(file_get_contents('php://input'), true);
    $title       = trim($body['title']       ?? '');
    $description = trim($body['description'] ?? '');
    $category    = trim($body['category']    ?? '');
    $price       = floatval($body['price']   ?? 0);

    if (!$title || !$description || !$category || $price <= 0) {
        http_response_code(400);
        echo json_encode(['message' => 'All fields required; price must be greater than 0.']);
        $db->close(); exit;
    }

    // Look up category_id
    $cStmt = $db->prepare('SELECT category_id FROM categories WHERE name = ?');
    $cStmt->bind_param('s', $category);
    $cStmt->execute();
    $cat = $cStmt->get_result()->fetch_assoc();
    if (!$cat) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid category.']);
        $db->close(); exit;
    }

    $userId     = $_SESSION['user_id'];
    $categoryId = $cat['category_id'];
    $stmt = $db->prepare('INSERT INTO services (user_id, category_id, title, description, price) VALUES (?,?,?,?,?)');
    $stmt->bind_param('iissd', $userId, $categoryId, $title, $description, $price);
    $stmt->execute();
    http_response_code(201);
    echo json_encode(['message' => 'Service created successfully.', 'service_id' => $db->insert_id]);

// ── PUT /services/{id} ────────────────────────────────────────
} elseif ($method === 'PUT' && $id) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401); echo json_encode(['message' => 'Not logged in.']); $db->close(); exit;
    }
    $body        = json_decode(file_get_contents('php://input'), true);
    $title       = trim($body['title']       ?? '');
    $description = trim($body['description'] ?? '');
    $category    = trim($body['category']    ?? '');
    $price       = floatval($body['price']   ?? 0);
    $userId      = $_SESSION['user_id'];

    $cStmt = $db->prepare('SELECT category_id FROM categories WHERE name = ?');
    $cStmt->bind_param('s', $category); $cStmt->execute();
    $cat = $cStmt->get_result()->fetch_assoc();
    if (!$cat) { http_response_code(400); echo json_encode(['message' => 'Invalid category.']); $db->close(); exit; }

    $categoryId = $cat['category_id'];
    $stmt = $db->prepare('UPDATE services SET title=?, description=?, category_id=?, price=? WHERE service_id=? AND user_id=?');
    $stmt->bind_param('ssidii', $title, $description, $categoryId, $price, $id, $userId);
    $stmt->execute();
    echo json_encode(['message' => 'Service updated.', 'affected' => $stmt->affected_rows]);

// ── DELETE /services/{id} ─────────────────────────────────────
} elseif ($method === 'DELETE' && $id) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401); $db->close(); exit;
    }
    $userId = $_SESSION['user_id'];
    $stmt   = $db->prepare('UPDATE services SET is_active=0 WHERE service_id=? AND user_id=?');
    $stmt->bind_param('ii', $id, $userId); $stmt->execute();
    echo json_encode(['message' => 'Service removed from marketplace.', 'affected' => $stmt->affected_rows]);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

$db->close();
?>
