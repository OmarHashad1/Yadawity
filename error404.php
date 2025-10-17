<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg+xml" href="/vite.svg" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    
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
    <link rel="stylesheet" href="./public/error.css" />
    <link rel="icon" type="image/x-icon" href="./image/YAD-05.ico">
    <title>404 - Page Not Found</title>
    <style>
      /* Override error.css to fix footer positioning */
      body {
        display: block !important;
        align-items: unset !important;
        justify-content: unset !important;
        min-height: 100vh;
        padding: 0 !important;
      }
      
      .main-content {
        min-height: calc(100vh - 200px); /* Adjust based on navbar and footer height */
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
      }
    </style>
  </head>
  <body>
    <?php include './components/includes/navbar.php'; ?>

    <?php include './components/includes/burger-menu.php'; ?>

    <div class="main-content">
      <div class="container">
        <!-- Text Content -->
        <div class="content">
          <h1 class="errorCode">404</h1>
          <h2 class="errorTitle">Page Not Found!!!!</h2>
          <p class="errorDescription">
            Oops! The page you're looking for seems to have wandered off into the digital void. 
            Don't worry, it happens to the best of us.
          </p>
          
          <!-- Action Buttons -->
          <div class="buttonContainer">
            <a class="btn" href="index.php" >Go Home</a>
          </div>
        </div>

        <!-- 3D Rotating Image Container -->
        <div class="imageContainer">
          <div class="rotatingContainer">
            <div class="rotatingImage">
              <img 
                src="/image/STC_EDS_MINAG_R_L_2011_229-001.jpg" 
                alt="Rotating 3D Image"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php include './components/includes/footer.php'; ?>

    <script src="./components/Navbar/navbar.js"></script>
    <script src="./components/BurgerMenu/burger-menu.js"></script>
    <script src="./app.js"></script>
  </body>
</html>