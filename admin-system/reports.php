<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Yadawity</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/admin-system/reports.css?v=<?php echo time(); ?>">
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
                    <li><a href="/contact.php">Contact</a></li>
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
                <h1 class="page-title">Reports Management</h1>
                <div class="page-actions">
                    <button class="btn btn-success" onclick="exportReport()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7,10 12,15 17,10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        Export
                    </button>
                    <button class="btn btn-primary" onclick="printReport()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6,9 6,2 18,2 18,9"></polyline>
                            <path d="M6,18H4a2,2 0 0,1 -2,-2V11a2,2 0 0,1 2,-2H20a2,2 0 0,1 2,2v5a2,2 0 0,1 -2,2H18"></path>
                            <polyline points="6,14 6,18 18,18 18,14"></polyline>
                        </svg>
                        Print
                    </button>
                </div>
            </div>

            <!-- Report Type Selector -->
            <div class="report-type-selector">
                <h3>Select Report Type</h3>
                <div class="report-types-grid">
                    <div class="report-type-card" onclick="selectReportType('sales_summary')">
                        <div class="report-type-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </div>
                        <div class="report-type-title">Sales Summary</div>
                        <div class="report-type-description">Comprehensive overview of sales performance, revenue trends, and order statistics.</div>
                        <ul class="report-type-features">
                            <li>Daily, weekly, and monthly sales data</li>
                            <li>Revenue analysis and trends</li>
                            <li>Order volume statistics</li>
                            <li>Average order value tracking</li>
                        </ul>
                    </div>

                    <div class="report-type-card" onclick="selectReportType('user_activity')">
                        <div class="report-type-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <div class="report-type-title">User Activity</div>
                        <div class="report-type-description">Track user engagement, login patterns, and activity metrics across the platform.</div>
                        <ul class="report-type-features">
                            <li>User login frequency and patterns</li>
                            <li>Activity tracking and engagement</li>
                            <li>User behavior analysis</li>
                            <li>Session duration statistics</li>
                        </ul>
                    </div>

                    <div class="report-type-card" onclick="selectReportType('artwork_performance')">
                        <div class="report-type-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21,15 16,10 5,21"></polyline>
                            </svg>
                        </div>
                        <div class="report-type-title">Artwork Performance</div>
                        <div class="report-type-description">Analyze artwork sales, views, ratings, and performance metrics.</div>
                        <ul class="report-type-features">
                            <li>Artwork sales and revenue data</li>
                            <li>View count and engagement metrics</li>
                            <li>Rating and review analysis</li>
                            <li>Performance ranking and trends</li>
                        </ul>
                    </div>

                    <div class="report-type-card" onclick="selectReportType('auction_results')">
                        <div class="report-type-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polygon points="10,8 16,12 10,16 10,8"></polygon>
                            </svg>
                        </div>
                        <div class="report-type-title">Auction Results</div>
                        <div class="report-type-description">Comprehensive analysis of auction performance, bidding patterns, and outcomes.</div>
                        <ul class="report-type-features">
                            <li>Auction success rates and outcomes</li>
                            <li>Bidding pattern analysis</li>
                            <li>Revenue from auctions</li>
                            <li>Participant engagement metrics</li>
                        </ul>
                    </div>

                    <div class="report-type-card" onclick="selectReportType('revenue_analysis')">
                        <div class="report-type-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 3v18h18"></path>
                                <path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"></path>
                            </svg>
                        </div>
                        <div class="report-type-title">Revenue Analysis</div>
                        <div class="report-type-description">Detailed revenue breakdown by category, source, and growth analysis.</div>
                        <ul class="report-type-features">
                            <li>Revenue by category and source</li>
                            <li>Growth rate analysis</li>
                            <li>Profit margin calculations</li>
                            <li>Revenue forecasting insights</li>
                        </ul>
                    </div>

                    <div class="report-type-card" onclick="selectReportType('inventory_status')">
                        <div class="report-type-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                                <line x1="3" y1="6" x2="21" y2="6"></line>
                                <path d="M16 10a4 4 0 0 1-8 0"></path>
                            </svg>
                        </div>
                        <div class="report-type-title">Inventory Status</div>
                        <div class="report-type-description">Monitor artwork inventory levels, stock status, and availability tracking.</div>
                        <ul class="report-type-features">
                            <li>Current inventory levels</li>
                            <li>Stock status monitoring</li>
                            <li>Low stock alerts</li>
                            <li>Inventory turnover analysis</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Report Parameters -->
            <div class="report-parameters" id="reportParameters" style="display: none;">
                <h3>Report Parameters</h3>
                <div class="parameters-grid">
                    <div class="parameter-group">
                        <label for="reportType" class="form-label">Report Type</label>
                        <select class="form-select" id="reportType" onchange="loadReport()">
                            <option value="">Select Report Type</option>
                            <option value="sales_summary">Sales Summary</option>
                            <option value="user_activity">User Activity</option>
                            <option value="artwork_performance">Artwork Performance</option>
                            <option value="auction_results">Auction Results</option>
                            <option value="revenue_analysis">Revenue Analysis</option>
                            <option value="inventory_status">Inventory Status</option>
                        </select>
                    </div>
                    <div class="parameter-group">
                        <label for="fromDate" class="form-label">From Date</label>
                        <input type="date" class="form-input" id="fromDate" onchange="loadReport()">
                    </div>
                    <div class="parameter-group">
                        <label for="toDate" class="form-label">To Date</label>
                        <input type="date" class="form-input" id="toDate" onchange="loadReport()">
                    </div>
                </div>
            </div>

            <!-- Generate Report Button -->
            <div class="generate-report-section" id="generateReportSection" style="display: none;">
                <button class="generate-report-btn" onclick="loadReport()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14,2 14,8 20,8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10,9 9,9 8,9"></polyline>
                    </svg>
                    Generate Report
                </button>
            </div>

            <!-- Report Results -->
            <div class="report-results" id="reportResults" style="display: none;">
                <h3 id="reportTitle">Report Results</h3>
                <div id="reportContent">
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14,2 14,8 20,8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10,9 9,9 8,9"></polyline>
                            </svg>
                        </div>
                        <h3>No Report Generated</h3>
                        <p>Select a report type and parameters to generate your report</p>
                    </div>
                </div>
            </div>

            <!-- Report Summary -->
            <div class="report-summary" id="reportSummary" style="display: none;">
                <div class="summary-item">
                    <div class="summary-value" id="totalRecords">-</div>
                    <div class="summary-label">Total Records</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value" id="totalValue">$0</div>
                    <div class="summary-label">Total Value</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value" id="averageValue">$0</div>
                    <div class="summary-label">Average</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value" id="reportStatus">-</div>
                    <div class="summary-label">Status</div>
                </div>
            </div>

            <!-- Export Options -->
            <div class="export-options" id="exportOptions" style="display: none;">
                <h4>Export Report</h4>
                <div class="export-buttons">
                    <button class="export-btn pdf" onclick="exportReport('pdf')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14,2 14,8 20,8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10,9 9,9 8,9"></polyline>
                        </svg>
                        Export PDF
                    </button>
                    <button class="export-btn excel" onclick="exportReport('excel')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14,2 14,8 20,8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10,9 9,9 8,9"></polyline>
                        </svg>
                        Export Excel
                    </button>
                    <button class="export-btn csv" onclick="exportReport('csv')">
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
    <script src="reports.js"></script>
</body>
</html>
