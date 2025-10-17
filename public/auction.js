// Auction Page JavaScript

// Global variables for auction data
let auctions = [];
let filteredAuctions = [];
let activeFilters = {};
let isLoading = false;

// API endpoint for auctions
const AUCTION_API_URL = './API/getAllAuction.php';

// Pagination variables
let currentPage = 1;
const auctionsPerPage = 12;
let totalPages = 1;

// DOM elements
const auctionGrid = document.getElementById('auctionGrid');
const loadingContainer = document.getElementById('loadingContainer');
const noResults = document.getElementById('noResults');
const searchInput = document.getElementById('searchInput');
const categoryFilter = document.getElementById('categoryFilter');
const statusFilter = document.getElementById('statusFilter');
const artistFilter = document.getElementById('artistFilter');
const minPriceInput = document.getElementById('minPrice');
const maxPriceInput = document.getElementById('maxPrice');

// Load auctions from API
async function loadAuctions(filters = {}) {
    isLoading = true;
    showLoading();
    
    try {
        // Build URL with filters
        const params = new URLSearchParams();
        
    if (filters.search) params.append('search', filters.search);
    if (filters.type && filters.type !== 'all') params.append('type', filters.type);
    if (filters.status) params.append('status', filters.status);
    if (filters.artist_id && filters.artist_id !== 'all') params.append('artist_id', filters.artist_id);
    if (filters.min_price) params.append('min_price', filters.min_price);
    if (filters.max_price) params.append('max_price', filters.max_price);
        
        // Add pagination
        params.append('limit', auctionsPerPage);
        params.append('offset', (currentPage - 1) * auctionsPerPage);
        
        const url = `${AUCTION_API_URL}?${params.toString()}`;
        console.log('Loading auctions from:', url);
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            auctions = data.data;
            totalPages = Math.ceil(data.total_count / auctionsPerPage);

            // Populate filters dynamically
            populateFilterDropdowns(auctions);

            renderAuctions(auctions);
            updatePaginationControls();
            updateAuctionCount(data.total_count);

            // Initialize timers for newly loaded auctions
            initializeTimers();
        } else {
            console.error('API Error:', data.message);
            showError('Failed to load auctions: ' + data.message);
        }
// Dynamically populate filter dropdowns based on auction data
function populateFilterDropdowns(auctions) {
    // Category
    if (categoryFilter) {
        const categories = Array.from(new Set(auctions.map(a => a.category && a.category.trim()).filter(Boolean)));
        categoryFilter.innerHTML = '<option value="all">All Categories</option>' +
            categories.map(cat => `<option value="${cat}">${cat}</option>`).join('');
    }
    // Status
    if (statusFilter) {
        const statuses = Array.from(new Set(auctions.map(a => a.auction && a.auction.status && a.auction.status.trim()).filter(Boolean)));
        statusFilter.innerHTML = '<option value="all">All Statuses</option>' +
            statuses.map(st => `<option value="${st}">${st.charAt(0).toUpperCase() + st.slice(1)}</option>`).join('');
    }
    // Artist
    if (artistFilter) {
        const artists = Array.from(new Map(
            auctions
                .filter(a => a.artist && a.artist.artist_id && a.artist.display_name)
                .map(a => [a.artist.artist_id, a.artist.display_name])
        ));
        artistFilter.innerHTML = '<option value="all">All Artists</option>' +
            artists.map(([id, name]) => `<option value="${id}">${name}</option>`).join('');
    }
}
    } catch (error) {
        console.error('Error loading auctions:', error);
        showError('Failed to load auctions. Please try again.');
    } finally {
        isLoading = false;
        hideLoading();
    }
}

// Show loading spinner
function showLoading() {
    if (loadingContainer) {
        loadingContainer.style.display = 'flex';
    }
    if (auctionGrid) {
        auctionGrid.style.opacity = '0.5';
    }
}

// Hide loading spinner
function hideLoading() {
    if (loadingContainer) {
        loadingContainer.style.display = 'none';
    }
    if (auctionGrid) {
        auctionGrid.style.opacity = '1';
    }
}

// Show error message
function showError(message) {
    if (auctionGrid) {
        auctionGrid.innerHTML = `
            <div class="error-message">
                <div class="error-icon">⚠️</div>
                <h3>Error Loading Auctions</h3>
                <p>${message}</p>
                <button class="retry-btn" onclick="loadAuctions()">Try Again</button>
            </div>
        `;
    }
}

// Additional DOM elements
const activeFiltersContainer = document.getElementById("activeFilters");
const searchResults = document.getElementById("searchResults");

document.addEventListener('DOMContentLoaded', function() {
    initializeAuctionPage();
});

function initializeAuctionPage() {
    // Load auctions from API
    loadAuctions();
    
    // Setup event listeners and initialize components
    setupEventListeners();
    updatePaginationControls();
    
    initializeTimers();
    initializeWatchlist();
    updateCartCount();
    updateWishlistCount();
}

// Setup event listeners for filters and search
function setupEventListeners() {
    // Search input
    if (searchInput) {
        searchInput.addEventListener("input", debounce(applyFilters, 300));
    }

    // Filter dropdowns and inputs
    if (categoryFilter) categoryFilter.addEventListener("change", applyFilters);
    if (statusFilter) statusFilter.addEventListener("change", applyFilters);
    if (artistFilter) artistFilter.addEventListener("change", applyFilters);
    if (minPriceInput) minPriceInput.addEventListener("input", debounce(applyFilters, 300));
    if (maxPriceInput) maxPriceInput.addEventListener("input", debounce(applyFilters, 300));
    // Artist
    if (artistFilter) {
        const artists = Array.from(new Set(auctions.map(a => a.artist && (a.artist.display_name || a.artist.name || a.artist).trim()).filter(Boolean)));
        artistFilter.innerHTML = '<option value="all">All Artists</option>' +
            artists.map(artist => `<option value="${artist}">${artist}</option>`).join('');
    }
}

// Render auctions on the page
function renderAuctions(auctionsToRender) {
    if (!auctionGrid) return;
    
    // Clear existing auctions
    auctionGrid.innerHTML = '';
    
    if (!auctionsToRender || auctionsToRender.length === 0) {
        showNoResults();
        return;
    }

    // Hide no results
    if (noResults) {
        noResults.style.display = 'none';
    }

    // Create auction cards
    auctionsToRender.forEach(auction => {
        const card = document.createElement("div");
        card.className = `auctionCard ${getStatusClass(auction.auction.status)}`;
        card.dataset.category = auction.category ? auction.category.toLowerCase() : '';
        card.dataset.artistId = auction.artist && auction.artist.artist_id ? auction.artist.artist_id : '';
        card.dataset.price = auction.auction.current_bid || auction.auction.starting_bid;

        card.innerHTML = `
            <div class="auctionImageContainer">
                <img 
                    src="${auction.image_missing ? '/image/placeholder-artwork.jpg' : (auction.image_src || '/image/placeholder-artwork.jpg')}" 
                    alt="${auction.title}"
                    class="auctionImage"
                    onerror="this.src='/image/placeholder-artwork.jpg'"
                />
                ${getStatusHTML(auction)}
                ${getTimerHTML(auction)}
            </div>
            
            <div class="auctionInfo">
                <div class="auctionContent">
                    <h3 class="auctionTitle">${auction.title}</h3>
                    <p class="auctionArtist">${auction.artist && auction.artist.display_name ? auction.artist.display_name : ''}</p>
                    <p class="auctionDescription">
                        ${auction.description && auction.description.length > 100 ? 
                            auction.description.substring(0, 100) + '...' : 
                            auction.description || ''}
                    </p>
                    
                    ${getPriceHTML(auction)}
                    ${getBidsCountHTML(auction)}
                </div>
                ${getActionsHTML(auction)}
            </div>
        `;

        auctionGrid.appendChild(card);
    });

    // Re-initialize timers for newly rendered auctions
    initializeTimers();
}

// Render paginated auctions (for compatibility with existing code)
function renderPaginatedAuctions() {
    // This function is now handled by renderAuctions since we're using API pagination
    // Keeping for backward compatibility
    console.log('renderPaginatedAuctions called - handled by API pagination');
}

// Helper function to show no results
function showNoResults() {
    if (auctionGrid) {
        auctionGrid.innerHTML = `
            <div class="no-results-container">
                <div class="no-results-icon">🎨</div>
                <h3>No auctions found</h3>
                <p>Try adjusting your search terms or filters</p>
                <button class="clear-search-btn" onclick="clearAllFilters()">Clear All Filters</button>
            </div>
        `;
    }
}

// Get status CSS class
function getStatusClass(status) {
    const statusMap = {
        'active': 'live',
        'upcoming': 'upcoming', 
        'starting_soon': 'upcoming',
        'sold': 'ended',
        'cancelled': 'ended'
    };
    return statusMap[status] || 'upcoming';
}

// Generate status HTML
function getStatusHTML(auction) {
    const status = auction.auction.status;
    const statusClass = getStatusClass(status);
    
    let statusText = status.toUpperCase();
    let icon = 'fas fa-clock';
    
    switch (status) {
        case 'active':
            statusText = 'LIVE';
            icon = 'fas fa-circle';
            break;
        case 'upcoming':
        case 'starting_soon':
            statusText = 'UPCOMING';
            icon = 'fas fa-clock';
            break;
        case 'sold':
            statusText = 'SOLD';
            icon = 'fas fa-check';
            break;
        case 'cancelled':
            statusText = 'CANCELLED';
            icon = 'fas fa-times';
            break;
    }
    
    return `<div class="auctionStatus ${statusClass}">
        <i class="${icon}"></i>
        <span>${statusText}</span>
    </div>`;
}

// Generate timer HTML
function getTimerHTML(auction) {
    const status = auction.auction.status;
    const endTime = auction.auction.end_time;
    const startTime = auction.auction.start_time;
    
    if (status === 'active' && endTime) {
        return `<div class="auctionTimer" data-end-time="${endTime}">
            <i class="fas fa-hourglass-half"></i>
            <span class="timeRemaining">Loading...</span>
        </div>`;
    } else if ((status === 'upcoming' || status === 'starting_soon') && startTime) {
        return `<div class="auctionTimer" data-start-time="${startTime}">
            <i class="fas fa-calendar"></i>
            <span class="timeRemaining">Loading...</span>
        </div>`;
    }
    
    return '';
}

// Generate price HTML
function getPriceHTML(auction) {
    const currentBid = auction.auction.current_bid;
    const startingBid = auction.auction.starting_bid;
    const displayPrice = currentBid || startingBid;
    
    return `<div class="auctionPricing">
        <div class="currentBid">
            <span class="bidLabel">${currentBid ? 'Current Bid' : 'Starting Bid'}</span>
            <span class="bidAmount">EGP ${parseFloat(displayPrice).toLocaleString()}</span>
        </div>
    </div>`;
}

// Generate bids count HTML
function getBidsCountHTML(auction) {
    const bidCount = auction.auction.bid_count || 0;
    
    return `<div class="bidsCount">
        <i class="fas fa-gavel"></i>
        <span>${bidCount} ${bidCount === 1 ? 'bid' : 'bids'}</span>
    </div>`;
}

// Generate actions HTML
function getActionsHTML(auction) {
    const status = auction.auction.status;
    const auctionId = auction.auction_id;
    
    let primaryButton = '';
    
    switch (status) {
        case 'active':
            primaryButton = `<button class="bidNowBtn" onclick="openAuctionPreview(${auctionId})">
                <i class="fas fa-gavel"></i>
                Bid Now
            </button>`;
            break;
        case 'upcoming':
        case 'starting_soon':
            primaryButton = `<button class="preRegisterBtn" onclick="openAuctionPreview(${auctionId})">
                <i class="fas fa-bell"></i>
                Pre-Register
            </button>`;
            break;
        default:
            primaryButton = `<button class="viewDetailsBtn" onclick="openAuctionPreview(${auctionId})">
                <i class="fas fa-eye"></i>
                View Details
            </button>`;
            break;
    }
    
    const watchIcon = status === 'sold' || status === 'cancelled' ? 'fas fa-heart disabled' : 'far fa-heart';
    const watchDisabled = status === 'sold' || status === 'cancelled' ? 'disabled' : '';
    
    return `<div class="auctionActions">
        ${primaryButton}
        <button class="watchBtn ${watchDisabled}">
            <i class="${watchIcon}"></i>
        </button>
    </div>`;
}

// Pagination functions - now reload from API
function previousPage() {
    if (currentPage > 1 && !isLoading) {
        currentPage--;
        loadAuctions(getCurrentFilters());
        scrollToTop();
    }
}

function nextPage() {
    if (currentPage < totalPages && !isLoading) {
        currentPage++;
        loadAuctions(getCurrentFilters());
        scrollToTop();
    }
}

function goToPage(page) {
    if (page >= 1 && page <= totalPages && page !== currentPage && !isLoading) {
        currentPage = page;
        loadAuctions(getCurrentFilters());
        scrollToTop();
    }
}

// Helper function to get current filters
function getCurrentFilters() {
    const filters = {};
    
    const searchTerm = searchInput ? searchInput.value.trim() : '';
    const selectedCategory = categoryFilter ? categoryFilter.value : '';
    const selectedStatus = statusFilter ? statusFilter.value : '';
    const minPrice = minPriceInput ? parseFloat(minPriceInput.value) || null : null;
    const maxPrice = maxPriceInput ? parseFloat(maxPriceInput.value) || null : null;
    const selectedArtist = artistFilter ? artistFilter.value : '';

    if (searchTerm) filters.search = searchTerm;
    if (selectedCategory && selectedCategory !== 'all') filters.type = selectedCategory;
    if (selectedStatus && selectedStatus !== 'all') filters.status = selectedStatus;
    if (selectedArtist && selectedArtist !== 'all') filters.artist_id = selectedArtist;
    if (minPrice) filters.min_price = minPrice;
    if (maxPrice) filters.max_price = maxPrice;

    return filters;
}

// Scroll to top helper
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Update pagination controls
function updatePaginationControls() {
    const paginationSection = document.querySelector('.pagination-section');
    const prevBtn = document.getElementById('prevPage');
    const nextBtn = document.getElementById('nextPage');
    const pageNumbers = document.getElementById('pageNumbers');
    const currentPageSpan = document.getElementById('currentPage');
    const totalPagesSpan = document.getElementById('totalPages');
    
    if (!paginationSection) return;
    
    // Show/hide pagination based on total pages
    if (totalPages <= 1) {
        paginationSection.style.display = 'none';
        return;
    } else {
        paginationSection.style.display = 'block';
    }
    
    // Update prev/next buttons
    if (prevBtn) {
        prevBtn.disabled = currentPage <= 1;
    }
    if (nextBtn) {
        nextBtn.disabled = currentPage >= totalPages;
    }
    
    // Update page info
    if (currentPageSpan) currentPageSpan.textContent = currentPage;
    if (totalPagesSpan) totalPagesSpan.textContent = totalPages;
    
    // Update page numbers
    if (pageNumbers) {
        pageNumbers.innerHTML = generatePageNumbers();
    }
}

// Generate page numbers HTML
function generatePageNumbers() {
    let pagesHtml = '';
    const maxVisiblePages = 5;
    
    if (totalPages <= maxVisiblePages) {
        // Show all pages
        for (let i = 1; i <= totalPages; i++) {
            const activeClass = i === currentPage ? 'active' : '';
            pagesHtml += `<button class="pagination-number ${activeClass}" onclick="goToPage(${i})">${i}</button>`;
        }
    } else {
        // Show pages with ellipsis
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);
        
        // First page
        if (startPage > 1) {
            pagesHtml += `<button class="pagination-number" onclick="goToPage(1)">1</button>`;
            if (startPage > 2) {
                pagesHtml += `<span class="pagination-dots">...</span>`;
            }
        }
        
        // Middle pages
        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === currentPage ? 'active' : '';
            pagesHtml += `<button class="pagination-number ${activeClass}" onclick="goToPage(${i})">${i}</button>`;
        }
        
        // Last page
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                pagesHtml += `<span class="pagination-dots">...</span>`;
            }
            pagesHtml += `<button class="pagination-number" onclick="goToPage(${totalPages})">${totalPages}</button>`;
        }
    }
    
    return pagesHtml;
}

// Clear all filters function
function clearAllFilters() {
    if (searchInput) searchInput.value = '';
    if (categoryFilter) categoryFilter.value = 'all';
    if (statusFilter) statusFilter.value = 'all';
    if (artistFilter) artistFilter.value = 'all';
    if (minPriceInput) minPriceInput.value = '';
    if (maxPriceInput) maxPriceInput.value = '';

    // Reset active filters
    activeFilters = {};

    // Reset to first page and reload
    currentPage = 1;
    loadAuctions();

    // Update display
    renderActiveFilters();
}

// These functions are no longer needed since we load directly from API
// but keeping updateAuctionCount for compatibility
function updateAuctionCount(totalCount = 0) {
    // Update any UI elements that show total auction count
    console.log(`Total auctions: ${totalCount}`);
}

function renderAuctionsGrid(auctionsToRender) {
    auctionGrid.innerHTML = '';
    
    if (auctionsToRender.length === 0) {
        auctionGrid.style.display = 'none';
        noResults.style.display = 'block';
        // Hide pagination when no results
        const paginationSection = document.querySelector('.pagination-section');
        if (paginationSection) {
            paginationSection.style.display = 'none';
        }
        return;
    } else {
        auctionGrid.style.display = 'grid';
        noResults.style.display = 'none';
        // Show pagination when there are results
        const paginationSection = document.querySelector('.pagination-section');
        if (paginationSection) {
            paginationSection.style.display = totalPages > 1 ? 'block' : 'none';
        }
    }

    auctionsToRender.forEach(auction => {
        const auctionCard = document.createElement('div');
        auctionCard.className = `auctionCard ${auction.status}`;
        auctionCard.dataset.category = auction.category;
        auctionCard.dataset.price = auction.price;

        const statusHTML = getStatusHTML(auction);
        const timerHTML = getTimerHTML(auction);
        const priceHTML = getPriceHTML(auction);
        const actionsHTML = getActionsHTML(auction);

        auctionCard.innerHTML = `
            <div class="auctionImageContainer">
                <img 
                    src="${auction.image_missing ? '/image/placeholder-artwork.jpg' : (auction.image_src || auction.image || '/image/placeholder-artwork.jpg')}" 
                    alt="${auction.title}"
                    class="auctionImage"
                    onerror="this.src='/image/placeholder-artwork.jpg'"
                />
                ${statusHTML}
                ${timerHTML}
                ${auction.status === 'ended' ? '<div class="soldOverlay"><span>SOLD</span></div>' : ''}
            </div>
            
            <div class="auctionInfo">
                <h3 class="auctionTitle">${auction.title}</h3>
                <p class="auctionArtist">by ${auction.artist}</p>
                <p class="auctionDescription">${auction.description}</p>
                
                <div class="auctionPricing">
                    ${priceHTML}
                    <div class="bidsCount">
                        ${getBidsCountHTML(auction)}
                    </div>
                </div>
                
                <div class="auctionActions">
                    ${actionsHTML}
                </div>
            </div>
        `;

        auctionGrid.appendChild(auctionCard);
    });

    // Re-initialize timers and watchlist for new cards
    initializeTimers();
    initializeWatchlist();
}

function getStatusHTML(auction) {
    const statusClass = auction.status;
    const statusIcon = auction.status === 'live' ? 'fas fa-circle' : 
                     auction.status === 'upcoming' ? 'fas fa-calendar' : 'fas fa-check';
    const statusText = auction.status === 'live' ? 'LIVE' : 
                      auction.status === 'upcoming' ? 'UPCOMING' : 'SOLD';
    
    return `<div class="auctionStatus ${statusClass}">
                <i class="${statusIcon}"></i>
                <span>${statusText}</span>
            </div>`;
}

function getStatusHTML(auction) {
    const status = auction.auction.status;
    const statusMap = {
        'active': { class: 'live', icon: 'fas fa-circle', text: 'LIVE' },
        'upcoming': { class: 'upcoming', icon: 'fas fa-calendar', text: 'UPCOMING' },
        'starting_soon': { class: 'upcoming', icon: 'fas fa-calendar', text: 'STARTING SOON' },
        'sold': { class: 'ended', icon: 'fas fa-check', text: 'SOLD' },
        'cancelled': { class: 'ended', icon: 'fas fa-times', text: 'CANCELLED' }
    };
    
    const statusInfo = statusMap[status] || statusMap['upcoming'];
    
    return `<div class="auctionStatus ${statusInfo.class}">
                <i class="${statusInfo.icon}"></i>
                <span>${statusInfo.text}</span>
            </div>`;
}

function getTimerHTML(auction) {
    const status = auction.auction.status;
    
    if (status === 'active' && auction.auction.end_time) {
        return `<div class="auctionTimer" data-end-time="${auction.auction.end_time}">
                    <i class="fas fa-clock"></i>
                    <span class="timeRemaining">Calculating...</span>
                </div>`;
    } else if ((status === 'upcoming' || status === 'starting_soon') && auction.auction.start_time) {
        return `<div class="auctionTimer" data-start-time="${auction.auction.start_time}">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="timeRemaining">Calculating...</span>
                </div>`;
    }
    return '';
}

function getPriceHTML(auction) {
    const status = auction.auction.status;
    let label, price;
    
    if (status === 'sold' || status === 'cancelled') {
        label = 'Final Price';
        price = auction.auction.formatted_current_bid || auction.auction.formatted_starting_bid;
    } else if (status === 'upcoming' || status === 'starting_soon') {
        label = 'Starting Bid';
        price = auction.auction.formatted_starting_bid;
    } else {
        label = 'Current Bid';
        price = auction.auction.formatted_current_bid || auction.auction.formatted_starting_bid;
    }
    
    return `<div class="auctionPricing">
                <div class="currentBid">
                    <span class="bidLabel">${label}</span>
                    <span class="bidAmount">${price}</span>
                </div>
            </div>`;
}

function getBidsCountHTML(auction) {
    const status = auction.auction.status;
    
    if (status === 'active') {
        // For active auctions, show bidder count (we can get this from auction bids later)
        return `<div class="bidsCount">
                    <i class="fas fa-users"></i>
                    <span>Active bidding</span>
                </div>`;
    } else if (status === 'upcoming' || status === 'starting_soon') {
        return `<div class="bidsCount">
                    <i class="fas fa-eye"></i>
                    <span>Auction starting soon</span>
                </div>`;
    } else if (status === 'sold') {
        return `<div class="bidsCount">
                    <i class="fas fa-trophy"></i>
                    <span>Auction completed</span>
                </div>`;
    } else if (status === 'cancelled') {
        return `<div class="bidsCount">
                    <i class="fas fa-times-circle"></i>
                    <span>Auction cancelled</span>
                </div>`;
    }
    return '';
}

function getActionsHTML(auction) {
    const buttonText = auction.button_text;
    const buttonType = auction.button_type;
    
    let buttonClass, buttonIcon;
    
    if (buttonType === 'pre-register') {
        buttonClass = 'preRegisterBtn';
        buttonIcon = 'fas fa-bell';
    } else {
        buttonClass = 'viewDetailsBtn';
        buttonIcon = 'fas fa-eye';
    }
    
    const watchButtonDisabled = (auction.auction.status === 'sold' || auction.auction.status === 'cancelled') ? 'disabled' : '';
    const watchButtonTitle = watchButtonDisabled ? 'Auction Ended' : 'Add to Watchlist';
    const watchButtonIcon = watchButtonDisabled ? 'fas fa-check' : 'far fa-heart';
    
    return `<div class="auctionActions">
                <button class="${buttonClass}" onclick="openAuctionPreview('auction-${auction.artwork_id}')">
                    <i class="${buttonIcon}"></i>
                    ${buttonText}
                </button>
                <button class="watchBtn ${watchButtonDisabled}" title="${watchButtonTitle}">
                    <i class="${watchButtonIcon}"></i>
                </button>
            </div>`;
}

function updatePaginationControls() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const paginationNumbers = document.getElementById('paginationNumbers');

    // Update previous button
    if (prevBtn) {
        prevBtn.disabled = currentPage === 1;
    }

    // Update next button
    if (nextBtn) {
        nextBtn.disabled = currentPage === totalPages;
    }

    // Update pagination numbers
    if (paginationNumbers) {
        paginationNumbers.innerHTML = '';

        if (totalPages <= 1) {
            // Hide pagination if only one page
            const paginationSection = document.querySelector('.pagination-section');
            if (paginationSection) {
                paginationSection.style.display = 'none';
            }
            return;
        } else {
            // Show pagination if more than one page
            const paginationSection = document.querySelector('.pagination-section');
            if (paginationSection) {
                paginationSection.style.display = 'block';
            }
        }

        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

        // Adjust start page if we're near the end
        if (endPage - startPage < maxVisiblePages - 1) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }

        // Add first page and dots if needed
        if (startPage > 1) {
            const firstPageBtn = document.createElement('button');
            firstPageBtn.className = 'pagination-number';
            firstPageBtn.textContent = '1';
            firstPageBtn.onclick = () => goToPage(1);
            paginationNumbers.appendChild(firstPageBtn);

            if (startPage > 2) {
                const dots = document.createElement('span');
                dots.className = 'pagination-dots';
                dots.textContent = '...';
                paginationNumbers.appendChild(dots);
            }
        }

        // Add visible page numbers
        for (let i = startPage; i <= endPage; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = `pagination-number ${i === currentPage ? 'active' : ''}`;
            pageBtn.textContent = i;
            pageBtn.onclick = () => goToPage(i);
            paginationNumbers.appendChild(pageBtn);
        }

        // Add last page and dots if needed
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                const dots = document.createElement('span');
                dots.className = 'pagination-dots';
                dots.textContent = '...';
                paginationNumbers.appendChild(dots);
            }

            const lastPageBtn = document.createElement('button');
            lastPageBtn.className = 'pagination-number';
            lastPageBtn.textContent = totalPages;
            lastPageBtn.onclick = () => goToPage(totalPages);
            paginationNumbers.appendChild(lastPageBtn);
        }
    }
}

function updateAuctionCount() {
    const startAuction = (currentPage - 1) * auctionsPerPage + 1;
    const endAuction = Math.min(currentPage * auctionsPerPage, filteredAuctions.length);
    
    // Update search results text
    if (searchResults) {
        if (filteredAuctions.length === auctions.length) {
            searchResults.innerHTML = `Showing ${startAuction}-${endAuction} of ${filteredAuctions.length} auctions`;
        } else {
            searchResults.innerHTML = `Found ${filteredAuctions.length} auctions - Showing ${startAuction}-${endAuction}`;
        }
        searchResults.style.display = 'block';
    }
}

function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Clear all filters function
function clearAllFilters() {
    if (searchInput) searchInput.value = '';
    if (categoryFilter) categoryFilter.value = '';
    if (statusFilter) statusFilter.value = '';
    if (minPriceInput) minPriceInput.value = '';
    if (maxPriceInput) maxPriceInput.value = '';

    activeFilters = {};
    filteredAuctions = [...auctions];
    currentPage = 1;
    totalPages = Math.ceil(auctions.length / auctionsPerPage);

    renderActiveFilters();
    updatePaginationControls();

    if (searchResults) {
        searchResults.style.display = 'none';
    }
}

// Render active filters
function renderActiveFilters() {
    if (!activeFiltersContainer) return;
    
    activeFiltersContainer.innerHTML = '';

    Object.entries(activeFilters).forEach(([key, value]) => {
        if (value && value !== '' && value !== 0 && value !== Infinity) {
            const filterTag = document.createElement('div');
            filterTag.className = 'filter-tag';

            let displayValue = value;
            if (key === 'searchTerm') {
                displayValue = `Search: ${value}`;
            } else if (key === 'minPrice' || key === 'maxPrice') {
                displayValue = `${key === 'minPrice' ? 'Min' : 'Max'} Price: EGP ${value.toLocaleString()}`;
            } else if (key === 'artist' || key === 'artist_id') {
                // Show artist name instead of ID
                const artistOption = artistFilter ? artistFilter.querySelector(`option[value='${value}']`) : null;
                const artistName = artistOption ? artistOption.textContent : value;
                displayValue = `Artist: ${artistName}`;
            } else {
                displayValue = `${key.charAt(0).toUpperCase() + key.slice(1)}: ${value}`;
            }

            filterTag.innerHTML = `
                <span>${displayValue}</span>
                <span class="remove-filter" onclick="removeFilter('${key}')">×</span>
            `;

            activeFiltersContainer.appendChild(filterTag);
        }
    });
}

// Remove individual filter
function removeFilter(filterKey) {
    switch (filterKey) {
        case 'searchTerm':
            if (searchInput) searchInput.value = '';
            break;
        case 'category':
            if (categoryFilter) categoryFilter.value = '';
            break;
        case 'status':
            if (statusFilter) statusFilter.value = '';
            break;
        case 'artist':
            if (artistFilter) artistFilter.value = 'all';
            break;
        case 'minPrice':
            if (minPriceInput) minPriceInput.value = '';
            break;
        case 'maxPrice':
            if (maxPriceInput) maxPriceInput.value = '';
            break;
    }
    applyFilters();
}

// Updated applyFilters function - now calls API with filters
function applyFilters() {
    if (isLoading) return; // Prevent multiple concurrent requests
    
    const searchTerm = searchInput ? searchInput.value.trim() : '';
    const selectedCategory = categoryFilter ? categoryFilter.value : '';
    const selectedStatus = statusFilter ? statusFilter.value : '';
    const minPrice = minPriceInput ? parseFloat(minPriceInput.value) || null : null;
    const maxPrice = maxPriceInput ? parseFloat(maxPriceInput.value) || null : null;
    const selectedArtist = artistFilter ? artistFilter.value : '';

    // Build filters object
    const filters = {};

    if (searchTerm) filters.search = searchTerm;
    if (selectedCategory && selectedCategory !== 'all') filters.type = selectedCategory;
    if (selectedStatus && selectedStatus !== 'all') filters.status = selectedStatus;
    if (selectedArtist && selectedArtist !== 'all') filters.artist_id = selectedArtist;
    if (minPrice) filters.min_price = minPrice;
    if (maxPrice) filters.max_price = maxPrice;

    // Reset to first page when filters change
    currentPage = 1;

    // Update active filters for display
    activeFilters = {};
    if (searchTerm) activeFilters.searchTerm = searchTerm;
    if (selectedCategory && selectedCategory !== 'all') activeFilters.category = selectedCategory;
    if (selectedStatus && selectedStatus !== 'all') activeFilters.status = selectedStatus;
    if (selectedArtist && selectedArtist !== 'all') activeFilters.artist = selectedArtist;
    if (minPrice) activeFilters.minPrice = minPrice;
    if (maxPrice) activeFilters.maxPrice = maxPrice;

    // Load auctions with filters
    loadAuctions(filters);

    // Update active filters display
    renderActiveFilters();
}

// Timer functionality for live auctions
function initializeTimers() {
    const timers = document.querySelectorAll('.auctionTimer[data-end-time]');
    
    timers.forEach(timer => {
        const endTime = new Date(timer.dataset.endTime).getTime();
        updateTimer(timer, endTime);
        
        // Update every minute
        setInterval(() => updateTimer(timer, endTime), 60000);
    });

    // Handle upcoming auction timers
    const upcomingTimers = document.querySelectorAll('.auctionTimer[data-start-time]');
    upcomingTimers.forEach(timer => {
        const startTime = new Date(timer.dataset.startTime).getTime();
        updateUpcomingTimer(timer, startTime);
        
        // Update every hour for upcoming auctions
        setInterval(() => updateUpcomingTimer(timer, startTime), 3600000);
    });
}

function updateTimer(timer, endTime) {
    const now = new Date().getTime();
    const timeLeft = endTime - now;
    
    if (timeLeft > 0) {
        const hours = Math.floor(timeLeft / (1000 * 60 * 60));
        const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
        
        const timeSpan = timer.querySelector('.timeRemaining');
        if (timeSpan) {
            timeSpan.textContent = `${hours}h ${minutes}m`;
        }
    } else {
        // Auction ended
        const timeSpan = timer.querySelector('.timeRemaining');
        if (timeSpan) {
            timeSpan.textContent = 'Ended';
        }
        
        // Convert card to ended state
        const card = timer.closest('.auctionCard');
        if (card) {
            card.classList.remove('live');
            card.classList.add('ended');
            
            const status = card.querySelector('.auctionStatus');
            if (status) {
                status.classList.remove('live');
                status.classList.add('ended');
                status.innerHTML = '<i class="fas fa-check"></i><span>ENDED</span>';
            }
            
            const bidBtn = card.querySelector('.bidNowBtn');
            if (bidBtn) {
                bidBtn.className = 'viewDetailsBtn';
                bidBtn.innerHTML = '<i class="fas fa-eye"></i>View Details';
            }
        }
    }
}

function updateUpcomingTimer(timer, startTime) {
    const now = new Date().getTime();
    const timeUntilStart = startTime - now;
    
    if (timeUntilStart > 0) {
        const days = Math.floor(timeUntilStart / (1000 * 60 * 60 * 24));
        const hours = Math.floor((timeUntilStart % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        
        const timeSpan = timer.querySelector('.timeRemaining');
        if (timeSpan) {
            if (days > 0) {
                timeSpan.textContent = `Starts in ${days}d ${hours}h`;
            } else {
                timeSpan.textContent = `Starts in ${hours}h`;
            }
        }
    } else {
        // Auction should have started
        const timeSpan = timer.querySelector('.timeRemaining');
        if (timeSpan) {
            timeSpan.textContent = 'Starting soon';
        }
    }
}

// Watchlist functionality
function initializeWatchlist() {
    const watchBtns = document.querySelectorAll('.watchBtn:not(.disabled)');
    
    watchBtns.forEach(btn => {
        btn.addEventListener('click', toggleWatchlist);
    });
}

function toggleWatchlist(event) {
    event.preventDefault();
    const btn = event.currentTarget;
    const icon = btn.querySelector('i');
    
    if (icon.classList.contains('far')) {
        // Add to watchlist
        icon.classList.remove('far');
        icon.classList.add('fas');
        btn.style.color = '#d4af37';
        btn.style.borderColor = '#d4af37';
        
        // Show notification
        showNotification('Added to watchlist', 'success');
        
        // Update wishlist count
        updateWishlistCount();
    } else {
        // Remove from watchlist
        icon.classList.remove('fas');
        icon.classList.add('far');
        btn.style.color = '#8b6f47';
        btn.style.borderColor = '#e8e4df';
        
        showNotification('Removed from watchlist', 'info');
    }
}

// Open auction preview page
function openAuctionPreview(auctionId) {
    // In a real application, this would navigate to the auction preview page
    window.location.href = `auction-preview.php?id=${auctionId}`;
}

// Utility functions
function updateCartCount() {
    const cartCount = document.getElementById('cartCount');
    const burgerCartCount = document.getElementById('burgerCartCount');
    
    // Get cart count from localStorage or default to 0
    const count = localStorage.getItem('cartCount') || '0';
    
    if (cartCount) cartCount.textContent = count;
    if (burgerCartCount) burgerCartCount.textContent = count;
}

function updateWishlistCount() {
    const wishlistCount = document.getElementById('wishlistCount');
    const burgerWishlistCount = document.getElementById('burgerWishlistCount');
    
    // Get wishlist count from localStorage or default to 0
    let count = parseInt(localStorage.getItem('wishlistCount') || '0');
    
    // Count active watchlist items on page
    const activeWatchItems = document.querySelectorAll('.watchBtn .fas.fa-heart').length;
    count = Math.max(count, activeWatchItems);
    
    localStorage.setItem('wishlistCount', count.toString());
    
    if (wishlistCount) {
        wishlistCount.textContent = count;
        wishlistCount.style.display = count > 0 ? 'flex' : 'none';
    }
    if (burgerWishlistCount) {
        burgerWishlistCount.textContent = count;
    }
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i>
        <span>${message}</span>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 2rem;
        right: 2rem;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#6b7280'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        z-index: 1000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Debounce function for search input
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
window.removeFilter = removeFilter;
window.openAuctionPreview = openAuctionPreview;
window.previousPage = previousPage;
window.nextPage = nextPage;
window.goToPage = goToPage;
