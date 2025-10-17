
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/x-icon" href="./image/YAD-05.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Artwork Collection | Yadawity Gallery</title>
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
      href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;700&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
      integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    />
    <link rel="stylesheet" href="./components/Navbar/navbar.css" />
    <link rel="stylesheet" href="./components/BurgerMenu/burger-menu.css" />
    <link rel="stylesheet" href="./public/homePage.css" />
    <link rel="stylesheet" href="./public/artwork.css">
    
    <style>
    .gallery-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding: 0;
    }
    
    .gallery-header h2 {
        font-family: 'Playfair Display', serif;
        font-size: 2rem;
        color: #2c3e50;
        margin: 0;
    }
    
    .artwork-count-container {
        background: #f8f9fa;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 500;
        color: #666;
    }
    
    .no-artworks-message,
    .error-message {
        grid-column: 1 / -1;
        text-align: center;
        padding: 4rem 2rem;
        background: #f8f9fa;
        border-radius: 12px;
        margin: 2rem 0;
    }
    
    .message-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }
    
    .message-content h3 {
        font-family: 'Playfair Display', serif;
        font-size: 1.5rem;
        margin: 0;
        color: #6a5424ff;
    }
    
    .message-content p {
        color: #666;
        margin: 0;
        max-width: 400px;
    }
    
    .retry-btn {
        background: #3498db;
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: background 0.3s ease;
    }
    
    .retry-btn:hover {
        background: #2980b9;
    }
    
    .enhanced-artwork-card img {
        width: 100%;
        height: auto;
        display: block;
        object-fit: cover;
        border-radius: 8px 8px 0 0;
        background: #f8f9fa;
    }
    
    .no-image-available {
        width: 100%;
        height: 200px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 8px 8px 0 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #6c757d;
        font-size: 0.9rem;
        border: 2px dashed #dee2e6;
        transition: all 0.3s ease;
    }
    
    .no-image-available:hover {
        background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
        border-color: #adb5bd;
    }
    
    .no-image-icon {
        font-size: 3rem;
        color: #adb5bd;
        margin-bottom: 0.5rem;
    }
    
    .no-image-text {
        font-weight: 500;
        text-align: center;
    }
    
    .image-error-indicator {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(231, 76, 60, 0.9);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
        z-index: 10;
    }
    
    .enhanced-add-to-cart:disabled {
        background: #95a5a6;
        cursor: not-allowed;
        opacity: 0.7;
    }
    
    .enhanced-add-to-cart:disabled:hover {
        background: #95a5a6;
        transform: none;
    }
    
    @media (max-width: 768px) {
        .gallery-header {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }
        
        .gallery-header h2 {
            font-size: 1.5rem;
        }
    }
    
    /* Search Bar Styles - Matching Auction/Course Pages */
    .main-search {
        position: relative;
        max-width: 600px;
        margin: 0 auto 3rem;
    }
    
    .search-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        background: rgb(255, 255, 255);
        border-radius: 3px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
    }

    .search-wrapper:hover {
        border-color: #8b7355;
        box-shadow: 0 6px 25px rgba(0, 0, 0, 0.15);
    }

    .search-wrapper:focus-within {
        border-color:#8b7355;
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
    }

    .search-input {
        flex: 1;
        padding: 1rem 1.5rem;
        border: none;
        outline: none;
        font-size: 1rem;
        background: transparent;
        color: #2c1810;
        font-family: 'Inter', sans-serif;
        font-weight: 400;
        border-radius: 0;
    }

    .search-input::placeholder {
        color: #6b4423;
        opacity: 0.8;
    }

    .search-btn {
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, #6b4423 0%, #8b7355 100%);
        border: none;
        color: white;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 50px;
        height: 90%;
        border-radius: 3px;
    }

    .search-btn:hover {
        background: linear-gradient(135deg, #4a2c17 0%, #6b4423 100%);
        transform: scale(1.02);
    }

    .search-btn i {
        font-size: 1.1rem;
        transition: transform 0.3s ease;
    }

    .search-btn:hover i {
        transform: scale(1.1);
    }
    
    /* Active Filters Styles */
   
    
    .active-filters-container {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .filter-tag {
        display: inline-flex;
        align-items: center;
        background: #6b4423;
        color: white;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
        gap: 0.5rem;
        animation: fadeIn 0.3s ease;
    }
    
    .filter-tag .remove-filter {
        cursor: pointer;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        transition: background 0.2s ease;
    }
    
    .filter-tag .remove-filter:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.1);
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @media (max-width: 768px) {
        .active-filters-section {
            margin: 1rem -1rem 0 -1rem;
        }
        
        .filter-tag {
            font-size: 0.8rem;
            padding: 0.3rem 0.6rem;
        }
    }
    </style>
  </head>

  <body>
    <!-- Navigation -->
    <?php include './components/includes/navbar.php'; ?>

    <?php include './components/includes/burger-menu.php'; ?>
    
    <div class="container">
      <header class="page-header">
            <div class="course-header-container">
                <h1 class="page-title">ARTWORKS</h1>
              </div>
        </header>
    <!-- Search Section -->
<!-- Search Section -->
        <div class="search-section">
            <!-- Hero Section -->
            <div class="search-hero">
                <h2>Discover Unique Artworks</h2>
                <p class="search-subtitle">Browse through our curated collection of artworks</p>
            </div>

      <!-- Main Search Bar -->
      <div class="main-search">
        <div class="search-wrapper">
            <input 
                type="text"
                class="search-input"
                id="searchInput"
                placeholder="Search artworks by name, artist, or style..."
                autocomplete="off"
            >
            <button class="search-btn" onclick="applyFilters()">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </div>

    <!-- Enhanced Filters Container (matches auction page) -->
    <div class="filters-container">
        <div class="filters-header">
            <h3>Filter Artworks</h3>
            <button class="clear-filters-btn" onclick="clearAllFilters()">
                <i class="fas fa-times"></i> Clear All
            </button>
        </div>
        <div class="filters-grid">
            <div class="filter-group">
                <label class="filter-label">
                    <i class="fas fa-th-large"></i>
                    Category
                </label>
                <select class="filter-select" id="categoryFilter">
                    <option value="all">All Categories</option>
                    <!-- Options will be populated dynamically by JS if needed -->
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
                    <i class="fas fa-dollar-sign"></i>
                    Price Range
                </label>
                <div class="price-range">
                    <div class="price-input-wrapper">
                        <span class="currency-symbol">$</span>
                        <input type="number" id="minPrice" class="filter-input price-input" placeholder="Min" min="0">
                    </div>
                    <span class="price-separator">-</span>
                    <div class="price-input-wrapper">
                        <span class="currency-symbol">$</span>
                        <input type="number" id="maxPrice" class="filter-input price-input" placeholder="Max" min="0">
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
        <!-- Active Filters Display -->
        <div class="active-filters-section" id="activeFiltersSection" style="display: none;">
            <div class="active-filters-header">
                <h4>Active Filters:</h4>
            </div>
            <div class="active-filters-container" id="activeFilters">
                <!-- Active filter tags will be displayed here -->
            </div>
        </div>
    </div>

    </div>


     
    
    <!-- Artwork Gallery Section -->
    <section class="artwork-gallery">
      <div class="gallery-header">
       
        <div class="artwork-count-container">
          <span>Showing <span id="artworkCount">0</span> artworks</span>
        </div>
      </div>
      
      <div class="artworks-grid" id="artworksGrid">
        <!-- Artworks will be loaded dynamically via JavaScript -->
        <div class="loading-message" id="loadingMessage">
          <div class="message-content">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #3498db; margin-bottom: 1rem;"></i>
            <p>Loading artworks...</p>
          </div>
        </div>
      </div>
    </section>

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
    </div>

    <!-- Footer -->
    <?php include './components/includes/footer.php'; ?>
    
    <!-- SweetAlert2 for cart notifications -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Cart functionality (must be loaded before other scripts that use it) -->
    <script src="./public/cart.js"></script>
    
    <!-- Wishlist functionality -->
    <script src="./public/wishlist-manager.js"></script>
    
    <!-- Main app functionality -->
    <script src="./app.js"></script>
    
    <!-- Navbar functionality for user dropdown and navigation -->
    <script src="./components/Navbar/navbar.js"></script>
    <script src="./components/BurgerMenu/burger-menu.js"></script>
    <script src="./public/artwork.js"></script>
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