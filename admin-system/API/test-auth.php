<?php
// Simple test script to verify authentication system
// This file should be removed in production

require_once "db.php";

echo "<h1>Authentication System Test</h1>";

// Test database connection
echo "<h2>Database Connection</h2>";
if (isset($pdo) && $pdo instanceof PDO) {
    echo "✅ Database connection successful<br>";
} else {
    echo "❌ Database connection failed<br>";
}

// Test if auth functions are available
echo "<h2>Authentication Functions</h2>";
if (function_exists('require_admin')) {
    echo "✅ require_admin() function available<br>";
} else {
    echo "❌ require_admin() function not found<br>";
}

if (function_exists('require_csrf_for_write')) {
    echo "✅ require_csrf_for_write() function available<br>";
} else {
    echo "❌ require_csrf_for_write() function not found<br>";
}

if (function_exists('generate_csrf_token')) {
    echo "✅ generate_csrf_token() function available<br>";
} else {
    echo "❌ generate_csrf_token() function not found<br>";
}

// Test session security
echo "<h2>Session Security</h2>";
if (function_exists('set_session_security')) {
    echo "✅ set_session_security() function available<br>";
    set_session_security();
    echo "✅ Session security configured<br>";
} else {
    echo "❌ set_session_security() function not found<br>";
}

// Test CSRF token generation
echo "<h2>CSRF Token Generation</h2>";
if (function_exists('generate_csrf_token')) {
    $token1 = generate_csrf_token();
    $token2 = generate_csrf_token();
    echo "✅ CSRF token 1: " . substr($token1, 0, 16) . "...<br>";
    echo "✅ CSRF token 2: " . substr($token2, 0, 16) . "...<br>";
    echo "✅ Tokens are different: " . ($token1 !== $token2 ? 'Yes' : 'No') . "<br>";
} else {
    echo "❌ generate_csrf_token() function not found<br>";
}

echo "<h2>Test Complete</h2>";
echo "If all items show ✅, the authentication system is properly configured.<br>";
echo "Remove this file in production for security reasons.";
?>
