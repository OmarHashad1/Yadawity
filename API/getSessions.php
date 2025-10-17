<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db.php'; // Include database connection

// helper to find a doctor photo in ../uploads/doctors_photo/
function find_doctor_photo($filename) {
    $doctorPhotoDir = __DIR__ . '/../uploads/doctors_photo/';
    if (!$filename) return null;
    $full = $doctorPhotoDir . $filename;
    if (file_exists($full)) return $filename;
    $basename = pathinfo($filename, PATHINFO_FILENAME);
    if (is_dir($doctorPhotoDir)) {
        $matches = glob($doctorPhotoDir . $basename . '.*');
        if ($matches && count($matches) > 0) {
            return basename($matches[0]);
        }
        $files = scandir($doctorPhotoDir);
        if ($files) {
            foreach ($files as $f) {
                if (stripos($f, $basename) !== false) return $f;
            }
        }
    }
    return null;
}

try {
    // Query to get all therapy sessions
    $query = "SELECT * FROM sessions ORDER BY date DESC";
    $result = $db->query($query);

    if (!$result) {
        http_response_code(500);
        echo json_encode(['error' => 'Query failed: ' . $db->error]);
        exit();
    }

    // Fetch all sessions
    $sessions = [];
    while ($row = $result->fetch_assoc()) {
        // Process doctor photo
        $doctorPhoto = find_doctor_photo($row['doctor_photo']);
        $row['doctor_photo'] = $doctorPhoto ? $doctorPhoto : 'placeholder-artwork.jpg';
        $sessions[] = $row;
    }

    // Return success response with sessions
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $sessions,
        'count' => count($sessions)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage()]);
} finally {
    // Close database connection
    $db->close();
}
?>