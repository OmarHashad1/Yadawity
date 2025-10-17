<?php
// Start output buffering to prevent any unwanted output
ob_start();

// Set JSON header for API responses
header('Content-Type: application/json');

// Only allow POST requests for this API
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Include database connection with error handling
try {
    require_once "db.php";
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

function checkUserAuthentication($db) {
    try {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if session variables exist
        if (isset($_SESSION['user_id']) && isset($_SESSION['session_token'])) {
            $user_id = $_SESSION['user_id'];
            $session_token = $_SESSION['session_token'];
            
            // Verify session in database
            $stmt = $db->prepare("SELECT user_id, expires_at FROM user_login_sessions WHERE session_id = ? AND user_id = ? AND is_active = 1");
            
            if (!$stmt) {
                throw new Exception("Failed to prepare session verification query: " . $db->error);
            }
            
            $stmt->bind_param("si", $session_token, $user_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute session verification query: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $session = $result->fetch_assoc();
                
                // Check if session hasn't expired
                if (strtotime($session['expires_at']) > time()) {
                    $stmt->close();
                    return true; // Valid session found
                } else {
                    // Session expired, clean it up
                    cleanupExpiredSession($db, $session_token);
                    session_unset();
                }
            }
            $stmt->close();
        }
        
        // Check for login cookie as backup
        return checkLoginCookie($db);
        
    } catch (Exception $e) {
        error_log("checkUserAuthentication function error: " . $e->getMessage());
        return false;
    }
}

function cleanupExpiredSession($db, $session_token) {
    try {
        $cleanup_stmt = $db->prepare("UPDATE user_login_sessions SET is_active = 0, logout_time = NOW() WHERE session_id = ?");
        
        if (!$cleanup_stmt) {
            throw new Exception("Failed to prepare cleanup query: " . $db->error);
        }
        
        $cleanup_stmt->bind_param("s", $session_token);
        
        if (!$cleanup_stmt->execute()) {
            throw new Exception("Failed to execute cleanup query: " . $cleanup_stmt->error);
        }
        
        $cleanup_stmt->close();
        return true;
        
    } catch (Exception $e) {
        error_log("cleanupExpiredSession function error: " . $e->getMessage());
        return false;
    }
}

function checkLoginCookie($db) {
    try {
        if (!isset($_COOKIE['user_login'])) {
            return false;
        }
        
        $cookie_parts = explode('_', $_COOKIE['user_login'], 2);
        if (count($cookie_parts) !== 2) {
            return false;
        }
        
        $user_id = intval($cookie_parts[0]);
        $cookie_hash = $cookie_parts[1];
        
        // Verify user exists and is active
        $stmt = $db->prepare("SELECT user_id, email FROM users WHERE user_id = ? AND is_active = 1");
        
        if (!$stmt) {
            throw new Exception("Failed to prepare user verification query: " . $db->error);
        }
        
        $stmt->bind_param("i", $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute user verification query: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Check if there's an active session for this user
            if (validateCookieSession($db, $user_id, $user['email'], $cookie_hash)) {
                $stmt->close();
                return true;
            }
        }
        $stmt->close();
        return false;
        
    } catch (Exception $e) {
        error_log("checkLoginCookie function error: " . $e->getMessage());
        return false;
    }
}

function validateCookieSession($db, $user_id, $email, $cookie_hash) {
    try {
        $session_stmt = $db->prepare("SELECT session_id, login_time FROM user_login_sessions WHERE user_id = ? AND is_active = 1 AND expires_at > NOW() ORDER BY login_time DESC LIMIT 1");
        
        if (!$session_stmt) {
            throw new Exception("Failed to prepare session validation query: " . $db->error);
        }
        
        $session_stmt->bind_param("i", $user_id);
        
        if (!$session_stmt->execute()) {
            throw new Exception("Failed to execute session validation query: " . $session_stmt->error);
        }
        
        $session_result = $session_stmt->get_result();
        
        if ($session_result->num_rows === 1) {
            $session_data = $session_result->fetch_assoc();
            
            // Verify cookie hash matches expected pattern for this session
            $expected_hash = hash('sha256', $email . $session_data['login_time'] . 'yadawity_salt');
            
            if (hash_equals($expected_hash, $cookie_hash)) {
                // Restore session variables
                $_SESSION['user_id'] = $user_id;
                $_SESSION['session_token'] = $session_data['session_id'];
                $_SESSION['email'] = $email;
                
                $session_stmt->close();
                return true;
            }
        }
        $session_stmt->close();
        return false;
        
    } catch (Exception $e) {
        error_log("validateCookieSession function error: " . $e->getMessage());
        return false;
    }
}

function validateFileSignature($file, $allowedTypes) {
    try {
        $signatures = [
            'image/jpeg' => ["\xFF\xD8\xFF"],
            'image/png' => ["\x89\x50\x4E\x47"],
            'image/gif' => ["\x47\x49\x46"],
            'application/pdf' => ["\x25\x50\x44\x46"]
        ];
        
        if (!file_exists($file['tmp_name'])) {
            return false;
        }
        
        $handle = fopen($file['tmp_name'], 'rb');
        if (!$handle) {
            return false;
        }
        
        $bytes = fread($handle, 8);
        fclose($handle);
        
        foreach ($allowedTypes as $mimeType) {
            if (isset($signatures[$mimeType])) {
                foreach ($signatures[$mimeType] as $signature) {
                    if (strpos($bytes, $signature) === 0) {
                        return true;
                    }
                }
            }
        }
        return false;
        
    } catch (Exception $e) {
        error_log("validateFileSignature function error: " . $e->getMessage());
        return false;
    }
}

function uploadFile($file, $uploadDir, $prefix = '') {
    try {
        $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $allowedDocTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        
        $allowedTypes = (strpos($prefix, 'id_') === 0) ? $allowedDocTypes : $allowedImageTypes;
        
        // Removed file signature validation for consistency
        if (!file_exists($file['tmp_name']) || $file['size'] <= 0) {
            return ['success' => false, 'message' => 'Invalid file or file is empty'];
        }
        
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = (strpos($prefix, 'id_') === 0) ? ['pdf', 'jpg', 'jpeg', 'png'] : ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            return ['success' => false, 'message' => 'File extension not allowed'];
        }
        
        if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            return ['success' => false, 'message' => 'File size too large (max 5MB)'];
        }
        
        $fileName = $prefix . uniqid() . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return ['success' => false, 'message' => 'Failed to create upload directory'];
            }
        }
        
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['success' => true, 'filename' => $fileName];
        }
        
        return ['success' => false, 'message' => 'Upload failed'];
        
    } catch (Exception $e) {
        error_log("uploadFile function error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Upload failed due to server error'];
    }
}

function completeUserRegistration($db, $data, $isActive = true) {
    try {
        $isActiveValue = $isActive ? 1 : 0;
        $profilePic = $data['profile_picture'] ?? null;
        
        if ($data['user_type'] === 'artist') {
            // Artist registration with additional fields (verification docs saved to folder only)
            $stmt = $db->prepare("INSERT INTO users (email, password, first_name, last_name, phone, user_type, profile_picture, bio, art_specialty, years_of_experience, national_id, is_active, is_verified, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())");
            
            if (!$stmt) {
                throw new Exception("Failed to prepare artist registration query: " . $db->error);
            }
            
            $stmt->bind_param("sssssssssssi", 
                $data['email'], 
                $data['password'], 
                $data['first_name'], 
                $data['last_name'], 
                $data['phone'], 
                $data['user_type'], 
                $profilePic, 
                $data['bio'], 
                $data['art_specialty'], 
                $data['years_of_experience'], 
                $data['national_id'], 
                $isActiveValue
            );
        } else {
            // Buyer registration
            $stmt = $db->prepare("INSERT INTO users (email, password, first_name, last_name, phone, user_type, profile_picture, is_active, is_verified, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())");
            
            if (!$stmt) {
                throw new Exception("Failed to prepare buyer registration query: " . $db->error);
            }
            
            $stmt->bind_param("sssssssi", $data['email'], $data['password'], $data['first_name'], $data['last_name'], $data['phone'], $data['user_type'], $profilePic, $isActiveValue);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute registration query: " . $stmt->error);
        }
        
        $userId = $db->insert_id;
        $stmt->close();
        
        return ['success' => true, 'message' => 'Registration completed successfully', 'user_id' => $userId];
        
    } catch (Exception $e) {
        error_log("completeUserRegistration function error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

function uploadProfilePicture($file, $userId, $firstName, $lastName) {
    try {
        $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif'];
        
        // Removed file signature validation for consistency
        if (!file_exists($file['tmp_name']) || $file['size'] <= 0) {
            return ['success' => false, 'message' => 'Invalid file or file is empty'];
        }
        
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            return ['success' => false, 'message' => 'File extension not allowed'];
        }
        
        if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            return ['success' => false, 'message' => 'File size too large (max 5MB)'];
        }
        
        // Create filename with user ID and name convention: user_{userId}_{firstName}_{lastName}_profile.{extension}
        $cleanFirstName = preg_replace('/[^a-zA-Z0-9]/', '', $firstName);
        $cleanLastName = preg_replace('/[^a-zA-Z0-9]/', '', $lastName);
        $fileName = "user_{$userId}_{$cleanFirstName}_{$cleanLastName}_profile." . $fileExtension;
        
        $uploadDir = dirname(__DIR__) . '/uploads/user_profile_picture/';
        $filePath = $uploadDir . $fileName;
        
        // Ensure upload directory exists with proper permissions
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                error_log("Failed to create upload directory: " . $uploadDir);
                return ['success' => false, 'message' => 'Failed to create upload directory'];
            }
        }
        
        // Check if directory is writable
        if (!is_writable($uploadDir)) {
            error_log("Upload directory not writable: " . $uploadDir);
            // Try to fix permissions
            chmod($uploadDir, 0777);
            if (!is_writable($uploadDir)) {
                return ['success' => false, 'message' => 'Upload directory not writable'];
            }
        }
        
        // Log upload attempt for debugging
        error_log("Attempting to upload profile picture: " . $fileName . " to " . $uploadDir);
        error_log("Source file: " . $file['tmp_name'] . " (exists: " . (file_exists($file['tmp_name']) ? 'yes' : 'no') . ")");
        
        // Remove old profile picture if exists
        $oldFiles = glob($uploadDir . "user_{$userId}_*_profile.*");
        foreach ($oldFiles as $oldFile) {
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }
        
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            error_log("Profile picture uploaded successfully: " . $fileName);
            return ['success' => true, 'filename' => $fileName];
        }
        
        error_log("Failed to move uploaded file from " . $file['tmp_name'] . " to " . $filePath);
        return ['success' => false, 'message' => 'Upload failed'];
        
    } catch (Exception $e) {
        error_log("uploadProfilePicture function error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Upload failed due to server error'];
    }
}

function saveProfilePictureToDatabase($db, $userId, $filename) {
    try {
        // Update profile_picture field in users table
        $updateUserStmt = $db->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
        if (!$updateUserStmt) {
            throw new Exception("Failed to prepare user update query: " . $db->error);
        }
        
        $updateUserStmt->bind_param("si", $filename, $userId);
        if (!$updateUserStmt->execute()) {
            throw new Exception("Failed to update user profile_picture field: " . $updateUserStmt->error);
        }
        $updateUserStmt->close();
        
        error_log("Profile picture filename saved to users table: " . $filename . " for user " . $userId);
        
        // Try to create user_profile_photo table if it doesn't exist
        $createTableQuery = "CREATE TABLE IF NOT EXISTS user_profile_photo (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNIQUE,
            photo_filename VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )";
        
        if (!$db->query($createTableQuery)) {
            error_log("Failed to create user_profile_photo table: " . $db->error);
        }
        
        // Try to add updated_at column if it doesn't exist (for existing tables)
        $alterTableQuery = "ALTER TABLE user_profile_photo ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        $db->query($alterTableQuery); // Ignore errors if column already exists
        
        // Insert into user_profile_photo table (optional, since we're saving to users table)
        $insertPhotoStmt = $db->prepare("INSERT INTO user_profile_photo (user_id, photo_filename) VALUES (?, ?) ON DUPLICATE KEY UPDATE photo_filename = VALUES(photo_filename)");
        if ($insertPhotoStmt) {
            $insertPhotoStmt->bind_param("is", $userId, $filename);
            if ($insertPhotoStmt->execute()) {
                error_log("Profile picture record inserted/updated in user_profile_photo table");
            } else {
                error_log("Failed to insert profile photo record: " . $insertPhotoStmt->error);
            }
            $insertPhotoStmt->close();
        }
        
        return ['success' => true];
        
    } catch (Exception $e) {
        error_log("saveProfilePictureToDatabase function error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to save profile picture to database: ' . $e->getMessage()];
    }
}

function processStep1($db) {
    try {
        // Validate POST data exists
        if (!isset($_POST['email']) || !isset($_POST['password']) || !isset($_POST['confirm_password']) ||
            !isset($_POST['first_name']) || !isset($_POST['last_name']) || !isset($_POST['phone']) || !isset($_POST['user_type'])) {
            return ['success' => false, 'errors' => ['Missing required fields']];
        }
        
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];
        $firstName = htmlspecialchars($_POST['first_name']);
        $lastName = htmlspecialchars($_POST['last_name']);
        $phone = htmlspecialchars($_POST['phone']);
        $userType = $_POST['user_type'];
        
        $errors = [];
        
        // Validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        // Robust password validation
        if (empty($password)) {
            $errors[] = "Password is required";
        } else {
            if (strlen($password) < 8) {
                $errors[] = "Password must be at least 8 characters long";
            }
            if (!preg_match('/[a-z]/', $password)) {
                $errors[] = "Password must contain at least one lowercase letter";
            }
            if (!preg_match('/[A-Z]/', $password)) {
                $errors[] = "Password must contain at least one uppercase letter";
            }
            if (!preg_match('/[0-9]/', $password)) {
                $errors[] = "Password must contain at least one number";
            }
            if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
                $errors[] = "Password must contain at least one special character";
            }
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = "Passwords do not match";
        }
        
        if (!preg_match('/^[a-zA-Z\s]+$/', $firstName) || !preg_match('/^[a-zA-Z\s]+$/', $lastName)) {
            $errors[] = "Names can only contain letters and spaces";
        }
        
        if (!preg_match('/^\+?[0-9\s\-\(\)]+$/', $phone)) {
            $errors[] = "Invalid phone number format";
        }
        
        if (!in_array($userType, ['buyer', 'artist'])) {
            $errors[] = "Please select a valid account type (Art Buyer or Artist)";
        }
        
        // Check if email already exists
        $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
        
        if (!$stmt) {
            throw new Exception("Failed to prepare email check query: " . $db->error);
        }
        
        $stmt->bind_param("s", $email);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute email check query: " . $stmt->error);
        }
        
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Email already registered";
        }
        $stmt->close();
        
        // Always store data in session for next step, even if there are errors
        // This allows users to retry without losing their data
        $_SESSION['signup_data'] = [
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'user_type' => $userType,
            'last_attempt' => time() // Add timestamp to track session freshness
        ];
        
        if (empty($errors)) {
            return ['success' => true, 'user_type' => $userType];
        }
        
        return ['success' => false, 'errors' => $errors];
        
    } catch (Exception $e) {
        error_log("processStep1 function error: " . $e->getMessage());
        return ['success' => false, 'errors' => ['Registration failed. Please try again.']];
    }
}

function processProfileUpload($db) {
    try {
        $errors = [];
        
        // Create session data if it doesn't exist for profile upload
        if (!isset($_SESSION['signup_data'])) {
            // For profile upload, we need to recreate the session data from POST
            $_SESSION['signup_data'] = [
                'email' => $_POST['email'] ?? '',
                'password' => password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT),
                'first_name' => $_POST['first_name'] ?? '',
                'last_name' => $_POST['last_name'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'user_type' => $_POST['user_type'] ?? 'buyer',
                'last_attempt' => time()
            ];
        }
        
        // Update session timestamp
        $_SESSION['signup_data']['last_attempt'] = time();
        
        // Complete registration first to get user ID
        $result = completeUserRegistration($db, $_SESSION['signup_data'], true);
        if (!$result['success']) {
            return ['success' => false, 'errors' => [$result['message']]];
        }
        
        $userId = $result['user_id'];
        
        // Handle profile picture upload if provided
        if (!empty($_FILES['profile_picture']['name'])) {
            $uploadResult = uploadProfilePicture(
                $_FILES['profile_picture'], 
                $userId, 
                $_SESSION['signup_data']['first_name'], 
                $_SESSION['signup_data']['last_name']
            );
            
            if (!$uploadResult['success']) {
                $errors[] = $uploadResult['message'];
            } else {
                // Save profile picture filename to database
                $saveResult = saveProfilePictureToDatabase($db, $userId, $uploadResult['filename']);
                if (!$saveResult['success']) {
                    $errors[] = $saveResult['message'];
                }
            }
        }
        
        if (empty($errors)) {
            unset($_SESSION['signup_data']);
            return ['success' => true, 'redirect' => 'login.php'];
        }
        
        return ['success' => false, 'errors' => $errors];
        
    } catch (Exception $e) {
        error_log("processProfileUpload function error: " . $e->getMessage());
        return ['success' => false, 'errors' => ['Registration failed. Please try again.']];
    }
}

function processArtistVerification($db) {
    try {
        // Validate POST data exists
        if (!isset($_POST['national_id']) || !isset($_POST['art_specialty']) || 
            !isset($_POST['years_experience']) || !isset($_POST['bio'])) {
            return ['success' => false, 'errors' => ['Missing required fields']];
        }
        
        $nationalId = htmlspecialchars($_POST['national_id']);
        $artSpecialty = htmlspecialchars($_POST['art_specialty']);
        $yearsExperience = intval($_POST['years_experience']);
        $bio = htmlspecialchars($_POST['bio']);
        
        $errors = [];
        
        // National ID validation - minimum 10 digits
        if (empty($nationalId)) {
            $errors[] = "National ID is required";
        } elseif (!preg_match('/^[0-9]+$/', $nationalId)) {
            $errors[] = "National ID must contain only numbers";
        } elseif (strlen($nationalId) < 10) {
            $errors[] = "National ID must be at least 10 digits long";
        }
        
        // Art specialty validation - must contain characters, not numbers only
        if (empty($artSpecialty)) {
            $errors[] = "Art specialty is required";
        } elseif (preg_match('/^[0-9]+$/', $artSpecialty)) {
            $errors[] = "Art specialty must contain characters, not numbers only";
        } elseif (strlen(trim($artSpecialty)) < 2) {
            $errors[] = "Art specialty must be at least 2 characters long";
        }
        
        // Years of experience validation - 0 to 100
        if ($yearsExperience < 0 || $yearsExperience > 100) {
            $errors[] = "Years of experience must be between 0 and 100";
        }
        
        // Bio validation - minimum 10 words
        if (empty($bio)) {
            $errors[] = "About you section is required";
        } else {
            $wordCount = str_word_count(trim($bio));
            if ($wordCount < 10) {
                $errors[] = "About you section must contain at least 10 words (currently $wordCount words)";
            }
        }
        
        if (empty($errors)) {
            // Complete registration for artist - create session data if it doesn't exist
            if (!isset($_SESSION['signup_data'])) {
                // For artist verification, we need to recreate the session data from POST
                $_SESSION['signup_data'] = [
                    'email' => $_POST['email'] ?? '',
                    'password' => password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT),
                    'first_name' => $_POST['first_name'] ?? '',
                    'last_name' => $_POST['last_name'] ?? '',
                    'phone' => $_POST['phone'] ?? '',
                    'user_type' => $_POST['user_type'] ?? 'artist',
                    'last_attempt' => time()
                ];
            }
            
            // Update session timestamp
            $_SESSION['signup_data']['last_attempt'] = time();
            
            $artistData = $_SESSION['signup_data'];
            $artistData['bio'] = $bio;
            $artistData['art_specialty'] = $artSpecialty;
            $artistData['years_of_experience'] = $yearsExperience;
            $artistData['national_id'] = $nationalId;
            // ID verification documents are handled separately by uploadIdPhotos.php
            
            // Complete registration first to get user ID
            $result = completeUserRegistration($db, $artistData, true);
            if (!$result['success']) {
                return ['success' => false, 'errors' => [$result['message']]];
            }
            
            $userId = $result['user_id'];
            
            // Handle profile picture upload if provided (same as profile_upload step)
            if (!empty($_FILES['profile_picture']['name'])) {
                $uploadResult = uploadProfilePicture(
                    $_FILES['profile_picture'], 
                    $userId, 
                    $artistData['first_name'], 
                    $artistData['last_name']
                );
                
                if (!$uploadResult['success']) {
                    // If profile picture upload fails, add it to errors but don't fail the entire registration
                    error_log("Failed to upload profile picture: " . $uploadResult['message']);
                } else {
                    // Save profile picture filename to database
                    $saveResult = saveProfilePictureToDatabase($db, $userId, $uploadResult['filename']);
                    if (!$saveResult['success']) {
                        error_log("Failed to save profile picture to database: " . $saveResult['message']);
                    }
                }
            }
            
            unset($_SESSION['signup_data']);
            return ['success' => true, 'redirect' => 'login.php', 'user_id' => $userId];
        }
        
        return ['success' => false, 'errors' => $errors];
        
    } catch (Exception $e) {
        error_log("processArtistVerification function error: " . $e->getMessage());
        return ['success' => false, 'errors' => ['Registration failed. Please try again.']];
    }
}

// Main API execution
try {
    // Clean any unwanted output
    ob_clean();
    
    // Check database connection
    if (!isset($db) || $db->connect_error) {
        throw new Exception("Database connection failed: " . ($db->connect_error ?? "Connection not established"));
    }

    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // If user is already authenticated, return error
    if (checkUserAuthentication($db)) {
        echo json_encode(['success' => false, 'message' => 'User already authenticated']);
        exit;
    }

    // Get the action from POST data
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'step1':
            $result = processStep1($db);
            echo json_encode($result);
            break;
            
        case 'profile_upload':
            $result = processProfileUpload($db);
            echo json_encode($result);
            break;
            
        case 'artist_verification':
            $result = processArtistVerification($db);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }

} catch (Exception $e) {
    ob_clean();
    error_log("signup API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred during registration. Please try again.']);
} finally {
    // Always close database connection
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }
    // End output buffering
    ob_end_flush();
}
?>
