<?php
require_once "API/db.php";

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize variables
$errors = [];
$step = $_GET['step'] ?? '1';

// Simple auth check function
function checkUserAuthentication($db) {
    try {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if session variables exist
        if (isset($_SESSION['user_id']) && isset($_SESSION['session_token'])) {
            $user_id = $_SESSION['user_id'];
            $session_token = $_SESSION['session_token'];
            
            // Verify session in database
            $stmt = $db->prepare("SELECT user_id, expires_at FROM user_login_sessions WHERE session_id = ? AND user_id = ? AND is_active = 1");
            
            if (!$stmt) {
                return false;
            }
            
            $stmt->bind_param("si", $session_token, $user_id);
            
            if (!$stmt->execute()) {
                return false;
            }
            
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $session = $result->fetch_assoc();
                
                // Check if session hasn't expired
                if (strtotime($session['expires_at']) > time()) {
                    $stmt->close();
                    return true;
                }
            }
            $stmt->close();
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("checkUserAuthentication function error: " . $e->getMessage());
        return false;
    }
}

// Check if user is already logged in - if so, redirect them away from signup
if (checkUserAuthentication($db)) {
    // User is already logged in, redirect to dashboard or home page
    header("Location: profile.php");
    exit();
}

// Handle form submissions via AJAX to API
if ($_POST && isset($_POST['action'])) {
    // This will be handled by JavaScript AJAX calls to the API
    // No server-side form processing needed here
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="./image/YAD-05.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Yadawity Gallery - Create Your Account</title>
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
      href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="./components/Navbar/navbar.css" />
    <link rel="stylesheet" href="./components/BurgerMenu/burger-menu.css" />
    <link rel="stylesheet" href="./public/homePage.css" />
    <link rel="stylesheet" href="./public/signup.css" />
    
   
</head>
<body>
    <?php include './components/includes/navbar.php'; ?>
    
    <?php include './components/includes/burger-menu.php'; ?>

    <div class="main-content">
        <div class="signup-container">
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

        <?php if ($step === '1'): ?>
            <p class="welcome-subtitle">Join our community of artists and art lovers. Create your account to start your artistic journey with us.</p>

            <div class="step-indicator">
                <div class="step active">1</div>
                <div class="step">2</div>
            </div>

            <div id="error-messages" class="error-messages" style="display: none;">
                <ul id="error-list"></ul>
            </div>

            <form id="signupForm">
                <input type="hidden" name="action" value="step1">

                <div class="user-type-selector">
                    <div class="user-type-option" onclick="selectUserType('buyer')">
                        <input type="radio" name="user_type" value="buyer" id="buyer">
                        <label for="buyer">
                            <i class="fas fa-shopping-bag user-type-icon"></i>
                            <h4>Art Buyer</h4>
                            <p>Discover and purchase unique artworks from talented artists around the world</p>
                        </label>
                    </div>
                    <div class="user-type-option" onclick="selectUserType('artist')">
                        <input type="radio" name="user_type" value="artist" id="artist">
                        <label for="artist">
                            <i class="fas fa-palette user-type-icon"></i>
                            <h4>Artist</h4>
                            <p>Showcase and sell your artworks to art enthusiasts globally</p>
                        </label>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" name="first_name" id="first_name" required autocomplete="given-name">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" name="last_name" id="last_name" required autocomplete="family-name">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" name="email" id="email" required autocomplete="email">
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" name="phone" id="phone" required autocomplete="tel">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <div class="password-input-container">
                            <input type="password" name="password" id="password" required autocomplete="new-password" placeholder="Enter your password">
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="strength-bar">
                                <div class="strength-fill"></div>
                            </div>
                            <span class="strength-text">Password strength</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <div class="password-input-container">
                            <input type="password" name="confirm_password" id="confirm_password" required autocomplete="new-password" placeholder="Confirm your password">
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="match-indicator" id="matchIndicator"></div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Continue</button>
            </form>

            <div class="signup-link">
                Already have an account? <a href="../login.php">Sign In</a>
            </div>

        <?php elseif ($step === 'profile_upload'): ?>
            <p class="welcome-subtitle">Almost done! Add a profile picture to help others recognize you in our community.</p>

            <div class="step-indicator">
                <div class="step completed">1</div>
                <div class="step active">2</div>
            </div>

            <div id="error-messages" class="error-messages" style="display: none;">
                <ul id="error-list"></ul>
            </div>

            <form id="profileUploadForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="profile_upload">

                <div class="form-group">
                    <label for="profile_picture">Profile Picture (Optional)</label>
                    <div class="file-input-container">
                        <input type="file" name="profile_picture" id="profile_picture" accept="image/*" onchange="previewFile(this, 'profile-preview')">
                        <label for="profile_picture" class="file-input-label">
                            <i class="fas fa-camera"></i>
                            Upload Profile Picture<br>
                            <small>Supported: JPG, PNG, GIF (Max 5MB)</small>
                        </label>
                    </div>
                    <div id="profile-preview" class="file-preview" style="display: none;"></div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary btn-back" onclick="goBack()">
                        <i class="fas fa-arrow-left"></i> Go Back
                    </button>
                    <button type="submit" class="btn btn-primary">Complete Registration</button>
                    <button type="button" class="btn btn-secondary" onclick="submitWithoutPhoto()">Skip for Now</button>
                </div>
            </form>

        <?php elseif ($step === 'artist_verification'): ?>
            <p class="welcome-subtitle">Complete your artist profile with verification documents to start showcasing your work.</p>

            <div class="step-indicator">
                <div class="step completed">1</div>
                <div class="step active">2</div>
            </div>

            <div id="error-messages" class="error-messages" style="display: none;">
                <ul id="error-list"></ul>
            </div>

            <form id="artistVerificationForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="artist_verification">

                <div class="form-group">
                    <label for="national_id">National ID Number *</label>
                    <input type="text" name="national_id" id="national_id" required minlength="10"
                           placeholder="Enter your national ID number (minimum 10 digits)">
                </div>

                <div class="form-group">
                    <label for="art_specialty">Art Specialty *</label>
                    <input type="text" name="art_specialty" id="art_specialty" required 
                           placeholder="e.g., Oil Painting, Digital Art, Sculpture">
                </div>

                <div class="form-group">
                    <label for="years_experience">Years of Experience *</label>
                    <input type="number" name="years_experience" id="years_experience" required min="0" max="100"
                           placeholder="0">
                </div>

                <div class="form-group">
                    <label for="bio">About You *</label>
                    <textarea name="bio" id="bio" required 
                              placeholder="Tell us about your artistic journey, style, and inspirations..." 
                              oninput="updateWordCount(this)"></textarea>
                    <div class="word-count" id="bio-word-count">0 words (minimum 10 words required)</div>
                </div>

                <div class="form-group">
                    <label for="id_front">National ID Front Photo *</label>
                    <div class="file-input-container">
                        <input type="file" name="id_front" id="id_front" required accept="image/jpeg,image/jpg,image/png" onchange="previewFile(this, 'id-front-preview')">
                        <label for="id_front" class="file-input-label">
                            <i class="fas fa-id-card"></i>
                            Upload ID Front Photo<br>
                            <small>Supported: JPG, PNG (Max 10MB)</small>
                        </label>
                    </div>
                    <div id="id-front-preview" class="file-preview" style="display: none;"></div>
                </div>

                <div class="form-group">
                    <label for="id_back">National ID Back Photo *</label>
                    <div class="file-input-container">
                        <input type="file" name="id_back" id="id_back" required accept="image/jpeg,image/jpg,image/png" onchange="previewFile(this, 'id-back-preview')">
                        <label for="id_back" class="file-input-label">
                            <i class="fas fa-id-card"></i>
                            Upload ID Back Photo<br>
                            <small>Supported: JPG, PNG (Max 10MB)</small>
                        </label>
                    </div>
                    <div id="id-back-preview" class="file-preview" style="display: none;"></div>
                </div>

                <div class="form-group">
                    <label for="profile_picture">Profile Picture (Optional)</label>
                    <div class="file-input-container">
                        <input type="file" name="profile_picture" id="profile_picture" accept="image/*" onchange="previewFile(this, 'profile-preview')">
                        <label for="profile_picture" class="file-input-label">
                            <i class="fas fa-camera"></i>
                            Upload Profile Picture<br>
                            <small>Supported: JPG, PNG, GIF (Max 5MB)</small>
                        </label>
                    </div>
                    <div id="profile-preview" class="file-preview" style="display: none;"></div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary btn-back" onclick="goBack()">
                        <i class="fas fa-arrow-left"></i> Go Back
                    </button>
                    <button type="submit" class="btn btn-primary">Submit for Verification</button>
                </div>
            </form>

        <?php elseif ($step === 'success'): ?>
            <div class="success-message">
                <i class="success-icon fas fa-check-circle"></i>
                <h2>Welcome to Yadawity Gallery!</h2>
                <p>Your account has been created successfully. You can now start exploring our collection of authentic artworks and connect with talented artists.</p>
                <a href="../login.php" class="btn btn-primary" style="text-decoration: none; display: inline-block;">Sign In to Your Account</a>
            </div>

        <?php elseif ($step === 'artist_pending'): ?>
            <div class="pending-message">
                <i class="pending-icon fas fa-clock"></i>
                <h2>Verification In Progress</h2>
                <p>Thank you for submitting your artist application! Your account is currently under review.</p>
                <p>Our team will verify your documents and activate your account within 2-3 business days. You'll receive an email notification once your account is approved.</p>
                <a href="login.php" class="btn btn-primary" style="text-decoration: none; display: inline-block;">LOGIN AS A USER</a>
            </div>
        <?php endif; ?>
    </div>
    </div> <!-- Close main-content -->
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../app.js"></script>
    <script src="components/BurgerMenu/burger-menu.js"></script>
    
    <script>
        function selectUserType(type) {
            // Remove selected class from all options
            document.querySelectorAll('.user-type-option').forEach(option => {
                option.classList.remove('selected');
            });

            // Add selected class to clicked option
            const selectedInput = document.querySelector(`input[value="${type}"]`);
            const selectedOption = selectedInput.closest('.user-type-option');
            
            selectedInput.checked = true;
            selectedOption.classList.add('selected');
        }

        function previewFile(input, previewId) {
            const preview = document.getElementById(previewId);
            const file = input.files[0];

            if (file) {
                // Basic file preview - server will validate file constraints
                preview.innerHTML = `<i class="fas fa-check"></i> Selected: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        }

        function updateWordCount(textarea) {
            const text = textarea.value.trim();
            const words = text.split(/\s+/).filter(word => word.length > 0);
            const wordCount = words.length;
            const countDisplay = document.getElementById('bio-word-count');
            
            if (countDisplay) {
                if (wordCount < 10) {
                    countDisplay.textContent = `${wordCount} words (minimum 10 words required)`;
                    countDisplay.style.color = '#d73502';
                } else {
                    countDisplay.textContent = `${wordCount} words`;
                    countDisplay.style.color = '#28a745';
                }
            }
        }

        function submitWithoutPhoto() {
            const form = document.querySelector('#profileUploadForm');
            const fileInput = document.querySelector('#profile_picture');
            if (fileInput) {
                fileInput.removeAttribute('required');
            }
            submitFormViaAPI(form);
        }

        function showErrors(errors) {
            const errorContainer = document.getElementById('error-messages');
            const errorList = document.getElementById('error-list');
            
            if (errors && errors.length > 0) {
                errorList.innerHTML = '';
                errors.forEach(error => {
                    const li = document.createElement('li');
                    li.textContent = error;
                    errorList.appendChild(li);
                });
                errorContainer.style.display = 'block';
            } else {
                errorContainer.style.display = 'none';
            }
        }

        function goBack() {
            // Show confirmation dialog before going back
            Swal.fire({
                title: 'Go Back?',
                text: 'Are you sure you want to go back? Any unsaved changes will be lost.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#8b6f47',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Go Back',
                cancelButtonText: 'Stay Here'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Clear step completion status when going back
                    clearAllDataAndRedirect();
                    
                    // Determine which step to go back to based on current step
                    const currentUrl = window.location.href;
                    
                    if (currentUrl.includes('step=profile_upload')) {
                        // Go back to step 1
                        window.location.href = 'signup.php?step=1';
                    } else if (currentUrl.includes('step=artist_verification')) {
                        // Go back to step 1  
                        window.location.href = 'signup.php?step=1';
                    } else {
                        // Default fallback
                        window.location.href = 'signup.php';
                    }
                }
            });
        }

        function submitFormViaAPI(form) {
            const formData = new FormData(form);
            
            // Show loading indicator
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait while we process your request',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('API/registerUser.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                return response.text();
            })
            .then(text => {
                // Debug: Log the raw response
                console.log('Raw response from server:', text);
                
                // Try to parse JSON
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('JSON parsing error:', e);
                    console.error('Raw response that failed to parse:', text);
                    
                    // Check if it's a PHP error/warning mixed with JSON
                    if (text.includes('{') && text.includes('}')) {
                        // Try to extract JSON from the response
                        const jsonStart = text.indexOf('{');
                        const jsonEnd = text.lastIndexOf('}') + 1;
                        if (jsonStart !== -1 && jsonEnd > jsonStart) {
                            try {
                                const extractedJson = text.substring(jsonStart, jsonEnd);
                                console.log('Attempting to parse extracted JSON:', extractedJson);
                                data = JSON.parse(extractedJson);
                            } catch (e2) {
                                throw new Error('Server returned invalid JSON response. Please check server logs.');
                            }
                        } else {
                            throw new Error('Server returned invalid JSON response. Please check server logs.');
                        }
                    } else {
                        throw new Error('Server returned non-JSON response. Please check server logs.');
                    }
                }
                
                Swal.close();
                
                if (data.success) {
                    if (data.redirect) {
                        // Clear signup data since registration is complete
                        clearAllDataAndRedirect();
                        window.location.href = data.redirect;
                    } else if (data.user_type === 'buyer') {
                        window.location.href = '?step=profile_upload';
                    } else if (data.user_type === 'artist') {
                        window.location.href = '?step=artist_verification';
                    } else {
                        window.location.href = '?step=success';
                    }
                } else {
                    showErrors(data.errors || [data.message]);
                    if (data.errors) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Registration Error',
                            html: data.errors.join('<br>'),
                            confirmButtonColor: '#8b6f47'
                        });
                    }
                }
            })
            .catch(error => {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Connection Error',
                    text: 'Failed to connect to server. Please try again.',
                    confirmButtonColor: '#8b6f47'
                });
            });
        }

        function submitArtistVerification(form) {
            console.log('submitArtistVerification called');
            const formData = new FormData(form);
            
            // Validate required fields
            const nationalId = formData.get('national_id');
            const artSpecialty = formData.get('art_specialty');
            const yearsExperience = formData.get('years_experience');
            const bio = formData.get('bio');
            const frontPhoto = formData.get('id_front');
            const backPhoto = formData.get('id_back');
            
            console.log('Form data:', {
                nationalId: nationalId,
                artSpecialty: artSpecialty,
                yearsExperience: yearsExperience,
                bio: bio ? bio.substring(0, 50) + '...' : null,
                frontPhoto: frontPhoto ? frontPhoto.name : 'No file',
                backPhoto: backPhoto ? backPhoto.name : 'No file'
            });
            
            // Check for missing required fields with specific messages
            const missingFields = [];
            const missingPhotos = [];
            
            if (!nationalId || nationalId.trim() === '') {
                missingFields.push('National ID Number');
            }
            if (!artSpecialty || artSpecialty.trim() === '') {
                missingFields.push('Art Specialty');
            }
            if (!yearsExperience || yearsExperience.trim() === '') {
                missingFields.push('Years of Experience');
            }
            if (!bio || bio.trim() === '') {
                missingFields.push('About You section');
            }
            
            // Check for missing ID photos separately
            if (!frontPhoto || frontPhoto.size === 0) {
                missingPhotos.push('Front ID Photo');
            }
            if (!backPhoto || backPhoto.size === 0) {
                missingPhotos.push('Back ID Photo');
            }
            
            // Handle missing ID photos with specific SweetAlert
            if (missingPhotos.length > 0) {
                let photoMessage = '';
                let photoTitle = '';
                
                if (missingPhotos.length === 2) {
                    photoTitle = 'ID Photos Required';
                    photoMessage = 'Please upload both your National ID front and back photos. These are required to verify your identity.';
                } else if (missingPhotos.includes('Front ID Photo')) {
                    photoTitle = 'Front ID Photo Missing';
                    photoMessage = 'Please upload the front side of your National ID. This is required for identity verification.';
                } else {
                    photoTitle = 'Back ID Photo Missing';
                    photoMessage = 'Please upload the back side of your National ID. This is required for identity verification.';
                }
                
                Swal.fire({
                    icon: 'warning',
                    title: photoTitle,
                    text: photoMessage,
                    confirmButtonColor: '#8b6f47',
                    confirmButtonText: 'Upload Photos'
                });
                return;
            }
            
            // Handle other missing fields
            if (missingFields.length > 0) {
                const missingText = missingFields.length === 1 
                    ? `Please fill in the missing field: ${missingFields[0]}`
                    : `Please fill in the missing fields: ${missingFields.join(', ')}`;
                
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Required Information',
                    text: missingText + '. Note: Profile picture is optional.',
                    confirmButtonColor: '#8b6f47'
                });
                return;
            }
            
            // National ID validation - minimum 10 digits
            if (!/^[0-9]+$/.test(nationalId)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid National ID',
                    text: 'National ID must contain only numbers.',
                    confirmButtonColor: '#8b6f47'
                });
                return;
            }
            
            if (nationalId.length < 10) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid National ID',
                    text: 'National ID must be at least 10 digits long.',
                    confirmButtonColor: '#8b6f47'
                });
                return;
            }
            
            // Art specialty validation - must contain characters, not numbers only
            if (/^[0-9]+$/.test(artSpecialty.trim())) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Art Specialty',
                    text: 'Art specialty must contain characters, not numbers only.',
                    confirmButtonColor: '#8b6f47'
                });
                return;
            }
            
            if (artSpecialty.trim().length < 2) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Art Specialty',
                    text: 'Art specialty must be at least 2 characters long.',
                    confirmButtonColor: '#8b6f47'
                });
                return;
            }
            
            // Years of experience validation
            const years = parseInt(yearsExperience);
            if (isNaN(years) || years < 0 || years > 100) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Years of Experience',
                    text: 'Years of experience must be between 0 and 100.',
                    confirmButtonColor: '#8b6f47'
                });
                return;
            }
            
            // Bio validation - minimum 10 words
            const wordCount = bio.trim().split(/\s+/).filter(word => word.length > 0).length;
            if (wordCount < 10) {
                Swal.fire({
                    icon: 'error',
                    title: 'Insufficient About You Section',
                    text: `Please write at least 10 words about yourself. Currently you have ${wordCount} words.`,
                    confirmButtonColor: '#8b6f47'
                });
                return;
            }
            
            // Optional profile picture validation
            const profilePicture = formData.get('profile_picture');
            if (profilePicture && profilePicture.size > 0) {
                const maxProfileSize = 5 * 1024 * 1024; // 5MB for profile picture
                const allowedProfileTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                
                if (profilePicture.size > maxProfileSize) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Profile Picture Too Large',
                        text: `Profile picture is too large (${(profilePicture.size / 1024 / 1024).toFixed(2)}MB). Maximum size is 5MB.`,
                        confirmButtonColor: '#8b6f47'
                    });
                    return;
                }
                
                if (!allowedProfileTypes.includes(profilePicture.type)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Profile Picture Type',
                        text: `Profile picture type (${profilePicture.type || 'unknown'}) is not supported. Please use JPEG, PNG, or GIF.`,
                        confirmButtonColor: '#8b6f47'
                    });
                    return;
                }
            }
            
            // Validate file sizes (10MB limit for ID photos)
            const maxFileSize = 10 * 1024 * 1024; // 10MB in bytes
            if (frontPhoto.size > maxFileSize) {
                Swal.fire({
                    icon: 'error',
                    title: 'File Too Large',
                    text: `Front ID photo is too large (${(frontPhoto.size / 1024 / 1024).toFixed(2)}MB). Maximum size is 10MB.`,
                    confirmButtonColor: '#8b6f47'
                });
                return;
            }
            
            if (backPhoto.size > maxFileSize) {
                Swal.fire({
                    icon: 'error',
                    title: 'File Too Large',
                    text: `Back ID photo is too large (${(backPhoto.size / 1024 / 1024).toFixed(2)}MB). Maximum size is 10MB.`,
                    confirmButtonColor: '#8b6f47'
                });
                return;
            }
            
            // Validate file types for ID photos
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!allowedTypes.includes(frontPhoto.type) || !allowedTypes.includes(backPhoto.type)) {
                const invalidFiles = [];
                if (!allowedTypes.includes(frontPhoto.type)) {
                    invalidFiles.push(`Front photo (${frontPhoto.type || 'unknown type'})`);
                }
                if (!allowedTypes.includes(backPhoto.type)) {
                    invalidFiles.push(`Back photo (${backPhoto.type || 'unknown type'})`);
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid File Type',
                    text: `Invalid file type for: ${invalidFiles.join(', ')}. ID photos must be JPEG or PNG images.`,
                    confirmButtonColor: '#8b6f47'
                });
                return;
            }
            
            // Show loading indicator
            Swal.fire({
                title: 'Uploading...',
                text: 'Please wait while we process your verification documents',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // First upload ID photos with proper naming
            const idFormData = new FormData();
            idFormData.append('national_id', nationalId);
            idFormData.append('front_photo', frontPhoto);
            idFormData.append('back_photo', backPhoto);
            idFormData.append('signup_upload', 'true'); // Flag to indicate this is a signup upload
            
            let registrationResult = null; // Store the registration result
            
            // First register the user to get the user_id
            fetch('API/registerUser.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                return response.text();
            })
            .then(text => {
                // Debug: Log the raw response
                console.log('Raw registration response:', text);
                
                // Try to parse JSON
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('JSON parsing error:', e);
                    console.error('Raw response that failed to parse:', text);
                    
                    // Check if it's a PHP error/warning mixed with JSON
                    if (text.includes('{') && text.includes('}')) {
                        // Try to extract JSON from the response
                        const jsonStart = text.indexOf('{');
                        const jsonEnd = text.lastIndexOf('}') + 1;
                        if (jsonStart !== -1 && jsonEnd > jsonStart) {
                            try {
                                const extractedJson = text.substring(jsonStart, jsonEnd);
                                console.log('Attempting to parse extracted registration JSON:', extractedJson);
                                data = JSON.parse(extractedJson);
                            } catch (e2) {
                                throw new Error('Registration server returned invalid JSON response. Please check server logs.');
                            }
                        } else {
                            throw new Error('Registration server returned invalid JSON response. Please check server logs.');
                        }
                    } else {
                        throw new Error('Registration server returned non-JSON response. Please check server logs.');
                    }
                }
                
                if (data.success) {
                    registrationResult = data; // Store the registration result
                    
                    console.log('Registration response:', data);
                    console.log('User ID from registration:', data.user_id);
                    console.log('Type of user_id:', typeof data.user_id);
                    
                    if (!data.user_id) {
                        throw new Error('Registration did not return a valid user ID');
                    }
                    
                    // Now upload ID photos with the real user_id
                    idFormData.append('actual_user_id', data.user_id);
                    console.log('Added actual_user_id to FormData:', data.user_id);
                    
                    return fetch('API/uploadIdPhotos.php', {
                        method: 'POST',
                        body: idFormData
                    });
                } else {
                    // Handle validation errors from server
                    if (data.errors && data.errors.length > 0) {
                        throw new Error(data.errors.join('. '));
                    } else {
                        throw new Error(data.message || 'Failed to register user');
                    }
                }
            })
            .then(response => {
                return response.text();
            })
            .then(text => {
                // Debug: Log the raw ID photo upload response
                console.log('Raw ID photo upload response:', text);
                
                // Try to parse JSON
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('ID photo upload JSON parsing error:', e);
                    console.error('Raw response that failed to parse:', text);
                    
                    // Check if it's a PHP error/warning mixed with JSON
                    if (text.includes('{') && text.includes('}')) {
                        // Try to extract JSON from the response
                        const jsonStart = text.indexOf('{');
                        const jsonEnd = text.lastIndexOf('}') + 1;
                        if (jsonStart !== -1 && jsonEnd > jsonStart) {
                            try {
                                const extractedJson = text.substring(jsonStart, jsonEnd);
                                console.log('Attempting to parse extracted ID photo JSON:', extractedJson);
                                data = JSON.parse(extractedJson);
                            } catch (e2) {
                                throw new Error('ID photo upload server returned invalid JSON response. Please check server logs.');
                            }
                        } else {
                            throw new Error('ID photo upload server returned invalid JSON response. Please check server logs.');
                        }
                    } else {
                        throw new Error('ID photo upload server returned non-JSON response. Please check server logs.');
                    }
                }
                
                if (data.success) {
                    // Both registration and ID upload successful
                    Swal.close();
                    
                    // Clear all signup data since registration is complete
                    clearAllDataAndRedirect();
                    
                    // Always show success message since registration was completed
                    // ID photo issues are secondary and can be resolved later
                    Swal.fire({
                        icon: 'success',
                        title: 'Verification Submitted!',
                        text: 'Your artist verification has been submitted successfully. We will review your documents and get back to you within 24-48 hours.',
                        confirmButtonColor: '#8b6f47'
                    }).then(() => {
                        // Use the redirect URL from the registration API response
                        if (registrationResult && registrationResult.redirect) {
                            window.location.href = registrationResult.redirect;
                        } else {
                            // Fallback to login.php if no redirect specified
                            window.location.href = 'login.php';
                        }
                    });
                } else {
                    // Handle ID photo upload errors
                    const errorMessage = data.message || 'Failed to upload ID photos';
                    
                    // Check if it's a validation error
                    if (errorMessage.includes('Invalid actual_user_id') || errorMessage.includes('user_id')) {
                        throw new Error('Registration error: Invalid user ID. Please try again.');
                    } else if (errorMessage.includes('file') || errorMessage.includes('upload')) {
                        throw new Error('File upload error: ' + errorMessage);
                    } else {
                        throw new Error('ID Photo Upload Failed: ' + errorMessage);
                    }
                }
            })
            .catch(error => {
                Swal.close();
                
                // Handle different types of errors with specific messages
                let errorTitle = 'Submission Error';
                let errorText = 'Failed to submit verification. Please try again.';
                
                if (error.message.includes('Registration error:')) {
                    errorTitle = 'Registration Failed';
                    errorText = error.message.replace('Registration error: ', '');
                } else if (error.message.includes('File upload error:')) {
                    errorTitle = 'File Upload Failed';
                    errorText = error.message.replace('File upload error: ', '');
                } else if (error.message.includes('ID Photo Upload Failed:')) {
                    errorTitle = 'ID Photo Upload Failed';
                    errorText = error.message.replace('ID Photo Upload Failed: ', '');
                } else if (error.message.includes('fetch')) {
                    errorTitle = 'Network Error';
                    errorText = 'Unable to connect to server. Please check your internet connection and try again.';
                } else if (error.message) {
                    errorText = error.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: errorTitle,
                    text: errorText,
                    confirmButtonColor: '#8b6f47',
                    confirmButtonText: 'Try Again'
                });
            });
        }

        // Clear all data and redirect to step 1
        function clearAllDataAndRedirect() {
            // Clear any form data that might be persisted
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                if (form.reset) {
                    form.reset();
                }
            });
            
            // Clear all file inputs specifically
            const fileInputs = document.querySelectorAll('input[type="file"]');
            fileInputs.forEach(input => {
                input.value = '';
            });
            
            // Clear any preview elements
            const previews = document.querySelectorAll('.file-preview');
            previews.forEach(preview => {
                preview.style.display = 'none';
                preview.innerHTML = '';
            });
            
            // Clear user type selection
            const userTypeOptions = document.querySelectorAll('.user-type-option');
            userTypeOptions.forEach(option => {
                option.classList.remove('selected');
            });
            const userTypeInputs = document.querySelectorAll('input[name="user_type"]');
            userTypeInputs.forEach(input => {
                input.checked = false;
            });
            
            return false; // No redirect needed
        }

        // Form validation and submission
        document.addEventListener('DOMContentLoaded', function() {
            // Step 1 form - check user type selection before submitting
            const signupForm = document.getElementById('signupForm');
            if (signupForm) {
                signupForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Check if user type is selected
                    const userType = document.querySelector('input[name="user_type"]:checked');
                    if (!userType) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Please Choose a Type',
                            text: 'Please select whether you want to join as an Art Buyer or Artist.',
                            confirmButtonColor: '#8b6f47'
                        });
                        return;
                    }
                    
                    submitFormViaAPI(this);
                });
            }

            // Profile upload form
            const profileUploadForm = document.getElementById('profileUploadForm');
            if (profileUploadForm) {
                profileUploadForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    submitFormViaAPI(this);
                });
            }

            // Artist verification form
            const artistVerificationForm = document.getElementById('artistVerificationForm');
            console.log('Looking for artistVerificationForm:', artistVerificationForm);
            if (artistVerificationForm) {
                console.log('Artist verification form found, adding event listener');
                artistVerificationForm.addEventListener('submit', function(e) {
                    console.log('Form submit event triggered');
                    e.preventDefault();
                    submitArtistVerification(this);
                });
            } else {
                console.log('Artist verification form NOT found');
            }

            // Real-time input validation indicators
            addInputValidation();
        });

        // Function to add real-time validation to inputs
        function addInputValidation() {
            // Email validation
            const emailInput = document.getElementById('email');
            if (emailInput) {
                emailInput.addEventListener('input', function() {
                    validateEmail(this);
                });
            }

            // Phone validation
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                phoneInput.addEventListener('input', function() {
                    validatePhone(this);
                });
            }

            // First name validation
            const firstNameInput = document.getElementById('first_name');
            if (firstNameInput) {
                firstNameInput.addEventListener('input', function() {
                    validateName(this);
                });
            }

            // Last name validation
            const lastNameInput = document.getElementById('last_name');
            if (lastNameInput) {
                lastNameInput.addEventListener('input', function() {
                    validateName(this);
                });
            }

            // Password validation
            const passwordInput = document.getElementById('password');
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    updatePasswordStrength(this.value);
                    validatePasswordInput(this);
                    // Also validate confirm password if it has a value
                    const confirmPasswordInput = document.getElementById('confirm_password');
                    if (confirmPasswordInput && confirmPasswordInput.value) {
                        checkPasswordMatch();
                        validatePasswordConfirmInput(confirmPasswordInput);
                    }
                });
            }

            // Confirm password validation
            const confirmPasswordInput = document.getElementById('confirm_password');
            if (confirmPasswordInput) {
                confirmPasswordInput.addEventListener('input', function() {
                    checkPasswordMatch();
                    validatePasswordConfirmInput(this);
                });
            }

            // National ID validation (for artist verification)
            const nationalIdInput = document.getElementById('national_id');
            if (nationalIdInput) {
                nationalIdInput.addEventListener('input', function() {
                    validateNationalId(this);
                });
            }

            // Art specialty validation
            const artSpecialtyInput = document.getElementById('art_specialty');
            if (artSpecialtyInput) {
                artSpecialtyInput.addEventListener('input', function() {
                    validateArtSpecialty(this);
                });
            }

            // Years of experience validation
            const yearsExperienceInput = document.getElementById('years_experience');
            if (yearsExperienceInput) {
                yearsExperienceInput.addEventListener('input', function() {
                    validateYearsExperience(this);
                });
            }

            // Bio validation
            const bioInput = document.getElementById('bio');
            if (bioInput) {
                bioInput.addEventListener('input', function() {
                    validateBio(this);
                });
            }
        }

        // Validation functions
        function validateEmail(input) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const isValid = emailRegex.test(input.value);
            
            if (input.value === '') {
                resetValidation(input);
            } else if (isValid) {
                setValid(input);
            } else {
                setInvalid(input);
            }
        }

        function validatePhone(input) {
            const phoneRegex = /^\+?[0-9\s\-\(\)]{10,}$/;
            const isValid = phoneRegex.test(input.value);
            
            if (input.value === '') {
                resetValidation(input);
            } else if (isValid) {
                setValid(input);
            } else {
                setInvalid(input);
            }
        }

        function validateName(input) {
            const nameRegex = /^[a-zA-Z\s]{2,}$/;
            const isValid = nameRegex.test(input.value);
            
            if (input.value === '') {
                resetValidation(input);
            } else if (isValid) {
                setValid(input);
            } else {
                setInvalid(input);
            }
        }

        function validatePasswordInput(input) {
            const password = input.value;
            const minLength = 8;
            const hasLower = /[a-z]/.test(password);
            const hasUpper = /[A-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[^a-zA-Z0-9]/.test(password);
            
            const isValid = password.length >= minLength && hasLower && hasUpper && hasNumber;
            
            if (password === '') {
                resetValidation(input);
            } else if (isValid) {
                setValid(input);
            } else {
                setInvalid(input);
            }
        }

        function validatePasswordConfirmInput(input) {
            const passwordInput = document.getElementById('password');
            const isValid = input.value === passwordInput.value && input.value !== '';
            
            if (input.value === '') {
                resetValidation(input);
            } else if (isValid) {
                setValid(input);
            } else {
                setInvalid(input);
            }
        }

        function updatePasswordStrength(password) {
            const strengthBar = document.querySelector('#passwordStrength .strength-fill');
            const strengthText = document.querySelector('#passwordStrength .strength-text');
            
            if (!strengthBar || !strengthText) return;
            
            let strength = 0;
            let text = 'Very Weak';
            let color = '#ff4444';
            
            if (password.length >= 8) strength += 20;
            if (password.match(/[a-z]/)) strength += 20;
            if (password.match(/[A-Z]/)) strength += 20;
            if (password.match(/[0-9]/)) strength += 20;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 20;
            
            if (strength >= 80) {
                text = 'Very Strong';
                color = '#22c55e';
            } else if (strength >= 60) {
                text = 'Strong';
                color = '#84cc16';
            } else if (strength >= 40) {
                text = 'Medium';
                color = '#f59e0b';
            } else if (strength >= 20) {
                text = 'Weak';
                color = '#f97316';
            }
            
            strengthBar.style.width = strength + '%';
            strengthBar.style.backgroundColor = color;
            strengthText.textContent = text;
        }

        function checkPasswordMatch() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const matchIndicator = document.getElementById('matchIndicator');
            
            if (!passwordInput || !confirmPasswordInput || !matchIndicator) return;
            
            if (confirmPasswordInput.value === '') {
                matchIndicator.textContent = '';
                return;
            }
            
            if (passwordInput.value === confirmPasswordInput.value) {
                matchIndicator.textContent = '✓ Passwords match';
                matchIndicator.style.color = '#22c55e';
            } else {
                matchIndicator.textContent = '✗ Passwords do not match';
                matchIndicator.style.color = '#ef4444';
            }
        }

        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = field.nextElementSibling;
            const icon = button.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                field.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        function validateNationalId(input) {
            const nationalIdRegex = /^[0-9]{10,}$/;
            const isValid = nationalIdRegex.test(input.value);
            
            if (input.value === '') {
                resetValidation(input);
            } else if (isValid) {
                setValid(input);
            } else {
                setInvalid(input);
            }
        }

        function validateArtSpecialty(input) {
            const isValid = input.value.trim().length >= 3;
            
            if (input.value === '') {
                resetValidation(input);
            } else if (isValid) {
                setValid(input);
            } else {
                setInvalid(input);
            }
        }

        function validateYearsExperience(input) {
            const value = parseInt(input.value);
            const isValid = !isNaN(value) && value >= 0 && value <= 50;
            
            if (input.value === '') {
                resetValidation(input);
            } else if (isValid) {
                setValid(input);
            } else {
                setInvalid(input);
            }
        }

        function validateBio(input) {
            const isValid = input.value.trim().length >= 10;
            
            if (input.value === '') {
                resetValidation(input);
            } else if (isValid) {
                setValid(input);
            } else {
                setInvalid(input);
            }
        }

        // Helper functions to set validation states
        function setValid(input) {
            input.classList.remove('invalid');
            input.classList.add('valid');
        }

        function setInvalid(input) {
            input.classList.remove('valid');
            input.classList.add('invalid');
        }

        function resetValidation(input) {
            input.classList.remove('valid', 'invalid');
        }
    </script>

    
    <!-- Navbar JavaScript -->
    <script src="./components/Navbar/navbar.js"></script>
    <script src="./components/BurgerMenu/burger-menu.js"></script>
</body>
</html>
