<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Yadawity Gallery: Discover classical art, healing, and heritage. Explore master artists, curated artworks, and testimonials from art lovers and collectors.">
c
    <title>Yadawity Gallery</title>

    <link
      href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
      rel="stylesheet"
    />
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
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
      integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    />
    <link rel="stylesheet" href="./components/Navbar/navbar.css" />
    <link rel="stylesheet" href="./components/BurgerMenu/burger-menu.css" />
    <link rel="stylesheet" href="./public/homePage.css" />

  </head>
  <body>
    <?php include './components/includes/navbar.php'; ?>

    <?php include './components/includes/burger-menu.php'; ?>

    <section class="hero">
      <h1>
        <span>WHERE CLASSICAL ART</span>
        <span>MEETS</span>
        <span class="highlight1">HEALING</span>
        <span class="highlight2">& HERITAGE</span>
      </h1>
      <p>
        A distinguished establishment preserving the finest traditions of art,
        fostering therapeutic healing, and connecting connoisseurs with
        masterful creations.
      </p>
    </section>

    <div class="sectionHeaderContainer">
      <div class="artisanDecorativeDivider fade-in-up">
        <div class="artisanOrnamentalIcon">
          <i class="fa-solid fa-palette"></i>
        </div>
      </div>

      <div class="sectionHeader">
        <h2 class="fade-in-up delay-100">Our professional Artist</h2>
        <p class="fade-in-up delay-200">
          Discover exceptional talent from our curated fellowship of master
          artists and distinguished craftspeople
        </p>
      </div>
      <div class="artistCardSection" id="artistCardSection">
        <!-- Artist cards will be loaded dynamically -->
        <div class="loading-spinner" id="artistsLoading">
          <i class="fas fa-spinner fa-spin"></i>
          <p>Loading artists...</p>
        </div>
      </div>
    </div>

    <div class="sectionHeaderContainer">
      <div class="artisanDecorativeDivider fade-in-up">
        <div class="artisanOrnamentalIcon">
          <i class="fa-solid fa-brush"></i>
        </div>
      </div>
      <div class="sectionHeader">
        <h2 class="fade-in-up delay-100">featured Art</h2>
        <p class="fade-in-up delay-200">
          Explore curated works by master artists and renowned craftspeople.
        </p>
      </div>
      <!-- Artwork Section -->
      <section class="galleryContainer" id="artworkCardSection">
        <!-- Loading spinner for artworks -->
        <div id="artworksLoading" class="loading-spinner">
          <i class="fas fa-spinner fa-spin"></i>
          <p>Loading featured artworks...</p>
        </div>
        <!-- Dynamic artwork cards will be loaded here -->
      </section>
      <button class="discoverArtistsBtn fade-in-up delay-500" onclick="window.location.href='artwork.php'">
        Discover Artworks
      </button>
    </div>

    <div class="sectionHeaderContainer">
      <div class="artisanDecorativeDivider fade-in-up">
        <div class="artisanOrnamentalIcon">
          <i class="fa-solid fa-award"></i>
        </div>
      </div>
      <div class="sectionHeader">
        <h2 class="fade-in-up delay-100">Users testimonials</h2>
        <p class="fade-in-up delay-200">
          Testimonials from those touched by our artists' and makers'
          excellence.
        </p>
      </div>
      <div class="testimonialsCardSection zoom-in delay-300">
        <div class="testimonialsCarousel">
          <div class="testimonialsCarouselContainer">
            <div class="testimonialsCarouselTrack" id="carouselTrack">
              <!-- testimonials cards will be generated here -->
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php include './components/includes/footer.php'; ?>

    <!-- SweetAlert2 for cart notifications -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Cart functionality (must be loaded before other scripts that use it) -->
    <script src="./public/cart.js"></script>
    
    <!-- Wishlist functionality -->
    <script src="./public/wishlist-manager.js"></script>
    
    <!-- Main app functionality including testimonials carousel -->
    <script src="./app.js"></script>
    
    <script src="./components/Navbar/navbar.js"></script>
    <script src="./components/BurgerMenu/burger-menu.js"></script>
    <script src="./public/homePage.js"></script>
    <!--Start of Tawk.to Script-->
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
<!--End of Tawk.to Script-->
  </body>
</html>
