<?php
// Check user authentication
require_once './API/checkCredentials.php';

// If user is not authenticated, redirect to login
if (!$isAuthenticated) {
    header('Location: login.php?redirect=cart.php&message=Please login to access your cart');
    exit;
}

// If user is not active, redirect to login
if (!$isUserActive) {
    header('Location: login.php?redirect=cart.php&message=Your account is not active');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Shopping Cart - Yadawity Gallery</title>

    <link rel="icon" type="image/x-icon" href="./image/YAD-05.ico">
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
    <link rel="stylesheet" href="./components/Navbar/navbar.css" />
    <link rel="stylesheet" href="./components/BurgerMenu/burger-menu.css" />
    <link rel="stylesheet" href="./public/homePage.css" />
    <link rel="stylesheet" href="./public/cart.css" />
    <link rel="stylesheet" href="./public/cart-page.css" />

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
          <i class="fas fa-shopping-bag"></i>
          <span>SHOPPING CART</span>
        </div>
        <h1 class="pageTitle">Your Cart</h1>
        <p class="pageDescription">
          Review your selected artworks and proceed to secure checkout.
          Each piece is carefully prepared for safe delivery to your home.
        </p>
      </div>
    </div>

    <!-- Cart Content -->
    <div class="cartContainer">
      <div class="cartLayout">
        <!-- Cart Items Section -->
        <div class="cartItems">
          <div class="cartHeader">
            <h2 id="cartItemsCount">Cart Items (0)</h2>
          </div>

          <!-- Loading state -->
          <div class="loadingState" id="loadingState">
            <div class="loadingSpinner">
              <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Loading your cart...</p>
          </div>

          <!-- Cart items will be populated dynamically -->
          <div id="cartItemsContainer">
            <!-- Dynamic cart items will be inserted here -->
          </div>
        </div>

        <!-- Order Summary Section -->
        <div class="orderSummary">
          <div class="summaryCard">
            <h3>Order Summary</h3>
            
            <div class="summaryLine">
              <span id="subtotalLabel">Subtotal (0 items):</span>
              <span id="subtotal">EGP 0</span>
            </div>
            
            <div class="summaryLine">
              <span>Shipping:</span>
              <span id="shipping">EGP 50</span>
            </div>
            
            <hr class="summaryDivider">
            
            <div class="summaryTotal">
              <span>Total:</span>
              <span id="total">EGP 0</span>
            </div>
            
            <button class="checkoutBtn" id="checkoutBtn">
              <i class="fas fa-lock"></i>
              Proceed to Checkout
            </button>
            
            <div class="securityNote">
              <i class="fas fa-shield-alt"></i>
              <span>Secure checkout with SSL encryption</span>
            </div>
          </div>

          <!-- Payment Methods -->
          <div class="paymentMethods">
            <h4>We Accept</h4>
            <div class="paymentOptions">
              <div class="paymentOption">
                <img src="./image/cash on delivery.png" alt="Cash on Delivery" class="paymentLogo">
                <span>Cash on Delivery</span>
              </div>
              <div class="paymentOption">
                <img src="./image/vodafone cash.jpg" alt="Vodafone Cash" class="paymentLogo">
                <span>Vodafone Cash</span>
              </div>
              <div class="paymentOption">
                <img src="./image/InstaPay.png" alt="InstaPay" class="paymentLogo">
                <span>InstaPay</span>
              </div>
              <div class="paymentOption">
                <img src="./image/meeza.png" alt="ميزا (Meeza)" class="paymentLogo">
                <span>ميزا (Meeza)</span>
              </div>
              <div class="paymentOption">
                <img src="./image/Visa .svg" alt="Visa" class="paymentLogo">
                <span>Visa</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty Cart State (initially hidden) -->
      <div class="emptyCart" style="display: none;">
        <div class="emptyIcon">
          <i class="fas fa-shopping-bag"></i>
        </div>
        <h3>Your cart is empty</h3>
        <p>Browse our collection to discover amazing artworks and add them to your cart.</p>
        <a href="artwork.php" class="browseBtn">
          <i class="fas fa-palette"></i>
          Browse Artworks
        </a>
      </div>

      <!-- Continue Shopping -->
      <div class="continueShoppingSection">
        <a href="artwork.php" class="continueShoppingBtn">
          <i class="fas fa-arrow-left"></i>
          Continue Shopping
        </a>
      </div>
    </div>

    <?php include './components/includes/footer.php'; ?>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script src="./components/BurgerMenu/burger-menu.js"></script>
    <script src="./components/Navbar/navbar.js"></script>
    <script src="./public/cart.js"></script>
    <script src="./public/wishlist-manager.js"></script>
    <script src="./public/cart-page.js"></script>
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
