// Cart Page JavaScript - Handles cart display and interactions
class CartPageManager {
    constructor() {
        this.cartItems = [];
        this.isLoading = false;
        this.isRemoving = false;
        this.cartEventHandler = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadCart();
    }

    bindEvents() {
        // Checkout button
        const checkoutBtn = document.getElementById('checkoutBtn');
        if (checkoutBtn) {
            checkoutBtn.addEventListener('click', () => this.proceedToCheckout());
        }
    }

    async loadCart() {
        this.showLoading(true);
        
        try {
            const response = await fetch('./API/getCart.php', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();
            
            if (data.success) {
                this.cartItems = data.data.cart_items || data.data.items || [];
                this.displayCart();
                this.updateSummary();
            } else {
                this.showError(data.message || 'Failed to load cart');
            }
        } catch (error) {
            console.error('Error loading cart:', error);
            this.showError('Failed to load cart. Please check your connection.');
        } finally {
            this.showLoading(false);
        }
    }

    showLoading(show) {
        const loadingState = document.getElementById('loadingState');
        const cartContainer = document.getElementById('cartItemsContainer');
        const emptyCart = document.querySelector('.emptyCart');
        
        if (show) {
            this.isLoading = true;
            if (loadingState) loadingState.style.display = 'block';
            if (cartContainer) cartContainer.style.display = 'none';
            if (emptyCart) emptyCart.style.display = 'none';
        } else {
            this.isLoading = false;
            if (loadingState) loadingState.style.display = 'none';
        }
    }

    displayCart() {
        const container = document.getElementById('cartItemsContainer');
        const emptyCart = document.querySelector('.emptyCart');
        const cartLayout = document.querySelector('.cartLayout');
        
        if (!container) return;

        if (this.cartItems.length === 0) {
            container.style.display = 'none';
            if (cartLayout) cartLayout.style.display = 'none';
            if (emptyCart) emptyCart.style.display = 'block';
            this.updateCartCount(0);
            return;
        }

        // Show cart layout and hide empty state
        if (cartLayout) cartLayout.style.display = 'grid';
        if (emptyCart) emptyCart.style.display = 'none';
        container.style.display = 'block';

        // Generate cart items HTML
        container.innerHTML = this.cartItems.map(item => this.createCartItemHTML(item)).join('');
        
        // Bind events for quantity controls and remove buttons
        this.bindCartItemEvents();
        
        // Update cart count
        this.updateCartCount(this.cartItems.length);
    }

    createCartItemHTML(item) {
        // Handle different product types
        const type = item.type || 'artwork';
        let productData, imageUrl, title, price, linkUrl, additionalInfo, productId;
        
        switch (type) {
            case 'artwork':
                const artwork = item.artwork || item;
                const artist = item.artist;
                
                productId = artwork.artwork_id || item.product_id;
                title = artwork.title;
                price = artwork.price;
                linkUrl = `artwork.php?id=${productId}`;
                
                // Format image URL
                imageUrl = './image/placeholder-artwork.jpg'; // Default fallback
                if (artwork.artwork_image_url) {
                    imageUrl = artwork.artwork_image_url;
                } else if (artwork.artwork_image) {
                    imageUrl = `./uploads/artworks/${artwork.artwork_image}`;
                }
                
                // Format artist name
                let artistName = 'Unknown Artist';
                if (artist && artist.full_name) {
                    artistName = artist.full_name;
                } else if (artist && artist.first_name && artist.last_name) {
                    artistName = `${artist.first_name} ${artist.last_name}`;
                } else if (artist && artist.first_name) {
                    artistName = artist.first_name;
                }
                
                additionalInfo = `
                    <p class="cartItemArtist">by ${artistName}</p>
                    <div class="cartItemDetails">
                        ${artwork.category ? `<div class="cartItemDetail"><i class="fas fa-tag"></i> ${artwork.category}</div>` : ''}
                    </div>
                `;
                break;
                
            case 'session':
                productId = item.product_id;
                title = item.title;
                price = item.price;
                linkUrl = `sessionsTherapy.php#session-${productId}`;
                
                // No image for sessions
                imageUrl = null;
                
                additionalInfo = `
                    <p class="cartItemType"><i class="fas fa-calendar-alt"></i> Therapy Session</p>
                    <div class="cartItemDetails">
                        ${item.category ? `<div class="cartItemDetail"><i class="fas fa-tag"></i> ${item.category}</div>` : ''}
                        ${item.date ? `<div class="cartItemDetail"><i class="fas fa-calendar"></i> ${new Date(item.date).toLocaleDateString()}</div>` : ''}
                        ${item.open_time ? `<div class="cartItemDetail"><i class="fas fa-clock"></i> ${item.open_time}</div>` : ''}
                        ${item.doctor_name ? `<div class="cartItemDetail"><i class="fas fa-user-md"></i> Dr. ${item.doctor_name}</div>` : ''}
                    </div>
                `;
                break;
                
            case 'workshop':
                productId = item.product_id;
                title = item.title;
                price = item.price;
                linkUrl = `workshops.php#workshop-${productId}`;
                
                // No image for workshops
                imageUrl = null;
                
                additionalInfo = `
                    <p class="cartItemType"><i class="fas fa-tools"></i> Workshop</p>
                    <div class="cartItemDetails">
                        ${item.category ? `<div class="cartItemDetail"><i class="fas fa-tag"></i> ${item.category}</div>` : ''}
                        ${item.date ? `<div class="cartItemDetail"><i class="fas fa-calendar"></i> ${new Date(item.date).toLocaleDateString()}</div>` : ''}
                        ${item.open_time ? `<div class="cartItemDetail"><i class="fas fa-clock"></i> ${item.open_time}</div>` : ''}
                        ${item.doctor_name ? `<div class="cartItemDetail"><i class="fas fa-user-md"></i> Dr. ${item.doctor_name}</div>` : ''}
                    </div>
                `;
                break;
                
            default:
                // Fallback for unknown types
                productId = item.product_id || item.id;
                title = item.title || 'Unknown Item';
                price = item.price || 0;
                linkUrl = '#';
                imageUrl = './image/placeholder-artwork.jpg';
                additionalInfo = `<p class="cartItemType">Unknown Type</p>`;
        }

        return `
            <div class="cartItem" data-cart-id="${item.cart_id}" data-product-id="${productId}" data-type="${type}">
                ${imageUrl ? `
                <div class="cartItemImage">
                    <img src="${imageUrl}" alt="${title}" onerror="this.onerror=null;this.src='./image/placeholder-artwork.jpg';">
                </div>
                ` : `
                <div class="cartItemIcon">
                    <i class="fas ${type === 'session' ? 'fa-calendar-alt' : type === 'workshop' ? 'fa-tools' : 'fa-image'} fa-3x"></i>
                </div>
                `}
                
                <div class="cartItemInfo">
                    <a href="${linkUrl}" class="cartItemTitle">${title}</a>
                    ${additionalInfo}
                    <div class="cartItemPrice">EGP ${parseFloat(price).toLocaleString()}</div>
                </div>
                
                <div class="cartItemActions">
                    <div class="quantityControls">
                        <button class="quantityBtn minus" data-action="decrease" ${item.quantity <= 1 || item.type === 'session' || item.type === 'workshop' ? 'disabled' : ''}>
                            <i class="fas fa-minus"></i>
                        </button>
                        <span class="quantityDisplay">${item.quantity}</span>
                        <button class="quantityBtn plus" data-action="increase" ${item.type === 'session' || item.type === 'workshop' ? 'disabled' : ''}>
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    
                    <button class="removeBtn" data-action="remove">
                        <i class="fas fa-trash-alt"></i>
                        Remove
                    </button>
                </div>
            </div>
        `;
    }

    bindCartItemEvents() {
        const container = document.getElementById('cartItemsContainer');
        if (!container) return;

        // Remove existing event listeners to prevent duplicates
        if (this.cartEventHandler) {
            container.removeEventListener('click', this.cartEventHandler);
        }

        // Create and store the event handler
        this.cartEventHandler = async (e) => {
            const button = e.target.closest('button');
            if (!button) return;

            const cartItem = button.closest('.cartItem');
            if (!cartItem) return;

            const cartId = cartItem.dataset.cartId;
            const action = button.dataset.action;

            // Prevent multiple rapid clicks
            if (button.disabled) return;
            button.disabled = true;

            try {
                switch (action) {
                    case 'increase':
                        await this.updateQuantity(cartId, 1);
                        break;
                    case 'decrease':
                        await this.updateQuantity(cartId, -1);
                        break;
                    case 'remove':
                        await this.removeItem(cartId);
                        break;
                }
            } finally {
                // Re-enable button after operation
                setTimeout(() => {
                    if (button && button.parentNode) {
                        button.disabled = false;
                    }
                }, 1000);
            }
        };

        container.addEventListener('click', this.cartEventHandler);
    }

    async updateQuantity(cartId, change) {
        const cartItem = this.cartItems.find(item => item.cart_id == cartId);
        if (!cartItem) {
            console.error('Cart item not found:', cartId);
            return;
        }

        // Check if this is a session or workshop - they cannot have quantity changed
        const productType = cartItem.type || 'artwork';
        if (productType === 'session' || productType === 'workshop') {
            this.showMessage('Quantity cannot be changed for sessions and workshops', 'error');
            return;
        }

        const newQuantity = cartItem.quantity + change;
        if (newQuantity < 1) {
            this.showMessage('Quantity cannot be less than 1', 'error');
            return;
        }

        if (newQuantity > 10) {
            this.showMessage('Maximum quantity allowed is 10', 'error');
            return;
        }

        try {
            // Show loading state for this item
            const itemElement = document.querySelector(`[data-cart-id="${cartId}"]`);
            if (itemElement) {
                itemElement.style.opacity = '0.6';
                itemElement.style.pointerEvents = 'none';
            }

            // Get product info from cart item
            const productId = itemElement ? itemElement.dataset.productId : (cartItem.artwork ? cartItem.artwork.artwork_id : cartItem.product_id);
            const productType = itemElement ? itemElement.dataset.type : (cartItem.type || 'artwork');

            console.log('Updating quantity:', { cartId, productId, productType, newQuantity });

            // Update quantity in database
            const response = await fetch('./API/updateCartQuantity.php', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId,
                    type: productType,
                    quantity: newQuantity
                })
            });

            const responseText = await response.text();
            console.log('Raw response:', responseText);
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON parsing error:', parseError);
                console.error('Response text:', responseText);
                throw new Error('Invalid JSON response from server');
            }

            // Handle both success and error responses
            if (!response.ok && !data.message) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            if (data.success) {
                // Update local state
                cartItem.quantity = newQuantity;
                
                // Update display
                const quantityDisplay = itemElement.querySelector('.quantityDisplay');
                if (quantityDisplay) {
                    quantityDisplay.textContent = newQuantity;
                }
                
                // Update minus button state
                const minusBtn = itemElement.querySelector('.quantityBtn.minus');
                if (minusBtn) {
                    minusBtn.disabled = newQuantity <= 1;
                }
                
                // Update plus button state if at max quantity
                const plusBtn = itemElement.querySelector('.quantityBtn.plus');
                if (plusBtn) {
                    plusBtn.disabled = newQuantity >= 10;
                }
                
                this.updateSummary();
                this.showMessage('Cart updated successfully', 'success');
                
                // Update cart count if provided
                if (data.cart_totals && data.cart_totals.total_quantity) {
                    this.updateCartCount(data.cart_totals.total_quantity);
                }
            } else {
                // Show the specific error message from the API
                const errorMessage = data.message || 'Unable to update cart quantity';
                this.showMessage(errorMessage, 'error');
            }
        } catch (error) {
            console.error('Error updating quantity:', error);
            // Only show generic error for actual network/parsing errors
            if (error.message.includes('Invalid JSON') || error.message.includes('HTTP error')) {
                this.showMessage('Something went wrong. Please refresh and try again.', 'error');
            } else {
                this.showMessage('Network error. Please check your connection.', 'error');
            }
        } finally {
            // Restore item state
            const itemElement = document.querySelector(`[data-cart-id="${cartId}"]`);
            if (itemElement) {
                itemElement.style.opacity = '1';
                itemElement.style.pointerEvents = 'auto';
            }
        }
    }

    async removeItem(cartId) {
        // Prevent multiple simultaneous remove operations
        if (this.isRemoving) {
            return;
        }

        // SweetAlert2 confirmation dialog
        const result = await Swal.fire({
            title: 'Remove Item?',
            text: 'Are you sure you want to remove this item from your cart?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#8b7355',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, remove it',
            cancelButtonText: 'Cancel',
            customClass: {
                popup: 'cart-swal-popup',
                title: 'cart-swal-title',
                confirmButton: 'cart-swal-confirm',
                cancelButton: 'cart-swal-cancel'
            }
        });

        if (!result.isConfirmed) {
            return;
        }

        this.isRemoving = true;

        // Find the product_id and type from the cart item
        const cartItem = this.cartItems.find(item => item.cart_id == cartId);
        if (!cartItem) {
            this.showMessage('Item not found in cart', 'error');
            this.isRemoving = false;
            return;
        }

        // Get product ID based on type
        let productId;
        if (cartItem.type === 'artwork' && cartItem.artwork) {
            productId = cartItem.artwork.artwork_id;
        } else {
            productId = cartItem.product_id;
        }
        
        const productType = cartItem.type || 'artwork'; // Default to 'artwork' if not specified

        // Debug logging
        console.log('Removing item:', { cartId, productId, productType, cartItem });

        try {
            const requestData = {
                product_id: productId,
                type: productType
            };
            
            console.log('Sending remove request:', requestData);
            
            const response = await fetch('./API/removeFromCart.php', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(requestData)
            });

            // Check if response is ok
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const responseText = await response.text();
            console.log('Raw response:', responseText);
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response text:', responseText);
                throw new Error('Invalid JSON response from server');
            }
            
            console.log('Remove response:', data);
            
            if (data.success) {
                // Show success message
                this.showMessage(data.message || 'Item removed from cart successfully', 'success');
                
                // Remove from local state
                this.cartItems = this.cartItems.filter(item => item.cart_id != cartId);
                
                // Remove from display with animation
                const itemElement = document.querySelector(`[data-cart-id="${cartId}"]`);
                if (itemElement) {
                    itemElement.style.animation = 'slideOutUp 0.3s ease-out';
                    setTimeout(() => {
                        itemElement.remove();
                        
                        // If cart is empty, refresh to show empty state
                        if (this.cartItems.length === 0) {
                            this.displayCart();
                        }
                        
                        this.updateSummary();
                        this.updateCartCount(data.cart_count || this.cartItems.length);
                    }, 300);
                }
                
                // Update cart count in navbar if exists
                if (window.updateCartCount) {
                    window.updateCartCount();
                }
            } else {
                this.showMessage(data.message || 'Failed to remove item', 'error');
            }
        } catch (error) {
            console.error('Error removing item:', error);
            this.showMessage('Failed to remove item from cart', 'error');
        } finally {
            this.isRemoving = false;
        }
    }

    updateSummary() {
        const subtotal = this.calculateSubtotal();
        const itemCount = this.cartItems.reduce((sum, item) => sum + item.quantity, 0);
        
        // Update subtotal
        const subtotalElement = document.getElementById('subtotal');
        const subtotalLabelElement = document.getElementById('subtotalLabel');
        if (subtotalElement) {
            subtotalElement.textContent = `EGP ${subtotal.toLocaleString()}`;
        }
        if (subtotalLabelElement) {
            subtotalLabelElement.textContent = `Subtotal (${itemCount} items):`;
        }
        
        // Calculate additional costs
        const shipping = 50; // Fixed 50 EGP shipping
        const total = subtotal + shipping;
        
        // Update shipping
        const shippingElement = document.getElementById('shipping');
        if (shippingElement) {
            shippingElement.textContent = `EGP ${shipping}`;
        }
        
        // Update total
        const totalElement = document.getElementById('total');
        if (totalElement) {
            totalElement.textContent = `EGP ${total.toLocaleString()}`;
        }
        
        // Enable/disable checkout button
        const checkoutBtn = document.getElementById('checkoutBtn');
        if (checkoutBtn) {
            checkoutBtn.disabled = this.cartItems.length === 0;
        }
    }

    calculateSubtotal() {
        return this.cartItems.reduce((sum, item) => {
            let price = 0;
            
            // Handle different product types
            if (item.type === 'artwork' && item.artwork) {
                price = parseFloat(item.artwork.price) || 0;
            } else if (item.price) {
                // For sessions, workshops, and other types that have price directly
                price = parseFloat(item.price) || 0;
            } else if (item.item_total) {
                // If item_total is already calculated
                return sum + parseFloat(item.item_total);
            }
            
            return sum + (price * (item.quantity || 1));
        }, 0);
    }

    updateCartCount(count) {
        const cartCountElement = document.getElementById('cartItemsCount');
        if (cartCountElement) {
            cartCountElement.textContent = `Cart Items (${count})`;
        }
    }

    proceedToCheckout() {
        if (this.cartItems.length === 0) {
            this.showMessage('Your cart is empty', 'warning');
            return;
        }
        
        // Redirect to checkout page (you'll need to create this)
        window.location.href = 'checkout.php';
    }

    showError(message) {
        const container = document.getElementById('cartItemsContainer');
        const emptyCart = document.querySelector('.emptyCart');
        const cartLayout = document.querySelector('.cartLayout');
        
        if (container) {
            container.innerHTML = `
                <div class="errorState">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Error Loading Cart</h3>
                    <p>${message}</p>
                    <button class="retryBtn" onclick="cartPageManager.loadCart()">
                        <i class="fas fa-redo"></i> Try Again
                    </button>
                </div>
            `;
            container.style.display = 'block';
        }
        
        if (cartLayout) cartLayout.style.display = 'grid';
        if (emptyCart) emptyCart.style.display = 'none';
    }

    showMessage(message, type = 'info') {
        // Remove existing messages
        const existingMessages = document.querySelectorAll('.message');
        existingMessages.forEach(msg => msg.remove());
        
        // Create new message
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        
        let icon = 'fas fa-info-circle';
        if (type === 'success') icon = 'fas fa-check-circle';
        else if (type === 'error') icon = 'fas fa-exclamation-circle';
        else if (type === 'warning') icon = 'fas fa-exclamation-triangle';
        
        messageDiv.innerHTML = `
            <i class="${icon}"></i>
            <span>${message}</span>
        `;
        
        // Insert at top of cart container
        const cartContainer = document.querySelector('.cartContainer');
        if (cartContainer) {
            cartContainer.insertBefore(messageDiv, cartContainer.firstChild);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.remove();
                }
            }, 5000);
        }
    }
}

// Initialize cart page manager when DOM is loaded
let cartPageManager;

document.addEventListener('DOMContentLoaded', () => {
    cartPageManager = new CartPageManager();
});

// Add slide out animation for remove
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOutUp {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-30px);
        }
    }
`;
document.head.appendChild(style);
