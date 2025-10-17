// Live galleries loaded from API. Keep an empty array as default/fallback.
let galleries = []
// Global variables
let filteredGalleries = []
let activeFilters = {}
let currentPage = 1
let galleriesPerPage = 6 // Show 6 galleries per page
let totalPages = 1

// API endpoint for local galleries
const LOCAL_GALLERY_API = './API/getAllLocalGallery.php'

// Fetch galleries from backend and normalize shape for the UI
async function fetchGalleriesFromAPI() {
  try {
    const res = await fetch(LOCAL_GALLERY_API, { cache: 'no-cache' })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    const payload = await res.json()
    if (!payload || !payload.success || !Array.isArray(payload.data)) {
      console.warn('Unexpected API response', payload)
      return []
    }

    // Normalize each gallery to the shape expected by renderGalleries
    const normalized = payload.data.map(normalizeGalleryFromAPI)
    return normalized
  } catch (err) {
    console.error('Failed to load galleries from API', err)
    return []
  }
}

function normalizeGalleryFromAPI(item) {
  // item contains fields like gallery_id, title, description, city, address, start_date, duration, is_currently_active, time_remaining_minutes, artist{...}
  const id = item.gallery_id || item.galleryId || null
  const artistName = item.artist && item.artist.name ? item.artist.name : ((item.artist && item.artist.first_name) ? `${item.artist.first_name} ${item.artist.last_name || ''}`.trim() : 'Unknown Artist')
  const city = item.city || item.address || ''
  const startDate = item.start_date || null
  const duration = Number.isFinite(Number(item.duration)) ? Number(item.duration) : null

  // derive end time and openHours display
  let openHours = ''
  if (startDate && duration) {
    try {
      const start = new Date(startDate)
      const end = new Date(start.getTime() + duration * 60000)
      openHours = `${start.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })} - ${end.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`
    } catch (e) {
      openHours = ''
    }
  }

  // compute a coarse timeRange label from start hour
  let timeRange = ''
  if (startDate) {
    const h = new Date(startDate).getHours()
    if (h >= 6 && h < 12) timeRange = 'morning'
    else if (h >= 12 && h < 17) timeRange = 'afternoon'
    else if (h >= 17 && h < 21) timeRange = 'evening'
    else timeRange = 'night'
  }

  // Priority for images: gallery_image > artist profile_picture > placeholder
  const galleryImage = item.gallery_image || null
  const artistPic = (item.artist && item.artist.profile_picture) ? item.artist.profile_picture : null
  const rawPic = galleryImage || artistPic
  
  let image = './image/placeholder-artwork.jpg'
  if (rawPic) {
    // If the picture is already a URL or absolute path, use it, otherwise prefix with uploads folder
    if (/^https?:\/\//i.test(rawPic) || rawPic.startsWith('/') || rawPic.startsWith('./')) {
      image = rawPic
    } else {
      // Check if it's a gallery image (prioritize gallery images)
      if (galleryImage) {
        // Gallery images could be in uploads/galleries/ or just uploads/
        if (rawPic.includes('/')) {
          image = `./${rawPic}`
        } else {
          image = `./uploads/galleries/${rawPic}`
        }
      } else {
        // Fallback to artist profile picture
        image = `./uploads/user_profile_picture/${rawPic}`
      }
    }
  }

  return {
    id,
    title: item.title || 'Untitled Gallery',
    artist: artistName,
    location: city,
    date: startDate || '',
    timeRange: timeRange,
    rating: item.rating || 0,
    image: image,
    description: item.description || '',
    openHours: openHours,
    capacity: item.capacity || null,
    available: !!item.is_currently_active,
    raw: item // keep original payload for anything else
  }
}

// DOM elements
const searchInput = document.getElementById("searchInput")
const categoryFilter = document.getElementById("categoryFilter")
const difficultyFilter = document.getElementById("difficultyFilter")
const timeRangeFilter = document.getElementById("timeRangeFilter")
const durationFilter = document.getElementById("durationFilter")
const activeFiltersContainer = document.getElementById("activeFilters")
const searchResults = document.getElementById("searchResults")
const coursesGrid = document.getElementById("coursesGrid")
const courseCount = document.getElementById("courseCount")
const noResults = document.getElementById("noResults")

// Initialize the page
document.addEventListener("DOMContentLoaded", async () => {
  setupEventListeners()
  setupNavigation()

  // Load galleries from API and initialize UI
  galleries = await fetchGalleriesFromAPI()
  filteredGalleries = [...galleries]
  // Populate filter selects from loaded galleries so filters match card data
  populateFiltersFromGalleries(galleries)
  totalPages = Math.ceil(filteredGalleries.length / galleriesPerPage)
  renderGalleries(filteredGalleries)
  updatePaginationControls()
})

// Populate filter dropdowns (artist/location) from gallery data
function escapeHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
}

function populateFiltersFromGalleries(galleriesArr) {
  if (!Array.isArray(galleriesArr)) return

  const artistSelect = document.getElementById('categoryFilter')
  const locationSelect = document.getElementById('difficultyFilter')

  // Collect unique artists and locations
  const artists = Array.from(new Set(galleriesArr.map(g => g.artist).filter(Boolean))).sort((a,b) => a.localeCompare(b))
  const locations = Array.from(new Set(galleriesArr.map(g => g.location).filter(Boolean))).sort((a,b) => a.localeCompare(b))

  if (artistSelect) {
    let html = '<option value="">All Artists</option>'
    artists.forEach(a => {
      html += `<option value="${escapeHtml(a)}">${escapeHtml(a)}</option>`
    })
    artistSelect.innerHTML = html
  }

  if (locationSelect) {
    let html = '<option value="">All Locations</option>'
    locations.forEach(l => {
      html += `<option value="${escapeHtml(l)}">${escapeHtml(l)}</option>`
    })
    locationSelect.innerHTML = html
  }
}

// Setup event listeners
function setupEventListeners() {
  // Search input
  searchInput.addEventListener("input", debounce(applyFilters, 300))

  // Filter dropdowns and inputs
  categoryFilter.addEventListener("change", applyFilters)
  difficultyFilter.addEventListener("change", applyFilters)
  durationFilter.addEventListener("change", applyFilters)
  if (timeRangeFilter) timeRangeFilter.addEventListener("change", applyFilters)
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
  const selectedArtist = categoryFilter.value
  const selectedLocation = difficultyFilter.value
  const selectedDate = durationFilter.value
  const selectedTimeRange = timeRangeFilter ? timeRangeFilter.value : ''

  // Reset active filters
  activeFilters = {}

  // Filter galleries
  filteredGalleries = galleries.filter((gallery) => {
    let matches = true

    // Search term filter
    if (searchTerm) {
      const searchableText =
        `${gallery.title} ${gallery.artist} ${gallery.location} ${gallery.description}`.toLowerCase()
      matches = matches && searchableText.includes(searchTerm)
      if (searchTerm) activeFilters.search = searchTerm
    }

    // Artist filter
    if (selectedArtist) {
      matches = matches && gallery.artist === selectedArtist
      activeFilters.artist = selectedArtist
    }

    // Location filter
    if (selectedLocation) {
      matches = matches && gallery.location === selectedLocation
      activeFilters.location = selectedLocation
    }

    // Date filter
    if (selectedDate) {
      matches = matches && gallery.date === selectedDate
      activeFilters.date = selectedDate
    }

    // Time Range filter
    if (selectedTimeRange) {
      matches = matches && gallery.timeRange === selectedTimeRange
      activeFilters.timeRange = selectedTimeRange
    }

    return matches
  })

  // Reset to first page when filters change
  currentPage = 1

  // Update UI
  renderActiveFilters()
  renderGalleries(filteredGalleries)
  updateSearchResults()
  updatePaginationControls()

  // Show/hide no results
  if (filteredGalleries.length === 0) {
    coursesGrid.style.display = "none"
    noResults.style.display = "block"
    // Hide pagination when no results
    const paginationSection = document.querySelector(".pagination-section")
    if (paginationSection) {
      paginationSection.style.display = "none"
    }
  } else {
    coursesGrid.style.display = "grid"
    noResults.style.display = "none"
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
      const _minEl = document.getElementById('minPriceInput')
      const _maxEl = document.getElementById('maxPriceInput')
      if (_minEl) _minEl.value = ''
      if (_maxEl) _maxEl.value = ''
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
  const _minEl = document.getElementById('minPriceInput')
  const _maxEl = document.getElementById('maxPriceInput')
  if (_minEl) _minEl.value = ''
  if (_maxEl) _maxEl.value = ''

  activeFilters = {}
  filteredGalleries = [...galleries]
  currentPage = 1

  renderActiveFilters()
  renderGalleries(galleries)
  updateSearchResults()
  updatePaginationControls()

  // Re-populate filters from full gallery list
  populateFiltersFromGalleries(galleries)

  coursesGrid.style.display = "grid"
  noResults.style.display = "none"
}

// Render courses
function renderGalleries(galleriesToRender) {
  coursesGrid.innerHTML = ""

  // Pagination logic
  const startIndex = (currentPage - 1) * galleriesPerPage
  const endIndex = startIndex + galleriesPerPage
  const paginatedGalleries = galleriesToRender.slice(startIndex, endIndex)

  paginatedGalleries.forEach((gallery) => {
    const galleryCard = document.createElement("div")
    galleryCard.className = "course-card"

    const availabilityBadge = gallery.available 
      ? `<div class="availability-badge available"><span class="availability-dot"></span><span>Available</span></div>`
      : `<div class="availability-badge unavailable"><span class="availability-dot"></span><span>Booked</span></div>`

    galleryCard.innerHTML = `
      ${availabilityBadge}
      <img src="${gallery.image}" alt="${gallery.title}" class="course-image" onerror="this.src='./image/placeholder-artwork.jpg'">
      <div class="course-overlay">
        <div class="quick-actions">
          <button class="quick-action-btn"><i class="fas fa-eye"></i></button>
        </div>
      </div>
      <div class="course-content">
        <h3 class="course-title">${gallery.title}</h3>
        <div class="course-instructor">Artist: ${gallery.artist}</div>
        <div class="course-category">${gallery.location}</div>
        <div class="course-meta">
          <div class="course-duration">
            <i class="fas fa-clock"></i>
            ${gallery.openHours}
          </div>
          <!-- Capacity removed -->
          <div class="gallery-time">
            <i class="fas fa-calendar"></i>
            ${formatDateDisplay(gallery.date)} • ${formatTimeDisplay(gallery.timeRange)}
          </div>
        </div>
        <button class="enroll-btn" onclick="bookGallery(${gallery.id})">
          <i class="fas fa-calendar-check"></i> Book Visit
        </button>
      </div>
    `

    coursesGrid.appendChild(galleryCard)
  })

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
  totalPages = Math.ceil(galleriesToRender.length / galleriesPerPage)
  updatePaginationInfo()
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
        <img src="${gallery.image}" alt="${gallery.title}" class="quick-view-image">
        <div class="quick-view-details">
          <h2>${gallery.title}</h2>
          <p class="instructor">Artist: ${gallery.artist}</p>
          <p class="description">${gallery.description}</p>
          <div class="meta-info">
            <span><i class="fas fa-map-marker-alt"></i> ${gallery.location}</span>
            <span><i class="fas fa-clock"></i> ${gallery.openHours}</span>
            <span><i class="fas fa-calendar"></i> ${formatDateDisplay(gallery.date)}</span>
            <span><i class="fas fa-users"></i> ${gallery.capacity || 'N/A'} capacity</span>
            <!-- Rating removed -->
          </div>
          <button class="enroll-btn" onclick="bookGallery(${gallery.id})">
            <i class="fas fa-calendar-check"></i> Book Visit
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
  const courseCountEl = document.getElementById('courseCount')
  if (!courseCountEl) return

  if (count === galleries.length) {
    courseCountEl.textContent = `Showing all ${count} galleries`
  } else {
    courseCountEl.textContent = `Showing ${count} of ${galleries.length} galleries`
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
  
  const courseCountEl = document.getElementById('courseCount')
  if (!courseCountEl) return

  if (filteredGalleries.length === 0) {
    courseCountEl.textContent = "No galleries found"
  } else if (filteredGalleries.length <= galleriesPerPage) {
    courseCountEl.textContent = `Showing ${filteredGalleries.length} galler${filteredGalleries.length === 1 ? 'y' : 'ies'}`
  } else {
    courseCountEl.textContent = `Showing ${startGallery}-${endGallery} of ${filteredGalleries.length} galleries`
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
  const gallery = galleries.find((g) => g.id === galleryId)
  if (gallery) {
    alert(`Booking request sent for ${gallery.title}!\nArtist: ${gallery.artist}\nLocation: ${gallery.location}\nTime: ${gallery.openHours}`)
  }
}

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