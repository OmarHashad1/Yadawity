<?php
// deleteAccount.php
header('Content-Type: application/json');
require_once 'db.php';
session_start();

$user_id = null;
if (isset($_COOKIE['user_login'])) {
    $cookie_parts = explode('_', $_COOKIE['user_login'], 2);
    $user_id = intval($cookie_parts[0]);
} elseif (isset($_COOKIE['user_id'])) {
    $user_id = intval($_COOKIE['user_id']);
}

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit;
}

if (isset($db) && $db) {
    $stmt = $db->prepare('DELETE FROM users WHERE user_id = ?');
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $success = $stmt->execute();
        $stmt->close();
        if ($success) {
            // Optionally, clear cookies
            setcookie('user_login', '', time() - 3600, '/');
            setcookie('user_id', '', time() - 3600, '/');
            session_destroy();
            echo json_encode(['success' => true]);
            exit;
        }
    }
}
echo json_encode(['success' => false, 'message' => 'Failed to delete account.']);
