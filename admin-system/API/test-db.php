<?php
require_once "db.php";

header('Content-Type: application/json');

try {
    // Test basic database connection
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('Database connection not available');
    }
    
    // Test if users table exists and has data
    $stmt = $pdo->query('SELECT COUNT(*) FROM users');
    $userCount = $stmt->fetchColumn();
    
    // Test if we can get some user data
    $stmt = $pdo->query('SELECT user_id, email, first_name, last_name, user_type, is_active FROM users LIMIT 5');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful',
        'user_count' => $userCount,
        'sample_users' => $users
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>



