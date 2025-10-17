<?php
// pending_artists.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

if (file_exists(__DIR__ . '/db.php')) require_once __DIR__ . '/db.php';
elseif (file_exists(__DIR__ . '/../db.php')) require_once __DIR__ . '/../db.php';
else { http_response_code(500); echo json_encode(['success' => false, 'message' => 'db.php not found.']); exit; }

global $conn, $pdo;
$driver = null;
if (isset($conn) && $conn instanceof mysqli) $driver = 'mysqli';
elseif (isset($pdo) && $pdo instanceof PDO) $driver = 'pdo';

if (!$driver) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'DB connection missing.']); exit; }

try {
    $artists = [];
    $sql = "SELECT user_id, email, first_name, last_name, phone, profile_picture, is_active, is_verified, created_at
            FROM users
            WHERE user_type = 'artist' AND is_verified = 0
            ORDER BY created_at DESC";

    if ($driver === 'mysqli') {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $artists[] = $row;
    } else {
        $stmt = $pdo->query($sql);
        $artists = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode(['success'=>true,'data'=>$artists]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
