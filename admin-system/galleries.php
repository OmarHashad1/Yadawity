<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Management - Yadawity</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/admin-system/galleries.css?v=<?php echo time(); ?>">
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
                <h1 class="page-title">Gallery Management</h1>
                <button class="btn btn-primary" onclick="openAddGalleryModal()">
                    <span class="btn-icon">+</span>
                    Add Gallery
                </button>
            </div>

            <!-- Search and Filters -->
            <div class="search-section">
                <div class="search-container">
                    <div class="search-input-group">
                        <input type="search" class="search-input" id="searchInput" placeholder="Search galleries by title or artist..." aria-label="Search galleries">
                        <button class="search-btn" onclick="searchGalleries()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="filter-group">
                        <select class="filter-select" id="statusFilter" onchange="filterGalleries()">
                            <option value="">All Galleries</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <select class="filter-select" id="typeFilter" onchange="filterGalleries()">
                            <option value="">All Types</option>
                            <option value="virtual">Virtual</option>
                            <option value="physical">Physical</option>
                        </select>
                        <input type="date" class="filter-select" id="dateFilter" onchange="filterGalleries()">
                    </div>
                </div>
            </div>

            <!-- Galleries Table -->
            <div class="table-container">
                <div class="table-card">
                    <div class="table-header">
                        <h3>Galleries</h3>
                        <div class="table-actions">
                            <span class="results-count" id="resultsCount">Loading...</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Artist ID</th>
                                    <th>Type</th>
                                    <th>Price</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Start Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="galleriesTableBody">
                                <tr>
                                    <td colspan="9" class="loading-row">
                                        <div class="loading-spinner"></div>
                                        <span>Loading galleries...</span>
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

    <!-- Gallery Modal -->
    <div class="modal" id="galleryModal" tabindex="-1" aria-labelledby="galleryModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="galleryForm" autocomplete="off">
                    <div class="modal-header">
                        <h5 class="modal-title" id="galleryModalTitle">Add New Gallery</h5>
                        <button type="button" class="modal-close" onclick="closeGalleryModal()" aria-label="Close">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="galleryId">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="title" class="form-label">Title *</label>
                                <input type="text" class="form-input" id="title" required>
                            </div>
                            <div class="form-group">
                                <label for="artistId" class="form-label">Artist ID *</label>
                                <input type="number" class="form-input" id="artistId" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="galleryType" class="form-label">Gallery Type *</label>
                                <select class="form-select" id="galleryType" required>
                                    <option value="virtual">Virtual</option>
                                    <option value="physical">Physical</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="duration" class="form-label">Duration (days) *</label>
                                <input type="number" class="form-input" id="duration" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="price" class="form-label">Price</label>
                                <input type="number" class="form-input" id="price" step="0.01" placeholder="Leave empty for free">
                            </div>
                            <div class="form-group">
                                <label for="startDate" class="form-label">Start Date *</label>
                                <input type="datetime-local" class="form-input" id="startDate" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-checkbox-label">
                                <input type="checkbox" class="form-checkbox" id="isActive" checked>
                                <span class="checkmark"></span>
                                Active Gallery
                            </label>
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-textarea" id="description" rows="3" placeholder="Enter gallery description..."></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-input" id="address" placeholder="Gallery address">
                            </div>
                            <div class="form-group">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-input" id="city" placeholder="Gallery city">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-input" id="phone" placeholder="Contact phone">
                            </div>
                            <div class="form-group">
                                <label for="image" class="form-label">Image URL</label>
                                <input type="url" class="form-input" id="image" placeholder="Gallery image URL">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeGalleryModal()">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveGallery()">Save Gallery</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Custom JS -->
    <script src="galleries.js"></script>
</body>
</html>
