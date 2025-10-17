/**
 * Burger Menu Component JavaScript - HTML5 Semantic Version
 * Handles burger menu functionality, interactions, and animations
 * Exact same functionality as navbar with HTML5 compliance
 */

class BurgerMenu {
    constructor() {
        this.overlay = null;
        this.container = null;
        this.closeBtn = null;
        this.userDropdown = null;
        this.userMenu = null;
        this.searchInput = null;
        this.isOpen = false;
        this.lastUserState = null; // Track last user state to prevent unnecessary updates
        this.iconsReset = false; // Track if icons have been reset to prevent repeated resets
        
        this.init();
    }

    init() {
        this.bindElements();
        this.bindEvents();
        this.updateActiveNavLink();
        this.updateCounters();
        this.updateUserInterface();
        
        // Set up periodic sync with main navbar
        this.setupUserSync();
    }

    setupUserSync() {
        // Listen for user change events from navbar/demo panel
        window.addEventListener('userUpdated', () => {
            this.refreshUserInterface();
        });
        
        // Also listen for storage changes from other tabs/windows
        window.addEventListener('storage', (e) => {
            if (e.key === 'currentUser') {
                this.updateUserInterface();
            }
        });
        
        // Fallback: Check for user changes every 5 seconds for sync
        setInterval(() => {
            const navbarUser = this.getUserFromNavbar();
            const currentUser = this.getUserFromStorage();
            
            // If navbar has different user info, update burger menu
            if (navbarUser && (
                navbarUser.name !== currentUser.name || 
                navbarUser.role !== currentUser.role ||
                navbarUser.isLoggedIn !== currentUser.isLoggedIn
            )) {
                // Update localStorage with navbar data
                localStorage.setItem('currentUser', JSON.stringify(navbarUser));
                // Refresh burger menu interface
                this.updateUserInterface();
            }
        }, 5000); // Increased to 5 seconds to reduce console spam
    }

    bindElements() {
        // Updated selectors for HTML5 semantic structure
        this.overlay = document.getElementById('burger-menu-overlay');
        this.container = document.querySelector('.burger-menu-container');
        this.closeBtn = document.getElementById('burger-menu-close');
        this.userDropdown = document.querySelector('.burger-user-dropdown');
        this.userMenu = document.getElementById('burger-user-dropdown-menu');
        this.searchInput = document.getElementById('burger-search-input');
    }

    bindEvents() {
        // Close button
        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', () => this.close());
        }

        // Overlay click to close
        if (this.overlay) {
            this.overlay.addEventListener('click', (e) => {
                if (e.target === this.overlay) {
                    this.close();
                }
            });
        }

        // User dropdown toggle - Updated selector
        if (this.userDropdown) {
            const userAccount = document.getElementById('burger-user-account-btn');
            if (userAccount) {
                userAccount.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.toggleUserDropdown();
                });
            }
        }

        // Search functionality
        this.bindSearchEvents();

        // Escape key to close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });

        // Navigation link clicks
        this.bindNavigationEvents();
        
        // Dropdown item navigation
        this.bindDropdownEvents();
        
        // Close user dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (this.userDropdown && this.userDropdown.classList.contains('active')) {
                const userAccountBtn = document.getElementById('burger-user-account-btn');
                if (!this.userDropdown.contains(e.target) && e.target !== userAccountBtn) {
                    this.userDropdown.classList.remove('active');
                    if (userAccountBtn && this.userMenu) {
                        userAccountBtn.setAttribute('aria-expanded', 'false');
                        this.userMenu.setAttribute('aria-hidden', 'true');
                    }
                }
            }
        });
    }

    bindSearchEvents() {
        const searchBtn = document.getElementById('burger-search-btn');
        
        if (searchBtn) {
            searchBtn.addEventListener('click', () => this.performSearch());
        }

        if (this.searchInput) {
            this.searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.performSearch();
                }
            });

            this.searchInput.addEventListener('input', (e) => {
                this.handleSearchInput(e.target.value);
            });
        }
    }

    bindNavigationEvents() {
        const navLinks = document.querySelectorAll('.burger-nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                // Add loading state
                this.showLoading(link);
                // Close menu after short delay
                setTimeout(() => this.close(), 300);
            });
        });
    }

    bindDropdownEvents() {
        const dropdownItems = document.querySelectorAll('.burger-dropdown-item');
        dropdownItems.forEach(item => {
            item.addEventListener('click', (e) => {
                const href = item.getAttribute('href');
                const itemText = item.querySelector('span').textContent.trim();
                
                // Handle navigation based on user role and item type
                if (this.handleBurgerDropdownNavigation(e, href, itemText)) {
                    // Navigation was handled, close menu
                    setTimeout(() => this.close(), 300);
                }
            });
        });
    }

    open() {
        if (this.overlay) {
            this.isOpen = true;
            this.overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Focus management removed - no automatic focus on search input
        }
    }

    close() {
        if (this.overlay) {
            this.isOpen = false;
            this.overlay.classList.remove('active');
            document.body.style.overflow = '';
            
            // Close user dropdown if open
            if (this.userDropdown) {
                this.userDropdown.classList.remove('active');
            }
            
            // Reset nav-toggle state when burger menu closes
            const navToggle = document.querySelector('.nav-toggle');
            if (navToggle) {
                navToggle.classList.remove('active');
            }
        }
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    toggleUserDropdown() {
        if (this.userDropdown) {
            this.userDropdown.classList.toggle('active');
            
            // Toggle aria attributes for accessibility
            const userAccountBtn = document.getElementById('burger-user-account-btn');
            if (userAccountBtn && this.userMenu) {
                const isExpanded = this.userDropdown.classList.contains('active');
                userAccountBtn.setAttribute('aria-expanded', isExpanded);
                this.userMenu.setAttribute('aria-hidden', !isExpanded);
            }
        }
    }

    performSearch() {
        if (this.searchInput) {
            const query = this.searchInput.value.trim();
            if (query) {
                // Implement your search logic here
                // Example: window.location.href = `search.html?q=${encodeURIComponent(query)}`;
                
                // Show search feedback
                this.showSearchFeedback('Searching...');
                
                // Close menu after search
                setTimeout(() => this.close(), 500);
            }
        }
    }

    handleSearchInput(value) {
        // Implement search suggestions or live search here
        if (value.length > 2) {
            // Show search suggestions
        }
    }

    showSearchFeedback(message) {
        // Create temporary feedback element
        const feedback = document.createElement('div');
        feedback.textContent = message;
        feedback.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--primaryBrown);
            color: white;
            padding: 8px 12px;
            border-radius: 0 0 8px 8px;
            font-size: 0.8rem;
            text-align: center;
            z-index: 10;
        `;
        
        const searchContainer = document.querySelector('.burger-search-container');
        if (searchContainer) {
            searchContainer.style.position = 'relative';
            searchContainer.appendChild(feedback);
            
            setTimeout(() => {
                if (feedback.parentNode) {
                    feedback.parentNode.removeChild(feedback);
                }
            }, 2000);
        }
    }

    showLoading(element) {
        const originalText = element.textContent;
        element.textContent = 'Loading...';
        element.style.opacity = '0.7';
        
        setTimeout(() => {
            element.textContent = originalText;
            element.style.opacity = '1';
        }, 1000);
    }

    // Handle dropdown navigation based on user role
    handleBurgerDropdownNavigation(event, href, itemText) {
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

    updateUserInterface() {
        // Get the most current user data by prioritizing navbar over localStorage
        const navbarUser = this.getUserFromNavbar();
        const storageUser = this.getUserFromStorage();
        
        // Use navbar data if available, otherwise use storage data
        let currentUser = navbarUser || storageUser;
        
        // If navbar and storage differ, sync them
        if (navbarUser && storageUser && (
            navbarUser.name !== storageUser.name || 
            navbarUser.role !== storageUser.role ||
            navbarUser.isLoggedIn !== storageUser.isLoggedIn
        )) {
            localStorage.setItem('currentUser', JSON.stringify(navbarUser));
            currentUser = navbarUser;
        }
        
        // Check if user state has actually changed to prevent unnecessary updates
        const userStateKey = `${currentUser.isLoggedIn}-${currentUser.userId}-${currentUser.name}-${currentUser.role}`;
        if (this.lastUserState === userStateKey) {
            return; // No changes, skip update
        }
        this.lastUserState = userStateKey;
        
        // Update UI elements
        const userNameElement = document.getElementById('burger-user-name');
        const userRoleElement = document.getElementById('burger-user-role');
        const artistSection = document.getElementById('burger-artist-section');
        const loginLogout = document.getElementById('burger-login-logout');
        const userAccountBtn = document.getElementById('burger-user-account-btn');
        const userAvatar = document.querySelector('.burger-user-avatar');
        
        // Update user info in dropdown header
        if (userNameElement) {
            userNameElement.textContent = currentUser.name;
        }
        
        if (userRoleElement) {
            userRoleElement.textContent = currentUser.role.charAt(0).toUpperCase() + currentUser.role.slice(1);
        }
        
        // Update user profile photos if logged in
        if (currentUser.isLoggedIn && currentUser.userId) {
            this.updateBurgerProfilePhoto(currentUser.userId);
            this.iconsReset = false; // Reset flag when user is logged in
        } else if (!this.iconsReset) {
            this.resetBurgerToDefaultIcons();
            this.iconsReset = true; // Set flag to prevent repeated resets
        }
        
        // Show/hide artist section based on role
        if (artistSection) {
            if (currentUser.role === 'artist' && currentUser.isLoggedIn) {
                artistSection.style.display = 'block';
            } else {
                artistSection.style.display = 'none';
            }
        }
        
        // Update login/logout button
        if (loginLogout) {
            if (currentUser.isLoggedIn) {
                loginLogout.innerHTML = '<i class="fas fa-sign-out-alt"></i><span>Logout</span>';
                loginLogout.href = '#';
                loginLogout.onclick = (e) => {
                    e.preventDefault();
                    this.logout();
                };
            } else {
                loginLogout.innerHTML = '<i class="fas fa-sign-in-alt"></i><span>Login</span>';
                loginLogout.href = 'login.php';
                loginLogout.onclick = function(e) {
                    e.preventDefault();
                    window.location.href = 'login.php';
                };
            }
        }
    }

    getUserFromNavbar() {
        // Try to get user info from the navbar's global currentUser variable first
        if (window.currentUser) {
            return window.currentUser;
        }
        
        // Fallback: Try to get user info from main navbar elements using class selectors
        const navbarUserName = document.querySelector('.user-name');
        const navbarUserRole = document.querySelector('.user-role');
        
        if (navbarUserName && navbarUserRole) {
            const name = navbarUserName.textContent.trim();
            const role = navbarUserRole.textContent.trim().toLowerCase();
            
            // Check if user is actually logged in (not showing default "Guest User")
            if (name && name !== 'Guest User' && name !== '') {
                return {
                    name: name,
                    role: role,
                    isLoggedIn: true
                };
            } else if (name === 'Guest User') {
                return {
                    name: 'Guest User',
                    role: 'visitor',
                    isLoggedIn: false
                };
            }
        }
        
        return null;
    }

    getUserFromStorage() {
        // Load user data from localStorage or use default
        let currentUser = {
            name: 'Guest User',
            role: 'visitor',
            isLoggedIn: false
        };
        
        const savedUser = localStorage.getItem('currentUser');
        if (savedUser) {
            try {
                currentUser = JSON.parse(savedUser);
            } catch (e) {
                console.warn('Invalid user data in localStorage');
            }
        }
        
        return currentUser;
    }

    // Method to refresh user interface (call this when user changes)
    refreshUserInterface() {
        this.updateUserInterface();
        this.updateCounters(); // This is now async but we don't need to await it
    }

    logout() {
        // Update localStorage
        const currentUser = {
            name: 'Guest User',
            role: 'visitor',
            isLoggedIn: false
        };
        
        localStorage.setItem('currentUser', JSON.stringify(currentUser));
        
        // Update interface
        this.updateUserInterface();
        
        // Close burger menu
        this.close();
        
        // Show notification (if available)
        if (typeof showNotification === 'function') {
            showNotification('Logged out successfully', 'info');
        }
        
        // Redirect to home page
        setTimeout(() => {
            window.location.href = 'index.html';
        }, 1000);
    }

    updateActiveNavLink() {
        const currentPage = window.location.pathname.split('/').pop() || 'index.html';
        const navLinks = document.querySelectorAll('.burger-nav-link');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href === currentPage || (currentPage === '' && href === 'index.html')) {
                link.classList.add('active');
                link.style.background = 'rgba(107, 68, 35, 0.15)';
                link.style.borderColor = 'var(--primary-brown)';
            } else {
                link.classList.remove('active');
                link.style.background = '';
                link.style.borderColor = '';
            }
        });
    }

    async updateCounters() {
        // Update cart counter - Updated selectors
        try {
            const cartCount = await this.getCartCount();
            const cartCountElement = document.getElementById('burger-cart-count');
            if (cartCountElement) {
                cartCountElement.textContent = cartCount;
                // Always show cart count even if 0, but style differently
                cartCountElement.style.display = 'flex';
                if (cartCount === 0) {
                    cartCountElement.style.opacity = '0.5';
                } else {
                    cartCountElement.style.opacity = '1';
                }
            }
        } catch (error) {
            console.error('Error updating cart count:', error);
        }

        // Update wishlist counter - Updated selectors
        try {
            const wishlistCount = await this.getWishlistCount();
            const wishlistCountElement = document.getElementById('burger-wishlist-count');
            if (wishlistCountElement) {
                wishlistCountElement.textContent = wishlistCount;
                // Always show wishlist count even if 0, but style differently
                wishlistCountElement.style.display = 'flex';
                if (wishlistCount === 0) {
                    wishlistCountElement.style.opacity = '0.5';
                } else {
                    wishlistCountElement.style.opacity = '1';
                }
            }
        } catch (error) {
            console.error('Error updating wishlist count:', error);
        }
    }

    async getCartCount() {
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

    async getWishlistCount() {
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

    updateLoginStatus() {
        const loginLogout = document.getElementById('burger-login-logout');
        const isLoggedIn = this.checkLoginStatus();
        
        if (loginLogout) {
            if (isLoggedIn) {
                loginLogout.innerHTML = '<i class="fas fa-sign-out-alt"></i><span>Logout</span>';
                loginLogout.href = '#logout';
            } else {
                loginLogout.innerHTML = '<i class="fas fa-sign-in-alt"></i><span>Login</span>';
                loginLogout.href = 'login.html';
            }
        }
    }

    checkLoginStatus() {
        // Implement your login status check here
        // Example: return !!localStorage.getItem('user-token');
        return false; // Placeholder
    }

    // Removed profile photo functionality - keeping original burger menu format
    // All burger menu elements will use default icons and text as designed

    // Update burger menu profile photo
    async updateBurgerProfilePhoto(userId) {
        if (!userId) {
            this.resetBurgerToDefaultIcons();
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
                    img.onload = () => {
                        
                        // Update burger menu user account button with profile photo
                        const burgerUserAccountBtn = document.getElementById('burger-user-account-btn');
                        if (burgerUserAccountBtn) {
                            const userIcon = burgerUserAccountBtn.querySelector('.fas.fa-user');
                            if (userIcon) {
                                userIcon.outerHTML = `<img src="${fullPhotoUrl}" alt="Profile Photo" class="burger-user-profile-photo" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover; margin-right: 10px;">`;
                            }
                        }
                        
                        // Update dropdown profile icon with profile photo
                        const dropdownProfileIcon = document.querySelector('.burgerDropdownItem .fas.fa-user-circle');
                        if (dropdownProfileIcon) {
                            dropdownProfileIcon.outerHTML = `<img src="${fullPhotoUrl}" alt="Profile Photo" class="burger-dropdown-profile-photo" style="width: 20px; height: 20px; border-radius: 50%; object-fit: cover; margin-right: 10px;">`;
                        }
                    };
                    
                    img.onerror = () => {
                        this.resetBurgerToDefaultIcons();
                    };
                    
                    img.src = fullPhotoUrl;
                    return;
                    
                } else {
                    // Check if this is a quiet failure (expected for unauthenticated users)
                    if (!data.quiet) {
                        // No profile photo available for burger menu
                    }
                    this.resetBurgerToDefaultIcons();
                }
            } else {
                this.resetBurgerToDefaultIcons();
            }
            
        } catch (error) {
            this.resetBurgerToDefaultIcons();
        }
    }

    // Reset burger menu to default icons
    resetBurgerToDefaultIcons() {
        
        // Reset burger menu user account button to default icon
        const burgerUserAccountBtn = document.getElementById('burger-user-account-btn');
        if (burgerUserAccountBtn) {
            const profilePhoto = burgerUserAccountBtn.querySelector('.burger-user-profile-photo');
            if (profilePhoto) {
                profilePhoto.outerHTML = '<i class="fas fa-user"></i>';
            }
        }
        
        // Reset dropdown profile icon to default
        const dropdownProfilePhoto = document.querySelector('.burger-dropdown-profile-photo');
        if (dropdownProfilePhoto) {
            dropdownProfilePhoto.outerHTML = '<i class="fas fa-user-circle"></i>';
        }
    }

    // Public methods for external control
    static getInstance() {
        if (!window.burgerMenuInstance) {
            window.burgerMenuInstance = new BurgerMenu();
        }
        return window.burgerMenuInstance;
    }
}

// Auto-initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    BurgerMenu.getInstance();
    
    // Set up nav-toggle button functionality
    setupNavToggle();
});

// Function to setup nav-toggle button
function setupNavToggle() {
    const navToggle = document.querySelector('.nav-toggle');
    
    if (navToggle) {
        navToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Add smooth animation to toggle button
            navToggle.classList.toggle('active');
            
            // Use global burger menu functions
            window.toggleBurgerMenu();
        });
    }
}

// Expose global methods for external burger menu control
window.openBurgerMenu = () => {
    const instance = BurgerMenu.getInstance();
    instance.open();
};

window.closeBurgerMenu = () => {
    const instance = BurgerMenu.getInstance();
    instance.close();
};

window.toggleBurgerMenu = () => {
    const instance = BurgerMenu.getInstance();
    instance.toggle();
};

// Add method to refresh burger menu when user data changes
window.refreshBurgerMenu = () => {
    const instance = BurgerMenu.getInstance();
    instance.refreshUserInterface();
};

// Listen for user changes in the main application
window.addEventListener('userUpdated', () => {
    const instance = BurgerMenu.getInstance();
    instance.refreshUserInterface();
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BurgerMenu;
}