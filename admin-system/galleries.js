// Yadawity Admin Galleries Management - Professional JavaScript
let currentPage = 1
let currentSearch = ""
let currentStatusFilter = ""
let currentTypeFilter = ""
let currentDateFilter = ""
let totalGalleries = 0

document.addEventListener("DOMContentLoaded", () => {
  // Check authentication
  if (!localStorage.getItem("csrf_token")) {
    window.location.href = "login.php"
    return
  }

  // Display user info
  const userInfo = document.getElementById("userInfo")
  const userName = localStorage.getItem("user_name") || "Admin"
  userInfo.textContent = `Welcome, ${userName}`
  userInfo.style.opacity = "1"

  // Load galleries
  loadGalleries()

  // Add search input event listener
  document.getElementById("searchInput").addEventListener("keypress", (e) => {
    if (e.key === "Enter") {
      searchGalleries()
    }
  })

  // Add real-time search with debouncing
  let searchTimeout
  document.getElementById("searchInput").addEventListener("input", (e) => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
      searchGalleries()
    }, 500)
  })
})

async function loadGalleries(page = 1, search = "", statusFilter = "", typeFilter = "", dateFilter = "") {
  try {
    showLoadingState()

    let url = `/admin-system/API/galleries.php?page=${page}&limit=20`
    const params = new URLSearchParams()

    if (page) params.append("page", page)
    if (search) params.append("q", search)
    if (statusFilter !== "") params.append("is_active", statusFilter)
    if (typeFilter) params.append("gallery_type", typeFilter)
    if (dateFilter) params.append("start_date", dateFilter)

    if (params.toString()) {
      url += "&" + params.toString()
    }

    const response = await fetch(url, {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-Token": localStorage.getItem("csrf_token"),
      },
    })

    if (response.status === 401 || response.status === 403) {
      localStorage.clear()
      window.location.href = "login.php"
      return
    }

    const data = await response.json()

    if (response.ok && data.data) {
      displayGalleries(data.data)
      updatePagination(data.meta)
      updateResultsCount(data.meta)
      totalGalleries = data.meta.total
    } else {
      showError("Failed to load galleries", data.error || "Unknown error occurred")
    }
  } catch (error) {
    console.error("Error loading galleries:", error)
    showError("Error loading galleries", "Network error or server unavailable")
  }
}

function showLoadingState() {
  const tbody = document.getElementById("galleriesTableBody")
  tbody.innerHTML = `
        <tr>
            <td colspan="9" class="loading-row">
                <div class="loading-spinner"></div>
                <span>Loading galleries...</span>
            </td>
        </tr>
    `
}

function displayGalleries(galleries) {
  const tbody = document.getElementById("galleriesTableBody")

  if (!galleries || galleries.length === 0) {
    tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center" style="padding: 3rem; color: var(--text-light);">
                    <div style="font-size: 1.2rem; margin-bottom: 0.5rem;">No galleries found</div>
                    <div style="font-size: 0.9rem;">Try adjusting your search or filters</div>
                </td>
            </tr>
        `
    return
  }

  let html = ""
  galleries.forEach((gallery, index) => {
    const statusClass = gallery.is_active ? "active" : "inactive"
    const statusText = gallery.is_active ? "Active" : "Inactive"
    const typeClass = gallery.gallery_type

    html += `
            <tr class="fade-in" style="animation-delay: ${index * 0.05}s;">
                <td><strong>#${gallery.gallery_id}</strong></td>
                <td><strong>${gallery.title}</strong></td>
                <td><span class="gallery-id">${gallery.artist_id}</span></td>
                <td><span class="gallery-type-badge ${typeClass}">${gallery.gallery_type}</span></td>
                <td class="gallery-price ${gallery.price ? "" : "free"}">${gallery.price ? gallery.price.toLocaleString() : "Free"}</td>
                <td class="gallery-duration">${gallery.duration}</td>
                <td><span class="gallery-status-badge ${statusClass}">${statusText}</span></td>
                <td class="gallery-date">${formatDate(gallery.start_date)}</td>
                <td class="action-buttons">
                    <button class="btn btn-sm btn-outline-primary" onclick="editGallery(${gallery.gallery_id})" title="Edit Gallery">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteGallery(${gallery.gallery_id})" title="Delete Gallery">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3,6 5,6 21,6"></polyline>
                            <path d="m19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"></path>
                        </svg>
                    </button>
                </td>
            </tr>
        `
  })

  tbody.innerHTML = html
}

function formatDate(dateString) {
  if (!dateString) return "N/A"
  const date = new Date(dateString)
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  })
}

function updatePagination(meta) {
  const pagination = document.getElementById("pagination")

  if (!meta || meta.total <= meta.limit) {
    pagination.innerHTML = ""
    return
  }

  const totalPages = Math.ceil(meta.total / meta.limit)
  let html = '<ul class="pagination">'

  // Previous button
  html += `
        <li class="page-item ${meta.page <= 1 ? "disabled" : ""}">
            <a class="page-link" href="#" onclick="changePage(${meta.page - 1})" ${meta.page <= 1 ? 'tabindex="-1"' : ""}>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
            </a>
        </li>
    `

  // Page numbers
  const startPage = Math.max(1, meta.page - 2)
  const endPage = Math.min(totalPages, meta.page + 2)

  if (startPage > 1) {
    html += '<li class="page-item"><a class="page-link" href="#" onclick="changePage(1)">1</a></li>'
    if (startPage > 2) {
      html += '<li class="page-item disabled"><span class="page-link">...</span></li>'
    }
  }

  for (let i = startPage; i <= endPage; i++) {
    if (i === meta.page) {
      html += `<li class="page-item active"><span class="page-link">${i}</span></li>`
    } else {
      html += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${i})">${i}</a></li>`
    }
  }

  if (endPage < totalPages) {
    if (endPage < totalPages - 1) {
      html += '<li class="page-item disabled"><span class="page-link">...</span></li>'
    }
    html += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${totalPages})">${totalPages}</a></li>`
  }

  // Next button
  html += `
        <li class="page-item ${meta.page >= totalPages ? "disabled" : ""}">
            <a class="page-link" href="#" onclick="changePage(${meta.page + 1})" ${meta.page >= totalPages ? 'tabindex="-1"' : ""}>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9,18 15,12 9,6"></polyline>
                </svg>
            </a>
        </li>
    `

  html += "</ul>"
  pagination.innerHTML = html
}

function updateResultsCount(meta) {
  const resultsCount = document.getElementById("resultsCount")
  if (meta) {
    const start = (meta.page - 1) * meta.limit + 1
    const end = Math.min(meta.page * meta.limit, meta.total)
    resultsCount.textContent = `Showing ${start}-${end} of ${meta.total} galleries`
  } else {
    resultsCount.textContent = "No results"
  }
}

function changePage(page) {
  if (page < 1) return
  currentPage = page
  loadGalleries(currentPage, currentSearch, currentStatusFilter, currentTypeFilter, currentDateFilter)
}

function searchGalleries() {
  currentSearch = document.getElementById("searchInput").value.trim()
  currentPage = 1
  loadGalleries(currentPage, currentSearch, currentStatusFilter, currentTypeFilter, currentDateFilter)
}

function filterGalleries() {
  currentStatusFilter = document.getElementById("statusFilter").value
  currentTypeFilter = document.getElementById("typeFilter").value
  currentDateFilter = document.getElementById("dateFilter").value
  currentPage = 1
  loadGalleries(currentPage, currentSearch, currentStatusFilter, currentTypeFilter, currentDateFilter)
}

function openAddGalleryModal() {
  document.getElementById("galleryModalTitle").textContent = "Add New Gallery"
  document.getElementById("galleryForm").reset()
  document.getElementById("galleryId").value = ""
  document.getElementById("isActive").checked = true

  // Set default start date to tomorrow
  const tomorrow = new Date()
  tomorrow.setDate(tomorrow.getDate() + 1)
  document.getElementById("startDate").value = tomorrow.toISOString().slice(0, 16)

  showModal()
}

function closeGalleryModal() {
  const modal = document.getElementById("galleryModal")
  modal.classList.remove("show")
}

function showModal() {
  const modal = document.getElementById("galleryModal")
  modal.classList.add("show")
}

async function editGallery(galleryId) {
  try {
    showLoadingState()

    const response = await fetch(`/admin-system/API/galleries.php?id=${galleryId}`, {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-Token": localStorage.getItem("csrf_token"),
      },
    })

    if (response.status === 401 || response.status === 403) {
      localStorage.clear()
      window.location.href = "login.php"
      return
    }

    if (response.ok) {
      const data = await response.json()
      const gallery = data.data

      document.getElementById("galleryModalTitle").textContent = "Edit Gallery"
      document.getElementById("galleryId").value = gallery.gallery_id
      document.getElementById("title").value = gallery.title
      document.getElementById("artistId").value = gallery.artist_id
      document.getElementById("galleryType").value = gallery.gallery_type
      document.getElementById("duration").value = gallery.duration
      document.getElementById("price").value = gallery.price || ""
      document.getElementById("startDate").value = gallery.start_date.replace(" ", "T")
      document.getElementById("isActive").checked = gallery.is_active == 1
      document.getElementById("description").value = gallery.description || ""
      document.getElementById("address").value = gallery.address || ""
      document.getElementById("city").value = gallery.city || ""
      document.getElementById("phone").value = gallery.phone || ""
      document.getElementById("image").value = gallery.img || ""

      showModal()
    } else {
      const errorData = await response.json()
      showError("Error loading gallery details", errorData.error || "Failed to load gallery")
    }
  } catch (error) {
    console.error("Error loading gallery details:", error)
    showError("Error loading gallery details", "Network error or server unavailable")
  }
}

async function saveGallery() {
  const galleryId = document.getElementById("galleryId").value
  const isEdit = galleryId !== ""

  // Form validation
  const title = document.getElementById("title").value
  const artistId = document.getElementById("artistId").value
  const galleryType = document.getElementById("galleryType").value
  const duration = document.getElementById("duration").value
  const startDate = document.getElementById("startDate").value

  if (!title || !artistId || !galleryType || !duration || !startDate) {
    showError("Please fill in all required fields.")
    return
  }

  if (isNaN(artistId) || Number.parseInt(artistId) < 1) {
    showError("Please enter a valid artist ID.")
    return
  }

  if (isNaN(duration) || Number.parseInt(duration) < 1) {
    showError("Please enter a valid duration (minimum 1 day).")
    return
  }

  const price = document.getElementById("price").value
  if (price && (isNaN(price) || Number.parseFloat(price) < 0)) {
    showError("Please enter a valid price.")
    return
  }

  const galleryData = {
    title: title,
    artist_id: Number.parseInt(artistId),
    gallery_type: galleryType,
    duration: Number.parseInt(duration),
    price: price ? Number.parseFloat(price) : null,
    start_date: startDate,
    is_active: document.getElementById("isActive").checked ? 1 : 0,
    description: document.getElementById("description").value,
    address: document.getElementById("address").value,
    city: document.getElementById("city").value,
    phone: document.getElementById("phone").value,
    img: document.getElementById("image").value,
  }

  try {
    const url = isEdit ? `/admin-system/API/galleries.php?id=${galleryId}` : "/admin-system/API/galleries.php"
    const method = isEdit ? "PUT" : "POST"

    const response = await fetch(url, {
      method: method,
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-Token": localStorage.getItem("csrf_token"),
      },
      body: JSON.stringify(galleryData),
    })

    if (response.status === 401 || response.status === 403) {
      localStorage.clear()
      window.location.href = "login.php"
      return
    }

    const data = await response.json()

    if (response.ok) {
      closeGalleryModal()
      showSuccess(isEdit ? "Gallery updated successfully!" : "Gallery created successfully!")
      loadGalleries(currentPage, currentSearch, currentStatusFilter, currentTypeFilter, currentDateFilter)
    } else {
      showError("Error: " + (data.error || "Failed to save gallery"))
    }
  } catch (error) {
    console.error("Error saving gallery:", error)
    showError("Error saving gallery. Please try again.", "Network error or server unavailable")
  }
}

async function deleteGallery(galleryId) {
  const result = await Swal.fire({
    title: "Delete Gallery",
    text: "Are you sure you want to delete this gallery? This action cannot be undone.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Delete",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#e74c3c",
    cancelButtonColor: "#2c3e50",
    reverseButtons: true,
  })

  if (!result.isConfirmed) return

  try {
    const response = await fetch(`/admin-system/API/galleries.php?id=${galleryId}`, {
      method: "DELETE",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-Token": localStorage.getItem("csrf_token"),
      },
    })

    if (response.status === 401 || response.status === 403) {
      localStorage.clear()
      window.location.href = "login.php"
      return
    }

    const data = await response.json()

    if (response.ok) {
      showSuccess("Gallery deleted successfully!")
      loadGalleries(currentPage, currentSearch, currentStatusFilter, currentTypeFilter, currentDateFilter)
    } else {
      showError("Error: " + (data.error || "Failed to delete gallery"))
    }
  } catch (error) {
    console.error("Error deleting gallery:", error)
    showError("Error deleting gallery. Please try again.", "Network error or server unavailable")
  }
}

function logout() {
  Swal.fire({
    title: "Logout",
    text: "Are you sure you want to logout?",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Logout",
    cancelButtonText: "Cancel",
    confirmButtonColor: "#e67e22",
    cancelButtonColor: "#2c3e50",
  }).then((result) => {
    if (result.isConfirmed) {
      fetch("/admin-system/API/logout.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-Token": localStorage.getItem("csrf_token"),
        },
      }).finally(() => {
        localStorage.clear()
        window.location.href = "login.php"
      })
    }
  })
}

function showError(message, error = null) {
  Swal.fire({
    icon: "error",
    title: "Error",
    text: message,
    footer: error ? `<pre style="font-size: 0.8rem; text-align: left;">${error}</pre>` : "",
    confirmButtonColor: "#e74c3c",
  })
}

function showSuccess(message) {
  Swal.fire({
    icon: "success",
    title: "Success",
    text: message,
    confirmButtonColor: "#27ae60",
    timer: 2000,
    timerProgressBar: true,
  })
}

// Close modal when clicking outside
document.addEventListener("click", (e) => {
  const modal = document.getElementById("galleryModal")
  if (e.target === modal) {
    closeGalleryModal()
  }
})

// Close modal with Escape key
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    closeGalleryModal()
  }
})
