<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Yadawity</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="users.css">
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
                <h1 class="page-title">User Management</h1>
                <button class="btn btn-primary" onclick="openAddUserModal()">
                    <span class="btn-icon">+</span>
                    Add User
                </button>
            </div>

            <!-- Search and Filters -->
            <div class="search-section">
                <div class="search-container">
                    <div class="search-input-group">
                        <input type="search" class="search-input" id="searchInput" placeholder="Search users by name or email..." aria-label="Search users">
                        <button class="search-btn" onclick="searchUsers()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="filter-group">
                        <select class="filter-select" id="statusFilter" onchange="filterUsers()">
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <select class="filter-select" id="typeFilter" onchange="filterUsers()">
                            <option value="">All Types</option>
                            <option value="buyer">Buyer</option>
                            <option value="artist">Artist</option>
                            <option value="admin">Admin</option>
                        </select>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearAllFilters()" id="clearFiltersBtn" style="display: none;">
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="table-container">
                <div class="table-card">
                    <div class="table-header">
                        <h3>Users</h3>
                        <div class="table-actions">
                            <span class="results-count" id="resultsCount">Loading...</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                <tr>
                                    <td colspan="7" class="loading-row">
                                        <div class="loading-spinner"></div>
                                        <span>Loading users...</span>
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

    <!-- User Modal -->
    <div class="modal" id="userModal" tabindex="-1" aria-labelledby="userModalTitle" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="userForm" autocomplete="off">
                    <div class="modal-header">
                        <h5 class="modal-title" id="userModalTitle">Add New User</h5>
                        <button type="button" class="modal-close" onclick="closeUserModal()" aria-label="Close">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="userId">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="firstName" class="form-label">First Name *</label>
                                <input type="text" class="form-input" id="firstName" required>
                            </div>
                            <div class="form-group">
                                <label for="lastName" class="form-label">Last Name *</label>
                                <input type="text" class="form-input" id="lastName" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-input" id="email" required>
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Password <span class="text-muted">(leave blank to keep existing)</span></label>
                            <input type="password" class="form-input" id="password">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="userType" class="form-label">User Type *</label>
                                <select class="form-select" id="userType" required>
                                    <option value="buyer">Buyer</option>
                                    <option value="artist">Artist</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-input" id="phone">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-checkbox-label">
                                <input type="checkbox" class="form-checkbox" id="isActive" checked>
                                <span class="checkmark"></span>
                                Active Account
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeUserModal()">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveUser()">Save User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Custom JS -->
    <script src="users.js"></script>
</body>
</html>
</html>