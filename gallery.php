<!DOCTYPE html>
<html lang="en">
<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gallery Hub - Book Your Perfect Gallery Experience</title>
        <meta name="description" content="Discover and book your perfect art gallery experience. Explore local and virtual galleries, meet professional artists, and immerse yourself in art culture with Yadawity.">
        <meta name="keywords" content="art gallery, local galleries, virtual galleries, art tours, art events, professional artists, book gallery, immersive art, VR art, exhibitions, Yadawity">
        <meta name="author" content="Yadawity">
        <meta property="og:title" content="Gallery Hub - Book Your Perfect Gallery Experience">
        <meta property="og:description" content="Discover and book your perfect art gallery experience. Explore local and virtual galleries, meet professional artists, and immerse yourself in art culture with Yadawity.">
        <meta property="og:image" content="/image/darker_image_25_percent.jpeg">
        <meta property="og:type" content="website">
        <meta property="og:url" content="http://localhost/gallery.php">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Gallery Hub - Book Your Perfect Gallery Experience">
        <meta name="twitter:description" content="Discover and book your perfect art gallery experience. Explore local and virtual galleries, meet professional artists, and immerse yourself in art culture with Yadawity.">
        <meta name="twitter:image" content="/image/darker_image_25_percent.jpeg">
        <link rel="canonical" href="http://localhost/gallery.php" />
        <link
            href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
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
        <link rel="stylesheet" href="./public/gallery.css">
        <link rel="icon" type="image/x-icon" href="./image/YAD-05.ico">
</head>
<body>
    <?php include './components/includes/navbar.php'; ?>

    <?php include './components/includes/burger-menu.php'; ?>
    <!-- Home Page -->

<!-- Hero Section -->
    <section class="supportHero">
        <div class="heroBackground">
            <img src="/image/darker_image_25_percent.jpeg" alt="Art Gallery" class="heroBackgroundImg">
            <div class="heroOverlay"></div>
        </div>
        <div class="heroContent">
            <div class="heroText">
              <div class="sectionHeaderContainer">
                <div class="artisanDecorativeDivider fade-in-up">
                  <div class="artisanOrnamentalIcon">
                    <i class="fa-solid fa-palette"></i>
                  </div>
                </div>
          
                <div class="sectionHeader">
                  <h1 class="fade-in-up delay-100">Our professional Artist</h1>
                </div>              
                 <div class="galleryOptions">
                    <!-- Local Gallery Card -->
                    <div class="optionCard" id="localOption">
                        <div class="optionIcon">
                            <i class="fas fa-palette"></i>
                        </div>
                        <h2>Local Galleries</h2>
                        <p>Experience art in person - visit physical galleries, meet artists, and immerse yourself in local art culture</p>
                        <div class="tags">
                            <span class="tag">In-Person Tours</span>
                            <span class="tag">Live Events</span>
                        </div>
                        <a href="localGallery.php" class="optionBtn">
                            Explore  <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>

                    <!-- Virtual Gallery Card -->
                    <div class="optionCard" id="virtualOption">
                        <div class="optionIcon">
                            <i class="fas fa-cube"></i>
                        </div>
                        <h2>Virtual Galleries</h2>
                        <p>Discover digital art experiences - immersive virtual tours, global exhibitions, and interactive installations</p>
                        <div class="tags">
                            <span class="tag">VR Tours</span>
                            <span class="tag">Global Access</span>
                        </div>
                        <a href="virtualGallery.php" class="optionBtn">
                            Explore  <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
      </section>

      <?php include './components/includes/footer.php'; ?>

    <!-- SweetAlert2 for cart notifications -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Cart functionality (must be loaded before other scripts that use it) -->
    <script src="./public/cart.js"></script>
    
    <!-- Main app functionality -->
    <script src="./app.js"></script>
    
    <!-- Navbar functionality (loaded after dependencies) -->
    <script src="./components/Navbar/navbar.js"></script>
    <script src="./components/BurgerMenu/burger-menu.js"></script>
    <script src="./public/gallery.js"></script>
    
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