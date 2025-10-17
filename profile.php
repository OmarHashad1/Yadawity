<?php
// Start output buffering to prevent header issues
ob_start();

// Check if user is authenticated as a buyer
require_once './API/checkCredentials.php';

$credentials = checkUserCredentials();

// Redirect if not authenticated
if (!$credentials['authenticated']) {
    ob_end_clean(); // Clean buffer before redirect
    header('Location: login.php');
    exit;
}

// Store user info for use in the page (treating all users the same)
$buyer_id = $credentials['user_id'];
$buyer_name = $credentials['user_name'];
$buyer_email = $credentials['user_email'];

// Clean the output buffer and continue with page rendering
ob_end_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="public/profile.css">
    <script src="public/profile.js" defer></script>
    <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
    rel="stylesheet"
    />
    <link rel="icon" type="image/x-icon" href="./image/YAD-05.ico">
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
    
    <style>
     
    </style>

</head>
<body>
    <!-- Navbar -->
    <?php include './components/includes/navbar.php'; ?>
    <!-- Burger Menu -->
    <?php include './components/includes/burger-menu.php'; ?>
    <div class="profile-container">
        <aside class="profile-sidebar">
            <div class="profile-card">
                <div class="profile-avatar">
                    <?php
                    // Set default user photo
                    $userPhoto = 'image/placeholder-artwork.jpg';
                    // User photo will be loaded via JavaScript/API
                    ?>
                    <img src="<?php echo $userPhoto; ?>" alt="User Avatar" />
                    <button class="avatar-upload-btn"><i class="fa fa-camera"></i></button>
                </div>
                <div class="profile-member-since">Member since <span id="memberSince">1970</span></div>
                <div class="profile-stats">
                    <div><span id="purchaseCount">0</span><br>PURCHASES</div>
                    <div><span id="wishlistCount">0</span><br>WISHLIST</div>
                    <div><span id="reviewCount">0</span><br>REVIEWS</div>
                </div>
            </div>
            <nav class="profile-nav">
                <ul>
                    <li class="active" data-section="personal"> <i class="fa fa-user"></i> Personal Information</li>
                    <li data-section="orders"> <i class="fa fa-shopping-bag"></i> Order History</li>
                    <li data-section="reviews"> <i class="fa fa-star"></i> Reviews</li>
                    <li data-section="security"> <i class="fa fa-shield"></i> Security</li>
                </ul>
            </nav>
        </aside>
        <main class="profile-main">
            <section id="personal" class="profile-section active">
                <h2>PERSONAL INFORMATION</h2>
                <form id="personalForm" class="modernForm">
                    <div class="formRow">
                        <div class="inputGroup">
                            <label for="firstName"><i class="fas fa-user"></i> First Name</label>
                            <input type="text" id="firstName" name="firstName" placeholder="Enter your first name">
                        </div>
                        <div class="inputGroup">
                            <label for="lastName"><i class="fas fa-user"></i> Last Name</label>
                            <input type="text" id="lastName" name="lastName" placeholder="Enter your last name">
                        </div>
                    </div>
                    <div class="formRow">
                        <div class="inputGroup">
                            <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <div style="flex: 1;">
                                    <input type="email" id="email" name="email" placeholder="Enter your email address" disabled>
                                </div>
                                <button type="button" class="btn btn-outline btn-sm" id="changeEmailBtn" style="white-space: nowrap;">
                                    <i class="fas fa-edit"></i> Change Email
                                </button>
                            </div>
                        </div>
                        <div class="inputGroup">
                            <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                            <input type="text" id="phone" name="phone" placeholder="Enter your phone number">
                        </div>
                    </div>
                    <div class="formRow">
                        <div class="inputGroup">
                            <label for="address"><i class="fas fa-map-marker-alt"></i> Address</label>
                            <input type="text" id="address" name="address" placeholder="Enter your address">
                        </div>
                    </div>
                    <div class="formActions" style="justify-content: flex-end;">
                        <button type="submit" id="saveChanges" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                    </div>
                                </form>
                                <!-- Edit Modal styled like workshops modal -->
                                <!-- Modal moved to end of body for overlay effect -->
                </form>
            </section>
            <section id="orders" class="profile-section">
                <h2>ORDER HISTORY</h2>
                <div id="orderList"></div>
            </section>
            <section id="reviews" class="profile-section">
                <h2>REVIEWS</h2>
                <div id="reviewList"></div>
            </section>
            <section id="security" class="profile-section">
                <h2>SECURITY SETTINGS</h2>
                
                <!-- Change Password Form -->
                <form class="modernForm" id="securityForm">
                    <div class="formSection">
                        <h3><i class="fas fa-key"></i> Change Password</h3>
                        <div class="formRow">
                            <div class="inputGroup">
                                <label for="currentPassword">Current Password</label>
                                <div class="passwordInput">
                                    <input type="password" id="currentPassword" placeholder="Enter current password">
                                    <button type="button" class="passwordToggle" onclick="togglePassword('currentPassword')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="formRow">
                            <div class="inputGroup half">
                                <label for="newPassword">New Password</label>
                                <div class="passwordInput">
                                    <input type="password" id="newPassword" placeholder="Enter new password">
                                    <button type="button" class="passwordToggle" onclick="togglePassword('newPassword')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="passwordStrength" id="passwordStrength">
                                    <div class="strengthBar">
                                        <div class="strengthFill"></div>
                                    </div>
                                    <span class="strengthText">Password strength</span>
                                </div>
                            </div>
                            <div class="inputGroup half">
                                <label for="confirmPassword">Confirm New Password</label>
                                <div class="passwordInput">
                                    <input type="password" id="confirmPassword" placeholder="Confirm new password">
                                    <button type="button" class="passwordToggle" onclick="togglePassword('confirmPassword')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="matchIndicator" id="matchIndicator"></div>
                            </div>
                        </div>
                    </div>

                    <div class="formActions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-shield-check"></i>
                            Change Password
                        </button>
                    </div>
                </form>

                <!-- Delete Account Section -->
                <div class="deleteAccountSection" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e5e5;">
                    <div class="deleteAccountWarning">
                        <div class="warningContent">
                            <h4><i class="fas fa-trash-alt"></i> Delete Account</h4>
                            <p>Permanently delete your account and all associated data. This action cannot be undone.</p>
                            <ul class="deleteWarningList">
                                <li>All your account data will be permanently removed</li>
                                <li>Your orders and reviews will be deleted</li>
                                <li>Your cart and wishlist will be cleared</li>
                                <li>This action is irreversible</li>
                            </ul>
                        </div>
                        <button type="button" class="btn btn-danger deleteAccountBtn" id="deleteAccountBtn">
                            <i class="fas fa-trash-alt"></i>
                            Delete My Account
                        </button>
                    </div>
                </div>
    <style>
    .delete-btn {
        background: linear-gradient(135deg, #d32f2f 0%, #a31515 100%) !important;
        color: #fff !important;
        border: none !important;
        box-shadow: 0 4px 12px rgba(211,47,47,0.12);
        transition: all 0.3s;
    }
    .delete-btn:hover {
        background: linear-gradient(135deg, #a31515 0%, #d32f2f 100%) !important;
        color: #fff !important;
        transform: translateY(-2px) scale(1.03);
    }
    </style>
    <!-- Delete Account Modal -->
    <div id="deleteAccountModal" class="profile-modal-overlay" style="display:none;">
        <div class="profile-modal">
            <button class="close-profile-modal" id="closeDeleteAccountModal" type="button" aria-label="Close">&times;</button>
            <form id="deleteAccountForm" style="gap:1.2rem;max-width:400px;width:100%;display:flex;flex-direction:column;align-items:center;">
                <div style="width:100%;text-align:center;">
                    <p style="font-size:1.1rem;color:#6b5a47;font-weight:600;">Are you sure you want to delete your account? This action cannot be undone.</p>
                </div>
                <button type="submit" class="save-btn" style="background:#d32f2f;max-width:200px;">Yes, Delete My Account</button>
            </form>
        </div>
    </div>
                </div>
            </section>
        </main>
    </div>
    <!-- Footer -->
    <?php include 'components/includes/footer.php'; ?>

    <!-- Change Email Modal -->
    <div class="modal-overlay" id="changeEmailModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Change Email Address</h2>
                <button class="modal-close" id="closeChangeEmailModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <!-- Step 1: Enter New Email -->
                <div id="emailStep1" class="verification-step active">
                    <div class="formGroup">
                        <label for="newEmail">New Email Address:</label>
                        <input type="email" id="newEmail" name="newEmail" class="form-control" placeholder="Enter new email address">
                        <div class="errorMessage" id="newEmailError"></div>
                    </div>
                    <div class="modalActions">
                        <button type="button" class="btn btn-secondary" id="cancelEmailChange">Cancel</button>
                        <button type="button" class="btn btn-primary" id="sendCodeBtn">Send Verification Code</button>
                    </div>
                </div>

                <!-- Step 2: Enter Verification Code -->
                <div id="emailStep2" class="verification-step">
                    <div class="verification-info">
                        <p><i class="fas fa-envelope"></i> We've sent a 6-digit verification code to:</p>
                        <p class="email-display" id="displayNewEmail"></p>
                        <p class="info-text">Please check your email and enter the code below. The code will expire in 15 minutes.</p>
                    </div>
                    <div class="formGroup">
                        <label for="verificationCode">Verification Code:</label>
                        <input type="text" id="verificationCode" name="verificationCode" class="form-control verification-input" placeholder="Enter 6-digit code" maxlength="6">
                        <div class="errorMessage" id="verificationCodeError"></div>
                    </div>
                    <div class="modalActions">
                        <button type="button" class="btn btn-outline" id="backToEmailStep">Back</button>
                        <button type="button" class="btn btn-secondary" id="resendCodeBtn">Resend Code</button>
                        <button type="button" class="btn btn-primary" id="verifyCodeBtn">Verify & Update</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Make user ID and type available to JavaScript
        window.USER_ID = <?php echo $buyer_id; ?>;
        window.USER_TYPE = '<?php echo $credentials['user_type']; ?>';
        window.BUYER_ID = <?php echo $buyer_id; ?>; // Keep for backward compatibility
        console.log('User ID set:', window.USER_ID);
        console.log('User type set:', window.USER_TYPE);
    </script>
    <script src="./components/BurgerMenu/burger-menu.js"></script>
    <script src="./components/Navbar/navbar.js"></script>
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
