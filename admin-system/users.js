// Yadawity Admin Users Management - Professional JavaScript
let currentPage = 1;
let currentSearch = '';
let currentStatusFilter = '';
let currentTypeFilter = '';
let totalUsers = 0;

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

    // Load users
    loadUsers();

    // Add search input event listener
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchUsers();
        }
    });

    // Add real-time search with debouncing
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchUsers();
        }, 500);
    });
    
    // Initialize filter visuals
    updateFilterVisuals();
});

async function loadUsers(page = 1, search = '', statusFilter = '', typeFilter = '') {
    try {
        showLoadingState();
        
        const params = new URLSearchParams();
        params.append('page', page);
        params.append('limit', '20');
        
        if (search) params.append('q', search);
        if (statusFilter) params.append('status', statusFilter);
        if (typeFilter) params.append('type', typeFilter);
        
        const url = `/admin-system/API/user.php?${params.toString()}`;
        
        // Debug logging
        console.log('Loading users with URL:', url);

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
            displayUsers(data.data);
            updatePagination(data.meta);
            updateResultsCount(data.meta);
            totalUsers = data.meta.total;
        } else {
            showError('Failed to load users', data.error || 'Unknown error occurred');
        }
    } catch (error) {
        console.error('Error loading users:', error);
        showError('Error loading users', 'Network error or server unavailable');
    }
}

function showLoadingState() {
    const tbody = document.getElementById('usersTableBody');
    let loadingMessage = 'Loading users...';
    
    // Show more specific loading message based on active filters
    if (currentSearch || currentStatusFilter || currentTypeFilter) {
        const filters = [];
        if (currentSearch) filters.push(`search: "${currentSearch}"`);
        if (currentStatusFilter) filters.push(`status: ${currentStatusFilter === '1' ? 'Active' : 'Inactive'}`);
        if (currentTypeFilter) filters.push(`type: ${currentTypeFilter}`);
        
        loadingMessage = `Loading users with filters: ${filters.join(', ')}...`;
    }
    
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="loading-row">
                <div class="loading-spinner"></div>
                <span>${loadingMessage}</span>
            </td>
        </tr>
    `;
}

function displayUsers(users) {
    const tbody = document.getElementById('usersTableBody');
    
    if (!users || users.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center" style="padding: 3rem; color: var(--text-light);">
                    <div style="font-size: 1.2rem; margin-bottom: 0.5rem;">No users found</div>
                    <div style="font-size: 0.9rem;">Try adjusting your search or filters</div>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    users.forEach((user, index) => {
        const statusClass = user.is_active == 1 ? 'badge-success' : 'badge-danger';
        const statusText = user.is_active == 1 ? 'Active' : 'Inactive';
        const typeClass = getTypeBadgeClass(user.user_type);
        
        html += `
            <tr class="fade-in" style="animation-delay: ${index * 0.05}s;">
                <td><strong>#${user.user_id}</strong></td>
                <td>
                    <div style="font-weight: 600;">${user.first_name} ${user.last_name}</div>
                    ${user.phone ? `<div style="font-size: 0.85rem; color: var(--text-light);">${user.phone}</div>` : ''}
                </td>
                <td>${user.email}</td>
                <td><span class="badge ${typeClass}">${user.user_type}</span></td>
                <td><span class="badge ${statusClass}">${statusText}</span></td>
                <td>${formatDate(user.created_at)}</td>
                <td class="action-buttons">
                    <button class="btn btn-sm btn-outline-primary" onclick="editUser(${user.user_id})" title="Edit User">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(${user.user_id})" title="Delete User">
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

function getTypeBadgeClass(userType) {
    switch(userType) {
        case 'admin': return 'badge-danger';
        case 'artist': return 'badge-warning';
        case 'buyer': return 'badge-info';
        default: return 'badge-secondary';
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
        resultsCount.textContent = `Showing ${start}-${end} of ${meta.total} users`;
    } else {
        resultsCount.textContent = 'No results';
    }
}

function changePage(page) {
    if (page < 1) return;
    currentPage = page;
    loadUsers(currentPage, currentSearch, currentStatusFilter, currentTypeFilter);
}

function searchUsers() {
    currentSearch = document.getElementById('searchInput').value.trim();
    currentPage = 1;
    
    // Update visual feedback
    updateFilterVisuals();
    
    loadUsers(currentPage, currentSearch, currentStatusFilter, currentTypeFilter);
}

function filterUsers() {
    currentStatusFilter = document.getElementById('statusFilter').value;
    currentTypeFilter = document.getElementById('typeFilter').value;
    currentPage = 1;
    
    // Update visual feedback for active filters
    updateFilterVisuals();
    
    loadUsers(currentPage, currentSearch, currentStatusFilter, currentTypeFilter);
}

function updateFilterVisuals() {
    const statusFilter = document.getElementById('statusFilter');
    const typeFilter = document.getElementById('typeFilter');
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    
    // Remove active class from both filters
    statusFilter.classList.remove('active');
    typeFilter.classList.remove('active');
    
    // Add active class to filters that have values
    if (currentStatusFilter) {
        statusFilter.classList.add('active');
    }
    if (currentTypeFilter) {
        typeFilter.classList.add('active');
    }
    
    // Show/hide clear filters button
    if (currentStatusFilter || currentTypeFilter || currentSearch) {
        clearFiltersBtn.style.display = 'inline-block';
    } else {
        clearFiltersBtn.style.display = 'none';
    }
}

function clearAllFilters() {
    // Clear search
    document.getElementById('searchInput').value = '';
    currentSearch = '';
    
    // Clear filters
    document.getElementById('statusFilter').value = '';
    document.getElementById('typeFilter').value = '';
    currentStatusFilter = '';
    currentTypeFilter = '';
    
    // Reset to first page
    currentPage = 1;
    
    // Update visuals
    updateFilterVisuals();
    
    // Reload users
    loadUsers(currentPage, currentSearch, currentStatusFilter, currentTypeFilter);
}

function openAddUserModal() {
    document.getElementById('userModalTitle').textContent = 'Add New User';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('password').required = true;
    document.getElementById('password').placeholder = 'Enter password';
    showModal();
}

function closeUserModal() {
    const modal = document.getElementById('userModal');
    modal.classList.remove('show');
}

function showModal() {
    const modal = document.getElementById('userModal');
    modal.classList.add('show');
}

async function editUser(userId) {
    try {
        showLoadingState();
        
        const response = await fetch(`/admin-system/API/user.php?id=${userId}`, {
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
            const user = data.data;
            
            document.getElementById('userModalTitle').textContent = 'Edit User';
            document.getElementById('userId').value = user.user_id;
            document.getElementById('firstName').value = user.first_name;
            document.getElementById('lastName').value = user.last_name;
            document.getElementById('email').value = user.email;
            document.getElementById('userType').value = user.user_type;
            document.getElementById('phone').value = user.phone || '';
            document.getElementById('isActive').checked = user.is_active == 1;
            document.getElementById('password').required = false;
            document.getElementById('password').placeholder = 'Leave blank to keep existing password';
            
            showModal();
        } else {
            const errorData = await response.json();
            showError('Error loading user details', errorData.error || 'Failed to load user');
        }
    } catch (error) {
        console.error('Error loading user details:', error);
        showError('Error loading user details', 'Network error or server unavailable');
    }
}

async function saveUser() {
    const userId = document.getElementById('userId').value;
    const isEdit = userId !== '';
    
    // Form validation
    const firstName = document.getElementById('firstName').value.trim();
    const lastName = document.getElementById('lastName').value.trim();
    const email = document.getElementById('email').value.trim();
    const userType = document.getElementById('userType').value;
    const password = document.getElementById('password').value;
    
    if (!firstName || !lastName || !email || !userType) {
        showError('Please fill in all required fields.');
        return;
    }
    
    if (!validateEmail(email)) {
        showError('Please enter a valid email address.');
        return;
    }
    
    if (!isEdit && !password) {
        showError('Password is required for new users.');
        return;
    }

    const userData = {
        first_name: firstName,
        last_name: lastName,
        email: email,
        user_type: userType,
        phone: document.getElementById('phone').value.trim(),
        is_active: document.getElementById('isActive').checked ? 1 : 0
    };

    // Add password if provided or if creating new user
    if (password || !isEdit) {
        userData.password = password;
    }

    try {
        const url = isEdit ? `/admin-system/API/user.php?id=${userId}` : '/admin-system/API/user.php';
        const method = isEdit ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': localStorage.getItem('csrf_token')
            },
            body: JSON.stringify(userData)
        });

        if (response.status === 401 || response.status === 403) {
            localStorage.clear();
            window.location.href = 'login.php';
            return;
        }

        const data = await response.json();
        
        if (response.ok) {
            closeUserModal();
            showSuccess(isEdit ? 'User updated successfully!' : 'User created successfully!');
            loadUsers(currentPage, currentSearch, currentStatusFilter, currentTypeFilter);
        } else {
            showError('Error: ' + (data.error || 'Failed to save user'));
        }
    } catch (error) {
        console.error('Error saving user:', error);
        showError('Error saving user. Please try again.', 'Network error or server unavailable');
    }
}

async function deleteUser(userId) {
    const result = await Swal.fire({
        title: 'Delete User',
        text: 'Are you sure you want to delete this user? This action cannot be undone.',
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
        const response = await fetch(`/admin-system/API/user.php?id=${userId}`, {
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
            showSuccess('User deleted successfully!');
            loadUsers(currentPage, currentSearch, currentStatusFilter, currentTypeFilter);
        } else {
            showError('Error: ' + (data.error || 'Failed to delete user'));
        }
    } catch (error) {
        console.error('Error deleting user:', error);
        showError('Error deleting user. Please try again.', 'Network error or server unavailable');
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

function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('userModal');
    if (e.target === modal) {
        closeUserModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeUserModal();
    }
});