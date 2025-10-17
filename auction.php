<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Auction House - Yadawity Gallery</title>
    <link rel="icon" type="image/x-icon" href="./image/YAD-05.ico">
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
    <link
      href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&display=swap"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
      integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    />
    <link rel="stylesheet" href="./components/Navbar/navbar.css" />`n    <link rel="stylesheet" href="./components/BurgerMenu/burger-menu.css" />
    <link rel="stylesheet" href="./public/homePage.css" />
    <link rel="stylesheet" href="./public/auction.css" />

  </head>
  <body>
    <?php include './components/includes/navbar.php'; ?>

    <?php include './components/includes/burger-menu.php'; ?>
        
      </div>
    </div>
    <div class="container">
      <!-- Page Header -->
    <header class="page-header">
        <div class="course-header-container">
            <h1 class="page-title">AUCTION HOUSE</h1>
        </div>
    </header>

    <!-- Search Section -->
    <div class="search-section">
        <!-- Hero Section -->
        <div class="search-hero">
            <h2>Discover Unique Artworks</h2>
            <p class="search-subtitle">Browse through our curated collection of art auctions</p>
        </div>

        <!-- Main Search Bar -->
        <div class="main-search">
            <div class="search-wrapper">
                <input 
                    type="text"
                    class="search-input"
                    id="searchInput"
                    placeholder="Search auctions by artwork, artist, or medium..."
                    autocomplete="off"
                >
                <button class="search-btn" onclick="applyFilters()">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>

        <!-- Enhanced Filters Container -->
        <div class="filters-container">
            <div class="filters-header">
                <h3>Filter Auctions</h3>
                <button class="clear-filters-btn" onclick="clearAllFilters()">
                    <i class="fas fa-times"></i> Clear All
                </button>
            </div>

            <div class="filters-grid">

                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-palette"></i>
                        Category
                    </label>
                    <select class="filter-select" id="categoryFilter">
                        <option value="">All Categories</option>
                        <option value="Portraits">Portraits</option>
                        <option value="Landscapes">Landscapes</option>
                        <option value="Abstract">Abstract</option>
                        <option value="Photography">Photography</option>
                        <option value="Mixed Media">Mixed Media</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-clock"></i>
                        Status
                    </label>
                    <select class="filter-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="live">Live Now</option>
                        <option value="upcoming">Upcoming</option>
                        <option value="ended">Ended</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-user"></i>
                        Artist
                    </label>
                    <select class="filter-select" id="artistFilter">
                        <option value="all">All Artists</option>
                        <!-- Options will be populated dynamically by JS -->
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-tag"></i>
                        Price Range
                    </label>
                    <div class="price-range">
                        <div class="price-input-wrapper">
                            <span class="currency-symbol">EGP</span>
                            <input
                                type="number"
                                class="filter-input price-input"
                                id="minPrice"
                                placeholder="Min"
                                min="0"
                            >
                        </div>
                        <span class="price-separator">-</span>
                        <div class="price-input-wrapper">
                            <span class="currency-symbol">EGP</span>
                            <input
                                type="number"
                                class="filter-input price-input"
                                id="maxPrice"
                                placeholder="Max"
                                min="0"
                            >
                        </div>
                    </div>
                </div>
            </div>

            <div class="filters-actions">
                <button class="apply-filters-btn" onclick="applyFilters()">
                    <i class="fas fa-filter"></i>
                    Apply Filters
                </button>
            </div>
        </div>

        <!-- Active Filters Display -->
        <div class="active-filters" id="activeFilters"></div>

        <!-- Search Results -->
        <div class="search-results" id="searchResults"></div>
    </div>
      </div>

    

    <!-- Auction Grid -->
    <div class="auctionGrid" id="auctionGrid">
        <!-- Dynamic auction cards will be loaded here via JavaScript -->
        <div class="loading-container" id="loadingContainer">
            <div class="loading-spinner"></div>
            <p>Loading auctions...</p>
        </div>
    </div>

    <!-- Pagination Section -->
    <section class="pagination-section">
        <div class="pagination-container">
            <div class="pagination-controls">
                <button class="pagination-btn prev-btn" id="prevBtn" onclick="previousPage()" disabled>
                    <i class="fas fa-chevron-left"></i>
                    <span>Previous</span>
                </button>
                
                <div class="pagination-numbers" id="paginationNumbers">
                    <button class="pagination-number active" onclick="goToPage(1)">1</button>
                    <button class="pagination-number" onclick="goToPage(2)">2</button>
                    <button class="pagination-number" onclick="goToPage(3)">3</button>
                    <span class="pagination-dots">...</span>
                    <button class="pagination-number" onclick="goToPage(4)">4</button>
                </div>
                
                <button class="pagination-btn next-btn" id="nextBtn" onclick="nextPage()">
                    <span>Next</span>
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </section>

    <!-- No Results -->
    <div class="no-results" id="noResults" style="display: none;">
        <div class="no-results-icon">🎨</div>
        <h3>No auctions found</h3>
        <p>Try adjusting your search terms or filters</p>
        <button class="clear-search-btn" onclick="clearAllFilters()">Clear All Filters</button>
    </div>

    <?php include './components/includes/footer.php'; ?>

    <!-- SweetAlert2 for cart notifications -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Cart functionality (must be loaded before other scripts that use it) -->
    <script src="./public/cart.js"></script>
    

    
    <!-- Navbar functionality (loaded after dependencies) -->
    <script src="./components/Navbar/navbar.js"></script>
    <script src="./components/BurgerMenu/burger-menu.js"></script>
    <script src="./public/auction.js"></script>
    
    <script>
        // Ensure counters are refreshed after page load
        window.addEventListener('load', function() {
            if (typeof refreshNavbarCounters === 'function') {
                setTimeout(refreshNavbarCounters, 50);
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
