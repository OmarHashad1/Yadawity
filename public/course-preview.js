// Course Preview Interactive Functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeCoursePreview();
});

function initializeCoursePreview() {
    // Initialize all interactive components
    setupCurriculumToggle();
    setupPreviewVideo();
    setupCartFunctionality();
    setupReviewActions();
    setupExpandableContent();
    setupStickyCard();
    setupProgressiveLoading();
}

// Curriculum Section Toggle
function setupCurriculumToggle() {
    const curriculumSections = document.querySelectorAll('.curriculum-section');
    
    // Expand first section by default and set its arrow to down
    if (curriculumSections.length > 0) {
        curriculumSections[0].classList.add('expanded');
        const firstSectionIcon = curriculumSections[0].querySelector('.section-toggle i');
        firstSectionIcon.className = 'fas fa-chevron-down';
    }
    
    curriculumSections.forEach(section => {
        const header = section.querySelector('.section-header');
        const toggle = section.querySelector('.section-toggle');
        
        header.addEventListener('click', function() {
            // Toggle expanded state
            section.classList.toggle('expanded');
            
            // Update toggle icon - down when expanded (showing content), right when collapsed (hiding content)
            const icon = toggle.querySelector('i');
            if (section.classList.contains('expanded')) {
                icon.className = 'fas fa-chevron-down';
            } else {
                icon.className = 'fas fa-chevron-right';
            }
            
            // Smooth animation
            const content = section.querySelector('.section-content');
            if (section.classList.contains('expanded')) {
                content.style.maxHeight = content.scrollHeight + 'px';
            } else {
                content.style.maxHeight = '0';
            }
        });
    });
    
    // Show all sections button
    const showAllBtn = document.querySelector('.show-all-sections');
    if (showAllBtn) {
        showAllBtn.addEventListener('click', function() {
            const hiddenSections = document.querySelectorAll('.curriculum-section:not(.expanded)');
            
            if (hiddenSections.length > 0) {
                // Expand all sections
                hiddenSections.forEach(section => {
                    section.classList.add('expanded');
                    const icon = section.querySelector('.section-toggle i');
                    icon.className = 'fas fa-chevron-down';
                    
                    // Set proper height for expanded content
                    const content = section.querySelector('.section-content');
                    content.style.maxHeight = content.scrollHeight + 'px';
                });
                showAllBtn.innerHTML = '<span>Show less</span><i class="fas fa-chevron-up"></i>';
            } else {
                // Collapse all except first
                curriculumSections.forEach((section, index) => {
                    if (index > 0) {
                        section.classList.remove('expanded');
                        const icon = section.querySelector('.section-toggle i');
                        icon.className = 'fas fa-chevron-right';
                        
                        // Collapse content properly
                        const content = section.querySelector('.section-content');
                        content.style.maxHeight = '0';
                    }
                });
                showAllBtn.innerHTML = '<span>Show all sections</span><i class="fas fa-chevron-down"></i>';
            }
        });
    }
}

// Preview Video Functionality
function setupPreviewVideo() {
    const playButtons = document.querySelectorAll('.play-button, .play-button-small');
    
    playButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Create modal for video preview
            createVideoModal();
        });
    });
    
    // Preview buttons in curriculum
    const previewBtns = document.querySelectorAll('.preview-btn');
    previewBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            createVideoModal();
        });
    });
}

function createVideoModal() {
    // Remove existing modal if any
    const existingModal = document.querySelector('.video-modal');
    if (existingModal) {
        existingModal.remove();
    }
    
    const modal = document.createElement('div');
    modal.className = 'video-modal';
    modal.innerHTML = `
        <div class="modal-overlay">
            <div class="modal-content">
                <button class="modal-close">&times;</button>
                <div class="video-container">
                    <video controls autoplay>
                        <source src="./video/course-preview.mp4" type="video/mp4">
                        <p>Your browser doesn't support video playback.</p>
                    </video>
                </div>
                <div class="video-info">
                    <h3>Course Preview</h3>
                    <p>Get a glimpse of what you'll learn in this comprehensive oil painting course.</p>
                </div>
            </div>
        </div>
    `;
    
    // Add modal styles
    const style = document.createElement('style');
    style.textContent = `
        .video-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .modal-content {
            background: white;
            border-radius: 8px;
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow: hidden;
            position: relative;
        }
        
        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 1.5rem;
            cursor: pointer;
            z-index: 10001;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .video-container {
            position: relative;
            width: 100%;
            aspect-ratio: 16/9;
        }
        
        .video-container video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .video-info {
            padding: 1.5rem;
        }
        
        .video-info h3 {
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        
        .video-info p {
            color: var(--text-secondary);
        }
    `;
    
    document.head.appendChild(style);
    document.body.appendChild(modal);
    
    // Close modal functionality
    const closeBtn = modal.querySelector('.modal-close');
    const overlay = modal.querySelector('.modal-overlay');
    
    closeBtn.addEventListener('click', () => modal.remove());
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) modal.remove();
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') modal.remove();
    });
}

// Cart Functionality
function setupCartFunctionality() {
    const addToCartBtns = document.querySelectorAll('.add-to-cart, .btn-primary-sidebar');
    const buyNowBtns = document.querySelectorAll('.buy-now, .btn-secondary-sidebar');
    
    addToCartBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Add loading state
            const originalText = btn.textContent;
            btn.textContent = 'Adding...';
            btn.disabled = true;
            
            setTimeout(() => {
                btn.textContent = 'Added to Cart';
                btn.style.background = 'var(--success-green)';
                showNotification('Course added to cart!', 'success');
                
                // Update cart count
                updateCartCount();
                
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.disabled = false;
                    btn.style.background = '';
                }, 2000);
            }, 1000);
        });
    });
    
    buyNowBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Simulate buy now process
            btn.textContent = 'Processing...';
            btn.disabled = true;
            
            setTimeout(() => {
                showNotification('Redirecting to checkout...', 'success');
                // In a real app, this would redirect to checkout
                btn.textContent = 'Buy now';
                btn.disabled = false;
            }, 1500);
        });
    });
}

function updateCartCount() {
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) {
        const currentCount = parseInt(cartCount.textContent) || 0;
        cartCount.textContent = currentCount + 1;
        
        // Add animation
        cartCount.style.animation = 'bounce 0.6s ease-in-out';
        setTimeout(() => {
            cartCount.style.animation = '';
        }, 600);
    }
}

// Review Actions
function setupReviewActions() {
    const helpfulBtns = document.querySelectorAll('.helpful-btn');
    const reportBtns = document.querySelectorAll('.report-btn');
    
    helpfulBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const countSpan = btn.textContent.match(/\((\d+)\)/);
            if (countSpan) {
                const currentCount = parseInt(countSpan[1]);
                const newCount = currentCount + 1;
                btn.innerHTML = `<i class="fas fa-thumbs-up"></i> Helpful (${newCount})`;
                btn.style.color = 'var(--udemy-purple)';
                btn.disabled = true;
                showNotification('Thank you for your feedback!', 'success');
            }
        });
    });
    
    reportBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Are you sure you want to report this review?')) {
                showNotification('Review reported. Thank you!', 'info');
                btn.disabled = true;
                btn.style.opacity = '0.5';
            }
        });
    });
    
    // Show more reviews
    const showMoreBtn = document.querySelector('.show-more-reviews');
    if (showMoreBtn) {
        showMoreBtn.addEventListener('click', function() {
            // In a real app, this would load more reviews
            showNotification('Loading more reviews...', 'info');
            
            setTimeout(() => {
                showNotification('All reviews loaded!', 'success');
                showMoreBtn.style.display = 'none';
            }, 1500);
        });
    }
}

// Expandable Content
function setupExpandableContent() {
    const showMoreBtns = document.querySelectorAll('.show-more-btn');
    
    showMoreBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const content = btn.closest('.description-content');
            const hiddenContent = content.querySelector('.hidden-content');
            
            if (!hiddenContent) {
                // Create hidden content
                const extraContent = document.createElement('div');
                extraContent.className = 'hidden-content';
                extraContent.innerHTML = `
                    <h3>Who This Course Is For:</h3>
                    <ul>
                        <li><strong>Complete Beginners:</strong> No prior experience needed - start from scratch</li>
                        <li><strong>Hobby Artists:</strong> Take your painting to the next level</li>
                        <li><strong>Art Students:</strong> Supplement your formal education with practical techniques</li>
                        <li><strong>Professional Artists:</strong> Refine your oil painting skills and learn new approaches</li>
                    </ul>
                    
                    <h3>Course Structure:</h3>
                    <p>This course is carefully structured to build your skills progressively. Each section includes theoretical knowledge, practical demonstrations, and hands-on projects. You'll start with the basics and gradually work your way up to advanced techniques used by master artists.</p>
                    
                    <h3>Community & Support:</h3>
                    <p>Join our vibrant community of artists where you can share your work, get feedback, and connect with fellow students. Our instructor provides regular feedback and guidance to help you improve.</p>
                `;
                
                btn.parentNode.insertBefore(extraContent, btn);
                btn.innerHTML = 'Show less <i class="fas fa-chevron-up"></i>';
            } else {
                if (hiddenContent.style.display === 'none') {
                    hiddenContent.style.display = 'block';
                    btn.innerHTML = 'Show less <i class="fas fa-chevron-up"></i>';
                } else {
                    hiddenContent.style.display = 'none';
                    btn.innerHTML = 'Show more <i class="fas fa-chevron-down"></i>';
                }
            }
        });
    });
}

// Sticky Card Functionality
function setupStickyCard() {
    const stickyCard = document.querySelector('.sticky-card');
    const heroSection = document.querySelector('.course-hero');
    
    if (stickyCard && heroSection) {
        let isSticky = false;
        
        function checkStickyPosition() {
            const heroBottom = heroSection.offsetTop + heroSection.offsetHeight;
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            if (scrollTop > heroBottom && !isSticky) {
                stickyCard.style.position = 'fixed';
                stickyCard.style.top = '100px';
                stickyCard.style.width = '400px';
                stickyCard.style.zIndex = '999';
                isSticky = true;
            } else if (scrollTop <= heroBottom && isSticky) {
                stickyCard.style.position = 'sticky';
                stickyCard.style.top = '100px';
                stickyCard.style.width = 'auto';
                stickyCard.style.zIndex = 'auto';
                isSticky = false;
            }
        }
        
        window.addEventListener('scroll', checkStickyPosition);
        window.addEventListener('resize', checkStickyPosition);
    }
}

// Progressive Loading for Better Performance
function setupProgressiveLoading() {
    // Lazy load images
    const images = document.querySelectorAll('img[data-src]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    observer.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    }
    
    // Progressive content loading
    const sections = document.querySelectorAll('.course-section');
    
    if ('IntersectionObserver' in window) {
        const sectionObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '50px'
        });
        
        sections.forEach(section => {
            section.style.opacity = '0';
            section.style.transform = 'translateY(20px)';
            section.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            sectionObserver.observe(section);
        });
    }
}

// Notification System
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notif => notif.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Add notification styles
    const style = document.createElement('style');
    style.textContent = `
        .notification {
            position: fixed;
            top: 100px;
            right: 20px;
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            border-left: 4px solid var(--udemy-purple);
            z-index: 10000;
            font-weight: 600;
            color: var(--text-primary);
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }
        
        .notification-success {
            border-left-color: var(--success-green);
        }
        
        .notification-info {
            border-left-color: var(--udemy-purple);
        }
        
        .notification-error {
            border-left-color: var(--udemy-red);
        }
        
        .notification.show {
            transform: translateX(0);
        }
        
        @keyframes bounce {
            0%, 20%, 60%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            80% {
                transform: translateY(-5px);
            }
        }
    `;
    
    if (!document.querySelector('#notification-styles')) {
        style.id = 'notification-styles';
        document.head.appendChild(style);
    }
    
    document.body.appendChild(notification);
    
    // Trigger animation
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    // Auto remove
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Share Functionality
function setupShareFunctionality() {
    const shareBtn = document.querySelector('.share-btn');
    
    if (shareBtn) {
        shareBtn.addEventListener('click', function() {
            if (navigator.share) {
                navigator.share({
                    title: 'Mastering Oil Painting Techniques',
                    text: 'Check out this amazing oil painting course!',
                    url: window.location.href
                });
            } else {
                // Fallback - copy to clipboard
                navigator.clipboard.writeText(window.location.href).then(() => {
                    showNotification('Course link copied to clipboard!', 'success');
                });
            }
        });
    }
}

// Initialize share functionality
document.addEventListener('DOMContentLoaded', function() {
    setupShareFunctionality();
});

// Smooth scrolling for anchor links
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

// Handle window resize for responsive adjustments
window.addEventListener('resize', function() {
    // Recalculate sticky card position
    const stickyCard = document.querySelector('.sticky-card');
    if (stickyCard && window.innerWidth <= 1024) {
        stickyCard.style.position = 'static';
        stickyCard.style.width = 'auto';
    }
});