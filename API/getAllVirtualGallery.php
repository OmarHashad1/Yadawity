<?php
// Suppress all errors to prevent corrupting JSON output
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection
require_once 'db.php';


function getVirtualGalleryQuery() {
    return "SELECT 
    g.gallery_id,
    g.artist_id,
    g.title,
    g.description,
    g.gallery_type,
    g.price,
    g.start_date,
    g.duration,
    g.is_active,
    g.created_at as gallery_created_at,
    g.img as gallery_image_path,
    -- Get primary gallery photo
    (SELECT gp.image_path FROM gallery_photos gp 
     WHERE gp.gallery_id = g.gallery_id AND gp.is_primary = 1 
     LIMIT 1) as gallery_photo,
    -- Get any gallery photo if no primary exists
    (SELECT gp.image_path FROM gallery_photos gp 
     WHERE gp.gallery_id = g.gallery_id 
     ORDER BY gp.is_primary DESC, gp.created_at ASC 
     LIMIT 1) as gallery_photo_fallback,
    -- Artist details with COALESCE to handle nulls
    COALESCE(u.user_id, 0) as artist_user_id,
    COALESCE(u.first_name, '') as artist_first_name,
    COALESCE(u.last_name, '') as artist_last_name,
    u.profile_picture as artist_profile_picture,
    u.profile_picture as artist_img,
    COALESCE(u.location, '') as artist_location,
    COALESCE(u.bio, '') as artist_general_bio,
    COALESCE(u.bio, '') as artist_bio,
    COALESCE(u.art_specialty, '') as art_specialty,
    u.years_of_experience,
    COALESCE(u.bio, '') as artist_education,
    -- Calculate if gallery is currently active based on start_date and duration
    CASE 
        WHEN g.start_date <= NOW() AND 
             DATE_ADD(g.start_date, INTERVAL g.duration MINUTE) > NOW() 
        THEN 1
        ELSE 0 
    END as is_currently_active,
    -- Calculate time remaining in minutes
    CASE 
        WHEN g.start_date <= NOW() AND 
             DATE_ADD(g.start_date, INTERVAL g.duration MINUTE) > NOW() 
        THEN TIMESTAMPDIFF(MINUTE, NOW(), DATE_ADD(g.start_date, INTERVAL g.duration MINUTE))
        ELSE 0 
    END as time_remaining_minutes,
    -- Calculate gallery status
    CASE 
        WHEN g.start_date > NOW() THEN 'upcoming'
        WHEN g.start_date <= NOW() AND 
             DATE_ADD(g.start_date, INTERVAL g.duration MINUTE) > NOW() 
        THEN 'active'
        ELSE 'ended'
    END as gallery_status
FROM galleries g
LEFT JOIN users u ON g.artist_id = u.user_id
WHERE g.gallery_type = 'virtual'
ORDER BY 
    CASE 
        WHEN g.start_date <= NOW() AND 
             DATE_ADD(g.start_date, INTERVAL g.duration MINUTE) > NOW() 
        THEN 1
        WHEN g.start_date > NOW() THEN 2
        ELSE 3
    END,
    g.start_date ASC;";
}


function calculateGalleryEndTime($start_date, $duration) {
    try {
        $start_time = new DateTime($start_date);
        $end_time = clone $start_time;
        $end_time->add(new DateInterval('PT' . $duration . 'M'));
        return $end_time->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return null;
    }
}


function getArtistAchievements($db, $artist_id) {
    try {
        $stmt = $db->prepare("SELECT achievement_name FROM artist_achievements WHERE user_id = ?");
        $stmt->bind_param("i", $artist_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $achievements = array();
        while ($row = $result->fetch_assoc()) {
            $achievements[] = $row['achievement_name'];
        }
        
        return $achievements;
    } catch (Exception $e) {
        return array();
    }
}


function getArtistRating($db, $artist_id) {
    try {
        $stmt = $db->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM artist_reviews WHERE artist_user_id = ?");
        $stmt->bind_param("i", $artist_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return array(
            'average_rating' => $row['avg_rating'] ? round((float)$row['avg_rating'], 1) : 0,
            'total_reviews' => (int)$row['total_reviews']
        );
    } catch (Exception $e) {
        return array('average_rating' => 0, 'total_reviews' => 0);
    }
}


function getGalleryTags($db, $gallery_id) {
    try {
        $stmt = $db->prepare("
            SELECT t.tag_id, t.name 
            FROM gallery_tags gt 
            JOIN tags t ON gt.tag_id = t.tag_id 
            WHERE gt.gallery_id = ?
        ");
        $stmt->bind_param("i", $gallery_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $tags = array();
        while ($row = $result->fetch_assoc()) {
            $tags[] = array(
                'tag_id' => (int)$row['tag_id'],
                'name' => $row['name']
            );
        }
        
        return $tags;
    } catch (Exception $e) {
        return array();
    }
}


function getAllGalleryPhotos($db, $gallery_id) {
    try {
        $stmt = $db->prepare("
            SELECT photo_id, image_path, is_primary, created_at 
            FROM gallery_photos 
            WHERE gallery_id = ? 
            ORDER BY is_primary DESC, created_at ASC
        ");
        $stmt->bind_param("i", $gallery_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $photos = array();
        while ($row = $result->fetch_assoc()) {
            $photos[] = array(
                'photo_id' => (int)$row['photo_id'],
                'image_path' => $row['image_path'],
                'is_primary' => (bool)$row['is_primary'],
                'created_at' => $row['created_at']
            );
        }
        
        return $photos;
    } catch (Exception $e) {
        return array();
    }
}


function formatVirtualGalleryData($db, $row) {
    $end_date = calculateGalleryEndTime($row['start_date'], $row['duration']);
    $achievements = getArtistAchievements($db, $row['artist_id']);
    $rating = getArtistRating($db, $row['artist_id']);
    $tags = getGalleryTags($db, $row['gallery_id']);
    $all_photos = getAllGalleryPhotos($db, $row['gallery_id']);
    
    // Determine the best available photo path
    $gallery_photo = null;
    
    // Priority order: primary gallery photo -> any gallery photo -> gallery img column
    if (!empty($row['gallery_photo'])) {
        $gallery_photo = $row['gallery_photo'];
    } elseif (!empty($row['gallery_photo_fallback'])) {
        $gallery_photo = $row['gallery_photo_fallback'];
    } elseif (!empty($row['gallery_image_path'])) {
        $gallery_photo = $row['gallery_image_path'];
    }
    
    // Determine the best available artist profile picture
    $artist_profile_picture = null;
    if (!empty($row['artist_profile_picture'])) {
        $artist_profile_picture = $row['artist_profile_picture'];
    } elseif (!empty($row['artist_img'])) {
        $artist_profile_picture = $row['artist_img'];
    }
    
    return array(
        'gallery_id' => (int)$row['gallery_id'],
        'artist_id' => (int)$row['artist_id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'gallery_type' => $row['gallery_type'],
        'price' => $row['price'] ? (float)$row['price'] : null,
        'start_date' => $row['start_date'],
        'duration' => (int)$row['duration'], // duration in minutes
        'end_date' => $end_date,
        'is_active' => (bool)$row['is_active'],
        'is_currently_active' => (bool)$row['is_currently_active'],
        'time_remaining_minutes' => (int)$row['time_remaining_minutes'],
        'gallery_status' => $row['gallery_status'],
        'gallery_photo' => $gallery_photo,
        'gallery_photos' => $all_photos,
        'tags' => $tags,
        'artist' => array(
            'artist_id' => (int)$row['artist_user_id'],
            'name' => trim($row['artist_first_name'] . ' ' . $row['artist_last_name']),
            'first_name' => $row['artist_first_name'],
            'last_name' => $row['artist_last_name'],
            'profile_picture' => $artist_profile_picture,
            'specialty' => isset($row['art_specialty']) ? $row['art_specialty'] : '',
            'years_of_experience' => isset($row['years_of_experience']) && $row['years_of_experience'] ? (int)$row['years_of_experience'] : null,
            'location' => $row['artist_location'],
            'bio' => isset($row['artist_bio']) ? $row['artist_bio'] : '',
            'general_bio' => $row['artist_general_bio'],
            'education' => isset($row['artist_education']) ? $row['artist_education'] : '',
            'achievements' => $achievements,
            'rating' => $rating
        ),
        'gallery_created_at' => $row['gallery_created_at'],
        'status' => array(
            'is_premium' => $row['price'] > 0,
            'access_type' => $row['price'] > 0 ? 'paid' : 'free',
            'duration_hours' => round($row['duration'] / 60, 1),
            'duration_days' => round($row['duration'] / (60 * 24), 1),
            'current_status' => $row['gallery_status']
        ),
        'timing' => array(
            'start_date' => $row['start_date'],
            'end_date' => $end_date,
            'duration_minutes' => (int)$row['duration'],
            'time_remaining_minutes' => (int)$row['time_remaining_minutes'],
            'is_currently_active' => (bool)$row['is_currently_active'],
            'status' => $row['gallery_status']
        )
    );
}


function getAllVirtualGalleries($db) {
    try {
        $sql = getVirtualGalleryQuery();
        $result = $db->query($sql);
        
        if (!$result) {
            throw new Exception("Database query failed: " . $db->error);
        }
        
        $galleries = array();
        
        while ($row = $result->fetch_assoc()) {
            $galleries[] = formatVirtualGalleryData($db, $row);
        }
        
        return $galleries;
        
    } catch (Exception $e) {
        throw new Exception("Error fetching virtual galleries: " . $e->getMessage());
    }
}

function sendSuccessResponse($galleries) {
    $active_count = 0;
    $upcoming_count = 0;
    $ended_count = 0;
    
    foreach ($galleries as $gallery) {
        switch ($gallery['gallery_status']) {
            case 'active':
                $active_count++;
                break;
            case 'upcoming':
                $upcoming_count++;
                break;
            case 'ended':
                $ended_count++;
                break;
        }
    }
    
    $response = array(
        'success' => true,
        'message' => 'Virtual galleries retrieved successfully',
        'data' => $galleries,
        'total_count' => count($galleries),
        'statistics' => array(
            'active_galleries' => $active_count,
            'upcoming_galleries' => $upcoming_count,
            'ended_galleries' => $ended_count
        ),
        'timestamp' => date('Y-m-d H:i:s')
    );
    
    echo json_encode($response, JSON_PRETTY_PRINT);
}


function sendErrorResponse($message, $statusCode = 500) {
    $response = array(
        'success' => false,
        'message' => 'Error retrieving virtual galleries',
        'error' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    );
    
    http_response_code($statusCode);
    echo json_encode($response, JSON_PRETTY_PRINT);
}


function handleGetAllVirtualGalleries() {
    global $db;
    
    try {
        // Validate database connection
        if (!$db || $db->connect_error) {
            throw new Exception("Database connection failed: " . ($db ? $db->connect_error : "Connection object not found"));
        }
        
        // Get all virtual galleries
        $galleries = getAllVirtualGalleries($db);
        
        // Send success response
        sendSuccessResponse($galleries);
        
    } catch (Exception $e) {
        // Send error response
        sendErrorResponse($e->getMessage());
    } finally {
        // Close database connection if it exists
        if ($db && !$db->connect_error) {
            $db->close();
        }
    }
}

// Execute the main function
handleGetAllVirtualGalleries();
?>