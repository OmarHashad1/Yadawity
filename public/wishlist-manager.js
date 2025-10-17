/**
 * Wishlist Functionality JavaScript Module
 * Global wishlist management with SweetAlert integration
 * Include this file in every page that needs wishlist functionality
 */

// ==========================================
// WISHLIST MANAGER CLASS
// ==========================================

class WishlistManager {
    constructor() {
        this.isProcessing = false;
        this.init();
    }

    init() {
        // Setup event listeners for add to wishlist buttons
        this.setupAddToWishlistListeners();
    }

    // Setup event listeners for add to wishlist buttons
    setupAddToWishlistListeners() {
        // Use event delegation to handle dynamically added buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.wishlist-btn, .add-to-wishlist-btn, .addToWishlistBtn, .love-btn') || 
                e.target.closest('.wishlist-btn, .add-to-wishlist-btn, .addToWishlistBtn, .love-btn')) {
                
                e.preventDefault();
                e.stopPropagation();
                
                const button = e.target.matches('.wishlist-btn, .add-to-wishlist-btn, .addToWishlistBtn, .love-btn') ? 
                               e.target : 
                               e.target.closest('.wishlist-btn, .add-to-wishlist-btn, .addToWishlistBtn, .love-btn');
                
                this.handleAddToWishlist(button);
            }
        });
    }

    // Handle add to wishlist action
    async handleAddToWishlist(button) {
        if (this.isProcessing) {
            return;
        }

        try {
            this.isProcessing = true;
            
            // Get artwork ID from button
            const artworkId = this.getArtworkId(button);
            if (!artworkId) {
                throw new Error('Artwork ID not found');
            }

            // Disable button and show loading
            this.setButtonLoading(button, true);

            // Make API call
            const result = await this.addToWishlistAPI(artworkId);

            if (result.success) {
                // Show success message
                await this.showSuccessAlert(result.message, result.data);
                
                // Update button state based on action
                if (result.data && result.data.action === 'already_exists') {
                    this.setButtonActive(button);
                } else {
                    this.setButtonActive(button);
                }
                
                // Update wishlist count if element exists
                this.updateWishlistCount();

            } else {
                // Handle specific error cases
                if (result.redirect) {
                    await this.showLoginRequired();
                    this.setButtonError(button, 'Login Required');
                } else {
                    await this.showErrorAlert(result.message);
                    this.setButtonError(button, 'Error');
                }
            }

        } catch (error) {
            console.error('Wishlist Error:', error);
            
            // Check if it's an authentication error
            if (error.message.includes('Authentication required')) {
                await this.showLoginRequired();
                this.setButtonError(button, 'Login Required');
            } else {
                await this.showErrorAlert('An error occurred while adding to wishlist');
                this.setButtonError(button, 'Error');
            }
        } finally {
            this.isProcessing = false;
        }
    }

    // Get artwork ID from button
    getArtworkId(button) {
        const artworkId = button.getAttribute('data-artwork-id') || 
               button.getAttribute('data-product-id') ||
               button.getAttribute('data-id') ||
               button.closest('[data-artwork-id]')?.getAttribute('data-artwork-id') ||
               button.closest('[data-product-id]')?.getAttribute('data-product-id') ||
               button.closest('[data-id]')?.getAttribute('data-id');
        
        console.log('Found artwork ID:', artworkId);
        return artworkId;
    }

    // Initialize wishlist buttons for dynamically loaded content
    initializeWishlistButtons() {
        // Since we use event delegation, no need to re-attach listeners
        // But we can do any additional setup here if needed
        const wishlistButtons = document.querySelectorAll('.wishlist-btn, .add-to-wishlist-btn, .addToWishlistBtn, .love-btn');
        
        wishlistButtons.forEach(button => {
            // Ensure button has proper attributes
            if (!button.getAttribute('data-artwork-id') && !button.getAttribute('data-id')) {
                console.warn('Wishlist button missing artwork ID:', button);
            }
        });
    }

    // Public method to add to wishlist (alias for handleAddToWishlist)
    addToWishlist(button) {
        return this.handleAddToWishlist(button);
    }

    // API call to add to wishlist
    async addToWishlistAPI(artworkId) {
        try {
            console.log('Making wishlist API call for artwork:', artworkId);
            
            const response = await fetch('./API/addToWishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({
                    artwork_id: parseInt(artworkId)
                })
            });

            console.log('Wishlist API response status:', response.status);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            console.log('Wishlist API result:', result);
            
            return result;
        } catch (error) {
            console.error('Wishlist API Error:', error);
            
            if (error.name === 'TypeError' && error.message.includes('fetch')) {
                throw new Error('Network error occurred');
            }
            
            throw error;
        }
    }

    // Button state management
    setButtonLoading(button, isLoading) {
        if (isLoading) {
            button.disabled = true;
            button.style.pointerEvents = 'none';
            
            // Store original content
            if (!button.dataset.originalContent) {
                button.dataset.originalContent = button.innerHTML;
            }
            
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.style.opacity = '0.7';
        } else {
            button.disabled = false;
            button.style.pointerEvents = 'auto';
            button.style.opacity = '1';
            
            // Restore original content
            if (button.dataset.originalContent) {
                button.innerHTML = button.dataset.originalContent;
                delete button.dataset.originalContent;
            }
        }
    }

    setButtonActive(button) {
        // Set button to active/loved state
        button.classList.add('active');
        button.innerHTML = '<i class="fas fa-heart"></i>';
        button.style.color = '#e74c3c'; // Red color for loved state
        button.style.animation = 'heartBeat 0.6s ease-in-out';
        
        // Reset animation after completion
        setTimeout(() => {
            button.style.animation = '';
        }, 600);
        
        // Reset loading state
        this.setButtonLoading(button, false);
    }

    setButtonInactive(button) {
        // Set button to inactive/unloved state
        button.classList.remove('active');
        button.innerHTML = '<i class="far fa-heart"></i>';
        button.style.color = ''; // Reset to default color
        
        // Reset loading state
        this.setButtonLoading(button, false);
    }

    setButtonError(button, errorText) {
        button.style.color = '#dc3545'; // Red color for error
        button.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
        
        // Reset after delay
        setTimeout(() => {
            this.setButtonInactive(button);
        }, 3000);
        
        // Reset loading state
        this.setButtonLoading(button, false);
    }

    // SweetAlert methods
    async showSuccessAlert(message, data = null) {
        const icon = data && data.action === 'already_exists' ? 'info' : 'success';
        const title = data && data.action === 'already_exists' ? 'Already in Wishlist' : 'Added to Wishlist!';
        
        // Add custom styles for the alert
        const style = document.createElement('style');
        style.textContent = `
            .custom-wishlist-popup {
                border-radius: 15px !important;
                border: 2px solid #8B4513 !important;
            }
            .custom-wishlist-button {
                background: linear-gradient(135deg, #8B4513 0%, #A0522D 100%) !important;
                border: none !important;
                border-radius: 8px !important;
                padding: 12px 24px !important;
                font-weight: 600 !important;
                letter-spacing: 0.5px !important;
                text-transform: uppercase !important;
                transition: all 0.3s ease !important;
                box-shadow: 0 4px 12px rgba(139, 69, 19, 0.3) !important;
            }
        `;
        document.head.appendChild(style);

        return await Swal.fire({
            icon: icon,
            title: title,
            text: message,
            confirmButtonText: 'OK',
            width: 500,
            padding: '2em',
            background: '#ffffff',
            customClass: {
                popup: 'custom-wishlist-popup',
                confirmButton: 'custom-wishlist-button'
            }
        });
    }

    async showErrorAlert(message) {
        return await Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message,
            confirmButtonText: 'OK',
            confirmButtonColor: '#d4af37',
            width: '400px',
            padding: '20px',
            customClass: {
                popup: 'wishlist-error-alert',
                title: 'wishlist-alert-title',
                content: 'wishlist-alert-content'
            }
        });
    }

    async showLoginRequired() {
        const result = await Swal.fire({
            icon: 'info',
            title: 'Login Required',
            text: 'Please log in to add items to your wishlist',
            showCancelButton: true,
            confirmButtonText: 'Go to Login',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d4af37',
            cancelButtonColor: '#6c757d',
            width: '400px',
            padding: '20px',
            customClass: {
                popup: 'wishlist-login-alert',
                title: 'wishlist-alert-title',
                content: 'wishlist-alert-content'
            }
        });

        if (result.isConfirmed) {
            window.location.href = `login.php?redirect=${encodeURIComponent(window.location.pathname)}`;
        }
    }

    // Update wishlist count in navbar
    updateWishlistCount() {
        // This could be enhanced to fetch actual count from server
        const wishlistCount = document.querySelector('.wishlistCount');
        if (wishlistCount) {
            let currentCount = parseInt(wishlistCount.textContent) || 0;
            currentCount += 1;
            wishlistCount.textContent = currentCount;
            wishlistCount.style.display = currentCount > 0 ? 'flex' : 'none';
        }
        
        // Also update the global app.js function if available
        if (window.YadawityNavbar && window.YadawityNavbar.updateWishlistCount) {
            window.YadawityNavbar.updateWishlistCount();
        }
    }
}

// ==========================================
// INITIALIZE WISHLIST MANAGER
// ==========================================

// Create global instance
let wishlistManager = null;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize wishlist manager
    wishlistManager = new WishlistManager();
    
    // Make it globally available
    window.wishlistManager = wishlistManager;
    
    console.log('Wishlist Manager initialized');
});

// Also initialize on window load as fallback
window.addEventListener('load', function() {
    if (!wishlistManager) {
        wishlistManager = new WishlistManager();
        window.wishlistManager = wishlistManager;
        console.log('Wishlist Manager initialized (fallback)');
    }
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = WishlistManager;
}

// ==========================================
// CSS ANIMATION STYLES (Add to your CSS)
// ==========================================
/*
Add this CSS to your main stylesheet:

@keyframes heartBeat {
    0% {
        transform: scale(1);
    }
    14% {
        transform: scale(1.3);
    }
    28% {
        transform: scale(1);
    }
    42% {
        transform: scale(1.3);
    }
    70% {
        transform: scale(1);
    }
}

.wishlist-btn.active {
    color: #e74c3c !important;
}

.wishlist-btn:hover {
    transform: scale(1.1);
    transition: transform 0.2s ease;
}

.colored-toast.swal2-toast {
    border-left: 4px solid #28a745;
}
*/
