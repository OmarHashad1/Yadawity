<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=yadawity', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Connection Failed: " . $e->getMessage());
}

// Include authentication helper functions
require_once __DIR__ . '/auth.php';

// Set up session security
set_session_security();
?>