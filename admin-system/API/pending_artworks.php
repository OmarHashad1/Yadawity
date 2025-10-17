<?php
// pending_artworks.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

// require db.php (tries multiple likely paths)
if (file_exists(__DIR__ . '/db.php')) {
    require_once __DIR__ . '/db.php';
} elseif (file_exists(__DIR__ . '/../db.php')) {
    require_once __DIR__ . '/../db.php';
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'db.php not found.']); exit;
}

// Helper: check connection type (mysqli or PDO)
function is_mysqli($c){ return (isset($c) && $c instanceof mysqli); }
function is_pdo($c){ return (isset($c) && $c instanceof PDO); }

// Expect $conn (mysqli) or $pdo (PDO) from db.php. If $conn not set, try $pdo variable.
global $conn, $pdo;
$driver = null;
if (isset($conn) && $conn instanceof mysqli) { $driver = 'mysqli'; }
elseif (isset($pdo) && $pdo instanceof PDO) { $driver = 'pdo'; }
else {
    // try common variable names fallback
    if (isset($mysqli) && $mysqli instanceof mysqli) { $conn = $mysqli; $driver = 'mysqli'; }
    if (isset($db) && $db instanceof PDO) { $pdo = $db; $driver = 'pdo'; }
}

if (!$driver) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection ($conn or $pdo) not found in db.php']);
    exit;
}

try {
    $artworks = [];

    if ($driver === 'mysqli') {
        $sql = "SELECT a.*, u.user_id AS artist_user_id, u.first_name, u.last_name, u.email
                FROM artworks a
                LEFT JOIN users u ON a.artist_id = u.user_id
                WHERE a.status = 'Pending'
                ORDER BY a.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            // fetch photos for artwork
            $photosStmt = $conn->prepare("SELECT photo_id, image_path, is_primary FROM artwork_photos WHERE artwork_id = ? ORDER BY is_primary DESC, photo_id ASC");
            $photosStmt->bind_param('i', $row['artwork_id']);
            $photosStmt->execute();
            $photosRes = $photosStmt->get_result();
            $photos = [];
            while ($p = $photosRes->fetch_assoc()) { $photos[] = $p; }
            $row['photos'] = $photos;
            $artworks[] = $row;
        }
    } else { // pdo
        $sql = "SELECT a.*, u.user_id AS artist_user_id, u.first_name, u.last_name, u.email
                FROM artworks a
                LEFT JOIN users u ON a.artist_id = u.user_id
                WHERE a.status = 'Pending'
                ORDER BY a.created_at DESC";
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $pstmt = $pdo->prepare("SELECT photo_id, image_path, is_primary FROM artwork_photos WHERE artwork_id = ? ORDER BY is_primary DESC, photo_id ASC");
            $pstmt->execute([$row['artwork_id']]);
            $photos = $pstmt->fetchAll(PDO::FETCH_ASSOC);
            $row['photos'] = $photos;
            $artworks[] = $row;
        }
    }

    echo json_encode(['success' => true, 'data' => $artworks]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
