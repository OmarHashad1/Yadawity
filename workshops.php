<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yadawity - Workshops</title>
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
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="./components/Navbar/navbar.css" />
    <link rel="stylesheet" href="./components/BurgerMenu/burger-menu.css" />
    <link rel="stylesheet" href="./public/homePage.css" />
    <link rel="stylesheet" href="./public/workshops.css">
    <link rel="icon" type="image/x-icon" href="./image/YAD-05.ico">
  
</head>
<body>
    <?php include './components/includes/navbar.php'; ?>

    <?php include './components/includes/burger-menu.php'; ?>

<div class="container">
        <!-- Header -->
        <header class="page-header">
            <div class="course-header-container">
                <h1 class="page-title">WORKSHOPS</h1>
            </div>
        </header>

        <!-- Search Section -->
        <div class="search-section">
            <!-- Hero Section -->
            <div class="search-hero">
                <h2>Find Your Workshop</h2>
            </div>

            <!-- Main Search Bar -->
             <div class="main-search">
                <div class="search-wrapper">
                    <input 
                        type="text"
                        class="search-input"
                        id="searchInput"
                        placeholder="Search gallery by date,location,time..."
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
                    <h3>Filter Sessions</h3>
                    <button class="clear-filters-btn" onclick="clearAllFilters()">
                        <i class="fas fa-times"></i> Clear All
                    </button>
                </div>

                <div class="filters-grid">
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-user-md"></i>
                            Doctor
                        </label>
                        <select class="filter-select" id="doctorFilter">
                            <option value="">All Doctors</option>
                            <option value="Dr. Ahmed Hassan">Dr. Ahmed Hassan</option>
                            <option value="Dr. Sara Youssef">Dr. Sara Youssef</option>
                            <option value="Dr. Mona Khaled">Dr. Mona Khaled</option>
                            <option value="Dr. Tarek Nabil">Dr. Tarek Nabil</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-palette"></i>
                            Category
                        </label>
                        <select class="filter-select" id="categoryFilter">
                            <option value="">All Categories</option>
                            <option value="Cognitive Art Therapy">Cognitive Art Therapy</option>
                            <option value="Dialectical Behavior Therapy (DBT) with Art">Dialectical Behavior Therapy (DBT) with Art</option>
                            <option value="Trauma-Informed Art Therapy">Trauma-Informed Art Therapy</option>
                            <option value="Behavioral Art Therapy">Behavioral Art Therapy</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-map-marker-alt"></i>
                            City
                        </label>
                        <select class="filter-select" id="cityFilter">
                            <option value="">All Cities</option>
                            <option value="Cairo">Cairo</option>
                            <option value="Alexandria">Alexandria</option>
                            <option value="Giza">Giza</option>
                            <option value="Mansoura">Mansoura</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-road"></i>
                            Street
                        </label>
                        <select class="filter-select" id="streetFilter">
                            <option value="">All Streets</option>
                            <option value="Tahrir Street">Tahrir Street</option>
                            <option value="El Merghany Street">El Merghany Street</option>
                            <option value="El Haram Street">El Haram Street</option>
                            <option value="Port Said Street">Port Said Street</option>
                            <option value="El Nasr Road">El Nasr Road</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-tag"></i>
                            Price Range
                        </label>
                        <div class="price-range-container">
                            <div class="price-input-wrapper">
                                <span class="currency-symbol">$</span>
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
                                <span class="currency-symbol">$</span>
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

                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-calendar-alt"></i>
                            Date
                        </label>
                        <select class="filter-select" id="dateFilter">
                            <option value="">All Dates</option>
                            <option value="2025-07-30">Today (July 30, 2025)</option>
                            <option value="2025-07-31">Tomorrow (July 31, 2025)</option>
                            <option value="2025-08-01">Friday (August 1, 2025)</option>
                            <option value="2025-08-02">Saturday (August 2, 2025)</option>
                            <option value="2025-08-03">Sunday (August 3, 2025)</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-clock"></i>
                            Time
                        </label>
                        <input type="time" class="filter-input" id="timeFilter">
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

        <!-- Courses Grid -->
        <div class="courses-grid" id="coursesGrid">
            <!-- Course cards will be dynamically added here -->
        </div>

    <!-- Course count (used by workshops.js) -->
    <div class="course-count" id="courseCount" style="color: #fff; margin-top: 1rem;"></div>

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
            <h3>No galleries found</h3>
            <p>Try adjusting your search terms or filters</p>
            <button class="clear-search-btn" onclick="clearAllFilters()">Clear All Filters</button>
        </div>
    </div>
<?php include './components/includes/footer.php'; ?>

</div>

<!-- Workshop Info Modal -->
<div id="doctorInfoModal" class="doctor-modal-overlay" style="display:none;">
    <div class="doctor-modal">
        <button class="close-doctor-modal" onclick="closeDoctorModal()">
            <i class="fas fa-times"></i>
        </button>
        
        <div class="modal-header">
            <div class="workshop-header-info">
                <h2 id="workshopTitle" class="workshop-title"></h2>
                <div class="doctor-avatar-container">
                    <img id="doctorPhoto" src="" alt="Doctor Photo" class="doctor-avatar" 
                         onerror="this.src='/image/placeholder-artwork.jpg';">
                </div>
            </div>
        </div>
        
        <div class="modal-body">
            <div class="workshop-info">
                <div class="doctor-info-section">
                    <h3 class="section-title">Doctor Information</h3>
                    <div class="doctor-credentials">
                        <div class="credential-item">
                            <span class="credential-label">Doctor:</span>
                            <span id="doctorName" class="credential-value"></span>
                        </div>
                        <div class="credential-item">
                            <span class="credential-label">Grade:</span>
                            <span id="doctorGrade" class="credential-value"></span>
                        </div>
                        <div class="credential-item experience-item">
                            <span class="credential-label">Experience:</span>
                            <div id="doctorExperience" class="credential-value experience-text"></div>
                        </div>
                    </div>
                </div>
                
                <div class="workshop-details-section">
                    <h3 class="section-title">Workshop Details</h3>
                    <div class="workshop-details">
                        <div class="detail-row">
                            <div class="detail-item">
                                <span class="detail-label">Category:</span>
                                <span id="workshopCategory" class="detail-value"></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Date:</span>
                                <span id="workshopDate" class="detail-value"></span>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-item">
                                <span class="detail-label">Time:</span>
                                <span id="workshopTime" class="detail-value"></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Capacity:</span>
                                <span id="workshopCapacity" class="detail-value"></span>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-item">
                                <span class="detail-label">Location:</span>
                                <span id="workshopLocation" class="detail-value"></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Price:</span>
                                <span id="workshopPrice" class="detail-value"></span>
                            </div>
                        </div>
                        <div class="workshop-description">
                            <span class="detail-label">Description:</span>
                            <p id="workshopDescription" class="detail-value description-text"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button class="book-visit-btn" id="modalBookVisitBtn" onclick="addWorkshopToCart()">
                <i class="fas fa-calendar-check"></i>
                BOOK VISIT
            </button>
        </div>
    </div>
</div>

<script src="./components/Navbar/navbar.js"></script>
<script src="./components/BurgerMenu/burger-menu.js"></script>
<script src="./public/workshops.js"></script>
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
<!-- test script removed -->
</body>
</html>