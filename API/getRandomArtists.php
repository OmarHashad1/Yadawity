<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

include 'db.php';

try {
    // Query to get 6 random artists with profile pictures and at least 1 artwork
    // Modified to be more flexible with verification requirements
    $query = "
        SELECT DISTINCT
            u.user_id,
            u.first_name,
            u.last_name,
            u.profile_picture,
            u.art_specialty,
            u.location,
            u.years_of_experience,
            u.bio,
            u.artist_bio,
            (SELECT COUNT(*) FROM artworks a WHERE a.artist_id = u.user_id AND a.is_available = 1) as artwork_count,
            COALESCE(AVG(ar.rating), 0) as average_rating,
            COUNT(ar.rating) as review_count
        FROM users u
        LEFT JOIN artist_reviews ar ON u.user_id = ar.artist_user_id
        WHERE u.user_type = 'artist' 
            AND u.is_active = 1 
            AND (u.is_verified = 1 OR (SELECT COUNT(*) FROM artworks a WHERE a.artist_id = u.user_id AND a.is_available = 1) >= 5)
            AND u.profile_picture IS NOT NULL 
            AND u.profile_picture != ''
            AND (SELECT COUNT(*) FROM artworks a WHERE a.artist_id = u.user_id AND a.is_available = 1) > 1
        GROUP BY u.user_id, u.first_name, u.last_name, u.profile_picture, u.art_specialty, u.location, u.years_of_experience, u.bio, u.artist_bio
        ORDER BY RAND()
        LIMIT 6
    ";
    
    // Use mysqli connection
    $result = mysqli_query($db, $query);
    $artists = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $artists[] = $row;
        }
    }
    
    // Format the data for frontend
    $formattedArtists = [];
    foreach ($artists as $artist) {
        // Handle profile picture path
        $profilePicturePath = '';
        if (!empty($artist['profile_picture'])) {
            // Check if it's already a full path or just filename
            if (strpos($artist['profile_picture'], 'uploads/') === 0 || strpos($artist['profile_picture'], './uploads/') === 0) {
                $profilePicturePath = $artist['profile_picture'];
            } else {
                $profilePicturePath = './uploads/user_profile_picture/' . $artist['profile_picture'];
            }
            
            // Check if file actually exists, if not use fallback
            $fullPath = __DIR__ . '/../uploads/user_profile_picture/' . basename($artist['profile_picture']);
            if (!file_exists($fullPath)) {
                $profilePicturePath = './image/Artist-PainterLookingAtCamera.webp';
            }
        } else {
            $profilePicturePath = './image/Artist-PainterLookingAtCamera.webp';
        }
        
        $formattedArtists[] = [
            'user_id' => $artist['user_id'],
            'name' => trim($artist['first_name'] . ' ' . $artist['last_name']),
            'first_name' => $artist['first_name'],
            'last_name' => $artist['last_name'],
            'profile_picture' => $profilePicturePath,
            'specialty' => $artist['art_specialty'] ?: 'Visual Artist',
            'location' => $artist['location'],
            'years_experience' => $artist['years_of_experience'],
            'bio' => $artist['bio'],
            'artist_bio' => $artist['artist_bio'],
            'artwork_count' => (int)$artist['artwork_count'],
            'average_rating' => round((float)$artist['average_rating'], 1),
            'review_count' => (int)$artist['review_count'],
            'rating_stars' => str_repeat('★', min(5, floor((float)$artist['average_rating']))) . 
                           str_repeat('☆', max(0, 5 - floor((float)$artist['average_rating']))),
            'badge' => $artist['years_of_experience'] >= 10 ? 'Master Artist' : 
                      ($artist['years_of_experience'] >= 5 ? 'Professional Artist' : 'Yadawity Partner')
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $formattedArtists,
        'count' => count($formattedArtists)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching artists: ' . $e->getMessage()
    ]);
}
?>
