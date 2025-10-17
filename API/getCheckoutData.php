<?php
// API/getCheckoutData.php
header('Content-Type: application/json');
require_once 'db.php'; // adjust path if needed
session_start();
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}
// Get user info
$user_sql = "SELECT name, email, phone, address, photo FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param('i', $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_info = $user_result->fetch_assoc();
// Get cart/order details
$sql = "SELECT a.id, a.name, c.quantity, a.price FROM cart c JOIN artworks a ON c.artwork_id = a.id WHERE c.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$items = [];
$total = 0;
while ($row = $result->fetch_assoc()) {
    $items[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'quantity' => (int)$row['quantity'],
        'price' => (float)$row['price']
    ];
    $total += $row['price'] * $row['quantity'];
}
// Shipping date: 5 days from now
$shipping_date = date('Y-m-d', strtotime('+5 days'));
echo json_encode([
    'success' => true,
    'user' => $user_info,
    'items' => $items,
    'total' => $total,
    'shipping_date' => $shipping_date
]);
