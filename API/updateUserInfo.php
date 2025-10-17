<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db.php';

function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function validateUserAuthentication($db) {
    $user_id = null;
    if (isset($_COOKIE['user_login'])) {
        $cookie_parts = explode('_', $_COOKIE['user_login'], 2);
        if (count($cookie_parts) !== 2) {
            throw new Exception('Invalid cookie format. Please log in again.');
        }
        $user_id = intval($cookie_parts[0]);
        if ($user_id <= 0) {
            throw new Exception('Invalid user session. Please log in again.');
        }
    } elseif (isset($_COOKIE['user_id'])) {
        $user_id = intval($_COOKIE['user_id']);
        if ($user_id <= 0) {
            throw new Exception('Invalid user session. Please log in again.');
        }
    } else {
        throw new Exception('No session found. Please log in.');
    }
    return $user_id;
}

try {
    $user_id = validateUserAuthentication($db);
    if (!$user_id) throw new Exception('User not authenticated.');

    $fields = ['firstName', 'lastName', 'email', 'phone', 'bio'];
    $updates = [];
    $params = [];
    $types = '';
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $updates[] = "$field = ?";
            $params[] = $_POST[$field];
            $types .= 's';
        }
    }
    // Handle photo upload
    $photoPath = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photoPath = 'uploads/profile_' . $user_id . '_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['photo']['tmp_name'], '../' . $photoPath);
        $updates[] = 'photo = ?';
        $params[] = $photoPath;
        $types .= 's';
    }
    if (count($updates) > 0) {
        $sql = 'UPDATE users SET ' . implode(',', $updates) . ' WHERE user_id = ?';
        $params[] = $user_id;
        $types .= 'i';
        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();
    }
    sendResponse(true, 'Profile updated successfully.');
} catch (Exception $e) {
    sendResponse(false, $e->getMessage());
}
