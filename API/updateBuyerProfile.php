<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db.php';
require_once 'checkCredentials.php';

function updateUserProfile($user_id, $profileData) {
    global $db;
    
    try {
        // Start transaction
        $db->autocommit(false);
        
        // Validate input
        if (!$user_id || !is_numeric($user_id)) {
            throw new InvalidArgumentException('Valid user ID is required');
        }
        
        // Verify user exists and is either a buyer or artist
        $check_query = "SELECT user_id, user_type FROM users WHERE user_id = ? AND (user_type = 'buyer' OR user_type = 'artist') AND is_active = 1";
        $stmt = $db->prepare($check_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('User not found or invalid user type', 404);
        }
        $user_row = $result->fetch_assoc();
        $stmt->close();
        
        // Build update query for users table
        $updateFields = [];
        $params = [];
        $types = "";
        
        // For artists, prevent updating first_name and last_name
        if (isset($profileData['first_name']) && strtolower($user_row['user_type']) !== 'artist') {
            $updateFields[] = "first_name = ?";
            $params[] = $profileData['first_name'];
            $types .= "s";
        }
        
        if (isset($profileData['last_name']) && strtolower($user_row['user_type']) !== 'artist') {
            $updateFields[] = "last_name = ?";
            $params[] = $profileData['last_name'];
            $types .= "s";
        }
        
        if (isset($profileData['email'])) {
            $updateFields[] = "email = ?";
            $params[] = $profileData['email'];
            $types .= "s";
        }
        
        // Try to handle phone number - check if column exists first
        if (isset($profileData['phone'])) {
            // Check if phone column exists
            $column_check = $db->query("SHOW COLUMNS FROM users LIKE 'phone'");
            if ($column_check && $column_check->num_rows > 0) {
                $updateFields[] = "phone = ?";
                $params[] = $profileData['phone'];
                $types .= "s";
            } else {
                // Try alternative column names
                $alt_check = $db->query("SHOW COLUMNS FROM users LIKE 'phone_number'");
                if ($alt_check && $alt_check->num_rows > 0) {
                    $updateFields[] = "phone_number = ?";
                    $params[] = $profileData['phone'];
                    $types .= "s";
                } else {
                    // Log that phone column doesn't exist but don't fail
                    error_log("Phone column not found in users table");
                }
            }
        }
        
        if (isset($profileData['location'])) {
            $updateFields[] = "location = ?";
            $params[] = $profileData['location'];
            $types .= "s";
        }
        
        // Update users table if there are fields to update
        if (!empty($updateFields)) {
            $params[] = $user_id;
            $types .= "i";
            
            $update_query = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE user_id = ?";
            $stmt = $db->prepare($update_query);
            
            if (!$stmt) {
                throw new Exception('Database prepare error: ' . $db->error);
            }
            
            $stmt->bind_param($types, ...$params);
            
            if (!$stmt->execute()) {
                throw new Exception('Database execution error: ' . $stmt->error);
            }
            
            $affected_rows = $stmt->affected_rows;
            $stmt->close();
            
            // Log successful update
            error_log("Buyer profile update successful. Updated fields: " . implode(", ", array_map(function($field) {
                return explode(" = ", $field)[0];
            }, $updateFields)) . ". Affected rows: $affected_rows");
            
        } else {
            // Log that no profile fields were updated
            error_log("No buyer profile fields were updated - all fields either missing or columns don't exist");
        }

        // Commit transaction
        $db->commit();
        $db->autocommit(true);
        
        // Create success message with details
        $updated_fields = [];
        if (!empty($updateFields)) {
            $updated_fields = array_map(function($field) {
                return explode(" = ", $field)[0];
            }, $updateFields);
        }

        $message = 'Profile updated successfully!';
        if (!empty($updated_fields)) {
            $message .= ' Updated: ' . implode(', ', $updated_fields);
        }

        return [
            'success' => true,
            'message' => $message,
            'updated_fields' => $updated_fields
        ];
        
    } catch (InvalidArgumentException $e) {
        $db->rollback();
        $db->autocommit(true);
        http_response_code(400);
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    } catch (Exception $e) {
        $db->rollback();
        $db->autocommit(true);
        $code = $e->getCode() === 404 ? 404 : 500;
        http_response_code($code);
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

try {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests are allowed', 405);
    }
    
    // Use checkCredentials.php for authentication
    $credentials = checkUserCredentials();
    
    // Check if user is authenticated
    if (!$credentials['authenticated']) {
        throw new Exception('Authentication required. Please log in.', 401);
    }
    
    // Check if user is a buyer or artist
    $user_type = strtolower($credentials['user_type']);
    if ($user_type !== 'buyer' && $user_type !== 'artist') {
        throw new Exception('Access denied. This endpoint is only for buyers and artists.', 403);
    }
    
    $user_id = $credentials['user_id'];
    
    // Get profile data from POST request
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data provided', 400);
    }
    
    // Update user profile
    $response = updateUserProfile($user_id, $input);
    
    // Output JSON response
    echo json_encode($response);
    
} catch (Exception $e) {
    $code = $e->getCode() ?: 500;
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} finally {
    if (isset($db) && $db) {
        $db->close();
    }
}
?>
