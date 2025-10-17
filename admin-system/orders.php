<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Yadawity</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/admin-system/orders.css?v=<?php echo time(); ?>">
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
                <h1 class="page-title">Order Management</h1>
                <button class="btn btn-primary" onclick="openAddOrderModal()">
                    <span class="btn-icon">+</span>
                    Add Order
                </button>
            </div>

            <!-- Search and Filters -->
            <div class="search-section">
                <div class="search-container">
                    <div class="search-input-group">
                        <input type="search" class="search-input" id="searchInput" placeholder="Search orders by number or buyer..." aria-label="Search orders">
                        <button class="search-btn" onclick="searchOrders()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="filter-group">
                        <select class="filter-select" id="statusFilter" onchange="filterOrders()">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <input type="date" class="filter-select" id="dateFilter" onchange="filterOrders()">
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="table-container">
                <div class="table-card">
                    <div class="table-header">
                        <h3>Orders</h3>
                        <div class="table-actions">
                            <span class="results-count" id="resultsCount">Loading...</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Order Number</th>
                                    <th>Buyer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="ordersTableBody">
                                <tr>
                                    <td colspan="7" class="loading-row">
                                        <div class="loading-spinner"></div>
                                        <span>Loading orders...</span>
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

    <!-- Order Modal -->
    <div class="modal" id="orderModal" tabindex="-1" aria-labelledby="orderModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="orderForm" autocomplete="off">
                    <div class="modal-header">
                        <h5 class="modal-title" id="orderModalTitle">Add New Order</h5>
                        <button type="button" class="modal-close" onclick="closeOrderModal()" aria-label="Close">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="orderId">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="orderNumber" class="form-label">Order Number *</label>
                                <input type="text" class="form-input" id="orderNumber" required>
                            </div>
                            <div class="form-group">
                                <label for="buyerId" class="form-label">Buyer ID *</label>
                                <input type="number" class="form-input" id="buyerId" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="buyerName" class="form-label">Buyer Name *</label>
                                <input type="text" class="form-input" id="buyerName" required>
                            </div>
                            <div class="form-group">
                                <label for="totalAmount" class="form-label">Total Amount *</label>
                                <input type="number" class="form-input" id="totalAmount" step="0.01" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select" id="status" required>
                                    <option value="pending">Pending</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="shipped">Shipped</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="orderDate" class="form-label">Order Date *</label>
                                <input type="date" class="form-input" id="orderDate" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="shippingAddress" class="form-label">Shipping Address</label>
                            <textarea class="form-textarea" id="shippingAddress" rows="3" placeholder="Enter shipping address..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeOrderModal()">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveOrder()">Save Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Custom JS -->
    <script src="orders.js"></script>
</body>
</html>
