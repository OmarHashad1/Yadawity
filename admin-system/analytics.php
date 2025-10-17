<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Yadawity</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/admin-system/analytics.css?v=<?php echo time(); ?>">
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
                    <span id="userInfo">Welcome, Admin</span>
                    <button class="logout-btn" onclick="logout()">Logout</button>
                </div>
            </nav>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Analytics Dashboard</h1>
                <div class="date-range-picker">
                    <input type="date" class="form-input" id="fromDate" onchange="loadAnalytics()">
                    <span class="date-separator">to</span>
                    <input type="date" class="form-input" id="toDate" onchange="loadAnalytics()">
                </div>
            </div>

            <!-- Analytics Cards -->
            <div class="metrics-grid">
                <div class="metric-card primary">
                    <div class="metric-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                        </svg>
                    </div>
                    <div class="metric-value" id="totalOrders">-</div>
                    <div class="metric-label">Total Orders</div>
                    <div class="metric-change positive">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="18,15 12,9 6,15"></polyline>
                        </svg>
                        <span>+12.5%</span>
                    </div>
                </div>

                <div class="metric-card success">
                    <div class="metric-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                    <div class="metric-value" id="totalRevenue">$0</div>
                    <div class="metric-label">Total Revenue</div>
                    <div class="metric-change positive">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="18,15 12,9 6,15"></polyline>
                        </svg>
                        <span>+8.3%</span>
                    </div>
                </div>

                <div class="metric-card info">
                    <div class="metric-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polygon points="10,8 16,12 10,16 10,8"></polygon>
                        </svg>
                    </div>
                    <div class="metric-value" id="activeAuctions">-</div>
                    <div class="metric-label">Active Auctions</div>
                    <div class="metric-change neutral">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        <span>0%</span>
                    </div>
                </div>

                <div class="metric-card warning">
                    <div class="metric-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21,15 16,10 5,21"></polyline>
                        </svg>
                    </div>
                    <div class="metric-value" id="artworksByType">-</div>
                    <div class="metric-label">Artworks by Type</div>
                    <div class="metric-change positive">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="18,15 12,9 6,15"></polyline>
                        </svg>
                        <span>+5.2%</span>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-section">
                <div class="charts-header">
                    <h3>Performance Analytics</h3>
                    <div class="chart-type-selector">
                        <button class="chart-type-btn active" onclick="switchChartType('line')">Line</button>
                        <button class="chart-type-btn" onclick="switchChartType('bar')">Bar</button>
                        <button class="chart-type-btn" onclick="switchChartType('area')">Area</button>
                    </div>
                </div>
                
                <div class="charts-grid">
                    <div class="chart-container">
                        <h4>Daily Orders & Revenue</h4>
                        <canvas id="dailyOrdersChart" width="620" height="400"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <h4>Top Artworks by Sales</h4>
                        <canvas id="topArtworksChart" width="620" height="400"></canvas>
                    </div>
                </div>
            </div>

            <!-- Additional Analytics -->
            <div class="analytics-details">
                <div class="analytics-card">
                    <div class="analytics-card-header">
                        <h4>Auction Status Distribution</h4>
                    </div>
                    <div class="analytics-card-body">
                        <div id="auctionsStatus" class="status-list"></div>
                    </div>
                </div>

                <div class="analytics-card">
                    <div class="analytics-card-header">
                        <h4>Artworks by Category</h4>
                    </div>
                    <div class="analytics-card-body">
                        <div id="artworksByTypeDetails" class="category-list"></div>
                    </div>
                </div>
            </div>

            <!-- Export Section -->
            <div class="export-section">
                <h4>Export Analytics Data</h4>
                <div class="export-buttons">
                    <button class="export-btn pdf" onclick="exportAnalytics('pdf')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14,2 14,8 20,8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10,9 9,9 8,9"></polyline>
                        </svg>
                        Export PDF
                    </button>
                    <button class="export-btn excel" onclick="exportAnalytics('excel')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14,2 14,8 20,8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10,9 9,9 8,9"></polyline>
                        </svg>
                        Export Excel
                    </button>
                    <button class="export-btn csv" onclick="exportAnalytics('csv')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14,2 14,8 20,8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10,9 9,9 8,9"></polyline>
                        </svg>
                        Export CSV
                    </button>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="dashboard-footer">
            <p>&copy; <?php echo date('Y'); ?> Yadawity. All rights reserved. | <a href="/privacyPolicy.php">Privacy Policy</a> | <a href="/termsOfService.php">Terms of Service</a></p>
        </footer>
    </div>

    <!-- Custom JS -->
    <script src="analytics.js"></script>
</body>
</html>
