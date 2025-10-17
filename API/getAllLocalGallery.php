<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection
require_once 'db.php';


function getLocalGalleryQuery() {
    return "SELECT 
                g.gallery_id,
                g.artist_id,
                g.title,
                g.description,
                g.gallery_type,
                g.address,
                g.city,
                g.phone,
                g.start_date,
                g.duration,
                g.is_active,
                g.created_at as gallery_created_at,
                g.img as gallery_image_path,
                
                -- Artist details
                u.user_id as artist_user_id,
                u.first_name as artist_first_name,
                u.last_name as artist_last_name,
                u.profile_picture as artist_profile_picture,
                u.art_specialty,
                u.years_of_experience,
                u.location as artist_location,
                u.artist_bio,
                
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
                END as time_remaining_minutes
                
            FROM galleries g
            INNER JOIN users u ON g.artist_id = u.user_id
            WHERE g.gallery_type = 'physical' 
                AND g.is_active = 1 
                AND u.is_active = 1
            ORDER BY 
                CASE 
                    WHEN g.start_date <= NOW() AND 
                         DATE_ADD(g.start_date, INTERVAL g.duration MINUTE) > NOW() 
                    THEN 1
                    WHEN g.start_date > NOW() THEN 2
                    ELSE 3
                END,
                g.start_date ASC";
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


function formatGalleryData($row) {
    $end_date = calculateGalleryEndTime($row['start_date'], $row['duration']);
    
    return array(
        'gallery_id' => (int)$row['gallery_id'],
        'artist_id' => (int)$row['artist_id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'gallery_type' => $row['gallery_type'],
        'address' => $row['address'],
        'city' => $row['city'],
        'phone' => $row['phone'],
        'start_date' => $row['start_date'],
        'duration' => (int)$row['duration'], // duration in minutes
        'end_date' => $end_date,
        'is_active' => (bool)$row['is_active'],
        'is_currently_active' => (bool)$row['is_currently_active'],
        'time_remaining_minutes' => (int)$row['time_remaining_minutes'],
        'gallery_image' => $row['gallery_image_path'], // Added gallery image
        'artist' => array(
            'artist_id' => (int)$row['artist_user_id'],
            'name' => $row['artist_first_name'] . ' ' . $row['artist_last_name'],
            'first_name' => $row['artist_first_name'],
            'last_name' => $row['artist_last_name'],
            'profile_picture' => $row['artist_profile_picture'],
            'specialty' => $row['art_specialty'],
            'years_of_experience' => $row['years_of_experience'] ? (int)$row['years_of_experience'] : null,
            'location' => $row['artist_location'],
            'bio' => $row['artist_bio']
        ),
        'gallery_created_at' => $row['gallery_created_at']
    );
}


function getAllLocalGalleries($db) {
    try {
        $sql = getLocalGalleryQuery();
        $result = $db->query($sql);
        
        if (!$result) {
            throw new Exception("Database query failed: " . $db->error);
        }
        
        $galleries = array();
        
        while ($row = $result->fetch_assoc()) {
            $galleries[] = formatGalleryData($row);
        }
        
        return $galleries;
        
    } catch (Exception $e) {
        throw new Exception("Error fetching local galleries: " . $e->getMessage());
    }
}

function sendSuccessResponse($galleries) {
    $response = array(
        'success' => true,
        'message' => 'Local galleries retrieved successfully',
        'data' => $galleries,
        'total_count' => count($galleries),
        'timestamp' => date('Y-m-d H:i:s')
    );
    
    echo json_encode($response, JSON_PRETTY_PRINT);
}


function sendErrorResponse($message, $statusCode = 500) {
    $response = array(
        'success' => false,
        'message' => 'Error retrieving local galleries',
        'error' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    );
    
    http_response_code($statusCode);
    echo json_encode($response, JSON_PRETTY_PRINT);
}


function handleGetAllLocalGalleries() {
    global $db;
    
    try {
        // Validate database connection
        if (!$db || $db->connect_error) {
            throw new Exception("Database connection failed: " . ($db ? $db->connect_error : "Connection object not found"));
        }
        
        // Get all local galleries
        $galleries = getAllLocalGalleries($db);
        
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
handleGetAllLocalGalleries();
?>