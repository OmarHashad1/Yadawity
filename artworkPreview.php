<?php
// Get artwork ID from URL parameter
$artwork_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no ID provided, redirect to gallery
if ($artwork_id <= 0) {
    header('Location: gallery.php');
    exit();
}

// Fetch artwork data directly from database
require_once './API/db.php';
$artwork_data = null;
$error_message = null;

try {
    // Get artwork information
    $query = "
        SELECT 
            a.artwork_id,
            a.artist_id,
            a.title,
            a.description,
            a.price,
            a.dimensions,
            a.year,
            a.category,
            a.style,
            a.artwork_image,
            a.type,
            a.is_available,
            a.on_auction,
            a.created_at,
            u.first_name as artist_first_name,
            u.last_name as artist_last_name,
            u.profile_picture as artist_profile_picture,
            u.art_specialty,
            u.years_of_experience,
            u.location as artist_location,
            u.bio as artist_bio,
            u.email as artist_email,
            u.phone as artist_phone
        FROM artworks a
        LEFT JOIN users u ON a.artist_id = u.user_id
        WHERE a.artwork_id = ? AND u.is_active = 1
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $artwork_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if (!$row) {
        header('Location: error404.php');
        exit();
    }
    
    // Format artwork data
    $artwork_data = [
        'artwork_id' => (int)$row['artwork_id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'price' => (float)$row['price'],
        'dimensions' => $row['dimensions'],
        'year' => $row['year'] ? (int)$row['year'] : null,
        'category' => $row['category'],
        'style' => $row['style'],
        'artwork_image' => $row['artwork_image'],
        'type' => $row['type'],
        'is_available' => (bool)$row['is_available'],
        'on_auction' => (bool)$row['on_auction'],
        'created_at' => $row['created_at'],
        'artist' => [
            'artist_id' => (int)$row['artist_id'],
            'first_name' => $row['artist_first_name'],
            'last_name' => $row['artist_last_name'],
            'full_name' => $row['artist_first_name'] . ' ' . $row['artist_last_name'],
            'profile_picture' => $row['artist_profile_picture'],
            'art_specialty' => $row['art_specialty'],
            'years_of_experience' => $row['years_of_experience'] ? (int)$row['years_of_experience'] : null,
            'location' => $row['artist_location'],
            'bio' => $row['artist_bio'],
            'email' => $row['artist_email'],
            'phone' => $row['artist_phone']
        ]
    ];
    
    // Add image URLs
    if ($artwork_data['artwork_image']) {
        $artwork_data['artwork_image_url'] = './uploads/artworks/' . $artwork_data['artwork_image'];
        $artwork_data['image_src'] = './uploads/artworks/' . $artwork_data['artwork_image'];
    } else {
        $artwork_data['artwork_image_url'] = './image/placeholder-artwork.jpg';
        $artwork_data['image_src'] = './image/placeholder-artwork.jpg';
    }
    
    // Get artwork photos from artwork_photos table
    $photos_query = "
        SELECT 
            photo_id,
            artwork_id,
            image_path,
            is_primary,
            created_at
        FROM artwork_photos 
        WHERE artwork_id = ? 
        ORDER BY is_primary DESC, photo_id ASC
    ";
    
    $photos_stmt = $db->prepare($photos_query);
    $photos_stmt->bind_param("i", $artwork_id);
    $photos_stmt->execute();
    $photos_result = $photos_stmt->get_result();
    
    $photos = [];
    while ($photo_row = $photos_result->fetch_assoc()) {
        $photos[] = [
            'photo_id' => (int)$photo_row['photo_id'],
            'artwork_id' => (int)$photo_row['artwork_id'],
            'image_path' => $photo_row['image_path'],
            'photo_url' => './uploads/artworks/' . $photo_row['image_path'],
            'is_primary' => (bool)$photo_row['is_primary'],
            'created_at' => $photo_row['created_at'] ?? null
        ];
    }
    
    $artwork_data['photos'] = $photos;
    
} catch (Exception $e) {
    error_log("Error loading artwork: " . $e->getMessage());
    header('Location: error404.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($artwork_data['title'] ?? 'Artwork'); ?> - Yadawity Gallery</title>
    <link rel="icon" type="image/x-icon" href="./image/YAD-05.ico">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer">
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
    <!-- Component Styles -->
    <link rel="stylesheet" href="./components/BurgerMenu/burger-menu.css">
    <link rel="stylesheet" href="./public/homePage.css">
    <link rel="stylesheet" href="./public/artworkPreview.css">
</head>
<body>
    <!-- Navigation -->
    <?php include './components/includes/navbar.php'; ?>

    <?php include './components/includes/burger-menu.php'; ?>
        
      </div>
    </div>

    <!-- Main Product Section -->
    <main class="productMain">
        <div class="productContainer">
            <!-- Product Gallery -->
            <div class="productGallery">
                <?php if (!empty($artwork_data['photos']) && count($artwork_data['photos']) > 1): ?>
                    <div class="photoCounter">
                        <i class="fas fa-images"></i>
                        <span><?php echo count($artwork_data['photos']); ?> Photos</span>
                    </div>
                <?php endif; ?>
                
                <div class="thumbnailList">
                    <?php if (!empty($artwork_data['photos'])): ?>
                        <?php foreach ($artwork_data['photos'] as $index => $photo): ?>
                            <img src="<?php echo htmlspecialchars($photo['photo_url']); ?>" 
                                 alt="Thumbnail <?php echo $index + 1; ?>" 
                                 class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" 
                                 data-main="<?php echo htmlspecialchars($photo['photo_url']); ?>"
                                 loading="lazy">
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Fallback to main artwork image if no photos -->
                        <img src="<?php echo htmlspecialchars($artwork_data['image_src']); ?>" 
                             alt="Thumbnail 1" 
                             class="thumbnail active" 
                             data-main="<?php echo htmlspecialchars($artwork_data['image_src']); ?>">
                    <?php endif; ?>
                </div>
                <div class="mainImageContainer">
                    <?php 
                    $main_image = !empty($artwork_data['photos']) 
                        ? $artwork_data['photos'][0]['photo_url'] 
                        : $artwork_data['image_src'];
                    ?>
                    <img src="<?php echo htmlspecialchars($main_image); ?>" 
                         alt="<?php echo htmlspecialchars($artwork_data['title']); ?>" 
                         class="mainImage" id="mainImage">
                    
                    <!-- Navigation arrows for multiple photos -->
                    <?php if (!empty($artwork_data['photos']) && count($artwork_data['photos']) > 1): ?>
                        <button class="photoNavBtn photoNavPrev" id="photoNavPrev">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="photoNavBtn photoNavNext" id="photoNavNext">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    <?php endif; ?>
                    
                    <button class="zoomBtn" id="zoomBtn">
                        <i class="fas fa-search-plus"></i>
                    </button>
                    
                    <!-- Current photo indicator -->
                    <?php if (!empty($artwork_data['photos']) && count($artwork_data['photos']) > 1): ?>
                        <div class="photoIndicator">
                            <span id="currentPhotoNumber">1</span> / <?php echo count($artwork_data['photos']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Product Information -->
            <div class="productInfo">
                <div class="productHeader">
                    <h1 class="productTitle"><?php echo htmlspecialchars($artwork_data['title']); ?></h1>
                    <div class="productMeta">
                        <span class="productType"><?php echo htmlspecialchars($artwork_data['type'] ?? $artwork_data['category']); ?></span>
                        <?php if ($artwork_data['dimensions']): ?>
                            <span class="productDimensions"><?php echo htmlspecialchars($artwork_data['dimensions']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="artistInfo">
                    <span class="byText">by</span>
                    <a href="artistProfile.php?id=<?php echo $artwork_data['artist']['artist_id']; ?>" class="artistLink" id="artistLink">
                        <span class="artistName"><?php echo htmlspecialchars($artwork_data['artist']['full_name']); ?></span>
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>

                <div class="artistDetailsSection">
                    <div class="artistDetails">
                        <div class="artistMeta">
                            <?php if ($artwork_data['artist']['art_specialty']): ?>
                                <span class="artistSpecialty"><?php echo htmlspecialchars($artwork_data['artist']['art_specialty']); ?></span>
                            <?php endif; ?>
                            <?php if ($artwork_data['artist']['location']): ?>
                                <span class="artistLocation">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($artwork_data['artist']['location']); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($artwork_data['artist']['years_of_experience']): ?>
                                <span class="artistExperience">
                                    <i class="fas fa-medal"></i>
                                    <?php echo $artwork_data['artist']['years_of_experience']; ?> years experience
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if ($artwork_data['artist']['bio']): ?>
                            <p class="artistBio"><?php echo htmlspecialchars(substr($artwork_data['artist']['bio'], 0, 150)); ?><?php echo strlen($artwork_data['artist']['bio']) > 150 ? '...' : ''; ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="priceSection">
                    <?php if ($artwork_data['on_auction']): ?>
                        <div class="priceContainer auction">
                            <div class="priceLabel">
                                <i class="fas fa-gavel"></i>
                                <span>Starting Bid</span>
                            </div>
                            <div class="priceAmount">
                                <span class="currency">EGP</span>
                                <span class="amount"><?php echo number_format($artwork_data['price'], 0); ?></span>
                            </div>
                            <div class="auctionBadge">
                                <i class="fas fa-fire"></i>
                                <span>Live Auction</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="priceContainer">
                            <?php if (isset($artwork_data['sale_price']) && $artwork_data['sale_price'] > 0): ?>
                                <div class="originalPriceRow">
                                    <span class="originalPrice">
                                        <span class="currency">EGP</span>
                                        <span class="amount"><?php echo number_format($artwork_data['sale_price'], 0); ?></span>
                                    </span>
                                    <div class="discountBadge">
                                        <span><?php echo round((($artwork_data['sale_price'] - $artwork_data['price']) / $artwork_data['sale_price']) * 100); ?>% OFF</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="currentPriceRow">
                                <div class="priceAmount">
                                    <span class="currency">EGP</span>
                                    <span class="amount"><?php echo number_format($artwork_data['price'], 0); ?></span>
                                </div>
                                <?php if (isset($artwork_data['sale_price']) && $artwork_data['sale_price'] > 0): ?>
                                    <div class="savingsText">
                                        You save EGP <?php echo number_format($artwork_data['sale_price'] - $artwork_data['price'], 0); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="priceFeatures">
                                <div class="feature">
                                    <i class="fas fa-shield-alt"></i>
                                    <span>Authenticity Guaranteed</span>
                                </div>
                                <div class="feature">
                                    <i class="fas fa-credit-card"></i>
                                    <span>Secure Payment</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="productDescription">
                    <h3>Description</h3>
                    <p><?php echo $artwork_data['description'] ? htmlspecialchars($artwork_data['description']) : 'No description available for this artwork.'; ?></p>
                    
                    <div class="artworkDetails">
                        <?php if ($artwork_data['type']): ?>
                        <div class="detailItem">
                            <span class="detailLabel">Type:</span>
                            <span class="detailValue"><?php echo htmlspecialchars($artwork_data['type']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($artwork_data['dimensions']): ?>
                        <div class="detailItem">
                            <span class="detailLabel">Dimensions:</span>
                            <span class="detailValue"><?php echo htmlspecialchars($artwork_data['dimensions']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($artwork_data['year']): ?>
                        <div class="detailItem">
                            <span class="detailLabel">Year:</span>
                            <span class="detailValue"><?php echo htmlspecialchars($artwork_data['year']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($artwork_data['style']): ?>
                        <div class="detailItem">
                            <span class="detailLabel">Style:</span>
                            <span class="detailValue"><?php echo htmlspecialchars($artwork_data['style']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($artwork_data['category']): ?>
                        <div class="detailItem">
                            <span class="detailLabel">Category:</span>
                            <span class="detailValue"><?php echo htmlspecialchars($artwork_data['category']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="deliveryInfo">
                    <div class="deliveryOption">
                        <i class="fas fa-truck"></i>
                        <div class="deliveryText">
                            <strong>Delivery</strong>
                            <span>Available - Check options at checkout</span>
                        </div>
                    </div>
                </div>

                <div class="purchaseActions">
                    <?php if ($artwork_data['on_auction']): ?>
                        <button class="addToCartBtn" id="placeBidBtn" onclick="window.location.href='auction.php?id=<?php echo $artwork_data['artwork_id']; ?>'">
                            <i class="fas fa-gavel"></i>
                            Place Bid
                        </button>
                    <?php elseif ($artwork_data['is_available']): ?>
                        <button class="addToCartBtn" id="addToCartBtn" data-artwork-id="<?php echo $artwork_data['artwork_id']; ?>">
                            <i class="fas fa-shopping-bag"></i>
                            Add to Cart
                        </button>
                    <?php else: ?>
                        <button class="addToCartBtn sold" disabled>
                            <i class="fas fa-times"></i>
                            Sold Out
                        </button>
                    <?php endif; ?>
                    
                    <button class="wishlistBtn" id="wishlistBtn" data-artwork-id="<?php echo $artwork_data['artwork_id']; ?>">
                        <i class="far fa-heart"></i>
                    </button>
                    
                    <button class="viewPortfolioBtn" onclick="window.location.href='artistProfile.php?id=<?php echo $artwork_data['artist']['artist_id']; ?>'">
                        <i class="fas fa-palette"></i>
                        View Portfolio
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Similar Products Section -->
    <section class="similarProducts">
        <div class="sectionContainer">
            <div class="sectionHeader">
                <h2>Similar Products</h2>
                <p>Discover more artworks that complement your style</p>
            </div>
            
            <div class="productsGrid" id="similarProductsGrid">
                <!-- Similar products will be loaded here via JavaScript -->
                <div class="loading-placeholder">
                    <p>Loading similar artworks...</p>
                </div>
            </div>

            <div class="viewAllContainer">
                <a href="gallery.php" class="viewAllBtn">
                    View All Artworks
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include './components/includes/footer.php'; ?>

    <!-- Cart functionality -->
    <?php include './components/includes/cart-scripts.php'; ?>

    <!-- Scripts -->
    <script>
        // Make artwork data available to JavaScript
        window.artworkData = <?php echo json_encode($artwork_data); ?>;
        window.artworkId = <?php echo $artwork_data['artwork_id']; ?>;
    </script>
    <script src="./components/Navbar/navbar.js"></script>
    <script src="./components/BurgerMenu/burger-menu.js"></script>
    <script src="./public/artworkPreview.js"></script>
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
