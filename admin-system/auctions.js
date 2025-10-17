// Yadawity Admin Auctions Management - Professional JavaScript
let currentPage = 1
let currentSearch = ""
let currentStatusFilter = ""
let currentDateFilter = ""
let totalAuctions = 0

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

  // Load auctions
  loadAuctions()

  // Add search input event listener
  document.getElementById("searchInput").addEventListener("keypress", (e) => {
    if (e.key === "Enter") {
      searchAuctions()
    }
  })

  // Add real-time search with debouncing
  let searchTimeout
  document.getElementById("searchInput").addEventListener("input", (e) => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
      searchAuctions()
    }, 500)
  })
})

async function loadAuctions(page = 1, search = "", statusFilter = "", dateFilter = "") {
  try {
    showLoadingState()

    let url = `/admin-system/API/auctions.php?page=${page}&limit=20`
    const params = new URLSearchParams()

    if (page) params.append("page", page)
    if (search) params.append("q", search)
    if (statusFilter) params.append("status", statusFilter)
    if (dateFilter) params.append("start_time", dateFilter)

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
      displayAuctions(data.data)
      updatePagination(data.meta)
      updateResultsCount(data.meta)
      totalAuctions = data.meta.total
    } else {
      showError("Failed to load auctions", data.error || "Unknown error occurred")
    }
  } catch (error) {
    console.error("Error loading auctions:", error)
    showError("Error loading auctions", "Network error or server unavailable")
  }
}

function showLoadingState() {
  const tbody = document.getElementById("auctionsTableBody")
  tbody.innerHTML = `
        <tr>
            <td colspan="9" class="loading-row">
                <div class="loading-spinner"></div>
                <span>Loading auctions...</span>
            </td>
        </tr>
    `
}

function displayAuctions(auctions) {
  const tbody = document.getElementById("auctionsTableBody")

  if (!auctions || auctions.length === 0) {
    tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center" style="padding: 3rem; color: var(--text-light);">
                    <div style="font-size: 1.2rem; margin-bottom: 0.5rem;">No auctions found</div>
                    <div style="font-size: 0.9rem;">Try adjusting your search or filters</div>
                </td>
            </tr>
        `
    return
  }

  let html = ""
  auctions.forEach((auction, index) => {
    const statusClass = getStatusBadgeClass(auction.status)

    html += `
            <tr class="fade-in" style="animation-delay: ${index * 0.05}s;">
                <td><strong>#${auction.id}</strong></td>
                <td><span class="auction-id">${auction.product_id}</span></td>
                <td><span class="auction-id">${auction.artist_id}</span></td>
                <td class="bid-amount starting">${(auction.starting_bid || 0).toLocaleString()}</td>
                <td class="bid-amount current">${(auction.current_bid || 0).toLocaleString()}</td>
                <td class="auction-time start">${formatDateTime(auction.start_time)}</td>
                <td class="auction-time end">${formatDateTime(auction.end_time)}</td>
                <td><span class="auction-status-badge ${auction.status}">${auction.status}</span></td>
                <td class="action-buttons">
                    <button class="btn btn-sm btn-outline-primary" onclick="editAuction(${auction.id})" title="Edit Auction">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteAuction(${auction.id})" title="Delete Auction">
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

function getStatusBadgeClass(status) {
  switch (status) {
    case "upcoming":
      return "upcoming"
    case "starting_soon":
      return "starting_soon"
    case "active":
      return "active"
    case "sold":
      return "sold"
    case "cancelled":
      return "cancelled"
    default:
      return "upcoming"
  }
}

function formatDateTime(dateString) {
  if (!dateString) return "N/A"
  const date = new Date(dateString)
  return date.toLocaleString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
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
    resultsCount.textContent = `Showing ${start}-${end} of ${meta.total} auctions`
  } else {
    resultsCount.textContent = "No results"
  }
}

function changePage(page) {
  if (page < 1) return
  currentPage = page
  loadAuctions(currentPage, currentSearch, currentStatusFilter, currentDateFilter)
}

function searchAuctions() {
  currentSearch = document.getElementById("searchInput").value.trim()
  currentPage = 1
  loadAuctions(currentPage, currentSearch, currentStatusFilter, currentDateFilter)
}

function filterAuctions() {
  currentStatusFilter = document.getElementById("statusFilter").value
  currentDateFilter = document.getElementById("dateFilter").value
  currentPage = 1
  loadAuctions(currentPage, currentSearch, currentStatusFilter, currentDateFilter)
}

function openAddAuctionModal() {
  document.getElementById("auctionModalTitle").textContent = "Add New Auction"
  document.getElementById("auctionForm").reset()
  document.getElementById("auctionId").value = ""
  document.getElementById("currentBid").value = "0.00"

  // Set default dates
  const now = new Date()
  const startTime = new Date(now.getTime() + 24 * 60 * 60 * 1000) // Tomorrow
  const endTime = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000) // Next week

  document.getElementById("startTime").value = startTime.toISOString().slice(0, 16)
  document.getElementById("endTime").value = endTime.toISOString().slice(0, 16)

  showModal()
}

function closeAuctionModal() {
  const modal = document.getElementById("auctionModal")
  modal.classList.remove("show")
}

function showModal() {
  const modal = document.getElementById("auctionModal")
  modal.classList.add("show")
}

async function editAuction(auctionId) {
  try {
    showLoadingState()

    const response = await fetch(`/admin-system/API/auctions.php?id=${auctionId}`, {
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
      const auction = data.data

      document.getElementById("auctionModalTitle").textContent = "Edit Auction"
      document.getElementById("auctionId").value = auction.id
      document.getElementById("productId").value = auction.product_id
      document.getElementById("artistId").value = auction.artist_id
      document.getElementById("startingBid").value = auction.starting_bid
      document.getElementById("currentBid").value = auction.current_bid || 0
      document.getElementById("startTime").value = auction.start_time.replace(" ", "T")
      document.getElementById("endTime").value = auction.end_time.replace(" ", "T")
      document.getElementById("status").value = auction.status

      showModal()
    } else {
      const errorData = await response.json()
      showError("Error loading auction details", errorData.error || "Failed to load auction")
    }
  } catch (error) {
    console.error("Error loading auction details:", error)
    showError("Error loading auction details", "Network error or server unavailable")
  }
}

async function saveAuction() {
  const auctionId = document.getElementById("auctionId").value
  const isEdit = auctionId !== ""

  // Form validation
  const productId = document.getElementById("productId").value
  const artistId = document.getElementById("artistId").value
  const startingBid = document.getElementById("startingBid").value
  const currentBid = document.getElementById("currentBid").value
  const startTime = document.getElementById("startTime").value
  const endTime = document.getElementById("endTime").value
  const status = document.getElementById("status").value

  if (!productId || !artistId || !startingBid || !startTime || !endTime || !status) {
    showError("Please fill in all required fields.")
    return
  }

  if (isNaN(startingBid) || Number.parseFloat(startingBid) < 0) {
    showError("Please enter a valid starting bid.")
    return
  }

  if (isNaN(currentBid) || Number.parseFloat(currentBid) < 0) {
    showError("Please enter a valid current bid.")
    return
  }

  if (new Date(startTime) >= new Date(endTime)) {
    showError("End time must be after start time.")
    return
  }

  const auctionData = {
    product_id: Number.parseInt(productId),
    artist_id: Number.parseInt(artistId),
    starting_bid: Number.parseFloat(startingBid),
    current_bid: Number.parseFloat(currentBid),
    start_time: startTime,
    end_time: endTime,
    status: status,
  }

  try {
    const url = isEdit ? `/admin-system/API/auctions.php?id=${auctionId}` : "/admin-system/API/auctions.php"
    const method = isEdit ? "PUT" : "POST"

    const response = await fetch(url, {
      method: method,
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-Token": localStorage.getItem("csrf_token"),
      },
      body: JSON.stringify(auctionData),
    })

    if (response.status === 401 || response.status === 403) {
      localStorage.clear()
      window.location.href = "login.php"
      return
    }

    const data = await response.json()

    if (response.ok) {
      closeAuctionModal()
      showSuccess(isEdit ? "Auction updated successfully!" : "Auction created successfully!")
      loadAuctions(currentPage, currentSearch, currentStatusFilter, currentDateFilter)
    } else {
      showError("Error: " + (data.error || "Failed to save auction"))
    }
  } catch (error) {
    console.error("Error saving auction:", error)
    showError("Error saving auction. Please try again.", "Network error or server unavailable")
  }
}

async function deleteAuction(auctionId) {
  const result = await Swal.fire({
    title: "Delete Auction",
    text: "Are you sure you want to delete this auction? This action cannot be undone.",
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
    const response = await fetch(`/admin-system/API/auctions.php?id=${auctionId}`, {
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
      showSuccess("Auction deleted successfully!")
      loadAuctions(currentPage, currentSearch, currentStatusFilter, currentDateFilter)
    } else {
      showError("Error: " + (data.error || "Failed to delete auction"))
    }
  } catch (error) {
    console.error("Error deleting auction:", error)
    showError("Error deleting auction. Please try again.", "Network error or server unavailable")
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
  const modal = document.getElementById("auctionModal")
  if (e.target === modal) {
    closeAuctionModal()
  }
})

// Close modal with Escape key
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    closeAuctionModal()
  }
})
