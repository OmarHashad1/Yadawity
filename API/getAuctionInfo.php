<?php
require_once "db.php";

// Set proper headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

function validateAuctionId() {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception("Auction ID is required");
    }
    
    $auction_id = (int)$_GET['id'];
    if ($auction_id <= 0) {
        throw new Exception("Invalid auction ID");
    }
    
    return $auction_id;
}

function buildAuctionQuery() {
    return "
        SELECT 
            au.id as auction_id,
            au.product_id,
            au.artist_id,
            au.starting_bid,
            au.current_bid,
            au.start_time,
            au.end_time,
            au.status as auction_status,
            au.created_at as auction_created_at,
            a.title as artwork_title,
            a.description as artwork_description,
            a.price as artwork_price,
            a.dimensions,
            a.year,
            a.category,
            a.style,
            a.artwork_image,
            a.type as artwork_type,
            a.is_available,
            a.on_auction,
            u.first_name as artist_first_name,
            u.last_name as artist_last_name,
            u.profile_picture as artist_profile_picture,
            u.art_specialty,
            u.years_of_experience,
            u.location as artist_location,
            u.bio as artist_bio,
            u.email as artist_email,
            u.phone as artist_phone,
            COUNT(DISTINCT ar.id) as review_count,
            COALESCE(AVG(ar.rating), 0) as average_rating,
            COUNT(DISTINCT ab.id) as bid_count,
            MAX(ab.bid_amount) as highest_bid
        FROM auctions au
        LEFT JOIN artworks a ON au.product_id = a.artwork_id
        LEFT JOIN users u ON au.artist_id = u.user_id
        LEFT JOIN artist_reviews ar ON au.artist_id = ar.artist_id
        LEFT JOIN auction_bids ab ON au.id = ab.auction_id
        WHERE au.id = ? AND u.is_active = 1
        GROUP BY au.id
    ";
}

function formatAuctionData($row) {
    $auction = [
        'auction_id' => (int)$row['auction_id'],
        'starting_bid' => (float)$row['starting_bid'],
        'current_bid' => (float)$row['current_bid'],
        'formatted_starting_bid' => '$' . number_format((float)$row['starting_bid'], 2),
        'formatted_current_bid' => '$' . number_format((float)$row['current_bid'], 2),
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
        'auction_status' => $row['auction_status'],
        'bid_count' => (int)$row['bid_count'],
        'highest_bid' => (float)$row['highest_bid'],
        'created_at' => $row['auction_created_at'],
        'artwork' => [
            'artwork_id' => (int)$row['product_id'],
            'title' => $row['artwork_title'],
            'description' => $row['artwork_description'],
            'original_price' => (float)$row['artwork_price'],
            'formatted_original_price' => '$' . number_format((float)$row['artwork_price'], 2),
            'dimensions' => $row['dimensions'],
            'year' => $row['year'] ? (int)$row['year'] : null,
            'material' => $row['material'],
            'artwork_image' => $row['artwork_image'],
            'type' => $row['artwork_type'],
            'category' => ucfirst(str_replace('_', ' ', $row['artwork_type'])),
            'is_available' => (bool)$row['is_available'],
            'on_auction' => (bool)$row['on_auction']
        ],
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
    if ($auction['artwork']['artwork_image']) {
        $auction['artwork']['artwork_image_url'] = './image/' . $auction['artwork']['artwork_image'];
        $auction['artwork']['image_src'] = './image/' . $auction['artwork']['artwork_image'];
    } else {
        $auction['artwork']['artwork_image_url'] = './image/placeholder-artwork.jpg';
        $auction['artwork']['image_src'] = './image/placeholder-artwork.jpg';
    }

    // Add artist profile picture URL
    if ($auction['artist']['profile_picture']) {
        $auction['artist']['profile_picture_url'] = './uploads/profiles/' . $auction['artist']['profile_picture'];
    } else {
        $auction['artist']['profile_picture_url'] = './image/default-artist.jpg';
    }

    // Add auction status information
    $now = new DateTime();
    $start_time = new DateTime($auction['start_time']);
    $end_time = new DateTime($auction['end_time']);

    $auction['status'] = [
        'is_active' => $auction['auction_status'] === 'active',
        'is_upcoming' => $start_time > $now,
        'is_ended' => $end_time < $now || $auction['auction_status'] === 'ended',
        'is_cancelled' => $auction['auction_status'] === 'cancelled',
        'time_remaining' => $end_time > $now ? $end_time->diff($now)->format('%d days %h hours %i minutes') : null,
        'status_text' => ucfirst($auction['auction_status'])
    ];

    return $auction;
}

function getAuctionById($db, $auction_id) {
    try {
        $query = buildAuctionQuery();
        $stmt = $db->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $db->error);
        }
        
        $stmt->bind_param("i", $auction_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Query execution failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if (!$row) {
            throw new Exception("Auction not found", 404);
        }
        
        $stmt->close();
        
        return formatAuctionData($row);
        
    } catch (Exception $e) {
        if ($e->getCode() === 404) {
            throw $e;
        }
        throw new Exception("Error fetching auction: " . $e->getMessage());
    }
}

function sendSuccessResponse($auction) {
    $response = [
        'success' => true,
        'message' => 'Auction retrieved successfully',
        'data' => $auction
    ];
    
    http_response_code(200);
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function sendErrorResponse($message, $statusCode = 500) {
    error_log("getAuctionInfo API Error: " . $message);
    
    $response = [
        'success' => false,
        'message' => $message,
        'error_code' => $statusCode === 404 ? 'NOT_FOUND' : 'INTERNAL_ERROR',
        'data' => null
    ];
    
    http_response_code($statusCode);
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function handleGetAuctionInfo() {
    global $db;
    
    try {
        // Validate database connection
        if (!isset($db) || $db->connect_error) {
            throw new Exception("Database connection failed: " . ($db->connect_error ?? "Connection not established"));
        }

        // Validate and get auction ID
        $auction_id = validateAuctionId();

        // Get auction information
        $auction = getAuctionById($db, $auction_id);

        // Send success response
        sendSuccessResponse($auction);

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
handleGetAuctionInfo();
?>