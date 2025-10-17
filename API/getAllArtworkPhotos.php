<?php
require_once "db.php";

// Set proper headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Function to get filter parameters
function getFilterParameters() {
    return [
        'limit' => isset($_GET['limit']) ? (int)$_GET['limit'] : null,
        'offset' => isset($_GET['offset']) ? (int)$_GET['offset'] : 0,
        'page' => isset($_GET['page']) ? (int)$_GET['page'] : null,
        'artwork_id' => isset($_GET['artwork_id']) ? (int)$_GET['artwork_id'] : null,
        'is_primary' => isset($_GET['is_primary']) ? (bool)$_GET['is_primary'] : null,
        'sort_by' => isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'created_at',
        'sort_order' => isset($_GET['sort_order']) && strtoupper($_GET['sort_order']) === 'ASC' ? 'ASC' : 'DESC'
    ];
}

// Function to build the query
function buildArtworkPhotosQuery($filters) {
    $query = "SELECT 
        photo_id,
        artwork_id,
        image_path,
        is_primary,
        created_at
    FROM artwork_photos WHERE 1=1";
    
    $conditions = [];
    $params = [];
    $types = "";
    
    // Add filters
    if ($filters['artwork_id'] !== null) {
        $conditions[] = "artwork_id = ?";
        $params[] = $filters['artwork_id'];
        $types .= "i";
    }
    
    if ($filters['is_primary'] !== null) {
        $conditions[] = "is_primary = ?";
        $params[] = $filters['is_primary'] ? 1 : 0;
        $types .= "i";
    }
    
    // Add conditions to query
    if (!empty($conditions)) {
        $query .= " AND " . implode(" AND ", $conditions);
    }
    
    // Add sorting
    $allowedSortFields = ['photo_id', 'artwork_id', 'created_at', 'is_primary'];
    $sortBy = in_array($filters['sort_by'], $allowedSortFields) ? $filters['sort_by'] : 'created_at';
    $query .= " ORDER BY " . $sortBy . " " . $filters['sort_order'];
    
    // Add pagination
    if ($filters['limit'] !== null) {
        if ($filters['page'] !== null) {
            $offset = ($filters['page'] - 1) * $filters['limit'];
        } else {
            $offset = $filters['offset'];
        }
        $query .= " LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $filters['limit'];
        $types .= "ii";
    }
    
    return ['query' => $query, 'params' => $params, 'types' => $types];
}

// Function to get total count
function getTotalCount($db, $filters) {
    $countQuery = "SELECT COUNT(*) as total FROM artwork_photos WHERE 1=1";
    $conditions = [];
    $params = [];
    $types = "";
    
    if ($filters['artwork_id'] !== null) {
        $conditions[] = "artwork_id = ?";
        $params[] = $filters['artwork_id'];
        $types .= "i";
    }
    
    if ($filters['is_primary'] !== null) {
        $conditions[] = "is_primary = ?";
        $params[] = $filters['is_primary'] ? 1 : 0;
        $types .= "i";
    }
    
    if (!empty($conditions)) {
        $countQuery .= " AND " . implode(" AND ", $conditions);
    }
    
    $stmt = $db->prepare($countQuery);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['total'];
}

try {
    // Get filter parameters
    $filters = getFilterParameters();
    
    // Build query
    $queryData = buildArtworkPhotosQuery($filters);
    
    // Prepare and execute the main query
    $stmt = $db->prepare($queryData['query']);
    
    if (!empty($queryData['params'])) {
        $stmt->bind_param($queryData['types'], ...$queryData['params']);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Fetch all artwork photos
    $artworkPhotos = [];
    while ($row = $result->fetch_assoc()) {
        // Convert is_primary to boolean for better JSON handling
        $row['is_primary'] = (bool)$row['is_primary'];
        
        // Format the created_at timestamp
        if ($row['created_at']) {
            $row['created_at'] = date('Y-m-d H:i:s', strtotime($row['created_at']));
        }
        
        $artworkPhotos[] = $row;
    }
    
    // Get total count for pagination info
    $totalCount = getTotalCount($db, $filters);
    
    // Calculate pagination info
    $pagination = null;
    if ($filters['limit'] !== null) {
        $currentPage = $filters['page'] ?? (floor($filters['offset'] / $filters['limit']) + 1);
        $totalPages = ceil($totalCount / $filters['limit']);
        
        $pagination = [
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'per_page' => $filters['limit'],
            'total_count' => $totalCount,
            'has_next' => $currentPage < $totalPages,
            'has_previous' => $currentPage > 1
        ];
    }
    
    // Prepare response
    $response = [
        'success' => true,
        'data' => $artworkPhotos,
        'count' => count($artworkPhotos),
        'total_count' => $totalCount,
        'pagination' => $pagination,
        'filters_applied' => array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        })
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // Error response
    $errorResponse = [
        'success' => false,
        'error' => 'Database error occurred',
        'message' => $e->getMessage(),
        'data' => []
    ];
    
    http_response_code(500);
    echo json_encode($errorResponse, JSON_PRETTY_PRINT);
}

// Close database connection
$db->close();
?>
