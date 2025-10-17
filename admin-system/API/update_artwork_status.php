<?php
// update_artwork_status.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

if (file_exists(__DIR__ . '/db.php')) {
    require_once __DIR__ . '/db.php';
} elseif (file_exists(__DIR__ . '/../db.php')) {
    require_once __DIR__ . '/../db.php';
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'db.php not found.']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$artwork_id = $input['artwork_id'] ?? null;
$status = $input['status'] ?? null; // expected 'Approved' or 'Rejected'

if (!$artwork_id || !$status || !in_array($status, ['Approved','Rejected'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters. Provide artwork_id and status (Approved or Rejected).']);
    exit;
}

// detect driver
global $conn, $pdo;
$driver = null;
if (isset($conn) && $conn instanceof mysqli) $driver = 'mysqli';
elseif (isset($pdo) && $pdo instanceof PDO) $driver = 'pdo';

if (!$driver) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'DB connection missing.']); exit; }

try {
    // Debug log
    error_log("artwork_id: $artwork_id, status: $status");
    if ($driver === 'mysqli') {
        $stmt = $conn->prepare("UPDATE artworks SET status = ? WHERE artwork_id = ?");
        $stmt->bind_param('si', $status, $artwork_id);
        $ok = $stmt->execute();
        if ($ok && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Artwork status updated.']);
        } else {
            error_log('No rows updated or error (MySQLi): ' . $stmt->error);
            echo json_encode(['success' => false, 'message' => 'No rows updated or error.']);
        }
    } else { // pdo
        $stmt = $pdo->prepare("UPDATE artworks SET status = :status WHERE artwork_id = :aid");
        $ok = $stmt->execute([':status' => $status, ':aid' => $artwork_id]);
        if ($ok && $stmt->rowCount() > 0) {
            echo json_encode(['success'=>true,'message'=>'Artwork status updated.']);
        } else {
            error_log('No rows updated or error (PDO): ' . implode(' | ', $stmt->errorInfo()));
            echo json_encode(['success'=>false,'message'=>'No rows updated or error.']);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
