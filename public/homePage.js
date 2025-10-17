// Homepage specific functionality
document.addEventListener('DOMContentLoaded', function() {
    // Check if elements exist
    const loadingElement = document.getElementById('artistsLoading');
    const container = document.getElementById('artistCardSection');
    
    // Load random artists for the homepage
    loadRandomArtists();
    
    // Load random artworks for the homepage
    loadRandomArtworks();
    
    // Initialize wishlist manager
    if (typeof WishlistManager !== 'undefined') {
        window.wishlistManager = new WishlistManager();
    }
    
    // Initialize testimonials carousel with delay to ensure DOM is ready
    setTimeout(() => {
        if (typeof initializeTestimonialsCarousel === 'function') {
            initializeTestimonialsCarousel();
        } else if (typeof testimonialsCarousel !== 'undefined') {
            new testimonialsCarousel();
        }
    }, 200);
});

// Load random artists
async function loadRandomArtists() {
    const loadingElement = document.getElementById('artistsLoading');
    const container = document.getElementById('artistCardSection');
    
    if (!container) {
        return;
    }
    
    try {
        const response = await fetch('./API/getRandomArtists.php');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success && data.data && data.data.length > 0) {
            // Hide loading spinner
            if (loadingElement) {
                loadingElement.style.display = 'none';
            }
            
            // Create artist cards
            const artistsHTML = data.data.map(artist => createArtistCard(artist)).join('');
            
            container.innerHTML = artistsHTML;
            
            // Check if cards are still there after a delay
            setTimeout(() => {
                if (container.children.length === 0) {
                    // Cards disappeared
                }
            }, 1000);
            
            setTimeout(() => {
                if (container.children.length === 0) {
                    // Cards still missing after 3 seconds
                }
            }, 3000);
            
        } else {
            throw new Error(data.message || 'No artists found');
        }
        
    } catch (error) {
        // Hide loading spinner and show error
        if (loadingElement) {
            loadingElement.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Failed to load artists. Please try again later.</p>
                </div>
            `;
        }
    }
}

// Create HTML for artist card
function createArtistCard(artist) {
    const profileImage = artist.profile_picture || './image/default-avatar.jpg';
    const bio = artist.bio || artist.artist_bio || 'Talented artist creating beautiful works.';
    const location = artist.location || 'Location not specified';
    const artistName = artist.name || `${artist.first_name} ${artist.last_name}` || 'Unknown Artist';
    const rating = artist.average_rating ? artist.average_rating.toFixed(1) : '0.0';
    const workCount = artist.artwork_count || 0;
    
    return `
        <div class="profileCard visible" data-artist-id="${artist.user_id}">
            <div class="profileHeader">
                <img src="${profileImage}" alt="${artistName}" class="profileImage" loading="lazy">
                <div class="ratingBadge">
                    <div class="starsContainer">
                        ${generateStars(artist.average_rating || 0)}
                    </div>
                    <span class="ratingText">${rating}</span>
                </div>
                <div class="academyBadge">
                    ${artist.badge || 'YADAWITY PARTNER'}
                </div>
            </div>
            <div class="profileContent">
                <h3 class="profileName">${artistName}</h3>
                <p class="profileSpecialty">${artist.specialty || 'Artist'}</p>
                <div class="profileStats">
                    <span class="masterpiecesCount">${workCount} Works</span>
                </div>
                <button class="viewPortfolioBtn" onclick="window.location.href='artistProfile.php?id=${artist.user_id}'">
                    View Portfolio
                </button>
            </div>
        </div>
    `;
}

// Generate star rating HTML
function generateStars(rating) {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    let starsHTML = '';
    
    for (let i = 0; i < 5; i++) {
        if (i < fullStars) {
            starsHTML += '<span class="star">★</span>';
        } else if (i === fullStars && hasHalfStar) {
            starsHTML += '<span class="star">☆</span>';
        } else {
            starsHTML += '<span class="star empty">☆</span>';
        }
    }
    
    return starsHTML;
}

// Load random artworks
async function loadRandomArtworks() {
    const loadingElement = document.getElementById('artworksLoading');
    const container = document.getElementById('artworkCardSection');
    
    if (!container) {
        return;
    }
    
    try {
        const response = await fetch('./API/getRandomArtworks.php');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success && data.data && data.data.length > 0) {
            // Hide loading spinner
            if (loadingElement) {
                loadingElement.style.display = 'none';
            }
            
            // Create artwork cards
            const artworksHTML = data.data.map(artwork => createArtworkCard(artwork)).join('');
            
            container.innerHTML = artworksHTML;
            
        } else {
            throw new Error(data.message || 'No artworks found');
        }
        
    } catch (error) {
        // Hide loading spinner and show error
        if (loadingElement) {
            loadingElement.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Failed to load artworks. Please try again later.</p>
                </div>
            `;
        }
    }
}

// Create HTML for artwork card
function createArtworkCard(artwork) {
    const artworkImage = artwork.artwork_image || './image/placeholder-artwork.jpg';
    const title = artwork.title || 'Untitled';
    const price = artwork.price || '0.00';
    const artistName = artwork.artist_name || 'Unknown Artist';
    const description = artwork.description || 'Beautiful artwork piece.';
    const artworkId = artwork.artwork_id;
    const artistId = artwork.artist_id;
    
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
                <p class="artworkPrice">$${price}</p>
                <p class="artworkArtist">by ${artistName}</p>
                <p class="artworkDescription">${description}</p>
                <div class="artworkActions">
                    <button class="addToCart" onclick="addToCart(${artworkId})">
                        Add to Cart
                    </button>
                    <button class="wishlistBtn" onclick="addToWishlist(${artworkId})" data-artwork-id="${artworkId}" title="Add to Wishlist">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
}

// ==========================================
// WISHLIST FUNCTIONALITY FOR HOMEPAGE
// ==========================================

// Global function to handle wishlist addition with visual feedback (similar to artwork.js)
async function addToWishlist(artworkId) {
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

    // Store original state in case of error
    const originalClass = heartIcon.className;
    const originalColor = wishlistBtn.style.color;

    try {
        // Provide immediate visual feedback
        heartIcon.className = 'fas fa-heart';
        wishlistBtn.style.color = '#e74c3c';
        wishlistBtn.disabled = true;

        // Call the API
        const response = await fetch('./API/addToWishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                artwork_id: artworkId,
                quantity: 1,
                type: 'artwork'
            })
        });

        const data = await response.json();

        if (data.success) {
            // Show success message using SweetAlert
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Added to Wishlist!',
                    text: 'Item added to wishlist successfully',
                    icon: 'success',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#8B4513',
                    showConfirmButton: true,
                    allowOutsideClick: false,
                    allowEscapeKey: false
                });
            }

            // Keep the filled heart and red color
            heartIcon.className = 'fas fa-heart';
            wishlistBtn.style.color = '#e74c3c';
            
            // Update wishlist counter if function exists
            if (typeof updateWishlistCount === 'function') {
                updateWishlistCount();
            }

            // Change the onclick to remove from wishlist
            wishlistBtn.setAttribute('onclick', `removeFromWishlist(${artworkId})`);
            wishlistBtn.setAttribute('title', 'Remove from Wishlist');
        } else {
            // Revert visual changes on error
            heartIcon.className = originalClass;
            wishlistBtn.style.color = originalColor;
            
            // Show error message
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Error',
                    text: data.message || 'Failed to add to wishlist.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            } else {
                alert(data.message || 'Failed to add to wishlist.');
            }
        }
    } catch (error) {
        console.error('Error adding to wishlist:', error);
        
        // Revert visual changes on error
        heartIcon.className = originalClass;
        wishlistBtn.style.color = originalColor;
        
        // Show error message
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Error',
                text: 'An unexpected error occurred. Please try again.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        } else {
            alert('An error occurred. Please try again.');
        }
    } finally {
        wishlistBtn.disabled = false;
    }
}

// Function to remove from wishlist (placeholder for future implementation)
async function removeFromWishlist(artworkId) {
    const wishlistBtn = document.querySelector(`button[data-artwork-id="${artworkId}"]`);
    if (!wishlistBtn) return;

    const heartIcon = wishlistBtn.querySelector('i');
    if (!heartIcon) return;

    // For now, just revert to outline heart
    heartIcon.className = 'far fa-heart';
    wishlistBtn.style.color = '';
    wishlistBtn.setAttribute('onclick', `addToWishlist(${artworkId})`);
    wishlistBtn.setAttribute('title', 'Add to Wishlist');

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Removed from Wishlist',
            text: 'Item removed from wishlist successfully',
            icon: 'info',
            confirmButtonText: 'OK',
            confirmButtonColor: '#8B4513',
            showConfirmButton: true,
            allowOutsideClick: false,
            allowEscapeKey: false
        });
    }
}

// Legacy function for backward compatibility
function toggleWishlist(artworkId) {
    // Redirect to the new addToWishlist function
    addToWishlist(artworkId);
}

