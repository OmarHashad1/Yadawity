<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Include database connection
require_once 'db.php';

try {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        exit();
    }
    
    $title = trim($input['title'] ?? '');
    $price = floatval($input['price'] ?? 0);
    $category = trim($input['category'] ?? '');
    $description = trim($input['description'] ?? '');
    $width = isset($input['width']) ? floatval($input['width']) : null;
    $height = isset($input['height']) ? floatval($input['height']) : null;
    $artist_id = intval($input['artist_id'] ?? 0);
    
    if (empty($title) || $price <= 0 || empty($category) || empty($description) || $artist_id <= 0) {
        echo json_encode([
            'isDuplicate' => false,
            'canProceed' => true
        ]);
        exit();
    }
    
    // Check for exact title match (case-insensitive)
    $titleCheckQuery = "
        SELECT artwork_id, title, price, dimensions, created_at 
        FROM artworks 
        WHERE artist_id = ? AND LOWER(title) = LOWER(?) 
        ORDER BY created_at DESC 
        LIMIT 1
    ";
    $titleCheck = $db->prepare($titleCheckQuery);
    if (!$titleCheck) {
        throw new Exception("Database prepare failed: " . $db->error);
    }
    
    $titleCheck->bind_param("is", $artist_id, $title);
    $titleCheck->execute();
    $titleResult = $titleCheck->get_result();
    $exactTitleMatch = $titleResult->fetch_assoc();
    $titleCheck->close();
    
    if ($exactTitleMatch) {
        echo json_encode([
            'isDuplicate' => true,
            'canProceed' => false,
            'reason' => 'You already have an artwork with this exact title.',
            'message' => 'Please use a different title for your artwork.',
            'existingArtwork' => [
                'id' => $exactTitleMatch['artwork_id'],
                'title' => $exactTitleMatch['title'],
                'price' => $exactTitleMatch['price']
            ]
        ]);
        exit();
    }
    
    // Check for similar titles (using SOUNDEX and LIKE comparison)
    $similarTitleQuery = "
        SELECT artwork_id, title, price, dimensions, created_at 
        FROM artworks 
        WHERE artist_id = ? 
        AND artwork_id != COALESCE(?, 0)
        AND (
            SOUNDEX(title) = SOUNDEX(?) 
            OR title LIKE CONCAT('%', ?, '%')
            OR ? LIKE CONCAT('%', title, '%')
        )
        ORDER BY created_at DESC 
        LIMIT 5
    ";
    $similarTitleCheck = $db->prepare($similarTitleQuery);
    if (!$similarTitleCheck) {
        throw new Exception("Database prepare failed: " . $db->error);
    }
    
    $similarTitleCheck->bind_param("iisss", $artist_id, $artist_id, $title, $title, $title);
    $similarTitleCheck->execute();
    $similarResult = $similarTitleCheck->get_result();
    
    while ($similar = $similarResult->fetch_assoc()) {
        $similarity = calculateStringSimilarity($title, $similar['title']);
        if ($similarity > 0.8) { // 80% similarity threshold
            $similarTitleCheck->close();
            echo json_encode([
                'isDuplicate' => true,
                'canProceed' => false,
                'reason' => sprintf('Very similar title found (%.0f%% match).', $similarity * 100),
                'message' => 'Please choose a more distinctive title.',
                'existingArtwork' => [
                    'id' => $similar['artwork_id'],
                    'title' => $similar['title'],
                    'price' => $similar['price']
                ]
            ]);
            exit();
        }
    }
    $similarTitleCheck->close();
    
    // Check for exact same dimensions and price combination
    if ($width && $height) {
        // For simplicity, we'll check if dimensions are in the description or if price is similar
        $dimensionQuery = "
            SELECT artwork_id, title, price, dimensions 
            FROM artworks 
            WHERE artist_id = ? 
            AND ABS(price - ?) < 50 
            ORDER BY created_at DESC 
            LIMIT 5
        ";
        $dimensionCheck = $db->prepare($dimensionQuery);
        if (!$dimensionCheck) {
            throw new Exception("Database prepare failed: " . $db->error);
        }
        
        $dimensionCheck->bind_param("id", $artist_id, $price);
        $dimensionCheck->execute();
        $dimensionResult = $dimensionCheck->get_result();
        
        while ($dimensionMatch = $dimensionResult->fetch_assoc()) {
            // Check if dimensions are similar (basic check)
            $existingDimensions = $dimensionMatch['dimensions'];
            if ($existingDimensions && (strpos($existingDimensions, (string)$width) !== false || strpos($existingDimensions, (string)$height) !== false)) {
                $dimensionCheck->close();
                echo json_encode([
                    'isDuplicate' => true,
                    'canProceed' => false,
                    'reason' => 'Found artwork with similar dimensions and price.',
                    'message' => 'This might be a duplicate of an existing artwork.',
                    'existingArtwork' => [
                        'id' => $dimensionMatch['artwork_id'],
                        'title' => $dimensionMatch['title'],
                        'price' => $dimensionMatch['price']
                    ]
                ]);
                exit();
            }
        }
        $dimensionCheck->close();
    }
    
    // Check for description similarity (only for very similar descriptions)
    $descriptionQuery = "
        SELECT artwork_id, title, description 
        FROM artworks 
        WHERE artist_id = ? 
        AND LENGTH(description) > 50 
        ORDER BY created_at DESC 
        LIMIT 10
    ";
    $descriptionCheck = $db->prepare($descriptionQuery);
    if (!$descriptionCheck) {
        throw new Exception("Database prepare failed: " . $db->error);
    }
    
    $descriptionCheck->bind_param("i", $artist_id);
    $descriptionCheck->execute();
    $descriptionResult = $descriptionCheck->get_result();
    
    while ($existing = $descriptionResult->fetch_assoc()) {
        $descSimilarity = calculateStringSimilarity($description, $existing['description']);
        if ($descSimilarity > 0.9) { // 90% similarity threshold for descriptions
            $descriptionCheck->close();
            echo json_encode([
                'isDuplicate' => true,
                'canProceed' => false,
                'reason' => sprintf('Very similar description found (%.0f%% match).', $descSimilarity * 100),
                'message' => 'Please write a more unique description.',
                'existingArtwork' => [
                    'id' => $existing['artwork_id'],
                    'title' => $existing['title'],
                    'description' => substr($existing['description'], 0, 100) . '...'
                ]
            ]);
            exit();
        }
    }
    $descriptionCheck->close();
    
    // No duplicates found
    echo json_encode([
        'isDuplicate' => false,
        'canProceed' => true
    ]);
    
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode([
        'isDuplicate' => false,
        'canProceed' => true,
        'error' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'isDuplicate' => false,
        'canProceed' => true,
        'error' => 'An error occurred'
    ]);
}

/**
 * Calculate string similarity using Levenshtein distance
 */
function calculateStringSimilarity($str1, $str2) {
    $len1 = strlen($str1);
    $len2 = strlen($str2);
    
    if ($len1 == 0) return $len2 == 0 ? 1.0 : 0.0;
    if ($len2 == 0) return 0.0;
    
    $levenshtein = levenshtein(strtolower($str1), strtolower($str2));
    $maxLen = max($len1, $len2);
    
    return 1.0 - ($levenshtein / $maxLen);
}
?>
