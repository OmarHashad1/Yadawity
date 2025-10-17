<?php
// Check if user is authenticated as an artist
require_once './API/checkCredentials.php';
require_once './API/db.php';

$credentials = checkUserCredentials();

// Redirect if not authenticated
if (!$credentials['authenticated']) {
    header('Location: index.php');
    exit;
}

// Redirect if not an artist
if ($credentials['user_type'] !== 'artist') {
    header('Location: index.php');
    exit;
}

// Redirect if not verified
if (!$credentials['is_verified']) {
    header('Location: index.php');
    exit;
}

// Store artist info for use in the page
$artist_id = $credentials['user_id'];
$artist_name = $credentials['user_name'];
$artist_email = $credentials['user_email'];

// Fetch dynamic dashboard data
try {
    // Initialize default values
    $current_balance = 0;
    $total_sales = 0;
    $avg_rating = 0;
    $chart_labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    $chart_data = [0, 0, 0, 0, 0, 0];

    // Check if required tables exist
    $tables_exist = true;
    $required_tables = ['order_items', 'artworks', 'orders'];
    
    foreach ($required_tables as $table) {
        $check_query = "SHOW TABLES LIKE '$table'";
        $result = $db->query($check_query);
        if ($result->num_rows == 0) {
            $tables_exist = false;
            break;
        }
    }
    
    if ($tables_exist) {
        // Get total balance from order_items where the artwork belongs to this artist
        $balance_query = "SELECT COALESCE(SUM(oi.price * oi.quantity * 0.85), 0) as total_balance 
                          FROM order_items oi 
                          INNER JOIN artworks a ON oi.artwork_id = a.artwork_id 
                          INNER JOIN orders o ON oi.order_id = o.order_id
                          WHERE a.artist_id = ? AND o.status = 'delivered'";
        $stmt = $db->prepare($balance_query);
        if ($stmt !== false) {
            $stmt->bind_param("i", $artist_id);
            $stmt->execute();
            $balance_result = $stmt->get_result();
            if ($balance_result && $row = $balance_result->fetch_assoc()) {
                $current_balance = $row['total_balance'];
            }
        }

        // Get total sales count from order_items
        $sales_query = "SELECT COUNT(*) as total_sales 
                        FROM order_items oi 
                        INNER JOIN artworks a ON oi.artwork_id = a.artwork_id 
                        INNER JOIN orders o ON oi.order_id = o.order_id
                        WHERE a.artist_id = ? AND o.status IN ('paid', 'shipped', 'delivered')";
        $stmt = $db->prepare($sales_query);
        if ($stmt !== false) {
            $stmt->bind_param("i", $artist_id);
            $stmt->execute();
            $sales_result = $stmt->get_result();
            if ($sales_result && $row = $sales_result->fetch_assoc()) {
                $total_sales = $row['total_sales'];
            }
        }
    }

    // Check if artist_reviews table exists for rating
    $review_table_check = $db->query("SHOW TABLES LIKE 'artist_reviews'");
    if ($review_table_check->num_rows > 0) {
        $rating_query = "SELECT COALESCE(AVG(rating), 0) as avg_rating 
                         FROM artist_reviews 
                         WHERE artist_id = ?";
        $stmt = $db->prepare($rating_query);
        if ($stmt !== false) {
            $stmt->bind_param("i", $artist_id);
            $stmt->execute();
            $rating_result = $stmt->get_result();
            if ($rating_result && $row = $rating_result->fetch_assoc()) {
                $avg_rating = round($row['avg_rating'], 1);
            }
        }
    }

    // Try to get chart data only if all tables exist
    if ($tables_exist) {
        $chart_query = "SELECT 
                            YEAR(o.order_date) as year,
                            MONTH(o.order_date) as month,
                            MONTHNAME(o.order_date) as month_name,
                            COALESCE(SUM(oi.price * oi.quantity * 0.85), 0) as monthly_revenue
                        FROM order_items oi 
                        INNER JOIN artworks a ON oi.artwork_id = a.artwork_id 
                        INNER JOIN orders o ON oi.order_id = o.order_id
                        WHERE a.artist_id = ? 
                            AND o.status IN ('paid', 'shipped', 'delivered')
                            AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                        GROUP BY YEAR(o.order_date), MONTH(o.order_date)
                        ORDER BY year, month";
        $stmt = $db->prepare($chart_query);
        if ($stmt !== false) {
            $stmt->bind_param("i", $artist_id);
            $stmt->execute();
            $chart_result = $stmt->get_result();
            
            $temp_chart_data = [];
            $temp_chart_labels = [];
            while ($row = $chart_result->fetch_assoc()) {
                $temp_chart_labels[] = $row['month_name'];
                $temp_chart_data[] = (float)$row['monthly_revenue'];
            }
            
            // Only update if we got data
            if (!empty($temp_chart_data)) {
                $chart_labels = $temp_chart_labels;
                $chart_data = $temp_chart_data;
            }
        }
    }

} catch (Exception $e) {
    // Default values in case of error
    $current_balance = 0;
    $total_sales = 0;
    $avg_rating = 0;
    $chart_labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    $chart_data = [0, 0, 0, 0, 0, 0];
    
    // Log error for debugging (remove this in production)
    error_log("Dashboard data error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="./image/YAD-05.ico">
    <title>Artist Portal - Yadawity Gallery</title>
     <meta name="description" content="Find and book your perfect local art gallery experience. Explore physical galleries, meet artists, and immerse yourself in local art culture with Yadawity.">
    <meta name="keywords" content="local art gallery, art galleries, in-person art, local artists, art tours, art events, book gallery, Yadawity">
    <meta name="author" content="Yadawity">
    <meta property="og:title" content="Yadawity - Local Galleries">
    <meta property="og:description" content="Find and book your perfect local art gallery experience. Explore physical galleries, meet artists, and immerse yourself in local art culture with Yadawity.">
    <meta property="og:image" content="/image/darker_image_25_percent.jpeg">
    <meta property="og:type" content="website">
    <meta property="og:url" content="http://localhost/localGallery.php">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Yadawity - Local Galleries">
    <meta name="twitter:description" content="Find and book your perfect local art gallery experience. Explore physical galleries, meet artists, and immerse yourself in local art culture with Yadawity.">
    <meta name="twitter:image" content="/image/darker_image_25_percent.jpeg">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer">
    
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom SweetAlert2 Z-Index Fix -->
    <style>
        /* Ensure SweetAlert appears above modals */
        .swal2-container {
            z-index: 999999 !important;
        }
        .swal2-popup {
            z-index: 999999 !important;
        }
        .swal2-backdrop-show {
            z-index: 999998 !important;
        }
        
        /* Make sure modal backdrop is lower */
        .modal {
            z-index: 1000;
        }
        .modal.active {
            z-index: 1000;
        }
    </style>
    
    <!-- Component Styles -->
    <link rel="stylesheet" href="./components/BurgerMenu/burger-menu.css">
    <link rel="stylesheet" href="./public/artist-portal.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbarYadawity" id="yadawityNavbar">
        <div class="navContainer">
            <!-- Mobile Menu Icon (hidden on desktop) -->
            <button class="mobileMenuIcon" id="mobileMenuIcon">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="navLogo">
                <a href="index.html" class="navLogoLink">
                    <div class="logoIcon">
                        <svg width="40" height="40" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                            <path d="M20 50 Q15 30 25 25 Q35 20 45 35 Q40 45 35 50 Q40 55 45 65 Q35 80 25 75 Q15 70 20 50 Z" fill="currentColor" opacity="0.8"/>
                            <path d="M80 50 Q85 30 75 25 Q65 20 55 35 Q60 45 65 50 Q60 55 55 65 Q65 80 75 75 Q85 70 80 50 Z" fill="currentColor" opacity="0.8"/>
                            <line x1="50" y1="20" x2="50" y2="80" stroke="currentColor" stroke-width="3"/>
                            <path d="M50 20 Q45 15 42 12 M50 20 Q55 15 58 12" stroke="currentColor" stroke-width="2" fill="none"/>
                        </svg>
                    </div>
                    <div class="logoText">
                        <span class="logoName">Yadawity</span>
                        <span class="logoEst">ARTIST PORTAL</span>
                    </div>
                </a>
            </div>
            <div class="navMenu" id="navMenu">
                <div class="artistBadge">
                    <i class="fas fa-palette"></i>
                    <span>ARTIST</span>
                </div>
                <a href="index.html" class="navLink">
                    <i class="fas fa-home"></i>
                    <span>BACK TO SITE</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Artist Sidebar -->
    <aside class="artistSidebar" id="artistSidebar">
        <div class="sidebarHeader">
            <h3>Artist Portal</h3>
            <button class="sidebarToggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <nav class="sidebarNav">
            <div class="navSection">
                <h4>OVERVIEW</h4>
                <a href="#dashboard-section" class="sidebarLink active" data-section="dashboard">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#statistics-section" class="sidebarLink" data-section="statistics">
                    <i class="fas fa-chart-bar"></i>
                    <span>My Statistics</span>
                </a>
                <a href="#reviews-section" class="sidebarLink" data-section="reviews">
                    <i class="fas fa-star"></i>
                    <span>User Reviews</span>
                </a>
            </div>

            <div class="navSection">
                <h4>SALES</h4>
                <a href="#orders-section" class="sidebarLink" data-section="orders">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Incoming Orders</span>
                </a>
            </div>

            <div class="navSection">
                <h4>MANAGEMENT</h4>
                <a href="#profile-section" class="sidebarLink" data-section="profile">
                    <i class="fas fa-user"></i>
                    <span>Manage Profile</span>
                </a>
                <a href="#artwork-section" class="sidebarLink" data-section="artwork">
                    <i class="fas fa-palette"></i>
                    <span>Add Artwork</span>
                </a>
                <a href="#gallery-section" class="sidebarLink" data-section="gallery">
                    <i class="fas fa-images"></i>
                    <span>Gallery Events</span>
                </a>
                <a href="#auction-section" class="sidebarLink" data-section="auction">
                    <i class="fas fa-gavel"></i>
                    <span>Auction Management</span>
                </a>
            </div>
        </nav>
    </aside>

    <main class="artistMain">
        <!-- Dashboard Section -->
        <div id="dashboard-section" class="content-section active">
            <div class="pageHeader">
                <div class="headerContent">
                    <h1>Artist Dashboard</h1>
                    <p>Welcome back! Here's your artistic journey overview.</p>
                </div>
                <div class="headerActions">
                    <button class="btn btn-secondary btn-sm" id="refreshBtn">
                        <i class="fas fa-sync-alt"></i>
                        Refresh
                    </button>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="statsGrid">
                <div class="statCard">
                    <div class="statIcon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="statContent">
                        <h3 id="totalBalance">EGP <?php echo number_format($current_balance, 2); ?></h3>
                        <p>Current Balance</p>
                    </div>
                </div>
                <div class="statCard">
                    <div class="statIcon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="statContent">
                        <h3 id="totalSales"><?php echo $total_sales; ?></h3>
                        <p>Total Sales</p>
                    </div>
                </div>
                <div class="statCard">
                    <div class="statIcon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="statContent">
                        <h3 id="avgRating"><?php echo $avg_rating; ?></h3>
                        <p>Average Rating</p>
                    </div>
                </div>
            </div>

            <!-- Charts and Content -->
            <div class="dashboardGrid">
                <div class="contentCard chartCard">
                    <div class="cardHeader">
                        <h2>Sales Overview</h2>
                    </div>
                    <div class="chartContainer">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Section -->
        <div id="statistics-section" class="content-section">
            <div class="statistics-container">
                <div class="statistics-header">
                    <h1><i class="fas fa-chart-bar"></i> My Statistics</h1>
                    <p>Overview of your artworks, galleries, and auctions performance</p>
                </div>

                <!-- Loading State -->
                <div id="statisticsLoading" style="display: none;">
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Loading statistics...</p>
                    </div>
                </div>

                <!-- Content Container -->
                <div id="statisticsContent"></div>

                <!-- Statistics Dashboard Cards -->
                <div class="statsGrid">
                    <div class="statCard">
                        <div class="statIcon">
                            <i class="fas fa-palette"></i>
                        </div>
                        <div class="statContent">
                            <h3 id="artwork-count">0</h3>
                            <p>ARTWORKS</p>
                        </div>
                    </div>
                    
                    <div class="statCard">
                        <div class="statIcon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="statContent">
                            <h3 id="galleries-count">0</h3>
                            <p>GALLERIES</p>
                        </div>
                    </div>
                    
                    <div class="statCard">
                        <div class="statIcon">
                            <i class="fas fa-gavel"></i>
                        </div>
                        <div class="statContent">
                            <h3 id="auctions-count">0</h3>
                            <p>AUCTIONS</p>
                        </div>
                    </div>
                </div>

                <!-- Artworks Section -->
                <div class="stats-section">
                    <div class="stats-section-header">
                        <div class="stats-section-title">
                            <i class="fas fa-palette stats-section-icon"></i>
                            <h2>My Artworks</h2>
                            <span class="stats-count" id="artworks-count">0</span>
                        </div>
                    </div>
                    <div class="artworks-section-container">
                        <div class="stats-swiper artworks-swiper">
                            <div class="swiper-wrapper" id="artworks-container">
                                <!-- Artworks will be loaded here -->
                            </div>
                            <div class="swiper-pagination"></div>
                            <div class="swiper-button-next"></div>
                            <div class="swiper-button-prev"></div>
                        </div>
                    </div>
                </div>

                <!-- Virtual Galleries Section -->
                <div class="stats-section">
                    <div class="stats-section-header">
                        <div class="stats-section-title">
                            <i class="fas fa-desktop stats-section-icon"></i>
                            <h2>Virtual Galleries</h2>
                            <span class="stats-count" id="virtual-galleries-count">0</span>
                        </div>
                    </div>
                    <div class="artworks-section-container">
                        <div class="stats-swiper virtual-galleries-swiper">
                            <div class="swiper-wrapper" id="virtual-galleries-container">
                                <!-- Virtual galleries will be loaded here -->
                            </div>
                            <div class="swiper-pagination"></div>
                            <div class="swiper-button-next"></div>
                            <div class="swiper-button-prev"></div>
                        </div>
                    </div>
                </div>

                <!-- Local Galleries Section -->
                <div class="stats-section">
                    <div class="stats-section-header">
                        <div class="stats-section-title">
                            <i class="fas fa-building stats-section-icon"></i>
                            <h2>Local Galleries</h2>
                            <span class="stats-count" id="local-galleries-count">0</span>
                        </div>
                    </div>
                    <div class="artworks-section-container">
                        <div class="stats-swiper local-galleries-swiper">
                            <div class="swiper-wrapper" id="local-galleries-container">
                                <!-- Local galleries will be loaded here -->
                            </div>
                            <div class="swiper-pagination"></div>
                            <div class="swiper-button-next"></div>
                            <div class="swiper-button-prev"></div>
                        </div>
                    </div>
                </div>

                <!-- Auctions Section -->
                <div class="stats-section">
                    <div class="stats-section-header">
                        <div class="stats-section-title">
                            <i class="fas fa-gavel stats-section-icon"></i>
                            <h2>My Auctions</h2>
                            <span class="stats-count" id="auctions-count">0</span>
                        </div>
                    </div>
                    <div class="artworks-section-container">
                        <div class="stats-swiper auctions-swiper">
                            <div class="swiper-wrapper" id="auctions-container">
                                <!-- Auctions will be loaded here -->
                            </div>
                            <div class="swiper-pagination"></div>
                            <div class="swiper-button-next"></div>
                            <div class="swiper-button-prev"></div>
                        </div>
                    </div>
                </div>
                </div> <!-- End statisticsContent -->
            </div>
        </div>

        <!-- User Reviews Section -->
        <div id="reviews-section" class="content-section">
            <div class="pageHeader">
                <div class="headerContent">
                    <h1><i class="fas fa-star"></i> User Reviews</h1>
                    <p>See what customers are saying about your artwork and courses</p>
                </div>
                <div class="headerActions">
                    <button class="btn btn-secondary btn-sm" id="refreshReviewsBtn">
                        <i class="fas fa-sync-alt"></i>
                        Refresh
                    </button>
                </div>
            </div>

            <!-- Reviews Statistics -->
            <div class="statsGrid">
                <div class="statCard">
                    <div class="statIcon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="statContent">
                        <h3 id="averageRating">-</h3>
                        <p>Average Rating</p>
                    </div>
                </div>
                <div class="statCard">
                    <div class="statIcon">
                        <i class="fas fa-comment"></i>
                    </div>
                    <div class="statContent">
                        <h3 id="totalReviews">-</h3>
                        <p>Total Reviews</p>
                    </div>
                </div>
                <div class="statCard">
                    <div class="statIcon">
                        <i class="fas fa-thumbs-up"></i>
                    </div>
                    <div class="statContent">
                        <h3 id="positiveReviews">-</h3>
                        <p>Positive Reviews</p>
                    </div>
                </div>
                <div class="statCard">
                    <div class="statIcon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="statContent">
                        <h3 id="recentReviews">-</h3>
                        <p>This Month</p>
                    </div>
                </div>
            </div>

            <!-- Reviews Filter and Content -->
            <div class="reviewsContainer">
                <div class="reviewsFilters">
                    <div class="filterGroup">
                        <label for="reviewType">Filter by Type:</label>
                        <select id="reviewType" class="filterSelect">
                            <option value="all">All Reviews</option>
                            <option value="artwork">Artwork Reviews</option>
                            <option value="course">Course Reviews</option>
                        </select>
                    </div>
                    <div class="filterGroup">
                        <label for="ratingFilter">Filter by Rating:</label>
                        <select id="ratingFilter" class="filterSelect">
                            <option value="all">All Ratings</option>
                            <option value="5">5 Stars</option>
                            <option value="4">4 Stars</option>
                            <option value="3">3 Stars</option>
                            <option value="2">2 Stars</option>
                            <option value="1">1 Star</option>
                        </select>
                    </div>
                    <div class="filterGroup">
                        <label for="dateFilter">Filter by Date:</label>
                        <select id="dateFilter" class="filterSelect">
                            <option value="all">All Time</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                            <option value="year">This Year</option>
                        </select>
                    </div>
                </div>

                <!-- Loading State -->
                <div id="reviewsLoading" class="loading-state" style="display: none;">
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Loading reviews...</p>
                    </div>
                </div>

                <!-- Reviews List -->
                <div id="reviewsList" class="reviews-list">
                    <!-- Reviews will be loaded here dynamically -->
                </div>

                <!-- Pagination -->
                <div id="reviewsPagination" class="pagination-container" style="display: none;">
                    <button id="prevReviewsBtn" class="btn btn-secondary">
                        <i class="fas fa-chevron-left"></i>
                        Previous
                    </button>
                    <span id="reviewsPageInfo">Page 1 of 10</span>
                    <button id="nextReviewsBtn" class="btn btn-secondary">
                        Next
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Orders Section -->
        <div id="orders-section" class="content-section">
            <div class="pageHeader">
                <div class="headerContent">
                    <h1>Incoming Orders</h1>
                    <p>Track and manage your artwork orders.</p>
                </div>
                <div class="headerActions">
                    <button class="btn btn-primary btn-sm" id="exportOrdersBtn">
                        <i class="fas fa-download"></i>
                        Export
                    </button>
                </div>
            </div>

            <div class="controlsPanel">
                <div class="filterGroup">
                    <label>Status</label>
                    <select class="filterSelect" id="orderStatusFilter">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="paid">Paid</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="searchGroup">
                    <label>Search Orders</label>
                    <input type="text" class="searchInput" placeholder="Search by order number or customer..." id="orderSearch">
                </div>
                <div class="refreshGroup">
                    <button class="btn btn-outline btn-sm" id="refreshOrdersBtn">
                        <i class="fas fa-sync-alt"></i>
                        Refresh
                    </button>
                </div>
            </div>

            <!-- Orders Statistics Cards -->
            <div class="statsGrid" id="ordersStatsGrid">
                <div class="statCard">
                    <div class="statIcon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="statInfo">
                        <h3 id="totalOrdersCount">-</h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                <div class="statCard">
                    <div class="statIcon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="statInfo">
                        <h3 id="pendingOrdersCount">-</h3>
                        <p>Pending Orders</p>
                    </div>
                </div>
                <div class="statCard">
                    <div class="statIcon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="statInfo">
                        <h3 id="deliveredOrdersCount">-</h3>
                        <p>Delivered</p>
                    </div>
                </div>
            </div>

            <div class="contentCard">
                <!-- Loading State -->
                <div class="loadingState" id="ordersLoadingState">
                    <div class="loadingSpinner">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                    <p>Loading your orders...</p>
                </div>

                <!-- Empty State -->
                <div class="emptyState" id="ordersEmptyState" style="display: none;">
                    <div class="emptyIcon">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <h3>No Orders Found</h3>
                    <p>You don't have any orders yet. Orders will appear here when customers purchase your artwork.</p>
                </div>

                <!-- Error State -->
                <div class="errorState" id="ordersErrorState" style="display: none;">
                    <div class="errorIcon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3>Unable to Load Orders</h3>
                    <p id="ordersErrorMessage">There was an error loading your orders. Please try again.</p>
                    <button class="btn btn-primary" onclick="loadArtistOrders()">
                        <i class="fas fa-retry"></i>
                        Try Again
                    </button>
                </div>

                <!-- Orders Table -->
                <div class="tableContainer" id="ordersTableContainer" style="display: none;">
                    <table class="dataTable">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Your Revenue</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Order Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="ordersTableBody">
                            <!-- Orders will be populated here -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div id="ordersPagination" class="pagination-container" style="display: none;">
                    <button id="prevOrdersBtn" class="btn btn-secondary">
                        <i class="fas fa-chevron-left"></i>
                        Previous
                    </button>
                    <span id="ordersPageInfo">Page 1 of 10</span>
                    <button id="nextOrdersBtn" class="btn btn-secondary">
                        Next
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Profile Section -->
        <div id="profile-section" class="content-section">
            <div class="pageHeader">
                <div class="headerContent">
                    <h1>Manage Profile</h1>
                    <p>Update your artist information and settings.</p>
                </div>
            </div>

            <div class="contentCard">
                <div class="cardHeader">
                    <h2><i class="fas fa-user-edit"></i> Artist Information</h2>
                    <div class="cardBadge">
                        <i class="fas fa-shield-check"></i>
                        Verified Artist
                    </div>
                </div>
                    <form class="modernForm" id="artistInfoForm">
                        <!-- Hidden field for user ID -->
                        <input type="hidden" id="currentUserId" value="<?php echo htmlspecialchars($artist_id); ?>">
                        
                        <!-- Profile Picture Section -->
                        <div class="profilePictureSection">
                            <div class="currentProfilePicture" onclick="selectProfilePhoto()" style="cursor: pointer;">
                                <img src="./image/Artist-PainterLookingAtCamera.webp" alt="Profile Picture" id="profilePreview">
                                <div class="profileOverlay">
                                    <i class="fas fa-camera"></i>
                                    <span>Change Photo</span>
                                </div>
                            </div>
                            <div class="profilePictureInfo">
                                <h4 id="profileNameDisplay">Loading...</h4>
                                <p id="profileSpecialtyDisplay">Artist</p>
                                <div class="profileStats">
                                    <span class="statBadge"><i class="fas fa-star"></i> 4.8 Rating</span>
                                    <span class="statBadge"><i class="fas fa-palette"></i> 127 Artworks</span>
                                </div>
                            </div>
                            <div class="profilePictureUpload">
                                <input type="file" id="profilePicture" accept="image/jpeg,image/jpg,image/png,image/webp" hidden>
                                <button type="button" class="btn btn-outline btn-sm" onclick="selectProfilePhoto()">
                                    <i class="fas fa-upload"></i>
                                    Upload New Photo
                                </button>
                            </div>
                        </div>

                        <div class="formRow">
                            <div class="inputGroup">
                                <label for="artistName"><i class="fas fa-user"></i> Artist Name</label>
                                <input type="text" id="artistName" value="" disabled>
                                <div class="inputHelp">
                                    <i class="fas fa-info-circle"></i>
                                    Name cannot be changed. Contact support if needed.
                                </div>
                            </div>
                        </div>

                        <div class="formRow">
                            <div class="inputGroup">
                                <label for="artistBio"><i class="fas fa-quote-left"></i> About Me</label>
                                <textarea id="artistBio" rows="4" placeholder="Tell the world about your artistic journey..." maxlength="500"></textarea>
                                <div class="inputIndicator" id="artistBioIndicator"></div>
                                <div class="errorMessage" id="artistBioError"></div>
                                <div class="charCounter">
                                    <span id="bioCharCount">0</span>/500 characters
                                </div>
                            </div>
                        </div>

                        <div class="formRow">
                            <div class="inputGroup half">
                                <label for="artistPhone"><i class="fas fa-phone"></i> Phone Number</label>
                                <input type="tel" id="artistPhone" value="" placeholder="+20 XXX XXX XXXX">
                                <div class="inputIndicator" id="artistPhoneIndicator"></div>
                                <div class="errorMessage" id="artistPhoneError"></div>
                            </div>
                            <div class="inputGroup half">
                                <label for="artistEmail"><i class="fas fa-envelope"></i> Email Address</label>
                                <div style="display: flex; gap: 10px; align-items: flex-start;">
                                    <div style="flex: 1; position: relative;">
                                        <input type="email" id="artistEmail" value="" placeholder="your@email.com" disabled>
                                        <div class="inputIndicator" id="artistEmailIndicator"></div>
                                        <div class="errorMessage" id="artistEmailError"></div>
                                    </div>
                                    <button type="button" class="btn btn-outline btn-sm" id="changeEmailBtn" onclick="openChangeEmailModal()" style="margin-top: 0; white-space: nowrap;">
                                        <i class="fas fa-edit"></i> Change Email
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="formRow">
                            <div class="inputGroup half">
                                <label for="artistSpecialty"><i class="fas fa-palette"></i> Art Specialty</label>
                                <select id="artistSpecialty">
                                    <option value="" disabled selected>Choose your art specialty</option>
                                    <option value="abstract">Abstract Art</option>
                                    <option value="realism">Realism</option>
                                    <option value="impressionism">Impressionism</option>
                                    <option value="expressionism">Expressionism</option>
                                    <option value="contemporary">Contemporary</option>
                                    <option value="traditional">Traditional</option>
                                    <option value="mixed">Mixed Media</option>
                                </select>
                                <div class="inputIndicator" id="artistSpecialtyIndicator"></div>
                                <div class="errorMessage" id="artistSpecialtyError"></div>
                            </div>
                            <div class="inputGroup half">
                                <label for="artistExperience"><i class="fas fa-clock"></i> Years of Experience</label>
                                <select id="artistExperience">
                                    <option value="1-2">1-2 years</option>
                                    <option value="3-5">3-5 years</option>
                                    <option value="6-10">6-10 years</option>
                                    <option value="10+" selected>10+ years</option>
                                </select>
                                <div class="inputIndicator" id="artistExperienceIndicator"></div>
                                <div class="errorMessage" id="artistExperienceError"></div>
                            </div>
                        </div>

                        <div class="formRow">
                            <div class="inputGroup">
                                <label for="artistAchievements"><i class="fas fa-trophy"></i> Achievements & Awards</label>
                                <div class="achievementsList" id="achievementsList">
                                    <div class="achievementItem">
                                        <span>Featured in Cairo Contemporary Art Exhibition 2024</span>
                                        <button type="button" class="removeAchievement"><i class="fas fa-times"></i></button>
                                    </div>
                                    <div class="achievementItem">
                                        <span>Winner of Best Abstract Painting Award 2023</span>
                                        <button type="button" class="removeAchievement"><i class="fas fa-times"></i></button>
                                    </div>
                                    <div class="achievementItem">
                                        <span>Solo exhibition at Alexandria Art Gallery 2022</span>
                                        <button type="button" class="removeAchievement"><i class="fas fa-times"></i></button>
                                    </div>
                                </div>
                                <div class="addAchievement">
                                    <input type="text" id="newAchievement" placeholder="Add new achievement...">
                                    <button type="button" class="btn btn-outline btn-sm" id="addAchievementBtn">
                                        <i class="fas fa-plus"></i> Add
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="formActions">
                            <button type="button" class="btn btn-secondary">
                                <i class="fas fa-undo"></i>
                                Reset Changes
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Save Profile
                            </button>
                        </div>
                    </form>

                    <!-- Security & Privacy Section -->
                    <div class="cardHeader" style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e5e5;">
                        <h2><i class="fas fa-shield-alt"></i> Security & Privacy</h2>
                        <div class="securityLevel">
                            <span class="securityBadge high">
                                <i class="fas fa-lock"></i>
                                High Security
                            </span>
                        </div>
                    </div>
                    <form class="modernForm" id="securityForm">
                     
                        <div class="formSection">
                            <h3><i class="fas fa-key"></i> Change Password</h3>
                            <div class="formRow">
                                <div class="inputGroup">
                                    <label for="currentPassword">Current Password</label>
                                    <div class="passwordInput">
                                        <input type="password" id="currentPassword" placeholder="Enter current password">
                                        <button type="button" class="passwordToggle" onclick="togglePassword('currentPassword')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="formRow">
                                <div class="inputGroup half">
                                    <label for="newPassword">New Password</label>
                                    <div class="passwordInput">
                                        <input type="password" id="newPassword" placeholder="Enter new password">
                                        <button type="button" class="passwordToggle" onclick="togglePassword('newPassword')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="passwordStrength" id="passwordStrength">
                                        <div class="strengthBar">
                                            <div class="strengthFill"></div>
                                        </div>
                                        <span class="strengthText">Password strength</span>
                                    </div>
                                </div>
                                <div class="inputGroup half">
                                    <label for="confirmPassword">Confirm New Password</label>
                                    <div class="passwordInput">
                                        <input type="password" id="confirmPassword" placeholder="Confirm new password">
                                        <button type="button" class="passwordToggle" onclick="togglePassword('confirmPassword')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="matchIndicator" id="matchIndicator"></div>
                                </div>
                            </div>
                        </div>

                        <div class="formActions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-shield-check"></i>
                                Change Password
                            </button>
                        </div>
                    </form>

                    <!-- Account Deletion Section -->
                    <div class="deleteAccountSection" style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e5e5;">
                        <div class="deleteAccountWarning">
                            <div class="warningContent">
                                <h4><i class="fas fa-trash-alt"></i> Delete Account</h4>
                                <p>Permanently delete your account and all associated data. This action cannot be undone.</p>
                                <ul class="deleteWarningList">
                                    <li>All your artworks will be permanently removed</li>
                                    <li>Your galleries and auctions will be deleted</li>
                                    <li>Your profile and account data will be deleted</li>
                                    <li>Your cart and wishlist will be cleared</li>
                                    <li>All orders and reviews will be removed</li>
                                    <li>This action is irreversible</li>
                                </ul>
                            </div>
                            <button type="button" class="btn btn-danger deleteAccountBtn" onclick="confirmDeleteAccount()">
                                <i class="fas fa-trash-alt"></i>
                                Delete My Account
                            </button>
                        </div>
                    </div>
                </div>
        </div>

        <!-- Add Artwork Section -->
        <div id="artwork-section" class="content-section">
            <div class="pageHeader">
                <div class="headerContent">
                    <h1>Add New Artwork</h1>
                    <p>Share your latest creation with the world and start selling.</p>
                </div>
            </div>

            <div class="artworkFormContainer">
                <div class="formProgress">
                    <div class="progressStep active" data-step="1">
                        <div class="stepNumber">1</div>
                        <span>Artwork Info</span>
                    </div>
                    <div class="progressStep" data-step="2">
                        <div class="stepNumber">2</div>
                        <span>Images</span>
                    </div>
                    <div class="progressStep" data-step="3">
                        <div class="stepNumber">3</div>
                        <span>Preview</span>
                    </div>
                </div>

                <div class="contentCard">
                    <form class="modernForm artworkForm" id="addArtworkForm">
                        <!-- Step 1: Artwork Information -->
                        <div class="formStep active" data-step="1">
                            <div class="stepHeader">
                                <h2><i class="fas fa-info-circle"></i> Artwork Information</h2>
                                <p>Tell us about your artwork's details and specifications</p>
                            </div>

                            <div class="formRow">
                                <div class="inputGroup">
                                    <label for="artworkName"><i class="fas fa-signature"></i> Artwork Title *</label>
                                    <input type="text" id="artworkName" placeholder="Give your artwork a captivating title...">
                                    <div class="inputIndicator" id="artworkNameIndicator"></div>
                                    <div class="errorMessage" id="artworkNameError"></div>
                                    <div class="inputHelp">This will be the main title displayed to buyers</div>
                                </div>
                            </div>

                            <div class="formRow">
                                <div class="inputGroup half">
                                    <label for="artworkPrice"><i class="fas fa-tag"></i> Price (EGP) *</label>
                                    <div class="priceInput">
                                        <span class="currency">EGP</span>
                                        <input type="number" id="artworkPrice" placeholder="0.00" step="0.01">
                                    </div>
                                    <div class="inputIndicator" id="artworkPriceIndicator"></div>
                                    <div class="errorMessage" id="artworkPriceError"></div>
                                    <div class="priceHelp">
                                        <span class="priceNote">Yadawity fee: 15%</span>
                                        <span class="netPrice">You'll receive: EGP <span id="netPrice">0.00</span></span>
                                    </div>
                                </div>
                                <div class="inputGroup half">
                                    <label for="artworkQuantity"><i class="fas fa-boxes"></i> Stock Quantity *</label>
                                    <input type="number" id="artworkQuantity" placeholder="1" min="1" value="1">
                                    <div class="inputIndicator" id="artworkQuantityIndicator"></div>
                                    <div class="errorMessage" id="artworkQuantityError"></div>
                                    <div class="inputHelp">Number of pieces available for sale</div>
                                </div>
                            </div>

                            <div class="formRow">
                                <div class="inputGroup half">
                                    <label for="artworkType"><i class="fas fa-layer-group"></i> Artwork Type *</label>
                                    <input type="text" id="artworkType" placeholder="e.g., Painting, Sculpture, Photography">
                                    <div class="inputIndicator" id="artworkTypeIndicator"></div>
                                    <div class="errorMessage" id="artworkTypeError"></div>
                                </div>
                                <div class="inputGroup half">
                                    <label for="artworkCategory"><i class="fas fa-tags"></i> Category *</label>
                                    <select id="artworkCategory">
                                        <option value="">Select Category</option>
                                        <option value="Portraits">Portraits</option>
                                        <option value="Landscapes">Landscapes</option>
                                        <option value="Abstract">Abstract</option>
                                        <option value="Photography">Photography</option>
                                        <option value="Mixed Media">Mixed Media</option>
                                    </select>
                                    <div class="inputIndicator" id="artworkCategoryIndicator"></div>
                                    <div class="errorMessage" id="artworkCategoryError"></div>
                                </div>
                            </div>

                            <div class="formRow">
                                <div class="inputGroup half">
                                    <label for="artworkStyle"><i class="fas fa-palette"></i> Style</label>
                                    <input type="text" id="artworkStyle" placeholder="e.g., Impressionism, Realism, Contemporary">
                                    <div class="inputIndicator" id="artworkStyleIndicator"></div>
                                    <div class="errorMessage" id="artworkStyleError"></div>
                                    <div class="inputHelp">Optional: Art style or movement (optional)</div>
                                </div>
                            </div>

                            <div class="formRow">
                                <div class="inputGroup half">
                                    <label for="artworkDimensions"><i class="fas fa-ruler"></i> Dimensions *</label>
                                    <input type="text" id="artworkDimensions" placeholder="e.g., 50 x 70 cm, 30 x 40 x 10 cm">
                                    <div class="inputIndicator" id="artworkDimensionsIndicator"></div>
                                    <div class="errorMessage" id="artworkDimensionsError"></div>
                                    <div class="inputHelp">Format: Width x Height x Depth (if applicable)</div>
                                </div>
                                <div class="inputGroup half">
                                    <label for="artworkYear"><i class="fas fa-calendar"></i> Year Created</label>
                                    <input type="number" id="artworkYear" placeholder="2025" value="2025">
                                    <div class="inputIndicator" id="artworkYearIndicator"></div>
                                    <div class="errorMessage" id="artworkYearError"></div>
                                </div>
                            </div>

                            <div class="formRow">
                                <div class="inputGroup">
                                    <label for="artworkDescription"><i class="fas fa-align-left"></i> Description *</label>
                                    <textarea id="artworkDescription" rows="5" placeholder="Describe your artwork, inspiration, technique, and story behind it..." maxlength="1000"></textarea>
                                    <div class="inputIndicator" id="artworkDescriptionIndicator"></div>
                                    <div class="errorMessage" id="artworkDescriptionError"></div>
                                    <div class="charCounter">
                                        <span id="descCharCount">0</span>/1000 characters
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Image Upload -->
                        <div class="formStep" data-step="2">
                            <div class="stepHeader">
                                <h2><i class="fas fa-images"></i> Artwork Images</h2>
                                <p>Upload high-quality images of your artwork</p>
                            </div>

                            <!-- Primary Image Section -->
                            <div class="imageUploadContainer">
                                <div class="imageUploadSection">
                                    <div class="sectionLabel">
                                        <label for="artworkPrimaryImage"><i class="fas fa-star"></i> Primary Artwork Image</label>
                                        <span class="sectionDescription">This will be the main image displayed for your artwork</span>
                                    </div>
                                    <div class="uploadZone primaryUploadZone" id="artworkPrimaryUploadZone">
                                        <div class="uploadContent">
                                            <i class="fas fa-star"></i>
                                            <h3>Drag & Drop Primary Image Here</h3>
                                            <p>or <span class="uploadLink">browse files</span></p>
                                            <div class="uploadReqs">
                                                <span>• JPG, PNG, WEBP up to 10MB</span>
                                            </div>
                                        </div>
                                        <input type="file" id="artworkPrimaryImage" accept="image/*" hidden>
                                    </div>
                                    
                                    <div class="uploadedImages" id="artworkPrimaryImagePreview">
                                        <!-- Primary image preview will appear here -->
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Images Section -->
                            <div class="imageUploadContainer">
                                <div class="imageUploadSection">
                                    <div class="sectionLabel">
                                        <label for="artworkImages"><i class="fas fa-images"></i> Additional Artwork Images</label>
                                        <span class="sectionDescription">Upload additional views and details of your artwork (optional)</span>
                                    </div>
                                    <div class="uploadZone" id="uploadZone">
                                        <div class="uploadContent">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <h3>Drag & Drop Additional Images Here</h3>
                                            <p>or <span class="uploadLink">browse files</span></p>
                                            <div class="uploadReqs">
                                                <span>• JPG, PNG, WEBP up to 10MB each</span>
                                                <span>• Maximum 9 additional images</span>
                                            </div>
                                        </div>
                                        <input type="file" id="artworkImages" multiple accept="image/*" hidden>
                                    </div>

                                    <div class="uploadedImages" id="uploadedImages">
                                        <!-- Uploaded images will appear here -->
                                    </div>
                                </div>
                            </div>

                                <div class="imageGuidelines">
                                    <h4><i class="fas fa-lightbulb"></i> Photography Tips</h4>
                                    <div class="guidelinesList">
                                        <div class="guideline">
                                            <i class="fas fa-check"></i>
                                            <span>Use natural light or professional lighting</span>
                                        </div>
                                        <div class="guideline">
                                            <i class="fas fa-check"></i>
                                            <span>Include front view, detail shots, and signature</span>
                                        </div>
                                        <div class="guideline">
                                            <i class="fas fa-check"></i>
                                            <span>Show the artwork in context (room setting)</span>
                                        </div>
                                        <div class="guideline">
                                            <i class="fas fa-check"></i>
                                            <span>Ensure colors are accurate and vibrant</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Preview -->
                        <div class="formStep" data-step="3">
                            <div class="stepHeader">
                                <h2><i class="fas fa-eye"></i> Preview & Publish</h2>
                                <p>Review your artwork listing before publishing</p>
                            </div>

                            <div class="artworkPreview" id="artworkPreview">
                                <!-- Preview will be generated here -->
                            </div>
                        </div>

                        <div class="formNavigation">
                            <button type="button" class="btn btn-secondary" id="prevStep" style="display: none;">
                                <i class="fas fa-arrow-left"></i>
                                Previous
                            </button>
                            <div class="stepInfo">
                                Step <span id="currentStepNum">1</span> of 3
                            </div>
                            <button type="button" class="btn btn-primary" id="nextStep">
                                Next
                                <i class="fas fa-arrow-right"></i>
                            </button>
                            <button type="submit" class="btn btn-success" id="publishBtn" style="display: none;">
                                <i class="fas fa-rocket"></i>
                                Publish Artwork
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Gallery Events Section -->
        <div id="gallery-section" class="content-section">
            <div class="pageHeader">
                <div class="headerContent">
                    <h1>Gallery Events</h1>
                    <p>Create and manage your gallery exhibitions and events.</p>
                </div>
                <div class="headerActions">
                    <button class="btn btn-outline btn-sm" id="viewMyEventsBtn">
                        <i class="fas fa-list"></i>
                        My Events
                    </button>
                </div>
            </div>

            <div class="galleryFormContainer">
                <div class="contentCard">
                    <form class="modernForm galleryForm" id="addGalleryEventForm">
                        <!-- Event Type Selection -->
                        <div class="stepHeader">
                            <h2><i class="fas fa-layer-group"></i> Choose Event Type</h2>
                            <p>Select the type of gallery event you want to create</p>
                        </div>

                        <div class="eventTypeSelection">
                            <div class="typeOption" data-type="virtual">
                                <input type="radio" id="typeVirtual" name="eventType" value="virtual">
                                <label for="typeVirtual">
                                    <div class="typeIcon">
                                        <i class="fas fa-desktop"></i>
                                    </div>
                                    <div class="typeContent">
                                        <h3>Virtual Exhibition</h3>
                                        <p>Host an online digital gallery experience that visitors can access from anywhere in the world</p>
                                        <ul class="typeFeatures">
                                            <li><i class="fas fa-check"></i> Global accessibility</li>
                                            <li><i class="fas fa-check"></i> Interactive digital experience</li>
                                            <li><i class="fas fa-check"></i> Easy sharing and promotion</li>
                                        </ul>
                                    </div>
                                </label>
                            </div>

                            <div class="typeOption" data-type="physical">
                                <input type="radio" id="typePhysical" name="eventType" value="physical">
                                <label for="typePhysical">
                                    <div class="typeIcon">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <div class="typeContent">
                                        <h3>Local Gallery</h3>
                                        <p>Organize a physical exhibition at a gallery location where visitors can attend in person</p>
                                        <ul class="typeFeatures">
                                            <li><i class="fas fa-check"></i> Personal interaction</li>
                                            <li><i class="fas fa-check"></i> Physical artwork viewing</li>
                                            <li><i class="fas fa-check"></i> Local community engagement</li>
                                        </ul>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Dynamic Event Details Section -->
                        <div class="eventDetailsSection" id="eventDetailsSection" style="display: none;">
                            <!-- Virtual Event Details -->
                            <div class="eventDetailsForm" id="virtualEventDetails" style="display: none;">
                                <div class="stepHeader">
                                    <h2><i class="fas fa-desktop"></i> Virtual Exhibition Details</h2>
                                    <p>Set up your online gallery event</p>
                                </div>

                                <div class="formRow">
                                    <div class="inputGroup">
                                        <label for="virtualEventTitle"><i class="fas fa-signature"></i> Event Title *</label>
                                        <input type="text" id="virtualEventTitle" placeholder="Give your virtual exhibition a compelling title..." required>
                                        <div class="inputIndicator" id="virtualEventTitleIndicator"></div>
                                        <div class="errorMessage" id="virtualEventTitleError"></div>
                                        <div class="inputHelp">This will be the main title displayed to online visitors</div>
                                    </div>
                                </div>

                                <div class="formRow">
                                    <div class="inputGroup">
                                        <label for="virtualEventDescription"><i class="fas fa-align-left"></i> Event Description *</label>
                                        <textarea id="virtualEventDescription" rows="5" placeholder="Describe your virtual exhibition, featured artworks, theme, and what visitors can expect..." required maxlength="1000"></textarea>
                                        <div class="inputIndicator" id="virtualEventDescriptionIndicator"></div>
                                        <div class="errorMessage" id="virtualEventDescriptionError"></div>
                                        <div class="charCounter">
                                            <span id="virtualDescCharCount">0</span>/1000 characters
                                        </div>
                                    </div>
                                </div>

                                <div class="formRow">
                                    <div class="inputGroup half">
                                        <label for="virtualEventPrice"><i class="fas fa-tag"></i> Entry Price (EGP)</label>
                                        <div class="priceInput">
                                            <span class="currency">EGP</span>
                                            <input type="number" id="virtualEventPrice" placeholder="0.00" step="0.01" min="0">
                                        </div>
                                        <div class="inputIndicator" id="virtualEventPriceIndicator"></div>
                                        <div class="errorMessage" id="virtualEventPriceError"></div>
                                        <div class="inputHelp">Leave empty or 0 for free virtual events</div>
                                    </div>
                                    <div class="inputGroup half">
                                        <label for="virtualEventDuration"><i class="fas fa-clock"></i> Duration (Minutes) *</label>
                                        <input type="number" id="virtualEventDuration" placeholder="120" min="1" max="120" required>
                                        <div class="inputIndicator" id="virtualEventDurationIndicator"></div>
                                        <div class="errorMessage" id="virtualEventDurationError"></div>
                                        <div class="inputHelp">How many minutes will this virtual event run? (Max: 2 hours)</div>
                                    </div>
                                </div>

                                <div class="formRow">
                                    <div class="inputGroup">
                                        <label for="virtualEventStartDate"><i class="fas fa-calendar-plus"></i> Start Date & Time *</label>
                                        <input type="datetime-local" id="virtualEventStartDate" required>
                                        <div class="inputIndicator" id="virtualEventStartDateIndicator"></div>
                                        <div class="errorMessage" id="virtualEventStartDateError"></div>
                                    </div>
                                </div>

                                <div class="formRow">
                                    <div class="inputGroup">
                                        <label for="virtualEventTags"><i class="fas fa-tags"></i> Event Tags <span class="tagCounter" id="virtualTagCounter">(0/10)</span></label>
                                        <div class="tagsInputContainer">
                                            <div class="tagsInput" id="virtualTagsInput">
                                                <div class="tagsList" id="virtualTagsList"></div>
                                                <div class="addTagInputWrapper">
                                                    <input type="text" id="virtualEventTags" placeholder="Type a tag and press Enter to add...">
                                                </div>
                                            </div>
                                            <div class="tagsInputIndicator">
                                                <i class="fas fa-info-circle"></i>
                                                <span>Press Enter, comma, or space to add tags</span>
                                            </div>
                                        </div>
                                        <div class="inputHelp">💡 Add relevant tags to help visitors find your virtual exhibition</div>
                                        
                                        <!-- Tag Suggestions -->
                                        <div class="tagSuggestions" id="virtualTagSuggestions">
                                            <div class="suggestionLabel">Popular tags:</div>
                                            <div class="suggestionTags">
                                                <span class="suggestedTag" onclick="addVirtualTag('contemporary')">contemporary</span>
                                                <span class="suggestedTag" onclick="addVirtualTag('abstract')">abstract</span>
                                                <span class="suggestedTag" onclick="addVirtualTag('modern')">modern</span>
                                                <span class="suggestedTag" onclick="addVirtualTag('painting')">painting</span>
                                                <span class="suggestedTag" onclick="addVirtualTag('digital')">digital</span>
                                                <span class="suggestedTag" onclick="addVirtualTag('sculpture')">sculpture</span>
                                                <span class="suggestedTag" onclick="addVirtualTag('exhibition')">exhibition</span>
                                                <span class="suggestedTag" onclick="addVirtualTag('gallery')">gallery</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Primary Image Section for Virtual Events -->
                                <div class="formRow">
                                    <div class="inputGroup">
                                        <label for="virtualPrimaryImage"><i class="fas fa-image"></i> Primary Gallery Image</label>
                                        <div class="imageUploadArea">
                                            <div class="uploadZone" id="virtualPrimaryUploadZone">
                                                <div class="uploadContent">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                    <h3>Drag & Drop Primary Image Here</h3>
                                                    <p>or <span class="uploadLink">browse file</span></p>
                                                    <div class="uploadReqs">
                                                        <span>• JPG, PNG, GIF, WEBP formats supported</span>
                                                        <span>• This will be the main gallery cover image</span>
                                                    </div>
                                                </div>
                                                <input type="file" id="virtualPrimaryImage" accept="image/*" hidden>
                                            </div>

                                            <div class="uploadedImages" id="virtualPrimaryImagePreview">
                                                <!-- Primary image preview will appear here -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Physical Event Details -->
                            <div class="eventDetailsForm" id="physicalEventDetails" style="display: none;">
                                <div class="stepHeader">
                                    <h2><i class="fas fa-building"></i> Local Gallery Details</h2>
                                    <p>Set up your physical gallery event</p>
                                </div>

                                <div class="formRow">
                                    <div class="inputGroup">
                                        <label for="physicalEventTitle"><i class="fas fa-signature"></i> Event Title *</label>
                                        <input type="text" id="physicalEventTitle" placeholder="Give your gallery exhibition a compelling title..." required>
                                        <div class="inputIndicator" id="physicalEventTitleIndicator"></div>
                                        <div class="errorMessage" id="physicalEventTitleError"></div>
                                        <div class="inputHelp">This will be the main title displayed to visitors</div>
                                    </div>
                                </div>

                                <div class="formRow">
                                    <div class="inputGroup">
                                        <label for="physicalEventDescription"><i class="fas fa-align-left"></i> Event Description *</label>
                                        <textarea id="physicalEventDescription" rows="5" placeholder="Describe your gallery exhibition, featured artworks, theme, and what visitors can expect..." required maxlength="1000"></textarea>
                                        <div class="inputIndicator" id="physicalEventDescriptionIndicator"></div>
                                        <div class="errorMessage" id="physicalEventDescriptionError"></div>
                                        <div class="charCounter">
                                            <span id="physicalDescCharCount">0</span>/1000 characters
                                        </div>
                                    </div>
                                </div>

                                <div class="formRow">
                                    <div class="inputGroup half">
                                        <label for="physicalEventPrice"><i class="fas fa-tag"></i> Entry Price (EGP)</label>
                                        <div class="priceInput">
                                            <span class="currency">EGP</span>
                                            <input type="number" id="physicalEventPrice" placeholder="0.00" step="0.01" min="0">
                                        </div>
                                        <div class="inputIndicator" id="physicalEventPriceIndicator"></div>
                                        <div class="errorMessage" id="physicalEventPriceError"></div>
                                        <div class="inputHelp">Leave empty or 0 for free events</div>
                                    </div>
                                    <div class="inputGroup half">
                                        <label for="physicalEventStartDate"><i class="fas fa-calendar-plus"></i> Start Date & Time *</label>
                                        <input type="datetime-local" id="physicalEventStartDate" required>
                                        <div class="inputIndicator" id="physicalEventStartDateIndicator"></div>
                                        <div class="errorMessage" id="physicalEventStartDateError"></div>
                                    </div>
                                </div>

                                <div class="formRow">
                                    <div class="inputGroup half">
                                        <label for="physicalEventPhone"><i class="fas fa-phone"></i> Contact Phone *</label>
                                        <input type="tel" id="physicalEventPhone" placeholder="+20 XXX XXX XXXX" required>
                                        <div class="inputIndicator" id="physicalEventPhoneIndicator"></div>
                                        <div class="errorMessage" id="physicalEventPhoneError"></div>
                                    </div>
                                    <div class="inputGroup half">
                                        <label for="physicalEventCity"><i class="fas fa-city"></i> City *</label>
                                        <input type="text" id="physicalEventCity" placeholder="e.g., Cairo, Alexandria..." required>
                                        <div class="inputIndicator" id="physicalEventCityIndicator"></div>
                                        <div class="errorMessage" id="physicalEventCityError"></div>
                                    </div>
                                </div>

                                <div class="formRow">
                                    <div class="inputGroup">
                                        <label for="physicalEventAddress"><i class="fas fa-map-marker-alt"></i> Gallery Address *</label>
                                        <textarea id="physicalEventAddress" rows="3" placeholder="Enter the full address of your gallery or event venue..." required></textarea>
                                        <div class="inputIndicator" id="physicalEventAddressIndicator"></div>
                                        <div class="errorMessage" id="physicalEventAddressError"></div>
                                        <div class="inputHelp">Include street address, district, and landmarks if applicable</div>
                                    </div>
                                </div>

                                <!-- Primary Image Section for Physical Events -->
                                <div class="formRow">
                                    <div class="inputGroup">
                                        <label for="physicalPrimaryImage"><i class="fas fa-image"></i> Primary Gallery Image</label>
                                        <div class="imageUploadArea">
                                            <div class="uploadZone" id="physicalPrimaryUploadZone">
                                                <div class="uploadContent">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                    <h3>Drag & Drop Primary Image Here</h3>
                                                    <p>or <span class="uploadLink">browse file</span></p>
                                                    <div class="uploadReqs">
                                                        <span>• JPG, PNG, GIF, WEBP formats supported</span>
                                                        <span>• This will be the main gallery cover image</span>
                                                    </div>
                                                </div>
                                                <input type="file" id="physicalPrimaryImage" accept="image/*" hidden>
                                            </div>

                                            <div class="uploadedImages" id="physicalPrimaryImagePreview">
                                                <!-- Primary image preview will appear here -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Preview Section -->
                            <div class="eventPreviewSection" id="eventPreviewSection" style="display: none;">
                                <div class="stepHeader">
                                    <h2><i class="fas fa-eye"></i> Preview & Publish</h2>
                                    <p>Review your event details before publishing</p>
                                </div>

                                <div class="eventPreview" id="eventPreview">
                                    <div class="previewContainer">
                                        <div class="previewHeader">
                                            <h3 id="previewTitle">Event Title</h3>
                                            <div class="previewMeta">
                                                <span class="previewType" id="previewType">Event Type</span>
                                                <span class="previewPrice" id="previewPrice">Free</span>
                                            </div>
                                        </div>
                                        
                                        <div class="previewContent">
                                            <div class="previewSection">
                                                <h4><i class="fas fa-align-left"></i> Description</h4>
                                                <p id="previewDescription">Event description will appear here</p>
                                            </div>
                                            
                                            <div class="previewSection" id="previewLocationSection" style="display: none;">
                                                <h4><i class="fas fa-map-marker-alt"></i> Location</h4>
                                                <div class="previewLocation">
                                                    <p id="previewAddress">Event address</p>
                                                    <p id="previewCity">City</p>
                                                    <p id="previewPhone">Contact phone</p>
                                                </div>
                                            </div>
                                            
                                            <div class="previewSection">
                                                <h4><i class="fas fa-calendar-alt"></i> Schedule</h4>
                                                <div class="previewSchedule">
                                                    <p><strong>Start:</strong> <span id="previewStart">Start date</span></p>
                                                    <p id="previewDurationRow"><strong>Duration:</strong> <span id="previewDays">Duration</span></p>
                                                </div>
                                            </div>

                                            <div class="previewSection" id="previewTagsSection" style="display: none;">
                                                <h4><i class="fas fa-tags"></i> Tags</h4>
                                                <div class="previewTags" id="previewTags">
                                                    <!-- Tags will be populated here -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success" id="publishGalleryEventBtn">
                                <i class="fas fa-rocket"></i>
                                Publish Event
                            </button>                        </div>

                    </form>
                </div>
            </div>

            <!-- My Events List -->
            <div class="contentCard" id="myEventsCard" style="display: none;">
                <div class="cardHeader">
                    <h2>My Gallery Events</h2>
                    <div class="cardActions">
                        <button class="btn btn-outline btn-sm" id="backToCreateEvent">
                            <i class="fas fa-plus"></i>
                            Create New Event
                        </button>
                    </div>
                </div>
                <div class="eventsList" id="eventsList">
                    <!-- Events will be loaded here -->
                    <div class="eventItem">
                        <div class="eventInfo">
                            <h4>Contemporary Art Showcase</h4>
                            <p><i class="fas fa-map-marker-alt"></i> Downtown Gallery, Cairo</p>
                            <p><i class="fas fa-calendar-alt"></i> Dec 15, 2024 - Dec 22, 2024</p>
                            <span class="statusBadge status-active">Active</span>
                        </div>
                        <div class="eventActions">
                            <button class="btn btn-outline btn-sm">Edit</button>
                            <button class="btn btn-primary btn-sm">View</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Auction Section -->
        <div id="auction-section" class="content-section">
            <div class="pageHeader">
                <div class="headerContent">
                    <h1>Auction Management</h1>
                    <p>Create auctions and track your bidding artworks.</p>
                </div>
            </div>

            <div class="artworkFormContainer">
                <div class="formProgress">
                    <div class="progressStep active" data-step="1">
                        <div class="stepNumber">1</div>
                        <span>Auction Info</span>
                    </div>
                    <div class="progressStep" data-step="2">
                        <div class="stepNumber">2</div>
                        <span>Images</span>
                    </div>
                    <div class="progressStep" data-step="3">
                        <div class="stepNumber">3</div>
                        <span>Preview</span>
                    </div>
                </div>

                <div class="contentCard">
                    <form class="modernForm auctionForm" id="addAuctionForm">
                        <!-- Step 1: Auction Information -->
                        <div class="formStep active" data-step="1">
                            <div class="stepHeader">
                                <h2><i class="fas fa-gavel"></i> Auction Information</h2>
                                <p>Set up your artwork auction details and bidding parameters</p>
                            </div>

                            <div class="formRow">
                                <div class="inputGroup">
                                    <label for="auctionArtworkName"><i class="fas fa-signature"></i> Artwork Title *</label>
                                    <input type="text" id="auctionArtworkName" placeholder="Give your artwork a captivating title...">
                                    <div class="inputIndicator" id="auctionArtworkNameIndicator"></div>
                                    <div class="errorMessage" id="auctionArtworkNameError"></div>
                                    <div class="inputHelp">This will be the main title displayed to bidders</div>
                                </div>
                            </div>

                            <div class="formRow">
                                <div class="inputGroup half">
                                    <label for="initialBid"><i class="fas fa-tag"></i> Starting Bid (EGP) *</label>
                                    <div class="priceInput">
                                        <span class="currency">EGP</span>
                                        <input type="number" id="initialBid" placeholder="0.00" step="0.01">
                                    </div>
                                    <div class="inputIndicator" id="initialBidIndicator"></div>
                                    <div class="errorMessage" id="initialBidError"></div>
                                    <div class="inputHelp">Set a competitive starting price to attract bidders</div>
                                </div>
                                <div class="inputGroup half">
                                    <label for="auctionType"><i class="fas fa-layer-group"></i> Artwork Type *</label>
                                    <input type="text" id="auctionType" placeholder="e.g., Painting, Sculpture, Photography">
                                    <div class="inputIndicator" id="auctionTypeIndicator"></div>
                                    <div class="errorMessage" id="auctionTypeError"></div>
                                </div>
                            </div>

                            <div class="formRow">
                                <div class="inputGroup half">
                                    <label for="auctionCategory"><i class="fas fa-tags"></i> Category *</label>
                                    <select id="auctionCategory">
                                        <option value="">Select Category</option>
                                        <option value="Portraits">Portraits</option>
                                        <option value="Landscapes">Landscapes</option>
                                        <option value="Abstract">Abstract</option>
                                        <option value="Photography">Photography</option>
                                        <option value="Mixed Media">Mixed Media</option>
                                    </select>
                                    <div class="inputIndicator" id="auctionCategoryIndicator"></div>
                                    <div class="errorMessage" id="auctionCategoryError"></div>
                                </div>
                                <div class="inputGroup half">
                                    <label for="auctionStyle"><i class="fas fa-palette"></i> Art Style</label>
                                    <input type="text" id="auctionStyle" placeholder="e.g., Impressionism, Realism, Contemporary">
                                    <div class="inputIndicator" id="auctionStyleIndicator"></div>
                                    <div class="errorMessage" id="auctionStyleError"></div>
                                    <div class="inputHelp">Optional: Art style or movement</div>
                                </div>
                            </div>

                            <div class="formRow">
                                <div class="inputGroup half">
                                    <label for="auctionDimensions"><i class="fas fa-ruler"></i> Dimensions *</label>
                                    <input type="text" id="auctionDimensions" placeholder="e.g., 50 x 70 cm, 30 x 40 x 10 cm">
                                    <div class="inputIndicator" id="auctionDimensionsIndicator"></div>
                                    <div class="errorMessage" id="auctionDimensionsError"></div>
                                    <div class="inputHelp">Format: Width x Height x Depth (if applicable)</div>
                                </div>
                                <div class="inputGroup half">
                                    <label for="auctionYear"><i class="fas fa-calendar"></i> Year Created</label>
                                    <input type="number" id="auctionYear" placeholder="2025" value="2025">
                                    <div class="inputIndicator" id="auctionYearIndicator"></div>
                                    <div class="errorMessage" id="auctionYearError"></div>
                                </div>
                            </div>

                            <div class="formRow">
                                <div class="inputGroup half">
                                    <label for="auctionStartDate"><i class="fas fa-calendar-plus fa-lg"></i> Auction Start Date *</label>
                                    <input type="datetime-local" id="auctionStartDate">
                                    <div class="inputIndicator" id="auctionStartDateIndicator"></div>
                                    <div class="errorMessage" id="auctionStartDateError"></div>
                                    <div class="inputHelp">Must be in the future</div>
                                </div>
                                <div class="inputGroup half">
                                    <label for="auctionEndDate"><i class="fas fa-calendar-times fa-lg"></i> Auction End Date *</label>
                                    <input type="datetime-local" id="auctionEndDate">
                                    <div class="inputIndicator" id="auctionEndDateIndicator"></div>
                                    <div class="errorMessage" id="auctionEndDateError"></div>
                                    <div class="inputHelp">Must be after start date</div>
                                </div>
                            </div>

                            <div class="formRow">
                                <div class="inputGroup">
                                    <label for="auctionDescription"><i class="fas fa-align-left"></i> Description *</label>
                                    <textarea id="auctionDescription" rows="5" placeholder="Describe your artwork, inspiration, technique, and story behind it..." maxlength="1000" minlength="10"></textarea>
                                    <div class="inputIndicator" id="auctionDescriptionIndicator"></div>
                                    <div class="errorMessage" id="auctionDescriptionError"></div>
                                    <div class="charCounter">
                                        <span id="auctionDescCharCount">0</span>/1000 characters (minimum 10)
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Image Upload -->
                        <div class="formStep" data-step="2">
                            <div class="stepHeader">
                                <h2><i class="fas fa-images"></i> Artwork Images</h2>
                                <p>Upload high-quality images of your artwork for the auction</p>
                            </div>

                            <!-- Primary Image Section -->
                            <div class="imageUploadContainer">
                                <div class="imageUploadSection">
                                    <div class="sectionLabel">
                                        <label for="auctionPrimaryImage"><i class="fas fa-star"></i> Primary Auction Image</label>
                                        <span class="sectionDescription">This will be the main image displayed for your auction</span>
                                    </div>
                                    <div class="uploadZone primaryUploadZone" id="auctionPrimaryUploadZone">
                                        <div class="uploadContent">
                                            <i class="fas fa-star"></i>
                                            <h3>Drag & Drop Primary Image Here</h3>
                                            <p>or <span class="uploadLink">browse files</span></p>
                                            <div class="uploadReqs">
                                                <span>• JPG, PNG, WEBP up to 50MB</span>
                                            </div>
                                        </div>
                                        <input type="file" id="auctionPrimaryImage" accept="image/*" hidden>
                                    </div>
                                    
                                    <div class="uploadedImages" id="auctionPrimaryImagePreview">
                                        <!-- Primary image preview will appear here -->
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Images Section -->
                            <div class="imageUploadContainer">
                                <div class="imageUploadSection">
                                    <div class="sectionLabel">
                                        <label for="auctionImages"><i class="fas fa-images"></i> Additional Auction Images</label>
                                        <span class="sectionDescription">Upload additional views and details of your artwork</span>
                                    </div>
                                    <div class="uploadZone" id="auctionUploadZone">
                                        <div class="uploadContent">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <h3>Drag & Drop Additional Images Here</h3>
                                            <p>or <span class="uploadLink">browse files</span></p>
                                            <div class="uploadReqs">
                                                <span>• JPG, PNG, WEBP up to 50MB each</span>
                                                <span>• Maximum 9 additional images</span>
                                            </div>
                                        </div>
                                        <input type="file" id="auctionImages" multiple accept="image/*" hidden>
                                    </div>

                                    <div class="uploadedImages" id="auctionUploadedImages">
                                        <!-- Uploaded images will appear here -->
                                    </div>
                                </div>
                            </div>

                                <div class="imageGuidelines">
                                    <h4><i class="fas fa-lightbulb"></i> Photography Tips for Auctions</h4>
                                    <div class="guidelinesList">
                                        <div class="guideline">
                                            <i class="fas fa-check"></i>
                                            <span>Use professional lighting to highlight details</span>
                                        </div>
                                        <div class="guideline">
                                            <i class="fas fa-check"></i>
                                            <span>Include multiple angles and close-up detail shots</span>
                                        </div>
                                        <div class="guideline">
                                            <i class="fas fa-check"></i>
                                            <span>Show the artwork signature or authentication</span>
                                        </div>
                                        <div class="guideline">
                                            <i class="fas fa-check"></i>
                                            <span>Capture true colors to avoid bidder disappointment</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Preview -->
                        <div class="formStep" data-step="3">
                            <div class="stepHeader">
                                <h2><i class="fas fa-eye"></i> Preview & Launch</h2>
                                <p>Review your auction listing before launching</p>
                            </div>

                            <div class="artworkPreview" id="auctionPreview">
                                <!-- Preview will be generated here -->
                            </div>
                        </div>

                        <div class="formNavigation">
                            <button type="button" class="btn btn-secondary" id="auctionPrevStep" style="display: none;">
                                <i class="fas fa-arrow-left"></i>
                                Previous
                            </button>
                            <div class="stepInfo">
                                Step <span id="auctionCurrentStepNum">1</span> of 3
                            </div>
                            <button type="button" class="btn btn-primary" id="auctionNextStep">
                                Next
                                <i class="fas fa-arrow-right"></i>
                            </button>
                            <button type="submit" class="btn btn-success" id="launchAuctionBtn" style="display: none;">
                                <i class="fas fa-gavel"></i>
                                Launch Auction
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Modals -->
    <div class="modal" id="orderModal">
        <div class="modalContent">
            <div class="modalHeader">
                <h2>Order Details</h2>
                <button class="modalClose" onclick="closeModal('orderModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modalBody">
                <div id="orderDetails">
                    <!-- Order details will be populated here -->
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="trackingModal">
        <div class="modalContent">
            <div class="modalHeader">
                <h2>Order Tracking</h2>
                <button class="modalClose" onclick="closeModal('trackingModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modalBody">
                <div id="trackingDetails">
                    <!-- Tracking details will be populated here -->
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="changeEmailModal">
        <div class="modalContent">
            <div class="modalHeader">
                <h2>Change Email Address</h2>
                <button class="modalClose" onclick="closeModal('changeEmailModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modalBody">
                <!-- Step 1: Enter New Email -->
                <div id="emailStep1" class="verification-step active">
                    <div class="formGroup">
                        <label for="newEmail">New Email Address:</label>
                        <input type="email" id="newEmail" name="newEmail" class="form-control" placeholder="Enter new email address">
                        <div class="indicatorField">
                            <div class="indicator" id="newEmailIndicator"></div>
                        </div>
                        <div class="errorMessage" id="newEmailError"></div>
                    </div>
                    <div class="modalActions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('changeEmailModal')">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="sendVerificationCode()" id="sendCodeBtn">Send Verification Code</button>
                    </div>
                </div>

                <!-- Step 2: Enter Verification Code -->
                <div id="emailStep2" class="verification-step">
                    <div class="verification-info">
                        <p><i class="fas fa-envelope"></i> We've sent a 6-digit verification code to:</p>
                        <p class="email-display" id="displayNewEmail"></p>
                        <p class="info-text">Please check your email and enter the code below. The code will expire in 15 minutes.</p>
                    </div>
                    <div class="formGroup">
                        <label for="verificationCode">Verification Code:</label>
                        <input type="text" id="verificationCode" name="verificationCode" class="form-control verification-input" placeholder="Enter 6-digit code" maxlength="6">
                        <div class="indicatorField">
                            <div class="indicator" id="verificationCodeIndicator"></div>
                        </div>
                        <div class="errorMessage" id="verificationCodeError"></div>
                    </div>
                    <div class="modalActions">
                        <button type="button" class="btn btn-outline" onclick="goBackToEmailStep()">Back</button>
                        <button type="button" class="btn btn-secondary" onclick="resendVerificationCode()" id="resendCodeBtn">Resend Code</button>
                        <button type="button" class="btn btn-primary" onclick="verifyCodeAndUpdateEmail()" id="verifyCodeBtn">Verify & Update</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Artwork Modal -->
    <div class="modal" id="editArtworkModal" onclick="closeModalOnBackdrop(event, 'editArtworkModal')">
        <div class="modalContent artwork-edit-modal" onclick="event.stopPropagation()" style="max-width: 800px; max-height: 90vh; overflow-y: auto;">
            <div class="modalHeader" style="position: sticky; top: 0; background: white; z-index: 10; padding: 20px; border-bottom: 1px solid #eee;">
                <h2><i class="fas fa-edit"></i> Edit Artwork</h2>
                <button class="modalClose" onclick="closeModal('editArtworkModal')" type="button" 
                        style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 20px; cursor: pointer; color: #666; z-index: 1000; padding: 8px; line-height: 1; border-radius: 50%; transition: all 0.2s ease;"
                        onmouseover="this.style.color='#333'; this.style.backgroundColor='#f5f5f5'" 
                        onmouseout="this.style.color='#666'; this.style.backgroundColor='transparent'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modalBody" style="padding: 30px;">
                <form id="editArtworkForm" class="artwork-edit-form">
                    <input type="hidden" id="editArtworkId" name="artwork_id">
                    
                    <!-- Title -->
                    <div class="form-row" style="margin-bottom: 25px;">
                        <div class="form-group full-width">
                            <label for="editArtworkTitle" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;"><i class="fas fa-signature"></i> Artwork Title *</label>
                            <input type="text" id="editArtworkTitle" name="title" placeholder="Give your artwork a captivating title..." required
                                   style="width: 100%; padding: 12px 16px; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 14px; transition: border-color 0.2s ease; background: #fafbfc;"
                                   onfocus="this.style.borderColor='#6B4423'; this.style.backgroundColor='white'"
                                   onblur="this.style.borderColor='#e1e5e9'; this.style.backgroundColor='#fafbfc'">
                            <div class="inputIndicator" id="editArtworkTitleIndicator"></div>
                            <div class="errorMessage" id="editArtworkTitleError"></div>
                        </div>
                    </div>

                    <!-- Price and Quantity -->
                    <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 25px;">
                        <div class="form-group" style="flex: 1;">
                            <label for="editArtworkPrice" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;"><i class="fas fa-tag"></i> Price (EGP) *</label>
                            <div class="priceInput" style="position: relative;">
                                <span class="currency" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #666; font-weight: 500; z-index: 5;">EGP</span>
                                <input type="number" id="editArtworkPrice" name="price" placeholder="0.00" step="0.01" min="0" required
                                       style="width: 100%; padding: 12px 16px 12px 50px; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 14px; transition: border-color 0.2s ease; background: #fafbfc;"
                                       onfocus="this.style.borderColor='#6B4423'; this.style.backgroundColor='white'"
                                       onblur="this.style.borderColor='#e1e5e9'; this.style.backgroundColor='#fafbfc'">
                            </div>
                            <div class="inputIndicator" id="editArtworkPriceIndicator"></div>
                            <div class="errorMessage" id="editArtworkPriceError"></div>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="editArtworkQuantity" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;"><i class="fas fa-boxes"></i> Stock Quantity *</label>
                            <input type="number" id="editArtworkQuantity" name="quantity" placeholder="1" min="1" required
                                   style="width: 100%; padding: 12px 16px; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 14px; transition: border-color 0.2s ease; background: #fafbfc;"
                                   onfocus="this.style.borderColor='#6B4423'; this.style.backgroundColor='white'"
                                   onblur="this.style.borderColor='#e1e5e9'; this.style.backgroundColor='#fafbfc'">
                            <div class="inputIndicator" id="editArtworkQuantityIndicator"></div>
                            <div class="errorMessage" id="editArtworkQuantityError"></div>
                        </div>
                    </div>

                    <!-- Type -->
                    <div class="form-row" style="margin-bottom: 25px;">
                        <div class="form-group">
                            <label for="editArtworkType" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;"><i class="fas fa-layer-group"></i> Artwork Type *</label>
                            <input type="text" id="editArtworkType" name="type" placeholder="e.g., Painting, Sculpture, Photography" required
                                   style="width: 100%; padding: 12px 16px; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 14px; transition: border-color 0.2s ease; background: #fafbfc;"
                                   onfocus="this.style.borderColor='#6B4423'; this.style.backgroundColor='white'"
                                   onblur="this.style.borderColor='#e1e5e9'; this.style.backgroundColor='#fafbfc'">
                            <div class="inputIndicator" id="editArtworkTypeIndicator"></div>
                            <div class="errorMessage" id="editArtworkTypeError"></div>
                        </div>
                    </div>

                    <!-- Dimensions and Year -->
                    <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 25px;">
                        <div class="form-group" style="flex: 1;">
                            <label for="editArtworkDimensions" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;"><i class="fas fa-ruler"></i> Dimensions *</label>
                            <input type="text" id="editArtworkDimensions" name="dimensions" placeholder="e.g., 50 x 70 cm, 30 x 40 x 10 cm" required
                                   style="width: 100%; padding: 12px 16px; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 14px; transition: border-color 0.2s ease; background: #fafbfc;"
                                   onfocus="this.style.borderColor='#6B4423'; this.style.backgroundColor='white'"
                                   onblur="this.style.borderColor='#e1e5e9'; this.style.backgroundColor='#fafbfc'">
                            <div class="inputIndicator" id="editArtworkDimensionsIndicator"></div>
                            <div class="errorMessage" id="editArtworkDimensionsError"></div>
                            <div class="inputHelp" style="font-size: 12px; color: #666; margin-top: 4px;">Format: Width x Height x Depth (if applicable)</div>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="editArtworkYear" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;"><i class="fas fa-calendar"></i> Year Created</label>
                            <input type="number" id="editArtworkYear" name="year" placeholder="2025" min="1800" max="2025"
                                   style="width: 100%; padding: 12px 16px; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 14px; transition: border-color 0.2s ease; background: #fafbfc;"
                                   onfocus="this.style.borderColor='#6B4423'; this.style.backgroundColor='white'"
                                   onblur="this.style.borderColor='#e1e5e9'; this.style.backgroundColor='#fafbfc'">
                            <div class="inputIndicator" id="editArtworkYearIndicator"></div>
                            <div class="errorMessage" id="editArtworkYearError"></div>
                        </div>
                    </div>

                    <!-- Availability and Auction -->
                    <div class="form-row" style="display: flex; gap: 20px; margin-bottom: 25px;">
                        <div class="form-group" style="flex: 1;">
                            <label for="editArtworkAvailable" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;"><i class="fas fa-check-circle"></i> Availability</label>
                            <select id="editArtworkAvailable" name="is_available"
                                    style="width: 100%; padding: 12px 16px; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 14px; transition: border-color 0.2s ease; background: #fafbfc; cursor: pointer;"
                                    onfocus="this.style.borderColor='#6B4423'; this.style.backgroundColor='white'"
                                    onblur="this.style.borderColor='#e1e5e9'; this.style.backgroundColor='#fafbfc'">
                                <option value="1">Available</option>
                                <option value="0">Not Available</option>
                            </select>
                            <div class="inputIndicator" id="editArtworkAvailableIndicator"></div>
                            <div class="errorMessage" id="editArtworkAvailableError"></div>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="editArtworkAuction" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;"><i class="fas fa-gavel"></i> On Auction</label>
                            <select id="editArtworkAuction" name="on_auction"
                                    style="width: 100%; padding: 12px 16px; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 14px; transition: border-color 0.2s ease; background: #fafbfc; cursor: pointer;"
                                    onfocus="this.style.borderColor='#6B4423'; this.style.backgroundColor='white'"
                                    onblur="this.style.borderColor='#e1e5e9'; this.style.backgroundColor='#fafbfc'">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                            <div class="inputIndicator" id="editArtworkAuctionIndicator"></div>
                            <div class="errorMessage" id="editArtworkAuctionError"></div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="form-row" style="margin-bottom: 30px;">
                        <div class="form-group full-width">
                            <label for="editArtworkDescription" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;"><i class="fas fa-align-left"></i> Description *</label>
                            <textarea id="editArtworkDescription" name="description" rows="5" placeholder="Describe your artwork, inspiration, technique, and story behind it..." maxlength="1000" required
                                      style="width: 100%; padding: 12px 16px; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 14px; transition: border-color 0.2s ease; background: #fafbfc; resize: vertical; min-height: 120px;"
                                      onfocus="this.style.borderColor='#6B4423'; this.style.backgroundColor='white'"
                                      onblur="this.style.borderColor='#e1e5e9'; this.style.backgroundColor='#fafbfc'"></textarea>
                            <div class="inputIndicator" id="editArtworkDescriptionIndicator"></div>
                            <div class="errorMessage" id="editArtworkDescriptionError"></div>
                            <div class="charCounter" style="text-align: right; font-size: 12px; color: #666; margin-top: 4px;">
                                <span id="editDescCharCount">0</span>/1000 characters
                            </div>
                        </div>
                    </div>

                    <div class="form-group full-width" style="margin-bottom: 30px;">
                        <div class="imageUploadContainer">
                            <div class="imageUploadSection">
                                <div class="sectionLabel" style="margin-bottom: 15px;">
                                    <label style="display: block; font-weight: 600; color: #333; margin-bottom: 4px;"><i class="fas fa-images"></i> Artwork Photos</label>
                                    <span class="sectionDescription" style="font-size: 14px; color: #666;">Manage additional photos of your artwork</span>
                                </div>
                                <div id="artworkPhotosContainer" class="artwork-photos-container" style="border: 2px dashed #e1e5e9; border-radius: 8px; padding: 20px; text-align: center; background: #fafbfc;">
                                    <!-- Photos will be loaded here dynamically -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions" style="display: flex; justify-content: flex-end; gap: 15px; padding-top: 20px; border-top: 1px solid #eee;">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editArtworkModal')"
                                style="padding: 12px 24px; border: 2px solid #ddd; background: white; color: #666; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.2s ease;"
                                onmouseover="this.style.borderColor='#bbb'; this.style.color='#555'"
                                onmouseout="this.style.borderColor='#ddd'; this.style.color='#666'">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary"
                                style="padding: 12px 24px; border: none; background: #6B4423; color: white; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.2s ease;"
                                onmouseover="this.style.backgroundColor='#5a3a1e'"
                                onmouseout="this.style.backgroundColor='#6B4423'">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <style>
        /* Enhanced Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.2s ease;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex !important;
            opacity: 1;
        }
        
        .modalContent {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            transform: scale(0.9);
            transition: transform 0.2s ease;
            position: relative;
        }
        
        .modal.active .modalContent {
            transform: scale(1);
        }
        
        .modal .modalClose:hover {
            background-color: #f5f5f5 !important;
            color: #333 !important;
        }
        
        body.modal-open {
            overflow: hidden !important;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .modalContent.artwork-edit-modal {
                max-width: 95% !important;
                margin: 10px;
            }
            
            .form-row {
                flex-direction: column !important;
            }
            
            .form-row .form-group {
                flex: none !important;
                margin-bottom: 15px;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="./public/artist-portal.js?v=<?php echo time(); ?>"></script>
    
    <!-- Sales Chart Script -->
    <script>
        // Global variable to track chart instance
        let salesChartInstance = null;
        
        // Initialize Sales Chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('salesChart');
            if (ctx) {
                // Destroy existing chart if it exists
                if (salesChartInstance) {
                    salesChartInstance.destroy();
                    salesChartInstance = null;
                }
                
                // Create new chart instance
                salesChartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($chart_labels); ?>,
                        datasets: [{
                            label: 'Monthly Revenue (EGP)',
                            data: <?php echo json_encode($chart_data); ?>,
                            borderColor: '#8B4513',
                            backgroundColor: 'rgba(139, 69, 19, 0.1)',
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#8B4513',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'EGP ' + value.toLocaleString();
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        },
                        elements: {
                            point: {
                                hoverRadius: 6
                            }
                        }
                    }
                });
            }
        });
        
        // Clean up chart when page unloads
        window.addEventListener('beforeunload', function() {
            if (salesChartInstance) {
                salesChartInstance.destroy();
                salesChartInstance = null;
            }
        });
    </script>
    
    <script>
        // Debug script to check profile update functionality
        console.log('🔧 Artist Portal Debug Script Loaded');
        
        // Check if our functions are available
        setTimeout(() => {
            console.log('📋 Profile Update Functions Check:', {
                updateArtistProfile: typeof updateArtistProfile,
                saveProfileChanges: typeof saveProfileChanges,
                initializeProfileSaveButton: typeof initializeProfileSaveButton
            });
            
            // Check if form elements exist
            console.log('📋 Form Elements Check:', {
                artistInfoForm: !!document.getElementById('artistInfoForm'),
                artistBio: !!document.getElementById('artistBio'),
                artistPhone: !!document.getElementById('artistPhone'),
                artistEmail: !!document.getElementById('artistEmail'),
                artistSpecialty: !!document.getElementById('artistSpecialty'),
                artistExperience: !!document.getElementById('artistExperience'),
                addAchievementBtn: !!document.getElementById('addAchievementBtn')
            });
            
            // Try to initialize manually if needed
            if (typeof initializeProfileSaveButton === 'function') {
                console.log('🚀 Manually triggering initializeProfileSaveButton...');
                initializeProfileSaveButton();
            }
            
            // Check if we're in the profile section
            const profileSection = document.getElementById('profile-section');
            if (profileSection) {
                console.log('✅ Profile section found:', profileSection.style.display !== 'none');
            }
            
        }, 1000);
        
        // Add click listener to profile nav item to re-initialize when switching sections
        document.addEventListener('DOMContentLoaded', function() {
            const profileNavItem = document.querySelector('[data-section="profile"]');
            if (profileNavItem) {
                profileNavItem.addEventListener('click', function() {
                    console.log('📍 Profile section clicked, re-initializing...');
                    setTimeout(() => {
                        if (typeof initializeProfileSaveButton === 'function') {
                            initializeProfileSaveButton();
                        }
                    }, 200);
                });
            }
        });
    </script>
    <script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/68ad7c376c34a5192ea60d8f/1j3iqqep5';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>
</body>
</html>