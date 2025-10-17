/**
 * Cart Functionality JavaScript Module
 * Global cart management with SweetAlert integration
 * Include this file in every page that needs cart functionality
 */

// ==========================================
// CART FUNCTIONALITY
// ==========================================

class CartManager {
    constructor() {
        this.isProcessing = false;
        this.init();
    }

    init() {
        // Setup event listeners for add to cart buttons
        this.setupAddToCartListeners();
        
        // Setup event listeners for quantity changes
        this.setupQuantityListeners();
        
    }

    // Setup event listeners for add to cart buttons
    setupAddToCartListeners() {
        // Use event delegation to handle dynamically added buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.addToCart, .add-to-cart-btn, .addToCartBtn') || 
                e.target.closest('.addToCart, .add-to-cart-btn, .addToCartBtn')) {
                
                e.preventDefault();
                e.stopPropagation();
                
                const button = e.target.matches('.addToCart, .add-to-cart-btn, .addToCartBtn') ? 
                               e.target : 
                               e.target.closest('.addToCart, .add-to-cart-btn, .addToCartBtn');
                
                this.handleAddToCart(button);
            }
        });
    }

    // Setup quantity change listeners
    setupQuantityListeners() {
        document.addEventListener('change', (e) => {
            if (e.target.matches('.cart-quantity-input')) {
                this.handleQuantityChange(e.target);
            }
        });
    }

    // Handle add to cart action
    async handleAddToCart(button) {
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

            // Get quantity (default to 1)
            let quantity = 1;
            const quantityInput = button.closest('.artworkCard, .artwork-card, .product-card')?.querySelector('.quantity-input');
            if (quantityInput) {
                quantity = parseInt(quantityInput.value) || 1;
            }

            // Disable button and show loading
            this.setButtonLoading(button, true);

            // Make API call
            const result = await this.addToCartAPI(artworkId, quantity);

            if (result.success) {
                // Show success message
                await this.showSuccessAlert(result.message, result.data);
                
                // Update button state
                this.setButtonSuccess(button);
                
                // Update cart count if element exists
                this.updateCartCount();

            } else {
                // Handle specific error cases
                if (result.redirect) {
                    await this.showLoginRequired();
                    this.setButtonError(button, 'Login Required');
                } else if (result.available_quantity !== undefined) {
                    await this.showQuantityError(result.message, result.available_quantity);
                    this.setButtonError(button, 'Quantity Error');
                } else if (result.available_to_add !== undefined) {
                    await this.showQuantityError(result.message, result.available_to_add);
                    this.setButtonError(button, 'Quantity Error');
                } else {
                    await this.showErrorAlert(result.message);
                    this.setButtonError(button, 'Error');
                }
            }

        } catch (error) {
            
            // Check if it's an authentication error
            if (error.message.includes('Authentication required')) {
                await this.showLoginRequired();
                this.setButtonError(button, 'Login Required');
            } else {
                await this.showErrorAlert('An error occurred while adding to cart');
                this.setButtonError(button, 'Error');
            }
        } finally {
            this.isProcessing = false;
            // Don't call setButtonLoading(button, false) here since success/error states handle the restoration
        }
    }

    // Get artwork ID from button
    getArtworkId(button) {
        const artworkId = button.getAttribute('data-artwork-id') || 
               button.getAttribute('data-product-id') ||
               button.closest('[data-artwork-id]')?.getAttribute('data-artwork-id') ||
               button.closest('[data-product-id]')?.getAttribute('data-product-id');
        
        
        return artworkId;
    }

    // Initialize cart buttons for dynamically loaded content
    initializeCartButtons() {
        // Since we use event delegation, no need to re-attach listeners
        // But we can do any additional setup here if needed
        const cartButtons = document.querySelectorAll('.addToCart, .add-to-cart-btn, .addToCartBtn');
        
        cartButtons.forEach(button => {
            // Ensure button has proper attributes
            if (!button.getAttribute('data-artwork-id')) {
            }
        });
    }

    // Public method to add to cart (alias for handleAddToCart)
    addToCart(button) {
        return this.handleAddToCart(button);
    }

    // API call to add item to cart
    async addToCartAPI(artworkId, quantity = 1) {
        
        // Parse and log specific user_login cookie
        const cookies = document.cookie.split(';');
        let userLoginCookie = null;
        for (let cookie of cookies) {
            const [name, value] = cookie.trim().split('=');
            if (name === 'user_login') {
                userLoginCookie = value;
                break;
            }
        }
        
        const response = await fetch('./API/addToCart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include', // Include cookies in the request
            body: JSON.stringify({
                artwork_id: parseInt(artworkId),
                quantity: parseInt(quantity)
            })
        });


        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const responseText = await response.text();
        
        // Check if response is HTML (indicates PHP error or redirect)
        if (responseText.trim().startsWith('<')) {
            throw new Error('Authentication required or server error');
        }
        
        try {
            const result = JSON.parse(responseText);
            return result;
        } catch (parseError) {
            throw new Error('Invalid response format from server');
        }
    }

    // Handle quantity change in cart
    async handleQuantityChange(input) {
        const artworkId = this.getArtworkId(input);
        const newQuantity = parseInt(input.value);

        if (!artworkId || newQuantity < 1) {
            input.value = input.min || 1;
            return;
        }

        try {
            const result = await this.addToCartAPI(artworkId, newQuantity);
            if (!result.success) {
                // Reset to previous value
                input.value = input.getAttribute('data-previous-value') || 1;
                await this.showErrorAlert(result.message);
            } else {
                input.setAttribute('data-previous-value', newQuantity);
                this.updateCartCount();
            }
        } catch (error) {
            input.value = input.getAttribute('data-previous-value') || 1;
            await this.showErrorAlert('Error updating quantity');
        }
    }

    // Set button to loading state
    setButtonLoading(button, loading) {
        if (loading) {
            // Store original content if not already stored
            if (!button.getAttribute('data-original-content')) {
                button.setAttribute('data-original-content', button.innerHTML);
                button.setAttribute('data-original-bg', button.style.backgroundColor || '');
            }
            
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            button.style.opacity = '0.7';
        } else {
            // Restore original content
            const originalContent = button.getAttribute('data-original-content');
            const originalBg = button.getAttribute('data-original-bg');
            
            if (originalContent) {
                button.innerHTML = originalContent;
                button.style.backgroundColor = originalBg;
            }
            
            button.disabled = false;
            button.style.opacity = '1';
        }
    }

    // Set button to success state temporarily
    setButtonSuccess(button) {
        // Store original content if not already stored
        if (!button.getAttribute('data-original-content')) {
            button.setAttribute('data-original-content', button.innerHTML);
            button.setAttribute('data-original-bg', button.style.backgroundColor || '');
        }
        
        const originalContent = button.getAttribute('data-original-content');
        const originalBg = button.getAttribute('data-original-bg');
        
        button.innerHTML = '<i class="fas fa-check"></i> Added!';
        button.style.backgroundColor = '#28a745';
        button.style.color = 'white';
        button.disabled = true;

        setTimeout(() => {
            button.innerHTML = originalContent;
            button.style.backgroundColor = originalBg;
            button.style.color = '';
            button.disabled = false;
        }, 2000);
    }

    // Set button to error state temporarily
    setButtonError(button, message = 'Error!') {
        // Store original content if not already stored
        if (!button.getAttribute('data-original-content')) {
            button.setAttribute('data-original-content', button.innerHTML);
            button.setAttribute('data-original-bg', button.style.backgroundColor || '');
        }
        
        const originalContent = button.getAttribute('data-original-content');
        const originalBg = button.getAttribute('data-original-bg');
        
        button.innerHTML = '<i class="fas fa-times"></i> ' + message;
        button.style.backgroundColor = '#dc3545';
        button.style.color = 'white';
        button.disabled = true;

        setTimeout(() => {
            button.innerHTML = originalContent;
            button.style.backgroundColor = originalBg;
            button.style.color = '';
            button.disabled = false;
        }, 3000);
    }

    // Update cart count in navigation
    async updateCartCount() {
        try {
            const response = await fetch('./API/getCart.php', {
                credentials: 'include' // Include cookies for authentication
            });
            const result = await response.json();
            
            if (result.success) {
                const cartCountElements = document.querySelectorAll('.cart-count, .cart-badge, .cart-counter');
                const totalItems = result.data.reduce((sum, item) => sum + item.quantity, 0);
                
                cartCountElements.forEach(element => {
                    element.textContent = totalItems;
                    element.style.display = totalItems > 0 ? 'block' : 'none';
                });
            }
        } catch (error) {
        }
    }

    // Show success alert
    async showSuccessAlert(message) {
        // Inject custom CSS for consistent styling
        const style = document.createElement('style');
        style.textContent = `
            .swal2-popup {
                border-radius: 15px !important;
                box-shadow: 0 10px 25px rgba(0,0,0,0.2) !important;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
            }
            .swal2-title {
                color: #2c3e50 !important;
                font-weight: 600 !important;
            }
            .swal2-content {
                color: #34495e !important;
            }
            .swal2-confirm {
                background: linear-gradient(135deg, #8B4513, #A0522D) !important;
                border: none !important;
                border-radius: 8px !important;
                padding: 10px 20px !important;
                font-weight: 600 !important;
                transition: all 0.3s ease !important;
            }
            .swal2-confirm:hover {
                background: linear-gradient(135deg, #A0522D, #CD853F) !important;
                transform: translateY(-2px) !important;
                box-shadow: 0 4px 12px rgba(139, 69, 19, 0.3) !important;
            }
        `;
        document.head.appendChild(style);

        Swal.fire({
            title: 'Added to Cart!',
            text: message,
            icon: 'success',
            confirmButtonText: 'OK',
            width: 500,
            padding: '2em',
            background: '#ffffff',
            customClass: {
                popup: 'custom-swal-popup',
                confirmButton: 'custom-swal-button'
            }
        });
    }

    // Show error alert
    async showErrorAlert(message) {
        if (typeof Swal !== 'undefined') {
            await Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonText: 'OK',
                confirmButtonColor: '#d4af37',
                width: '400px',
                padding: '20px',
                customClass: {
                    popup: 'cart-error-alert',
                    title: 'cart-alert-title',
                    content: 'cart-alert-content'
                }
            });
        } else {
            alert(message);
        }
    }

    // Show quantity error with suggestion
    async showQuantityError(message, availableQuantity) {
        if (typeof Swal !== 'undefined') {
            const result = await Swal.fire({
                icon: 'warning',
                title: 'Quantity Limitation',
                text: message,
                showCancelButton: true,
                confirmButtonText: `Add ${availableQuantity} item(s)`,
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#d4af37',
                cancelButtonColor: '#6c757d',
                width: '400px',
                padding: '20px',
                customClass: {
                    popup: 'cart-quantity-alert',
                    title: 'cart-alert-title',
                    content: 'cart-alert-content'
                }
            });

            if (result.isConfirmed && availableQuantity > 0) {
                // Find the button and add available quantity
                const button = document.querySelector(`[data-artwork-id="${artworkId}"]`);
                if (button) {
                    this.handleAddToCart(button, availableQuantity);
                }
            }
        } else {
            const confirm = window.confirm(`${message}\n\nWould you like to add ${availableQuantity} item(s) instead?`);
            if (confirm && availableQuantity > 0) {
                const button = document.querySelector(`[data-artwork-id="${artworkId}"]`);
                if (button) {
                    this.handleAddToCart(button, availableQuantity);
                }
            }
        }
    }

    // Show login required alert
    async showLoginRequired() {
        if (typeof Swal !== 'undefined') {
            const result = await Swal.fire({
                icon: 'info',
                title: 'Login Required',
                text: 'Please log in to add items to your cart',
                showCancelButton: true,
                confirmButtonText: 'Go to Login',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#d4af37',
                cancelButtonColor: '#6c757d',
                width: '400px',
                padding: '20px',
                customClass: {
                    popup: 'cart-login-alert',
                    title: 'cart-alert-title',
                    content: 'cart-alert-content'
                }
            });

            if (result.isConfirmed) {
                window.location.href = 'login.php';
            }
        } else {
            const confirm = window.confirm('Please log in to add items to your cart.\n\nWould you like to go to the login page?');
            if (confirm) {
                window.location.href = 'login.php';
            }
        }
    }

    // Native notification fallback
    showNativeNotification(message, type) {
        // Create a simple notification div
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#28a745' : '#dc3545'};
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 10000;
            font-family: Arial, sans-serif;
            max-width: 300px;
        `;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
}

// ==========================================
// QUICK ADD TO CART FUNCTIONS
// ==========================================

// Quick function for simple add to cart (quantity = 1)
async function quickAddToCart(artworkId) {
    if (window.cartManager) {
        const button = document.querySelector(`[data-artwork-id="${artworkId}"]`);
        if (button) {
            await window.cartManager.handleAddToCart(button);
        } else {
            // Create a temporary button for the API call
            const tempButton = document.createElement('button');
            tempButton.setAttribute('data-artwork-id', artworkId);
            await window.cartManager.handleAddToCart(tempButton);
        }
    }
}

// Function to add multiple items to cart
async function addMultipleToCart(items) {
    if (!window.cartManager) return;
    
    const results = [];
    for (const item of items) {
        try {
            const result = await window.cartManager.addToCartAPI(item.artwork_id, item.quantity || 1);
            results.push(result);
        } catch (error) {
        }
    }
    
    // Show summary
    const successCount = results.filter(r => r.success).length;
    const message = `${successCount} of ${items.length} items added to cart`;
    
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: successCount === items.length ? 'success' : 'warning',
            title: 'Bulk Add Complete',
            text: message,
            timer: 3000
        });
    }
    
    window.cartManager.updateCartCount();
}

// ==========================================
// INITIALIZATION
// ==========================================

// Initialize cart manager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize global cart manager
    window.cartManager = new CartManager();
    
    // Update cart count on page load
    if (window.cartManager) {
        window.cartManager.updateCartCount();
    }
    
});

// ==========================================
// CSS STYLES (Auto-inject if not present)
// ==========================================

// Inject basic cart styles if not already present
function injectCartStyles() {
    if (document.getElementById('cart-styles')) return;
    
    const style = document.createElement('style');
    style.id = 'cart-styles';
    style.textContent = `
        /* SweetAlert Custom Styling */
        .cart-success-alert,
        .cart-error-alert,
        .cart-quantity-alert,
        .cart-login-alert {
            border-radius: 12px !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
        }
        
        .cart-alert-title {
            font-size: 1.25rem !important;
            font-weight: 600 !important;
            color: #2c3e50 !important;
            margin-bottom: 0.5rem !important;
        }
        
        .cart-alert-content {
            font-size: 0.95rem !important;
            color: #5a6c7d !important;
            line-height: 1.5 !important;
        }
        
        .swal2-popup {
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15) !important;
            border: 1px solid rgba(212, 175, 55, 0.2) !important;
        }
        
        .swal2-icon {
            border-width: 3px !important;
        }
        
        .swal2-icon.swal2-success {
            border-color: #28a745 !important;
            color: #28a745 !important;
        }
        
        .swal2-icon.swal2-error {
            border-color: #dc3545 !important;
            color: #dc3545 !important;
        }
        
        .swal2-icon.swal2-warning {
            border-color: #ffc107 !important;
            color: #ffc107 !important;
        }
        
        .swal2-icon.swal2-info {
            border-color: #17a2b8 !important;
            color: #17a2b8 !important;
        }
        
        /* Cart UI Styling */
        .cart-success-toast {
            font-size: 14px !important;
        }
        
        .cart-error-alert {
            font-size: 16px !important;
        }
        
        .addToCart:disabled,
        .add-to-cart-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .addToCart,
        .add-to-cart-btn {
            transition: all 0.3s ease !important;
        }
        
        .addToCart .fa-spinner {
            animation: spin 1s linear infinite;
        }
        
        .addToCart .fa-check,
        .add-to-cart-btn .fa-check {
            animation: checkmark 0.5s ease-in-out;
        }
        
        .addToCart .fa-times,
        .add-to-cart-btn .fa-times {
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes checkmark {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .cart-count,
        .cart-badge,
        .cart-counter {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            font-weight: bold;
            position: absolute;
            top: -8px;
            right: -8px;
            min-width: 18px;
            text-align: center;
            display: none;
        }
    `;
    
    document.head.appendChild(style);
}

// Inject styles when DOM is loaded
document.addEventListener('DOMContentLoaded', injectCartStyles);

// Export for module usage (if needed)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { CartManager, quickAddToCart, addMultipleToCart };
}
