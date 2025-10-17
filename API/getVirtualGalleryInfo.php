<?php
require_once "db.php";

// Set proper headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

function validateGalleryId() {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception("Gallery ID is required");
    }
    
    $gallery_id = (int)$_GET['id'];
    if ($gallery_id <= 0) {
        throw new Exception("Invalid gallery ID");
    }
    
    return $gallery_id;
}

function buildVirtualGalleryQuery() {
    return "
        SELECT 
            g.gallery_id,
            g.artist_id,
            g.title,
            g.description,
            g.gallery_type,
            g.price,
            g.start_date,
            g.duration,
            g.is_active,
            g.created_at,
            u.first_name as artist_first_name,
            u.last_name as artist_last_name,
            u.profile_picture as artist_profile_picture,
            u.art_specialty,
            u.years_of_experience,
            u.location as artist_location,
            u.artist_bio,
            u.email as artist_email,
            u.phone as artist_phone,
            COUNT(DISTINCT ar.id) as review_count,
            COALESCE(AVG(ar.rating), 0) as average_rating,
            CASE 
                WHEN g.start_date <= NOW() AND 
                     DATE_ADD(g.start_date, INTERVAL g.duration MINUTE) > NOW() 
                THEN 1
                ELSE 0 
            END as is_currently_active,
            CASE 
                WHEN g.start_date <= NOW() AND 
                     DATE_ADD(g.start_date, INTERVAL g.duration MINUTE) > NOW() 
                THEN TIMESTAMPDIFF(MINUTE, NOW(), DATE_ADD(g.start_date, INTERVAL g.duration MINUTE))
                ELSE 0 
            END as time_remaining_minutes
        FROM galleries g
        LEFT JOIN users u ON g.artist_id = u.user_id
        LEFT JOIN artist_reviews ar ON g.artist_id = ar.artist_id
        WHERE g.gallery_id = ? AND g.gallery_type = 'virtual'
        GROUP BY g.gallery_id
    ";
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

function formatVirtualGalleryData($row) {
    $end_date = calculateGalleryEndTime($row['start_date'], $row['duration']);
    
    $gallery = [
        'gallery_id' => (int)$row['gallery_id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'gallery_type' => $row['gallery_type'],
        'price' => $row['price'] ? (float)$row['price'] : null,
        'formatted_price' => $row['price'] ? '$' . number_format((float)$row['price'], 2) : 'Free',
        'start_date' => $row['start_date'],
        'duration' => (int)$row['duration'],
        'duration_text' => round($row['duration'] / 60, 1) . ' hour' . ($row['duration'] > 60 ? 's' : ''),
        'end_date' => $end_date,
        'is_active' => (bool)$row['is_active'],
        'is_currently_active' => (bool)$row['is_currently_active'],
        'time_remaining_minutes' => (int)$row['time_remaining_minutes'],
        'created_at' => $row['created_at'],
        'artist' => [
            'artist_id' => (int)$row['artist_id'],
            'first_name' => $row['artist_first_name'],
            'last_name' => $row['artist_last_name'],
            'full_name' => $row['artist_first_name'] . ' ' . $row['artist_last_name'],
            'display_name' => 'By ' . $row['artist_first_name'] . ' ' . $row['artist_last_name'],
            'profile_picture' => $row['artist_profile_picture'],
            'art_specialty' => $row['art_specialty'],
            'years_of_experience' => $row['years_of_experience'] ? (int)$row['years_of_experience'] : null,
            'location' => $row['artist_location'],
            'bio' => $row['artist_bio'],
            'email' => $row['artist_email'],
            'phone' => $row['artist_phone']
        ],
        'reviews' => [
            'count' => (int)$row['review_count'],
            'average_rating' => round((float)$row['average_rating'], 2)
        ]
    ];

    // Add artist profile picture URL
    if ($gallery['artist']['profile_picture']) {
        $gallery['artist']['profile_picture_url'] = './uploads/profiles/' . $gallery['artist']['profile_picture'];
    } else {
        $gallery['artist']['profile_picture_url'] = './image/default-artist.jpg';
    }

    // Add gallery status
    $gallery['status'] = [
        'is_active' => $gallery['is_active'],
        'is_currently_active' => $gallery['is_currently_active'],
        'status_text' => $gallery['is_currently_active'] ? 'Currently Active' : ($gallery['is_active'] ? 'Scheduled' : 'Inactive'),
        'gallery_type_text' => 'Virtual Gallery',
        'access_type' => $gallery['price'] > 0 ? 'Premium - Paid Access' : 'Free Access',
        'is_premium' => $gallery['price'] > 0
    ];

    // Add time information
    if ($gallery['is_currently_active'] && $gallery['time_remaining_minutes'] > 0) {
        $hours = floor($gallery['time_remaining_minutes'] / 60);
        $minutes = $gallery['time_remaining_minutes'] % 60;
        $gallery['time_remaining_text'] = $hours > 0 ? "{$hours}h {$minutes}m remaining" : "{$minutes}m remaining";
    } else {
        $gallery['time_remaining_text'] = null;
    }

    // Add truncated description
    if ($gallery['description']) {
        $gallery['short_description'] = strlen($gallery['description']) > 150 
            ? substr($gallery['description'], 0, 150) . '...' 
            : $gallery['description'];
    } else {
        $gallery['short_description'] = 'No description available.';
    }

    return $gallery;
}

function getVirtualGalleryById($db, $gallery_id) {
    try {
        $query = buildVirtualGalleryQuery();
        $stmt = $db->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $db->error);
        }
        
        $stmt->bind_param("i", $gallery_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Query execution failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if (!$row) {
            throw new Exception("Virtual gallery not found", 404);
        }
        
        $stmt->close();
        
        return formatVirtualGalleryData($row);
        
    } catch (Exception $e) {
        if ($e->getCode() === 404) {
            throw $e;
        }
        throw new Exception("Error fetching virtual gallery: " . $e->getMessage());
    }
}

function sendSuccessResponse($gallery) {
    $response = [
        'success' => true,
        'message' => 'Virtual gallery retrieved successfully',
        'data' => $gallery
    ];
    
    http_response_code(200);
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function sendErrorResponse($message, $statusCode = 500) {
    error_log("getVirtualGalleryInfo API Error: " . $message);
    
    $response = [
        'success' => false,
        'message' => $message,
        'error_code' => $statusCode === 404 ? 'NOT_FOUND' : 'INTERNAL_ERROR',
        'data' => null
    ];
    
    http_response_code($statusCode);
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function handleGetVirtualGalleryInfo() {
    global $db;
    
    try {
        // Validate database connection
        if (!isset($db) || $db->connect_error) {
            throw new Exception("Database connection failed: " . ($db->connect_error ?? "Connection not established"));
        }

        // Validate and get gallery ID
        $gallery_id = validateGalleryId();

        // Get virtual gallery information
        $gallery = getVirtualGalleryById($db, $gallery_id);

        // Send success response
        sendSuccessResponse($gallery);

    } catch (Exception $e) {
        // Send error response
        $statusCode = $e->getCode() === 404 ? 404 : 500;
        sendErrorResponse($e->getMessage(), $statusCode);
    } finally {
        // Close database connection if it exists
        if (isset($db) && !$db->connect_error) {
            $db->close();
        }
    }
}

// Execute the main function
handleGetVirtualGalleryInfo();
?>