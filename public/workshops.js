
// Global variables
let galleries = [];
let filteredGalleries = [];
let activeFilters = {};
let currentPage = 1;
let galleriesPerPage = 6; // Show 6 galleries per page instead of all 12
let totalPages = 1;

// DOM elements
const searchInput = document.getElementById("searchInput");
const doctorFilter = document.getElementById("doctorFilter");
const categoryFilter = document.getElementById("categoryFilter");
const cityFilter = document.getElementById("cityFilter");
const streetFilter = document.getElementById("streetFilter");
const minPriceInput = document.getElementById("minPrice");
const maxPriceInput = document.getElementById("maxPrice");
const dateFilter = document.getElementById("dateFilter");
const timeFilter = document.getElementById("timeFilter");
const activeFiltersContainer = document.getElementById("activeFilters");
const searchResults = document.getElementById("searchResults");
const coursesGrid = document.getElementById("coursesGrid");
const courseCount = document.getElementById("courseCount");
const noResults = document.getElementById("noResults");

// Fetch data and initialize
document.addEventListener("DOMContentLoaded", () => {
  // rendering confirmed; no test card injected
  fetch("./API/getWorkshops.php")
    .then((res) => res.json())
    .then((data) => {
      console.log('Fetched workshops data:', data);
      if (data.success && Array.isArray(data.data)) {
        galleries = data.data;
        filteredGalleries = [...galleries];
        totalPages = Math.ceil(galleries.length / galleriesPerPage);
        populateWorkshopFilters(galleries);
        renderGalleries(galleries);
        updatePaginationControls();
        setupEventListeners();
        setupNavigation();
      } else {
        coursesGrid.innerHTML = '<div style="color:red">Failed to load workshops.</div>';
      }
    })
    .catch((err) => {
      console.error('Error fetching workshops:', err);
      coursesGrid.innerHTML = '<div style="color:red">Error loading workshops.</div>';
    });
});

// Populate filter dropdowns dynamically from workshops data
function populateWorkshopFilters(workshops) {
  // Doctor
  if (doctorFilter) {
    const doctors = Array.from(new Set(workshops.map(w => w.doctor_name).filter(Boolean)));
    doctorFilter.innerHTML = '<option value="">All Doctors</option>' + doctors.map(d => `<option value="${d}">${d}</option>`).join('');
  }
  // Category
  if (categoryFilter) {
    const categories = Array.from(new Set(workshops.map(w => w.category).filter(Boolean)));
    categoryFilter.innerHTML = '<option value="">All Categories</option>' + categories.map(c => `<option value="${c}">${c}</option>`).join('');
  }
  // City
  if (cityFilter) {
    const cities = Array.from(new Set(workshops.map(w => w.city).filter(Boolean)));
    cityFilter.innerHTML = '<option value="">All Cities</option>' + cities.map(c => `<option value="${c}">${c}</option>`).join('');
  }
  // Street
  if (streetFilter) {
    const streets = Array.from(new Set(workshops.map(w => w.street).filter(Boolean)));
    streetFilter.innerHTML = '<option value="">All Streets</option>' + streets.map(s => `<option value="${s}">${s}</option>`).join('');
  }
}

// Global variables
// ...existing code...


// Setup event listeners
function setupEventListeners() {
  // Search input
  searchInput.addEventListener("input", debounce(applyFilters, 300))

  // Filter dropdowns and inputs
  if (doctorFilter) doctorFilter.addEventListener("change", applyFilters)
  if (categoryFilter) categoryFilter.addEventListener("change", applyFilters)
  if (cityFilter) cityFilter.addEventListener("change", applyFilters)
  if (streetFilter) streetFilter.addEventListener("change", applyFilters)
  if (minPriceInput) minPriceInput.addEventListener("input", debounce(applyFilters, 300))
  if (maxPriceInput) maxPriceInput.addEventListener("input", debounce(applyFilters, 300))
  if (dateFilter) dateFilter.addEventListener("change", applyFilters)
  if (timeFilter) timeFilter.addEventListener("change", applyFilters)
}

// Setup navigation functionality
function setupNavigation() {
  const navToggle = document.getElementById("navToggle")
  const navMenu = document.getElementById("navMenu")
  const userAccount = document.getElementById("userAccount")
  const userMenu = document.getElementById("userMenu")

  // Mobile menu toggle
  if (navToggle && navMenu) {
    navToggle.addEventListener("click", () => {
      navMenu.classList.toggle("active")
    })
  }

  // User dropdown
  if (userAccount && userMenu) {
    userAccount.addEventListener("click", (e) => {
      e.preventDefault()
      userMenu.classList.toggle("active")
    })

    // Close dropdown when clicking outside
    document.addEventListener("click", (e) => {
      if (!userAccount.contains(e.target) && !userMenu.contains(e.target)) {
        userMenu.classList.remove("active")
      }
    })
  }

  // Navbar search
  const navbarSearch = document.getElementById("navbarSearch")
  const searchButton = document.getElementById("searchButton")

  if (searchButton) {
    searchButton.addEventListener("click", () => {
      const query = navbarSearch.value.trim()
      if (query) {
        searchInput.value = query
        applyFilters()
      }
    })
  }

  if (navbarSearch) {
    navbarSearch.addEventListener("keypress", (e) => {
      if (e.key === "Enter") {
        const query = navbarSearch.value.trim()
        if (query) {
          searchInput.value = query
          applyFilters()
        }
      }
    })
  }
}

// Apply filters function
function applyFilters() {

  const searchTerm = searchInput.value.toLowerCase().trim()
  const selectedDoctor = doctorFilter ? doctorFilter.value : ""
  const selectedCategory = categoryFilter ? categoryFilter.value : ""
  const selectedCity = cityFilter ? cityFilter.value : ""
  const selectedStreet = streetFilter ? streetFilter.value : ""
  const minPrice = minPriceInput ? Number.parseFloat(minPriceInput.value) || 0 : 0
  const maxPrice = maxPriceInput ? Number.parseFloat(maxPriceInput.value) || Number.POSITIVE_INFINITY : Number.POSITIVE_INFINITY
  const selectedDate = dateFilter ? dateFilter.value : ""
  const selectedTime = timeFilter ? timeFilter.value : ""

  // Reset active filters
  activeFilters = {}

  // Filter workshops using correct fields from API
  filteredGalleries = galleries.filter((workshop) => {
    let matches = true;

    // Search term filter
    if (searchTerm) {
      const searchableText =
        `${workshop.title} ${workshop.doctor_name} ${workshop.city} ${workshop.street} ${workshop.workshop_description}`.toLowerCase();
      matches = matches && searchableText.includes(searchTerm);
      if (searchTerm) activeFilters.search = searchTerm;
    }

    // Doctor filter
    if (selectedDoctor) {
      matches = matches && workshop.doctor_name === selectedDoctor;
      activeFilters.doctor = selectedDoctor;
    }

    // Category filter
    if (selectedCategory) {
      matches = matches && workshop.category === selectedCategory;
      activeFilters.category = selectedCategory;
    }

    // City filter
    if (selectedCity) {
      matches = matches && workshop.city === selectedCity;
      activeFilters.city = selectedCity;
    }

    // Street filter
    if (selectedStreet) {
      matches = matches && workshop.street === selectedStreet;
      activeFilters.street = selectedStreet;
    }

    // Price filter
    if (minPrice > 0 || maxPrice < Number.POSITIVE_INFINITY) {
      matches = matches && workshop.price >= minPrice && workshop.price <= maxPrice;
      activeFilters.price = `$${minPrice} - $${maxPrice === Number.POSITIVE_INFINITY ? "∞" : maxPrice}`;
    }

    // Date filter
    if (selectedDate) {
      matches = matches && workshop.date === selectedDate;
      activeFilters.date = selectedDate;
    }

    // Time filter
    if (selectedTime) {
      matches = matches && workshop.open_time === selectedTime;
      activeFilters.time = selectedTime;
    }

    return matches;
  });

  // Reset to first page when filters change
  currentPage = 1;

  // Update UI
  renderActiveFilters();
  renderGalleries(filteredGalleries);
  updateSearchResults();
  updatePaginationControls();

  // Show/hide no results
  const paginationSection = document.querySelector(".pagination-section");
  if (filteredGalleries.length === 0) {
    coursesGrid.innerHTML = '';
    coursesGrid.style.display = "none";
    noResults.style.display = "block";
    if (paginationSection) paginationSection.style.display = "none";
  } else {
    coursesGrid.style.display = "grid";
    noResults.style.display = "none";
    if (paginationSection) paginationSection.style.display = "block";
  }
}

// Render active filters
function renderActiveFilters() {
  activeFiltersContainer.innerHTML = ""

  Object.entries(activeFilters).forEach(([key, value]) => {
    const filterTag = document.createElement("div")
    filterTag.className = "filter-tag"

    let displayValue = value
    if (key === "search") {
      displayValue = `Search: ${value}`
    } else {
      displayValue = `${key.charAt(0).toUpperCase() + key.slice(1)}: ${value}`
    }

    filterTag.innerHTML = `
      <span>${displayValue}</span>
      <span class="remove-filter" onclick="removeFilter('${key}')">×</span>
    `

    activeFiltersContainer.appendChild(filterTag)
  })
}

// Remove individual filter
function removeFilter(filterKey) {
  switch (filterKey) {
    case "search":
      searchInput.value = ""
      break
    case "category":
      categoryFilter.value = ""
      break
    case "difficulty":
      difficultyFilter.value = ""
      break
    case "duration":
      durationFilter.value = ""
      break
    case "price":
      minPriceInput.value = ""
      maxPriceInput.value = ""
      break
  }
  applyFilters()
}

// Clear all filters
function clearAllFilters() {
  searchInput.value = ""
  categoryFilter.value = ""
  difficultyFilter.value = ""
  durationFilter.value = ""
  minPriceInput.value = ""
  maxPriceInput.value = ""

  activeFilters = {}
  filteredGalleries = [...galleries]
  currentPage = 1

  renderActiveFilters();
  renderGalleries(galleries);
  updateSearchResults();
  updatePaginationControls();
  coursesGrid.style.display = "grid";
  noResults.style.display = "none";
  const paginationSection = document.querySelector(".pagination-section");
  if (paginationSection) paginationSection.style.display = "block";
}

// Render courses
function renderGalleries(galleriesToRender) {
  console.log('Rendering galleries:', galleriesToRender);
  coursesGrid.innerHTML = "";

  // Pagination logic
  const startIndex = (currentPage - 1) * galleriesPerPage;
  const endIndex = startIndex + galleriesPerPage;
  const paginatedGalleries = galleriesToRender.slice(startIndex, endIndex);

  if (paginatedGalleries.length === 0) {
    coursesGrid.innerHTML = '<div style="color: #fff; text-align: center; padding: 2rem;">No workshops available.</div>';
    return;
  }

  paginatedGalleries.forEach((workshop) => {
    const galleryCard = document.createElement("div");
    galleryCard.className = "course-card";

    // Use default placeholder image for workshops
  const imageSrc = '/image/placeholder-artwork.jpg';
  const doctorPhoto = workshop.doctor_photo ? `/image/${workshop.doctor_photo}` : '/image/placeholder-artwork.jpg';
    const priceHTML = workshop.price !== undefined ? `$${workshop.price}` : '';

    galleryCard.innerHTML = `
  <!-- Image removed as requested -->
      <div class="course-content">
        <h3 class="course-title">${workshop.title}</h3>
        <div class="course-instructor"><b>Doctor:</b> ${workshop.doctor_name || ''}</div>
        <div class="course-category"><b>Category:</b> ${workshop.category || ''}</div>
        <div class="course-location">
          <span><i class='fas fa-map-marker-alt'></i> <b>City:</b> ${workshop.city || ''}</span>
          <span><i class='fas fa-road'></i> <b>Street:</b> ${workshop.street || ''}</span>
        </div>
        <div class="course-datetime">
          <span><i class='fas fa-calendar-alt'></i> <b>Date:</b> ${workshop.date || ''}</span>
          <span><i class='fas fa-clock'></i> <b>Time:</b> ${workshop.open_time || ''}</span>
        </div>
        <div class="course-meta">
          <div class="course-students"><i class="fas fa-users"></i> <b>Capacity:</b> ${workshop.capacity}</div>
        </div>
        <div class="course-description">${workshop.workshop_description || ''}</div>
  <div class="course-price-info">
          <div class="course-price"><span class="price">${priceHTML}</span></div>
        </div>
        <button class="enroll-btn" onclick="bookGallery(${workshop.id})">
          <i class="fas fa-calendar-check"></i> Book Visit
        </button>
      </div>
    `;

    coursesGrid.appendChild(galleryCard);
  });

  // Add quick view event listeners
  document.querySelectorAll('.quick-action-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const galleryCard = e.target.closest('.course-card');
      const galleryId = parseInt(galleryCard.querySelector('.enroll-btn').getAttribute('onclick').match(/\d+/)[0]);
      openQuickView(galleryId);
    });
  });

  // Update pagination info
  totalPages = Math.ceil(galleriesToRender.length / galleriesPerPage);
  updatePaginationInfo();
}

// Helper functions for display formatting
function formatDateDisplay(date) {
  const dateMap = {
    'today': 'Today',
    'tomorrow': 'Tomorrow',
    'this-week': 'This Week',
    'next-week': 'Next Week',
    'this-weekend': 'This Weekend',
    'next-weekend': 'Next Weekend',
    'this-month': 'This Month',
    'next-month': 'Next Month'
  }
  return dateMap[date] || date
}

function formatTimeDisplay(timeRange) {
  const timeMap = {
    'morning': 'Morning',
    'afternoon': 'Afternoon',
    'evening': 'Evening',
    'night': 'Night',
    'early-morning': 'Early Morning',
    'late-night': 'Late Night',
    'business-hours': 'Business Hours',
    'extended-hours': 'Extended Hours'
  }
  return timeMap[timeRange] || timeRange
}

// Open quick view modal
function openQuickView(galleryId) {
  const gallery = galleries.find(g => g.id === galleryId);
  if (!gallery) return;
  
  // Create modal overlay
  const overlay = document.createElement('div');
  overlay.className = 'quick-view-overlay';
  overlay.innerHTML = `
    <div class="quick-view-modal">
      <div class="quick-view-content">
        <img src="/image/placeholder-artwork.jpg" onerror="this.onerror=null;this.src='/image/placeholder-artwork.jpg'" alt="${gallery.title}" class="quick-view-image">
        <div class="quick-view-details">
          <h2>${gallery.title}</h2>
          <p class="instructor">Doctor: ${gallery.doctor_name || ''}</p>
          <p class="description">${gallery.workshop_description || ''}</p>
          <div class="meta-info">
            <span><i class="fas fa-map-marker-alt"></i> ${gallery.city || ''} ${gallery.street || ''}</span>
            <span><i class="fas fa-clock"></i> ${gallery.open_time || ''}</span>
            <span><i class="fas fa-calendar"></i> ${formatDateDisplay(gallery.date)}</span>
            <span><i class="fas fa-users"></i> ${gallery.capacity} capacity</span>
          </div>
          <div class="price-info">
            <span class="price">$${course.price}</span>
            ${course.originalPrice ? `<span class="original-price">$${course.originalPrice}</span>` : ''}
          </div>
          <button class="enroll-btn" onclick="enrollCourse(${course.id})">
            <i class="fas fa-graduation-cap"></i> Enroll Now
          </button>
        </div>
        <button class="close-modal"><i class="fas fa-times"></i></button>
      </div>
    </div>
  `;

  // Add close functionality
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay || e.target.closest('.close-modal')) {
      document.body.removeChild(overlay);
    }
  });

  // Add escape key to close
  const handleEscape = (e) => {
    if (e.key === 'Escape') {
      document.body.removeChild(overlay);
      document.removeEventListener('keydown', handleEscape);
    }
  };
  document.addEventListener('keydown', handleEscape);

  document.body.appendChild(overlay);
}

// Update search results text
function updateSearchResults() {
  if (Object.keys(activeFilters).length > 0) {
    const filterCount = Object.keys(activeFilters).length
    const resultCount = filteredGalleries.length
    searchResults.innerHTML = `Found ${resultCount} galleries with ${filterCount} active filter${filterCount > 1 ? "s" : ""}`
    searchResults.style.display = "block"
  } else {
    searchResults.style.display = "none"
  }
}

// Update gallery count
function updateGalleryCount(count) {
  if (count === galleries.length) {
    courseCount.textContent = `Showing all ${count} galleries`
  } else {
    courseCount.textContent = `Showing ${count} of ${galleries.length} galleries`
  }
}

// Update pagination info
function updatePaginationInfo() {
  const paginationInfo = document.getElementById("paginationInfo")
  if (paginationInfo) {
    const startGallery = (currentPage - 1) * galleriesPerPage + 1
    const endGallery = Math.min(currentPage * galleriesPerPage, filteredGalleries.length)
    paginationInfo.textContent = `Showing ${startGallery} - ${endGallery} of ${filteredGalleries.length} galleries`
  }
}

// Pagination functions
function previousPage() {
  if (currentPage > 1) {
    currentPage--
    renderGalleries(filteredGalleries)
    updatePaginationControls()
    scrollToTop()
  }
}

function nextPage() {
  if (currentPage < totalPages) {
    currentPage++
    renderGalleries(filteredGalleries)
    updatePaginationControls()
    scrollToTop()
  }
}

function goToPage(page) {
  if (page >= 1 && page <= totalPages) {
    currentPage = page
    renderGalleries(filteredGalleries)
    updatePaginationControls()
    scrollToTop()
  }
}

function updatePaginationControls() {
  const prevBtn = document.getElementById("prevBtn")
  const nextBtn = document.getElementById("nextBtn")
  const paginationNumbers = document.getElementById("paginationNumbers")

  // Update previous button
  if (prevBtn) {
    prevBtn.disabled = currentPage === 1
  }

  // Update next button
  if (nextBtn) {
    nextBtn.disabled = currentPage === totalPages
  }

  // Update pagination numbers
  if (paginationNumbers) {
    paginationNumbers.innerHTML = ""

    if (totalPages <= 1) {
      // Hide pagination if only one page
      const paginationSection = document.querySelector(".pagination-section")
      if (paginationSection) {
        paginationSection.style.display = "none"
      }
      return
    } else {
      // Show pagination if more than one page
      const paginationSection = document.querySelector(".pagination-section")
      if (paginationSection) {
        paginationSection.style.display = "block"
      }
    }

    const maxVisiblePages = 5
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2))
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1)

    // Adjust start page if we're near the end
    if (endPage - startPage < maxVisiblePages - 1) {
      startPage = Math.max(1, endPage - maxVisiblePages + 1)
    }

    // Add first page and dots if needed
    if (startPage > 1) {
      const firstPageBtn = document.createElement("button")
      firstPageBtn.className = "pagination-number"
      firstPageBtn.textContent = "1"
      firstPageBtn.onclick = () => goToPage(1)
      paginationNumbers.appendChild(firstPageBtn)

      if (startPage > 2) {
        const dots = document.createElement("span")
        dots.className = "pagination-dots"
        dots.textContent = "..."
        paginationNumbers.appendChild(dots)
      }
    }

    // Add visible page numbers
    for (let i = startPage; i <= endPage; i++) {
      const pageBtn = document.createElement("button")
      pageBtn.className = `pagination-number ${i === currentPage ? "active" : ""}`
      pageBtn.textContent = i
      pageBtn.onclick = () => goToPage(i)
      paginationNumbers.appendChild(pageBtn)
    }

    // Add last page and dots if needed
    if (endPage < totalPages) {
      if (endPage < totalPages - 1) {
        const dots = document.createElement("span")
        dots.className = "pagination-dots"
        dots.textContent = "..."
        paginationNumbers.appendChild(dots)
      }

      const lastPageBtn = document.createElement("button")
      lastPageBtn.className = "pagination-number"
      lastPageBtn.textContent = totalPages
      lastPageBtn.onclick = () => goToPage(totalPages)
      paginationNumbers.appendChild(lastPageBtn)
    }
  }

  // Update gallery count with pagination info
  updateGalleryCountWithPagination()
}

function updateGalleryCountWithPagination() {
  const startGallery = (currentPage - 1) * galleriesPerPage + 1
  const endGallery = Math.min(currentPage * galleriesPerPage, filteredGalleries.length)
  
  if (filteredGalleries.length === 0) {
    courseCount.textContent = "No galleries found"
  } else if (filteredGalleries.length <= galleriesPerPage) {
    courseCount.textContent = `Showing ${filteredGalleries.length} galler${filteredGalleries.length === 1 ? 'y' : 'ies'}`
  } else {
    courseCount.textContent = `Showing ${startGallery}-${endGallery} of ${filteredGalleries.length} galleries`
  }
}

function scrollToTop() {
  window.scrollTo({
    top: 0,
    behavior: 'smooth'
  })
}

// Book gallery function

function bookGallery(galleryId) {
  // Store current workshop ID for cart functionality
  currentWorkshopId = galleryId;
  
  // Find the workshop data by ID
  const workshop = galleries.find(w => w.workshop_id == galleryId || w.id == galleryId);
  
  if (!workshop) {
    console.error('Workshop not found:', galleryId);
    return;
  }
  
  // Populate modal with workshop data
  document.getElementById('workshopTitle').textContent = workshop.title || 'Workshop';
  
  // Use different doctor photos based on workshop ID for variety
  const doctorPhotos = [
    'shutterstock_2480850611.jpg',
    'portrait-of-a-happy-young-doctor-in-his-clinic-royalty-free-image-1661432441.avif',
    'istock-482499394.webp',
    'gallery_68a26027b3429_1755471911.png'
  ];
  
  const photoIndex = galleryId % doctorPhotos.length;
  document.getElementById('doctorPhoto').src = `/uploads/doctors_photo/${doctorPhotos[photoIndex]}`;
  document.getElementById('doctorName').textContent = workshop.doctor_name || 'N/A';
  document.getElementById('doctorGrade').textContent = 'Consultant Psychiatrist'; // Default grade
  document.getElementById('doctorExperience').textContent = workshop.doctor_description || '15 years in art therapy and mental health.\n\nHead of the National Art Therapy Association.\n\nAuthor of "Healing Through Art: A Modern Approach".';
  
  // Workshop details
  document.getElementById('workshopCategory').textContent = workshop.category || 'N/A';
  document.getElementById('workshopDate').textContent = workshop.date || 'N/A';
  document.getElementById('workshopTime').textContent = workshop.open_time || 'N/A';
  document.getElementById('workshopCapacity').textContent = workshop.capacity ? `${workshop.capacity} people` : 'N/A';
  document.getElementById('workshopLocation').textContent = workshop.city && workshop.street ? `${workshop.city}, ${workshop.street}` : workshop.city || workshop.street || 'N/A';
  document.getElementById('workshopPrice').textContent = workshop.price ? `$${workshop.price}` : 'Free';
  document.getElementById('workshopDescription').textContent = workshop.workshop_description || 'No description available.';

  // Show modal
  const modal = document.getElementById('doctorInfoModal');
  if (modal) {
    modal.style.display = 'flex';
  }
}

window.bookGallery = bookGallery;

// Global variable to store current workshop ID for modal
let currentWorkshopId = null;

// Add workshop to cart function
async function addWorkshopToCart() {
  if (!currentWorkshopId) {
    Swal.fire({
      icon: 'warning',
      title: 'No Workshop Selected',
      text: 'Please select a workshop first.',
      confirmButtonColor: '#6b4423'
    });
    return;
  }

  try {
    const response = await fetch('./API/addToCart.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'include',
      body: JSON.stringify({
        product_id: currentWorkshopId,
        quantity: 1,
        type: 'workshop'
      })
    });

    const data = await response.json();

    if (data.success) {
      // Show success message
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: 'Workshop added to cart successfully!',
        confirmButtonColor: '#6b4423',
        timer: 2000,
        showConfirmButton: false
      });
      
      // Close modal
      closeDoctorModal();
      
      // Optional: Update cart count if you have a cart counter
      updateCartCount();
    } else {
      // Handle specific error messages
      console.error('Error response from API:', data);
      
      if (data.message && data.message.includes('log in')) {
        Swal.fire({
          icon: 'warning',
          title: 'Login Required',
          text: 'Please log in to add workshops to cart',
          confirmButtonColor: '#6b4423',
          confirmButtonText: 'Go to Login'
        }).then((result) => {
          if (result.isConfirmed) {
            window.location.href = 'login.php';
          }
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: data.message || 'Failed to add workshop to cart',
          confirmButtonColor: '#6b4423'
        });
      }
    }
  } catch (error) {
    console.error('Error adding workshop to cart:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Network error: ' + error.message,
      confirmButtonColor: '#6b4423'
    });
  }
}

// Update cart count function (optional)
async function updateCartCount() {
  try {
    const response = await fetch('./API/getCart.php', {
      credentials: 'include'
    });
    const data = await response.json();
    
    if (data.success && data.data) {
      const cartCount = data.data.items ? data.data.items.length : 0;
      // Update cart count in UI if you have a cart counter element
      const cartCountElement = document.querySelector('.cart-count');
      if (cartCountElement) {
        cartCountElement.textContent = cartCount;
      }
    }
  } catch (error) {
    console.error('Error updating cart count:', error);
  }
}

window.addWorkshopToCart = addWorkshopToCart;

function closeDoctorModal() {
  document.getElementById('doctorInfoModal').style.display = 'none';
}

window.closeDoctorModal = closeDoctorModal;

// Debounce function for search input
function debounce(func, wait) {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}

// Add keyboard navigation support
document.addEventListener("keydown", (e) => {
  // Escape key to clear search
  if (e.key === "Escape") {
    if (searchInput.value || Object.keys(activeFilters).length > 0) {
      clearAllFilters()
    }
  }

  // Enter key on search input
  if (e.key === "Enter" && e.target === searchInput) {
    applyFilters()
  }
})

// Initialize cart and wishlist counters
function updateCartCount() {
  const cartCount = document.getElementById("cartCount")
  const count = 0 // This would typically get the count from localStorage or a backend

  if (cartCount) cartCount.textContent = count
}

function updateWishlistCount() {
  const wishlistCount = document.getElementById("wishlistCount")
  const count = 0 // This would typically get the count from localStorage or a backend

  if (wishlistCount) {
    wishlistCount.textContent = count
    wishlistCount.style.display = count > 0 ? "block" : "none"
  }
}

// Handle window resize for responsive behavior
window.addEventListener(
  "resize",
  debounce(() => {
    // Recalculate grid layout if needed
    const grid = document.getElementById("coursesGrid")
    if (grid) {
      grid.style.display = "none"
      setTimeout(() => {
        grid.style.display = "grid"
      }, 10)
    }
  }, 250),
)

// Initialize counters
updateCartCount()
updateWishlistCount()

// Export functions for global access
window.applyFilters = applyFilters
window.clearAllFilters = clearAllFilters
window.removeFilter = removeFilter
window.bookGallery = bookGallery
window.previousPage = previousPage
window.nextPage = nextPage
window.goToPage = goToPage