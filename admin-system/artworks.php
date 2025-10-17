<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artwork Management - Yadawity</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="artworks.css">
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
                <h1 class="page-title">Artwork Management</h1>
                <button class="btn btn-primary" onclick="openAddArtworkModal()">
                    <span class="btn-icon">+</span>
                    Add Artwork
                </button>
            </div>

            <!-- Search and Filters -->
            <div class="search-section">
                <div class="search-container">
                    <div class="search-input-group">
                        <input type="search" class="search-input" id="searchInput" placeholder="Search artworks by title or artist..." aria-label="Search artworks">
                        <button class="search-btn" onclick="searchArtworks()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="filter-group">
                        <select class="filter-select" id="typeFilter" onchange="filterArtworks()">
                            <option value="">All Types</option>
                            <option value="painting">Painting</option>
                            <option value="sculpture">Sculpture</option>
                            <option value="photography">Photography</option>
                            <option value="digital">Digital</option>
                            <option value="mixed_media">Mixed Media</option>
                            <option value="other">Other</option>
                        </select>
                        <select class="filter-select" id="statusFilter" onchange="filterArtworks()">
                            <option value="">All Status</option>
                            <option value="1">Available</option>
                            <option value="0">Unavailable</option>
                        </select>
                        <select class="filter-select" id="auctionFilter" onchange="filterArtworks()">
                            <option value="">All Auctions</option>
                            <option value="1">On Auction</option>
                            <option value="0">Not on Auction</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Artworks Table -->
            <div class="table-container">
                <div class="table-card">
                    <div class="table-header">
                        <h3>Artworks</h3>
                        <div class="table-actions">
                            <span class="results-count" id="resultsCount">Loading...</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Artist</th>
                                    <th>Type</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Auction</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="artworksTableBody">
                                <tr>
                                    <td colspan="10" class="loading-row">
                                        <div class="loading-spinner"></div>
                                        <span>Loading artworks...</span>
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

    <!-- Artwork Modal -->
    <div class="modal" id="artworkModal" tabindex="-1" aria-labelledby="artworkModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="artworkForm" autocomplete="off">
                    <div class="modal-header">
                        <h5 class="modal-title" id="artworkModalTitle">Add New Artwork</h5>
                        <button type="button" class="modal-close" onclick="closeArtworkModal()" aria-label="Close">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="artworkId">
                        
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
                                <label for="type" class="form-label">Type *</label>
                                <select class="form-select" id="type" required>
                                    <option value="painting">Painting</option>
                                    <option value="sculpture">Sculpture</option>
                                    <option value="photography">Photography</option>
                                    <option value="digital">Digital</option>
                                    <option value="mixed_media">Mixed Media</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="price" class="form-label">Price *</label>
                                <input type="number" class="form-input" id="price" step="0.01" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-textarea" id="description" rows="3" placeholder="Enter artwork description..."></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="dimensions" class="form-label">Dimensions</label>
                                <input type="text" class="form-input" id="dimensions" placeholder="e.g., 24x36 inches">
                            </div>
                            <div class="form-group">
                                <label for="year" class="form-label">Year</label>
                                <input type="text" class="form-input" id="year" placeholder="e.g., 2023">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="material" class="form-label">Material</label>
                            <input type="text" class="form-input" id="material" placeholder="e.g., Oil on canvas">
                        </div>

                        <div class="form-group">
                            <label for="artworkImage" class="form-label">Image URL</label>
                            <input type="url" class="form-input" id="artworkImage" placeholder="https://example.com/image.jpg">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-checkbox-label">
                                    <input type="checkbox" class="form-checkbox" id="isAvailable" checked>
                                    <span class="checkmark"></span>
                                    Available for Sale
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="form-checkbox-label">
                                    <input type="checkbox" class="form-checkbox" id="onAuction">
                                    <span class="checkmark"></span>
                                    On Auction
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeArtworkModal()">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveArtwork()">Save Artwork</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Custom JS -->
    <script src="artworks.js"></script>
</body>
</html>
