// Yadawity Admin Orders Management - Professional JavaScript
let currentPage = 1
let currentSearch = ""
let currentStatusFilter = ""
let currentDateFilter = ""
let totalOrders = 0

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

  // Load orders
  loadOrders()

  // Add search input event listener
  document.getElementById("searchInput").addEventListener("keypress", (e) => {
    if (e.key === "Enter") {
      searchOrders()
    }
  })

  // Add real-time search with debouncing
  let searchTimeout
  document.getElementById("searchInput").addEventListener("input", (e) => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
      searchOrders()
    }, 500)
  })
})

async function loadOrders(page = 1, search = "", statusFilter = "", dateFilter = "") {
  try {
    showLoadingState()

    let url = `/admin-system/API/orders.php?page=${page}&limit=20`
    const params = new URLSearchParams()

    if (page) params.append("page", page)
    if (search) params.append("q", search)
    if (statusFilter) params.append("status", statusFilter)
    if (dateFilter) params.append("order_date", dateFilter)

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
      displayOrders(data.data)
      updatePagination(data.meta)
      updateResultsCount(data.meta)
      totalOrders = data.meta.total
    } else {
      showError("Failed to load orders", data.error || "Unknown error occurred")
    }
  } catch (error) {
    console.error("Error loading orders:", error)
    showError("Error loading orders", "Network error or server unavailable")
  }
}

function showLoadingState() {
  const tbody = document.getElementById("ordersTableBody")
  tbody.innerHTML = `
        <tr>
            <td colspan="7" class="loading-row">
                <div class="loading-spinner"></div>
                <span>Loading orders...</span>
            </td>
        </tr>
    `
}

function displayOrders(orders) {
  const tbody = document.getElementById("ordersTableBody")

  if (!orders || orders.length === 0) {
    tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center" style="padding: 3rem; color: var(--text-light);">
                    <div style="font-size: 1.2rem; margin-bottom: 0.5rem;">No orders found</div>
                    <div style="font-size: 0.9rem;">Try adjusting your search or filters</div>
                </td>
            </tr>
        `
    return
  }

  let html = ""
  orders.forEach((order, index) => {
    const statusClass = getStatusBadgeClass(order.status)

    html += `
            <tr class="fade-in" style="animation-delay: ${index * 0.05}s;">
                <td><strong>#${order.id}</strong></td>
                <td><span class="order-number">${order.order_number}</span></td>
                <td>
                    <div style="font-weight: 600;">${order.buyer_name}</div>
                    <div style="font-size: 0.85rem; color: var(--text-light);">ID: ${order.buyer_id}</div>
                </td>
                <td class="order-amount">${(order.total_amount || 0).toLocaleString()}</td>
                <td><span class="order-status-badge ${order.status}">${order.status}</span></td>
                <td>${formatDate(order.order_date)}</td>
                <td class="action-buttons">
                    <button class="btn btn-sm btn-outline-primary" onclick="editOrder(${order.id})" title="Edit Order">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteOrder(${order.id})" title="Delete Order">
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
    case "pending":
      return "pending"
    case "confirmed":
      return "confirmed"
    case "shipped":
      return "shipped"
    case "delivered":
      return "delivered"
    case "cancelled":
      return "cancelled"
    default:
      return "pending"
  }
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
    resultsCount.textContent = `Showing ${start}-${end} of ${meta.total} orders`
  } else {
    resultsCount.textContent = "No results"
  }
}

function changePage(page) {
  if (page < 1) return
  currentPage = page
  loadOrders(currentPage, currentSearch, currentStatusFilter, currentDateFilter)
}

function searchOrders() {
  currentSearch = document.getElementById("searchInput").value.trim()
  currentPage = 1
  loadOrders(currentPage, currentSearch, currentStatusFilter, currentDateFilter)
}

function filterOrders() {
  currentStatusFilter = document.getElementById("statusFilter").value
  currentDateFilter = document.getElementById("dateFilter").value
  currentPage = 1
  loadOrders(currentPage, currentSearch, currentStatusFilter, currentDateFilter)
}

function openAddOrderModal() {
  document.getElementById("orderModalTitle").textContent = "Add New Order"
  document.getElementById("orderForm").reset()
  document.getElementById("orderId").value = ""
  document.getElementById("orderDate").value = new Date().toISOString().split("T")[0]
  showModal()
}

function closeOrderModal() {
  const modal = document.getElementById("orderModal")
  modal.classList.remove("show")
}

function showModal() {
  const modal = document.getElementById("orderModal")
  modal.classList.add("show")
}

async function editOrder(orderId) {
  try {
    showLoadingState()

    const response = await fetch(`/admin-system/API/orders.php?id=${orderId}`, {
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
      const order = data.data

      document.getElementById("orderModalTitle").textContent = "Edit Order"
      document.getElementById("orderId").value = order.id
      document.getElementById("orderNumber").value = order.order_number
      document.getElementById("buyerId").value = order.buyer_id
      document.getElementById("buyerName").value = order.buyer_name
      document.getElementById("totalAmount").value = order.total_amount
      document.getElementById("status").value = order.status
      document.getElementById("orderDate").value = order.order_date
      document.getElementById("shippingAddress").value = order.shipping_address || ""

      showModal()
    } else {
      const errorData = await response.json()
      showError("Error loading order details", errorData.error || "Failed to load order")
    }
  } catch (error) {
    console.error("Error loading order details:", error)
    showError("Error loading order details", "Network error or server unavailable")
  }
}

async function saveOrder() {
  const orderId = document.getElementById("orderId").value
  const isEdit = orderId !== ""

  // Form validation
  const orderNumber = document.getElementById("orderNumber").value.trim()
  const buyerId = document.getElementById("buyerId").value
  const buyerName = document.getElementById("buyerName").value.trim()
  const totalAmount = document.getElementById("totalAmount").value
  const status = document.getElementById("status").value
  const orderDate = document.getElementById("orderDate").value

  if (!orderNumber || !buyerId || !buyerName || !totalAmount || !status || !orderDate) {
    showError("Please fill in all required fields.")
    return
  }

  if (isNaN(totalAmount) || Number.parseFloat(totalAmount) < 0) {
    showError("Please enter a valid amount.")
    return
  }

  const orderData = {
    order_number: orderNumber,
    buyer_id: Number.parseInt(buyerId),
    buyer_name: buyerName,
    total_amount: Number.parseFloat(totalAmount),
    status: status,
    order_date: orderDate,
    shipping_address: document.getElementById("shippingAddress").value.trim(),
  }

  try {
    const url = isEdit ? `/admin-system/API/orders.php?id=${orderId}` : "/admin-system/API/orders.php"
    const method = isEdit ? "PUT" : "POST"

    const response = await fetch(url, {
      method: method,
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-Token": localStorage.getItem("csrf_token"),
      },
      body: JSON.stringify(orderData),
    })

    if (response.status === 401 || response.status === 403) {
      localStorage.clear()
      window.location.href = "login.php"
      return
    }

    const data = await response.json()

    if (response.ok) {
      closeOrderModal()
      showSuccess(isEdit ? "Order updated successfully!" : "Order created successfully!")
      loadOrders(currentPage, currentSearch, currentStatusFilter, currentDateFilter)
    } else {
      showError("Error: " + (data.error || "Failed to save order"))
    }
  } catch (error) {
    console.error("Error saving order:", error)
    showError("Error saving order. Please try again.", "Network error or server unavailable")
  }
}

async function deleteOrder(orderId) {
  const result = await Swal.fire({
    title: "Delete Order",
    text: "Are you sure you want to delete this order? This action cannot be undone.",
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
    const response = await fetch(`/admin-system/API/orders.php?id=${orderId}`, {
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
      showSuccess("Order deleted successfully!")
      loadOrders(currentPage, currentSearch, currentStatusFilter, currentDateFilter)
    } else {
      showError("Error: " + (data.error || "Failed to delete order"))
    }
  } catch (error) {
    console.error("Error deleting order:", error)
    showError("Error deleting order. Please try again.", "Network error or server unavailable")
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
  const modal = document.getElementById("orderModal")
  if (e.target === modal) {
    closeOrderModal()
  }
})

// Close modal with Escape key
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    closeOrderModal()
  }
})
