<?php
require_once "db.php";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit();
}


// Query workshops
$sql = "SELECT workshop_id, title, category, city, street, date, capacity, open_time, price, doctor_name, doctor_description, doctor_photo, workshop_description, created_at, is_active FROM workshops WHERE is_active = 1 ORDER BY date ASC";
$result = $db->query($sql);

// helper to find an image file in ../image/; tries exact name then basename.* matches
function find_image_file($filename) {
	$imageDir = __DIR__ . '/../image/';
	if (!$filename) return null;
	$full = $imageDir . $filename;
	if (file_exists($full)) return $filename;
	// try matching basename with any extension (case-insensitive)
	$basename = pathinfo($filename, PATHINFO_FILENAME);
	$matches = glob($imageDir . $basename . '.*');
	if ($matches && count($matches) > 0) {
		// return the filename portion
		return basename($matches[0]);
	}
	// try case-insensitive search
	$files = scandir($imageDir);
	foreach ($files as $f) {
		if (stripos($f, $basename) !== false) return $f;
	}
	return null;
}

// helper to find a doctor photo in ../uploads/doctors_photo/
function find_doctor_photo($filename) {
	$doctorPhotoDir = __DIR__ . '/../uploads/doctors_photo/';
	if (!$filename) return null;
	
	// First try exact match
	$full = $doctorPhotoDir . $filename;
	if (file_exists($full)) return $filename;
	
	// try matching basename with any extension (case-insensitive)
	$basename = pathinfo($filename, PATHINFO_FILENAME);
	$matches = glob($doctorPhotoDir . $basename . '.*');
	if ($matches && count($matches) > 0) {
		return basename($matches[0]);
	}
	
	// try case-insensitive search
	if (is_dir($doctorPhotoDir)) {
		$files = scandir($doctorPhotoDir);
		if ($files) {
			foreach ($files as $f) {
				if ($f === '.' || $f === '..') continue;
				if (stripos($f, $basename) !== false) return $f;
			}
		}
	}
	
	// Log missing files for debugging
	error_log("Doctor photo not found: " . $filename . " in " . $doctorPhotoDir);
	return null;
}

$workshops = [];
if ($result === false) {
	// SQL error, output error message for debugging
	echo json_encode([
		"success" => false,
		"error" => "Database query failed",
		"sql_error" => $db->error
	]);
	exit;
}

if ($result->num_rows > 0) {
	while ($row = $result->fetch_assoc()) {
		// Resolve doctor image filename
		$doctorPhoto = find_doctor_photo($row['doctor_photo']);
		
		// Debug logging
		error_log("Workshop: " . $row['title'] . " - Doctor photo in DB: " . $row['doctor_photo'] . " - Found: " . ($doctorPhoto ?: 'NOT FOUND'));

	$workshops[] = [
			'id' => $row['workshop_id'],
			'title' => $row['title'],
			'category' => $row['category'],
			'city' => $row['city'],
			'street' => $row['street'],
			'date' => $row['date'],
			'capacity' => (int)$row['capacity'],
			'open_time' => $row['open_time'],
			'price' => (float)$row['price'],
			'doctor_name' => $row['doctor_name'],
			'doctor_description' => $row['doctor_description'],
			'doctor_photo' => $doctorPhoto ? $doctorPhoto : 'placeholder-artwork.jpg', // filename or placeholder
			'workshop_description' => $row['workshop_description'],
			'created_at' => $row['created_at'],
		];
	}
}

echo json_encode(["success" => true, "data" => $workshops]);
exit;
