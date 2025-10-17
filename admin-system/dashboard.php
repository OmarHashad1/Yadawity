<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Yadawity</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
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

    <!-- Main Wrapper -->
    <div class="main-wrapper">
        <!-- Header -->
        <header class="main-header">
            <nav class="navbar">
                <ul class="navbar-links">
                    <li><a href="/index.php">Home</a></li>
                    <li><a href="/gallery.php">Gallery</a></li>
                   
                    <li><a href="/about.php">About</a></li>
                    <li><a href="/support.php">Support</a></li>
                </ul>
                <div class="navbar-user">
                    <span id="userInfo">Welcome, <?php echo isset($userName) ? $userName : 'Admin'; ?></span>
                    <button class="logout-btn" onclick="logout()">Logout</button>
                </div>
            </nav>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <h1 class="dashboard-title">Dashboard</h1>
            <div class="summary-cards">
                <div class="summary-card primary">
                    <div class="card-title">Total Users</div>
                    <div class="card-value" id="usersTotal">-</div>
                </div>
                <div class="summary-card success">
                    <div class="card-title">Total Artworks</div>
                    <div class="card-value" id="artworksTotal">-</div>
                </div>
                <div class="summary-card info">
                    <div class="card-title">Total Orders</div>
                    <div class="card-value" id="ordersTotal">-</div>
                </div>
                <div class="summary-card warning">
                    <div class="card-title">Total Revenue</div>
                    <div class="card-value" id="revenueTotal">$0</div>
                </div>
            </div>
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="card-header"><h6>User Breakdown</h6></div>
                    <div class="card-body">
                        <div class="metric-row">
                            <span class="metric-label">Artists</span>
                            <span class="metric-value" id="usersArtists">-</span>
                        </div>
                        <div class="metric-row">
                            <span class="metric-label">Buyers</span>
                            <span class="metric-value" id="usersBuyers">-</span>
                        </div>
                        <div class="metric-row">
                            <span class="metric-label">Admins</span>
                            <span class="metric-value" id="usersAdmins">-</span>
                        </div>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="card-header"><h6>Artwork Status</h6></div>
                    <div class="card-body">
                        <div class="metric-row">
                            <span class="metric-label">Available</span>
                            <span class="metric-value" id="artworksAvailable">-</span>
                        </div>
                        <div class="metric-row">
                            <span class="metric-label">On Auction</span>
                            <span class="metric-value" id="artworksOnAuction">-</span>
                        </div>
                        <div class="metric-row">
                            <span class="metric-label">Sold</span>
                            <span class="metric-value" id="artworksSold">-</span>
                        </div>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="card-header"><h6>Orders by Status</h6></div>
                    <div class="card-body">
                        <div id="ordersByStatus"></div>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="card-header"><h6>Auctions</h6></div>
                    <div class="card-body">
                        <div class="metric-row">
                            <span class="metric-label">Active</span>
                            <span class="metric-value" id="auctionsActive">-</span>
                        </div>
                        <div class="metric-row">
                            <span class="metric-label">Upcoming</span>
                            <span class="metric-value" id="auctionsUpcoming">-</span>
                        </div>
                        <div class="metric-row">
                            <span class="metric-label">Sold</span>
                            <span class="metric-value" id="auctionsSold">-</span>
                        </div>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="card-header"><h6>Galleries</h6></div>
                    <div class="card-body">
                        <div class="metric-row">
                            <span class="metric-label">Physical</span>
                            <span class="metric-value" id="galleriesPhysical">-</span>
                        </div>
                        <div class="metric-row">
                            <span class="metric-label">Virtual</span>
                            <span class="metric-value" id="galleriesVirtual">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <!-- Footer -->
        <footer class="dashboard-footer">
            <p>&copy; <?php echo date('Y'); ?> Yadawity. All rights reserved. | <a href="/privacyPolicy.php">Privacy Policy</a> | <a href="/termsOfService.php">Terms of Service</a></p>
        </footer>
    </div>
    <!-- Custom JS -->
    <script src="dashboard.js"></script>
</body>
</html>
