<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auction Management - Yadawity</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/admin-system/auctions.css?v=<?php echo time(); ?>">
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
                <h1 class="page-title">Auction Management</h1>
                <button class="btn btn-primary" onclick="openAddAuctionModal()">
                    <span class="btn-icon">+</span>
                    Add Auction
                </button>
            </div>

            <!-- Search and Filters -->
            <div class="search-section">
                <div class="search-container">
                    <div class="search-input-group">
                        <input type="search" class="search-input" id="searchInput" placeholder="Search auctions by product or artist..." aria-label="Search auctions">
                        <button class="search-btn" onclick="searchAuctions()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="filter-group">
                        <select class="filter-select" id="statusFilter" onchange="filterAuctions()">
                            <option value="">All Statuses</option>
                            <option value="upcoming">Upcoming</option>
                            <option value="starting_soon">Starting Soon</option>
                            <option value="active">Active</option>
                            <option value="sold">Sold</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <input type="date" class="filter-select" id="dateFilter" onchange="filterAuctions()">
                    </div>
                </div>
            </div>

            <!-- Auctions Table -->
            <div class="table-container">
                <div class="table-card">
                    <div class="table-header">
                        <h3>Auctions</h3>
                        <div class="table-actions">
                            <span class="results-count" id="resultsCount">Loading...</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Product ID</th>
                                    <th>Artist ID</th>
                                    <th>Starting Bid</th>
                                    <th>Current Bid</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="auctionsTableBody">
                                <tr>
                                    <td colspan="9" class="loading-row">
                                        <div class="loading-spinner"></div>
                                        <span>Loading auctions...</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-container" id="pagination"></div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="dashboard-footer">
            <p>&copy; <?php echo date('Y'); ?> Yadawity. All rights reserved. | <a href="/privacyPolicy.php">Privacy Policy</a> | <a href="/termsOfService.php">Terms of Service</a></p>
        </footer>
    </div>

    <!-- Auction Modal -->
    <div class="modal" id="auctionModal" tabindex="-1" aria-labelledby="auctionModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="auctionForm" autocomplete="off">
                    <div class="modal-header">
                        <h5 class="modal-title" id="auctionModalTitle">Add New Auction</h5>
                        <button type="button" class="modal-close" onclick="closeAuctionModal()" aria-label="Close">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="auctionId">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="productId" class="form-label">Product ID *</label>
                                <input type="number" class="form-input" id="productId" required>
                            </div>
                            <div class="form-group">
                                <label for="artistId" class="form-label">Artist ID *</label>
                                <input type="number" class="form-input" id="artistId" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="startingBid" class="form-label">Starting Bid *</label>
                                <input type="number" class="form-input" id="startingBid" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label for="currentBid" class="form-label">Current Bid</label>
                                <input type="number" class="form-input" id="currentBid" step="0.01" value="0.00">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="startTime" class="form-label">Start Time *</label>
                                <input type="datetime-local" class="form-input" id="startTime" required>
                            </div>
                            <div class="form-group">
                                <label for="endTime" class="form-label">End Time *</label>
                                <input type="datetime-local" class="form-input" id="endTime" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="status" class="form-label">Status *</label>
                            <select class="form-select" id="status" required>
                                <option value="upcoming">Upcoming</option>
                                <option value="starting_soon">Starting Soon</option>
                                <option value="active">Active</option>
                                <option value="sold">Sold</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeAuctionModal()">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveAuction()">Save Auction</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Custom JS -->
    <script src="auctions.js"></script>
</body>
</html>
