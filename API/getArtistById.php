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

function getArtistById($artist_id) {
    global $db;
    
    try {
        // Validate input
        if (!$artist_id || !is_numeric($artist_id)) {
            throw new InvalidArgumentException('Valid artist ID is required');
        }
        
        // Get basic artist information
        $query = "SELECT 
                    user_id,
                    profile_picture,
                    first_name,
                    last_name,
                    email,
                    art_specialty,
                    years_of_experience,
                    achievements,
                    bio,
                    artist_bio,
                    location,
                    education,
                    created_at
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
            throw new Exception('Artist not found or inactive', 404);
        }
        
        $artist = $result->fetch_assoc();
        $stmt->close();
        
        // Get artwork count
        $artwork_count = 0;
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
        
        // Get rating information from reviews table
        $average_rating = 0;
        $review_count = 0;
        
        try {
            $review_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM reviews WHERE artist_user_id = ? AND review_type = 'artwork'";
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
            error_log("Reviews table query failed: " . $e->getMessage());
        }
        
        // Format and return the response
        $response_data = [
            'artist_id' => (int)$artist['user_id'],
            'profile_picture' => !empty($artist['profile_picture']) ? './uploads/user_profile_picture/' . $artist['profile_picture'] : './image/Artist-PainterLookingAtCamera.webp',
            'first_name' => $artist['first_name'],
            'last_name' => $artist['last_name'],
            'full_name' => trim($artist['first_name'] . ' ' . $artist['last_name']),
            'email' => $artist['email'],
            'art_specialty' => $artist['art_specialty'],
            'years_of_experience' => (int)$artist['years_of_experience'],
            'bio' => $artist['bio'],
            'artist_bio' => $artist['artist_bio'],
            'location' => $artist['location'],
            'education' => $artist['education'],
            'artwork_count' => $artwork_count,
            'average_rating' => $average_rating,
            'review_count' => $review_count,
            'created_at' => $artist['created_at']
        ];
        
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
    // Get artist ID from query parameter
    $artist_id = null;
    
    if (isset($_GET['artist_id'])) {
        $artist_id = (int)$_GET['artist_id'];
    } elseif (isset($_GET['id'])) {
        $artist_id = (int)$_GET['id'];
    }
    
    if (!$artist_id) {
        throw new InvalidArgumentException('Artist ID parameter is required');
    }
    
    // Get artist information
    $response = getArtistById($artist_id);
    
    // Output JSON response
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($db) && $db) {
        $db->close();
    }
}
?>
