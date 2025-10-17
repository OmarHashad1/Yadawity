<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('ETag: ' . md5(microtime()));

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

// Use correct database connection variable
$conn = $db;

// Function to validate user cookie and get user ID
function validateUserCookie($conn) {
    if (!isset($_COOKIE['user_login'])) {
        error_log("getArtistStatistics: No user_login cookie found");
        return null;
    }
    
    $cookieValue = $_COOKIE['user_login'];
    
    // Extract user ID from cookie (format: user_id_hash)
    $parts = explode('_', $cookieValue, 2);
    if (count($parts) !== 2) {
        error_log("getArtistStatistics: Invalid cookie format");
        return null;
    }
    
    $user_id = (int)$parts[0];
    $provided_hash = $parts[1];
    
    if ($user_id <= 0) {
        error_log("getArtistStatistics: Invalid user_id: " . $user_id);
        return null;
    }
    
    // Get user data
    $stmt = $conn->prepare("SELECT email, is_active FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("getArtistStatistics: User not found: " . $user_id);
        return null;
    }
    
    $user = $result->fetch_assoc();
    
    // Check if user is active
    if (!$user['is_active']) {
        error_log("getArtistStatistics: User not active: " . $user_id);
        return null;
    }
    
    // Get all active login sessions for this user with session_id
    $session_stmt = $conn->prepare("
        SELECT session_id, login_time 
        FROM user_login_sessions 
        WHERE user_id = ? AND is_active = 1 
        ORDER BY login_time DESC
    ");
    $session_stmt->bind_param("i", $user_id);
    $session_stmt->execute();
    $session_result = $session_stmt->get_result();
    
    // Try to validate the hash against any active session using the same method as login
    $valid_session = false;
    $session_count = 0;
    while ($session = $session_result->fetch_assoc()) {
        $session_count++;
        $cookie_data = $session['session_id'] . '|' . $user['email'] . '|' . $user_id . '|' . $session['login_time'];
        $expected_hash = hash_hmac('sha256', $cookie_data, $session['session_id'] . '_' . $user['email']);
        error_log("getArtistStatistics: Session " . $session_count . " - Expected hash: " . $expected_hash . ", Provided: " . $provided_hash);
        if ($provided_hash === $expected_hash) {
            $valid_session = true;
            error_log("getArtistStatistics: Hash matched for session " . $session_count);
            break;
        }
    }
    error_log("getArtistStatistics: Checked " . $session_count . " sessions, valid: " . ($valid_session ? 'true' : 'false'));
    $session_stmt->close();
    
    if (!$valid_session) {
        error_log("getArtistStatistics: No valid session found for user " . $user_id);
        return null;
    }
    
    return $user_id;
}

// Function to get authenticated user ID
function getAuthenticatedUserId($conn) {
    // COMPLETELY destroy any existing session data to prevent contamination
    $_SESSION = array();
    
    // Destroy the session cookie if it exists
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session completely
    session_destroy();
    
    // Start fresh session to ensure no contamination
    session_start();
    
    // ONLY use cookie authentication - no session fallbacks
    $cookie_user_id = validateUserCookie($conn);
    if ($cookie_user_id) {
        error_log("getAuthenticatedUserId: Using cookie authentication for user_id = " . $cookie_user_id);
        return $cookie_user_id;
    }
    
    error_log("getAuthenticatedUserId: No valid authentication found");
    return null;
}

function getArtistProducts($conn, $artist_id) {
    try {
        // Debug logging
        error_log("getArtistProducts: Using artist_id = " . $artist_id);
        
        $query = "
            SELECT 
                a.artwork_id,
                a.title,
                a.description,
                a.price,
                a.dimensions,
                a.artwork_image,
                a.type,
                a.is_available,
                a.on_auction,
                a.created_at,
                auc.id as auction_id,
                0 as cart_count,
                0 as wishlist_count,
                COUNT(DISTINCT CASE WHEN o.status IN ('paid', 'shipped', 'delivered') THEN o.id END) as sales_count,
                COALESCE(SUM(CASE WHEN o.status IN ('paid', 'shipped', 'delivered') THEN oi.price * oi.quantity END), 0) as total_earnings
            FROM artworks a
            LEFT JOIN auctions auc ON a.artwork_id = auc.product_id AND a.artist_id = auc.artist_id
            LEFT JOIN order_items oi ON a.artwork_id = oi.artwork_id
            LEFT JOIN orders o ON oi.order_id = o.id
            WHERE a.artist_id = ? 
            AND (auc.id IS NULL OR auc.status NOT IN ('upcoming', 'starting_soon', 'active'))
            GROUP BY a.artwork_id
            ORDER BY a.created_at DESC
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $artist_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        error_log("getArtistStatistics: Querying artworks for artist_id: " . $artist_id);
        error_log("getArtistStatistics: Query result count: " . $result->num_rows);
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $status = 'Draft';
            if ($row['on_auction']) {
                $status = 'On Auction';
            } elseif ($row['is_available']) {
                $status = 'Available';
            } else {
                $status = 'Sold';
            }
            
            $products[] = [
                'id' => $row['artwork_id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'price' => (float)$row['price'],
                'dimensions' => $row['dimensions'],
                'image' => $row['artwork_image'] ? '/uploads/artworks/' . $row['artwork_image'] : '/image/placeholder-artwork.jpg',
                'type' => $row['type'],
                'status' => $status,
                'auction_id' => $row['auction_id'], // Include auction_id for items that are on auction
                'on_auction' => (bool)$row['on_auction'], // Include on_auction flag
                'cart_count' => (int)$row['cart_count'],
                'wishlist_count' => (int)$row['wishlist_count'],
                'sales_count' => (int)$row['sales_count'],
                'total_earnings' => (float)$row['total_earnings'],
                'created_at' => $row['created_at']
            ];
        }
        
        $stmt->close();
        
        // Debug logging
        error_log("getArtistProducts: Found " . count($products) . " products for artist_id " . $artist_id);
        
        return $products;
        
    } catch (Exception $e) {
        throw new Exception("Error fetching artist products: " . $e->getMessage());
    }
}

function getArtistGalleries($conn, $artist_id) {
    try {
        $query = "
            SELECT 
                g.gallery_id as id,
                g.title,
                g.description,
                g.gallery_type,
                g.address,
                g.city,
                g.phone,
                g.price,
                g.duration,
                g.is_active,
                g.created_at,
                g.img,
                0 as artwork_count,
                0 as enrolled_count,
                0 as cart_count,
                0 as wishlist_count
            FROM galleries g
            WHERE g.artist_id = ?
            ORDER BY g.created_at DESC
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $artist_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $virtual_galleries = [];
        $local_galleries = [];
        
        while ($row = $result->fetch_assoc()) {
            // Handle gallery image path - check if it already contains the full path
            $gallery_image = '';
            if ($row['img']) {
                if (strpos($row['img'], 'uploads/galleries/') === 0) {
                    // Already contains full path, just add leading slash
                    $gallery_image = '/' . $row['img'];
                } else {
                    // Just filename, prepend the uploads/galleries/ path
                    $gallery_image = '/uploads/galleries/' . $row['img'];
                }
            } else {
                $gallery_image = '/image/default-gallery.jpg';
            }
            
            $gallery_data = [
                'id' => $row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'price' => (float)$row['price'],
                'duration' => (int)$row['duration'],
                'artwork_count' => (int)$row['artwork_count'],
                'enrolled_count' => (int)$row['enrolled_count'],
                'cart_count' => (int)$row['cart_count'],
                'wishlist_count' => (int)$row['wishlist_count'],
                'created_at' => $row['created_at'],
                'image' => $gallery_image
            ];
            
            if ($row['gallery_type'] === 'virtual') {
                $gallery_data['status'] = $row['is_active'] ? 'Published' : 'Draft';
                $virtual_galleries[] = $gallery_data;
            } else { // physical gallery
                $gallery_data['address'] = $row['address'];
                $gallery_data['city'] = $row['city'];
                $gallery_data['phone'] = $row['phone'];
                $gallery_data['status'] = $row['is_active'] ? 'Approved' : 'Pending Approval';
                $local_galleries[] = $gallery_data;
            }
        }
        
        $stmt->close();
        
        return [
            'virtual_galleries' => $virtual_galleries,
            'local_galleries' => $local_galleries
        ];
        
    } catch (Exception $e) {
        throw new Exception("Error fetching artist galleries: " . $e->getMessage());
    }
}

function getArtistAuctions($conn, $artist_id) {
    try {
        $query = "
            SELECT 
                auc.id as auction_id,
                auc.product_id as artwork_id,
                auc.starting_bid,
                auc.current_bid,
                auc.start_time,
                auc.end_time,
                auc.status as auction_status,
                auc.created_at as auction_created_at,
                a.title as artwork_title,
                a.description as artwork_description,
                a.price as artwork_original_price,
                a.artwork_image,
                a.type as artwork_type,
                a.dimensions,
                COUNT(ab.id) as bid_count
            FROM auctions auc
            JOIN artworks a ON auc.product_id = a.artwork_id
            LEFT JOIN auction_bids ab ON auc.id = ab.auction_id
            WHERE auc.artist_id = ?
            GROUP BY auc.id
            ORDER BY auc.created_at DESC
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $artist_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $auctions = [];
        while ($row = $result->fetch_assoc()) {
            // Determine auction status display
            $status_display = '';
            switch ($row['auction_status']) {
                case 'upcoming':
                    $status_display = 'Upcoming';
                    break;
                case 'starting_soon':
                    $status_display = 'Starting Soon';
                    break;
                case 'active':
                    $status_display = 'Active';
                    break;
                case 'sold':
                    $status_display = 'Sold';
                    break;
                case 'cancelled':
                    $status_display = 'Cancelled';
                    break;
                default:
                    $status_display = ucfirst($row['auction_status']);
            }
            
            $auctions[] = [
                'auction_id' => (int)$row['auction_id'],
                'artwork_id' => (int)$row['artwork_id'],
                'artwork_title' => $row['artwork_title'],
                'artwork_description' => $row['artwork_description'],
                'artwork_type' => $row['artwork_type'],
                'dimensions' => $row['dimensions'],
                'image' => $row['artwork_image'] ? '/uploads/artworks/' . $row['artwork_image'] : '/image/placeholder-artwork.jpg',
                'original_price' => (float)$row['artwork_original_price'],
                'starting_bid' => (float)$row['starting_bid'],
                'current_bid' => (float)$row['current_bid'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time'],
                'status' => $row['auction_status'],
                'status_display' => $status_display,
                'bid_count' => (int)$row['bid_count'],
                'created_at' => $row['auction_created_at']
            ];
        }
        
        $stmt->close();
        
        error_log("getArtistAuctions: Found " . count($auctions) . " auctions for artist_id " . $artist_id);
        
        return $auctions;
        
    } catch (Exception $e) {
        throw new Exception("Error fetching artist auctions: " . $e->getMessage());
    }
}

function getArtistSummaryStats($conn, $artist_id) {
    try {
        // Get total products (excluding those in active auctions)
        $products_query = "
            SELECT COUNT(*) as total_products 
            FROM artworks a
            LEFT JOIN auctions auc ON a.artwork_id = auc.product_id AND a.artist_id = auc.artist_id
            WHERE a.artist_id = ? 
            AND (auc.id IS NULL OR auc.status NOT IN ('upcoming', 'starting_soon', 'active'))
        ";
        $stmt = $conn->prepare($products_query);
        $stmt->bind_param("i", $artist_id);
        $stmt->execute();
        $total_products = $stmt->get_result()->fetch_assoc()['total_products'];
        $stmt->close();
        
        // Get total galleries
        $galleries_query = "SELECT COUNT(*) as total_galleries FROM galleries WHERE artist_id = ?";
        $stmt = $conn->prepare($galleries_query);
        $stmt->bind_param("i", $artist_id);
        $stmt->execute();
        $total_galleries = $stmt->get_result()->fetch_assoc()['total_galleries'];
        $stmt->close();
        
        // Get total auctions
        $auctions_query = "SELECT COUNT(*) as total_auctions FROM auctions WHERE artist_id = ?";
        $stmt = $conn->prepare($auctions_query);
        $stmt->bind_param("i", $artist_id);
        $stmt->execute();
        $total_auctions = $stmt->get_result()->fetch_assoc()['total_auctions'];
        $stmt->close();
        
        // Get total sales
        $sales_query = "
            SELECT 
                COUNT(DISTINCT o.id) as total_sales,
                COALESCE(SUM(oi.price * oi.quantity), 0) as total_revenue
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN artworks a ON oi.artwork_id = a.artwork_id
            WHERE a.artist_id = ? AND o.status IN ('paid', 'shipped', 'delivered')
        ";
        $stmt = $conn->prepare($sales_query);
        $stmt->bind_param("i", $artist_id);
        $stmt->execute();
        $sales_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // Get total cart and wishlist counts (set to 0 for now since tables may not exist)
        $engagement_data = [
            'total_wishlist' => 0,
            'total_cart' => 0
        ];
        
        return [
            'total_artworks' => (int)$total_products,
            'total_galleries' => (int)$total_galleries,
            'total_auctions' => (int)$total_auctions,
            'total_sales' => (int)$sales_data['total_sales'],
            'total_revenue' => (float)$sales_data['total_revenue'],
            'total_wishlist' => (int)$engagement_data['total_wishlist'],
            'total_cart' => (int)$engagement_data['total_cart']
        ];
        
    } catch (Exception $e) {
        throw new Exception("Error fetching artist summary stats: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Check if user_id is provided directly in the URL parameter
        if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
            $artist_id = (int)$_GET['user_id'];
            error_log("Using provided user_id: " . $artist_id);
            
            // Validate that this user exists and is active
            $user_check_stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND is_active = 1");
            $user_check_stmt->bind_param("i", $artist_id);
            $user_check_stmt->execute();
            $user_check_result = $user_check_stmt->get_result();
            
            if ($user_check_result->num_rows === 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'User not found or inactive.',
                    'error_code' => 'USER_NOT_FOUND'
                ]);
                exit;
            }
        } else {
            // Fall back to cookie validation
            $artist_id = getAuthenticatedUserId($conn);
            
            // Debug logging
            error_log("getArtistStatistics: Authenticated artist_id = " . ($artist_id ?? 'null'));
            if (isset($_COOKIE['user_login'])) {
                error_log("getArtistStatistics: Cookie present = " . substr($_COOKIE['user_login'], 0, 10) . "...");
            }
            
            if (!$artist_id) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'User not authenticated. Please log in.',
                    'error_code' => 'AUTHENTICATION_REQUIRED'
                ]);
                exit;
            }
        }
        
        // Validate database connection
        if (!isset($conn) || $conn->connect_error) {
            throw new Exception("Database connection failed");
        }
        
        // Add final confirmation of artist ID
        error_log("getArtistStatistics: Final artist_id being used for queries: " . $artist_id);
        
        // Get artist info
        $artist_info_query = "SELECT first_name, last_name, email FROM users WHERE user_id = ? AND is_active = 1";
        $artist_stmt = $conn->prepare($artist_info_query);
        $artist_stmt->bind_param("i", $artist_id);
        $artist_stmt->execute();
        $artist_result = $artist_stmt->get_result();
        
        if ($artist_result->num_rows === 0) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Artist not found or inactive.',
                'error_code' => 'ARTIST_NOT_FOUND'
            ]);
            exit;
        }
        
        $artist_info = $artist_result->fetch_assoc();
        
        // Get all data
        $products = getArtistProducts($conn, $artist_id);
        $galleries = getArtistGalleries($conn, $artist_id);
        $auctions = getArtistAuctions($conn, $artist_id);
        $summary_stats = getArtistSummaryStats($conn, $artist_id);
        
        $response = [
            'success' => true,
            'message' => 'Artist statistics retrieved successfully',
            'data' => [
                'artist_id' => $artist_id,
                'artist_info' => $artist_info,
                'summary' => $summary_stats,
                'products' => $products,
                'auctions' => $auctions,
                'virtual_galleries' => $galleries['virtual_galleries'],
                'local_galleries' => $galleries['local_galleries']
            ]
        ];
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        error_log("Artist Statistics API Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error retrieving artist statistics: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

$conn->close();
?>
