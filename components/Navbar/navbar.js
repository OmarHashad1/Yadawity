/**
 * CART AND WISHLIST COUNT IMPLEMENTATION SUMMARY:
 * 
 * 1. The getCart.php API now uses checkCredentials.php to authenticate users
 * 2. The getWishlist.php API also uses checkCredentials.php for authentication
 * 3. For unauthenticated users (guests), both APIs will return an error, and the navbar shows 0
 * 4. For authenticated users, the APIs return the actual cart/wishlist count
 * 5. The navbar.js functions are simplified - they just call the APIs and show the results
 * 6. localStorage is no longer used for cart/wishlist counts
 * 7. Global functions refreshNavbarCounters(), updateCartCount(), and updateWishlistCount() 
 *    are available for other parts of the application to refresh counts
 */

// Yadawity Modern Navbar Component JavaScript

// Throttling variables to prevent rapid successive updates
let cartUpdateTimeout = null;
let wishlistUpdateTimeout = null;

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
        if (event.detail.action === 'logout') {
            currentUser = event.detail.user;
            updateUserInterface();
            forceHideArtistSections();
        }
    });
    
        // Also check authentication when page becomes visible (after redirects)
    document.addEventListener('visibilitychange', async function() {
        if (!document.hidden) {
            await loadUserData();
        }
    });
    
    // Check authentication again after a short delay for redirects from login
    setTimeout(async () => {
        await checkRealAuthenticationStatus();
        updateUserInterface();
    }, 500);
});

// Additional event listener for when page is fully loaded (including images, etc.)
window.addEventListener('load', function() {
    setTimeout(() => {
        refreshNavbarCounters();
    }, 200);
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
    
    // Force update counters after initialization
    setTimeout(() => {
        updateCartCount();
        updateWishlistCount();
    }, 100);
    
    // Force update counters again after a longer delay
    setTimeout(() => {
        updateCartCount();
        updateWishlistCount();
    }, 1000);
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
    
    updateUserInterface();
    
    // Remove old artist section logic since we now handle everything in dropdown
}

// Check real authentication status from server/cookies using checkCredentials API
async function checkRealAuthenticationStatus() {
    try {
        // Always try the API call first since cookies are being passed automatically
        const response = await fetch('./API/checkCredentials.php', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        
        if (response.ok) {
            const responseText = await response.text();
            
            try {
                const userData = JSON.parse(responseText);
                    
                    // Handle checkCredentials API response format
                    if (userData.authenticated === true) {
                        // Extract user information from the API response
                        const userName = userData.user_name || 'User';
                        const userType = userData.user_type || 'guest';
                        const userId = userData.user_id;
                        const userEmail = userData.user_email;
                        const isVerified = userData.is_verified || false;
                        
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
                        
                        // Force UI update immediately after setting currentUser
                        setTimeout(() => {
                            updateUserInterface();
                        }, 100);
                        
                        return true; // Successfully authenticated
                    } else {
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
                    // Error parsing API response
                }
            } else {
                const errorText = await response.text();
                
                // Set user as guest if API fails
                currentUser = {
                    name: 'Guest User',
                    role: 'guest',
                    isLoggedIn: false
                };
                updateUserInterface();
            }
    } catch (error) {
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
    
    if (currentUser.isLoggedIn) {
        // User is logged in - update user info
        if (userName) {
            userName.textContent = currentUser.name;
        }
        if (userRole) {
            userRole.textContent = currentUser.role.toUpperCase();
        }
        
        // Update user profile photos
        updateUserProfilePhoto(currentUser.userId);
        
        // Update login/logout button to logout
        if (loginLogoutBtn) {
            loginLogoutBtn.innerHTML = '<i class="fas fa-sign-out-alt"></i><span>Logout</span>';
            loginLogoutBtn.onclick = logout;
            loginLogoutBtn.href = '#';
        }
        
        // Show/hide sections based on user role and verification status
        if (currentUser.role === 'artist') {
            
            if (currentUser.isVerified) {
                // Show artist section for verified artists
                if (artistSection) {
                    artistSection.style.display = 'block';
                    artistSection.style.visibility = 'visible';
                }
            } else {
                // Hide artist section for unverified artists
                if (artistSection) {
                    artistSection.style.display = 'none';
                }
            }
            
            // Show general dropdown section for all artists (verified or not)
            if (generalDropdownSection) {
                generalDropdownSection.style.display = 'block';
            }
        } else if (currentUser.role === 'buyer' || currentUser.role === 'admin') {
            // Hide artist section for buyers and admins
            if (artistSection) {
                artistSection.style.display = 'none';
            }
            // Show general dropdown section for buyers and admins
            if (generalDropdownSection) {
                generalDropdownSection.style.display = 'block';
            }
        } else {
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
        
        // User is not logged in (guest)
        if (userName) {
            userName.textContent = 'Guest User';
        }
        if (userRole) {
            userRole.textContent = 'VISITOR';
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
        }
        
        // Hide artist section for guests
        if (artistSection) {
            artistSection.style.display = 'none';
        }
        
        // Show general dropdown section for guests (they can still access profile, support, etc.)
        if (generalDropdownSection) {
            generalDropdownSection.style.display = 'block';
        }
    }
    
    // Update cart and wishlist counts
    updateCartCount();
    updateWishlistCount();
    
    
    // Trigger burger menu update if available
    if (window.burgerMenuInstance && typeof window.burgerMenuInstance.updateUserInterface === 'function') {
        window.burgerMenuInstance.updateUserInterface();
    }
}

// Function to update user profile photo
async function updateUserProfilePhoto(userId) {
    if (!userId) {
        resetToDefaultIcons();
        return;
    }
    
    try {
        // Get the correct profile photo URL from the API
        const response = await fetch('./API/getUserProfilePhoto.php', {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            
            if (data.success && data.profile_photo_url) {
                const photoUrl = data.profile_photo_url;
                const timestamp = Date.now(); // Cache busting
                const fullPhotoUrl = `${photoUrl}?t=${timestamp}`;
                
                // Test if image loads before updating UI
                const img = new Image();
                img.onload = function() {
                    
                    // Update main user account button with profile photo
                    const userAccountBtn = document.getElementById('user-account');
                    if (userAccountBtn) {
                        userAccountBtn.innerHTML = `<img src="${fullPhotoUrl}" alt="Profile Photo" class="user-profile-photo" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; position: absolute; top: 0; left: 0;">`;
                        userAccountBtn.style.position = 'relative';
                        userAccountBtn.style.overflow = 'hidden';
                        userAccountBtn.style.borderRadius = '50%';
                        userAccountBtn.style.width = '50px';
                        userAccountBtn.style.height = '50px';
                        userAccountBtn.style.minWidth = '50px';
                        userAccountBtn.style.minHeight = '50px';
                    }
                    
                    // Update dropdown avatar with profile photo
                    const userAvatar = document.querySelector('.user-avatar');
                    if (userAvatar) {
                        userAvatar.innerHTML = `<img src="${fullPhotoUrl}" alt="Profile Photo" class="user-avatar-photo" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; position: absolute; top: 0; left: 0;">`;
                        userAvatar.style.position = 'relative';
                        userAvatar.style.overflow = 'hidden';
                        userAvatar.style.borderRadius = '50%';
                        userAvatar.style.width = '60px';
                        userAvatar.style.height = '60px';
                        userAvatar.style.minWidth = '60px';
                        userAvatar.style.minHeight = '60px';
                    }
                };
                
                img.onerror = function() {
                    console.error('Failed to load profile photo:', fullPhotoUrl);
                    resetToDefaultIcons();
                };
                
                img.src = fullPhotoUrl;
                return;
                
                } else {
                    // Check if this is a quiet failure (expected for unauthenticated users)
                    if (!data.quiet) {
                        // No profile photo available
                    }
                    resetToDefaultIcons();
                }
            } else {
                console.error('Failed to fetch profile photo');
                resetToDefaultIcons();
            }    } catch (error) {
        console.error('Error updating profile photo:', error);
        resetToDefaultIcons();
    }
}

// Function to reset to default icons
function resetToDefaultIcons() {
    
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
        } else {
        }
        
        // Force page reload to ensure clean state
        setTimeout(() => {
            window.location.reload();
        }, 100);
        
    } catch (error) {
        
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
    // Add a small delay to ensure DOM is ready
    setTimeout(() => {
        updateCartCount();
        updateWishlistCount();
    }, 100);
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
            if (data.success && data.data && typeof data.data.total_items !== 'undefined') {
                return data.data.total_items;
            }
        } else {
            // Cart API failed
        }
        
        // If API call fails or user not authenticated, return 0
        return 0;
    } catch (error) {
        console.error('Cart API error:', error); // Debug log
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
        return 0;
    }
}

function updateCartCount() {
    // Clear any existing timeout to prevent rapid successive calls
    if (cartUpdateTimeout) {
        clearTimeout(cartUpdateTimeout);
    }
    
    cartUpdateTimeout = setTimeout(() => {
        const cartCount = document.getElementById('cart-count');
        
        if (cartCount) {
            // Ensure the element is visible immediately
            cartCount.style.display = 'flex';
            cartCount.style.visibility = 'visible';
            
            // Only set "0" if there's no existing content to prevent flickering
            if (!cartCount.textContent || cartCount.textContent.trim() === '') {
                cartCount.textContent = '0';
            }
            
            // Fetch dynamic cart count from API (authentication handled by API)
            fetchCartCount().then(count => {
                animateCounter(cartCount, count);
            }).catch(error => {
                console.error('Error fetching cart count:', error);
                // Keep showing 0 on error
                animateCounter(cartCount, 0);
            });
        } else {
            console.error('cart-count element not found');
        }
        cartUpdateTimeout = null;
    }, 100); // 100ms throttle
}

function updateWishlistCount() {
    // Clear any existing timeout to prevent rapid successive calls
    if (wishlistUpdateTimeout) {
        clearTimeout(wishlistUpdateTimeout);
    }
    
    wishlistUpdateTimeout = setTimeout(() => {
        const wishlistCount = document.getElementById('wishlist-count');
        
        if (wishlistCount) {
            // Ensure the element is visible immediately
            wishlistCount.style.display = 'flex';
            wishlistCount.style.visibility = 'visible';
            
            // Only set "0" if there's no existing content to prevent flickering
            if (!wishlistCount.textContent || wishlistCount.textContent.trim() === '') {
                wishlistCount.textContent = '0';
            }
            
            // Fetch dynamic wishlist count from API (authentication handled by API)
            fetchWishlistCount().then(count => {
                // Always show wishlist count
                wishlistCount.style.display = 'flex';
                animateCounter(wishlistCount, count);
                // Style differently if count is 0
                if (count === 0) {
                    wishlistCount.style.opacity = '0.6';
                } else {
                    wishlistCount.style.opacity = '1';
                }
            }).catch(error => {
                console.error('Error fetching wishlist count:', error);
                // Keep showing 0 on error
                wishlistCount.style.display = 'flex';
                animateCounter(wishlistCount, 0);
                wishlistCount.style.opacity = '0.6';
            });
        } else {
            console.error('wishlist-count element not found');
        }
        wishlistUpdateTimeout = null;
    }, 100); // 100ms throttle
}

function animateCounter(element, newValue) {
    const currentValue = parseInt(element.textContent) || 0;
    if (currentValue !== newValue) {
        element.style.animation = 'pulse 0.3s ease-in-out';
        setTimeout(() => {
            element.textContent = newValue;
            element.style.animation = '';
        }, 150);
    } else {
        // Even if same value, update it to make sure
        element.textContent = newValue;
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
        return;
    }
    
    
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

        // Handle dropdown item navigation based on user role
        const dropdownItems = userMenu.querySelectorAll('.dropdown-item');
        dropdownItems.forEach(item => {
            item.addEventListener('click', function(e) {
                const href = item.getAttribute('href');
                const itemText = item.querySelector('span').textContent.trim();
                
                // Handle navigation based on user role and item type
                if (handleDropdownNavigation(e, href, itemText)) {
                    // Navigation was handled, close dropdown
                    setTimeout(() => {
                        userMenu.style.display = 'none';
                        userMenu.classList.remove('show');
                    }, 100);
                }
            });
        });
    }
}

// Handle dropdown navigation based on user role
function handleDropdownNavigation(event, href, itemText) {
    const currentUser = window.currentUser || { isLoggedIn: false, role: 'guest' };
    
    // Support is always allowed for everyone
    if (itemText === 'Support') {
        return false; // Let default navigation happen
    }
    
    // If user is not logged in (guest), redirect to login for all items except Support
    if (!currentUser.isLoggedIn || currentUser.role === 'guest' || currentUser.role === 'visitor') {
        event.preventDefault();
        window.location.href = 'login.php';
        return true; // Navigation was handled
    }
    
    // Handle logged-in user navigation based on role
    if (currentUser.isLoggedIn) {
        switch (itemText) {
            case 'My Profile':
                event.preventDefault();
                window.location.href = 'profile.php';
                return true;
                
            case 'My Orders':
                event.preventDefault();
                if (currentUser.role === 'buyer' || currentUser.role === 'admin') {
                    window.location.href = 'profile.php';
                } else if (currentUser.role === 'artist') {
                    window.location.href = 'profile.php';
                }
                return true;
                
            // Artist Portal items - keep as they are
            case 'Artist Dashboard':
            case 'Manage Profile':
            case 'User Reviews':
                return false; // Let default navigation happen
                
            default:
                return false; // Let default navigation happen
        }
    }
    
    return false; // Let default navigation happen
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
    currentUser = {
        name: 'Test Artist',
        role: 'artist',
        isLoggedIn: true,
        userId: 123,
        isVerified: true // Test with verified artist
    };
    localStorage.setItem('currentUser', JSON.stringify(currentUser));
    updateUserInterface();
}

// Add function to test unverified artist
function testUnverifiedArtistLogin() {
    currentUser = {
        name: 'Unverified Artist',
        role: 'artist',
        isLoggedIn: true,
        userId: 124,
        isVerified: false // Test with unverified artist
    };
    localStorage.setItem('currentUser', JSON.stringify(currentUser));
    updateUserInterface();
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

// Global function to refresh cart and wishlist counters
// Can be called from other parts of the application when items are added/removed
window.refreshNavbarCounters = function() {
    updateCartCount();
    updateWishlistCount();
};

// Export counter functions globally for external access
window.updateCartCount = updateCartCount;
window.updateWishlistCount = updateWishlistCount;

// Test function to manually update cart count - for debugging
window.testCartCount = function() {
    updateCartCount();
};

// Test function to manually set cart count - for debugging  
window.setCartCount = function(count) {
    const cartCount = document.getElementById('cart-count');
    if (cartCount) {
        cartCount.style.display = 'flex';
        cartCount.textContent = count;
    }
};

// Test function for debugging - can be called from browser console
window.testNavbarCounters = function() {
    
    const cartCount = document.getElementById('cart-count');
    const wishlistCount = document.getElementById('wishlist-count');
    
    updateCartCount();
    updateWishlistCount();
};
