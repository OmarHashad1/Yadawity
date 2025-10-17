// Artist Profile JavaScript

// Function to hide review section if user is viewing their own profile or is not logged in
function hideReviewSectionIfOwnProfile() {
  let shouldHideReviews = false;
  let messageTitle = '';
  let messageText = '';
  let iconClass = '';

  // Check if this is the artist's own profile
  if (window.IS_OWN_PROFILE === true) {
    shouldHideReviews = true;
    messageTitle = 'This is Your Profile';
    messageText = 'You\'re viewing your own artist profile. Reviews from collectors will appear here as you receive them.';
    iconClass = 'fas fa-user-circle';
  }
  // Check if user is not logged in
  else if (window.IS_USER_LOGGED_IN === false) {
    shouldHideReviews = true;
    messageTitle = 'Sign In to Leave Reviews';
    messageText = 'Create an account or sign in to view reviews and share your experience with this artist\'s work.';
    iconClass = 'fas fa-sign-in-alt';
  }

  if (shouldHideReviews) {
    const reviewSection = document.querySelector('.reviews-section');
    if (reviewSection) {
      reviewSection.style.display = 'none';
      console.log('Review section hidden -', window.IS_OWN_PROFILE ? 'artist viewing own profile' : 'guest user');
    }
    
    // Also hide the review form specifically if the section selector doesn't work
    const reviewFormSection = document.querySelector('.review-form-section');
    if (reviewFormSection) {
      reviewFormSection.style.display = 'none';
    }
    
    // Add a message where reviews would be
    const reviewsContainer = document.querySelector('.reviews-container');
    if (reviewsContainer) {
      const messageElement = document.createElement('div');
      messageElement.className = 'profile-message';
      messageElement.innerHTML = `
        <div style="text-align: center; padding: 40px 20px; background: linear-gradient(135deg, #f8f9fa, #e9ecef); border-radius: 15px; margin: 20px 0;">
          <div style="font-size: 48px; color: #D4AF37; margin-bottom: 15px;">
            <i class="${iconClass}"></i>
          </div>
          <h3 style="color: #2c3e50; margin-bottom: 10px;">${messageTitle}</h3>
          <p style="color: #6c757d; font-size: 16px; max-width: 400px; margin: 0 auto;">
            ${messageText}
          </p>
          ${window.IS_USER_LOGGED_IN === false ? `
            <div style="margin-top: 20px;">
              <a href="login.php" style="background: #D4AF37; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; display: inline-block; font-weight: 600; transition: all 0.3s ease;">
                Sign In
              </a>
              <a href="signup.php" style="background: transparent; color: #D4AF37; padding: 12px 24px; border: 2px solid #D4AF37; border-radius: 8px; text-decoration: none; display: inline-block; font-weight: 600; margin-left: 10px; transition: all 0.3s ease;">
                Create Account
              </a>
            </div>
          ` : ''}
        </div>
      `;
      reviewsContainer.innerHTML = '';
      reviewsContainer.appendChild(messageElement);
    }
  }
}

// Function to get artist ID from URL or global variable
function getArtistIdFromUrl() {
  // First try to get from global variable set by PHP
  if (window.ARTIST_ID) {
    return window.ARTIST_ID;
  }
  
  // Fallback to URL parameters
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get('id') || urlParams.get('artist_id');
}

document.addEventListener("DOMContentLoaded", () => {
  // Check if user is viewing their own profile and hide review section if so
  hideReviewSectionIfOwnProfile()
  
  // Load artist profile data
  loadArtistProfile()
  
  // Initialize functionality that doesn't depend on dynamic content
  initializeStarRating()
  initializeReviewForm()
  initializeScrollAnimations()
  
  // Note: Wishlist, QuickView, AddToCart, and ImageLazyLoading will be initialized 
  // after artwork cards are loaded in renderArtistArtworks()
})

function loadArtistProfile() {
  const artistId = getArtistIdFromUrl()
  if (!artistId) {
    showNotification('No artist specified. Please select an artist to view their profile.', 'error')
    // Hide loading placeholder and show error message
    const container = document.getElementById('artist-artworks-container')
    if (container) {
      container.innerHTML = '<div class="error-message"><p>No artist selected. Please go back and select an artist.</p></div>'
    }
    return
  }
  
  // Fetch artist info using the new API endpoint
  fetch(`./API/getArtistById.php?artist_id=${artistId}`)
    .then(res => {
      if (!res.ok) {
        throw new Error(`HTTP error! status: ${res.status}`);
      }
      return res.text();
    })
    .then(text => {
      if (!text.trim()) {
        throw new Error('Empty response from getArtistById API');
      }
      
      if (text.trim().startsWith('<')) {
        throw new Error('Server returned HTML instead of JSON');
      }
      
      const data = JSON.parse(text);
      if (data.success && data.data) {
        renderArtistInfo(data.data)
        // Fetch artworks
        fetch(`./API/getAllArtworks.php?artist_id=${artistId}`)
          .then(res => {
            if (!res.ok) {
              throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.text();
          })
          .then(text => {
            if (!text.trim()) {
              throw new Error('Empty response from getAllArtworks API');
            }
            
            if (text.trim().startsWith('<')) {
              throw new Error('Server returned HTML instead of JSON');
            }
            
            const artworksData = JSON.parse(text);
            if (artworksData.success && artworksData.data) {
              renderArtistArtworks(artworksData.data)
            } else {
              // Show no artworks message
              const container = document.getElementById('artist-artworks-container')
              if (container) {
                container.innerHTML = '<div class="no-artworks-message"><p>This artist hasn\'t uploaded any artworks yet.</p></div>'
              }
            }
          })
          .catch(error => {
            console.error('Error loading artworks:', error)
            const container = document.getElementById('artist-artworks-container')
            if (container) {
              container.innerHTML = '<div class="error-message"><p>Error loading artworks. Please try again.</p></div>'
            }
          })
        // Fetch achievements
        fetch(`./API/getAchievements.php?artist_id=${artistId}`)
          .then(res => {
            if (!res.ok) {
              throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.text();
          })
          .then(text => {
            if (!text.trim()) {
              console.warn('Empty response from getAchievements API');
              showNoAchievements();
              return;
            }
            
            if (text.trim().startsWith('<')) {
              console.error('Server returned HTML instead of JSON for achievements');
              showNoAchievements();
              return;
            }
            
            const achievementsData = JSON.parse(text);
            if (achievementsData.success && achievementsData.data && achievementsData.data.length > 0) {
              renderArtistAchievements(achievementsData.data)
            } else {
              showNoAchievements()
            }
          })
          .catch(error => {
            console.error('Error loading achievements:', error)
            showNoAchievements()
          })
        // Load and display reviews for the artist
        // loadArtistReviews(artistId) // Reviews cards removed
        
        // Load artist review statistics
        loadArtistReviewStats(artistId)
      } else {
        showNotification('Artist not found.', 'error')
        // Show artist not found message
        const container = document.getElementById('artist-artworks-container')
        if (container) {
          container.innerHTML = '<div class="error-message"><p>Artist not found. Please check the link and try again.</p></div>'
        }
      }
    })
    .catch(error => {
      console.error('Error loading artist profile:', error)
      showNotification('Error loading artist profile. Please try again.', 'error')
      const container = document.getElementById('artist-artworks-container')
      if (container) {
        container.innerHTML = '<div class="error-message"><p>Error loading artist profile. Please check your connection and try again.</p></div>'
      }
    })
}

function renderArtistInfo(artist) {
  document.getElementById('artist-main-image').src = artist.profile_picture || './image/artist-sitting-on-the-floor.jpg'
  document.getElementById('artist-name').textContent = artist.full_name || 'Artist Name'
  
  // Handle specialty and remove "contemporary"
  let specialty = artist.art_specialty || '-';
  if (specialty.toLowerCase().includes('contemporary')) {
    specialty = specialty.replace(/contemporary/gi, '').trim();
    if (!specialty || specialty === '') {
      specialty = 'Artist';
    }
  }
  document.getElementById('artist-specialty').textContent = specialty;
  document.getElementById('artist-artwork-count').textContent = artist.artwork_count || '0'
  document.getElementById('artist-experience').textContent = artist.years_of_experience || '0'
  document.getElementById('artist-review-count').textContent = artist.review_count || '0'
  document.getElementById('about-artist-title').textContent = `About ${artist.full_name || 'Artist'}`
  document.getElementById('artist-bio').textContent = artist.bio || artist.artist_bio || 'No biography available.'
  
  // Update signature
  const signatureElement = document.getElementById('artist-signature');
  if (signatureElement) {
    signatureElement.textContent = artist.full_name || 'Artist';
  }
  
  // Update stats in sidebar
  const aboutArtworkCount = document.getElementById('about-artwork-count');
  if (aboutArtworkCount) {
    aboutArtworkCount.textContent = artist.artwork_count || '0';
  }
  
  const aboutExperience = document.getElementById('about-experience');
  if (aboutExperience) {
    aboutExperience.textContent = artist.years_of_experience || '0';
  }
  
  const aboutReviews = document.getElementById('about-reviews');
  if (aboutReviews) {
    aboutReviews.textContent = artist.review_count || '0';
  }
  
  // Update reviews section
  updateReviewsSection(artist);
}

function updateReviewsSection(artist) {
  // Update reviews header
  const reviewsTitleElement = document.getElementById('reviews-section-title');
  const reviewsSubtitleElement = document.getElementById('reviews-section-subtitle');
  
  if (reviewsTitleElement) {
    reviewsTitleElement.textContent = `${artist.full_name || 'Artist'} Reviews`;
  }
  
  if (reviewsSubtitleElement) {
    reviewsSubtitleElement.textContent = `See what collectors and art enthusiasts are saying about ${artist.full_name || 'this artist'}'s work`;
  }
  
  // Update overall rating
  const ratingElement = document.getElementById('artist-rating');
  const reviewCountElement = document.getElementById('artist-total-reviews');
  const starsElement = document.getElementById('artist-rating-stars');
  
  if (ratingElement) {
    const rating = artist.average_rating || artist.rating || 0;
    ratingElement.textContent = rating > 0 ? Number(rating).toFixed(1) : '0.0';
  }
  
  if (reviewCountElement) {
    reviewCountElement.textContent = artist.review_count || '0';
  }
  
  if (starsElement) {
    const rating = artist.average_rating || artist.rating || 0;
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    
    let starsHTML = '';
    
    // Add full stars
    for (let i = 0; i < fullStars; i++) {
      starsHTML += '<i class="fas fa-star"></i>';
    }
    
    // Add half star if needed
    if (hasHalfStar) {
      starsHTML += '<i class="fas fa-star-half-alt"></i>';
    }
    
    // Add empty stars to make 5 total
    const remainingStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
    for (let i = 0; i < remainingStars; i++) {
      starsHTML += '<i class="far fa-star"></i>';
    }
    
    starsElement.innerHTML = starsHTML;
  }
}

// Load artist review statistics from artist_reviews table
function loadArtistReviewStats(artistId) {
  fetch(`./API/getArtistReviewStats.php?artist_id=${artistId}`)
    .then(res => {
      if (!res.ok) {
        throw new Error(`HTTP error! status: ${res.status}`);
      }
      return res.text();
    })
    .then(text => {
      if (!text.trim()) {
        console.warn('Empty response from getArtistReviewStats API');
        updateReviewStatsDisplay(0.0, 0);
        return;
      }
      
      if (text.trim().startsWith('<')) {
        console.error('Server returned HTML instead of JSON for review stats');
        updateReviewStatsDisplay(0.0, 0);
        return;
      }
      
      try {
        const data = JSON.parse(text);
        if (data.success && data.data) {
          updateReviewStatsDisplay(data.data.average_rating, data.data.total_reviews);
        } else {
          // If no reviews found, show 0.0 rating
          updateReviewStatsDisplay(0.0, 0);
        }
      } catch (parseError) {
        console.error('JSON parse error for review stats:', parseError, 'Response:', text);
        updateReviewStatsDisplay(0.0, 0);
      }
    })
    .catch(error => {
      console.error('Error loading review stats:', error);
      // Fallback to 0.0 rating on error
      updateReviewStatsDisplay(0.0, 0);
    });
}

// Update the review statistics display
function updateReviewStatsDisplay(averageRating, totalReviews) {
  // Update rating number
  const ratingElement = document.getElementById('artist-rating');
  if (ratingElement) {
    ratingElement.textContent = averageRating.toFixed(1);
  }
  
  // Update total reviews count
  const totalReviewsElement = document.getElementById('artist-total-reviews');
  if (totalReviewsElement) {
    totalReviewsElement.textContent = totalReviews;
  }
  
  // Update stars display
  const starsElement = document.getElementById('artist-rating-stars');
  if (starsElement) {
    starsElement.innerHTML = generateStarsHTML(averageRating);
  }
  
  // Update stats in hero section
  const heroReviewCount = document.getElementById('artist-review-count');
  if (heroReviewCount) {
    heroReviewCount.textContent = totalReviews;
  }
  
  // Update stats in about section
  const aboutReviews = document.getElementById('about-reviews');
  if (aboutReviews) {
    aboutReviews.textContent = totalReviews;
  }
}

// Generate stars HTML based on rating
function generateStarsHTML(rating) {
  let starsHTML = '';
  const fullStars = Math.floor(rating);
  const hasHalfStar = rating % 1 >= 0.5;
  
  for (let i = 0; i < 5; i++) {
    if (i < fullStars) {
      starsHTML += '<i class="fas fa-star"></i>';
    } else if (i === fullStars && hasHalfStar) {
      starsHTML += '<i class="fas fa-star-half-alt"></i>';
    } else {
      starsHTML += '<i class="far fa-star"></i>';
    }
  }
  
  return starsHTML;
}

// Load and display reviews for the artist
function loadArtistReviews(artistId) {
  fetch(`./API/getReviews.php?artist_id=${artistId}`)
    .then(res => {
      if (!res.ok) {
        throw new Error(`HTTP error! status: ${res.status}`);
      }
      return res.text();
    })
    .then(text => {
      if (!text.trim()) {
        console.warn('Empty response from getReviews API');
        updateReviewStats([]);
        return;
      }
      
      if (text.trim().startsWith('<')) {
        console.error('Server returned HTML instead of JSON for reviews');
        updateReviewStats([]);
        return;
      }
      
      const data = JSON.parse(text);
      if (data.success && data.reviews && data.reviews.length > 0) {
        renderReviews(data.reviews);
        updateReviewStats(data.reviews);
      } else {
        // No reviews to display - reviews section will only show rating stats and form
        updateReviewStats([]);
      }
    })
    .catch(error => {
      console.error('Error loading reviews:', error);
      updateReviewStats([]);
    });
}

// Update review statistics based on reviews data
function updateReviewStats(reviews) {
  const reviewCountElement = document.getElementById('artist-total-reviews');
  const ratingElement = document.getElementById('artist-rating');
  const starsElement = document.getElementById('artist-rating-stars');
  const aboutReviewsElement = document.getElementById('about-reviews');
  const artistReviewCountElement = document.getElementById('artist-review-count');
  
  const totalReviews = reviews.length;
  let averageRating = 0;
  
  if (totalReviews > 0) {
    const totalRating = reviews.reduce((sum, review) => sum + review.rating, 0);
    averageRating = totalRating / totalReviews;
  }
  
  // Update review count
  if (reviewCountElement) {
    reviewCountElement.textContent = totalReviews;
  }
  if (aboutReviewsElement) {
    aboutReviewsElement.textContent = totalReviews;
  }
  if (artistReviewCountElement) {
    artistReviewCountElement.textContent = totalReviews;
  }
  
  // Update average rating
  if (ratingElement) {
    const displayRating = totalReviews > 0 ? averageRating.toFixed(1) : '0.0';
    ratingElement.textContent = displayRating;
  }
  
  // Update stars
  if (starsElement) {
    const fullStars = Math.floor(averageRating);
    const hasHalfStar = averageRating % 1 >= 0.5;
    
    let starsHTML = '';
    
    // Add full stars
    for (let i = 0; i < fullStars; i++) {
      starsHTML += '<i class="fas fa-star"></i>';
    }
    
    // Add half star if needed
    if (hasHalfStar) {
      starsHTML += '<i class="fas fa-star-half-alt"></i>';
    }
    
    // Add empty stars to make 5 total
    const remainingStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
    for (let i = 0; i < remainingStars; i++) {
      starsHTML += '<i class="far fa-star"></i>';
    }
    
    starsElement.innerHTML = starsHTML;
  }
}

// Render reviews in the reviews grid
function renderReviews(reviews) {
  // Get the reviews grid container
  const reviewsGrid = document.querySelector('.reviews-grid');
  if (!reviewsGrid) {
    console.error('Reviews grid container not found');
    return;
  }
  
  // Clear existing reviews
  reviewsGrid.innerHTML = '';
  
  if (reviews.length === 0) {
    reviewsGrid.innerHTML = '<p class="no-reviews">No reviews yet. Be the first to leave a review!</p>';
    return;
  }
  
  reviews.forEach(review => {
    const reviewCard = document.createElement('div');
    reviewCard.className = 'review-card';
    reviewCard.setAttribute('data-review-id', review.review_id);
    
    // Generate stars for the rating
    let starsHTML = '';
    for (let i = 1; i <= 5; i++) {
      if (i <= review.rating) {
        starsHTML += '<i class="fas fa-star"></i>';
      } else {
        starsHTML += '<i class="far fa-star"></i>';
      }
    }
    
    // Format date
    const reviewDate = new Date(review.created_at);
    const timeAgo = getTimeAgo(reviewDate);
    
    // Check if this review belongs to the current user
    const currentUserId = getCurrentUserId(); // You'll need to implement this
    const isUserReview = currentUserId && currentUserId == review.user_id;
    
    reviewCard.innerHTML = `
      <div class="review-header">
        <div class="reviewer-info">
          <img src="${review.user_profile_picture || './image/default-avatar.png'}" alt="Reviewer" class="reviewer-avatar">
          <div>
            <h4>${review.user_name || 'Anonymous'}</h4>
            <p>Art Enthusiast</p>
          </div>
        </div>
        <div class="review-actions">
          <div class="review-rating">
            ${starsHTML}
          </div>
          ${isUserReview ? `<button class="delete-review-btn" onclick="deleteReview(${review.review_id})" title="Delete Review">
            <i class="fas fa-trash"></i>
          </button>` : ''}
        </div>
      </div>
      <div class="review-content">
        <p>"${review.review_text}"</p>
      </div>
      <div class="review-date">
        <small>Purchased: "${review.artwork_title}" - ${timeAgo}</small>
      </div>
    `;
    
    reviewsGrid.appendChild(reviewCard);
  });
}

// Helper function to get time ago
function getTimeAgo(date) {
  const now = new Date();
  const diffTime = Math.abs(now - date);
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  
  if (diffDays === 1) {
    return '1 day ago';
  } else if (diffDays < 7) {
    return `${diffDays} days ago`;
  } else if (diffDays < 30) {
    const weeks = Math.floor(diffDays / 7);
    return weeks === 1 ? '1 week ago' : `${weeks} weeks ago`;
  } else if (diffDays < 365) {
    const months = Math.floor(diffDays / 30);
    return months === 1 ? '1 month ago' : `${months} months ago`;
  } else {
    const years = Math.floor(diffDays / 365);
    return years === 1 ? '1 year ago' : `${years} years ago`;
  }
}

// Get current user ID (implement based on your session management)
function getCurrentUserId() {
  // This should return the current logged-in user's ID
  // You can implement this by checking session, localStorage, or making an API call
  return localStorage.getItem('user_id') || sessionStorage.getItem('user_id');
}

// Delete review function
function deleteReview(reviewId) {
  if (!confirm('Are you sure you want to delete this review?')) {
    return;
  }
  
  const currentUserId = getCurrentUserId();
  if (!currentUserId) {
    showNotification('Please log in to delete reviews', 'error');
    return;
  }
  
  fetch('./API/deleteReview.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `review_id=${reviewId}&user_id=${currentUserId}`
  })
  .then(res => {
    if (!res.ok) {
      throw new Error(`HTTP error! status: ${res.status}`);
    }
    return res.text();
  })
  .then(text => {
    if (!text.trim()) {
      throw new Error('Empty response from deleteReview API');
    }
    
    if (text.trim().startsWith('<')) {
      throw new Error('Server returned HTML instead of JSON');
    }
    
    const data = JSON.parse(text);
    if (data.success) {
      showNotification('Review deleted successfully', 'success');
      // Remove the review card from the DOM
      const reviewCard = document.querySelector(`[data-review-id="${reviewId}"]`);
      if (reviewCard) {
        reviewCard.remove();
      }
      // Reload artist data to update rating statistics
      loadArtistProfile();
    } else {
      showNotification(data.message || 'Failed to delete review', 'error');
    }
  })
  .catch(error => {
    console.error('Error deleting review:', error);
    showNotification('Error deleting review. Please try again.', 'error');
  });
}

function renderArtistArtworks(artworks) {
  const container = document.getElementById('artist-artworks-container')
  if (!container) return
  container.innerHTML = ''
  if (!Array.isArray(artworks) || artworks.length === 0) {
    container.innerHTML = '<p>No artworks found for this artist.</p>'
      populateReviewArtworkDropdown()
      return
  }
  
  // Limit to maximum 6 artworks for featured section
  const featuredArtworks = artworks.slice(0, 6)
  
  featuredArtworks.forEach(artwork => {
    const card = document.createElement('div')
    card.className = 'enhanced-artwork-card fade-in-left'
    card.setAttribute('data-category', artwork.category || '')
    card.setAttribute('data-price', artwork.price || '')
    card.innerHTML = `
      <div class="artwork-image-container">
        <img class="enhanced-artwork-image" src="${artwork.artwork_image_url || './image/placeholder.png'}" alt="${artwork.title}" style="opacity: 1;" />
        <div class="artwork-overlay">
          <div class="quick-actions">
            <button class="quick-action-btn" data-id="${artwork.artwork_id}" title="Preview Artwork">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>
      </div>
      <div class="enhanced-artwork-info">
        <div class="artwork-category">${artwork.category || ''}</div>
        <h3 class="enhanced-artwork-title">${artwork.title || ''}</h3>
        <p class="enhanced-artwork-artist">
          <a href="artistProfile.php?id=${artwork.artist ? artwork.artist.artist_id : ''}" class="artist-link" style="color:inherit;text-decoration:underline;cursor:pointer;">
            ${artwork.artist ? artwork.artist.display_name : ''}
          </a>
        </p>
        <p class="enhanced-artwork-price">${artwork.formatted_price || ''}</p>
        <p class="artwork-dimensions">${artwork.dimensions || ''}</p>
        <p class="enhanced-artwork-description">${artwork.description || ''}</p>
        <div class="artwork-actions">
          <button class="enhanced-add-to-cart" data-id="${artwork.artwork_id}">ADD TO CART</button>
          <button class="wishlist-btn" data-id="${artwork.artwork_id}" title="Add to Wishlist"><i class="far fa-heart"></i></button>
        </div>
      </div>
    `
    container.appendChild(card)
  })
  
  // Initialize functionality for newly created artwork cards
  initializeArtworkCardFunctionality()
  // Initialize functionality for newly created artwork cards
  initializeArtworkCardFunctionality()
  populateReviewArtworkDropdown()
}

// Initialize functionality for artwork cards (for dynamically loaded content)
function initializeArtworkCardFunctionality() {
  // Initialize wishlist functionality for new cards
  initializeWishlistForNewCards()
  
  // Initialize add to cart functionality for new cards
  initializeAddToCartForNewCards()
  
  // Initialize image lazy loading for new cards
  initializeImageLazyLoadingForNewCards()
  
  // Initialize preview button functionality for new cards
  initializePreviewButtonForNewCards()
}

// Wishlist functionality for dynamically created cards
async function initializeWishlistForNewCards() {
  const wishlistBtns = document.querySelectorAll(".wishlist-btn")
  
  if (wishlistBtns.length === 0) return

  // Load wishlist state from API
  try {
    const wishlistData = await getWishlistAPI()
    let wishlistArtworkIds = []
    
    // Safely extract wishlist IDs
    if (wishlistData.success && wishlistData.wishlist && Array.isArray(wishlistData.wishlist)) {
      wishlistArtworkIds = wishlistData.wishlist.map(item => item.artwork_id.toString())
    }

    // Load existing wishlist state for all buttons (including new ones)
    wishlistBtns.forEach((btn) => {
      const artworkId = btn.dataset.id
      if (wishlistArtworkIds.includes(artworkId)) {
        btn.classList.add("active")
        btn.innerHTML = '<i class="fas fa-heart"></i>'
      } else {
        btn.classList.remove("active")
        btn.innerHTML = '<i class="far fa-heart"></i>'
      }
      
      // Remove existing event listeners to avoid duplicates
      btn.removeEventListener("click", handleWishlistClick)
      // Add event listener
      btn.addEventListener("click", handleWishlistClick)
    })
  } catch (error) {
    console.error('Error loading wishlist:', error)
    
    // Fallback to localStorage for backward compatibility
    const wishlist = JSON.parse(localStorage.getItem("artistWishlist")) || []
    
    wishlistBtns.forEach((btn) => {
      const artworkId = btn.dataset.id
      if (wishlist.includes(artworkId)) {
        btn.classList.add("active")
        btn.innerHTML = '<i class="fas fa-heart"></i>'
      } else {
        btn.classList.remove("active")
        btn.innerHTML = '<i class="far fa-heart"></i>'
      }
      
      // Remove existing event listeners to avoid duplicates
      btn.removeEventListener("click", handleWishlistClick)
      // Add event listener
      btn.addEventListener("click", handleWishlistClick)
    })
  }
}

// API call to get wishlist
async function getWishlistAPI() {
  try {
    const response = await fetch('./API/getWishlist.php', {
      method: 'GET',
      credentials: 'include'
    })

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const responseText = await response.text()
    
    if (responseText.trim().startsWith('<')) {
      throw new Error('Authentication required or server error')
    }
    
    return JSON.parse(responseText)
  } catch (error) {
    console.error('Error getting wishlist:', error)
    return { success: false, wishlist: [] }
  }
}

// Wishlist click handler function
async function handleWishlistClick(e) {
  e.preventDefault()
  const btn = this
  const artworkId = btn.dataset.id
  const artworkTitle = btn.closest(".enhanced-artwork-card").querySelector(".enhanced-artwork-title").textContent
  
  // Disable button temporarily
  btn.disabled = true
  
  try {
    const isCurrentlyInWishlist = btn.classList.contains("active")
    
    if (isCurrentlyInWishlist) {
      // Remove from wishlist
      const result = await removeFromWishlistAPI(artworkId)
      
      if (result.success) {
        btn.classList.remove("active")
        btn.innerHTML = '<i class="far fa-heart"></i>'
        showNotification(`"${artworkTitle}" removed from wishlist`, "info")
      } else {
        if (result.redirect) {
          showNotification('Please log in to manage wishlist', 'error')
        } else {
          showNotification(result.message || 'Failed to remove from wishlist', 'error')
        }
      }
    } else {
      // Add to wishlist
      const result = await addToWishlistAPI(artworkId)
      
      if (result.success) {
        btn.classList.add("active")
        btn.innerHTML = '<i class="fas fa-heart"></i>'
        showNotification(`"${artworkTitle}" added to wishlist`, "success")
      } else {
        if (result.redirect) {
          showNotification('Please log in to add items to wishlist', 'error')
        } else {
          showNotification(result.message || 'Failed to add to wishlist', 'error')
        }
      }
    }

    // Add animation
    btn.style.transform = "scale(1.2)"
    setTimeout(() => {
      btn.style.transform = "scale(1)"
    }, 200)

  } catch (error) {
    console.error('Wishlist error:', error)
    showNotification('An error occurred. Please try again.', 'error')
  } finally {
    btn.disabled = false
  }
}

// API call to add item to wishlist
async function addToWishlistAPI(artworkId) {
  try {
    const response = await fetch('./API/addToWishlist.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'include',
      body: JSON.stringify({
        artwork_id: parseInt(artworkId)
      })
    })

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const responseText = await response.text()
    
    if (responseText.trim().startsWith('<')) {
      throw new Error('Authentication required or server error')
    }
    
    return JSON.parse(responseText)
  } catch (error) {
    console.error('Error adding to wishlist:', error)
    return { success: false, message: 'Failed to add to wishlist' }
  }
}

// API call to remove item from wishlist
async function removeFromWishlistAPI(artworkId) {
  try {
    // Since there might not be a specific remove API, we'll try to use the add API with a remove action
    const response = await fetch('./API/addToWishlist.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'include',
      body: JSON.stringify({
        artwork_id: parseInt(artworkId),
        action: 'remove'
      })
    })

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const responseText = await response.text()
    
    if (responseText.trim().startsWith('<')) {
      throw new Error('Authentication required or server error')
    }
    
    return JSON.parse(responseText)
  } catch (error) {
    console.error('Error removing from wishlist:', error)
    return { success: false, message: 'Failed to remove from wishlist' }
  }
}

// Quick view functionality for dynamically created cards
function initializeQuickViewForNewCards() {
  const quickViewBtns = document.querySelectorAll('.quick-action-btn[data-action="view"]')

  quickViewBtns.forEach((btn) => {
    // Remove existing event listeners to avoid duplicates
    btn.removeEventListener("click", handleQuickViewClick)
    // Add event listener
    btn.addEventListener("click", handleQuickViewClick)
  })
}

// Quick view click handler function
function handleQuickViewClick() {
  const artworkId = this.dataset.id
  
  if (artworkId) {
    // Redirect to artwork preview page with the artwork ID
    window.location.href = `artworkPreview.php?id=${artworkId}`
  } else {
    console.error('Artwork ID not found')
    showNotification('Unable to preview artwork. Please try again.', 'error')
  }
}

// Add to cart functionality for dynamically created cards
function initializeAddToCartForNewCards() {
  const addToCartBtns = document.querySelectorAll(".enhanced-add-to-cart")

  addToCartBtns.forEach((btn) => {
    // Remove existing event listeners to avoid duplicates
    btn.removeEventListener("click", handleAddToCartClick)
    // Add event listener
    btn.addEventListener("click", handleAddToCartClick)
  })
}

// Add to cart click handler function
function handleAddToCartClick() {
  const artworkId = this.dataset.id
  addToCart(artworkId)
}

// Image lazy loading for dynamically created cards
function initializeImageLazyLoadingForNewCards() {
  const images = document.querySelectorAll(".enhanced-artwork-image")

  const imageObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const img = entry.target
        
        // Only apply lazy loading if image hasn't been loaded yet
        if (!img.dataset.loaded) {
          const originalSrc = img.src
          
          // Set loading state
          img.style.opacity = "0"
          img.style.transition = "opacity 0.3s ease"
          
          // Create new image to preload
          const newImg = new Image()
          newImg.onload = () => {
            img.style.opacity = "1"
            img.dataset.loaded = "true"
          }
          newImg.onerror = () => {
            // If image fails to load, still show it
            img.style.opacity = "1"
            img.dataset.loaded = "true"
          }
          newImg.src = originalSrc
        }

        imageObserver.unobserve(img)
      }
    })
  })

  images.forEach((img) => {
    // Check if image is already loaded or visible
    if (img.complete && img.naturalHeight !== 0) {
      img.dataset.loaded = "true"
      img.style.opacity = "1"
    } else {
      imageObserver.observe(img)
    }
  })
}

// Preview button functionality for dynamically created cards
function initializePreviewButtonForNewCards() {
  const previewBtns = document.querySelectorAll('.quick-action-btn')

  previewBtns.forEach((btn) => {
    // Remove existing event listeners to avoid duplicates
    btn.removeEventListener("click", handlePreviewClick)
    // Add event listener
    btn.addEventListener("click", handlePreviewClick)
  })
}

// Preview click handler function
function handlePreviewClick() {
  const artworkId = this.dataset.id
  
  if (artworkId) {
    // Redirect to artwork preview page with the artwork ID
    window.location.href = `artworkPreview.php?id=${artworkId}`
  } else {
    console.error('Artwork ID not found')
    showNotification('Unable to preview artwork. Please try again.', 'error')
  }
}  function populateReviewArtworkDropdown() {
    const dropdown = document.getElementById("purchasedArtwork")
    if (!dropdown) return

    const artistId = getArtistIdFromUrl()
    if (!artistId) {
      dropdown.innerHTML = '<option value="">Unable to load artworks</option>'
      return
    }

    // Set loading state
    dropdown.innerHTML = '<option value="">Loading your purchased artworks...</option>'
    dropdown.disabled = true

    // Fetch purchased artworks using the dedicated API
    fetch(`./API/getPurchasedArtworks.php?artist_id=${artistId}`, {
      method: 'GET',
      credentials: 'include' // Include cookies for authentication
    })
    .then(response => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      return response.text()
    })
    .then(text => {
      console.log('Raw API response:', text); // Debug log
      
      if (!text || !text.trim()) {
        throw new Error('Empty response from server')
      }
      
      if (text.trim().startsWith('<')) {
        console.error('Server returned HTML:', text.substring(0, 200));
        throw new Error('Server returned HTML instead of JSON - likely an authentication error')
      }
      
      let data;
      try {
        data = JSON.parse(text);
      } catch (parseError) {
        console.error('JSON parse error:', parseError, 'Response text:', text);
        throw new Error('Invalid JSON response from server');
      }
      
      dropdown.disabled = false
      
      if (data.success && data.data && Array.isArray(data.data) && data.data.length > 0) {
        // User has purchased artworks from this artist
        dropdown.innerHTML = '<option value="">Select an artwork you purchased</option>'
        
        data.data.forEach(artwork => {
          const option = document.createElement('option')
          option.value = artwork.title || 'Unknown Artwork'
          option.textContent = artwork.title || 'Unknown Artwork'
          dropdown.appendChild(option)
        })
      } else if (data.success && data.data && Array.isArray(data.data) && data.data.length === 0) {
        // User hasn't purchased any artworks from this artist
        dropdown.innerHTML = '<option value="">You haven\'t purchased any artworks from this artist</option>'
      } else if (!data.success) {
        // Show specific error message from server, with helpful suggestion
        const message = data.message || 'Authentication required'
        if (message.includes('Authentication') || message.includes('log in')) {
          dropdown.innerHTML = '<option value="">Please log in to leave a review</option>'
        } else {
          dropdown.innerHTML = `<option value="">${message}</option>`
        }
      } else {
        // Generic error - fallback
        dropdown.innerHTML = '<option value="">Please log in to leave a review</option>'
      }
    })
    .catch(error => {
      console.error('Error fetching purchased artworks:', error)
      dropdown.disabled = false
      
      // Provide user-friendly error message based on error type
      if (error.message.includes('JSON')) {
        dropdown.innerHTML = '<option value="">Server error - please try again later</option>'
      } else if (error.message.includes('HTTP')) {
        dropdown.innerHTML = '<option value="">Connection error - please check your internet</option>'
      } else {
        dropdown.innerHTML = '<option value="">Unable to load artworks - please try again</option>'
      }
    })
  }


function renderArtistAchievements(achievements) {
  const container = document.getElementById('achievements-container')
  const noAchievements = document.getElementById('no-achievements')
  
  if (!container) return
  
  // Hide loading and no achievements message
  container.innerHTML = ''
  if (noAchievements) noAchievements.style.display = 'none'
  
  if (!Array.isArray(achievements) || achievements.length === 0) {
    showNoAchievements()
    return
  }

  // Function to get appropriate icon based on achievement name
  function getAchievementIcon(achievementName) {
    const name = achievementName.toLowerCase()
    if (name.includes('award') || name.includes('winner') || name.includes('first') || name.includes('champion')) {
      return 'fa-trophy'
    } else if (name.includes('exhibition') || name.includes('gallery') || name.includes('show')) {
      return 'fa-palette'
    } else if (name.includes('education') || name.includes('degree') || name.includes('certified') || name.includes('graduate')) {
      return 'fa-graduation-cap'
    } else if (name.includes('year') || name.includes('experience') || name.includes('veteran')) {
      return 'fa-clock'
    } else if (name.includes('international') || name.includes('global') || name.includes('world')) {
      return 'fa-globe'
    } else if (name.includes('master') || name.includes('expert') || name.includes('professional')) {
      return 'fa-star'
    } else if (name.includes('innovation') || name.includes('creative') || name.includes('original')) {
      return 'fa-lightbulb'
    } else {
      return 'fa-medal'
    }
  }

  // Function to get achievement color theme
  function getAchievementColor(index) {
    const colors = ['#FFD700', '#C0C0C0', '#CD7F32', '#4CAF50', '#2196F3', '#9C27B0', '#FF9800', '#E91E63']
    return colors[index % colors.length]
  }

  achievements.forEach((achievement, index) => {
    const achievementCard = document.createElement('div')
    achievementCard.className = 'achievement-card-enhanced' // Use different class to avoid conflicts
    
    const icon = getAchievementIcon(achievement.achievement_name || '')
    const color = getAchievementColor(index)
    
    achievementCard.innerHTML = `
      <div class="achievement-icon-enhanced" style="background: linear-gradient(135deg, ${color}, ${color}88); color: white;">
        <i class="fas ${icon}"></i>
      </div>
      <div class="achievement-content-enhanced">
        <h4 class="achievement-title-enhanced">${achievement.achievement_name || 'Recognition'}</h4>
        <div class="achievement-date-enhanced">
          <i class="fas fa-calendar"></i>
          <span>Achievement #${achievement.achievement_id}</span>
        </div>
      </div>
      <div class="achievement-badge-enhanced">
        <i class="fas fa-check-circle"></i>
      </div>
    `
    
    container.appendChild(achievementCard)
  })

  // Add CSS styles for enhanced achievement cards
  if (!document.querySelector('#achievement-styles-enhanced')) {
    const styles = document.createElement('style')
    styles.id = 'achievement-styles-enhanced'
    styles.textContent = `
      .achievement-card-enhanced {
        background: linear-gradient(135deg, #ffffff, #f8f9fa);
        border-radius: 15px;
        padding: 20px;
        margin: 15px 0;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        border: 1px solid #e9ecef;
        position: relative;
        overflow: hidden;
        border-left: 5px solid var(--gold-accent);
      }
      
      .achievement-card-enhanced:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
      }
      
      .achievement-card-enhanced::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #D4AF37, #FFD700);
      }
      
      .achievement-icon-enhanced {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      }
      
      .achievement-content-enhanced {
        flex: 1;
      }
      
      .achievement-title-enhanced {
        margin: 0 0 8px 0;
        font-size: 18px;
        font-weight: 600;
        color: #2c3e50;
        line-height: 1.3;
      }
      
      .achievement-date-enhanced {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 14px;
        color: #6c757d;
      }
      
      .achievement-badge-enhanced {
        color: #28a745;
        font-size: 20px;
        flex-shrink: 0;
      }
      
      @media (max-width: 768px) {
        .achievement-card-enhanced {
          padding: 15px;
          margin: 10px 0;
        }
        
        .achievement-icon-enhanced {
          width: 50px;
          height: 50px;
          font-size: 20px;
        }
        
        .achievement-title-enhanced {
          font-size: 16px;
        }
      }
    `
    document.head.appendChild(styles)
  }

  console.log('Finished rendering achievements')
}

function showNoAchievements() {
  const container = document.getElementById('achievements-container')
  const noAchievements = document.getElementById('no-achievements')
  
  if (container) container.innerHTML = ''
  if (noAchievements) noAchievements.style.display = 'block'
}

// Wishlist functionality
function initializeWishlist() {
  initializeWishlistForNewCards()
}

// Quick view functionality
function initializeQuickView() {
  initializeQuickViewForNewCards()
}



// Add to cart functionality
function initializeAddToCart() {
  initializeAddToCartForNewCards()
}

function addToCart(artworkId) {
  // Check if CartManager is available globally
  if (window.cartManager) {
    // Create a temporary button element with the artwork ID for the cart manager
    const tempButton = document.createElement('button');
    tempButton.setAttribute('data-artwork-id', artworkId);
    window.cartManager.handleAddToCart(tempButton);
    return;
  }

  // Fallback to API call if CartManager is not available
  addToCartAPI(artworkId);
}

// API call to add item to cart (fallback method)
async function addToCartAPI(artworkId, quantity = 1) {
  try {
    // Show loading state
    const button = document.querySelector(`.enhanced-add-to-cart[data-id="${artworkId}"]`);
    if (button) {
      const originalText = button.textContent;
      button.textContent = 'ADDING...';
      button.disabled = true;
    }

    const response = await fetch('./API/addToCart.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'include', // Include cookies for authentication
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
    
    const result = JSON.parse(responseText);

    if (result.success) {
      // Show success message
      if (window.Swal) {
        Swal.fire({
          icon: 'success',
          title: 'Added to Cart!',
          text: result.message,
          timer: 2000,
          timerProgressBar: true,
          showConfirmButton: false,
          toast: true,
          position: 'top-end'
        });
      } else {
        showNotification(result.message, 'success');
      }

      // Update button state
      if (button) {
        button.textContent = 'ADDED!';
        button.style.backgroundColor = '#28a745';
        setTimeout(() => {
          button.textContent = 'ADD TO CART';
          button.style.backgroundColor = '';
          button.disabled = false;
        }, 2000);
      }

      // Update cart count
      updateCartCount();

    } else {
      // Handle specific error cases
      if (result.redirect) {
        if (window.Swal) {
          Swal.fire({
            icon: 'warning',
            title: 'Login Required',
            text: 'Please log in to add items to your cart',
            showCancelButton: true,
            confirmButtonText: 'Login',
            cancelButtonText: 'Cancel'
          }).then((result) => {
            if (result.isConfirmed) {
              window.location.href = 'login.php';
            }
          });
        } else {
          showNotification('Please log in to add items to cart', 'error');
        }
      } else {
        if (window.Swal) {
          Swal.fire({
            icon: 'error',
            title: 'Cannot Add to Cart',
            text: result.message
          });
        } else {
          showNotification(result.message, 'error');
        }
      }

      // Reset button state
      if (button) {
        button.textContent = 'ADD TO CART';
        button.disabled = false;
      }
    }

  } catch (error) {
    console.error('Error adding to cart:', error);
    
    // Check if it's an authentication error
    if (error.message.includes('Authentication required')) {
      if (window.Swal) {
        Swal.fire({
          icon: 'warning',
          title: 'Login Required',
          text: 'Please log in to add items to your cart',
          showCancelButton: true,
          confirmButtonText: 'Login',
          cancelButtonText: 'Cancel'
        }).then((result) => {
          if (result.isConfirmed) {
            window.location.href = 'login.php';
          }
        });
      } else {
        showNotification('Please log in to add items to cart', 'error');
      }
    } else {
      if (window.Swal) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'An error occurred while adding to cart'
        });
      } else {
        showNotification('An error occurred while adding to cart', 'error');
      }
    }

    // Reset button state
    const button = document.querySelector(`.enhanced-add-to-cart[data-id="${artworkId}"]`);
    if (button) {
      button.textContent = 'ADD TO CART';
      button.disabled = false;
    }
  }
}

// Update cart count
function updateCartCount() {
  // Check if there's a global cart manager with count functionality
  if (window.cartManager && typeof window.cartManager.updateCartCount === 'function') {
    window.cartManager.updateCartCount();
    return;
  }

  // Fallback: fetch cart count from API
  fetch('./API/getCart.php', {
    method: 'GET',
    credentials: 'include'
  })
  .then(response => response.json())
  .then(data => {
    if (data.success && data.cart) {
      const totalItems = data.cart.reduce((sum, item) => sum + item.quantity, 0);
      updateCartCountDisplay(totalItems);
    } else {
      updateCartCountDisplay(0);
    }
  })
  .catch(error => {
    console.error('Error fetching cart count:', error);
    // Fallback to localStorage for backward compatibility
    const cart = JSON.parse(localStorage.getItem("artistCart")) || [];
    updateCartCountDisplay(cart.length);
  });
}

// Update cart count display elements
function updateCartCountDisplay(count) {
  const cartCountElements = document.querySelectorAll(".cart-count, .cart-counter, .cart-badge");

  cartCountElements.forEach((element) => {
    element.textContent = count;
    if (count > 0) {
      element.style.display = "inline";
      element.classList.add('has-items');
    } else {
      element.style.display = "none";
      element.classList.remove('has-items');
    }
  });
}

// Star rating functionality
function initializeStarRating() {
  const starRatings = document.querySelectorAll(".star-rating")

  starRatings.forEach((rating) => {
    const stars = rating.querySelectorAll(".star")
    let currentRating = 0

    stars.forEach((star, index) => {
      star.addEventListener("mouseenter", () => {
        highlightStars(stars, index + 1)
      })

      star.addEventListener("mouseleave", () => {
        highlightStars(stars, currentRating)
      })

      star.addEventListener("click", () => {
        currentRating = index + 1
        highlightStars(stars, currentRating)
        rating.dataset.rating = currentRating

        // Show rating feedback
        showRatingFeedback(currentRating)
      })
    })
  })
}

function highlightStars(stars, count) {
  stars.forEach((star, index) => {
    if (index < count) {
      star.classList.add("active")
    } else {
      star.classList.remove("active")
    }
  })
}

function showRatingFeedback(rating) {
  const messages = {
    1: "We appreciate your feedback",
    2: "Thank you for your review",
    3: "Good to know your thoughts",
    4: "Great! Thank you",
    5: "Excellent! We're thrilled",
  }

  // Update the rating text
  const ratingText = document.querySelector('.rating-text');
  if (ratingText) {
    ratingText.textContent = messages[rating];
    ratingText.style.color = '#d4a574';
    ratingText.style.fontWeight = '600';
  }

  // Brief notification
  setTimeout(() => {
    if (ratingText) {
      ratingText.textContent = 'Click to rate';
      ratingText.style.color = '#8b7355';
      ratingText.style.fontWeight = '400';
    }
  }, 2000);
}

// Review form functionality
function initializeReviewForm() {
  const reviewTextarea = document.getElementById("quickReview")
  const characterCounter = document.getElementById("characterCounter")
  const submitBtn = document.getElementById("submitQuickReview")

  if (reviewTextarea && characterCounter) {
    reviewTextarea.addEventListener("input", function () {
      const currentLength = this.value.length
      const maxLength = this.getAttribute("maxlength")
      characterCounter.textContent = `${currentLength}/${maxLength}`

      if (currentLength > maxLength * 0.9) {
        characterCounter.style.color = "var(--red-accent)"
      } else {
        characterCounter.style.color = "var(--text-light)"
      }
    })
  }

  if (submitBtn) {
    submitBtn.addEventListener("click", () => {
      submitReview()
    })
  }
}

function submitReview() {
  const artworkSelect = document.getElementById("purchasedArtwork")
  const reviewText = document.getElementById("quickReview")
  const starRating = document.querySelector(".star-rating")

  // Validation
  if (!artworkSelect.value) {
    Swal.fire({
      icon: 'warning',
      title: 'Artwork Required',
      text: 'Please select an artwork that you have purchased before writing a review.',
      confirmButtonText: 'OK',
      confirmButtonColor: '#D4AF37'
    });
    artworkSelect.focus()
    return
  }

  if (!reviewText.value.trim()) {
    Swal.fire({
      icon: 'info',
      title: 'Review Required',
      text: 'Please share your thoughts about the artwork to help other collectors.',
      confirmButtonText: 'OK',
      confirmButtonColor: '#D4AF37'
    });
    reviewText.focus()
    return
  }

  const rating = starRating ? starRating.dataset.rating : 0
  if (!rating) {
    Swal.fire({
      icon: 'info',
      title: 'Rating Required',
      text: 'Please rate your experience with this artwork by clicking on the stars.',
      confirmButtonText: 'OK',
      confirmButtonColor: '#D4AF37'
    });
    return
  }

  const artistId = getArtistIdFromUrl()
  if (!artistId) {
    Swal.fire({
      icon: 'error',
      title: 'Page Error',
      text: 'Unable to identify the artist profile. Please return to the gallery and select an artist.',
      confirmButtonText: 'Go to Gallery',
      confirmButtonColor: '#D4AF37'
    }).then(() => {
      window.location.href = 'gallery.php';
    });
    return
  }

  // Show loading state
  const submitBtn = document.getElementById("submitQuickReview")
  const originalHTML = submitBtn.innerHTML
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...'
  submitBtn.disabled = true

  // Prepare review data
  const reviewData = {
    artist_id: parseInt(artistId),
    artwork_title: artworkSelect.value,
    rating: parseInt(rating),
    review_text: reviewText.value.trim()
  }

  // Submit review to API
  fetch('./API/submitReview.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    credentials: 'include', // Include cookies for authentication
    body: JSON.stringify(reviewData)
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Show success message with SweetAlert2
      Swal.fire({
        icon: 'success',
        title: 'Review Submitted!',
        text: 'Thank you for sharing your experience! Your review will help other art collectors make informed decisions.',
        confirmButtonText: 'Wonderful!',
        confirmButtonColor: '#D4AF37',
        timer: 5000,
        timerProgressBar: true
      });

      // Reset form
      artworkSelect.value = ""
      reviewText.value = ""
      document.getElementById("characterCounter").textContent = "0/500"

      // Reset star rating
      const stars = starRating.querySelectorAll(".star")
      stars.forEach((star) => star.classList.remove("active"))
      starRating.dataset.rating = 0

      // Refresh the artwork dropdown to remove the reviewed artwork
      populateReviewArtworkDropdown()

      // Update the overall rating display
      loadArtistProfile()

      // Success animation
      submitBtn.innerHTML = '<i class="fas fa-check"></i> Submitted!'
      submitBtn.style.background = "var(--green-accent)"

      setTimeout(() => {
        submitBtn.innerHTML = originalHTML
        submitBtn.style.background = ""
        submitBtn.disabled = false
      }, 3000)
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Review Submission Failed',
        text: data.message || 'We encountered an issue while submitting your review. Please try again.',
        confirmButtonText: 'Try Again',
        confirmButtonColor: '#D4AF37'
      });
      submitBtn.innerHTML = originalHTML
      submitBtn.disabled = false
    }
  })
  .catch(error => {
    console.error('Error submitting review:', error)
    Swal.fire({
      icon: 'error',
      title: 'Connection Error',
      text: 'Unable to connect to the server. Please check your internet connection and try again.',
      confirmButtonText: 'OK',
      confirmButtonColor: '#D4AF37'
    });
    submitBtn.innerHTML = originalHTML
    submitBtn.disabled = false
  })
}

// Scroll animations
function initializeScrollAnimations() {
  const observerOptions = {
    threshold: 0.1,
    rootMargin: "0px 0px -50px 0px",
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = "1"
        entry.target.style.transform = "translateY(0)"
      }
    })
  }, observerOptions)

  // Observe elements for animation
  const animatedElements = document.querySelectorAll(".enhanced-artwork-card, .review-card, .achievement-item")
  animatedElements.forEach((el) => {
    observer.observe(el)
  })
}

// Image lazy loading
function initializeImageLazyLoading() {
  initializeImageLazyLoadingForNewCards()
}

// Notification system
function showNotification(message, type = "info") {
  const notification = document.createElement("div")
  notification.className = `notification notification-${type}`
  notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close">&times;</button>
    `

  // Add notification styles if not already added
  if (!document.querySelector("#notification-styles")) {
    const notificationStyles = `
            <style id="notification-styles">
                .notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: var(--white);
                    border-radius: 10px;
                    padding: 15px 20px;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
                    z-index: 1000;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    min-width: 300px;
                    animation: slideInRight 0.3s ease;
                    border-left: 5px solid var(--primary-brown);
                }
                
                .notification-success {
                    border-left-color: var(--green-accent);
                }
                
                .notification-error {
                    border-left-color: var(--red-accent);
                }
                
                .notification-info {
                    border-left-color: var(--gold-accent);
                }
                
                .notification-content {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    color: var(--text-dark);
                }
                
                .notification-close {
                    background: none;
                    border: none;
                    font-size: 1.2rem;
                    cursor: pointer;
                    color: var(--text-light);
                    padding: 0;
                    width: 20px;
                    height: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                .notification-close:hover {
                    color: var(--text-dark);
                }
                
                @keyframes slideInRight {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                
                @keyframes slideOutRight {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                }
            </style>
        `
    document.head.insertAdjacentHTML("beforeend", notificationStyles)
  }

  document.body.appendChild(notification)

  // Close functionality
  const closeBtn = notification.querySelector(".notification-close")
  closeBtn.addEventListener("click", () => {
    removeNotification(notification)
  })

  // Auto remove after 5 seconds
  setTimeout(() => {
    if (document.body.contains(notification)) {
      removeNotification(notification)
    }
  }, 5000)
}

function removeNotification(notification) {
  notification.style.animation = "slideOutRight 0.3s ease"
  setTimeout(() => {
    if (document.body.contains(notification)) {
      document.body.removeChild(notification)
    }
  }, 300)
}

function getNotificationIcon(type) {
  const icons = {
    success: "check-circle",
    error: "exclamation-circle",
    info: "info-circle",
  }
  return icons[type] || "info-circle"
}

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    e.preventDefault()
    const target = document.querySelector(this.getAttribute("href"))
    if (target) {
      target.scrollIntoView({
        behavior: "smooth",
        block: "start",
      })
    }
  })
})

// Initialize cart count on page load
document.addEventListener("DOMContentLoaded", () => {
  updateCartCount()
})
