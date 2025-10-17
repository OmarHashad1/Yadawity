// Yadawity Admin Artworks Management - Professional JavaScript
let currentPage = 1;
let currentSearch = '';
let currentTypeFilter = '';
let currentStatusFilter = '';
let currentAuctionFilter = '';
let totalArtworks = 0;

document.addEventListener('DOMContentLoaded', function() {
    // Check authentication
    if (!localStorage.getItem('csrf_token')) {
        window.location.href = 'login.php';
        return;
    }

    // Display user info
    const userInfo = document.getElementById('userInfo');
    const userName = localStorage.getItem('user_name') || 'Admin';
    userInfo.textContent = `Welcome, ${userName}`;
    userInfo.style.opacity = '1';

    // Load artworks
    loadArtworks();

    // Add search input event listener
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchArtworks();
        }
    });

    // Add real-time search with debouncing
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchArtworks();
        }, 500);
    });
});

async function loadArtworks(page = 1, search = '', typeFilter = '', statusFilter = '', auctionFilter = '') {
    try {
        showLoadingState();
        
        let url = `/admin-system/API/artworks.php?page=${page}&limit=20`;
        const params = new URLSearchParams();
        
        if (page) params.append('page', page);
        if (search) params.append('q', search);
        if (typeFilter) params.append('type', typeFilter);
        if (statusFilter) params.append('status', statusFilter);
        if (auctionFilter) params.append('auction', auctionFilter);
        
        if (params.toString()) {
            url += '&' + params.toString();
        }

        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': localStorage.getItem('csrf_token')
            }
        });

        if (response.status === 401 || response.status === 403) {
            localStorage.clear();
            window.location.href = 'login.php';
            return;
        }

        const data = await response.json();
        
        if (response.ok && data.data) {
            displayArtworks(data.data);
            updatePagination(data.meta);
            updateResultsCount(data.meta);
            totalArtworks = data.meta.total;
        } else {
            showError('Failed to load artworks', data.error || 'Unknown error occurred');
        }
    } catch (error) {
        console.error('Error loading artworks:', error);
        showError('Error loading artworks', 'Network error or server unavailable');
    }
}

function showLoadingState() {
    const tbody = document.getElementById('artworksTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="10" class="loading-row">
                <div class="loading-spinner"></div>
                <span>Loading artworks...</span>
            </td>
        </tr>
    `;
}

function displayArtworks(artworks) {
    const tbody = document.getElementById('artworksTableBody');
    
    if (!artworks || artworks.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="text-center" style="padding: 3rem; color: var(--text-light); background: var(--background-light);">
                    <div style="font-size: 1.2rem; margin-bottom: 0.5rem; color: var(--primary-color);">No artworks found</div>
                    <div style="font-size: 0.9rem; color: var(--secondary-color);">Try adjusting your search or filters</div>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    artworks.forEach((artwork, index) => {
        const statusClass = artwork.is_available == 1 ? 'badge-success' : 'badge-danger';
        const statusText = artwork.is_available == 1 ? 'Available' : 'Unavailable';
        const auctionClass = artwork.on_auction == 1 ? 'badge-warning' : 'badge-secondary';
        const auctionText = artwork.on_auction == 1 ? 'Yes' : 'No';
        const typeClass = getTypeBadgeClass(artwork.type);
        
        // Handle artwork image
        const imageCell = artwork.artwork_image ? 
            `<img src="${artwork.artwork_image}" alt="${artwork.title}" class="artwork-image" onerror="this.parentElement.innerHTML='<div class=\\'artwork-image-placeholder\\'>🖼️</div>'">` :
            '<div class="artwork-image-placeholder">🖼️</div>';
        
        html += `
            <tr class="fade-in" style="animation-delay: ${index * 0.05}s;">
                <td><strong>#${artwork.artwork_id}</strong></td>
                <td>${imageCell}</td>
                <td>
                    <div style="font-weight: 600;">${artwork.title}</div>
                    ${artwork.description ? `<div style="font-size: 0.85rem; color: var(--text-light);">${artwork.description.substring(0, 50)}${artwork.description.length > 50 ? '...' : ''}</div>` : ''}
                </td>
                <td><strong>#${artwork.artist_id}</strong></td>
                <td><span class="type-badge ${artwork.type}">${artwork.type}</span></td>
                <td class="artwork-price">${(artwork.price || 0).toLocaleString()}</td>
                <td><span class="badge ${statusClass}">${statusText}</span></td>
                <td><span class="badge ${auctionClass}">${auctionText}</span></td>
                <td>${formatDate(artwork.created_at)}</td>
                <td class="action-buttons">
                    <button class="btn btn-sm btn-outline-info" onclick="viewArtwork(${artwork.artwork_id})" title="View Artwork">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                    <button class="btn btn-sm btn-outline-primary" onclick="editArtwork(${artwork.artwork_id})" title="Edit Artwork">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteArtwork(${artwork.artwork_id})" title="Delete Artwork">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3,6 5,6 21,6"></polyline>
                            <path d="m19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"></path>
                        </svg>
                    </button>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

function getTypeBadgeClass(artworkType) {
    switch(artworkType) {
        case 'painting': return 'painting';
        case 'sculpture': return 'sculpture';
        case 'photography': return 'photography';
        case 'digital': return 'digital';
        case 'mixed_media': return 'mixed_media';
        default: return 'other';
    }
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function updatePagination(meta) {
    const pagination = document.getElementById('pagination');
    
    if (!meta || meta.total <= meta.limit) {
        pagination.innerHTML = '';
        return;
    }

    const totalPages = Math.ceil(meta.total / meta.limit);
    let html = '<ul class="pagination">';

    // Previous button
    html += `
        <li class="page-item ${meta.page <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changePage(${meta.page - 1})" ${meta.page <= 1 ? 'tabindex="-1"' : ''}>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
            </a>
        </li>
    `;

    // Page numbers
    const startPage = Math.max(1, meta.page - 2);
    const endPage = Math.min(totalPages, meta.page + 2);

    if (startPage > 1) {
        html += '<li class="page-item"><a class="page-link" href="#" onclick="changePage(1)">1</a></li>';
        if (startPage > 2) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        if (i === meta.page) {
            html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
        } else {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${i})">${i}</a></li>`;
        }
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        html += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${totalPages})">${totalPages}</a></li>`;
    }

    // Next button
    html += `
        <li class="page-item ${meta.page >= totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changePage(${meta.page + 1})" ${meta.page >= totalPages ? 'tabindex="-1"' : ''}>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9,18 15,12 9,6"></polyline>
                </svg>
            </a>
        </li>
    `;

    html += '</ul>';
    pagination.innerHTML = html;
}

function updateResultsCount(meta) {
    const resultsCount = document.getElementById('resultsCount');
    if (meta) {
        const start = (meta.page - 1) * meta.limit + 1;
        const end = Math.min(meta.page * meta.limit, meta.total);
        resultsCount.textContent = `Showing ${start}-${end} of ${meta.total} artworks`;
    } else {
        resultsCount.textContent = 'No results';
    }
}

function changePage(page) {
    if (page < 1) return;
    currentPage = page;
    loadArtworks(currentPage, currentSearch, currentTypeFilter, currentStatusFilter, currentAuctionFilter);
}

function searchArtworks() {
    currentSearch = document.getElementById('searchInput').value.trim();
    currentPage = 1;
    loadArtworks(currentPage, currentSearch, currentTypeFilter, currentStatusFilter, currentAuctionFilter);
}

function filterArtworks() {
    currentTypeFilter = document.getElementById('typeFilter').value;
    currentStatusFilter = document.getElementById('statusFilter').value;
    currentAuctionFilter = document.getElementById('auctionFilter').value;
    currentPage = 1;
    loadArtworks(currentPage, currentSearch, currentTypeFilter, currentStatusFilter, currentAuctionFilter);
}

function openAddArtworkModal() {
    document.getElementById('artworkModalTitle').textContent = 'Add New Artwork';
    document.getElementById('artworkForm').reset();
    document.getElementById('artworkId').value = '';
    showModal();
}

function closeArtworkModal() {
    const modal = document.getElementById('artworkModal');
    modal.classList.remove('show');
}

function showModal() {
    const modal = document.getElementById('artworkModal');
    modal.classList.add('show');
}

async function viewArtwork(artworkId) {
    try {
        const response = await fetch(`/admin-system/API/artworks.php?id=${artworkId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': localStorage.getItem('csrf_token')
            }
        });

        if (response.status === 401 || response.status === 403) {
            localStorage.clear();
            window.location.href = 'login.php';
            return;
        }

        if (response.ok) {
            const data = await response.json();
            const artwork = data.data;
            
            // Show artwork details in a modal or redirect to detail page
            Swal.fire({
                title: artwork.title,
                html: `
                    <div style="text-align: left;">
                        <p><strong>Artist ID:</strong> ${artwork.artist_id}</p>
                        <p><strong>Type:</strong> ${artwork.type}</p>
                        <p><strong>Price:</strong> $${(artwork.price || 0).toLocaleString()}</p>
                        <p><strong>Status:</strong> ${artwork.is_available == 1 ? 'Available' : 'Unavailable'}</p>
                        <p><strong>On Auction:</strong> ${artwork.on_auction == 1 ? 'Yes' : 'No'}</p>
                        ${artwork.description ? `<p><strong>Description:</strong> ${artwork.description}</p>` : ''}
                        ${artwork.dimensions ? `<p><strong>Dimensions:</strong> ${artwork.dimensions}</p>` : ''}
                        ${artwork.year ? `<p><strong>Year:</strong> ${artwork.year}</p>` : ''}
                        ${artwork.material ? `<p><strong>Material:</strong> ${artwork.material}</p>` : ''}
                    </div>
                `,
                imageUrl: artwork.artwork_image || null,
                imageWidth: 300,
                imageHeight: 200,
                imageAlt: artwork.title,
                confirmButtonText: 'Close',
                confirmButtonColor: '#2c3e50'
            });
        } else {
            const errorData = await response.json();
            showError('Error loading artwork details', errorData.error || 'Failed to load artwork');
        }
    } catch (error) {
        console.error('Error loading artwork details:', error);
        showError('Error loading artwork details', 'Network error or server unavailable');
    }
}

async function editArtwork(artworkId) {
    try {
        showLoadingState();
        
        const response = await fetch(`/admin-system/API/artworks.php?id=${artworkId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': localStorage.getItem('csrf_token')
            }
        });

        if (response.status === 401 || response.status === 403) {
            localStorage.clear();
            window.location.href = 'login.php';
            return;
        }

        if (response.ok) {
            const data = await response.json();
            const artwork = data.data;
            
            document.getElementById('artworkModalTitle').textContent = 'Edit Artwork';
            document.getElementById('artworkId').value = artwork.artwork_id;
            document.getElementById('title').value = artwork.title;
            document.getElementById('artistId').value = artwork.artist_id;
            document.getElementById('type').value = artwork.type;
            document.getElementById('price').value = artwork.price;
            document.getElementById('description').value = artwork.description || '';
            document.getElementById('dimensions').value = artwork.dimensions || '';
            document.getElementById('year').value = artwork.year || '';
            document.getElementById('material').value = artwork.material || '';
            document.getElementById('artworkImage').value = artwork.artwork_image || '';
            document.getElementById('isAvailable').checked = artwork.is_available == 1;
            document.getElementById('onAuction').checked = artwork.on_auction == 1;
            
            showModal();
        } else {
            const errorData = await response.json();
            showError('Error loading artwork details', errorData.error || 'Failed to load artwork');
        }
    } catch (error) {
        console.error('Error loading artwork details:', error);
        showError('Error loading artwork details', 'Network error or server unavailable');
    }
}

async function saveArtwork() {
    const artworkId = document.getElementById('artworkId').value;
    const isEdit = artworkId !== '';
    
    // Form validation
    const title = document.getElementById('title').value.trim();
    const artistId = document.getElementById('artistId').value;
    const type = document.getElementById('type').value;
    const price = document.getElementById('price').value;
    
    if (!title || !artistId || !type || !price) {
        showError('Please fill in all required fields.');
        return;
    }
    
    if (isNaN(price) || parseFloat(price) < 0) {
        showError('Please enter a valid price.');
        return;
    }

    const artworkData = {
        title: title,
        artist_id: parseInt(artistId),
        type: type,
        price: parseFloat(price),
        description: document.getElementById('description').value.trim(),
        dimensions: document.getElementById('dimensions').value.trim(),
        year: document.getElementById('year').value.trim(),
        material: document.getElementById('material').value.trim(),
        artwork_image: document.getElementById('artworkImage').value.trim(),
        is_available: document.getElementById('isAvailable').checked ? 1 : 0,
        on_auction: document.getElementById('onAuction').checked ? 1 : 0
    };

    try {
        const url = isEdit ? `/admin-system/API/artworks.php?id=${artworkId}` : '/admin-system/API/artworks.php';
        const method = isEdit ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': localStorage.getItem('csrf_token')
            },
            body: JSON.stringify(artworkData)
        });

        if (response.status === 401 || response.status === 403) {
            localStorage.clear();
            window.location.href = 'login.php';
            return;
        }

        const data = await response.json();
        
        if (response.ok) {
            closeArtworkModal();
            showSuccess(isEdit ? 'Artwork updated successfully!' : 'Artwork created successfully!');
            loadArtworks(currentPage, currentSearch, currentTypeFilter, currentStatusFilter, currentAuctionFilter);
        } else {
            showError('Error: ' + (data.error || 'Failed to save artwork'));
        }
    } catch (error) {
        console.error('Error saving artwork:', error);
        showError('Error saving artwork. Please try again.', 'Network error or server unavailable');
    }
}

async function deleteArtwork(artworkId) {
    const result = await Swal.fire({
        title: 'Delete Artwork',
        text: 'Are you sure you want to delete this artwork? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#2c3e50',
        reverseButtons: true
    });

    if (!result.isConfirmed) return;

    try {
        const response = await fetch(`/admin-system/API/artworks.php?id=${artworkId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': localStorage.getItem('csrf_token')
            }
        });

        if (response.status === 401 || response.status === 403) {
            localStorage.clear();
            window.location.href = 'login.php';
            return;
        }

        const data = await response.json();
        
        if (response.ok) {
            showSuccess('Artwork deleted successfully!');
            loadArtworks(currentPage, currentSearch, currentTypeFilter, currentStatusFilter, currentAuctionFilter);
        } else {
            showError('Error: ' + (data.error || 'Failed to delete artwork'));
        }
    } catch (error) {
        console.error('Error deleting artwork:', error);
        showError('Error deleting artwork. Please try again.', 'Network error or server unavailable');
    }
}

function logout() {
    Swal.fire({
        title: 'Logout',
        text: 'Are you sure you want to logout?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Logout',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#e67e22',
        cancelButtonColor: '#2c3e50'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('/admin-system/API/logout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': localStorage.getItem('csrf_token')
                }
            }).finally(() => {
                localStorage.clear();
                window.location.href = 'login.php';
            });
        }
    });
}

function showError(message, error = null) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: message,
        footer: error ? `<pre style="font-size: 0.8rem; text-align: left;">${error}</pre>` : '',
        confirmButtonColor: '#e74c3c'
    });
}

function showSuccess(message) {
    Swal.fire({
        icon: 'success',
        title: 'Success',
        text: message,
        confirmButtonColor: '#27ae60',
        timer: 2000,
        timerProgressBar: true
    });
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('artworkModal');
    if (e.target === modal) {
        closeArtworkModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeArtworkModal();
    }
});
