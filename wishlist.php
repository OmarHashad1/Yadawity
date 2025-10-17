<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/x-icon" href="./image/YAD-05.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Wishlist - Yadawity Gallery</title>
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
    <link rel="stylesheet" href="./public/wishlist.css" />

  </head>
  <body>
    <?php include './components/includes/navbar.php'; ?>

    <?php include './components/includes/burger-menu.php'; ?>
        
      </div>
    </div>

    <!-- Page Header -->
    <div class="pageHeader">
      <div class="pageHeaderContent">
        <div class="pageHeaderBadge">
          <i class="fas fa-heart"></i>
          <span>MY FAVORITES</span>
        </div>
        <h1 class="pageTitle">Wishlist</h1>
        <p class="pageDescription">
          Your curated collection of favorite artworks and pieces you'd love to own.
          Keep track of what catches your eye and never miss a piece you love.
        </p>
      </div>
    </div>

    <!-- Wishlist Content -->
    <div class="wishlistContainer">
      <!-- Wishlist Grid -->
      <div class="wishlistGrid" id="wishlistGrid">
        <!-- Loading spinner -->
        <div id="wishlistLoading" class="loading-spinner">
          <i class="fas fa-spinner fa-spin"></i>
          <p>Loading your wishlist...</p>
        </div>
        <!-- Dynamic wishlist items will be loaded here -->
      </div>

      <!-- Empty State (initially hidden) -->
      <div class="emptyWishlist" id="emptyWishlist" style="display: none;">
        <div class="emptyIcon">
          <i class="fas fa-shopping-bag"></i>
        </div>
        <h3>Your wishlist is empty</h3>
        <p>Start exploring our gallery to find pieces you love and add them to your wishlist.</p>
        <a href="artwork.php" class="browseBtn">
          <i class="fas fa-palette"></i>
          Browse Artworks
        </a>
      </div>
    </div>

    <?php include './components/includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="./public/cart.js"></script>
    <script src="./components/BurgerMenu/burger-menu.js"></script>
    <script src="./components/Navbar/navbar.js"></script>
    <script src="./public/wishlist.js"></script>
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
