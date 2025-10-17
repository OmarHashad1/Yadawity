// Product Preview JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all functionality
    initializeImageGallery();
    initializeAddToCart();
    initializeWishlist();
    initializeSocialShare();
    initializeSimilarProducts();
    initializeNavbarIntegration();
    initializeBurgerMenuIntegration();
    
    // Add loading animations
    initializeLoadingAnimations();
    
    // Initialize smooth scrolling
    initializeSmoothScrolling();
});

// Image Gallery Functionality
function initializeImageGallery() {
    const thumbnails = document.querySelectorAll('.thumbnail');
    const mainImage = document.getElementById('mainImage');
    const zoomBtn = document.getElementById('zoomBtn');
    const photoNumberElement = document.getElementById('currentPhotoNumber');
    const prevBtn = document.getElementById('photoNavPrev');
    const nextBtn = document.getElementById('photoNavNext');
    
    // Function to update photo display
    function updatePhoto(index) {
        if (index < 0 || index >= thumbnails.length) return;
        
        // Remove active class from all thumbnails
        thumbnails.forEach(t => t.classList.remove('active'));
        
        // Add active class to selected thumbnail
        thumbnails[index].classList.add('active');
        
        // Change main image
        const newSrc = thumbnails[index].getAttribute('data-main');
        mainImage.src = newSrc;
        mainImage.alt = thumbnails[index].alt;
        
        // Update photo indicator number
        if (photoNumberElement) {
            photoNumberElement.textContent = index + 1;
        }
        
        // Update navigation buttons visibility
        updateNavigationButtons(index);
    }
    
    // Function to update navigation button states
    function updateNavigationButtons(activeIndex) {
        if (prevBtn) {
            if (activeIndex <= 0) {
                prevBtn.style.opacity = '0.3';
                prevBtn.style.pointerEvents = 'none';
            } else {
                prevBtn.style.opacity = '';
                prevBtn.style.pointerEvents = '';
            }
        }
        
        if (nextBtn) {
            if (activeIndex >= thumbnails.length - 1) {
                nextBtn.style.opacity = '0.3';
                nextBtn.style.pointerEvents = 'none';
            } else {
                nextBtn.style.opacity = '';
                nextBtn.style.pointerEvents = '';
            }
        }
    }
    
    // Function to get current active index
    function getCurrentActiveIndex() {
        return Array.from(thumbnails).findIndex(thumb => thumb.classList.contains('active'));
    }
    
    // Thumbnail click functionality
    thumbnails.forEach((thumbnail, index) => {
        thumbnail.addEventListener('click', function() {
            updatePhoto(index);
        });
    });
    
    // Navigation button functionality
    if (prevBtn) {
        prevBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const currentIndex = getCurrentActiveIndex();
            if (currentIndex > 0) {
                updatePhoto(currentIndex - 1);
            }
        });
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const currentIndex = getCurrentActiveIndex();
            if (currentIndex < thumbnails.length - 1) {
                updatePhoto(currentIndex + 1);
            }
        });
    }
    
    // Zoom functionality
    if (zoomBtn) {
        zoomBtn.addEventListener('click', function() {
            openImageModal(mainImage.src, mainImage.alt);
        });
    }
    
    // Click on main image to zoom
    if (mainImage) {
        mainImage.addEventListener('click', function() {
            openImageModal(this.src, this.alt);
        });
    }
    
    // Add keyboard navigation for photos
    document.addEventListener('keydown', function(e) {
        if (thumbnails.length > 1) {
            const activeIndex = getCurrentActiveIndex();
            
            if (e.key === 'ArrowLeft' && activeIndex > 0) {
                e.preventDefault();
                updatePhoto(activeIndex - 1);
            } else if (e.key === 'ArrowRight' && activeIndex < thumbnails.length - 1) {
                e.preventDefault();
                updatePhoto(activeIndex + 1);
            }
        }
    });
    
    // Initialize photo counter and navigation buttons on page load
    if (thumbnails.length > 0) {
        const initialActiveIndex = getCurrentActiveIndex();
        const startIndex = initialActiveIndex >= 0 ? initialActiveIndex : 0;
        
        if (photoNumberElement) {
            photoNumberElement.textContent = startIndex + 1;
        }
        
        updateNavigationButtons(startIndex);
    }
}

function openImageModal(src, alt) {
    // Create modal overlay
    const modal = document.createElement('div');
    modal.className = 'imageModal';
    modal.innerHTML = `
        <div class="imageModalOverlay">
            <div class="imageModalContainer">
                <button class="imageModalClose">
                    <i class="fas fa-times"></i>
                </button>
                <img src="${src}" alt="${alt}" class="imageModalImage">
            </div>
        </div>
    `;
    
    // Add modal styles
    const modalStyles = `
        .imageModal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.9);
            animation: fadeIn 0.3s ease;
        }
        
        .imageModalContainer {
            position: relative;
            max-width: 90vw;
            max-height: 90vh;
        }
        
        .imageModalImage {
            width: 100%;
            height: auto;
            max-width: 100%;
            max-height: 90vh;
            object-fit: contain;
        }
        
        .imageModalClose {
            position: absolute;
            top: -40px;
            right: 0;
            background: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: var(--text-dark);
            transition: all 0.3s ease;
        }
        
        .imageModalClose:hover {
            background-color: var(--red-accent);
            color: white;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    `;
    
    // Add styles if not already added
    if (!document.querySelector('#image-modal-styles')) {
        const styleSheet = document.createElement('style');
        styleSheet.id = 'image-modal-styles';
        styleSheet.textContent = modalStyles;
        document.head.appendChild(styleSheet);
    }
    
    // Add modal to page
    document.body.appendChild(modal);
    
    // Close modal functionality
    const closeBtn = modal.querySelector('.imageModalClose');
    const overlay = modal.querySelector('.imageModalOverlay');
    
    closeBtn.addEventListener('click', function() {
        document.body.removeChild(modal);
    });
    
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            document.body.removeChild(modal);
        }
    });
    
    // ESC key to close
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (document.body.contains(modal)) {
                document.body.removeChild(modal);
            }
        }
    });
}

// Add to Cart Functionality
function initializeAddToCart() {
    const addToCartBtn = document.getElementById('addToCartBtn');
    
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', async function() {
            const artworkId = this.getAttribute('data-artwork-id') || window.artworkId;
            const productTitle = document.querySelector('.productTitle').textContent;
            
            if (!artworkId) {
                console.log('Unable to add artwork to cart - no artwork ID');
                return;
            }
            
            // Show loading state
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            this.disabled = true;
            
            try {
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
                
                const data = await response.json();
                
                if (data.success) {
                    // Success state
                    this.innerHTML = '<i class="fas fa-check"></i> Added to Cart';
                    this.style.backgroundColor = 'var(--green-accent)';
                    // Item added to cart successfully
                    
                    // Update cart count
                    updateCartCount();
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-shopping-bag"></i> Add to Cart';
                        this.style.backgroundColor = 'var(--primary-brown)';
                        this.disabled = false;
                    }, 2000);
                } else {
                    // Error state
                    this.innerHTML = '<i class="fas fa-shopping-bag"></i> Add to Cart';
                    this.disabled = false;
                    console.log('Failed to add to cart:', data.message);
                }
            } catch (error) {
                console.error('Error adding to cart:', error);
                this.innerHTML = '<i class="fas fa-shopping-bag"></i> Add to Cart';
                this.disabled = false;
                // Error adding to cart - check console for details
            }
        });
    }
}

// Wishlist Functionality
function initializeWishlist() {
    const wishlistBtn = document.getElementById('wishlistBtn');
    
    if (wishlistBtn) {
        wishlistBtn.addEventListener('click', async function() {
            const artworkId = this.getAttribute('data-artwork-id') || window.artworkId;
            const productTitle = document.querySelector('.productTitle').textContent;
            
            if (!artworkId) {
                console.log('Unable to add artwork to wishlist - no artwork ID');
                return;
            }
            
            try {
                const response = await fetch('./API/addToWishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        artwork_id: artworkId
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.classList.add('active');
                    this.innerHTML = '<i class="fas fa-heart"></i>';
                    // Item added to wishlist successfully
                } else {
                    console.log('Failed to add to wishlist:', data.message);
                }
            } catch (error) {
                console.error('Error adding to wishlist:', error);
                // Error adding to wishlist - check console for details
            }
        });
    }
}

// Social Share Functionality
function initializeSocialShare() {
    const shareButtons = document.querySelectorAll('.shareBtn');
    
    shareButtons.forEach(button => {
        button.addEventListener('click', function() {
            const platform = this.getAttribute('data-platform');
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.querySelector('.productTitle').textContent);
            const description = encodeURIComponent(document.querySelector('.productDescription p').textContent);
            
            let shareUrl = '';
            
            switch (platform) {
                case 'facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
                    break;
                case 'twitter':
                    shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${title}`;
                    break;
                case 'pinterest':
                    const imageUrl = encodeURIComponent(document.querySelector('.mainImage').src);
                    shareUrl = `https://pinterest.com/pin/create/button/?url=${url}&media=${imageUrl}&description=${title}`;
                    break;
                case 'whatsapp':
                    shareUrl = `https://wa.me/?text=${title}%20${url}`;
                    break;
            }
            
            if (shareUrl) {
                window.open(shareUrl, '_blank', 'width=600,height=400,scrollbars=yes,resizable=yes');
            }
        });
    });
}

// Similar Products Functionality
function initializeSimilarProducts() {
    // Load similar products dynamically
    loadSimilarProducts();
}

// Load similar products from API
async function loadSimilarProducts() {
    const similarProductsGrid = document.getElementById('similarProductsGrid');
    
    try {
        const response = await fetch('./API/getRandomArtworks.php');
        const data = await response.json();
        
        if (data.success && data.data && data.data.length > 0) {
            // Filter out current artwork if present
            const currentArtworkId = window.artworkId;
            const similarArtworks = data.data.filter(artwork => artwork.artwork_id !== currentArtworkId);
            
            // Limit to 4 similar products
            const limitedArtworks = similarArtworks.slice(0, 4);
            
            // Generate HTML for similar products using the same structure as homepage
            const similarProductsHTML = limitedArtworks.map(artwork => {
                const artworkImage = artwork.artwork_image || './image/placeholder-artwork.jpg';
                const title = artwork.title || 'Untitled';
                const price = artwork.price || '0.00';
                const artistName = artwork.artist_name || 'Unknown Artist';
                const description = artwork.description || 'Beautiful artwork piece.';
                const artworkId = artwork.artwork_id;
                
                return `
                    <div class="artworkCard" data-artwork-id="${artworkId}">
                        <div class="artwork-image-container">
                            <img src="${artworkImage}" alt="${title}" class="artworkImage" loading="lazy">
                            <div class="artwork-overlay">
                                <div class="quick-actions">
                                    <button class="quick-action-btn" onclick="window.location.href='artworkPreview.php?id=${artworkId}'" title="View Artwork">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="artworkInfo">
                            <h3 class="artworkTitle">${title}</h3>
                            <p class="artworkPrice">EGP ${parseFloat(price).toFixed(0)}</p>
                            <p class="artworkArtist">by ${artistName}</p>
                            <p class="artworkDescription">${description}</p>
                            <div class="artworkActions">
                                <button class="addToCart" onclick="addToCartSimilar(${artworkId})">
                                    Add to Cart
                                </button>
                                <button class="wishlistBtn" onclick="addToWishlistSimilar(${artworkId})" data-artwork-id="${artworkId}" title="Add to Wishlist">
                                    <i class="far fa-heart"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            
            similarProductsGrid.innerHTML = similarProductsHTML;
            
        } else {
            similarProductsGrid.innerHTML = `
                <div class="no-products">
                    <p>No similar artworks found at the moment.</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading similar products:', error);
        similarProductsGrid.innerHTML = `
            <div class="error-message">
                <p>Unable to load similar artworks. Please try again later.</p>
            </div>
        `;
    }
}

// Add to cart functionality for similar products
async function addToCartSimilar(artworkId) {
    if (!artworkId) {
        console.error('Artwork ID is required');
        return;
    }

    try {
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

        const data = await response.json();
        
        if (data.success) {
            // Show success message
            console.log('Item added to cart successfully!');
            
            // Update cart count if function exists
            if (typeof updateCartCount === 'function') {
                updateCartCount();
            }
        } else {
            console.log('Failed to add item to cart:', data.message);
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        // An error occurred - check console for details
    }
}

// Add to wishlist functionality for similar products
async function addToWishlistSimilar(artworkId) {
    if (!artworkId) {
        console.error('Artwork ID is required for wishlist operation');
        return;
    }

    // Find the specific wishlist button for this artwork
    const wishlistBtn = document.querySelector(`button[data-artwork-id="${artworkId}"]`);
    if (!wishlistBtn) {
        console.error('Wishlist button not found for artwork ID:', artworkId);
        return;
    }

    const heartIcon = wishlistBtn.querySelector('i');
    if (!heartIcon) {
        console.error('Heart icon not found in wishlist button');
        return;
    }

    try {
        const response = await fetch('./API/addToWishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                artwork_id: artworkId
            })
        });

        const data = await response.json();
        
        if (data.success) {
            // Visual feedback - fill the heart
            heartIcon.classList.remove('far');
            heartIcon.classList.add('fas');
            wishlistBtn.style.color = '#e74c3c';
            
            // Show success message
            console.log('Added to wishlist!');
            
            // Optional: disable button temporarily
            wishlistBtn.disabled = true;
            setTimeout(() => {
                if (wishlistBtn) wishlistBtn.disabled = false;
            }, 1000);
            
        } else {
            console.log('Failed to add to wishlist:', data.message);
        }
    } catch (error) {
        console.error('Error adding to wishlist:', error);
        // An error occurred - check console for details
    }
}

// Navbar Integration
function initializeNavbarIntegration() {
    // Update cart count display
    updateCartCount();
    
    // Handle search functionality
    const searchInput = document.getElementById('navbar-search');
    const searchButton = document.getElementById('search-button');
    
    if (searchInput && searchButton) {
        searchButton.addEventListener('click', function(e) {
            e.preventDefault();
            performSearch(searchInput.value);
        });
        
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch(this.value);
            }
        });
    }
}

// Burger Menu Integration
function initializeBurgerMenuIntegration() {
    const navToggle = document.getElementById('nav-toggle');
    const burgerMenuOverlay = document.getElementById('burgerMenuOverlay');
    const burgerMenuClose = document.getElementById('burgerMenuClose');
    const burgerSearchInput = document.getElementById('burgerSearchInput');
    const burgerSearchButton = document.getElementById('burgerSearchButton');
    
    // Handle burger menu search
    if (burgerSearchInput && burgerSearchButton) {
        burgerSearchButton.addEventListener('click', function(e) {
            e.preventDefault();
            performSearch(burgerSearchInput.value);
        });
        
        burgerSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch(this.value);
            }
        });
    }
}

// Loading Animations
function initializeLoadingAnimations() {
    // Add fade-in animation to product content
    const productContainer = document.querySelector('.productContainer');
    if (productContainer) {
        productContainer.style.opacity = '0';
        productContainer.style.transform = 'translateY(20px)';
        productContainer.style.transition = 'all 0.6s ease';
        
        setTimeout(() => {
            productContainer.style.opacity = '1';
            productContainer.style.transform = 'translateY(0)';
        }, 100);
    }
}

// Smooth Scrolling
function initializeSmoothScrolling() {
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Utility Functions
// showNotification function removed - notifications disabled
/*
function showNotification(message, type = 'info') {
    // Function disabled - notifications removed per user request
}
*/

function updateCartCount() {
    // Update cart count display in navbar
    const cartCountElement = document.querySelector('.cart-count');
    if (cartCountElement) {
        // This would typically fetch from an API or localStorage
        // For now, we'll just increment the existing count
        const currentCount = parseInt(cartCountElement.textContent) || 0;
        cartCountElement.textContent = currentCount;
    }
}

function performSearch(query) {
    if (query.trim()) {
        // Redirect to gallery page with search query
        window.location.href = `gallery.php?search=${encodeURIComponent(query.trim())}`;
    }
}
