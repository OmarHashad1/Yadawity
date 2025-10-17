// Yadawity Navbar Component JavaScript

// Initialize navbar functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all navbar functions
    initializeNavbar();
});

function initializeNavbar() {
    // Mobile menu toggle
    setupMobileMenu();
    
    // Search functionality
    setupSearch();
    
    // User dropdown
    setupUserDropdown();
    
    // Navbar scroll effects
    
    // Cart and wishlist counters
    setupCounters();
    
    // Active page detection
    setActivePage();
}

// Mobile Menu Toggle - Use existing BurgerMenu component
function setupMobileMenu() {
    const navToggle = document.querySelector('.nav-toggle');
    
    if (navToggle) {
        navToggle.addEventListener('click', function() {
            // Check if burger menu function exists globally
            if (typeof openBurgerMenu === 'function') {
                openBurgerMenu();
            } else {
                // Fallback toggle
                const navMenu = document.querySelector('.nav-menu');
                if (navMenu) {
                    navMenu.classList.toggle('active');
                    navToggle.classList.toggle('active');
                }
            }
        });
    }
}

// Search Functionality
function setupSearch() {
    const searchInput = document.querySelector('.searchInput');
    const mobileSearchInput = document.querySelector('.mobileSearchInput');
    const searchBtn = document.getElementById('searchButton'); // Fixed selector to match HTML ID
    const mobileSearchOverlay = document.querySelector('.mobileSearchOverlay');
    const mobileSearchClose = document.querySelector('.mobileSearchClose');
    const mobileSearchIcon = document.querySelector('.navIconLink.mobileSearch');

    // Desktop search
    if (searchBtn && searchInput) {
        searchBtn.addEventListener('click', function(e) {
            e.preventDefault();
            performSearch(searchInput.value);
        });

        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch(this.value);
            }
        });

        // Search suggestions (placeholder functionality)
        searchInput.addEventListener('input', function() {
            // You can implement search suggestions here
        });
    }

    // Mobile search overlay
    if (mobileSearchIcon && mobileSearchOverlay) {
        mobileSearchIcon.addEventListener('click', function(e) {
            e.preventDefault();
            openMobileSearch();
        });
    }

    if (mobileSearchClose) {
        mobileSearchClose.addEventListener('click', function() {
            closeMobileSearch();
        });
    }

    if (mobileSearchOverlay) {
        mobileSearchOverlay.addEventListener('click', function(e) {
            if (e.target === mobileSearchOverlay) {
                closeMobileSearch();
            }
        });
    }

    if (mobileSearchInput) {
        mobileSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch(this.value);
                closeMobileSearch();
            }
        });
    }
}

function performSearch(query) {
    if (query.trim()) {
        // Implement your search logic here
        // For example: window.location.href = `/search?q=${encodeURIComponent(query)}`;
        
        // Placeholder alert - replace with actual search functionality
        alert(`Searching for: "${query}"`);
    }
}

function openMobileSearch() {
    const overlay = document.querySelector('.mobileSearchOverlay');
    const input = document.querySelector('.mobileSearchInput');
    
    if (overlay) {
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Focus on input after animation
        setTimeout(() => {
            if (input) input.focus();
        }, 300);
    }
}

function closeMobileSearch() {
    const overlay = document.querySelector('.mobileSearchOverlay');
    
    if (overlay) {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// User Dropdown
function setupUserDropdown() {
    const userDropdown = document.querySelector('.user-dropdown');
    const dropdownMenu = document.querySelector('.user-dropdown-menu');

    if (userDropdown && dropdownMenu) {
        // Handle dropdown for touch devices
        userDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Toggle dropdown on mobile/touch devices
            if (window.innerWidth <= 768) {
                dropdownMenu.style.opacity = dropdownMenu.style.opacity === '1' ? '0' : '1';
                dropdownMenu.style.visibility = dropdownMenu.style.visibility === 'visible' ? 'hidden' : 'visible';
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userDropdown.contains(e.target)) {
                dropdownMenu.style.opacity = '0';
                dropdownMenu.style.visibility = 'hidden';
            }
        });
    }
}

// Navbar Scroll Effects


// Cart and Wishlist Counters
function setupCounters() {
    // These functions would typically connect to your cart/wishlist data
    updateCartCount();
    updateWishlistCount();
}

function updateCartCount(count = 3) {
    const cartCount = document.querySelector('.cartCount');
    if (cartCount) {
        cartCount.textContent = count;
        cartCount.style.display = count > 0 ? 'flex' : 'none';
    }
}

function updateWishlistCount(count = 7) {
    const wishlistCount = document.querySelector('.wishlistCount');
    if (wishlistCount) {
        wishlistCount.textContent = count;
        wishlistCount.style.display = count > 0 ? 'flex' : 'none';
    }
}

// Active Page Detection
function setActivePage() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.navLink');
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        
        // Check if the link's href matches the current path
        const linkPath = new URL(link.href).pathname;
        if (linkPath === currentPath || 
            (linkPath !== '/' && currentPath.includes(linkPath))) {
            link.classList.add('active');
        }
    });
    
    // If no link matches, set home as active for root path
    if (currentPath === '/' || currentPath === '/index.php') {
        const homeLink = document.querySelector('.navLink[href*="index"], .navLink[href="/"], .navLink[href="#home"]');
        if (homeLink) {
            homeLink.classList.add('active');
        }
    }
}

// Utility Functions
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

// Smooth scrolling for anchor links
function setupSmoothScroll() {
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            
            // Skip invalid selectors (empty hash or just #)
            if (!targetId || targetId === '#' || targetId.length <= 1) {
                return;
            }
            
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                e.preventDefault();
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Initialize smooth scrolling
setupSmoothScroll();

// Export functions for external use
window.YadawityNavbar = {
    updateCartCount,
    updateWishlistCount,
    setActivePage,
    openMobileSearch,
    closeMobileSearch,
    performSearch
};

// Handle resize events
window.addEventListener('resize', debounce(function() {
    // Close mobile menu on resize to larger screen
    if (window.innerWidth > 1438) {
        const navMenu = document.querySelector('.nav-menu');
        const navToggle = document.querySelector('.nav-toggle');
        
        if (navMenu) {
            navMenu.classList.remove('active');
            // Remove mobile actions when resizing to desktop
            const mobileActions = navMenu.querySelector('.mobile-nav-actions');
            if (mobileActions) {
                mobileActions.remove();
            }
        }
        if (navToggle) navToggle.classList.remove('active');
    }
}, 250));


const artisanFilterButtons = document.querySelectorAll('.artisanCategoryBtn');

artisanFilterButtons.forEach(button => {
    button.addEventListener('click', () => {
        // Remove active class from all buttons
        artisanFilterButtons.forEach(btn => btn.classList.remove('active'));
        // Add active class to clicked button
        button.classList.add('active');

        const filterValue = button.dataset.filter;
    });
});

const slider = document.getElementById('cardSlider');

function slideRight() {
  slider.scrollBy({ left: 320, behavior: 'smooth' });
}

function slideLeft() {
  slider.scrollBy({ left: -320, behavior: 'smooth' });
}

// Yadawity mansory layout cards
 // Add interactive functionality - this will be replaced by cart.js for real functionality
 document.querySelectorAll('.addToCart').forEach(button => {
    button.addEventListener('click', function() {
        // This is a fallback - the real cart functionality is in cart.js
        
        // If cart manager is available, use it
        if (window.cartManager) {
            window.cartManager.addToCart(this);
        } else {
            const card = this.closest('.artworkCard');
            const title = card.querySelector('.artworkTitle').textContent;
            
            // Simple feedback animation
            this.textContent = 'Added!';
            this.style.backgroundColor = '#28a745';
            
            setTimeout(() => {
                this.textContent = 'Add to cart';
                this.style.backgroundColor = '#4a3c2a';
            }, 2000);
        }
    });
});

// Hover effects for cards
document.querySelectorAll('.artworkCard').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
});


//testimonials card scroll 
   // testimonials data
   const testimonialss = [
    {
        name: "Lord Edmund Blackwood",
        title: "Art Collector",
        text: "The therapeutic arts program has provided profound healing through the mastery of classical techniques.",
        image: "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop&crop=face"
    },
    {
        name: "Dr. Sarah Chen",
        title: "Art Therapist",
        text: "This program has revolutionized how I approach healing through creative expression. The results speak for themselves.",
        image: "https://images.unsplash.com/photo-1489424731084-a5d8b219a5bb?w=150&h=150&fit=crop&crop=face"
    },
    {
        name: "Marcus Rodriguez",
        title: "Creative Director",
        text: "The fusion of traditional techniques with modern therapeutic approaches created a transformative experience for our team.",
        image: "https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&h=150&fit=crop&crop=face"
    },
    {
        name: "Isabella Morrison",
        title: "Museum Curator",
        text: "An extraordinary journey through the healing power of art. The program's methodology is both innovative and deeply rooted in tradition.",
        image: "https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150&h=150&fit=crop&crop=face"
    },
    {
        name: "Prof. James Wellington",
        title: "Fine Arts Professor",
        text: "Never before have I witnessed such a profound connection between artistic practice and personal transformation.",
        image: "https://images.unsplash.com/photo-1560250097-0b93528c311a?w=150&h=150&fit=crop&crop=face"
    },
    {
        name: "Maria Santos",
        title: "Wellness Coach",
        text: "The program's holistic approach to creativity and healing has opened new pathways for my clients' recovery and growth.",
        image: "https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&h=150&fit=crop&crop=face"
    },
    {
        name: "David Kim",
        title: "Gallery Owner",
        text: "This therapeutic arts initiative has created a ripple effect of positive change throughout our entire artistic community.",
        image: "https://images.unsplash.com/photo-1507591064344-4c6ce005b128?w=150&h=150&fit=crop&crop=face"
    },
    {
        name: "Elena Vasquez",
        title: "Art Student",
        text: "The program helped me discover not just my artistic voice, but also my path to emotional healing and self-discovery.",
        image: "https://images.unsplash.com/photo-1489424731084-a5d8b219a5bb?w=150&h=150&fit=crop&crop=face"
    },
    {
        name: "Robert Thompson",
        title: "Philanthropist",
        text: "Investing in this program was one of the most meaningful decisions I've made. The impact on participants is truly remarkable.",
        image: "https://images.unsplash.com/photo-1463453091185-61582044d556?w=150&h=150&fit=crop&crop=face"
    }
];

class testimonialsCarousel {
    constructor() {
        this.init();
    }

    init() {
        this.createCards();
    }

    createCards() {
        const track = document.getElementById('carouselTrack');
        
        // Add null check to prevent the error when the element doesn't exist
        if (!track) {
            return;
        }
        
        track.innerHTML = '';

        // Create multiple sets for seamless infinite scroll
        const sets = 4; // Create 4 sets of cards for smooth looping
        for (let set = 0; set < sets; set++) {
            testimonialss.forEach((testimonials, index) => {
                const card = document.createElement('div');
                card.className = 'testimonialsCardItem';
                card.setAttribute('tabindex', '0');
                card.innerHTML = `
                    <div class="testimonialsQuoteIcon">"</div>
                    <div class="testimonialsProfileSection">
                        <img src="${testimonials.image}" alt="${testimonials.name}" class="testimonialsProfileImage" 
                             onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMzAiIGN5PSIzMCIgcj0iMzAiIGZpbGw9IiNGOEY2RjMiLz4KPHN2ZyB4PSIxNSIgeT0iMTUiIHdpZHRoPSIzMCIgaGVpZ2h0PSIzMCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSIjOTk5Ij4KPHA+dGggZD0iTTEyIDEyYzIuMjEgMCA0LTEuNzkgNC00cy0xLjc5LTQtNC00LTQgMS43OS00IDQgMS43OSA0IDQgNHptMCAyYy0yLjY3IDAtOCAxLjM0LTggNHYyaDE2di0yYzAtMi42Ni01LjMzLTQtOC00eiIvPgo8L3N2Zz4KPC9zdmc+'">
                        <div class="testimonialsProfileInfo">
                            <div class="testimonialsProfileName">${testimonials.name}</div>
                            <div class="testimonialsProfileTitle">${testimonials.title}</div>
                        </div>
                    </div>
                    <div class="testimonialsTextContent">${testimonials.text}</div>
                    <div class="testimonialsStarRating">
                        <span class="testimonialsStar">★</span>
                        <span class="testimonialsStar">★</span>
                        <span class="testimonialsStar">★</span>
                        <span class="testimonialsStar">★</span>
                        <span class="testimonialsStar">★</span>
                    </div>
                `;
                track.appendChild(card);
            });
        }
    }
}

// Create global instance for testimonials carousel
let testimonialsCarouselInstance = null;

// Function to initialize testimonials carousel
function initializeTestimonialsCarousel() {
    const track = document.getElementById('carouselTrack');
    if (track && !testimonialsCarouselInstance) {
        testimonialsCarouselInstance = new testimonialsCarousel();
        return true;
    }
    return false;
}

// Initialize carousel when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Add a small delay to ensure all elements are rendered
    setTimeout(() => {
        initializeTestimonialsCarousel();
    }, 100);
});

// Also try to initialize when window loads as fallback
window.addEventListener('load', () => {
    if (!testimonialsCarouselInstance) {
        initializeTestimonialsCarousel();
    }
});

// Export for external use
window.initializeTestimonialsCarousel = initializeTestimonialsCarousel;



//scroll animations
// Scroll animation functionality
document.addEventListener('DOMContentLoaded', () => {
    // Get all elements to animate
    const animatedElements = document.querySelectorAll('.fade-in-left, .fade-in-right, .fade-in-up, .fade-in-down, .zoom-in, .zoom-out, .scroll-hidden');
    
    // Initial check for elements in viewport
    checkElementsInViewport();
    
    // Add scroll event listener
    window.addEventListener('scroll', checkElementsInViewport);
    
    // Function to check if elements are in viewport
    function checkElementsInViewport() {
        animatedElements.forEach(element => {
            const elementPosition = element.getBoundingClientRect();
            const offset = 50; // Offset to trigger animation earlier
            
            // Check if element is in viewport
            if (elementPosition.top <= window.innerHeight - offset && 
                elementPosition.bottom >= 0) {
                element.classList.add('scroll-visible');
            } else if (element.classList.contains('repeat-animation')) {
                // Only remove class if element is set to repeat animations when scrolling back out
                element.classList.remove('scroll-visible');
            }
        });
    }
    
    // Add resize event listener to ensure proper checking when window size changes
    window.addEventListener('resize', checkElementsInViewport);
});



//courses page 
// Course data
const courses = [
    {
        id: 1,
        title: "Digital Painting Masterclass",
        instructor: "Sarah Martinez",
        description: "Master advanced digital painting techniques with industry-standard tools.",
        price: 89,
        originalPrice: 129,
        duration: "12 weeks",
        students: 2847,
        rating: 4.9,
        category: "Digital Art",
        difficulty: "intermediate",
        tags: ["Photoshop", "Procreate", "Color Theory"],
        image: "https://images.pexels.com/photos/1053687/pexels-photo-1053687.jpeg?auto=compress&cs=tinysrgb&w=600"
    },
    {
        id: 2,
        title: "Watercolor Fundamentals",
        instructor: "Emma Thompson",
        description: "Explore traditional watercolor techniques with modern applications.",
        price: 65,
        originalPrice: 95,
        duration: "8 weeks",
        students: 1923,
        rating: 4.8,
        category: "Traditional Art",
        difficulty: "beginner",
        tags: ["Watercolor", "Landscapes", "Color Mixing"],
        image: "https://images.pexels.com/photos/1047540/pexels-photo-1047540.jpeg?auto=compress&cs=tinysrgb&w=600"
    },
    {
        id: 3,
        title: "Character Design Workshop",
        instructor: "Alex Rivera",
        description: "Design compelling characters for games and animation.",
        price: 120,
        originalPrice: 180,
        duration: "16 weeks",
        students: 3156,
        rating: 4.9,
        category: "Concept Art",
        difficulty: "advanced",
        tags: ["Character Design", "Anatomy", "Storytelling"],
        image: "https://images.pexels.com/photos/1646953/pexels-photo-1646953.jpeg?auto=compress&cs=tinysrgb&w=600"
    },
    {
        id: 4,
        title: "Abstract Art Exploration",
        instructor: "Marina Kowalski",
        description: "Discover your unique artistic voice through abstract expression.",
        price: 75,
        originalPrice: 110,
        duration: "10 weeks",
        students: 1567,
        rating: 4.7,
        category: "Fine Art",
        difficulty: "beginner",
        tags: ["Abstract", "Mixed Media", "Expression"],
        image: "https://images.pexels.com/photos/1183992/pexels-photo-1183992.jpeg?auto=compress&cs=tinysrgb&w=600"
    },
    {
        id: 5,
        title: "3D Sculpture Digital Art",
        instructor: "David Chen",
        description: "Create stunning 3D sculptures using industry-leading software.",
        price: 150,
        originalPrice: 220,
        duration: "20 weeks",  
        students: 2234,
        rating: 4.8,
        category: "3D Art",
        difficulty: "advanced",
        tags: ["ZBrush", "Blender", "3D Modeling"],
        image: "https://images.pexels.com/photos/1194420/pexels-photo-1194420.jpeg?auto=compress&cs=tinysrgb&w=600"
    },
    {
        id: 6,
        title: "Oil Painting Techniques",
        instructor: "Leonardo Rossi",
        description: "Master traditional oil painting with classical techniques.",
        price: 95,
        originalPrice: 140,
        duration: "14 weeks",
        students: 1789,
        rating: 4.9,
        category: "Traditional Art",
        difficulty: "intermediate",
        tags: ["Oil Painting", "Classical", "Portraits"],
        image: "https://images.pexels.com/photos/1266808/pexels-photo-1266808.jpeg?auto=compress&cs=tinysrgb&w=600"
    },
    {
        id: 7,
        title: "Portrait Drawing Mastery",
        instructor: "Isabella Rodriguez",
        description: "Create lifelike portraits with advanced drawing techniques.",
        price: 78,
        originalPrice: 115,
        duration: "11 weeks",
        students: 2156,
        rating: 4.8,
        category: "Drawing",
        difficulty: "intermediate",
        tags: ["Portraits", "Graphite", "Anatomy"],
        image: "https://images.pexels.com/photos/1646953/pexels-photo-1646953.jpeg?auto=compress&cs=tinysrgb&w=600"
    },
    {
        id: 8,
        title: "Ceramic Pottery Workshop",
        instructor: "James Wilson",
        description: "Learn the timeless craft of pottery and ceramics.",
        price: 110,
        originalPrice: 160,
        duration: "15 weeks",
        students: 1432,
        rating: 4.7,
        category: "Ceramics",
        difficulty: "beginner",
        tags: ["Pottery", "Ceramics", "Wheel Throwing"],
        image: "https://images.pexels.com/photos/1094767/pexels-photo-1094767.jpeg?auto=compress&cs=tinysrgb&w=600"
    },
    {
        id: 9,
        title: "Street Art & Murals",
        instructor: "Carlos Mendez",
        description: "Master urban art techniques and large-scale mural creation.",
        price: 85,
        originalPrice: 125,
        duration: "9 weeks",
        students: 1876,
        rating: 4.6,
        category: "Street Art",
        difficulty: "intermediate",
        tags: ["Murals", "Spray Paint", "Urban Art"],
        image: "https://images.pexels.com/photos/1646953/pexels-photo-1646953.jpeg?auto=compress&cs=tinysrgb&w=600"
    },
    {
        id: 10,
        title: "Fashion Illustration",
        instructor: "Sophie Laurent",
        description: "Develop your fashion illustration style with professional techniques.",
        price: 92,
        originalPrice: 135,
        duration: "13 weeks",
        students: 2089,
        rating: 4.8,
        category: "Fashion Art",
        difficulty: "intermediate",
        tags: ["Fashion", "Illustration", "Design"],
        image: "https://images.pexels.com/photos/1183992/pexels-photo-1183992.jpeg?auto=compress&cs=tinysrgb&w=600"
    },
    {
        id: 11,
        title: "Calligraphy & Hand Lettering",
        instructor: "Yuki Tanaka",
        description: "Perfect the art of beautiful lettering and calligraphy.",
        price: 68,
        originalPrice: 98,
        duration: "7 weeks",
        students: 1654,
        rating: 4.9,
        category: "Typography",
        difficulty: "beginner",
        tags: ["Calligraphy", "Lettering", "Typography"],
        image: "https://images.pexels.com/photos/1053687/pexels-photo-1053687.jpeg?auto=compress&cs=tinysrgb&w=600"
    },
    {
        id: 12,
        title: "Landscape Photography Art",
        instructor: "Michael Anderson",
        description: "Transform landscape photography into fine art.",
        price: 105,
        originalPrice: 155,
        duration: "12 weeks",
        students: 2345,
        rating: 4.7,
        category: "Photography",
        difficulty: "intermediate",
        tags: ["Photography", "Landscapes", "Composition"],
        image: "https://images.pexels.com/photos/1047540/pexels-photo-1047540.jpeg?auto=compress&cs=tinysrgb&w=600"
    }
];

let filteredCourses = [...courses];
let activeFilters = {};

// Create course card HTML - Matching Gallery Style
function createCourseCard(course) {
    // Generate stars based on rating
    const fullStars = Math.floor(course.rating);
    const hasHalfStar = course.rating % 1 >= 0.5;
    let starsHTML = '';
    
    for (let i = 0; i < fullStars; i++) {
        starsHTML += '<span class="star">★</span>';
    }
    
    if (hasHalfStar) {
        starsHTML += '<span class="star">☆</span>';
    }
    
    // Format the difficulty text with proper capitalization
    const difficultyText = course.difficulty.charAt(0).toUpperCase() + course.difficulty.slice(1);
    
    return `
        <div class="course-card" data-course-id="${course.id}" onclick="enrollCourse(${course.id})">
            <div class="course-image">
                <img src="${course.image}" alt="${course.title}" loading="lazy">
                
                <div class="course-rating">
                    <div class="stars-container">${starsHTML}</div>
                    <span class="rating-text">${course.rating.toFixed(1)}</span>
                </div>
                
                <div class="difficulty-badge difficulty-${course.difficulty}">
                    ${difficultyText}
                </div>
                
                <div class="course-partner">
                    YADAWITY PARTNER
                </div>
            </div>
            
            <div class="course-content">
                <h3 class="course-title">${course.title}</h3>
                <p class="course-instructor">${course.instructor}</p>
                <p class="course-category">${course.category}</p>
                
                <div class="course-meta">
                    <div class="course-duration">
                        <i class="fas fa-calendar-alt"></i>
                        ${course.duration}
                    </div>
                    <div class="course-students">
                        <i class="fas fa-users"></i>
                        ${course.students.toLocaleString()} students
                    </div>
                </div>
                
                <div class="course-price-info">
                    <div class="course-price">
                        <span class="price">$${course.price}</span>
                        ${course.originalPrice ? `<span class="original-price">$${course.originalPrice}</span>` : ''}
                    </div>
                </div>
                
                <button class="enroll-btn" onclick="event.stopPropagation(); enrollCourse(${course.id})">
                    Enroll Now
                </button>
            </div>
        </div>
    `;
}

// Enhanced filtering function
function applyFilters() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const category = document.getElementById('categoryFilter').value;
    const difficulty = document.getElementById('difficultyFilter').value;
    const minPrice = parseFloat(document.getElementById('minPrice').value) || 0;
    const maxPrice = parseFloat(document.getElementById('maxPrice').value) || Infinity;
    const duration = document.getElementById('durationFilter').value;

    // Update active filters
    activeFilters = {};
    if (searchTerm) activeFilters.search = searchTerm;
    if (category) activeFilters.category = category;
    if (difficulty) activeFilters.difficulty = difficulty;
    if (minPrice > 0) activeFilters.minPrice = minPrice;
    if (maxPrice < Infinity) activeFilters.maxPrice = maxPrice;
    if (duration) activeFilters.duration = duration;

    filteredCourses = courses.filter(course => {
        // Name/Search filter
        const matchesSearch = !searchTerm || 
            course.title.toLowerCase().includes(searchTerm) ||
            course.instructor.toLowerCase().includes(searchTerm) ||
            course.description.toLowerCase().includes(searchTerm) ||
            course.tags.some(tag => tag.toLowerCase().includes(searchTerm));

        // Category filter
        const matchesCategory = !category || course.category === category;

        // Difficulty filter
        const matchesDifficulty = !difficulty || course.difficulty === difficulty;

        // Price filter
        const matchesPrice = course.price >= minPrice && course.price <= maxPrice;

        // Duration filter
        const matchesDuration = !duration || 
            (duration === 'short' && parseInt(course.duration) <= 8) ||
            (duration === 'medium' && parseInt(course.duration) >= 9 && parseInt(course.duration) <= 15) ||
            (duration === 'long' && parseInt(course.duration) >= 16);

        return matchesSearch && matchesCategory && matchesDifficulty && matchesPrice && matchesDuration;
    });

    updateActiveFiltersDisplay();
    updateSearchResults();
    renderCourses();
}

// Update active filters display
function updateActiveFiltersDisplay() {
    const activeFiltersContainer = document.getElementById('activeFilters');
    activeFiltersContainer.innerHTML = '';

    Object.entries(activeFilters).forEach(([key, value]) => {
        const filterTag = document.createElement('div');
        filterTag.className = 'filter-tag';
        
        let displayText = '';
        switch(key) {
            case 'search':
                displayText = `Search: "${value}"`;
                break;
            case 'category':
                displayText = `Category: ${value}`;
                break;
            case 'difficulty':
                displayText = `Level: ${value}`;
                break;
            case 'minPrice':
                displayText = `Min: $${value}`;
                break;
            case 'maxPrice':
                displayText = `Max: $${value}`;
                break;
            case 'duration':
                displayText = `Duration: ${value}`;
                break;
        }

        filterTag.innerHTML = `
            ${displayText}
            <span class="remove-filter" onclick="removeFilter('${key}')">×</span>
        `;
        
        activeFiltersContainer.appendChild(filterTag);
    });
}

// Remove individual filter
function removeFilter(filterKey) {
    switch(filterKey) {
        case 'search':
            document.getElementById('searchInput').value = '';
            break;
        case 'category':
            document.getElementById('categoryFilter').value = '';
            break;
        case 'difficulty':
            document.getElementById('difficultyFilter').value = '';
            break;
        case 'minPrice':
            document.getElementById('minPrice').value = '';
            break;
        case 'maxPrice':
            document.getElementById('maxPrice').value = '';
            break;
        case 'duration':
            document.getElementById('durationFilter').value = '';
            break;
    }
    applyFilters();
}

// Clear all filters
function clearAllFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('difficultyFilter').value = '';
    document.getElementById('minPrice').value = '';
    document.getElementById('maxPrice').value = '';
    document.getElementById('durationFilter').value = '';
    applyFilters();
}

// Update search results display
function updateSearchResults() {
    const courseCount = document.getElementById('courseCount');
    
    if (!courseCount) return; // Exit if element doesn't exist
    
    const count = filteredCourses.length;
    const total = courses.length;
    
    if (Object.keys(activeFilters).length > 0) {
        const plural = count !== 1 ? 's' : '';
        courseCount.textContent = `Showing ${count} of ${total} course${plural}`;
    } else {
        courseCount.textContent = `Showing all ${total} courses`;
    }
}

// Render courses with improved performance using Intersection Observer
function renderCourses(coursesToRender = filteredCourses) {
    const coursesGrid = document.getElementById('coursesGrid');
    const noResults = document.getElementById('noResults');
    
    if (!coursesGrid) return; // Exit if element doesn't exist
    
    if (coursesToRender.length === 0) {
        coursesGrid.innerHTML = '';
        if (noResults) noResults.classList.add('show');
    } else {
        coursesGrid.innerHTML = coursesToRender.map(course => createCourseCard(course)).join('');
        if (noResults) noResults.classList.remove('show');
        
        // Use Intersection Observer to animate cards progressively
        initIntersectionObserver();
    }
}

// Initialize Intersection Observer for progressive loading
function initIntersectionObserver() {
    // Get all course cards that need to be animated
    const courseCards = document.querySelectorAll('.course-card:not(.visible)');
    
    // Options for the observer
    const options = {
        root: null, // viewport
        rootMargin: '0px',
        threshold: 0.15 // 15% of the item visible
    };
    
    // Create new observer
    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Add the visible class to trigger animation
                entry.target.classList.add('visible');
                // Stop observing this element
                observer.unobserve(entry.target);
            }
        });
    }, options);
    
    // Start observing each card
    courseCards.forEach((card, index) => {
        observer.observe(card);
    });
}



// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Initial render - only if on courses page
    if (document.getElementById('coursesGrid')) {
        renderCourses();
        updateSearchResults();
        
        // Search input event listener
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', applyFilters);
        }
    }
    
    // Filter event listeners - only add if elements exist
    const categoryFilter = document.getElementById('categoryFilter');
    const difficultyFilter = document.getElementById('difficultyFilter');
    const minPrice = document.getElementById('minPrice');
    const maxPrice = document.getElementById('maxPrice');
    const durationFilter = document.getElementById('durationFilter');
    
    if (categoryFilter) categoryFilter.addEventListener('change', applyFilters);
    if (difficultyFilter) difficultyFilter.addEventListener('change', applyFilters);
    if (minPrice) minPrice.addEventListener('input', applyFilters);
    if (maxPrice) maxPrice.addEventListener('input', applyFilters);
    if (durationFilter) durationFilter.addEventListener('change', applyFilters);
    
    // Min price arrows functionality
    const minArrowUp = document.querySelector('.min-arrow-up');
    const minArrowDown = document.querySelector('.min-arrow-down');
    const minPriceInput = document.getElementById('minPrice');
    
    if (minArrowUp && minPriceInput) {
        minArrowUp.addEventListener('click', function() {
            const currentValue = parseInt(minPriceInput.value) || 0;
            minPriceInput.value = currentValue + 10;
            applyFilters();
        });
    }
    
    if (minArrowDown && minPriceInput) {
        minArrowDown.addEventListener('click', function() {
            const currentValue = parseInt(minPriceInput.value) || 0;
            if (currentValue >= 10) {
                minPriceInput.value = currentValue - 10;
                applyFilters();
            }
        });
    }
    
    // Max price arrows functionality
    const maxArrowUp = document.querySelector('.max-arrow-up');
    const maxArrowDown = document.querySelector('.max-arrow-down');
    const maxPriceInput = document.getElementById('maxPrice');
    
    if (maxArrowUp && maxPriceInput) {
        maxArrowUp.addEventListener('click', function() {
            const currentValue = parseInt(maxPriceInput.value) || 0;
            maxPriceInput.value = currentValue + 10;
            applyFilters();
        });
    }
    
    if (maxArrowDown && maxPriceInput) {
        maxArrowDown.addEventListener('click', function() {
            const currentValue = parseInt(maxPriceInput.value) || 0;
            if (currentValue >= 10) {
                maxPriceInput.value = currentValue - 10;
                applyFilters();
            }
        });
    }
    
    // Clear search on escape key
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                clearAllFilters();
            }
        });
    }
});

// Smooth Scrolling
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      const targetId = this.getAttribute('href');
      
      // Skip invalid selectors (empty hash or just #)
      if (!targetId || targetId === '#' || targetId.length <= 1) {
        return;
      }
      
      e.preventDefault();
      const target = document.querySelector(targetId);
      if (target) {
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });

// Star Rating Script
document.querySelectorAll('.star-rating').forEach(ratingContainer => {
    const stars = ratingContainer.querySelectorAll('.star');
    
    stars.forEach((star, index) => {
      star.addEventListener('click', function() {
        const value = this.getAttribute('data-value');
        
        // Remove active class from all stars in this rating group
        stars.forEach(s => s.classList.remove('active'));
        
        // Add active class to clicked star and all previous stars
        for (let i = 0; i < value; i++) {
          stars[i].classList.add('active');
        }
      });

      // Hover effects
      star.addEventListener('mouseenter', function() {
        const value = this.getAttribute('data-value');
        for (let i = 0; i < value; i++) {
          stars[i].style.color = 'var(--gold-star)';
        }
      });

      star.addEventListener('mouseleave', function() {
        stars.forEach(s => {
          if (!s.classList.contains('active')) {
            s.style.color = 'var(--cream)';
          }
        });
      });
    });
  });

// Character Counter for Review Textarea
const reviewTextarea = document.getElementById('quickReview');
const characterCounter = document.getElementById('characterCounter');

if (reviewTextarea && characterCounter) {
    reviewTextarea.addEventListener('input', function() {
        const currentLength = this.value.length;
        const maxLength = this.getAttribute('maxlength');
        characterCounter.textContent = `${currentLength}/${maxLength}`;
    
        // Change color when approaching limit
        if (currentLength > maxLength * 0.8) {
            characterCounter.style.color = 'var(--red-accent)';
        } else {
            characterCounter.style.color = 'var(--text-light)';
        }
    });
}

// Submit Review Script
const submitReviewBtn = document.getElementById('submitReview');
if (submitReviewBtn) {
    submitReviewBtn.addEventListener('click', function () {
        const name = document.getElementById('clientName').value;
        const email = document.getElementById('clientEmail').value;
        const review = document.getElementById('reviewText').value;
        const artwork = document.getElementById('artworkTitle').value;
        const rating = document.getElementById('starRating').value;

    if (name && email && review && artwork) {
      // Here you can add the code to submit the review to the server

      // Reset the form
      document.querySelector('.feedback-form').reset();
      document.querySelectorAll('.star-rating .star').forEach(s => s.classList.remove('active'));
    } else {
      alert('Please fill in all fields and select a rating.');
    }
  });
}

// Submit Quick Review Script
const submitQuickReviewBtn = document.getElementById('submitQuickReview');
if (submitQuickReviewBtn) {
    submitQuickReviewBtn.addEventListener('click', function () {
        const artworkPurchased = document.getElementById('purchasedArtwork').value;
        const reviewText = document.getElementById('quickReview').value;
    
    // Get overall rating
    const overallStars = document.querySelectorAll('div[data-type="overall"] .star.active');
    const overallRating = overallStars.length;
    
    if (overallRating > 0 && reviewText.trim() && artworkPurchased) {
      const reviewData = {
        artworkPurchased: artworkPurchased,
        overallRating: overallRating,
        reviewText: reviewText,
        timestamp: new Date().toISOString()
      };

      alert('Thank you for your review! Your feedback has been submitted.');

      // Reset the form
      document.getElementById('quickReview').value = '';
      document.getElementById('purchasedArtwork').selectedIndex = 0;
      document.querySelectorAll('.star-rating .star').forEach(s => {
        s.classList.remove('active');
        s.style.color = '#e0e0e0';
      });
      
      // Reset character counter
      const characterCounter = document.getElementById('characterCounter');
      if (characterCounter) {
          characterCounter.textContent = '0/500';
          characterCounter.style.color = 'var(--text-light)';
      }
    } else {
      alert('Please select an artwork, choose a rating, and write your review.');
    }
  });
}

// Enhanced Artwork Cards Functionality
document.querySelectorAll('.enhanced-add-to-cart').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const card = this.closest('.enhanced-artwork-card');
        const artworkId = this.getAttribute('data-id');
        const title = card.querySelector('.enhanced-artwork-title').textContent;
        const price = card.querySelector('.enhanced-artwork-price').textContent;
        
        // Add to cart animation
        this.textContent = 'Added!';
        this.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
        this.style.transform = 'scale(0.95)';
        
        // Reset button after animation
        setTimeout(() => {
            this.textContent = 'Add to Cart';
            this.style.background = 'linear-gradient(135deg, var(--brown-medium) 0%, var(--brown-light) 100%)';
            this.style.transform = 'scale(1)';
        }, 2000);
        
        // Update cart count
        updateCartCount();
    });
});

// Enhanced Wishlist Functionality
document.querySelectorAll('.wishlist-btn').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const artworkId = this.getAttribute('data-id');
        const card = this.closest('.enhanced-artwork-card');
        const title = card.querySelector('.enhanced-artwork-title').textContent;
        
        // Toggle wishlist state
        this.classList.toggle('active');
        
        if (this.classList.contains('active')) {
            this.innerHTML = '<i class="fas fa-heart"></i>';
            this.style.animation = 'heartBeat 0.6s ease-in-out';
        } else {
            this.innerHTML = '<i class="far fa-heart"></i>';
        }
        
        // Reset animation
        setTimeout(() => {
            this.style.animation = '';
        }, 600);
        
        updateWishlistCount();
    });
});

// Enhanced Card Quick Actions
document.querySelectorAll('.quick-action-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const action = this.dataset.action;
        const artworkId = this.dataset.id;
        
        if(action === 'view') {
            openQuickView(artworkId, this.closest('.enhanced-artwork-card'));
        }
    });
});

// Enhanced Card Hover Effects
document.querySelectorAll('.enhanced-artwork-card').forEach(card => {
    let hoverTimeout;
    
    card.addEventListener('mouseenter', function() {
        clearTimeout(hoverTimeout);
        
        // Smooth scale and lift animation
        this.style.transform = 'translateY(-8px)';
        this.style.boxShadow = '0 12px 35px rgba(0, 0, 0, 0.15)';
        
        // Animate image
        const image = this.querySelector('.enhanced-artwork-image');
        if (image) {
            image.style.transform = 'scale(1.08)';
        }
        
        // Show overlay with delay
        const overlay = this.querySelector('.artwork-overlay');
        if (overlay) {
            hoverTimeout = setTimeout(() => {
                overlay.style.opacity = '1';
            }, 100);
        }
    });
    
    card.addEventListener('mouseleave', function() {
        clearTimeout(hoverTimeout);
        
        // Reset transforms
        this.style.transform = 'translateY(0)';
        this.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.1)';
        
        // Reset image
        const image = this.querySelector('.enhanced-artwork-image');
        if (image) {
            image.style.transform = 'scale(1)';
        }
        
        // Hide overlay
        const overlay = this.querySelector('.artwork-overlay');
        if (overlay) {
            overlay.style.opacity = '0';
        }
    });
});

// Utility Functions for Enhanced Cards
function openQuickView(artworkId, card) {
    const title = card.querySelector('.enhanced-artwork-title').textContent;
    const artist = card.querySelector('.enhanced-artwork-artist').textContent;
    const price = card.querySelector('.enhanced-artwork-price').textContent;
    const image = card.querySelector('.enhanced-artwork-image').src;
    const description = card.querySelector('.enhanced-artwork-description').textContent;
    
    // Create quick view modal (you can implement a proper modal here)
    
    // For now, just scroll to the artwork details or open in new tab
    // You can implement a proper modal overlay here
}