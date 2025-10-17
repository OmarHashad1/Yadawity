<nav class="navbar navbar-yadawity" id="yadawity-navbar">
    <div class="nav-container">
            <div class="nav-logo">
                <a href="index.php" class="nav-logo-link">
                    <div class="logo-container">
                        <img src="./image/Logo.png" alt="Yadawity Gallery" class="logo-image">
                    </div>
                    <div class="logo-text">
                        <span class="logo-name">Yadawity</span>
                        <span class="logo-est">EST. 2025</span>
                    </div>
                </a>
            </div>        <div class="nav-menu" id="nav-menu">
            <a href="index.php" class="nav-link" data-page="home">
                <i class="fas fa-home nav-link-icon"></i>
                <span>HOME</span>
            </a>
            <a href="gallery.php" class="nav-link" data-page="gallery">
                <i class="fas fa-images nav-link-icon"></i>
                <span>GALLERY</span>
            </a>
            <a href="artwork.php" class="nav-link" data-page="artwork">
                <i class="fas fa-palette nav-link-icon"></i>
                <span>ARTWORKS</span>
            </a>
            <a href="auction.php" class="nav-link" data-page="auction">
                <i class="fas fa-gavel nav-link-icon"></i>
                <span>AUCTION</span>
            </a>
            <a href="artTherapy.php" class="nav-link therapy-nav" data-page="therapy">
                <i class="fas fa-heart nav-link-icon"></i>
                <span>THERAPY</span>
            </a>
        </div>

        <div class="nav-actions">

                <a href="wishlist.php" class="nav-icon-link" title="Wishlist" id="wishlist-link">
                    <i class="fas fa-heart"></i>
                    <span class="wishlist-count" id="wishlist-count">0</span>
                </a>

                <a href="cart.php" class="nav-icon-link cart-link" title="Cart" id="cart-link">
                    <i class="fas fa-shopping-bag"></i>
                    <span class="cart-count" id="cart-count">0</span>
                </a>            <div class="user-dropdown">
                <a href="#" class="nav-icon-link user-account-btn" title="Account" id="user-account">
                    <i class="fas fa-user"></i>
                </a>
                <div class="user-dropdown-menu" id="user-menu">
                    <div class="dropdown-header">
                        <?php
                        // Show user profile photo if logged in
                        require_once __DIR__ . '/../../API/db.php';
                        require_once __DIR__ . '/../../API/checkCredentials.php';
                        
                        $profilePhoto = '';
                        $showProfilePhoto = false;
                        $debugLog = [];
                        
                        // Use the same authentication method as profile page
                        $credentials = checkUserCredentials();
                        $debugLog[] = "Auth check: " . ($credentials['authenticated'] ? 'SUCCESS' : 'FAILED');
                        
                        if ($credentials['authenticated']) {
                            $uid = $credentials['user_id'];
                            $debugLog[] = "User ID: $uid";
                            
                            $stmt = $db->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
                            if ($stmt) {
                                $stmt->bind_param("i", $uid);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                if ($row = $result->fetch_assoc()) {
                                    $profilePhoto = $row['profile_picture'];
                                    $debugLog[] = "DB profile_picture: '$profilePhoto'";
                                    
                                    // Define document root for use in all conditions
                                    $documentRoot = $_SERVER['DOCUMENT_ROOT'];
                                    
                                    // Check if profile photo exists and is valid
                                    if (!empty($profilePhoto) && trim($profilePhoto) !== '') {
                                        $debugLog[] = "Profile photo not empty";
                                        
                                        // Use a more reliable path resolution
                                        $fullPath = $documentRoot . '/htdocs/uploads/user_profile_picture/' . $profilePhoto;
                                        $relativePath = './uploads/user_profile_picture/' . $profilePhoto;
                                        
                                        $debugLog[] = "Checking paths - Full: " . (file_exists($fullPath) ? 'EXISTS' : 'MISSING') . ", Relative: " . (file_exists($relativePath) ? 'EXISTS' : 'MISSING');
                                        
                                        if (file_exists($fullPath) && is_readable($fullPath)) {
                                            $showProfilePhoto = true;
                                            $debugLog[] = "SUCCESS: Using document root path";
                                        } elseif (file_exists($relativePath) && is_readable($relativePath)) {
                                            $showProfilePhoto = true;
                                            $debugLog[] = "SUCCESS: Using relative path";
                                        } else {
                                            $debugLog[] = "FALLBACK: Searching for user files";
                                            
                                            // File doesn't exist, try to find an alternative
                                            $patterns = [
                                                $documentRoot . "/htdocs/uploads/user_profile_picture/user_{$uid}_*",
                                                "./uploads/user_profile_picture/user_{$uid}_*"
                                            ];
                                            
                                            foreach ($patterns as $pattern) {
                                                $files = glob($pattern);
                                                if (!empty($files)) {
                                                    $profilePhoto = basename($files[0]);
                                                    $showProfilePhoto = true;
                                                    $debugLog[] = "FALLBACK SUCCESS: Found $profilePhoto";
                                                    
                                                    // Update database with the found file
                                                    $updateStmt = $db->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
                                                    if ($updateStmt) {
                                                        $updateStmt->bind_param("si", $profilePhoto, $uid);
                                                        $updateStmt->execute();
                                                        $updateStmt->close();
                                                        $debugLog[] = "Database updated with found file";
                                                    }
                                                    break;
                                                }
                                            }
                                        }
                                    } else {
                                        $debugLog[] = "Profile photo empty, searching for orphaned files";
                                        
                                        // No profile photo in database, check if file exists
                                        $patterns = [
                                            $documentRoot . "/htdocs/uploads/user_profile_picture/user_{$uid}_*",
                                            "./uploads/user_profile_picture/user_{$uid}_*"
                                        ];
                                        
                                        foreach ($patterns as $pattern) {
                                            $files = glob($pattern);
                                            if (!empty($files)) {
                                                $profilePhoto = basename($files[0]);
                                                $showProfilePhoto = true;
                                                $debugLog[] = "ORPHAN SUCCESS: Found $profilePhoto";
                                                
                                                // Update database with the found file
                                                $updateStmt = $db->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
                                                if ($updateStmt) {
                                                    $updateStmt->bind_param("si", $profilePhoto, $uid);
                                                    $updateStmt->execute();
                                                    $updateStmt->close();
                                                    $debugLog[] = "Database updated with orphaned file";
                                                }
                                                break;
                                            }
                                        }
                                    }
                                }
                                $stmt->close();
                            }
                        }
                        
                        // Output debug info to console
                        echo "<script>console.log('Navbar Debug for User " . ($credentials['authenticated'] ? $credentials['user_id'] : 'Not Auth') . ":', " . json_encode($debugLog) . ");</script>";
                        ?>
                        <div class="user-avatar">
                            <?php if ($showProfilePhoto): ?>
                                <?php 
                                $ts = time() . rand(1000, 9999); // Stronger cache busting
                                $cleanPhoto = trim($profilePhoto); // Ensure no whitespace
                                ?>
                                <img src="./uploads/user_profile_picture/<?php echo htmlspecialchars($cleanPhoto); ?>?v=<?php echo $ts; ?>" 
                                     alt="Profile Photo" 
                                     style="width:40px;height:40px;border-radius:50%;object-fit:cover;" 
                                     onerror="console.error('Navbar image failed:', '<?php echo htmlspecialchars($cleanPhoto); ?>'); this.style.display='none'; this.nextElementSibling.style.display='inline';"
                                     onload="console.log('Navbar image loaded:', '<?php echo htmlspecialchars($cleanPhoto); ?>');">
                                <i class="fas fa-user-circle" style="display:none;"></i>
                            <?php else: ?>
                                <i class="fas fa-user-circle"></i>
                            <?php endif; ?>
                        </div>
                        <div class="user-info">
                            <span class="user-name" id="user-name">Guest User</span>
                            <span class="user-role" id="user-role">Visitor</span>
                        </div>
                    </div>
                    
                        <div class="dropdown-section">
                            <a href="artistProfile.php" class="dropdown-item">
                                <i class="fas fa-user-circle"></i>
                                <span>My Profile</span>
                            </a>
                            <a href="profile.php#orders" class="dropdown-item">
                                <i class="fas fa-box"></i>
                                <span>My Orders</span>
                            </a>
                            <a href="support.php" class="dropdown-item">
                                <i class="fas fa-headset"></i>
                                <span>Support</span>
                            </a>
                        </div>                    <!-- Artist Portal Section - Only visible for artists -->
                    <div class="dropdown-section artist-section" id="artist-section" style="display: none;">
                        <div class="dropdown-section-title">
                            <i class="fas fa-palette"></i>
                            <span>Artist Portal</span>
                        </div>
                        <a href="artistPortal.php" class="dropdown-item artist-item">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Artist Dashboard</span>
                        </a>
                        <a href="artistPortal.php#profile-section" class="dropdown-item artist-item">
                            <i class="fas fa-user-edit"></i>
                            <span>Manage Profile</span>
                        </a>
                        <a href="artistPortal.php#reviews-section" class="dropdown-item artist-item">
                            <i class="fas fa-star"></i>
                            <span>User Reviews</span>
                        </a>
                        <a href="artistPortal.php#orders-section" class="dropdown-item artist-item">
                            <i class="fas fa-box"></i>
                            <span>My Orders</span>
                        </a>
                    </div>
                    
                    <div class="dropdown-divider"></div>
                    <a href="login.php" class="dropdown-item logout-item" id="login-logout">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Login</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="nav-toggle" id="nav-toggle">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </div>
    </div>
 </nav>
