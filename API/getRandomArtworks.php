<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

include 'db.php';

try {
    // Query to get 9 random artworks with non-null artwork_image
    $query = "
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
            CONCAT(u.first_name, ' ', u.last_name) as artist_name,
            u.profile_picture as artist_profile
        FROM artworks a
        LEFT JOIN users u ON a.artist_id = u.user_id
        WHERE a.artwork_image IS NOT NULL 
        AND a.artwork_image != ''
        AND a.is_available = 1
        AND a.on_auction = 0
        ORDER BY RAND()
        LIMIT 9
    ";
    
    $result = mysqli_query($db, $query);
    
    if (!$result) {
        throw new Exception('Database query failed: ' . mysqli_error($db));
    }
    
    $artworks = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $artworks[] = $row;
    }
    
    // Process the results to format the image paths and other data
    $processedArtworks = [];
    foreach ($artworks as $artwork) {
        // Format the artwork image path and check if file exists
        $imagePath = './uploads/artworks/' . $artwork['artwork_image'];
        $fullImagePath = __DIR__ . '/../uploads/artworks/' . $artwork['artwork_image'];
        
        // Check if the image file actually exists, if not use placeholder
        if (!file_exists($fullImagePath)) {
            $imagePath = './image/placeholder-artwork.jpg';
        }
        
        // Format price
        $formattedPrice = number_format($artwork['price'], 2);
        
        // Format dimensions
        $dimensions = $artwork['dimensions'] ? $artwork['dimensions'] : 'N/A';
        
        // Format artist profile picture
        $artistProfile = $artwork['artist_profile'] 
            ? './uploads/users/' . $artwork['artist_profile']
            : './image/Artist-PainterLookingAtCamera.webp';
        
        $processedArtworks[] = [
            'artwork_id' => (int)$artwork['artwork_id'],
            'artist_id' => (int)$artwork['artist_id'],
            'title' => $artwork['title'],
            'description' => $artwork['description'],
            'price' => $formattedPrice,
            'dimensions' => $dimensions,
            'year' => $artwork['year'],
            'category' => $artwork['category'],
            'style' => $artwork['style'],
            'artwork_image' => $imagePath,
            'type' => $artwork['type'],
            'is_available' => (bool)$artwork['is_available'],
            'on_auction' => (bool)$artwork['on_auction'],
            'artist_name' => $artwork['artist_name'] ?: 'Unknown Artist',
            'artist_profile' => $artistProfile
        ];
    }
    
    if (count($processedArtworks) > 0) {
        echo json_encode([
            'success' => true,
            'data' => $processedArtworks,
            'count' => count($processedArtworks),
            'message' => 'Artworks fetched successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'data' => [],
            'count' => 0,
            'message' => 'No artworks found'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'data' => [],
        'count' => 0,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
