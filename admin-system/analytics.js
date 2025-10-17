// Yadawity Admin Analytics Management - Professional JavaScript
let dailyOrdersChart = null
let topArtworksChart = null
let currentChartType = "line"
const Swal = window.Swal // Declare the Swal variable

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

  // Set default date range (last 30 days)
  const today = new Date()
  const thirtyDaysAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000)

  document.getElementById("fromDate").value = thirtyDaysAgo.toISOString().split("T")[0]
  document.getElementById("toDate").value = today.toISOString().split("T")[0]

  // Load analytics
  loadAnalytics()
})

async function loadAnalytics() {
  const fromDate = document.getElementById("fromDate").value
  const toDate = document.getElementById("toDate").value

  if (!fromDate || !toDate) {
    showError("Please select both start and end dates.")
    return
  }

  if (new Date(fromDate) > new Date(toDate)) {
    showError("Start date cannot be after end date.")
    return
  }

  try {
    showLoadingState()

    const response = await fetch(`/admin-system/API/analytics.php?from_date=${fromDate}&to_date=${toDate}`, {
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
      updateAnalyticsCards(data.data)
      updateCharts(data.data)
      updateAdditionalAnalytics(data.data)
      hideLoadingState()
    } else {
      showError("Failed to load analytics", data.error || "Unknown error occurred")
      hideLoadingState()
    }
  } catch (error) {
    console.error("Error loading analytics:", error)
    showError("Error loading analytics", "Network error or server unavailable")
    hideLoadingState()
  }
}

function showLoadingState() {
  // Show loading state for metrics cards
  document.getElementById("totalOrders").textContent = "..."
  document.getElementById("totalRevenue").textContent = "$..."
  document.getElementById("activeAuctions").textContent = "..."
  document.getElementById("artworksByType").textContent = "..."

  // Show loading state for charts
  const chartContainers = document.querySelectorAll(".chart-container")
  chartContainers.forEach((container) => {
    const canvas = container.querySelector("canvas")
    if (canvas) {
      const ctx = canvas.getContext("2d")
      ctx.clearRect(0, 0, canvas.width, canvas.height)
      ctx.fillStyle = "#f8f9fa"
      ctx.fillRect(0, 0, canvas.width, canvas.height)
      ctx.fillStyle = "#6c757d"
      ctx.font = "16px Arial"
      ctx.textAlign = "center"
      ctx.fillText("Loading...", canvas.width / 2, canvas.height / 2)
    }
  })
}

function hideLoadingState() {
  // Loading state is cleared when data is loaded
}

function updateAnalyticsCards(data) {
  // Update summary cards with animation
  animateValue("totalOrders", data.total_orders || 0, 0, 1000)
  animateValue("totalRevenue", data.total_revenue || 0, 0, 1000, true)
  animateValue("activeAuctions", data.active_auctions || 0, 0, 1000)
  animateValue("artworksByType", data.artworks_by_type_count || 0, 0, 1000)
}

function animateValue(elementId, targetValue, startValue, duration, isCurrency = false) {
  const element = document.getElementById(elementId)
  const start = performance.now()
  const difference = targetValue - startValue

  function updateValue(currentTime) {
    const elapsed = currentTime - start
    const progress = Math.min(elapsed / duration, 1)

    // Easing function for smooth animation
    const easeOutQuart = 1 - Math.pow(1 - progress, 4)
    const currentValue = startValue + difference * easeOutQuart

    if (isCurrency) {
      element.textContent = `$${Math.floor(currentValue).toLocaleString()}`
    } else {
      element.textContent = Math.floor(currentValue).toLocaleString()
    }

    if (progress < 1) {
      requestAnimationFrame(updateValue)
    }
  }

  requestAnimationFrame(updateValue)
}

function updateCharts(data) {
  updateDailyOrdersChart(data)
  updateTopArtworksChart(data)
}

function updateDailyOrdersChart(data) {
  if (dailyOrdersChart) {
    dailyOrdersChart.destroy()
  }

  // Limit to last 10 days with data
  const dailyOrders = data.daily_orders ? data.daily_orders.slice(-10) : []

  const dailyOrdersCanvas = document.getElementById("dailyOrdersChart")
  dailyOrdersCanvas.width = 620
  dailyOrdersCanvas.height = 400
  const dailyOrdersCtx = dailyOrdersCanvas.getContext("2d")
  const chartData = {
    labels: dailyOrders.map((item) => formatDate(item.date)) || [],
    datasets: [
      {
        label: "Orders",
        data: dailyOrders.map((item) => item.count) || [],
        borderColor: "rgb(107, 68, 35)",
        backgroundColor: "rgba(107, 68, 35, 0.1)",
        yAxisID: "y",
        tension: 0.4,
        fill: currentChartType === "area",
      },
      {
        label: "Revenue ($)",
        data: dailyOrders.map((item) => item.revenue) || [],
        borderColor: "rgb(212, 165, 116)",
        backgroundColor: "rgba(212, 165, 116, 0.1)",
        yAxisID: "y1",
        tension: 0.4,
        fill: currentChartType === "area",
      },
    ],
  }

  // Calculate suggested max for y and y1 axes
  const ordersMax = Math.max(...dailyOrders.map((item) => item.count), 10)
  const revenueMax = Math.max(...dailyOrders.map((item) => item.revenue), 100)

  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
      mode: "index",
      intersect: false,
    },
    plugins: {
      legend: {
        position: "top",
        labels: {
          usePointStyle: true,
          padding: 20,
          font: {
            family: "Roboto, sans-serif",
            size: 12,
          },
        },
      },
      tooltip: {
        backgroundColor: "rgba(107, 68, 35, 0.9)",
        titleColor: "white",
        bodyColor: "white",
        borderColor: "rgba(212, 165, 116, 0.5)",
        borderWidth: 1,
        cornerRadius: 8,
        callbacks: {
          label: (context) => {
            let label = context.dataset.label || ""
            if (label) {
              label += ": "
            }
            if (context.datasetIndex === 1) {
              label += "$" + context.parsed.y.toLocaleString()
            } else {
              label += context.parsed.y.toLocaleString()
            }
            return label
          },
        },
      },
    },
    scales: {
      x: {
        display: true,
        title: {
          display: true,
          text: "Date",
          font: {
            family: "Roboto, sans-serif",
            size: 12,
          },
        },
        grid: {
          color: "rgba(0, 0, 0, 0.05)",
        },
      },
      y: {
        type: "linear",
        display: true,
        position: "left",
        title: {
          display: true,
          text: "Orders",
          font: {
            family: "Roboto, sans-serif",
            size: 12,
          },
        },
        grid: {
          color: "rgba(0, 0, 0, 0.05)",
        },
        suggestedMax: ordersMax,
        beginAtZero: true,
      },
      y1: {
        type: "linear",
        display: true,
        position: "right",
        title: {
          display: true,
          text: "Revenue ($)",
          font: {
            family: "Roboto, sans-serif",
            size: 12,
          },
        },
        grid: {
          drawOnChartArea: false,
        },
        suggestedMax: revenueMax,
        beginAtZero: true,
      },
    },
  }

  dailyOrdersChart = new window.Chart(dailyOrdersCtx, {
    type: currentChartType,
    data: chartData,
    options: chartOptions,
  })
}

function updateTopArtworksChart(data) {
  if (topArtworksChart) {
    topArtworksChart.destroy()
  }

  const topArtworksCanvas = document.getElementById("topArtworksChart")
  topArtworksCanvas.width = 620
  topArtworksCanvas.height = 400
  const topArtworksCtx = topArtworksCanvas.getContext("2d")

  const chartData = {
    labels: data.top_artworks?.map((item) => truncateText(item.title, 20)) || [],
    datasets: [
      {
        data: data.top_artworks?.map((item) => item.sales_count) || [],
        backgroundColor: ["#6b4423", "#d4a574", "#4a2c17", "#8b6f47", "#27ae60", "#f39c12", "#e74c3c", "#3498db"],
        borderWidth: 2,
        borderColor: "#ffffff",
      },
    ],
  }

  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: "bottom",
        labels: {
          usePointStyle: true,
          padding: 20,
          font: {
            family: "Roboto, sans-serif",
            size: 11,
          },
        },
      },
      tooltip: {
        backgroundColor: "rgba(107, 68, 35, 0.9)",
        titleColor: "white",
        bodyColor: "white",
        borderColor: "rgba(212, 165, 116, 0.5)",
        borderWidth: 1,
        cornerRadius: 8,
        callbacks: {
          label: (context) => `Sales: ${context.parsed.toLocaleString()}`,
        },
      },
    },
  }

  topArtworksChart = new window.Chart(topArtworksCtx, {
    type: "doughnut",
    data: chartData,
    options: chartOptions,
  })
}

function updateAdditionalAnalytics(data) {
  updateAuctionsStatus(data)
  updateArtworksByType(data)
}

function updateAuctionsStatus(data) {
  const auctionsStatusDiv = document.getElementById("auctionsStatus")
  if (data.auctions_status) {
    let html = ""
    Object.entries(data.auctions_status).forEach(([status, count]) => {
      const statusClass = getStatusClass(status)
      const statusText = formatStatusText(status)
      html += `
                <div class="status-item">
                    <span class="status-badge ${statusClass}">${statusText}</span>
                    <span class="status-count">${count}</span>
                </div>
            `
    })
    auctionsStatusDiv.innerHTML = html
  } else {
    auctionsStatusDiv.innerHTML = '<div class="empty-state">No auction data available</div>'
  }
}

function updateArtworksByType(data) {
  const artworksByTypeDiv = document.getElementById("artworksByTypeDetails")
  if (data.artworks_by_type) {
    let html = ""
    Object.entries(data.artworks_by_type).forEach(([type, count]) => {
      html += `
                <div class="category-item">
                    <span class="category-name">${formatCategoryText(type)}</span>
                    <span class="category-count">${count}</span>
                </div>
            `
    })
    artworksByTypeDiv.innerHTML = html
  } else {
    artworksByTypeDiv.innerHTML = '<div class="empty-state">No artwork data available</div>'
  }
}

function getStatusClass(status) {
  const statusClasses = {
    upcoming: "upcoming",
    starting_soon: "starting_soon",
    active: "active",
    sold: "sold",
    cancelled: "cancelled",
  }
  return statusClasses[status] || "upcoming"
}

function formatStatusText(status) {
  const statusTexts = {
    upcoming: "Upcoming",
    starting_soon: "Starting Soon",
    active: "Active",
    sold: "Sold",
    cancelled: "Cancelled",
  }
  return statusTexts[status] || status
}

function formatCategoryText(category) {
  return category.replace(/_/g, " ").replace(/\b\w/g, (l) => l.toUpperCase())
}

function formatDate(dateString) {
  if (!dateString) return "N/A"
  const date = new Date(dateString)
  return date.toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
  })
}

function truncateText(text, maxLength) {
  if (text.length <= maxLength) return text
  return text.substring(0, maxLength) + "..."
}

function switchChartType(type) {
  currentChartType = type

  // Update active button
  document.querySelectorAll(".chart-type-btn").forEach((btn) => {
    btn.classList.remove("active")
  })
  event.target.classList.add("active")

  // Reload charts with new type
  loadAnalytics()
}

function exportAnalytics(format) {
  const fromDate = document.getElementById("fromDate").value
  const toDate = document.getElementById("toDate").value

  if (!fromDate || !toDate) {
    showError("Please select both start and end dates before exporting.")
    return
  }

  try {
    const url = `/admin-system/API/analytics.php?from_date=${fromDate}&to_date=${toDate}&export=${format}`

    // Create a temporary link to trigger download
    const link = document.createElement("a")
    link.href = url
    link.download = `analytics_${fromDate}_to_${toDate}.${format}`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)

    showSuccess(`${format.toUpperCase()} export started successfully!`)
  } catch (error) {
    console.error("Error exporting analytics:", error)
    showError("Error exporting analytics", "Failed to generate export file")
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
