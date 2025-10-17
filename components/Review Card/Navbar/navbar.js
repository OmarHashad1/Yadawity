// Yadawity Modern Navbar Component JavaScript

// User role simulation - In production, this would come from your server/API
let currentUser = {
    name: 'Guest User',
    role: 'visitor', // 'visitor', 'buyer', 'artist'
    isLoggedIn: false
};

// Make currentUser globally accessible for other components
window.currentUser = currentUser;

// Initialize navbar functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', async function() {
    initializeNavbar();
    await loadUserData();
    
    // Listen for user updates from other components
    window.addEventListener('userUpdated', function(event) {
        console.log('Navbar received userUpdated event:', event.detail);
        if (event.detail.action === 'logout') {
            currentUser = event.detail.user;
            updateUserInterface();
            forceHideArtistSections();
        }
    });
    
    // Also check authentication when page becomes visible (after redirects)
    document.addEventListener('visibilitychange', async function() {
        if (!document.hidden) {
            console.log('Page became visible, rechecking authentication...');
            await checkRealAuthenticationStatus();
            updateUserInterface();
        }
    });
    
    // Check authentication again after a short delay for redirects from login
    setTimeout(async () => {
        console.log('Delayed authentication check...');
        await checkRealAuthenticationStatus();
        updateUserInterface();
    }, 500);
});

function initializeNavbar() {
    // Initialize all navbar functions
    setupMobileMenu();
    // setupSearch removed since search input is no longer in navbar
    setupUserDropdown();
    setupScrollEffects();
    setupCounters();
    setActivePage();
    setupAnimations();
}

// Load user data from localStorage or API
async function loadUserData() {
    // First check if user is logged in via real authentication (cookies/session)
    await checkRealAuthenticationStatus();
    
    // Then check localStorage as fallback
    const savedUser = localStorage.getItem('currentUser');
    if (savedUser) {
        try {
            const localUser = JSON.parse(savedUser);
            // Only use localStorage if not overridden by real auth
            if (!currentUser.isLoggedIn) {
                currentUser = localUser;
            }
        } catch (e) {
            console.error('Error parsing saved user data:', e);
            localStorage.removeItem('currentUser');
        }
    }
    
    // If no authentication found, ensure clean guest state
    if (!currentUser.isLoggedIn) {
        currentUser = {
            name: 'Guest User',
            role: 'visitor',
            isLoggedIn: false
        };
    }
    
    console.log('Final currentUser after loadUserData:', currentUser);
    updateUserInterface();
    
    // Remove old artist section logic since we now handle everything in dropdown
}

// Check real authentication status from server/cookies using checkCredentials API
async function checkRealAuthenticationStatus() {
    try {
        console.log('Starting authentication check...');
        
        // Always try the API call first since cookies are being passed automatically
        console.log('Making API call to check credentials...');
        const response = await fetch('./API/checkCredentials.php', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        
        console.log('API Response status:', response.status);
            
            if (response.ok) {
                const responseText = await response.text();
                console.log('Raw API response:', responseText);
                
                try {
                    const userData = JSON.parse(responseText);
                    console.log('Parsed user data from checkCredentials API:', userData);
                    
                    // Handle checkCredentials API response format
                    if (userData.authenticated === true) {
                        // Extract user information from the API response
                        const userName = userData.user_name || 'User';
                        const userType = userData.user_type || 'guest';
                        const userId = userData.user_id;
                        const userEmail = userData.user_email;
                        const isVerified = userData.is_verified || false;
                        
                        console.log('Extracted user info:', { userName, userType, userId, userEmail, isVerified });
                        
                        // Update currentUser with real data
                        currentUser = {
                            name: userName,
                            role: userType,
                            isLoggedIn: true,
                            userId: userId,
                            email: userEmail,
                            isVerified: isVerified
                        };
                        
                        // Update global reference for other components
                        window.currentUser = currentUser;
                        
                        // Save to localStorage for consistency
                        localStorage.setItem('currentUser', JSON.stringify(currentUser));
                        
                        console.log('Updated currentUser with real auth:', currentUser);
                        
                        // Force UI update immediately after setting currentUser
                        setTimeout(() => {
                            console.log('Forcing UI update after auth check...');
                            updateUserInterface();
                        }, 100);
                        
                        return true; // Successfully authenticated
                    } else {
                        console.log('User not authenticated:', userData.message);
                        // Set user as guest if not authenticated
                        currentUser = {
                            name: 'Guest User',
                            role: 'guest',
                            isLoggedIn: false
                        };
                        window.currentUser = currentUser;
                        updateUserInterface();
                        return false;
                    }
                } catch (parseError) {
                    console.error('Error parsing API response:', parseError);
                    console.log('Response was not valid JSON:', responseText);
                }
            } else {
                console.warn('Failed to check credentials. Status:', response.status);
                const errorText = await response.text();
                console.log('Error response:', errorText);
                
                // Set user as guest if API fails
                currentUser = {
                    name: 'Guest User',
                    role: 'guest',
                    isLoggedIn: false
                };
                updateUserInterface();
            }
    } catch (error) {
        console.error('Error checking authentication status:', error);
        // Set user as guest on error
        currentUser = {
            name: 'Guest User',
            role: 'guest',
            isLoggedIn: false
        };
        updateUserInterface();
    }
    
    return false; // Not authenticated
}

// Force hide all artist sections
function forceHideArtistSections() {
    // Hide all possible artist sections with specific IDs
    const artistElementIds = [
        'artist-section',
        'mobileArtistSection', 
        'mobile-artist-section'
    ];
    
    artistElementIds.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.style.display = 'none';
            element.style.visibility = 'hidden';
            console.log(`Hidden artist section with ID: ${id}`);
        }
    });
    
    // Also hide by class names
    const artistSections = document.querySelectorAll('.artist-section, .mobile-artist-section, .burgerArtistPortal');
    artistSections.forEach(section => {
        if (section) {
            section.style.display = 'none';
            section.style.visibility = 'hidden';
        }
    });
    
    console.log('Forced hiding of all artist sections');
}

// Update UI based on user role
function updateUserInterface() {
    const userName = document.querySelector('.user-name');
    const userRole = document.querySelector('.user-role');
    const artistSection = document.querySelector('.artist-section');
    const loginLogoutBtn = document.getElementById('login-logout');
    const generalDropdownSection = document.querySelector('.dropdown-section');
    const userAccountBtn = document.getElementById('user-account');
    const userAvatar = document.querySelector('.user-avatar');
    
    console.log('=== UPDATE USER INTERFACE ===');
    console.log('Current user state:', currentUser);
    console.log('Found elements:', {
        userName: !!userName,
        userRole: !!userRole,
        artistSection: !!artistSection,
        loginLogoutBtn: !!loginLogoutBtn,
        generalDropdownSection: !!generalDropdownSection,
        userAccountBtn: !!userAccountBtn,
        userAvatar: !!userAvatar
    });
    
    if (currentUser.isLoggedIn) {
        console.log('User is logged in - updating interface...');
        
        // User is logged in - update user info
        if (userName) {
            userName.textContent = currentUser.name;
            console.log('Updated user name to:', currentUser.name);
        }
        if (userRole) {
            userRole.textContent = currentUser.role.toUpperCase();
            console.log('Updated user role to:', currentUser.role.toUpperCase());
        }
        
        // Update user profile photos
        updateUserProfilePhoto(currentUser.userId);
        
        // Update login/logout button to logout
        if (loginLogoutBtn) {
            loginLogoutBtn.innerHTML = '<i class="fas fa-sign-out-alt"></i><span>Logout</span>';
            loginLogoutBtn.onclick = logout;
            loginLogoutBtn.href = '#';
            console.log('Updated login/logout button to logout');
        }
        
        // Show/hide sections based on user role and verification status
        if (currentUser.role === 'artist') {
            console.log('User is an artist - checking verification status...');
            console.log('Is verified:', currentUser.isVerified);
            
            if (currentUser.isVerified) {
                console.log('Artist is verified - showing artist section');
                // Show artist section for verified artists
                if (artistSection) {
                    artistSection.style.display = 'block';
                    artistSection.style.visibility = 'visible';
                    console.log('Artist section shown for verified artist');
                }
            } else {
                console.log('Artist is not verified - hiding artist section');
                // Hide artist section for unverified artists
                if (artistSection) {
                    artistSection.style.display = 'none';
                    console.log('Artist section hidden for unverified artist');
                }
            }
            
            // Show general dropdown section for all artists (verified or not)
            if (generalDropdownSection) {
                generalDropdownSection.style.display = 'block';
            }
        } else if (currentUser.role === 'buyer' || currentUser.role === 'admin') {
            console.log('User is buyer/admin - hiding artist section');
            // Hide artist section for buyers and admins
            if (artistSection) {
                artistSection.style.display = 'none';
                console.log('Artist section hidden for non-artist user');
            }
            // Show general dropdown section for buyers and admins
            if (generalDropdownSection) {
                generalDropdownSection.style.display = 'block';
            }
        } else {
            console.log('User has other role - hiding artist section');
            // For any other role, hide artist section
            if (artistSection) {
                artistSection.style.display = 'none';
            }
            // Show general dropdown section
            if (generalDropdownSection) {
                generalDropdownSection.style.display = 'block';
            }
        }
    } else {
        console.log('User is not logged in - showing guest interface...');
        
        // User is not logged in (guest)
        if (userName) {
            userName.textContent = 'Guest User';
            console.log('Set user name to Guest User');
        }
        if (userRole) {
            userRole.textContent = 'VISITOR';
            console.log('Set user role to VISITOR');
        }
        
        // Reset to default icons for guest users
        resetToDefaultIcons();
        
        // Update login/logout button to login
        if (loginLogoutBtn) {
            loginLogoutBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i><span>Login</span>';
            loginLogoutBtn.onclick = function(e) {
                e.preventDefault();
                window.location.href = 'login.php';
            };
            loginLogoutBtn.href = 'login.php';
            console.log('Updated login/logout button to login');
        }
        
        // Hide artist section for guests
        if (artistSection) {
            artistSection.style.display = 'none';
            console.log('Artist section hidden for guest user');
        }
        
        // Show general dropdown section for guests (they can still access profile, support, etc.)
        if (generalDropdownSection) {
            generalDropdownSection.style.display = 'block';
        }
    }
    
    console.log('=== END UPDATE USER INTERFACE ===');
    
    // Trigger burger menu update if available
    if (window.burgerMenuInstance && typeof window.burgerMenuInstance.updateUserInterface === 'function') {
        window.burgerMenuInstance.updateUserInterface();
    }
}

// Function to update user profile photo
async function updateUserProfilePhoto(userId) {
    if (!userId) {
        console.log('No user ID provided, keeping default icons');
        return;
    }
    
    try {
        console.log('Fetching profile photo for user ID:', userId);
        
        // Try to load images with different naming patterns based on the actual files in the directory
        const tryLoadImage = (path) => {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.onload = () => resolve(path);
                img.onerror = () => reject();
                img.src = path;
            });
        };
        
        // Try different naming patterns based on the actual files we found in the directory
        // The photos appear to be named like: user_{ID}_{Name}_profile.jpg
        const namingPatterns = [
            // Try the pattern with user names from the directory
            `uploads/user_profile_picture/user_${userId}_Omar_Hashad_profile.jpg`,
            `uploads/user_profile_picture/user_${userId}_omar_hashad_profile.jpg`,
            `uploads/user_profile_picture/user_${userId}_nancy_Hashad_profile.jpg`,
            `uploads/user_profile_picture/user_${userId}_Nancy_Hashad_profile.jpg`,
            // Try simple patterns
            `uploads/user_profile_picture/${userId}.jpg`,
            `uploads/user_profile_picture/${userId}.png`,
            `uploads/user_profile_picture/user_${userId}.jpg`,
            `uploads/user_profile_picture/user_${userId}.png`
        ];
        
        for (const photoPath of namingPatterns) {
            try {
                const photoUrl = await tryLoadImage(photoPath);
                console.log('Profile photo found:', photoUrl);
                
                // Update main user account button with profile photo
                const userAccountBtn = document.getElementById('user-account');
                if (userAccountBtn) {
                    userAccountBtn.innerHTML = `<img src="${photoUrl}" alt="Profile Photo" class="user-profile-photo" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; position: absolute; top: 0; left: 0;">`;
                    userAccountBtn.style.position = 'relative';
                    userAccountBtn.style.overflow = 'hidden';
                    userAccountBtn.style.borderRadius = '50%';
                    userAccountBtn.style.width = '50px';
                    userAccountBtn.style.height = '50px';
                    userAccountBtn.style.minWidth = '50px';
                    userAccountBtn.style.minHeight = '50px';
                    console.log('Updated main user account button with profile photo');
                }
                
                // Update dropdown avatar with profile photo
                const userAvatar = document.querySelector('.user-avatar');
                if (userAvatar) {
                    userAvatar.innerHTML = `<img src="${photoUrl}" alt="Profile Photo" class="user-avatar-photo" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; position: absolute; top: 0; left: 0;">`;
                    userAvatar.style.position = 'relative';
                    userAvatar.style.overflow = 'hidden';
                    userAvatar.style.borderRadius = '50%';
                    userAvatar.style.width = '60px';
                    userAvatar.style.height = '60px';
                    userAvatar.style.minWidth = '60px';
                    userAvatar.style.minHeight = '60px';
                    console.log('Updated dropdown avatar with profile photo');
                }
                return;
            } catch (error) {
                // Continue to next pattern
                continue;
            }
        }
        
        console.log('No profile photo found with any naming pattern, keeping default icons');
        resetToDefaultIcons();
        
    } catch (error) {
        console.error('Error loading profile photo:', error);
        resetToDefaultIcons();
    }
}

// Function to reset to default icons
function resetToDefaultIcons() {
    console.log('Resetting to default user icons');
    
    // Reset main user account button to default icon
    const userAccountBtn = document.getElementById('user-account');
    if (userAccountBtn) {
        userAccountBtn.innerHTML = '<i class="fas fa-user"></i>';
        userAccountBtn.style.position = '';
        userAccountBtn.style.overflow = '';
        userAccountBtn.style.borderRadius = '';
        userAccountBtn.style.width = '';
        userAccountBtn.style.height = '';
        userAccountBtn.style.minWidth = '';
        userAccountBtn.style.minHeight = '';
        console.log('Reset main user account button to default icon');
    }
    
    // Reset dropdown avatar to default icon
    const userAvatar = document.querySelector('.user-avatar');
    if (userAvatar) {
        userAvatar.innerHTML = '<i class="fas fa-user-circle"></i>';
        userAvatar.style.position = '';
        userAvatar.style.overflow = '';
        userAvatar.style.borderRadius = '';
        userAvatar.style.width = '';
        userAvatar.style.height = '';
        userAvatar.style.minWidth = '';
        userAvatar.style.minHeight = '';
        console.log('Reset dropdown avatar to default icon');
    }
}

// Simulate user login (for demo purposes)
function simulateLogin(userType = 'buyer') {
    currentUser = {
        name: userType === 'artist' ? 'Jane Smith' : userType === 'buyer' ? 'John Doe' : 'Guest User',
        role: userType,
        isLoggedIn: true,
        isVerified: userType === 'artist' ? true : false // Default verified artist for testing
    };
    
    localStorage.setItem('currentUser', JSON.stringify(currentUser));
    updateUserInterface();
    
    // Dispatch custom event to notify burger menu and other components
    window.dispatchEvent(new CustomEvent('userUpdated', {
        detail: { 
            user: currentUser,
            action: 'login',
            userType: userType
        }
    }));
    
    // Notification removed as requested
}

// Logout function
async function logout() {
    try {
        // Call the logout API
        const response = await fetch('./API/logout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        // Clear all user data regardless of API response
        currentUser = {
            name: 'Guest User',
            role: 'visitor',
            isLoggedIn: false
        };
        window.currentUser = currentUser;
        
        // Clear all localStorage related to user data
        localStorage.removeItem('currentUser');
        localStorage.clear(); // Clear everything to be safe
        
        // Force update UI immediately
        updateUserInterface();
        forceHideArtistSections();
        
        // Dispatch custom event to notify burger menu and other components
        window.dispatchEvent(new CustomEvent('userUpdated', {
            detail: { 
                user: currentUser,
                action: 'logout'
            }
        }));
        
        if (response && response.ok) {
            console.log('Logout API call successful');
        } else {
            console.warn('Logout API call failed, but local logout completed');
        }
        
        // Force page reload to ensure clean state
        setTimeout(() => {
            window.location.reload();
        }, 100);
        
    } catch (error) {
        console.error('Error during logout:', error);
        
        // Still complete the logout locally
        currentUser = {
            name: 'Guest User',
            role: 'visitor',
            isLoggedIn: false
        };
        localStorage.clear();
        updateUserInterface();
        forceHideArtistSections();
        
        // Force page reload even if API fails
        setTimeout(() => {
            window.location.reload();
        }, 100);
    }
}

// Enhanced Mobile Menu Toggle
function setupMobileMenu() {
    const navToggle = document.querySelector('.nav-toggle');
    const burgerMenuOverlay = document.getElementById('burger-menu-overlay');
    const navbar = document.querySelector('.navbar');

    if (navToggle && burgerMenuOverlay) {
        navToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Add smooth animation to toggle button
            navToggle.classList.toggle('active');
            
            // Use global burger menu functions if available
            if (typeof window.toggleBurgerMenu === 'function') {
                window.toggleBurgerMenu();
            } else {
                // Fallback: manually toggle burger menu with animation
                burgerMenuOverlay.classList.toggle('active');
                document.body.style.overflow = burgerMenuOverlay.classList.contains('active') ? 'hidden' : '';
                
                // Add blur effect to navbar
                if (burgerMenuOverlay.classList.contains('active')) {
                    navbar.style.backdropFilter = 'blur(10px)';
                } else {
                    navbar.style.backdropFilter = 'blur(20px)';
                }
            }
        });
    }
}


// Enhanced Scroll Effects
function setupScrollEffects() {
    const navbar = document.querySelector('.navbar');
    let lastScrollY = window.scrollY;
    let ticking = false;

    function updateNavbar() {
        const scrollY = window.scrollY;
        
        if (scrollY > 100) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }

        // Hide navbar on scroll down, show on scroll up
        if (scrollY > lastScrollY && scrollY > 200) {
            navbar.style.transform = 'translateY(-100%)';
        } else {
            navbar.style.transform = 'translateY(0)';
        }

        lastScrollY = scrollY;
        ticking = false;
    }

    function requestTick() {
        if (!ticking) {
            requestAnimationFrame(updateNavbar);
            ticking = true;
        }
    }

    window.addEventListener('scroll', requestTick, { passive: true });
}

// Enhanced Counters with Animation
function setupCounters() {
    console.log('setupCounters called - initializing dynamic counters');
    updateCartCount();
    updateWishlistCount();
}

// API functions to fetch dynamic counts
async function fetchCartCount() {
    try {
        const response = await fetch('API/getCart.php', {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        if (response.ok) {
            const data = await response.json();
            if (data.success && data.data) {
                return data.data.total_items || 0;
            }
        }
        return 0;
    } catch (error) {
        console.error('Error fetching cart count:', error);
        return 0;
    }
}

async function fetchWishlistCount() {
    try {
        const response = await fetch('API/getWishlist.php', {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        if (response.ok) {
            const data = await response.json();
            if (data.success && data.data) {
                return data.data.total_items || 0;
            }
        }
        return 0;
    } catch (error) {
        console.error('Error fetching wishlist count:', error);
        return 0;
    }
}

function updateCartCount() {
    console.log('updateCartCount called');
    const cartCount = document.getElementById('cart-count');
    if (cartCount) {
        console.log('Cart count element found, fetching from API...');
        // Fetch dynamic cart count from API
        fetchCartCount().then(count => {
            console.log('Cart count from API:', count);
            animateCounter(cartCount, count);
        }).catch(error => {
            console.warn('Error fetching cart count:', error);
            // Fallback to localStorage
            const count = localStorage.getItem('cartCount') || 0;
            console.log('Using fallback cart count:', count);
            animateCounter(cartCount, count);
        });
    } else {
        console.warn('Cart count element not found');
    }
}

function updateWishlistCount() {
    console.log('updateWishlistCount called');
    const wishlistCount = document.getElementById('wishlist-count');
    if (wishlistCount) {
        console.log('Wishlist count element found, fetching from API...');
        // Fetch dynamic wishlist count from API
        fetchWishlistCount().then(count => {
            console.log('Wishlist count from API:', count);
            // Always show wishlist count (like cart count)
            wishlistCount.style.display = 'flex';
            animateCounter(wishlistCount, count);
            // Style differently if count is 0
            if (count === 0) {
                wishlistCount.style.opacity = '0.6';
            } else {
                wishlistCount.style.opacity = '1';
            }
        }).catch(error => {
            console.warn('Error fetching wishlist count:', error);
            // Fallback to localStorage
            const count = localStorage.getItem('wishlistCount') || 0;
            console.log('Using fallback wishlist count:', count);
            wishlistCount.style.display = 'flex';
            animateCounter(wishlistCount, count);
            // Style differently if count is 0
            if (count === 0) {
                wishlistCount.style.opacity = '0.6';
            } else {
                wishlistCount.style.opacity = '1';
            }
        });
    } else {
        console.warn('Wishlist count element not found');
    }
}

function animateCounter(element, newValue) {
    const currentValue = parseInt(element.textContent) || 0;
    if (currentValue !== newValue) {
        element.style.animation = 'pulse 0.3s ease-in-out';
        setTimeout(() => {
            element.textContent = newValue;
            element.style.animation = '';
        }, 150);
    }
}

// Setup smooth animations
function setupAnimations() {
    // Add intersection observer for navbar animations
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'fadeInUp 0.6s ease-out';
            }
        });
    }, observerOptions);

    // Observe navbar elements
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        observer.observe(link);
    });
}

// Set Active Page
function setActivePage() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });
}

// Global search function
function performSearch(query) {
    if (!query || query.trim() === '') {
        console.log('Empty search query');
        return;
    }
    
    console.log('Performing search for:', query);
    
    // Redirect to artwork page with search parameter
    const searchUrl = `artwork.php?search=${encodeURIComponent(query.trim())}`;
    window.location.href = searchUrl;
}

// Make performSearch available globally
window.performSearch = performSearch;

// Export functions for use in other scripts
window.NavbarController = {
    simulateLogin,
    logout,
    updateCartCount,
    updateWishlistCount,
    performSearch
};

function closeMobileSearch() {
    const overlay = document.querySelector('.mobile-search-overlay');
    
    if (overlay) {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// User Dropdown
function setupUserDropdown() {
    const userAccount = document.getElementById('user-account');
    const userMenu = document.getElementById('user-menu');
    const userDropdown = document.querySelector('.user-dropdown');
    let hideTimeout;

    if (userAccount && userMenu) {
        // Enhanced hover effects with smooth animations
        userAccount.parentElement.addEventListener('mouseenter', function() {
            clearTimeout(hideTimeout);
            userMenu.style.display = 'block';
            setTimeout(() => {
                userMenu.classList.add('show');
            }, 10);
        });

        userAccount.parentElement.addEventListener('mouseleave', function() {
            userMenu.classList.remove('show');
            hideTimeout = setTimeout(() => {
                userMenu.style.display = 'none';
            }, 300);
        });

        // Add click handler for mobile - only prevent default on the account button, not dropdown items
        userAccount.addEventListener('click', function(e) {
            e.preventDefault();
            if (window.innerWidth <= 768) {
                userMenu.classList.toggle('show');
            }
        });

        // Close dropdown when clicking outside - but allow clicks inside dropdown
        document.addEventListener('click', function(e) {
            if (userDropdown && !userDropdown.contains(e.target)) {
                userMenu.style.display = 'none';
                userMenu.classList.remove('show');
            }
        });

        // Allow dropdown item links to work properly
        const dropdownItems = userMenu.querySelectorAll('.dropdown-item');
        dropdownItems.forEach(item => {
            item.addEventListener('click', function(e) {
                // Don't prevent default - let the link work normally
                // Just close the dropdown after a short delay
                setTimeout(() => {
                    userMenu.style.display = 'none';
                    userMenu.classList.remove('show');
                }, 100);
            });
        });
    }
}

// Navbar Scroll Effects
function setupScrollEffects() {
    const navbar = document.querySelector('.navbar');
    let lastScrollY = window.scrollY;

    if (navbar) {
        window.addEventListener('scroll', function() {
            const currentScrollY = window.scrollY;
            
            // Add/remove background based on scroll position
            if (currentScrollY > 50) {
                navbar.style.background = 'rgba(250, 248, 243, 0.95)';
                navbar.style.boxShadow = '0 4px 30px rgba(107, 68, 35, 0.15)';
            } else {
                navbar.style.background = 'rgba(250, 248, 243, 0.98)';
                navbar.style.boxShadow = '0 4px 30px rgba(107, 68, 35, 0.1)';
            }

            // Hide/show navbar on scroll (optional)
            // Uncomment the following lines if you want auto-hide functionality
            /*
            if (currentScrollY > lastScrollY && currentScrollY > 200) {
                navbar.style.transform = 'translateY(-100%)';
            } else {
                navbar.style.transform = 'translateY(0)';
            }
            */
            
            lastScrollY = currentScrollY;
        });
    }
}

// Active Page Detection
function setActivePage() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        
        // Check if the link's href matches the current path
        const linkPath = new URL(link.href).pathname;
        if (linkPath === currentPath || 
            (linkPath !== '/' && currentPath.includes(linkPath))) {
            link.classList.add('active');
        }
    });
    
    // If no link matches, set home as active for root path
    if (currentPath === '/' || currentPath === '/index.html') {
        const homeLink = document.querySelector('.nav-link[href*="index"], .nav-link[href="/"], .nav-link[href="#home"]');
        if (homeLink) {
            homeLink.classList.add('active');
        }
    }
}

// Utility Functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Smooth scrolling for anchor links
function setupSmoothScroll() {
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            
            // Skip if targetId is empty, just '#', or doesn't have proper format
            if (!targetId || targetId === '#' || targetId.length <= 1) {
                return;
            }
            
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                e.preventDefault();
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Initialize smooth scrolling
setupSmoothScroll();

// Export functions for external use
window.YadawityNavbar = {
    updateCartCount,
    updateWishlistCount,
    setActivePage,
    performSearch
};

// Make login simulation functions available globally for testing
window.NavbarController = {
    simulateLogin,
    logout,
    updateCartCount,
    updateWishlistCount,
    performSearch
};

// TESTING: Add a function to test artist login
function testArtistLogin() {
    console.log('Testing artist login...');
    currentUser = {
        name: 'Test Artist',
        role: 'artist',
        isLoggedIn: true,
        userId: 123,
        isVerified: true // Test with verified artist
    };
    localStorage.setItem('currentUser', JSON.stringify(currentUser));
    updateUserInterface();
    console.log('Artist login test completed. Check dropdown menu.');
}

// Add function to test unverified artist
function testUnverifiedArtistLogin() {
    console.log('Testing unverified artist login...');
    currentUser = {
        name: 'Unverified Artist',
        role: 'artist',
        isLoggedIn: true,
        userId: 124,
        isVerified: false // Test with unverified artist
    };
    localStorage.setItem('currentUser', JSON.stringify(currentUser));
    updateUserInterface();
    console.log('Unverified artist login test completed. Should show regular dropdown only.');
}

// Make test function available globally
window.testArtistLogin = testArtistLogin;
window.testUnverifiedArtistLogin = testUnverifiedArtistLogin;

// TESTING: Remove this in production - no auto login simulation
// setTimeout(() => {
//     simulateLogin('artist');
// }, 1000);

// Handle resize events
window.addEventListener('resize', debounce(function() {
    // Close mobile menu on resize to larger screen
    if (window.innerWidth > 992) {
        const navMenu = document.querySelector('.nav-menu');
        const navToggle = document.querySelector('.nav-toggle');
        
        if (navMenu) navMenu.classList.remove('active');
        if (navToggle) navToggle.classList.remove('active');
    }
}, 250));

// Global function to refresh all counters (can be called from other pages)
window.refreshCounters = function() {
    // Update navbar counters
    updateCartCount();
    updateWishlistCount();
    
    // Update burger menu counters if available
    if (window.burgerMenuInstance && typeof window.burgerMenuInstance.updateCounters === 'function') {
        window.burgerMenuInstance.updateCounters();
    }
};

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { updateCartCount, updateWishlistCount, checkAuthentication, refreshCounters };
}
