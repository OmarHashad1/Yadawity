<!-- Burger Menu Component -->
<div class="burger-menu-overlay" id="burger-menu-overlay">
  <nav class="burger-menu-container" id="burger-menu-container" role="navigation" aria-label="Mobile Navigation Menu">
    <!-- Header -->
    <header class="burger-menu-header">
      <h1 class="burger-menu-logo">
        <a href="index.php" aria-label="Yadawity Home">Yadawity</a>
      </h1>
      <button class="burger-menu-close" id="burger-menu-close" aria-label="Close Menu">
        <i class="fas fa-times" aria-hidden="true"></i>
      </button>
    </header>

    <!-- Main Navigation Links - Exact same as navbar -->
    <section class="burger-nav-section">
      <h2 class="burger-section-title">Navigation</h2>
      <ul class="burger-nav-links" role="menubar">
        <li role="none">
          <a href="index.php" class="burger-nav-link" data-page="home" role="menuitem">
            <i class="fas fa-home burger-nav-icon" aria-hidden="true"></i>
            <span>HOME</span>
          </a>
        </li>
        <li role="none">
          <a href="gallery.php" class="burger-nav-link" data-page="gallery" role="menuitem">
            <i class="fas fa-images burger-nav-icon" aria-hidden="true"></i>
            <span>GALLERY</span>
          </a>
        </li>
        <li role="none">
          <a href="artwork.php" class="burger-nav-link" data-page="artworks" role="menuitem">
            <i class="fas fa-palette burger-nav-icon" aria-hidden="true"></i>
            <span>ARTWORKS</span>
          </a>
        </li>
        <li role="none">
          <a href="auction.php" class="burger-nav-link" data-page="auction" role="menuitem">
            <i class="fas fa-gavel burger-nav-icon" aria-hidden="true"></i>
            <span>AUCTION</span>
          </a>
        </li>
        <li role="none">
          <a href="artTherapy.php" class="burger-nav-link therapy-nav" data-page="therapy" role="menuitem">
            <i class="fas fa-heart burger-nav-icon" aria-hidden="true"></i>
            <span>THERAPY</span>
          </a>
        </li>
      </ul>
    </section>

    <!-- Actions Section - Exact same as navbar -->
    <section class="burger-actions-section">
      <h2 class="burger-section-title">Actions</h2>
      

      <!-- Icon Links - Same as navbar -->
      <div class="burger-icon-links">
        <a href="wishlist.php" class="burger-icon-link" title="Wishlist" id="burger-wishlist-link">
          <i class="fas fa-heart" aria-hidden="true"></i>
          <span class="burger-wishlist-count" id="burger-wishlist-count">7</span>
        </a>

        <a href="cart.php" class="burger-icon-link cart-link" title="Cart" id="burger-cart-link">
          <i class="fas fa-shopping-bag" aria-hidden="true"></i>
          <span class="burger-cart-count" id="burger-cart-count">3</span>
        </a>
      </div>

      <!-- User Dropdown - Exact same as navbar -->
      <div class="burger-user-dropdown">
        <button class="burger-user-account-btn" 
                id="burger-user-account-btn" 
                aria-expanded="false"
                aria-haspopup="true"
                aria-controls="burger-user-dropdown-menu"
                title="Account">
          <i class="fas fa-user" aria-hidden="true"></i>
        </button>
        <div class="burger-user-dropdown-menu" id="burger-user-dropdown-menu" role="menu" aria-hidden="true">
          <div class="burger-dropdown-header">
            <div class="burger-user-avatar">
              <i class="fas fa-user-circle" aria-hidden="true"></i>
            </div>
            <div class="burger-user-info">
              <span class="burger-user-name" id="burger-user-name">Guest User</span>
              <span class="burger-user-role" id="burger-user-role">Visitor</span>
            </div>
          </div>
          
          <div class="burger-dropdown-section">
            <a href="artistProfile.php" class="burger-dropdown-item" role="menuitem">
              <i class="fas fa-user-circle" aria-hidden="true"></i>
              <span>My Profile</span>
            </a>
            <a href="profile.php#orders" class="burger-dropdown-item" role="menuitem">
              <i class="fas fa-box" aria-hidden="true"></i>
              <span>My Orders</span>
            </a>
            <a href="support.php" class="burger-dropdown-item" role="menuitem">
              <i class="fas fa-headset" aria-hidden="true"></i>
              <span>Support</span>
            </a>
          </div>
          
          <!-- Artist Portal Section - Only visible for artists -->
          <div class="burger-dropdown-section burger-artist-section" id="burger-artist-section" style="display: none;">
            <div class="burger-dropdown-section-title">
              <i class="fas fa-palette" aria-hidden="true"></i>
              <span>Artist Portal</span>
            </div>
            <a href="artistPortal.php" class="burger-dropdown-item burger-artist-item" role="menuitem">
              <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
              <span>Artist Dashboard</span>
            </a>
            <a href="artistPortal.php#profile-section" class="burger-dropdown-item burger-artist-item" role="menuitem">
              <i class="fas fa-user-edit" aria-hidden="true"></i>
              <span>Manage Profile</span>
            </a>
            <a href="artistPortal.php#reviews-section" class="burger-dropdown-item burger-artist-item" role="menuitem">
              <i class="fas fa-star" aria-hidden="true"></i>
              <span>User Reviews</span>
            </a>
            <a href="artistPortal.php#orders-section" class="burger-dropdown-item burger-artist-item" role="menuitem">
              <i class="fas fa-box" aria-hidden="true"></i>
              <span>My Orders</span>
            </a>
          </div>
          
          <div class="burger-dropdown-divider" role="separator"></div>
          <a href="login.php" class="burger-dropdown-item burger-logout-item" id="burger-login-logout" role="menuitem">
            <i class="fas fa-sign-in-alt" aria-hidden="true"></i>
            <span>Login</span>
          </a>
        </div>
      </div>
    </section>
  </nav>
</div>

<div class="mobileSearchOverlay" id="mobileSearchOverlay">
  <div class="mobileSearchContainer">
    <input
      type="text"
      placeholder="Search artists, artworks..."
      class="mobileSearchInput"
      id="mobileSearchInput"
    />
    <button class="mobileSearchClose" id="mobileSearchClose">
      <i class="fas fa-times"></i>
    </button>
  </div>
  <div class="searchSuggestions" id="mobileSearchSuggestions"></div>
</div>
