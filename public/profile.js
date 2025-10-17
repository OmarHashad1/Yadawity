document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing profile functionality...');
    
    var navToggle = document.getElementById('nav-toggle');
    if (navToggle) {
        navToggle.addEventListener('click', function() {
            window.openBurgerMenu();
        });
    }
    
    // Only load profile stats, not form fields
    loadProfileStats();
    
    // Load current user data into form fields
    loadCurrentUserData();
    
    // Initialize form submission handler
    initializeFormHandler();
    
    // Initialize all other functionality
    initializeProfileNavigation();
    initializeDeleteAccountModal();
    initializeEmailChangeModal();
    initializePasswordForm();
    initializeProfilePhotoUpload();
    
    console.log('All profile functionality initialized');
});

function loadProfileStats() {
    // Fetch user information from getBuyerInfo API for stats only
    fetch('./API/getBuyerInfo.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const buyerInfo = data.data;
                
                // Only update profile stats, not form fields
                document.getElementById('memberSince').textContent = buyerInfo.member_since ? new Date(buyerInfo.member_since).getFullYear() : '2023';
                document.getElementById('purchaseCount').textContent = buyerInfo.orders_count || '0';
                document.getElementById('wishlistCount').textContent = buyerInfo.wishlist_count || '0';
                document.getElementById('reviewCount').textContent = buyerInfo.reviews_count || '0';
                
                // Debug: Log the actual values being set
                console.log('Setting stats - Orders:', buyerInfo.orders_count, 'Wishlist:', buyerInfo.wishlist_count, 'Reviews:', buyerInfo.reviews_count);
                
                console.log('Profile stats loaded successfully from API:', buyerInfo);
                
            } else {
                console.error('Failed to load buyer info:', data.message);
                // On error, show default values
                document.getElementById('memberSince').textContent = '2023';
                document.getElementById('purchaseCount').textContent = '0';
                document.getElementById('wishlistCount').textContent = '0';
                document.getElementById('reviewCount').textContent = '0';
            }
        })
        .catch(error => {
            console.error('Error loading buyer info:', error);
            // On error, show default values
            document.getElementById('memberSince').textContent = '2023';
            document.getElementById('purchaseCount').textContent = '0';
            document.getElementById('wishlistCount').textContent = '0';
            document.getElementById('reviewCount').textContent = '0';
        });
}

function loadCurrentUserData() {
    // Load profile photo
    loadProfilePhoto();
    
    // Fetch user information from getBuyerInfo API to populate form fields
    fetch('./API/getBuyerInfo.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const buyerInfo = data.data;
                
                // Populate form fields with current user data
                const firstNameField = document.getElementById('firstName');
                const lastNameField = document.getElementById('lastName');
                const emailField = document.getElementById('email');
                const phoneField = document.getElementById('phone');
                const addressField = document.getElementById('address');
                
                if (firstNameField) firstNameField.value = buyerInfo.first_name || '';
                if (lastNameField) lastNameField.value = buyerInfo.last_name || '';
                if (emailField) emailField.value = buyerInfo.email || '';
                if (phoneField) phoneField.value = buyerInfo.phone || '';
                if (addressField) addressField.value = buyerInfo.address || '';
                
                console.log('User data loaded into form fields:', buyerInfo);
                
            } else {
                console.error('Failed to load user data for form:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading user data for form:', error);
        });
}

function initializeFormHandler() {
    const personalForm = document.getElementById('personalForm');
    if (personalForm) {
        personalForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = {
                first_name: document.getElementById('firstName').value,
                last_name: document.getElementById('lastName').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value,
                location: document.getElementById('address').value
            };
            
            // Show loading state
            const saveButton = document.getElementById('saveChanges');
            const originalText = saveButton.innerHTML;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            saveButton.disabled = true;
            
            // Submit to API
            fetch('./API/updateBuyerProfile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showNotification('Profile updated successfully!', 'success');
                    console.log('Profile updated:', data);
                    
                    // Keep the form values - don't clear them
                    // The values are already in the form fields, so no need to do anything
                } else {
                    // Show error message
                    showNotification('Error: ' + data.message, 'error');
                    console.error('Profile update failed:', data.message);
                }
            })
            .catch(error => {
                // Show error message
                showNotification('An error occurred while updating your profile.', 'error');
                console.error('Error updating profile:', error);
            })
            .finally(() => {
                // Restore button state
                saveButton.innerHTML = originalText;
                saveButton.disabled = false;
            });
        });
    }
}

function initializeProfileNavigation() {
    // Sidebar navigation
    const navItems = document.querySelectorAll('.profile-nav li');
    const sections = document.querySelectorAll('.profile-section');
    
    navItems.forEach(item => {
        item.addEventListener('click', () => {
            navItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            sections.forEach(sec => sec.classList.remove('active'));
            document.getElementById(item.dataset.section).classList.add('active');
            
            // If reviews tab is clicked, load reviews
            if(item.dataset.section === 'reviews'){
                loadReviews();
            }
            
            // If orders tab is clicked, load orders
            if(item.dataset.section === 'orders'){
                loadOrders();
            }
        });
    });
}

function initializeDeleteAccountModal() {
    // Delete Account Modal logic
    const deleteBtn = document.getElementById('deleteAccountBtn');
    const deleteModal = document.getElementById('deleteAccountModal');
    const closeDeleteModal = document.getElementById('closeDeleteAccountModal');
    const deleteForm = document.getElementById('deleteAccountForm');
    
    if (deleteBtn && deleteModal && closeDeleteModal && deleteForm) {
        deleteBtn.addEventListener('click', function() {
            deleteModal.style.display = 'flex';
        });
        
        closeDeleteModal.addEventListener('click', function() {
            deleteModal.style.display = 'none';
        });
        
        deleteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!confirm('Are you absolutely sure you want to delete your account? This cannot be undone.')) return;
            
            fetch('API/deleteAccount.php', {
                method: 'POST',
                credentials: 'same-origin'
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Your account has been deleted.');
                    window.location.href = 'index.php';
                } else {
                    alert('Failed to delete account: ' + (data.message || ''));
                }
            })
            .catch(() => {
                alert('An error occurred while deleting your account.');
            });
        });
    }
}

// Fetch reviews
function loadReviews(){
    // Use the global USER_ID available from the profile page
    const userId = window.USER_ID;
    
    if (!userId) {
        console.error('User ID not available');
        document.getElementById('reviewList').innerHTML = '<div class="error-message"><p>User session not found. Please refresh the page.</p></div>';
        return;
    }
    
    // Create a form to send POST data with user ID
    const formData = new FormData();
    formData.append('user_id', userId);
    
    fetch('API/getUserReviews.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            console.log('Reviews data:', data); // Debug log
            const reviewList = document.getElementById('reviewList');
            if(data.success && data.reviews && data.reviews.length > 0){
                reviewList.innerHTML = data.reviews.map(review => `
                    <div class='review-card' data-review-id='${review.review_id}'>
                        <div class='review-header'>
                            <div class='review-info'>
                                <h4>Review by ${review.user_name}</h4>
                                <div class='review-rating'>
                                    <span class='stars'>${'★'.repeat(review.rating)}${'☆'.repeat(5 - review.rating)}</span>
                                    <span class='rating-text'>(${review.rating}/5)</span>
                                </div>
                            </div>
                            <div class='review-actions'>
                                <div class='review-date'>${new Date(review.created_at).toLocaleDateString()}</div>
                                <button class='delete-review-btn' onclick='deleteReview(${review.review_id})' title='Delete Review'>
                                    <i class='fas fa-trash'></i>
                                </button>
                            </div>
                        </div>
                        <div class='review-content'>
                            <div class='review-item-info'>
                                <strong>Item:</strong> ${review.title || 'N/A'}
                                ${review.artist_name ? `<br><strong>Artist:</strong> ${review.artist_name}` : ''}
                                ${review.type ? `<br><strong>Type:</strong> ${review.type}` : ''}
                            </div>
                            <div class='review-comment'>
                                <p>${review.comment}</p>
                            </div>
                        </div>
                    </div>
                `).join('');
            } else {
                reviewList.innerHTML = '<div class="no-reviews"><p>No reviews found.</p></div>';
            }
        })
        .catch(error => {
            console.error('Error loading reviews:', error);
            document.getElementById('reviewList').innerHTML = '<div class="error-message"><p>Error loading reviews. Please try again.</p></div>';
        });
}

// Fetch orders
function loadOrders(){
    fetch('API/getUserOrder.php')
        .then(res => res.json())
        .then(data => {
            const orderList = document.getElementById('orderList');
            if(data && data.length > 0){
                orderList.innerHTML = data.map(order => `
                    <div class='order-card'>
                        <div class='order-header'>
                            <div class='order-title-section'>
                                <h3>Order #${order.order_id}</h3>
                                <span class='order-type order-type-${order.type}'>${order.type.charAt(0).toUpperCase() + order.type.slice(1)}</span>
                            </div>
                            <div class='order-status status-${order.status.toLowerCase()}'>${order.status}</div>
                        </div>
                        <div class='order-info'>
                            <p><strong>Date:</strong> ${new Date(order.created_at).toLocaleDateString()}</p>
                            <p><strong>Total:</strong> $${parseFloat(order.total_amount).toFixed(2)}</p>
                        </div>
                        ${order.type === 'gallery' ? `
                            <div class='product-info gallery-info'>
                                <h4><i class="fas fa-images"></i> Gallery Access Details</h4>
                                <div class='product-details'>
                                    ${order.main_gallery_name ? `<p><strong>Gallery Name:</strong> ${order.main_gallery_name}</p>` : '<p><strong>Gallery Name:</strong> Premium Gallery Access</p>'}
                                    <p><strong>Gallery Type:</strong> <span class='gallery-type ${order.main_gallery_type?.toLowerCase() || 'virtual'}'>${order.main_gallery_type || 'VIRTUAL'}</span></p>
                                    ${order.main_gallery_artist ? `<p><strong>Featured Artist:</strong> ${order.main_gallery_artist}</p>` : '<p><strong>Featured Artist:</strong> Multiple Artists</p>'}
                                    ${order.main_gallery_description ? `<p><strong>Description:</strong> ${order.main_gallery_description}</p>` : '<p><strong>Description:</strong> Exclusive access to curated artworks and exhibitions</p>'}
                                    ${order.main_gallery_location ? `<p><strong>Location:</strong> ${order.main_gallery_location}</p>` : '<p><strong>Access Type:</strong> Digital/Virtual Gallery</p>'}
                                    <p><strong>Access Price:</strong> $${parseFloat(order.total_amount).toFixed(2)}</p>
                                    <p><strong>Access Status:</strong> <span style="color: #8b7355; font-weight: 600; background: rgba(139, 115, 85, 0.1); padding: 0.25rem 0.5rem; border-radius: 3px;">Active</span></p>
                                </div>
                                ${order.main_gallery_image ? `<img src='uploads/galleries/${order.main_gallery_image}' alt='${order.main_gallery_name || 'Gallery'}' class='main-product-image' onerror="this.src='image/gallery-placeholder.jpg'">` : `<div class="main-product-image" style="background: linear-gradient(135deg, #f5f3f0 0%, #e0d6c3 100%); display: flex; align-items: center; justify-content: center; color: #8b7355; font-weight: bold; text-align: center; padding: 1rem; flex-direction: column; font-size: 0.8rem; line-height: 1.2;">🎨<br>Gallery<br>Access</div>`}
                            </div>
                        ` : ''}
                        <div class='order-items'>
                            ${order.items && order.items.length > 0 ? `
                                <h4>Items (${order.items.length}):</h4>
                                ${order.items.map(item => `
                                    <div class='order-item'>
                                        <img src='${item.artwork_image ? (item.artwork_image.startsWith('uploads/') ? item.artwork_image : 'uploads/artworks/' + item.artwork_image) : 'image/placeholder-artwork.jpg'}' alt='${item.artwork_title}' class='order-item-image'>
                                        <div class='order-item-details'>
                                            <h5>${item.artwork_title}</h5>
                                            <p>Price: $${parseFloat(item.price).toFixed(2)}</p>
                                            <p>Quantity: ${item.quantity}</p>
                                        </div>
                                    </div>
                                `).join('')}
                            ` : `
                                <div class='order-summary'>
                                    ${order.type === 'gallery' ? `
                                        <p><i class="fas fa-check-circle"></i> Gallery Access Successfully Purchased</p>
                                        <p><strong>Total Paid:</strong> $${parseFloat(order.total_amount).toFixed(2)}</p>
                                        <p><small>Your gallery access is now active. Enjoy exploring the curated collection!</small></p>
                                    ` : `
                                        <p><i class="fas fa-receipt"></i> Order completed - Item details not available</p>
                                        <p><small>This order was processed successfully with a total of $${parseFloat(order.total_amount).toFixed(2)}</small></p>
                                    `}
                                </div>
                            `}
                        </div>
                    </div>
                `).join('');
            } else {
                orderList.innerHTML = '<div class="no-orders"><i class="fas fa-shopping-bag"></i><p>No orders found.</p></div>';
            }
        })
        .catch(error => {
            console.error('Error loading orders:', error);
            const orderList = document.getElementById('orderList');
            orderList.innerHTML = '<div class="error-message"><i class="fas fa-exclamation-triangle"></i><p>Failed to load orders. Please try again later.</p></div>';
        });
}

// SweetAlert notification function
function showNotification(message, type) {
    if (type === 'success') {
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: message,
            confirmButtonColor: '#4caf50',
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false
        });
    } else {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: message,
            confirmButtonColor: '#d32f2f',
            confirmButtonText: 'OK'
        });
    }
}

// Email Change Modal Functions
let currentVerificationEmail = '';
let resendTimer = null;

function initializeEmailChangeModal() {
    const changeEmailBtn = document.getElementById('changeEmailBtn');
    const closeModal = document.getElementById('closeChangeEmailModal');
    const cancelBtn = document.getElementById('cancelEmailChange');
    const sendCodeBtn = document.getElementById('sendCodeBtn');
    const backBtn = document.getElementById('backToEmailStep');
    const resendBtn = document.getElementById('resendCodeBtn');
    const verifyBtn = document.getElementById('verifyCodeBtn');

    if (changeEmailBtn) {
        changeEmailBtn.addEventListener('click', openChangeEmailModal);
    }

    if (closeModal) {
        closeModal.addEventListener('click', closeChangeEmailModal);
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeChangeEmailModal);
    }

    if (sendCodeBtn) {
        sendCodeBtn.addEventListener('click', sendVerificationCode);
    }

    if (backBtn) {
        backBtn.addEventListener('click', goBackToEmailStep);
    }

    if (resendBtn) {
        resendBtn.addEventListener('click', resendVerificationCode);
    }

    if (verifyBtn) {
        verifyBtn.addEventListener('click', verifyCodeAndUpdateEmail);
    }

    // Close modal when clicking outside
    const modal = document.getElementById('changeEmailModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeChangeEmailModal();
            }
        });
    }
}

function openChangeEmailModal() {
    const modal = document.getElementById('changeEmailModal');
    const currentEmail = document.getElementById('email').value;
    const newEmailField = document.getElementById('newEmail');
    
    if (modal && newEmailField) {
        newEmailField.value = currentEmail;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Reset modal to step 1
        showEmailStep(1);
        
        // Clear any previous validation state
        clearEmailValidationStates();
        
        // Focus on the email field
        setTimeout(() => {
            newEmailField.focus();
        }, 100);
    }
}

function closeChangeEmailModal() {
    const modal = document.getElementById('changeEmailModal');
    
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        
        // Reset form
        resetEmailModalForm();
        
        // Clear timer if running
        if (resendTimer) {
            clearInterval(resendTimer);
            resendTimer = null;
        }
    }
}

function showEmailStep(step) {
    const step1 = document.getElementById('emailStep1');
    const step2 = document.getElementById('emailStep2');
    
    if (step === 1) {
        step1.classList.add('active');
        step2.classList.remove('active');
    } else {
        step1.classList.remove('active');
        step2.classList.add('active');
    }
}

function clearEmailValidationStates() {
    const errorElements = ['newEmailError', 'verificationCodeError'];
    errorElements.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.classList.remove('show');
            element.textContent = '';
        }
    });
}

function resetEmailModalForm() {
    const newEmailField = document.getElementById('newEmail');
    const verificationCodeField = document.getElementById('verificationCode');
    
    if (newEmailField) newEmailField.value = '';
    if (verificationCodeField) verificationCodeField.value = '';
    
    clearEmailValidationStates();
    showEmailStep(1);
    currentVerificationEmail = '';
}

function validateNewEmail() {
    const newEmailField = document.getElementById('newEmail');
    const errorElement = document.getElementById('newEmailError');
    const newEmail = newEmailField.value.trim();
    const currentEmail = document.getElementById('email').value;
    
    if (!newEmail) {
        showEmailError(errorElement, 'Please enter a new email address');
        return false;
    }
    
    if (!isValidEmail(newEmail)) {
        showEmailError(errorElement, 'Please enter a valid email address');
        return false;
    }
    
    if (newEmail === currentEmail) {
        showEmailError(errorElement, 'New email must be different from current email');
        return false;
    }
    
    hideEmailError(errorElement);
    return true;
}

function validateVerificationCode() {
    const codeField = document.getElementById('verificationCode');
    const errorElement = document.getElementById('verificationCodeError');
    const code = codeField.value.trim();
    
    if (!code) {
        showEmailError(errorElement, 'Please enter the verification code');
        return false;
    }
    
    if (code.length !== 6 || !/^\d{6}$/.test(code)) {
        showEmailError(errorElement, 'Please enter a valid 6-digit code');
        return false;
    }
    
    hideEmailError(errorElement);
    return true;
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function showEmailError(element, message) {
    if (element) {
        element.textContent = message;
        element.classList.add('show');
    }
}

function hideEmailError(element) {
    if (element) {
        element.classList.remove('show');
        element.textContent = '';
    }
}

function sendVerificationCode() {
    if (!validateNewEmail()) {
        return;
    }
    
    const newEmail = document.getElementById('newEmail').value.trim();
    const currentEmail = document.getElementById('email').value;
    const sendButton = document.getElementById('sendCodeBtn');
    const userId = window.USER_ID;
    
    if (!userId) {
        Swal.fire({
            icon: 'error',
            title: 'Authentication Error',
            text: 'User session not found. Please refresh the page and try again.',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    // Disable button and show loading
    sendButton.disabled = true;
    sendButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    
    const formData = new FormData();
    formData.append('action', 'send_verification');
    formData.append('new_email', newEmail);
    formData.append('current_email', currentEmail);
    formData.append('user_id', userId);
    
    fetch('./API/changeEmail.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Send verification response:', data);
        
        if (data.success) {
            currentVerificationEmail = newEmail;
            document.getElementById('displayNewEmail').textContent = newEmail;
            showEmailStep(2);
            
            Swal.fire({
                icon: 'success',
                title: 'Verification Code Sent!',
                text: `A verification code has been sent to ${newEmail}`,
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false
            });
            
            // Start resend timer
            startResendTimer();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Failed to Send Code',
                text: data.message || 'Unable to send verification code. Please try again.',
                confirmButtonText: 'OK'
            });
        }
    })
    .catch(error => {
        console.error('Error sending verification code:', error);
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: 'Failed to send verification code. Please check your connection and try again.',
            confirmButtonText: 'OK'
        });
    })
    .finally(() => {
        // Restore button state
        sendButton.disabled = false;
        sendButton.innerHTML = 'Send Verification Code';
    });
}

function verifyCodeAndUpdateEmail() {
    if (!validateVerificationCode()) {
        return;
    }
    
    const verificationCode = document.getElementById('verificationCode').value.trim();
    const newEmail = currentVerificationEmail || document.getElementById('newEmail').value.trim();
    const verifyButton = document.getElementById('verifyCodeBtn');
    const userId = window.USER_ID;
    
    if (!newEmail) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Email information is missing. Please start the verification process again.',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    if (!userId) {
        Swal.fire({
            icon: 'error',
            title: 'Authentication Error',
            text: 'User session not found. Please refresh the page and try again.',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    // Disable button and show loading
    verifyButton.disabled = true;
    verifyButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
    
    const formData = new FormData();
    formData.append('action', 'verify_code');
    formData.append('new_email', newEmail);
    formData.append('verification_code', verificationCode);
    formData.append('user_id', userId);
    
    fetch('./API/changeEmail.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Verify code response:', data);
        
        if (data.success) {
            // Update the email field with new email
            document.getElementById('email').value = newEmail;
            
            // Close modal
            closeChangeEmailModal();
            
            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'Email Updated Successfully!',
                text: `Your email has been changed to ${newEmail}`,
                confirmButtonText: 'OK'
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Verification Failed',
                text: data.message || 'Invalid verification code. Please try again.',
                confirmButtonText: 'OK'
            });
        }
    })
    .catch(error => {
        console.error('Error verifying code:', error);
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: 'Failed to verify code. Please check your connection and try again.',
            confirmButtonText: 'OK'
        });
    })
    .finally(() => {
        // Restore button state
        verifyButton.disabled = false;
        verifyButton.innerHTML = 'Verify & Update';
    });
}

function goBackToEmailStep() {
    showEmailStep(1);
    clearEmailValidationStates();
}

function resendVerificationCode() {
    const newEmail = currentVerificationEmail || document.getElementById('newEmail').value.trim();
    const currentEmail = document.getElementById('email').value;
    const resendButton = document.getElementById('resendCodeBtn');
    const userId = window.USER_ID;
    
    if (!newEmail) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Email information is missing. Please start the verification process again.',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    if (!userId) {
        Swal.fire({
            icon: 'error',
            title: 'Authentication Error',
            text: 'User session not found. Please refresh the page and try again.',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    // Disable button and show loading
    resendButton.disabled = true;
    resendButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resending...';
    
    const formData = new FormData();
    formData.append('action', 'send_verification');
    formData.append('new_email', newEmail);
    formData.append('current_email', currentEmail);
    formData.append('user_id', userId);
    
    fetch('./API/changeEmail.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Code Resent!',
                text: `A new verification code has been sent to ${newEmail}`,
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false
            });
            
            // Start resend timer again
            startResendTimer();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Failed to Resend Code',
                text: data.message || 'Unable to resend verification code. Please try again.',
                confirmButtonText: 'OK'
            });
        }
    })
    .catch(error => {
        console.error('Error resending verification code:', error);
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: 'Failed to resend verification code. Please check your connection and try again.',
            confirmButtonText: 'OK'
        });
    })
    .finally(() => {
        // Restore button state
        resendButton.disabled = false;
        resendButton.innerHTML = 'Resend Code';
    });
}

function startResendTimer() {
    const resendButton = document.getElementById('resendCodeBtn');
    let timeLeft = 60; // 60 seconds cooldown
    
    resendButton.disabled = true;
    
    resendTimer = setInterval(() => {
        if (timeLeft > 0) {
            resendButton.innerHTML = `Resend Code (${timeLeft}s)`;
            timeLeft--;
        } else {
            resendButton.innerHTML = 'Resend Code';
            resendButton.disabled = false;
            clearInterval(resendTimer);
            resendTimer = null;
        }
    }, 1000);
}

// Initialize password form functionality
function initializePasswordForm() {
    const newPasswordField = document.getElementById('newPassword');
    const confirmPasswordField = document.getElementById('confirmPassword');
    const securityForm = document.getElementById('securityForm');
    
    if (newPasswordField) {
        newPasswordField.addEventListener('input', function() {
            updatePasswordStrength(this.value);
            checkPasswordMatch();
        });
    }
    
    if (confirmPasswordField) {
        confirmPasswordField.addEventListener('input', checkPasswordMatch);
    }
    
    if (securityForm) {
        securityForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handlePasswordChange();
        });
    }
}

// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// Update password strength indicator
function updatePasswordStrength(password) {
    const strengthBar = document.querySelector('.strengthFill');
    const strengthText = document.querySelector('.strengthText');
    
    if (!strengthBar || !strengthText) return;
    
    let strength = 0;
    let text = 'Very Weak';
    let color = '#ff4444';
    
    if (password.length >= 8) strength += 20;
    if (password.match(/[a-z]/)) strength += 20;
    if (password.match(/[A-Z]/)) strength += 20;
    if (password.match(/[0-9]/)) strength += 20;
    if (password.match(/[^a-zA-Z0-9]/)) strength += 20;
    
    if (strength >= 80) {
        text = 'Very Strong';
        color = '#22c55e';
    } else if (strength >= 60) {
        text = 'Strong';
        color = '#84cc16';
    } else if (strength >= 40) {
        text = 'Medium';
        color = '#f59e0b';
    } else if (strength >= 20) {
        text = 'Weak';
        color = '#f97316';
    }
    
    strengthBar.style.width = strength + '%';
    strengthBar.style.backgroundColor = color;
    strengthText.textContent = text;
}

// Check if passwords match
function checkPasswordMatch() {
    const newPassword = document.getElementById('newPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    const matchIndicator = document.getElementById('matchIndicator');
    
    if (!newPassword || !confirmPassword || !matchIndicator) return;
    
    if (confirmPassword.value === '') {
        matchIndicator.textContent = '';
        return;
    }
    
    if (newPassword.value === confirmPassword.value) {
        matchIndicator.textContent = '✓ Passwords match';
        matchIndicator.style.color = '#22c55e';
    } else {
        matchIndicator.textContent = '✗ Passwords do not match';
        matchIndicator.style.color = '#ef4444';
    }
}

// Handle password change form submission (matching artist portal implementation)
function handlePasswordChange() {
    const currentPassword = document.getElementById('currentPassword')?.value;
    const newPassword = document.getElementById('newPassword')?.value;
    const confirmPassword = document.getElementById('confirmPassword')?.value;
    
    // Validate required fields
    if (!currentPassword || !newPassword || !confirmPassword) {
        Swal.fire({
            icon: 'error',
            title: 'Missing Information',
            text: 'Please fill in all password fields.',
            confirmButtonColor: '#8B4513'
        });
        return;
    }
    
    // Validate password match
    if (newPassword !== confirmPassword) {
        Swal.fire({
            icon: 'error',
            title: 'Password Mismatch',
            text: 'New password and confirmation do not match.',
            confirmButtonColor: '#8B4513'
        });
        return;
    }
    
    // Show loading state
    Swal.fire({
        title: 'Changing Password...',
        text: 'Please wait while we update your password.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Make API call using JSON format as expected by changePassword.php
    fetch('API/changePassword.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            currentPassword: currentPassword,
            newPassword: newPassword,
            confirmPassword: confirmPassword
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        const contentType = response.headers.get('content-type');
        console.log('Content type:', contentType);
        
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server returned non-JSON response');
        }
        
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            // Show success message with email notification info
            let successMessage = 'Your password has been updated successfully.';
            if (data.data && data.data.email_notification) {
                successMessage += '\n\n' + data.data.email_notification;
            }
            
            Swal.fire({
                icon: 'success',
                title: 'Password Changed!',
                text: successMessage,
                confirmButtonColor: '#8B4513'
            }).then(() => {
                // Clear the form
                document.getElementById('currentPassword').value = '';
                document.getElementById('newPassword').value = '';
                document.getElementById('confirmPassword').value = '';
                
                // Reset password strength indicator
                const strengthBar = document.querySelector('.strengthFill');
                const strengthText = document.querySelector('.strengthText');
                if (strengthBar) {
                    strengthBar.style.width = '0%';
                    strengthBar.style.backgroundColor = '#e5e7eb';
                }
                if (strengthText) {
                    strengthText.textContent = 'Password strength';
                }
                
                // Reset password match indicator
                const matchIndicator = document.getElementById('matchIndicator');
                if (matchIndicator) {
                    matchIndicator.textContent = '';
                    matchIndicator.style.color = '';
                }
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Password Change Failed',
                text: data.message || 'Failed to change password. Please try again.',
                confirmButtonColor: '#8B4513'
            });
        }
    })
    .catch(error => {
        console.error('Error changing password:', error);
        
        let errorMessage = 'Failed to change password. Please try again.';
        
        if (error.message === 'Server returned non-JSON response') {
            errorMessage = 'Server error occurred. Please try again later.';
        }
        
        Swal.fire({
            icon: 'error',
            title: 'Password Change Failed',
            text: errorMessage,
            confirmButtonColor: '#8B4513'
        });
    });
}

// Function to delete a review
function deleteReview(reviewId) {
    // Show confirmation dialog using SweetAlert
    Swal.fire({
        title: 'Delete Review?',
        text: 'Are you sure you want to delete this review? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#8b7355',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Proceed with deletion
            const userId = window.USER_ID;
            
            if (!userId) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'User session not found. Please refresh the page.',
                    confirmButtonColor: '#8b7355'
                });
                return;
            }
            
            // Create form data
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('review_id', reviewId);
            
            // Show loading state
            Swal.fire({
                title: 'Deleting Review...',
                text: 'Please wait while we delete your review.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Send delete request
            fetch('API/deleteReview.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the review card from DOM
                    const reviewCard = document.querySelector(`[data-review-id="${reviewId}"]`);
                    if (reviewCard) {
                        reviewCard.remove();
                    }
                    
                    // Check if there are any reviews left
                    const remainingReviews = document.querySelectorAll('.review-card');
                    if (remainingReviews.length === 0) {
                        document.getElementById('reviewList').innerHTML = '<div class="no-reviews"><p>No reviews found.</p></div>';
                    }
                    
                    // Update review count in sidebar
                    loadProfileStats();
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Review Deleted',
                        text: 'Your review has been successfully deleted.',
                        confirmButtonColor: '#8b7355'
                    });
                } else {
                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'Delete Failed',
                        text: data.message || 'Failed to delete review. Please try again.',
                        confirmButtonColor: '#8b7355'
                    });
                }
            })
            .catch(error => {
                console.error('Error deleting review:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while deleting the review. Please try again.',
                    confirmButtonColor: '#8b7355'
                });
            });
        }
    });
}

// Function to load user's profile photo
function loadProfilePhoto() {
    const userId = window.USER_ID;
    if (!userId) return;
    
    // For now, we'll use a simple approach - try to load the photo based on naming convention
    // In a production environment, you'd want to get this from the database
    const avatarImg = document.querySelector('.profile-avatar img');
    if (!avatarImg) return;
    
    // Create a test image to check if user profile photo exists
    const testImg = new Image();
    testImg.onload = function() {
        // Photo exists, update the avatar
        avatarImg.src = this.src;
    };
    testImg.onerror = function() {
        // Photo doesn't exist, keep the default placeholder
        console.log('No profile photo found for user, using default');
    };
    
    // Try to load the profile photo (we'll need to get the user's name from somewhere)
    // For now, let's use a generic pattern and see if we can get the actual filename from the database later
    fetch('API/getUserProfilePhoto.php', {
        method: 'POST',
        body: new URLSearchParams({
            'user_id': userId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.profile_photo_url) {
            // Add timestamp to prevent caching
            const timestamp = new Date().getTime();
            avatarImg.src = `${data.profile_photo_url}?t=${timestamp}`;
            console.log('Profile photo loaded successfully:', data.profile_photo_url);
        } else {
            console.log('No profile photo found, using default');
        }
    })
    .catch(error => {
        console.log('Profile photo API error:', error);
    });
}

// Initialize profile photo upload functionality
function initializeProfilePhotoUpload() {
    console.log('Starting profile photo upload initialization...');
    
    const avatarUploadBtn = document.querySelector('.avatar-upload-btn');
    console.log('Avatar upload button found:', avatarUploadBtn);
    
    if (!avatarUploadBtn) {
        console.error('Avatar upload button not found!');
        return;
    }
    
    // Create hidden file input
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = 'image/*';
    fileInput.style.display = 'none';
    fileInput.id = 'profile-photo-input';
    document.body.appendChild(fileInput);
    console.log('File input created and added to DOM');
    
    // Handle avatar upload button click
    avatarUploadBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        console.log('Avatar upload button clicked!');
        fileInput.click();
    });
    
    // Handle file selection
    fileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        
        console.log('File selected:', file.name);
        
        // Validate file type
        if (!file.type.startsWith('image/')) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid File Type',
                text: 'Please select a valid image file (JPG, PNG, GIF, etc.)',
                confirmButtonColor: '#8b7355'
            });
            return;
        }
        
        // Validate file size (max 5MB)
        const maxSize = 5 * 1024 * 1024; // 5MB in bytes
        if (file.size > maxSize) {
            Swal.fire({
                icon: 'error',
                title: 'File Too Large',
                text: 'Please select an image smaller than 5MB',
                confirmButtonColor: '#8b7355'
            });
            return;
        }
        
        // Show confirmation dialog with preview
        const reader = new FileReader();
        reader.onload = (e) => {
            Swal.fire({
                title: 'Change Profile Photo?',
                html: `
                    <div style="margin: 20px 0;">
                        <img src="${e.target.result}" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #8b7355; box-shadow: 0 4px 12px rgba(0,0,0,0.1);" />
                    </div>
                    <p style="color: #666; font-size: 14px;">This will replace your current profile photo.</p>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#8b7355',
                cancelButtonColor: '#dc2626',
                confirmButtonText: 'Yes, update photo',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    uploadProfilePhoto(file);
                }
                // Reset file input
                fileInput.value = '';
            });
        };
        reader.readAsDataURL(file);
    });
    
    console.log('Profile photo upload initialized successfully');
}

// Function to upload profile photo
function uploadProfilePhoto(file) {
    const userId = window.USER_ID;
    
    if (!userId) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'User session not found. Please refresh the page.',
            confirmButtonColor: '#8b7355'
        });
        return;
    }
    
    // Show loading state
    Swal.fire({
        title: 'Uploading Photo...',
        text: 'Please wait while we update your profile photo.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Create form data
    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('profile_photo', file);
    
    // Send upload request
    fetch('API/updateProfilePhoto.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Upload response:', data);
        if (data.success) {
            // Update the profile avatar image immediately
            const avatarImg = document.querySelector('.profile-avatar img');
            if (avatarImg && data.profile_picture_url) {
                // Add timestamp to prevent caching
                const timestamp = new Date().getTime();
                avatarImg.src = `${data.profile_picture_url}?t=${timestamp}`;
            }
            
            // Also reload the profile photo using the proper API to ensure consistency
            setTimeout(() => {
                loadProfilePhoto();
            }, 100);
            
            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'Photo Updated!',
                text: 'Your profile photo has been successfully updated.',
                confirmButtonColor: '#8b7355'
            });
        } else {
            // Show error message
            Swal.fire({
                icon: 'error',
                title: 'Upload Failed',
                text: data.message || 'Failed to update profile photo. Please try again.',
                confirmButtonColor: '#8b7355'
            });
        }
    })
    .catch(error => {
        console.error('Error uploading profile photo:', error);
        Swal.fire({
            icon: 'error',
            title: 'Upload Error',
            text: 'An error occurred while uploading your photo. Please try again.',
            confirmButtonColor: '#8b7355'
        });
    });
}
