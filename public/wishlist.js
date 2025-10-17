// Wishlist Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initializeWishlistPage();
});

// Utility Functions
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
    
    if (diffDays === 0) {
        return 'Today';
    } else if (diffDays === 1) {
        return 'Yesterday';
    } else if (diffDays < 30) {
        return `${diffDays} days ago`;
    } else {
        return date.toLocaleDateString();
    }
}

// Initialize page
function initializeWishlistPage() {
    initializeWishlistActions();
    initializeFilters();
    // Fetch wishlist from API and render items, then wire item actions
    fetchWishlistFromAPI();
    // Use global navbar function for cart count
    if (typeof window.updateCartCount === 'function') {
        window.updateCartCount();
    }
}

// Fetch wishlist data from server API and render
function fetchWishlistFromAPI() {
    const endpoint = './API/getWishlist.php';

    fetch(endpoint, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(json => {
        if (!json || json.success !== true) {
            // If not logged in or no wishlist, show empty state
            console.warn('Wishlist API error:', json);
            showEmptyState();
            if (json && json.message) showNotification(json.message, 'error');
            return;
        }

        const items = json.data && json.data.wishlist_items ? json.data.wishlist_items : [];
        renderWishlistItems(items);
        // updateWishlistStats(); // Commented out - stats elements not present in current HTML
        // Use global navbar function for wishlist count
        if (typeof window.updateWishlistCount === 'function') {
            window.updateWishlistCount();
        }
    })
    .catch(err => {
        console.error('Failed to fetch wishlist:', err);
        showEmptyState();
        showNotification('Unable to load wishlist. Please try again later.', 'error');
    });
}

// Render wishlist items into the DOM
function renderWishlistItems(items) {
    const container = document.querySelector('.wishlistGrid');
    const emptyState = document.querySelector('.emptyWishlist');
    const wishlistStats = document.querySelector('.wishlistStats');
    const wishlistActions = document.querySelector('.wishlistActions');
    const loadingSpinner = document.querySelector('#wishlistLoading');

    if (!container) return;

    // Hide loading spinner
    if (loadingSpinner) {
        loadingSpinner.style.display = 'none';
    }

    // Add cart-layout class for styling
    const wishlistContainer = document.querySelector('.wishlistContainer');
    if (wishlistContainer) {
        wishlistContainer.classList.add('cart-layout');
    }

    // Clear container except loading spinner
    const existingItems = container.querySelectorAll('.wishlistItem');
    existingItems.forEach(item => item.remove());

    if (!items || items.length === 0) {
        showEmptyState();
        return;
    }

    // Show main wishlist sections
    if (wishlistStats) wishlistStats.style.display = 'block';
    if (wishlistActions) wishlistActions.style.display = 'flex';
    if (emptyState) emptyState.style.display = 'none';
    container.style.display = 'grid';

    // Build each item with cart-like structure
    items.forEach(item => {
        const art = item.artwork || {};
        const artist = item.artist || {};
        const price = art.price ? parseFloat(art.price) : 0;
        const salePrice = art.sale_price ? parseFloat(art.sale_price) : null;
        const onSale = art.on_sale && salePrice && salePrice < price;
        const finalPrice = onSale ? salePrice : price;
        
        // Determine image source
        let imgSrc = art.artwork_image_url || art.artwork_image || '';
        if (imgSrc && !imgSrc.startsWith('http') && !imgSrc.startsWith('/') && !imgSrc.startsWith('./') && !imgSrc.startsWith('../')) {
            imgSrc = './uploads/artworks/' + imgSrc;
        }
        if (!imgSrc) imgSrc = './image/placeholder-artwork.jpg';

        // Format artist name
        let artistName = 'Unknown Artist';
        if (artist && artist.name) {
            artistName = artist.name;
        } else if (artist && artist.first_name && artist.last_name) {
            artistName = `${artist.first_name} ${artist.last_name}`;
        } else if (artist && artist.first_name) {
            artistName = artist.first_name;
        }

        const isAvailable = art.is_available ? true : false;

        const itemEl = document.createElement('div');
        itemEl.className = 'wishlistItem cartItem'; // Use cart item styling
        itemEl.dataset.wishlistId = item.wishlist_id;
        itemEl.dataset.artworkId = art.artwork_id;
        itemEl.dataset.category = art.category || '';
        itemEl.dataset.price = finalPrice;

        itemEl.innerHTML = `
            <div class="cartItemImage wishlistItemImage">
                <img src="${imgSrc}" alt="${escapeHtml(art.title || 'Artwork')}" onerror="this.onerror=null;this.src='./image/placeholder-artwork.jpg';">
                ${onSale ? '<div class="saleTag">SALE</div>' : ''}
            </div>
            
            <div class="cartItemInfo wishlistItemInfo">
                <a href="artworkPreview.php?id=${art.artwork_id}" class="cartItemTitle wishlistItemTitle">${escapeHtml(art.title || '')}</a>
                <p class="cartItemArtist wishlistItemArtist">by ${escapeHtml(artistName)}</p>
                
                <div class="cartItemPrice wishlistItemPrice">
                    ${onSale ? `<span class="originalPrice">EGP ${price.toLocaleString()}</span>` : ''}
                    <span class="currentPrice">EGP ${finalPrice.toLocaleString()}</span>
                </div>
                
                <div class="cartItemDetails wishlistItemDetails">
                    ${art.category ? `<div class="cartItemDetail"><i class="fas fa-tag"></i> ${art.category}</div>` : ''}
                    ${art.dimensions ? `<div class="cartItemDetail"><i class="fas fa-ruler"></i> ${art.dimensions}</div>` : ''}
                    ${art.year ? `<div class="cartItemDetail"><i class="fas fa-calendar"></i> ${art.year}</div>` : ''}
                </div>
            </div>
            
            <div class="cartItemActions wishlistItemActions">
                <button class="addToCartBtn btn-primary" data-action="add-to-cart" data-artwork-id="${art.artwork_id}">
                    <i class="fas fa-shopping-cart"></i>
                    Add to Cart
                </button>
                
                <button class="removeBtn btn-secondary" data-action="remove" data-wishlist-id="${item.wishlist_id}">
                    <i class="fas fa-heart-broken"></i>
                    Remove from Wishlist
                </button>
            </div>
        `;

        container.appendChild(itemEl);
    });

    // Wire up event handlers for dynamically added items
    initializeItemActions();
}

// Simple HTML escaper to avoid injecting raw data
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// Wishlist Actions
function initializeWishlistActions() {
    const shareBtn = document.getElementById('shareWishlistBtn');
    const clearBtn = document.getElementById('clearWishlistBtn');

    if (shareBtn) {
        shareBtn.addEventListener('click', shareWishlist);
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', clearWishlist);
    }
}

function shareWishlist() {
    if (navigator.share) {
        navigator.share({
            title: 'My Yadawity Wishlist',
            text: 'Check out my curated collection of favorite artworks!',
            url: window.location.href
        }).catch(console.error);
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            showNotification('Wishlist link copied to clipboard!', 'success');
        }).catch(() => {
            showNotification('Unable to share wishlist', 'error');
        });
    }
}

function clearWishlist() {
    if (confirm('Are you sure you want to clear your entire wishlist? This action cannot be undone.')) {
        const wishlistItems = document.querySelectorAll('.wishlistItem');
        wishlistItems.forEach(item => {
            item.remove();
        });
        
        showEmptyState();
        updateWishlistStats();
        showNotification('Wishlist cleared successfully', 'info');
    }
}

// Filter functionality
function initializeFilters() {
    const categoryFilter = document.getElementById('categoryFilter');
    const sortFilter = document.getElementById('sortFilter');

    if (categoryFilter) {
        categoryFilter.addEventListener('change', filterWishlistItems);
    }

    if (sortFilter) {
        sortFilter.addEventListener('change', sortWishlistItems);
    }
}

function filterWishlistItems() {
    const categoryFilter = document.getElementById('categoryFilter');
    const selectedCategory = categoryFilter.value;
    const items = document.querySelectorAll('.wishlistItem');

    items.forEach(item => {
        const itemCategory = item.dataset.category;
        
        if (!selectedCategory || itemCategory === selectedCategory) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });

    updateVisibleItemsCount();
}

function sortWishlistItems() {
    const sortFilter = document.getElementById('sortFilter');
    const sortBy = sortFilter.value;
    const container = document.querySelector('.wishlistGrid');
    const items = Array.from(container.querySelectorAll('.wishlistItem'));

    items.sort((a, b) => {
        switch (sortBy) {
            case 'price-low':
                return parseInt(a.dataset.price) - parseInt(b.dataset.price);
            case 'price-high':
                return parseInt(b.dataset.price) - parseInt(a.dataset.price);
            case 'name':
                const titleA = a.querySelector('.wishlistTitle').textContent;
                const titleB = b.querySelector('.wishlistTitle').textContent;
                return titleA.localeCompare(titleB);
            case 'recent':
            default:
                return 0; // Keep original order for recent
        }
    });

    // Re-append sorted items
    items.forEach(item => container.appendChild(item));
}

// Item Actions
function initializeItemActions() {
    const container = document.querySelector('.wishlistContainer');
    if (!container) return;

    // Unified click handler for wishlist actions using event delegation
    container.addEventListener('click', function(e) {
        const button = e.target.closest('[data-action]');
        if (!button) return;

        e.preventDefault();
        e.stopPropagation();

        const action = button.dataset.action;
        const itemEl = button.closest('.wishlistItem');
        
        if (!itemEl) return;

        switch (action) {
            case 'remove':
                handleRemoveFromWishlist(button, itemEl);
                break;

            case 'add-to-cart':
                handleAddToCart(button, itemEl);
                break;
        }
    });
}

function handleRemoveFromWishlist(button, itemEl) {
    const wishlistId = button.dataset.wishlistId;
    const itemTitle = itemEl.querySelector('.wishlistItemTitle')?.textContent || 'this artwork';
    
    Swal.fire({
        title: 'Remove from Wishlist?',
        text: `Are you sure you want to remove "${itemTitle}" from your wishlist?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, remove it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            if (wishlistId) {
                removeFromWishlistAPI(wishlistId, itemTitle, itemEl);
            } else {
                console.error('No wishlist ID found for item removal');
            }
        }
    });
}

function handleAddToCart(button, itemEl) {
    const artworkId = button.dataset.artworkId;
    const itemTitle = itemEl.querySelector('.wishlistItemTitle')?.textContent || 'artwork';
    
    if (artworkId) {
        addToCartAPI(artworkId, itemTitle, button);
    } else {
        console.error('No artwork ID found for add to cart');
    }
}

async function removeFromWishlistAPI(wishlistId, itemTitle, itemEl) {
    try {
        const response = await fetch('./API/removeFromWishlist.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                wishlist_id: wishlistId
            })
        });

        // Check if response is ok and has JSON content
        if (!response.ok) {
            const text = await response.text();
            console.error('API Error Response:', text);
            throw new Error(`Server error: ${response.status} ${response.statusText}`);
        }

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Non-JSON Response:', text);
            throw new Error('Server returned non-JSON response');
        }

        const result = await response.json();
        
        if (result.success) {
            // Animate removal
            itemEl.style.transform = 'scale(0.8)';
            itemEl.style.opacity = '0';
            
            setTimeout(() => {
                itemEl.remove();
                // Use global navbar function for wishlist count
                if (typeof window.updateWishlistCount === 'function') {
                    window.updateWishlistCount();
                }
                checkEmptyState();
                
                Swal.fire({
                    title: 'Removed!',
                    text: `"${itemTitle}" has been removed from your wishlist.`,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            }, 300);
        } else {
            throw new Error(result.message || 'Failed to remove item');
        }
    } catch (error) {
        console.error('Error removing from wishlist:', error);
        Swal.fire({
            title: 'Error',
            text: 'Failed to remove item from wishlist. Please try again.',
            icon: 'error'
        });
    }
}

async function addToCartAPI(artworkId, itemTitle, button) {
    try {
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
        button.disabled = true;

        const response = await fetch('./API/addToCart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                artwork_id: artworkId,
                quantity: 1
            })
        });

        const result = await response.json();
        
        if (result.success) {
            // Show success state
            button.innerHTML = '<i class="fas fa-check"></i> Added!';
            button.style.background = '#22c55e';
            
            updateCartCount();
            
            Swal.fire({
                title: 'Added to Cart!',
                text: `"${itemTitle}" has been added to your cart.`,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
            
            // Reset button after 2 seconds
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.style.background = '';
                button.disabled = false;
            }, 2000);
        } else {
            throw new Error(result.message || 'Failed to add to cart');
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        button.innerHTML = originalHTML;
        button.disabled = false;
        
        Swal.fire({
            title: 'Error',
            text: 'Failed to add item to cart. Please try again.',
            icon: 'error'
        });
    }
}

// Stats and State Management
function updateWishlistStats() {
    const items = document.querySelectorAll('.wishlistItem');
    const itemCount = items.length;
    
    // Update items count
    const itemsCountElement = document.querySelector('.statsNumber');
    if (itemsCountElement) {
        itemsCountElement.textContent = itemCount;
    }
    
    // Calculate total value
    let totalValue = 0;
    items.forEach(item => {
        const price = parseInt(item.dataset.price) || 0;
        totalValue += price;
    });
    
    const totalValueElement = document.querySelectorAll('.statsNumber')[2];
    if (totalValueElement) {
        totalValueElement.textContent = `EGP ${totalValue.toLocaleString()}`;
    }
    
    // Update artists count (example logic)
    const artistsCount = new Set(
        Array.from(items).map(item => 
            item.querySelector('.wishlistArtist').textContent
        )
    ).size;
    
    const artistsCountElement = document.querySelectorAll('.statsNumber')[1];
    if (artistsCountElement) {
        artistsCountElement.textContent = artistsCount;
    }
}

function updateVisibleItemsCount() {
    const visibleItems = document.querySelectorAll('.wishlistItem[style*="block"], .wishlistItem:not([style*="none"])');
    const itemsCountElement = document.querySelector('.statsNumber');
    if (itemsCountElement) {
        itemsCountElement.textContent = visibleItems.length;
    }
}

function checkEmptyState() {
    const items = document.querySelectorAll('.wishlistItem');
    const emptyState = document.querySelector('.emptyWishlist');
    const wishlistGrid = document.querySelector('.wishlistGrid');
    
    if (items.length === 0) {
        showEmptyState();
    }
}

function showEmptyState() {
    const emptyState = document.querySelector('.emptyWishlist');
    const wishlistGrid = document.querySelector('.wishlistGrid');
    const wishlistStats = document.querySelector('.wishlistStats'); // May not exist
    const wishlistActions = document.querySelector('.wishlistActions');
    const loadingSpinner = document.querySelector('#wishlistLoading');
    
    // Hide loading spinner
    if (loadingSpinner) {
        loadingSpinner.style.display = 'none';
    }
    
    if (emptyState && wishlistGrid) {
        wishlistGrid.style.display = 'none';
        if (wishlistStats) {
            wishlistStats.style.display = 'none';
        }
        if (wishlistActions) {
            wishlistActions.style.display = 'none';
        }
        emptyState.style.display = 'block';
    }
}

// Utility Functions - Use the navbar functions for consistent cart/wishlist counts
// Remove local updateCartCount and updateWishlistCount functions to avoid conflicts
// The navbar.js file handles these functions properly with API integration

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#22c55e' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        z-index: 10000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Remove after delay
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Export functions for external use
window.wishlistFunctions = {
    addToWishlist: function(itemData) {
        // Function to add items to wishlist from other pages
        console.log('Adding to wishlist:', itemData);
    },
    removeFromWishlistAPI: removeFromWishlistAPI,
    clearWishlist: clearWishlist,
    updateWishlistStats: updateWishlistStats
};
