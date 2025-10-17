<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once "db.php";
require_once "checkCredentials.php";

// Use correct database connection variable
$conn = $db;

// Initialize response
$response = [
    'success' => false,
    'profile_photo_url' => null,
    'message' => 'Not authenticated'
];

// Check authentication
$credentials = checkUserCredentials();
$user_id = null;

if ($credentials['authenticated']) {
    // Use authenticated user ID
    $user_id = $credentials['user_id'];
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    // For testing purposes, allow direct user ID
    $user_id = (int)$_POST['user_id'];
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }
} else {
    // Return a quiet failure for unauthenticated users to prevent console spam
    $response = [
        'success' => false,
        'profile_photo_url' => null,
        'message' => 'User not authenticated',
        'quiet' => true  // Flag to indicate this is expected
    ];
    echo json_encode($response);
    exit;
}

try {
    // Get user information including profile picture
    $stmt = $conn->prepare("SELECT first_name, last_name, profile_picture FROM users WHERE user_id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        $response = [
            'success' => false,
            'profile_photo_url' => null,
            'message' => 'User not found in database'
        ];
    } else {
        $profilePhoto = $user['profile_picture'];
        
        // Check if user has a profile picture set in database and if it exists
        if (!empty($profilePhoto) && trim($profilePhoto) !== '') {
            $profilePhotoPath = '../uploads/user_profile_picture/' . $profilePhoto;
            $profilePhotoUrl = './uploads/user_profile_picture/' . $profilePhoto;
            
            // Check if the file exists
            if (file_exists($profilePhotoPath)) {
                $response = [
                    'success' => true,
                    'profile_picture' => $profilePhoto,
                    'profile_photo_url' => $profilePhotoUrl,
                    'filename' => $profilePhoto,
                    'user_name' => $user['first_name'] . ' ' . $user['last_name'],
                    'message' => 'Profile photo found'
                ];
            } else {
                // File doesn't exist, try to find any file for this user (fallback search)
                $pattern = '../uploads/user_profile_picture/user_' . $user_id . '_*';
                $files = glob($pattern);
                
                if (!empty($files)) {
                    $foundFile = basename($files[0]);
                    
                    // Update database with found file
                    $updateStmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
                    if ($updateStmt) {
                        $updateStmt->bind_param("si", $foundFile, $user_id);
                        $updateStmt->execute();
                        $updateStmt->close();
                    }
                    
                    $response = [
                        'success' => true,
                        'profile_picture' => $foundFile,
                        'profile_photo_url' => './uploads/user_profile_picture/' . $foundFile,
                        'filename' => $foundFile,
                        'user_name' => $user['first_name'] . ' ' . $user['last_name'],
                        'message' => 'Profile photo found and database updated'
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'profile_photo_url' => null,
                        'message' => 'Profile photo file not found',
                        'filename' => $profilePhoto
                    ];
                }
            }
        } else {
            // No profile photo in database, check for orphaned files
            $pattern = '../uploads/user_profile_picture/user_' . $user_id . '_*';
            $files = glob($pattern);
            
            if (!empty($files)) {
                $foundFile = basename($files[0]);
                
                // Update database with found file
                $updateStmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
                if ($updateStmt) {
                    $updateStmt->bind_param("si", $foundFile, $user_id);
                    $updateStmt->execute();
                    $updateStmt->close();
                }
                
                $response = [
                    'success' => true,
                    'profile_picture' => $foundFile,
                    'profile_photo_url' => './uploads/user_profile_picture/' . $foundFile,
                    'filename' => $foundFile,
                    'user_name' => $user['first_name'] . ' ' . $user['last_name'],
                    'message' => 'Orphaned profile photo found and database updated'
                ];
            } else {
                $response = [
                    'success' => false,
                    'profile_photo_url' => null,
                    'message' => 'No profile photo set for user'
                ];
            }
        }
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Get profile photo error: " . $e->getMessage());
    $response = [
        'success' => false,
        'profile_photo_url' => null,
        'message' => 'Database error occurred'
    ];
}

echo json_encode($response);
?>
