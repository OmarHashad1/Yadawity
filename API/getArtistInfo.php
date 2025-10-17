<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db.php';

function getArtistInfo($artist_id) {
    global $db;
    
    try {
        // Validate input
        if (!$artist_id || !is_numeric($artist_id)) {
            throw new InvalidArgumentException('Valid artist ID is required');
        }
        
        // Check if phone column exists in the database
        $phone_column = '';
        $phone_check = $db->query("SHOW COLUMNS FROM users LIKE 'phone_number'");
        if ($phone_check && $phone_check->num_rows > 0) {
            $phone_column = 'phone_number,';
        } else {
            $alt_phone_check = $db->query("SHOW COLUMNS FROM users LIKE 'phone'");
            if ($alt_phone_check && $alt_phone_check->num_rows > 0) {
                $phone_column = 'phone,';
            }
        }
        
        // First, get basic artist information
        $query = "SELECT 
                    profile_picture,
                    first_name,
                    last_name,
                    email,
                    {$phone_column}
                    art_specialty,
                    years_of_experience,
                    achievements,
                    bio,
                    artist_bio,
                    location,
                    education
                  FROM users 
                  WHERE user_id = ? AND is_active = 1";
        
        $stmt = $db->prepare($query);
        
        if (!$stmt) {
            throw new Exception('Database prepare error: ' . $db->error);
        }
        
        $stmt->bind_param("i", $artist_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Database execution error: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('User not found or inactive', 404);
        }
        
        $artist = $result->fetch_assoc();
        $stmt->close();
        
                // Get artwork count (if artworks table exists)
        $artwork_count = 0;
        
        // Check if artworks table exists
        $table_check = $db->query("SHOW TABLES LIKE 'artworks'");
        if ($table_check && $table_check->num_rows > 0) {
            try {
                $artwork_query = "SELECT COUNT(*) as count FROM artworks WHERE artist_id = ?";
                $stmt = $db->prepare($artwork_query);
                if ($stmt) {
                    $stmt->bind_param("i", $artist_id);
                    if ($stmt->execute()) {
                        $artwork_result = $stmt->get_result();
                        if ($artwork_result) {
                            $artwork_data = $artwork_result->fetch_assoc();
                            $artwork_count = (int)$artwork_data['count'];
                        }
                    }
                    $stmt->close();
                }
            } catch (Exception $e) {
                error_log("Artworks table query failed: " . $e->getMessage());
            }
        }
        
        // Get rating information (if artist_reviews table exists)
        $average_rating = 0;
        $review_count = 0;
        
        // Check if artist_reviews table exists
        $review_table_check = $db->query("SHOW TABLES LIKE 'artist_reviews'");
        if ($review_table_check && $review_table_check->num_rows > 0) {
            try {
                $review_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM artist_reviews WHERE artist_user_id = ?";
                $stmt = $db->prepare($review_query);
                if ($stmt) {
                    $stmt->bind_param("i", $artist_id);
                    if ($stmt->execute()) {
                        $review_result = $stmt->get_result();
                        if ($review_result) {
                            $review_data = $review_result->fetch_assoc();
                            $average_rating = round((float)$review_data['avg_rating'], 1);
                            $review_count = (int)$review_data['count'];
                        }
                    }
                    $stmt->close();
                }
            } catch (Exception $e) {
                error_log("Artist reviews table query failed: " . $e->getMessage());
            }
        }
        
        // Achievements are now handled by dedicated getAchievements.php API
        
        // Format and return the response
        $response_data = [
            'profile_picture' => !empty($artist['profile_picture']) ? './uploads/user_profile_picture/' . $artist['profile_picture'] : './image/Artist-PainterLookingAtCamera.webp',
            'first_name' => $artist['first_name'],
            'last_name' => $artist['last_name'],
            'full_name' => trim($artist['first_name'] . ' ' . $artist['last_name']),
            'email' => $artist['email'],
            'art_specialty' => $artist['art_specialty'], // Don't set default here, let frontend handle it
            'years_of_experience' => (int)$artist['years_of_experience'],
            // achievements removed - now handled by dedicated getAchievements.php API
            'bio' => $artist['bio'],
            'artist_bio' => $artist['artist_bio'],
            'location' => $artist['location'],
            'education' => $artist['education'],
            'artwork_count' => $artwork_count,
            'average_rating' => $average_rating,
            'review_count' => $review_count
        ];
        
        // Add phone data if it exists
        if (isset($artist['phone_number'])) {
            $response_data['phone_number'] = $artist['phone_number'];
        } elseif (isset($artist['phone'])) {
            $response_data['phone'] = $artist['phone'];
        }
        
        return [
            'success' => true,
            'data' => $response_data
        ];
        
    } catch (InvalidArgumentException $e) {
        http_response_code(400);
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    } catch (Exception $e) {
        $code = $e->getCode() === 404 ? 404 : 500;
        http_response_code($code);
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

try {
    // Get artist ID from cookie
    $artist_id = null;
    
    if (isset($_COOKIE['user_login'])) {
        // Extract user ID from the cookie value
        $cookie_value = $_COOKIE['user_login'];
        // The cookie format appears to be: userID_hash, so we extract the number before the first underscore
        $parts = explode('_', $cookie_value);
        if (count($parts) > 0 && is_numeric($parts[0])) {
            $artist_id = (int)$parts[0];
        }
    }
    
    // Fallback to query parameter if cookie is not available
    if (!$artist_id && isset($_GET['artist_id'])) {
        $artist_id = (int)$_GET['artist_id'];
    }
    
    // Get artist information
    $response = getArtistInfo($artist_id);
    
    // Output JSON response
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
} finally {
    if (isset($db) && $db) {
        $db->close();
    }
}
?>