// DOM Elements
const artworkSearchInput = document.getElementById('searchInput');
const artworksGrid = document.getElementById('artworksGrid');
const artworkCount = document.getElementById('artworkCount');

// Filter state for artwork page
let artworkActiveFilters = {
  search: '',
  category: 'all',
  artist_id: 'all',
  sort: 'featured',
  minPrice: '',
  maxPrice: ''
};

// Pagination variables
let currentPage = 1;
let totalPages = 1;
let totalCount = 0;
let isLoading = false;
const itemsPerPage = 9;

// Initialize filters and setup when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  // Populate category filter with backend-compatible options
  const categoryFilter = document.getElementById('categoryFilter');
  if (categoryFilter) {
    const categories = [
      { value: 'all', label: 'All Categories' },
      { value: 'portraits', label: 'Portraits' },
      { value: 'landscapes', label: 'Landscapes' },
      { value: 'abstract', label: 'Abstract' },
      { value: 'photography', label: 'Photography' },
      { value: 'mixed-media', label: 'Mixed Media' }
    ];
    categoryFilter.innerHTML = categories.map(cat => `<option value="${cat.value}">${cat.label}</option>`).join('');
  }
  setupArtworkEventListeners();
  // Suppress image loading errors globally
  window.addEventListener('error', function(e) {
    if (e.target && e.target.tagName === 'IMG') {
      e.preventDefault();
      e.stopPropagation();
      return false;
    }
  }, true);
  // Attach Apply Filters button event (by class)
  const applyFiltersBtns = document.getElementsByClassName('apply-filters-btn');
  Array.from(applyFiltersBtns).forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      applyFilters();
    });
  });
  // Attach Clear All button event (by class)
  const clearAllBtns = document.getElementsByClassName('clear-filters-btn');
  Array.from(clearAllBtns).forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      clearAllFilters();
    });
  });
  loadArtworks(1);
});

// Setup event listeners when DOM is loaded
function setupArtworkEventListeners() {
  // Artist filter
  const artistFilter = document.getElementById('artistFilter');
  if (artistFilter) {
    artistFilter.addEventListener('change', (e) => {
      artworkActiveFilters.artist_id = e.target.value;
      updateArtworkActiveFilters();
      applyFilters();
    });
  }
  // Search input event listener
  if (artworkSearchInput) {
    artworkSearchInput.addEventListener('input', debounce(() => {
      applyFilters();
    }, 300));

    artworkSearchInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        applyFilters();
      }
    });
  }

  // Category filter
  const categoryFilter = document.getElementById('categoryFilter');
  if (categoryFilter) {
    categoryFilter.addEventListener('change', (e) => {
      artworkActiveFilters.category = e.target.value;
      updateArtworkActiveFilters();
      applyFilters();
    });
  }

  // Sort filter
  const sortByFilter = document.getElementById('sortBy');
  if (sortByFilter) {
    sortByFilter.addEventListener('change', (e) => {
      artworkActiveFilters.sort = e.target.value;
      updateArtworkActiveFilters();
      applyFilters();
    });
  }

  // Price range filters
  const minPriceFilter = document.getElementById('minPrice');
  if (minPriceFilter) {
    minPriceFilter.addEventListener('change', (e) => {
      artworkActiveFilters.minPrice = e.target.value;
      updateArtworkActiveFilters();
      applyFilters();
    });
  }

  const maxPriceFilter = document.getElementById('maxPrice');
  if (maxPriceFilter) {
    maxPriceFilter.addEventListener('change', (e) => {
      artworkActiveFilters.maxPrice = e.target.value;
      updateArtworkActiveFilters();
      applyFilters();
    });
  }
}

// Update active filters display
function updateArtworkActiveFilters() {
  // Artist filter tag
  if (artworkActiveFilters.artist_id && artworkActiveFilters.artist_id !== 'all') {
    const artistFilter = document.getElementById('artistFilter');
    let artistName = 'Artist';
    if (artistFilter) {
      const selected = artistFilter.options[artistFilter.selectedIndex];
      artistName = selected ? selected.text : 'Artist';
    }
    addFilterTag('Artist', artistName, () => {
      artworkActiveFilters.artist_id = 'all';
      if (artistFilter) artistFilter.value = 'all';
      updateArtworkActiveFilters();
      applyFilters();
    });
    hasActiveFilters = true;
  }
  const activeFiltersContainer = document.getElementById('activeFilters');
  const activeFiltersSection = document.getElementById('activeFiltersSection');
  
  if (!activeFiltersContainer) return;
  
  activeFiltersContainer.innerHTML = '';
  let hasActiveFilters = false;

  // Search filter tag
  if (artworkActiveFilters.search) {
    addFilterTag('Search', `"${artworkActiveFilters.search}"`, () => {
      artworkActiveFilters.search = '';
      if (artworkSearchInput) artworkSearchInput.value = '';
      updateArtworkActiveFilters();
      applyFilters();
    });
    hasActiveFilters = true;
  }

  // Category filter tag
  if (artworkActiveFilters.category !== 'all') {
    addFilterTag('Category', artworkActiveFilters.category, () => {
      artworkActiveFilters.category = 'all';
      const categoryFilter = document.getElementById('categoryFilter');
      if (categoryFilter) categoryFilter.value = 'all';
      updateArtworkActiveFilters();
      applyFilters();
    });
    hasActiveFilters = true;
  }

  // Price range filter tag
  if (artworkActiveFilters.minPrice || artworkActiveFilters.maxPrice) {
    const priceText = `$${artworkActiveFilters.minPrice || '0'} - $${artworkActiveFilters.maxPrice || '∞'}`;
    addFilterTag('Price', priceText, () => {
      artworkActiveFilters.minPrice = '';
      artworkActiveFilters.maxPrice = '';
      const minPriceFilter = document.getElementById('minPrice');
      const maxPriceFilter = document.getElementById('maxPrice');
      if (minPriceFilter) minPriceFilter.value = '';
      if (maxPriceFilter) maxPriceFilter.value = '';
      updateArtworkActiveFilters();
      applyFilters();
    });
    hasActiveFilters = true;
  }

  // Sort filter tag
  if (artworkActiveFilters.sort !== 'featured') {
    const sortText = artworkActiveFilters.sort.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    addFilterTag('Sort', sortText, () => {
      artworkActiveFilters.sort = 'featured';
      const sortByFilter = document.getElementById('sortBy');
      if (sortByFilter) sortByFilter.value = 'featured';
      updateArtworkActiveFilters();
      applyFilters();
    });
    hasActiveFilters = true;
  }

  // Show/hide the active filters section
  if (activeFiltersSection) {
    activeFiltersSection.style.display = hasActiveFilters ? 'block' : 'none';
  }
}

// Add a filter tag to the active filters container
function addFilterTag(type, value, removeCallback) {
  const activeFiltersContainer = document.getElementById('activeFilters');
  
  const filterTag = document.createElement('div');
  filterTag.className = 'filter-tag';
  filterTag.innerHTML = `
    <span>${type}: ${value}</span>
    <span class="remove-filter">×</span>
  `;

  filterTag.querySelector('.remove-filter').addEventListener('click', removeCallback);
  activeFiltersContainer.appendChild(filterTag);
}

// Apply filters function - Updated to work with API
function applyFilters() {
  // Update filter state from DOM
  if (artworkSearchInput) artworkActiveFilters.search = artworkSearchInput.value.trim();
  const categoryFilter = document.getElementById('categoryFilter');
  if (categoryFilter) {
    // Map frontend value to backend value if needed
    artworkActiveFilters.category = categoryFilter.value;
  }
  const artistFilter = document.getElementById('artistFilter');
  if (artistFilter) artworkActiveFilters.artist_id = artistFilter.value;
  const minPriceFilter = document.getElementById('minPrice');
  if (minPriceFilter) artworkActiveFilters.minPrice = minPriceFilter.value;
  const maxPriceFilter = document.getElementById('maxPrice');
  if (maxPriceFilter) artworkActiveFilters.maxPrice = maxPriceFilter.value;
  const sortByFilter = document.getElementById('sortBy');
  if (sortByFilter) artworkActiveFilters.sort = sortByFilter.value;
  // Load artworks with current filters
  loadArtworks(1); // Reset to page 1 when applying filters
}

// Clear all filters
function clearAllFilters() {
  // Reset filter state
  artworkActiveFilters = {
    search: '',
    category: 'all',
    sort: 'featured',
    minPrice: '',
    maxPrice: ''
  };
  
  // Reset form inputs
  if (artworkSearchInput) artworkSearchInput.value = '';
  
  const categoryFilter = document.getElementById('categoryFilter');
  if (categoryFilter) categoryFilter.value = 'all';
  
  const sortByFilter = document.getElementById('sortBy');
  if (sortByFilter) sortByFilter.value = 'featured';
  
  const minPriceFilter = document.getElementById('minPrice');
  if (minPriceFilter) minPriceFilter.value = '';
  
  const maxPriceFilter = document.getElementById('maxPrice');
  if (maxPriceFilter) maxPriceFilter.value = '';
  
  // Update display and apply filters
  updateArtworkActiveFilters();
  applyFilters();
}

// Functions for artwork interaction
function viewArtwork(artworkId) {
  // Redirect to artwork detail page or open modal
  window.location.href = `artworkPreview.php?id=${artworkId}`;
}

// The addToCart function is now handled by cart.js through event delegation
// No need for a separate function here since cart.js listens for .add-to-cart-btn clicks

// The addToWishlist function is now handled by wishlist-manager.js through event delegation
// No need for a separate function here since wishlist-manager.js listens for .love-btn clicks

// Load artworks with pagination and filters
function loadArtworks(page = 1) {
  if (isLoading) return;
  
  isLoading = true;
  const artworksGrid = document.getElementById('artworksGrid');
  const loadingMessage = document.getElementById('loadingMessage');
  
  // Show loading message
  if (loadingMessage) {
    loadingMessage.style.display = 'block';
  }
  
  // Build API URL with pagination and filters
  const params = new URLSearchParams({
    page: page,
    limit: itemsPerPage
  });
  
  // Add filters if they exist
  if (typeof artworkActiveFilters !== 'undefined') {
    if (artworkActiveFilters.search) {
      params.append('search', artworkActiveFilters.search);
    }
    if (artworkActiveFilters.category && artworkActiveFilters.category !== 'all') {
      params.append('category', artworkActiveFilters.category);
    }
    if (artworkActiveFilters.artist_id && artworkActiveFilters.artist_id !== 'all') {
      params.append('artist_id', artworkActiveFilters.artist_id);
    }
    if (artworkActiveFilters.sort && artworkActiveFilters.sort !== 'featured') {
      params.append('sort_by', artworkActiveFilters.sort);
    }
    if (artworkActiveFilters.minPrice) {
      params.append('min_price', artworkActiveFilters.minPrice);
    }
    if (artworkActiveFilters.maxPrice) {
      params.append('max_price', artworkActiveFilters.maxPrice);
    }
  }
  
  const apiUrl = `./API/getAllArtworks.php?${params.toString()}`;
  console.log('Loading artworks from:', apiUrl);
  
  fetch(apiUrl)
  .then(response => {
    console.log('API Response status:', response.status);
    return response.json();
  })
  .then(data => {
    console.log('API Response data:', data);
    if (data.success && data.data) {
      currentPage = page; // Update currentPage FIRST
      renderArtworks(data.data);
      updatePagination(data.pagination || {}, data.total_count || 0);
      updateArtworkCount(data.total_count || 0);
    } else {
      console.log('API returned no data or error:', data);
      showError('No artworks available', data.message);
    }
  })
  .catch(error => {
    console.error('Error loading artworks:', error);
    showError('Unable to load artworks', 'Please check your connection and try again.');
  })
  .finally(() => {
    isLoading = false;
    if (loadingMessage) {
      loadingMessage.style.display = 'none';
    }
  });
}

// Render artworks to the grid
function renderArtworks(artworks) {
  // Only populate artist dropdown once, on first load
  if (!renderArtworks.artistDropdownPopulated) {
    const artistFilter = document.getElementById('artistFilter');
    if (artistFilter) {
      fetch('./API/getAllArtworks.php?limit=10000')
        .then(res => res.json())
        .then(data => {
          // Deduplicate by artist_id and display_name
          const seen = new Set();
          const uniqueArtists = [];
          if (data && data.data) {
            data.data.forEach(a => {
              if (a.artist && a.artist.artist_id && a.artist.display_name) {
                const key = a.artist.artist_id + '|' + a.artist.display_name;
                if (!seen.has(key)) {
                  seen.add(key);
                  uniqueArtists.push({ id: a.artist.artist_id, name: a.artist.display_name });
                }
              }
            });
          }
          const options = ['<option value="all">All Artists</option>'];
          uniqueArtists.forEach(artist => {
            options.push(`<option value="${artist.id}">${artist.name}</option>`);
          });
          artistFilter.innerHTML = options.join('');
          // Set the dropdown to the current filter value
          if (artistFilter.value !== artworkActiveFilters.artist_id) {
            artistFilter.value = artworkActiveFilters.artist_id;
          }
        });
    }
    renderArtworks.artistDropdownPopulated = true;
  }
  const artworksGrid = document.getElementById('artworksGrid');
  const loadingMessage = document.getElementById('loadingMessage');
  // Clear existing content
  artworksGrid.innerHTML = '';
  artworks.forEach(artwork => {
    const card = createArtworkCard(artwork);
    artworksGrid.appendChild(card);
  });
  // Re-add loading message for future use
  if (loadingMessage) {
    artworksGrid.appendChild(loadingMessage);
    loadingMessage.style.display = 'none';
  }
}

// Create individual artwork card
function createArtworkCard(artwork) {
  const card = document.createElement('div');
  card.className = 'enhanced-artwork-card';
  card.setAttribute('data-category', artwork.type);
  card.setAttribute('data-price', artwork.price);
  
  // Only show image if artwork has image_src
  let imageElement = '';
  
  if (artwork.image_src) {
    imageElement = `
      <img src="${escapeHtml(artwork.image_src)}" 
           alt="${escapeHtml(artwork.title)}" 
           class="enhanced-artwork-image" 
           loading="lazy" 
           data-artwork-id="${artwork.artwork_id}">
    `;
  } else {
    imageElement = `
      <div class="no-image-available">
        <i class="fas fa-image no-image-icon"></i>
        <div class="no-image-text">No image available</div>
      </div>
    `;
  }
  
  // Generate action button
  let actionButton = '';
  if (artwork.is_available && !artwork.on_auction) {
    actionButton = `<button class="enhanced-add-to-cart add-to-cart-btn" data-artwork-id="${artwork.artwork_id}" data-quantity="1">Add to Cart</button>`;
  } else {
    actionButton = `<button class="enhanced-add-to-cart" disabled>${escapeHtml(artwork.status_text)}</button>`;
  }
  
  card.innerHTML = `
    <div class="artwork-image-container">
      ${imageElement}
      <div class="artwork-overlay">
        <div class="quick-actions">
          <button class="quick-action-btn" onclick="viewArtwork(${artwork.artwork_id})">
            <i class="fas fa-eye"></i>
          </button>
        </div>
      </div>
    </div>
    <div class="enhanced-artwork-info">
      <div class="artwork-category">${escapeHtml(artwork.category)}</div>
      <h3 class="enhanced-artwork-title">${escapeHtml(artwork.title)}</h3>
      <p class="enhanced-artwork-artist">${escapeHtml(artwork.artist.display_name)}</p>
      <p class="enhanced-artwork-price">${escapeHtml(artwork.formatted_price)}</p>
      <p class="artwork-dimensions">${escapeHtml(artwork.dimensions)}</p>
      <p class="enhanced-artwork-description">${escapeHtml(artwork.description)}</p>
      <div class="artwork-actions">
        ${actionButton}
        <button class="wishlist-btn love-btn" data-artwork-id="${artwork.artwork_id}">
          <i class="far fa-heart"></i>
        </button>
      </div>
    </div>
  `;
  
  // Add event listeners for image error handling
  const img = card.querySelector('.enhanced-artwork-image');
  if (img) {
    img.addEventListener('error', (e) => {
      // Prevent the error from appearing in console
      e.preventDefault();
      e.stopPropagation();
      handleImageError(img, artwork.artwork_id);
      return false;
    });
  }
  
  return card;
}

// Update pagination controls
function updatePagination(pagination, total) {
  totalPages = pagination.total_pages || 1;
  totalCount = total;
  
  const paginationNumbers = document.getElementById('paginationNumbers');
  const prevBtn = document.getElementById('prevBtn');
  const nextBtn = document.getElementById('nextBtn');
  
  if (!paginationNumbers) return;
  
  // Update button states
  if (prevBtn) {
    prevBtn.disabled = !pagination.has_previous;
  }
  if (nextBtn) {
    nextBtn.disabled = !pagination.has_next;
  }
  
  // Generate pagination numbers
  paginationNumbers.innerHTML = generatePaginationNumbers(currentPage, totalPages);
}

// Generate pagination number buttons
function generatePaginationNumbers(current, total) {
  let html = '';
  
  if (total <= 7) {
    // Show all pages if total is 7 or less
    for (let i = 1; i <= total; i++) {
      html += `<button class="pagination-number ${i === current ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
    }
  } else {
    // Show condensed pagination for more than 7 pages
    if (current <= 4) {
      // Show first 5 pages
      for (let i = 1; i <= 5; i++) {
        html += `<button class="pagination-number ${i === current ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
      }
      html += '<span class="pagination-dots">...</span>';
      html += `<button class="pagination-number" onclick="goToPage(${total})">${total}</button>`;
    } else if (current >= total - 3) {
      // Show last 5 pages
      html += `<button class="pagination-number" onclick="goToPage(1)">1</button>`;
      html += '<span class="pagination-dots">...</span>';
      for (let i = total - 4; i <= total; i++) {
        html += `<button class="pagination-number ${i === current ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
      }
    } else {
      // Show middle pages
      html += `<button class="pagination-number" onclick="goToPage(1)">1</button>`;
      html += '<span class="pagination-dots">...</span>';
      for (let i = current - 1; i <= current + 1; i++) {
        html += `<button class="pagination-number ${i === current ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
      }
      html += '<span class="pagination-dots">...</span>';
      html += `<button class="pagination-number" onclick="goToPage(${total})">${total}</button>`;
    }
  }
  
  return html;
}

// Update artwork count display
function updateArtworkCount(total) {
  const countElement = document.getElementById('artworkCount');
  if (countElement) {
    const startRange = ((currentPage - 1) * itemsPerPage) + 1;
    const endRange = Math.min(currentPage * itemsPerPage, total);
    countElement.textContent = `${startRange}-${endRange} of ${total}`;
  }
}

// Show error message
function showError(title, message) {
  const artworksGrid = document.getElementById('artworksGrid');
  artworksGrid.innerHTML = `
    <div class="error-message">
      <div class="message-content">
        <i class="fas fa-exclamation-triangle" style="font-size: 4rem; color: #e74c3c; margin-bottom: 1rem;"></i>
        <h3>${escapeHtml(title)}</h3>
        <p>${escapeHtml(message)}</p>
        <button class="retry-btn" onclick="loadArtworks(${currentPage})">Retry</button>
      </div>
    </div>
  `;
}

// Pagination functions
function goToPage(page) {
  if (page !== currentPage && page >= 1 && page <= totalPages) {
    loadArtworks(page);
    
    // Scroll to the gallery header section
    const gallerySection = document.querySelector('.artwork-gallery');
    if (gallerySection) {
      gallerySection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }
}

function previousPage() {
  if (currentPage > 1) {
    goToPage(currentPage - 1);
  }
}

function nextPage() {
  if (currentPage < totalPages) {
    goToPage(currentPage + 1);
  }
}

// Utility function to escape HTML
function escapeHtml(text) {
  if (text == null) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// Handle image loading errors - show no image icon instead of hiding
function handleImageError(img, artworkId) {
  // Replace the failed image with a no-image placeholder
  const container = img.parentElement;
  if (container) {
    // Create a proper placeholder div
    const placeholder = document.createElement('div');
    placeholder.className = 'no-image-available';
    placeholder.style.cssText = `
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      height: 200px;
      background-color: #f5f5f5;
      border: 2px dashed #ddd;
      color: #999;
      font-size: 14px;
    `;
    placeholder.innerHTML = `
      <div style="font-size: 48px; margin-bottom: 10px;">🖼️</div>
      <div>Image not available</div>
    `;
    
    // Replace the img with the placeholder
    container.replaceChild(placeholder, img);
  }
}

// Debounce function to limit how often a function is called
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

// Export functions for global access
window.applyFilters = applyFilters;
window.clearAllFilters = clearAllFilters;
window.viewArtwork = viewArtwork;
// addToCart is now handled by cart.js through event delegation
// Wishlist functionality is now handled by wishlist-manager.js
window.goToPage = goToPage;
window.previousPage = previousPage;
window.nextPage = nextPage;