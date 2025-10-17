<?php
// Get artist ID from URL parameter
$artist_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// If no ID provided, try to get from other parameter names for backward compatibility
if (!$artist_id) {
    $artist_id = isset($_GET['artist_id']) ? (int)$_GET['artist_id'] : null;
}

// If still no artist ID, redirect to gallery page
if (!$artist_id) {
    header('Location: gallery.php');
    exit();
}

// Include database connection
require_once './API/db.php';

// Verify that the user is a verified artist
try {
    $stmt = $db->prepare("SELECT user_type, is_verified FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $artist_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // User not found
        header('Location: error404.php');
        exit();
    }
    
    $user = $result->fetch_assoc();
    
    // Check if user is an artist and is verified
    if ($user['user_type'] !== 'artist') {
        // User is not an artist (might be a buyer)
        header('Location: error403.php');
        exit();
    }
    
    if (!$user['is_verified'] || $user['is_verified'] == 0) {
        // Artist is not verified
        header('Location: error403.php');
        exit();
    }
    
    $stmt->close();
    
    // Check if current user is viewing their own profile
    $current_user_id = null;
    $is_own_profile = false;
    
    // Check if user is logged in by validating cookie
    if (isset($_COOKIE['user_login'])) {
        $cookieValue = $_COOKIE['user_login'];
        $parts = explode('_', $cookieValue, 2);
        
        if (count($parts) === 2) {
            $current_user_id = (int)$parts[0];
            // If the current user ID matches the artist ID, it's their own profile
            if ($current_user_id === $artist_id) {
                $is_own_profile = true;
            }
        }
    }
    
} catch (Exception $e) {
    // Database error
    error_log("Artist verification error: " . $e->getMessage());
    header('Location: error503.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Artist Profile | Yadawity Gallery</title>

    <link
      href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;700&display=swap"
      rel="stylesheet"
    /> <meta name="description" content="Find and book your perfect local art gallery experience. Explore physical galleries, meet artists, and immerse yourself in local art culture with Yadawity.">
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
    <link rel="icon" type="image/x-icon" href="./image/YAD-05.ico">
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
    <link rel="stylesheet" href="./public/artist-profile.css" />
  </head>

  <body>
    <!-- Navigation -->
    <?php include './components/includes/navbar.php'; ?>

    <?php include './components/includes/burger-menu.php'; ?>
    

    <!-- Artist Hero Section -->
    <section class="artist-hero">
      <div class="artist-hero-content">
        <div class="artist-profile-image">
          <img id="artist-main-image" src="./image/artist-sitting-on-the-floor.jpg" alt="Artist" class="artist-main-image" />
        </div>
        <div class="artist-info">
          <h1 class="artist-name" id="artist-name">Artist Name</h1>
          <p class="artist-specialty" id="artist-specialty">Specialty</p>
          <div class="artist-stats">
            <div class="stat-item">
              <span class="stat-number" id="artist-artwork-count">0</span>
              <span class="stat-label">Masterpieces</span>
            </div>
            <div class="stat-item">
              <span class="stat-number" id="artist-experience">0</span>
              <span class="stat-label">Years Experience</span>
            </div>
            <div class="stat-item">
              <span class="stat-number" id="artist-review-count">0</span>
              <span class="stat-label">Happy Clients</span>
            </div>
          </div>
          <div class="artist-actions">
            <a href="#products" class="btn-primary">
              <i class="fas fa-palette"></i>
              View Artworks
            </a>
            <a href="#about" class="btn-secondary">
              <i class="fas fa-info-circle"></i>
              About Artist
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- About Section -->
    <section class="about-section" id="about">
      <div class="about-container">
        <div class="about-header">
          <div class="section-badge">
            <i class="fas fa-user-circle"></i>
            <span>Artist Story</span>
          </div>
          <h2 class="section-title" id="about-artist-title">About Artist</h2>
          <div class="section-divider"></div>
        </div>
        
        <div class="about-content-wrapper">
          <div class="about-text-content">
            <div class="bio-container">
              <div class="bio-icon">
                <i class="fas fa-quote-left"></i>
              </div>
              <div class="bio-text" id="artist-bio">
                Loading artist biography...
              </div>
              <div class="bio-signature">
                <div class="signature-line"></div>
                <span class="signature-text" id="artist-signature">Artist</span>
              </div>
            </div>
          </div>
          
          <div class="about-stats-sidebar">
            <div class="stats-card">
              <div class="stats-header">
                <i class="fas fa-chart-line"></i>
                <h3>At a Glance</h3>
              </div>
              <div class="stats-grid">
                <div class="stat-box">
                  <div class="stat-icon">
                    <i class="fas fa-palette"></i>
                  </div>
                  <div class="stat-info">
                    <span class="stat-number" id="about-artwork-count">0</span>
                    <span class="stat-label">Artworks</span>
                  </div>
                </div>
                <div class="stat-box">
                  <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                  </div>
                  <div class="stat-info">
                    <span class="stat-number" id="about-experience">0</span>
                    <span class="stat-label">Years</span>
                  </div>
                </div>
                <div class="stat-box">
                  <div class="stat-icon">
                    <i class="fas fa-heart"></i>
                  </div>
                  <div class="stat-info">
                    <span class="stat-number" id="about-reviews">0</span>
                    <span class="stat-label">Reviews</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="achievements-section" id="artist-achievements">
          <div class="achievements-header">
            <div class="section-badge achievements-badge">
              <i class="fas fa-trophy"></i>
              <span>Recognition</span>
            </div>
            <h3 class="achievements-title">Achievements & Recognition</h3>
            <div class="achievements-divider"></div>
          </div>
          
          <div class="achievements-grid" id="achievements-container">
            <!-- Achievements will be loaded here dynamically -->
            <div class="achievements-loading">
              <i class="fas fa-spinner fa-spin"></i>
              <span>Loading achievements...</span>
            </div>
          </div>
          
          <div class="no-achievements" id="no-achievements" style="display: none;">
            <div class="no-achievements-icon">
              <i class="fas fa-award"></i>
            </div>
            <h4>No Achievements Yet</h4>
            <p>This artist hasn't added any achievements or recognition to their profile yet.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Artist Products/Artworks Section -->
    <section class="products-section" id="products">
      <div class="products-header">
        <h2>Featured Artworks</h2>
        <p style="color: var(--text-light); font-size: 1.2rem">
          Discover this artist's most celebrated masterpieces
        </p>
      </div>

      <div class="mansoryLayoutProductCard">
        <div class="galleryContainer" id="artist-artworks-container">
          <!-- Artworks will be loaded dynamically via JavaScript -->
          <div class="loading-placeholder">
            <p>Loading artworks...</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Reviews Section -->
    <section class="reviews-section">
      <div class="reviews-container">
        <div class="reviews-header">
          <h2 id="reviews-section-title">Artist Reviews</h2>
          <p id="reviews-section-subtitle">See what collectors and art enthusiasts are saying about this artist's work</p>
        </div>

        <div class="reviews-stats">
          <div class="rating-overview">
            <div class="overall-rating">
              <span class="rating-number" id="artist-rating">0.0</span>
              <div class="stars" id="artist-rating-stars">
                <i class="far fa-star"></i>
                <i class="far fa-star"></i>
                <i class="far fa-star"></i>
                <i class="far fa-star"></i>
                <i class="far fa-star"></i>
              </div>
              <p>Based on <span id="artist-total-reviews">0</span> reviews</p>
            </div>
          </div>
        </div>

        <div class="review-form-section">
          <div class="review-form-header">
            <div class="header-icon">
              <i class="fas fa-star"></i>
            </div>
            <div class="header-text">
              <h3>Share Your Experience</h3>
              <p>Have you purchased artwork from this artist? Leave a review to help other collectors.</p>
            </div>
          </div>
          
          <div class="review-form-container">
            <form class="review-form">
              <div class="form-section">
                <div class="form-group artwork-select-group">
                  <div class="form-label-container">
                    <label for="purchasedArtwork">
                      <i class="fas fa-palette"></i>
                      Artwork Purchased
                    </label>
                  </div>
                  <div class="select-wrapper">
                    <select id="purchasedArtwork" required>
                      <option value="">Loading your purchased artworks...</option>
                    </select>
                    <i class="fas fa-chevron-down select-arrow"></i>
                  </div>
                </div>
                
                <div class="form-group rating-group">
                  <div class="form-label-container">
                    <label>
                      <i class="fas fa-star"></i>
                      Your Rating
                    </label>
                  </div>
                  <div class="star-rating-container">
                    <div class="star-rating" data-rating="0">
                      <span class="star" data-value="1"><i class="far fa-star"></i></span>
                      <span class="star" data-value="2"><i class="far fa-star"></i></span>
                      <span class="star" data-value="3"><i class="far fa-star"></i></span>
                      <span class="star" data-value="4"><i class="far fa-star"></i></span>
                      <span class="star" data-value="5"><i class="far fa-star"></i></span>
                    </div>
                    <span class="rating-text">Click to rate</span>
                  </div>
                </div>
              </div>
              
              <div class="form-group review-text-group">
                <div class="form-label-container">
                  <label for="quickReview">
                    <i class="fas fa-comment-alt"></i>
                    Your Review
                  </label>
                </div>
                <div class="textarea-wrapper">
                  <textarea id="quickReview" rows="4" 
                    placeholder="Share your thoughts about the artwork quality, delivery experience, and overall satisfaction..." 
                    maxlength="500" required></textarea>
                  <div class="textarea-footer">
                    <small id="characterCounter">0/500</small>
                  </div>
                </div>
              </div>
              
              <div class="form-actions">
                <button type="button" id="submitQuickReview" class="submit-review-btn">
                  <span class="btn-content">
                    <i class="fas fa-paper-plane"></i>
                    <span>Submit Review</span>
                  </span>
                  <div class="btn-background"></div>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <?php include './components/includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Make artist ID and profile ownership status available to JavaScript
        window.ARTIST_ID = <?php echo json_encode($artist_id); ?>;
        window.IS_OWN_PROFILE = <?php echo json_encode($is_own_profile); ?>;
        window.IS_USER_LOGGED_IN = <?php echo json_encode($current_user_id !== null); ?>;
    </script>
    <script src="./public/cart.js"></script>
    <script src="./components/Navbar/navbar.js"></script>
    <script src="./components/BurgerMenu/burger-menu.js"></script>
    <script src="./public/artist-profile.js"></script>
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
