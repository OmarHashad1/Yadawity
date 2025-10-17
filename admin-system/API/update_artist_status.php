<?php
// update_artist_status.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

if (file_exists(__DIR__ . '/db.php')) require_once __DIR__ . '/db.php';
elseif (file_exists(__DIR__ . '/../db.php')) require_once __DIR__ . '/../db.php';
else { http_response_code(500); echo json_encode(['success' => false, 'message' => 'db.php not found.']); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$user_id = $input['user_id'] ?? null;
$action = strtolower($input['action'] ?? '');

if (!$user_id || !in_array($action, ['approve','reject'])) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Provide user_id and action (approve or reject).']);
    exit;
}

global $conn, $pdo;
$driver = null;
if (isset($conn) && $conn instanceof mysqli) $driver = 'mysqli';
elseif (isset($pdo) && $pdo instanceof PDO) $driver = 'pdo';

if (!$driver) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'DB connection missing.']); exit; }

try {
    if ($action === 'approve') {
        // set is_verified = 1 and is_active = 1
        if ($driver === 'mysqli') {
            $stmt = $conn->prepare("UPDATE users SET is_verified = 1, is_active = 1 WHERE user_id = ?");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $ok = $stmt->affected_rows >= 0;
        } else {
            $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, is_active = 1 WHERE user_id = :uid");
            $ok = $stmt->execute([':uid' => $user_id]);
        }
        echo json_encode(['success'=>$ok, 'message' => $ok ? 'Artist approved.' : 'No rows changed.']);
    } else {
        // reject: set is_verified = 0 and is_active = 0 (you can change logic)
        if ($driver === 'mysqli') {
            $stmt = $conn->prepare("UPDATE users SET is_verified = 0, is_active = 0 WHERE user_id = ?");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $ok = $stmt->affected_rows >= 0;
        } else {
            $stmt = $pdo->prepare("UPDATE users SET is_verified = 0, is_active = 0 WHERE user_id = :uid");
            $ok = $stmt->execute([':uid' => $user_id]);
        }
        echo json_encode(['success'=>$ok, 'message' => $ok ? 'Artist rejected/disabled.' : 'No rows changed.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
