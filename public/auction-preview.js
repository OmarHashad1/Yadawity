// Auction Preview Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    loadAuctionData();
    initializeAuctionPreview();
});

function loadAuctionData() {
    const urlParams = new URLSearchParams(window.location.search);
    const auctionId = urlParams.get('id');
    if (!auctionId) return;
    fetch(`./API/getAuctionInfo.php?id=${auctionId}`)
        .then(res => res.json())
        .then(json => {
            if (!json.success || !json.data) return;
            const auction = json.data;
            // Main image
            document.getElementById('mainImage').src = auction.artwork.image_src;
            document.getElementById('mainImage').alt = auction.artwork.title;
            // Status
            document.getElementById('auctionStatusText').textContent = auction.status.status_text.toUpperCase();
            document.getElementById('auctionStatus').className = 'auctionStatus ' + (auction.status.is_active ? 'live' : auction.status.is_ended ? 'ended' : auction.status.is_upcoming ? 'upcoming' : '');
            // Meta
            document.getElementById('lotNumber').textContent = 'LOT #' + auction.auction_id;
            document.getElementById('auctionDate').textContent = auction.start_time ? `Live Auction - ${new Date(auction.start_time).toLocaleString()}` : '';
            document.getElementById('auctionTitle').textContent = auction.artwork.title;
            // Artist
            document.getElementById('artistLink').textContent = auction.artist.full_name;
            document.getElementById('artistLink').href = `artistProfile.php?id=${auction.artist.artist_id}`;
            document.getElementById('artistDetails').textContent = auction.artist.art_specialty ? `(${auction.artist.art_specialty})` : '';
            // Bid info
            document.getElementById('currentBidAmount').textContent = `EGP ${auction.current_bid.toLocaleString()}`;
            document.getElementById('bidderCount').textContent = `${auction.bid_count} ${auction.bid_count === 1 ? 'bidder' : 'bidders'}`;
            document.getElementById('watchingCount').textContent = '0 watching'; // You can update this if you have data
            document.getElementById('nextBidAmount').textContent = `EGP ${(auction.current_bid + 2500).toLocaleString()}`;
            // Details
            document.getElementById('medium').textContent = auction.artwork.material || '-';
            document.getElementById('dimensions').textContent = auction.artwork.dimensions || '-';
            document.getElementById('year').textContent = auction.artwork.year || '-';
            document.getElementById('signature').textContent = auction.artwork.signature || '-';
            document.getElementById('provenance').textContent = auction.artwork.provenance || '-';
            document.getElementById('condition').textContent = auction.artwork.condition || '-';
            document.getElementById('estimateRange').textContent = auction.estimate_range || '-';
            // Description
            document.getElementById('descriptionText').innerHTML = `<p>${auction.artwork.description || '-'}</p>`;
            // Artist bio
            document.getElementById('artistProfilePicture').src = auction.artist.profile_picture_url;
            document.getElementById('artistProfilePicture').alt = auction.artist.full_name;
            document.getElementById('artistName').textContent = auction.artist.full_name;
            document.getElementById('artistBioText').textContent = auction.artist.bio || '-';
            document.getElementById('viewProfileBtn').href = `artistProfile.php?id=${auction.artist.artist_id}`;

            // Show all available auction, artwork, and artist data in the console for debugging
            console.log('Auction Data:', auction);
            // Optionally, display all data in a dedicated section (for admin/debug)
            let allDataDiv = document.getElementById('allAuctionData');
            if (!allDataDiv) {
                allDataDiv = document.createElement('div');
                allDataDiv.id = 'allAuctionData';
                allDataDiv.style = 'margin:2rem 0;padding:1rem;background:#f8f8f8;border:1px solid #ccc;max-width:900px;overflow-x:auto;font-size:0.95em;';
                document.body.appendChild(allDataDiv);
            }
            allDataDiv.innerHTML = `<h3>All Auction Data (Debug)</h3><pre>${JSON.stringify(auction, null, 2)}</pre>`;
        });
}

// Timer functionality
function initializeTimer() {
    // Set auction end time (example: 2 hours 45 minutes from now)
    const endTime = new Date(Date.now() + (2 * 60 * 60 * 1000) + (45 * 60 * 1000) + (32 * 1000));
    updateTimer(endTime);
    
    // Update every second
    setInterval(() => updateTimer(endTime), 1000);
}

function updateTimer(endTime) {
    const now = new Date().getTime();
    const timeLeft = endTime - now;
    
    const timerDisplay = document.getElementById('timerDisplay');
    
    if (timeLeft > 0) {
        const hours = Math.floor(timeLeft / (1000 * 60 * 60));
        const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
        
        if (timerDisplay) {
            timerDisplay.textContent = `${hours}h ${minutes}m ${seconds}s`;
        }
        
        // Change color when time is running out
        const timerElement = document.getElementById('auctionTimer');
        if (timeLeft < 5 * 60 * 1000 && timerElement) { // Less than 5 minutes
            timerElement.style.background = 'linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)';
        }
    } else {
        // Auction ended
        if (timerDisplay) {
            timerDisplay.textContent = 'Auction Ended';
        }
        
        // Disable bidding
        const placeBidBtn = document.getElementById('placeBidBtn');
        const bidAmount = document.getElementById('bidAmount');
        const quickBidBtns = document.querySelectorAll('.quickBidBtn');
        
        if (placeBidBtn) {
            placeBidBtn.disabled = true;
            placeBidBtn.textContent = 'Auction Ended';
            placeBidBtn.style.background = '#6b7280';
        }
        
        if (bidAmount) {
            bidAmount.disabled = true;
        }
        
        quickBidBtns.forEach(btn => {
            btn.disabled = true;
            btn.style.opacity = '0.5';
        });
    }
}

// Bidding functionality
function initializeBidding() {
    const placeBidBtn = document.getElementById('placeBidBtn');
    const bidAmount = document.getElementById('bidAmount');
    const quickBidBtns = document.querySelectorAll('.quickBidBtn');
    
    if (placeBidBtn) {
        placeBidBtn.addEventListener('click', placeBid);
    }
    
    if (bidAmount) {
        bidAmount.addEventListener('input', validateBidAmount);
        bidAmount.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                placeBid();
            }
        });
    }
    
    // Quick bid buttons are handled by onclick in HTML
}

function setQuickBid(amount) {
    const bidAmount = document.getElementById('bidAmount');
    if (bidAmount) {
        bidAmount.value = amount;
        validateBidAmount();
    }
}

function validateBidAmount() {
    const bidAmount = document.getElementById('bidAmount');
    const placeBidBtn = document.getElementById('placeBidBtn');
    const currentBid = 75000; // This would come from the server
    const minNextBid = 77500;
    
    if (bidAmount && placeBidBtn) {
        const amount = parseInt(bidAmount.value);
        
        if (amount >= minNextBid) {
            placeBidBtn.disabled = false;
            placeBidBtn.style.opacity = '1';
            bidAmount.style.borderColor = '#d4af37';
        } else {
            placeBidBtn.disabled = true;
            placeBidBtn.style.opacity = '0.6';
            bidAmount.style.borderColor = '#dc2626';
        }
    }
}

function placeBid() {
    const bidAmount = document.getElementById('bidAmount');
    const maxBidCheckbox = document.getElementById('maxBidCheckbox');
    
    if (!bidAmount || !bidAmount.value) {
        showNotification('Please enter a bid amount', 'error');
        return;
    }
    
    const amount = parseInt(bidAmount.value);
    const minNextBid = 77500;
    
    if (amount < minNextBid) {
        showNotification(`Minimum bid is EGP ${minNextBid.toLocaleString()}`, 'error');
        return;
    }
    
    // Simulate bid placement
    const placeBidBtn = document.getElementById('placeBidBtn');
    const originalText = placeBidBtn.innerHTML;
    
    placeBidBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Placing Bid...';
    placeBidBtn.disabled = true;
    
    setTimeout(() => {
        // Update current bid
        updateCurrentBid(amount);
        
        // Add to bidding history
        addToBiddingHistory('you', amount, 'Just now');
        
        // Reset form
        bidAmount.value = '';
        placeBidBtn.innerHTML = originalText;
        placeBidBtn.disabled = false;
        
        // Show success message
        const isMaxBid = maxBidCheckbox && maxBidCheckbox.checked;
        const message = isMaxBid ? 
            `Maximum bid of EGP ${amount.toLocaleString()} placed successfully!` :
            `Bid of EGP ${amount.toLocaleString()} placed successfully!`;
        
        showNotification(message, 'success');
        
        // Update next minimum bid
        updateNextMinimumBid(amount + 2500);
        
    }, 1500);
}

function updateCurrentBid(amount) {
    const currentBidAmount = document.getElementById('currentBidAmount');
    const bidInput = document.getElementById('bidAmount');
    
    if (currentBidAmount) {
        currentBidAmount.textContent = `EGP ${amount.toLocaleString()}`;
        
        // Add animation
        currentBidAmount.style.transform = 'scale(1.1)';
        currentBidAmount.style.color = '#dc2626';
        
        setTimeout(() => {
            currentBidAmount.style.transform = 'scale(1)';
            currentBidAmount.style.color = '#d4af37';
        }, 300);
    }
    
    // Update quick bid buttons
    const quickBidBtns = document.querySelectorAll('.quickBidBtn');
    const newAmounts = [amount + 2500, amount + 5000, amount + 10000];
    
    quickBidBtns.forEach((btn, index) => {
        if (newAmounts[index]) {
            btn.textContent = `EGP ${newAmounts[index].toLocaleString()}`;
            btn.onclick = () => setQuickBid(newAmounts[index]);
        }
    });
    
    // Update minimum bid input
    if (bidInput) {
        bidInput.min = amount + 2500;
        bidInput.placeholder = (amount + 2500).toString();
    }
}

function updateNextMinimumBid(amount) {
    const nextBidAmount = document.querySelector('.nextBidAmount');
    if (nextBidAmount) {
        nextBidAmount.textContent = `EGP ${amount.toLocaleString()}`;
    }
}

function addToBiddingHistory(bidder, amount, time) {
    const historyBody = document.getElementById('biddingHistoryBody');
    
    if (historyBody) {
        // Remove current class from previous bid
        const currentRow = historyBody.querySelector('.current');
        if (currentRow) {
            currentRow.classList.remove('current');
        }
        
        // Create new row
        const newRow = document.createElement('div');
        newRow.className = 'historyRow current';
        newRow.innerHTML = `
            <div class="historyCol bidder">${bidder}</div>
            <div class="historyCol amount">EGP ${amount.toLocaleString()}</div>
            <div class="historyCol time">${time}</div>
        `;
        
        // Insert at the beginning
        historyBody.insertBefore(newRow, historyBody.firstChild);
        
        // Limit to 10 rows
        const rows = historyBody.querySelectorAll('.historyRow');
        if (rows.length > 10) {
            historyBody.removeChild(rows[rows.length - 1]);
        }
    }
}

// Simulate live bidding activity
function simulateLiveBidding() {
    const bidders = [
        'art_enthusiast_23', 'collector_pro', 'gallery_owner', 'bidder_1847',
        'art_lover_99', 'investment_collector', 'museum_curator', 'private_collector'
    ];
    
    // Simulate random bids every 30-120 seconds
    setInterval(() => {
        if (Math.random() < 0.3) { // 30% chance every interval
            const randomBidder = bidders[Math.floor(Math.random() * bidders.length)];
            const currentBid = parseInt(document.getElementById('currentBidAmount').textContent.replace(/[^\d]/g, ''));
            const newBid = currentBid + (Math.floor(Math.random() * 3) + 1) * 2500; // Increase by 2500-7500
            
            setTimeout(() => {
                updateCurrentBid(newBid);
                addToBiddingHistory(randomBidder, newBid, 'Just now');
                updateBidderCount();
            }, Math.random() * 5000); // Random delay up to 5 seconds
        }
    }, 45000); // Check every 45 seconds
}

function updateBidderCount() {
    const bidStats = document.querySelectorAll('.bidStat');
    bidStats.forEach(stat => {
        if (stat.textContent.includes('bidders')) {
            const currentCount = parseInt(stat.textContent.match(/\d+/)[0]);
            stat.innerHTML = `<i class="fas fa-users"></i><span>${currentCount + 1} bidders</span>`;
        }
    });
}

// Watchlist functionality
function initializeWatchlist() {
    const watchlistBtn = document.getElementById('watchlistBtn');
    
    if (watchlistBtn) {
        watchlistBtn.addEventListener('click', toggleWatchlist);
    }
}

function toggleWatchlist() {
    const watchlistBtn = document.getElementById('watchlistBtn');
    const icon = watchlistBtn.querySelector('i');
    
    if (icon.classList.contains('far')) {
        // Add to watchlist
        icon.classList.remove('far');
        icon.classList.add('fas');
        watchlistBtn.innerHTML = '<i class="fas fa-heart"></i>Remove from Watchlist';
        watchlistBtn.style.borderColor = '#d4af37';
        watchlistBtn.style.color = '#d4af37';
        
        showNotification('Added to watchlist', 'success');
        updateWishlistCount();
    } else {
        // Remove from watchlist
        icon.classList.remove('fas');
        icon.classList.add('far');
        watchlistBtn.innerHTML = '<i class="far fa-heart"></i>Add to Watchlist';
        watchlistBtn.style.borderColor = '#e8e4df';
        watchlistBtn.style.color = '#6b4423';
        
        showNotification('Removed from watchlist', 'info');
    }
}

// Image gallery functionality
function initializeImageGallery() {
    const thumbnails = document.querySelectorAll('.thumbnail');
    
    thumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', () => changeMainImage(thumbnail));
    });
}

function changeMainImage(thumbnail) {
    const mainImage = document.getElementById('mainImage');
    const newImageSrc = thumbnail.querySelector('img').src.replace('w=100&h=100', 'w=600&h=600');
    
    if (mainImage) {
        // Fade out
        mainImage.style.opacity = '0.5';
        
        setTimeout(() => {
            mainImage.src = newImageSrc;
            mainImage.style.opacity = '1';
        }, 150);
    }
    
    // Update active thumbnail
    document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
    thumbnail.classList.add('active');
}

function openImageFullscreen() {
    const mainImage = document.getElementById('mainImage');
    
    if (mainImage) {
        // Create fullscreen overlay
        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            cursor: pointer;
        `;
        
        const image = document.createElement('img');
        image.src = mainImage.src;
        image.style.cssText = `
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
            border-radius: 8px;
        `;
        
        overlay.appendChild(image);
        document.body.appendChild(overlay);
        
        // Close on click
        overlay.addEventListener('click', () => {
            document.body.removeChild(overlay);
        });
        
        // Close on escape key
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                document.body.removeChild(overlay);
                document.removeEventListener('keydown', handleEscape);
            }
        };
        document.addEventListener('keydown', handleEscape);
    }
}

// Share functionality
function shareAuction() {
    if (navigator.share) {
        navigator.share({
            title: 'Abstract Harmony - Auction at Yadawity Gallery',
            text: 'Check out this amazing artwork currently up for auction!',
            url: window.location.href
        });
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            showNotification('Auction link copied to clipboard', 'success');
        }).catch(() => {
            showNotification('Unable to copy link', 'error');
        });
    }
}

// Utility functions
function updateCartCount() {
    const cartCount = document.getElementById('cartCount');
    const burgerCartCount = document.getElementById('burgerCartCount');
    
    const count = localStorage.getItem('cartCount') || '0';
    
    if (cartCount) cartCount.textContent = count;
    if (burgerCartCount) burgerCartCount.textContent = count;
}

function updateWishlistCount() {
    const wishlistCount = document.getElementById('wishlistCount');
    const burgerWishlistCount = document.getElementById('burgerWishlistCount');
    
    let count = parseInt(localStorage.getItem('wishlistCount') || '0');
    
    // Check if current item is in watchlist
    const watchlistBtn = document.getElementById('watchlistBtn');
    if (watchlistBtn && watchlistBtn.querySelector('.fas.fa-heart')) {
        count = Math.max(count, 1);
    }
    
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
        max-width: 300px;
        font-size: 0.875rem;
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Remove after 4 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (document.body.contains(notification)) {
                document.body.removeChild(notification);
            }
        }, 300);
    }, 4000);
}

// Navbar functionality
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchBtn = document.getElementById('searchButton');
    const searchInput = document.getElementById('navbarSearch');
    
    if (searchBtn && searchInput) {
        searchBtn.addEventListener('click', performSearch);
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    }
    
    // User dropdown
    const userAccount = document.getElementById('userAccount');
    const userMenu = document.getElementById('userMenu');
    
    if (userAccount && userMenu) {
        userAccount.addEventListener('click', function(e) {
            e.preventDefault();
            userMenu.classList.toggle('show');
        });
        
        document.addEventListener('click', function(e) {
            if (!userAccount.contains(e.target) && !userMenu.contains(e.target)) {
                userMenu.classList.remove('show');
            }
        });
    }
});

function performSearch() {
    const searchInput = document.getElementById('navbarSearch');
    const query = searchInput.value.trim();
    
    if (query) {
        showNotification(`Searching for: ${query}`, 'info');
        // In a real application, this would redirect to search results
        setTimeout(() => {
            window.location.href = `auction.html?search=${encodeURIComponent(query)}`;
        }, 1000);
    }
}
