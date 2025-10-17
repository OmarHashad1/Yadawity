<?php
require_once "db.php";

// Set proper headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

function validateArtworkId() {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception("Artwork ID is required");
    }
    
    $artwork_id = (int)$_GET['id'];
    if ($artwork_id <= 0) {
        throw new Exception("Invalid artwork ID");
    }
    
    return $artwork_id;
}

function buildArtworkQuery() {
    return "
        SELECT 
            a.artwork_id,
            a.artist_id,
            a.title,
            a.description,
            a.price,
            a.dimensions,
            a.year,
            a.category,
            a.style,
            a.artwork_image,
            a.type,
            a.is_available,
            a.on_auction,
            a.created_at,
            u.first_name as artist_first_name,
            u.last_name as artist_last_name,
            u.profile_picture as artist_profile_picture,
            u.art_specialty,
            u.years_of_experience,
            u.location as artist_location,
            u.bio as artist_bio,
            u.email as artist_email,
            u.phone as artist_phone,
            COUNT(r.review_id) as review_count,
            COALESCE(AVG(r.rating), 0) as average_rating
        FROM artworks a
        LEFT JOIN users u ON a.artist_id = u.user_id
        LEFT JOIN reviews r ON a.artwork_id = r.artwork_id AND r.review_type = 'artwork'
        WHERE a.artwork_id = ? AND u.is_active = 1
        GROUP BY a.artwork_id
    ";
}

function formatArtworkData($row) {
    $artwork = [
        'artwork_id' => (int)$row['artwork_id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'price' => (float)$row['price'],
        'formatted_price' => '$' . number_format((float)$row['price'], 2),
        'dimensions' => $row['dimensions'],
        'year' => $row['year'] ? (int)$row['year'] : null,
        'category' => $row['category'],
        'style' => $row['style'],
        'material' => ($row['category'] && $row['style']) ? $row['category'] . ', ' . $row['style'] : ($row['category'] ?: $row['style']),
        'artwork_image' => $row['artwork_image'],
        'type' => $row['type'],
        'is_available' => (bool)$row['is_available'],
        'on_auction' => (bool)$row['on_auction'],
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

    // Add image URLs
    if ($artwork['artwork_image']) {
        $artwork['artwork_image_url'] = './uploads/artworks/' . $artwork['artwork_image'];
        $artwork['image_src'] = './uploads/artworks/' . $artwork['artwork_image'];
    } else {
        $artwork['artwork_image_url'] = './image/placeholder-artwork.jpg';
        $artwork['image_src'] = './image/placeholder-artwork.jpg';
    }

    // Add artist profile picture URL
    if ($artwork['artist']['profile_picture']) {
        $artwork['artist']['profile_picture_url'] = './uploads/profiles/' . $artwork['artist']['profile_picture'];
    } else {
        $artwork['artist']['profile_picture_url'] = './image/default-artist.jpg';
    }

    // Add status information
    $artwork['status'] = [
        'is_for_sale' => $artwork['is_available'] && !$artwork['on_auction'],
        'is_on_auction' => $artwork['on_auction'],
        'availability' => $artwork['is_available'] ? 'available' : 'sold',
        'status_text' => $artwork['on_auction'] ? 'On Auction' : ($artwork['is_available'] ? 'Available' : 'Sold')
    ];

    // Add formatted dimensions
    if ($artwork['dimensions']) {
        $artwork['formatted_dimensions'] = $artwork['dimensions'];
    } else {
        $artwork['formatted_dimensions'] = 'Dimensions not specified';
    }

    // Add truncated description for previews
    if ($artwork['description']) {
        $artwork['short_description'] = strlen($artwork['description']) > 120 
            ? substr($artwork['description'], 0, 120) . '...' 
            : $artwork['description'];
    } else {
        $artwork['short_description'] = 'No description available.';
    }

    return $artwork;
}

function getArtworkPhotos($db, $artwork_id) {
    try {
        $query = "
            SELECT 
                photo_id,
                artwork_id,
                image_path,
                is_primary,
                created_at
            FROM artwork_photos 
            WHERE artwork_id = ? 
            ORDER BY is_primary DESC, photo_id ASC
        ";
        
        $stmt = $db->prepare($query);
        if (!$stmt) {
            throw new Exception("Photo query preparation failed: " . $db->error);
        }
        
        $stmt->bind_param("i", $artwork_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Photo query execution failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $photos = [];
        
        while ($row = $result->fetch_assoc()) {
            $photos[] = [
                'photo_id' => (int)$row['photo_id'],
                'artwork_id' => (int)$row['artwork_id'],
                'image_path' => $row['image_path'],
                'photo_url' => './uploads/artworks/' . $row['image_path'],
                'is_primary' => (bool)$row['is_primary'],
                'created_at' => $row['created_at'] ?? null
            ];
        }
        
        $stmt->close();
        
        return $photos;
        
    } catch (Exception $e) {
        error_log("Error fetching artwork photos: " . $e->getMessage());
        return []; // Return empty array if photos can't be fetched
    }
}

function getArtworkById($db, $artwork_id) {
    try {
        $query = buildArtworkQuery();
        $stmt = $db->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $db->error);
        }
        
        $stmt->bind_param("i", $artwork_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Query execution failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if (!$row) {
            throw new Exception("Artwork not found", 404);
        }
        
        $stmt->close();
        
        // Get basic artwork data
        $artwork = formatArtworkData($row);
        
        // Get artwork photos from artwork_photos table
        $artwork['photos'] = getArtworkPhotos($db, $artwork_id);
        
        return $artwork;
        
    } catch (Exception $e) {
        if ($e->getCode() === 404) {
            throw $e;
        }
        throw new Exception("Error fetching artwork: " . $e->getMessage());
    }
}

function sendSuccessResponse($artwork) {
    $response = [
        'success' => true,
        'message' => 'Artwork retrieved successfully',
        'data' => $artwork
    ];
    
    http_response_code(200);
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function sendErrorResponse($message, $statusCode = 500) {
    error_log("getArtworkInfo API Error: " . $message);
    
    $response = [
        'success' => false,
        'message' => $message,
        'error_code' => $statusCode === 404 ? 'NOT_FOUND' : 'INTERNAL_ERROR',
        'data' => null
    ];
    
    http_response_code($statusCode);
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function handleGetArtworkInfo() {
    global $db;
    
    try {
        // Validate database connection
        if (!isset($db) || $db->connect_error) {
            throw new Exception("Database connection failed: " . ($db->connect_error ?? "Connection not established"));
        }

        // Validate and get artwork ID
        $artwork_id = validateArtworkId();

        // Get artwork information
        $artwork = getArtworkById($db, $artwork_id);

        // Send success response
        sendSuccessResponse($artwork);

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
handleGetArtworkInfo();
?>