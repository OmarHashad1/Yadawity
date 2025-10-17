<?php
// Check if user is already authenticated
if (isset($_COOKIE['user_login'])) {
    // Include database connection
    require_once './API/db.php';
    
    if ($db && !$db->connect_error) {
        $cookieValue = $_COOKIE['user_login'];
        $parts = explode('_', $cookieValue, 2);
        
        if (count($parts) === 2) {
            $userId = $parts[0];
            $cookieHash = $parts[1];
            
            // Get user info to verify cookie
            $stmt = $db->prepare("SELECT user_id, email, first_name, last_name, user_type FROM users WHERE user_id = ? AND is_active = 1");
            if ($stmt) {
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    // Get active session for this user to verify cookie
                    $session_stmt = $db->prepare("SELECT session_id, login_time FROM user_login_sessions WHERE user_id = ? AND is_active = 1 AND expires_at > NOW() ORDER BY login_time DESC LIMIT 1");
                    if ($session_stmt) {
                        $session_stmt->bind_param("i", $userId);
                        $session_stmt->execute();
                        $session_result = $session_stmt->get_result();
                        
                        if ($session_result->num_rows === 1) {
                            $session = $session_result->fetch_assoc();
                            $session_token = $session['session_id'];
                            $login_time = $session['login_time'];
                            
                            // Generate expected hash using same deterministic method
                            $cookie_data = $session_token . '|' . $user['email'] . '|' . $user['user_id'] . '|' . $login_time;
                            $expectedHash = hash_hmac('sha256', $cookie_data, $session_token . '_' . $user['email']);
                            
                            if ($cookieHash === $expectedHash) {
                                // User is authenticated, redirect to appropriate page
                                $redirect_url = ($user['user_type'] === 'admin') ? 'admin-dashboard.php' : 'index.php';
                                header('Location: ' . $redirect_url);
                                exit;
                            }
                        }
                        $session_stmt->close();
                    }
                }
                $stmt->close();
            }
        }
        $db->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Yadawity - Welcome Back</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="./components/Navbar/navbar.css" />
    <link rel="stylesheet" href="./components/BurgerMenu/burger-menu.css" />
    <link rel="stylesheet" href="./public/homePage.css" />
    <link rel="stylesheet" href="./public/login.css" />
    
</head>
<body>
    <?php include './components/includes/navbar.php'; ?>
    <?php include './components/includes/burger-menu.php'; ?>

    <div class="main-content">
        <div class="login-container">
          <div class="logo-section">
            <div class="logo">
              <svg width="40" height="40" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 50 Q15 30 25 25 Q35 20 45 35 Q40 45 35 50 Q40 55 45 65 Q35 80 25 75 Q15 70 20 50 Z" fill="currentColor" opacity="0.8"/>
                <path d="M80 50 Q85 30 75 25 Q65 20 55 35 Q60 45 65 50 Q60 55 55 65 Q65 80 75 75 Q85 70 80 50 Z" fill="currentColor" opacity="0.8"/>
                <line x1="50" y1="20" x2="50" y2="80" stroke="currentColor" stroke-width="3"/>
                <path d="M50 20 Q45 15 42 12 M50 20 Q55 15 58 12" stroke="currentColor" stroke-width="2" fill="none"/>
              </svg>
            </div>
            <div class="brand-info">
              <div class="brand-name">Yadawity</div>
              <div class="brand-tagline">EST. 2025</div>
            </div>
          </div>

          <p class="welcome-subtitle">
            Sign in to your account to continue exploring authentic artworks and handcrafted creations
          </p>

          <form id="loginForm">
            <div class="form-group">
              <label for="email">Email Address</label>
              <input type="text" id="email" name="email" placeholder="johndoe@example.com" />
            </div>

            <div class="form-group password-section">
              <label for="password">Password</label>
              <a href="#" class="forgot-password">Forgot password?</a>
              <div style="clear: both"></div>
              <div class="password-input-container">
                <input type="password" id="password" name="password" />
                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>

            <div class="form-group remember-me-section">
              <label class="remember-checkbox">
                <input type="checkbox" id="remember_me" name="remember_me" />
                <span class="checkmark"></span>
                Keep me logged in for 30 days
              </label>
            </div>

            <button type="submit" class="sign-in-btn" id="loginBtn">Login</button>
          </form>

          <div class="signup-link">
            Don't have an account? <a href="signup.php">Join Yadawity</a>
          </div>
        </div>
    </div>

    <?php include './components/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsencrypt/3.3.2/jsencrypt.min.js"></script>
    <script src="./app.js"></script>
    <script src="./components/BurgerMenu/burger-menu.js"></script>
    <script src="./components/Navbar/navbar.js"></script>
    <script src="./public/login.js"></script>
  
</body>
</html>