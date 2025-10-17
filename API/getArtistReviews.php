<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

// Use correct database connection variable
$conn = $db;

// Function to validate user cookie and get user ID
function validateUserCookie($conn) {
    if (!isset($_COOKIE['user_login'])) {
        error_log("getArtistReviews: No user_login cookie found");
        return null;
    }
    
    $cookieValue = $_COOKIE['user_login'];
    
    // Extract user ID from cookie (format: user_id_hash)
    $parts = explode('_', $cookieValue, 2);
    if (count($parts) !== 2) {
        return null;
    }
    
    $user_id = (int)$parts[0];
    $provided_hash = $parts[1];
    
    if ($user_id <= 0) {
        return null;
    }
    
    // Get user data
    $stmt = $conn->prepare("SELECT email, is_active FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    $user = $result->fetch_assoc();
    
    // Check if user is active
    if (!$user['is_active']) {
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
    while ($session = $session_result->fetch_assoc()) {
        $cookie_data = $session['session_id'] . '|' . $user['email'] . '|' . $user_id . '|' . $session['login_time'];
        $expected_hash = hash_hmac('sha256', $cookie_data, $session['session_id'] . '_' . $user['email']);
        if ($provided_hash === $expected_hash) {
            return $user_id; // Hash matches one of the active sessions
        }
    }
    
    // No matching hash found
    return null;
}

try {
    // Check if user_id is provided directly in the URL parameter
    if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
        $artist_id = (int)$_GET['user_id'];
        error_log("Using provided user_id as artist_id: " . $artist_id);
        
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
        $artist_id = validateUserCookie($conn);
        
        if (!$artist_id) {
            echo json_encode([
                'success' => false,
                'message' => 'User not authenticated. Please log in.',
                'error_code' => 'AUTHENTICATION_REQUIRED'
            ]);
            exit;
        }
        error_log("Using cookie user_id as artist_id: " . $artist_id);
    }
    
    // Validate database connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection failed");
    }

    // Get filter parameters
    $type = isset($_GET['type']) ? $_GET['type'] : 'all';
    $rating = isset($_GET['rating']) ? intval($_GET['rating']) : 0;
    $date_filter = isset($_GET['date']) ? $_GET['date'] : 'all';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 10;
    $offset = ($page - 1) * $limit;

    // Build WHERE conditions - using artist_user_id from artist_reviews table
    $whereConditions = ["ar.artist_user_id = ?"];
    $params = [$artist_id];
    $paramTypes = "i";

    // Filter by rating
    if ($rating > 0) {
        $whereConditions[] = "ar.rating = ?";
        $params[] = $rating;
        $paramTypes .= "i";
    }

    // Filter by review type
    if ($type !== 'all' && in_array($type, ['course', 'artwork'])) {
        $whereConditions[] = "ar.review_type = ?";
        $params[] = $type;
        $paramTypes .= "s";
    }

    // Filter by date
    switch ($date_filter) {
        case 'week':
            $whereConditions[] = "ar.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            break;
        case 'month':
            $whereConditions[] = "ar.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
        case 'year':
            $whereConditions[] = "ar.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
    }

    // Main query - using artist_reviews table
    $mainQuery = "
        SELECT 
            ar.review_id,
            u.first_name,
            u.last_name,
            CONCAT(u.first_name, ' ', u.last_name) as reviewer_name,
            ar.rating,
            ar.comment as review_text,
            ar.review_type as type,
            ar.created_at as review_date,
            'General Review' as item_title,
            CASE 
                WHEN ar.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) 
                THEN CONCAT(TIMESTAMPDIFF(HOUR, ar.created_at, NOW()), ' hours ago')
                WHEN ar.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)
                THEN CONCAT(TIMESTAMPDIFF(DAY, ar.created_at, NOW()), ' days ago')
                ELSE DATE_FORMAT(ar.created_at, '%M %d, %Y')
            END as time_ago
        FROM artist_reviews ar
        LEFT JOIN users u ON ar.user_id = u.user_id
        WHERE " . implode(' AND ', $whereConditions) . "
        ORDER BY ar.created_at DESC
        LIMIT ? OFFSET ?
    ";

    // Add limit and offset to params
    $params[] = $limit;
    $params[] = $offset;
    $paramTypes .= "ii";

    // Count query
    $countQuery = "
        SELECT COUNT(*) as total
        FROM artist_reviews ar
        WHERE " . implode(' AND ', $whereConditions) . "
    ";

    // Execute count query
    $countParams = array_slice($params, 0, -2); // Remove limit and offset
    $countParamTypes = substr($paramTypes, 0, -2); // Remove 'ii'
    
    $countStmt = $conn->prepare($countQuery);
    if (!empty($countParams)) {
        $countStmt->bind_param($countParamTypes, ...$countParams);
    }
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalRecords = $countResult->fetch_assoc()['total'];
    $countStmt->close();

    $totalPages = ceil($totalRecords / $limit);

    // Execute main query
    $stmt = $conn->prepare($mainQuery);
    $stmt->bind_param($paramTypes, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    $stmt->close();

    // Calculate statistics
    $statsQuery = "
        SELECT 
            COUNT(*) as total_reviews,
            AVG(ar.rating) as avg_rating,
            SUM(CASE WHEN ar.rating >= 4 THEN 1 ELSE 0 END) as positive_reviews,
            SUM(CASE WHEN ar.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH) THEN 1 ELSE 0 END) as recent_reviews
        FROM artist_reviews ar
        WHERE ar.artist_user_id = ?
    ";

    $statsStmt = $conn->prepare($statsQuery);
    $statsStmt->bind_param("i", $artist_id);
    $statsStmt->execute();
    $stats = $statsStmt->get_result()->fetch_assoc();
    $statsStmt->close();

    $totalReviews = $stats['total_reviews'] ?? 0;
    $avgRating = $stats['avg_rating'] ?? 0;
    $positiveReviews = $stats['positive_reviews'] ?? 0;
    $recentReviews = $stats['recent_reviews'] ?? 0;
    $positivePercentage = $totalReviews > 0 ? round(($positiveReviews / $totalReviews) * 100, 1) : 0;

    // Response
    $response = [
        'success' => true,
        'reviews' => $reviews,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'limit' => $limit
        ],
        'statistics' => [
            'total_reviews' => $totalReviews,
            'average_rating' => round($avgRating, 1),
            'positive_percentage' => $positivePercentage,
            'recent_reviews' => $recentReviews
        ],
        'filters_applied' => [
            'type' => $type,
            'rating' => $rating,
            'date' => $date_filter
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
