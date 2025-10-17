<?php
// Admin Pending Artists Page
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Artists - Admin</title>
    <link rel="stylesheet" href="admin-shared.css">
    <link rel="stylesheet" href="pending_artists.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
      
   
    
      
    <nav class="navbar">
        <ul class="navbar-links">
            <li><a href="/index.php">Home</a></li>
            <li><a href="/gallery.php">Gallery</a></li>
            <li><a href="/about.php">About</a></li>
            <li><a href="/support.php">Support</a></li>
        </ul>
        <div class="navbar-user">
            <span id="userInfo">Welcome, Admin</span>
            <button class="logout-btn" onclick="logout()">Logout</button>
        </div>
    </nav>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <a href="/index.php" class="logo-text">Yadawity</a>
        </div>
        <ul class="sidebar-links">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="users.php">Users</a></li>
            <li><a href="pending_artists.php" class="active">Pending Artists</a></li>
            <li><a href="artworks.php">Artworks</a></li>
            <li><a href="pending_artworks.php">Pending Artworks</a></li>
            <li><a href="orders.php">Orders</a></li>
            <li><a href="auctions.php">Auctions</a></li>
            <li><a href="galleries.php">Galleries</a></li>
            <li><a href="analytics.php">Analytics</a></li>
            <li><a href="reports.php">Reports</a></li>
        </ul>
    </aside>
    <div class="main-wrapper">
        <main class="main-content">
            <section class="artists-section">
                <div class="section-header">
                    <h2>Review Pending Artists</h2>
                </div>
                <div id="pendingArtistsGrid" class="artists-grid">
                     Artists will be loaded here by JS 
                </div>
            </section>
        </main>
        <footer class="dashboard-footer">
            &copy; 2025 Yadawity Admin. All rights reserved.
        </footer>
    </div>
    <script src="pending_artists.js"></script>
</body>
</html>
