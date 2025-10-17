// Yadawity Admin Reports Management - Professional JavaScript
let currentReportData = null
let selectedReportType = null

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
})

function selectReportType(reportType) {
  selectedReportType = reportType

  // Update visual selection
  document.querySelectorAll(".report-type-card").forEach((card) => {
    card.classList.remove("selected")
  })

  // Find the clicked card and add selected class
  const clickedCard = event.currentTarget
  clickedCard.classList.add("selected")

  // Update the select dropdown
  document.getElementById("reportType").value = reportType

  // Show parameters section
  document.getElementById("reportParameters").style.display = "block"
  document.getElementById("generateReportSection").style.display = "block"

  // Hide results and export options
  document.getElementById("reportResults").style.display = "none"
  document.getElementById("reportSummary").style.display = "none"
  document.getElementById("exportOptions").style.display = "none"

  // Scroll to parameters
  document.getElementById("reportParameters").scrollIntoView({
    behavior: "smooth",
    block: "start",
  })
}

async function loadReport() {
  const reportType = document.getElementById("reportType").value
  const fromDate = document.getElementById("fromDate").value
  const toDate = document.getElementById("toDate").value

  if (!reportType || !fromDate || !toDate) {
    showError("Please select a report type and date range.")
    return
  }

  if (new Date(fromDate) > new Date(toDate)) {
    showError("Start date cannot be after end date.")
    return
  }

  try {
    showLoadingState()

    const response = await fetch(
      `/admin-system/API/reports.php?type=${reportType}&from_date=${fromDate}&to_date=${toDate}`,
      {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-Token": localStorage.getItem("csrf_token"),
        },
      },
    )

    if (response.status === 401 || response.status === 403) {
      localStorage.clear()
      window.location.href = "login.php"
      return
    }

    const data = await response.json()

    if (response.ok && data.data) {
      currentReportData = data.data
      displayReport(reportType, data.data)
      updateReportSummary(data.data)

      // Show results sections
      document.getElementById("reportResults").style.display = "block"
      document.getElementById("reportSummary").style.display = "grid"
      document.getElementById("exportOptions").style.display = "block"

      hideLoadingState()
      showSuccess("Report generated successfully!")

      // Scroll to results
      document.getElementById("reportResults").scrollIntoView({
        behavior: "smooth",
        block: "start",
      })
    } else {
      showError("Failed to load report", data.error || "Unknown error occurred")
      hideLoadingState()
    }
  } catch (error) {
    console.error("Error loading report:", error)
    showError("Error loading report", "Network error or server unavailable")
    hideLoadingState()
  }
}

function showLoadingState() {
  const reportContent = document.getElementById("reportContent")
  reportContent.innerHTML = `
        <div class="empty-state">
            <div class="empty-state-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M12 6v6l4 2"></path>
                </svg>
            </div>
            <h3>Generating Report...</h3>
            <p>Please wait while we process your data</p>
        </div>
    `
}

function hideLoadingState() {
  // Loading state is cleared when data is loaded
}

function displayReport(reportType, data) {
  const reportTitle = document.getElementById("reportTitle")
  const reportContent = document.getElementById("reportContent")

  // Set report title
  const titleMap = {
    sales_summary: "Sales Summary Report",
    user_activity: "User Activity Report",
    artwork_performance: "Artwork Performance Report",
    auction_results: "Auction Results Report",
    revenue_analysis: "Revenue Analysis Report",
    inventory_status: "Inventory Status Report",
  }
  reportTitle.textContent = titleMap[reportType] || "Report"

  // Generate report content based on type
  let html = ""

  switch (reportType) {
    case "sales_summary":
      html = generateSalesSummaryReport(data)
      break
    case "user_activity":
      html = generateUserActivityReport(data)
      break
    case "artwork_performance":
      html = generateArtworkPerformanceReport(data)
      break
    case "auction_results":
      html = generateAuctionResultsReport(data)
      break
    case "revenue_analysis":
      html = generateRevenueAnalysisReport(data)
      break
    case "inventory_status":
      html = generateInventoryStatusReport(data)
      break
    default:
      html = '<div class="empty-state"><h3>Unknown report type</h3></div>'
  }

  reportContent.innerHTML = html
}

function generateSalesSummaryReport(data) {
  if (!data.sales_data || data.sales_data.length === 0) {
    return `
            <div class="empty-state">
                <h3>No Sales Data Available</h3>
                <p>No sales data found for the selected period</p>
            </div>
        `
  }

  let html = `
        <div class="report-table-container">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Orders</th>
                        <th>Revenue</th>
                        <th>Average Order Value</th>
                    </tr>
                </thead>
                <tbody>
    `

  data.sales_data.forEach((item, index) => {
    html += `
            <tr class="fade-in" style="animation-delay: ${index * 0.05}s;">
                <td>${formatDate(item.date)}</td>
                <td><strong>${item.orders.toLocaleString()}</strong></td>
                <td><span class="text-success">$${item.revenue.toLocaleString()}</span></td>
                <td>$${item.average_order_value.toLocaleString()}</td>
            </tr>
        `
  })

  html += `
                </tbody>
            </table>
        </div>
    `

  return html
}

function generateUserActivityReport(data) {
  if (!data.user_activity || data.user_activity.length === 0) {
    return `
            <div class="empty-state">
                <h3>No User Activity Data Available</h3>
                <p>No user activity data found for the selected period</p>
            </div>
        `
  }

  let html = `
        <div class="report-table-container">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Login Count</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
    `

  data.user_activity.forEach((item, index) => {
    html += `
            <tr class="fade-in" style="animation-delay: ${index * 0.05}s;">
                <td><strong>#${item.user_id}</strong></td>
                <td>${item.name}</td>
                <td><span class="text-info">${item.login_count.toLocaleString()}</span></td>
                <td>${formatDateTime(item.last_login)}</td>
                <td>${item.actions}</td>
            </tr>
        `
  })

  html += `
                </tbody>
            </table>
        </div>
    `

  return html
}

function generateArtworkPerformanceReport(data) {
  if (!data.artwork_performance || data.artwork_performance.length === 0) {
    return `
            <div class="empty-state">
                <h3>No Artwork Performance Data Available</h3>
                <p>No artwork performance data found for the selected period</p>
            </div>
        `
  }

  let html = `
        <div class="report-table-container">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Artwork ID</th>
                        <th>Title</th>
                        <th>Views</th>
                        <th>Sales</th>
                        <th>Revenue</th>
                        <th>Rating</th>
                    </tr>
                </thead>
                <tbody>
    `

  data.artwork_performance.forEach((item, index) => {
    const ratingDisplay = item.rating ? `${item.rating.toFixed(1)} ⭐` : "N/A"
    html += `
            <tr class="fade-in" style="animation-delay: ${index * 0.05}s;">
                <td><strong>#${item.artwork_id}</strong></td>
                <td>${truncateText(item.title, 30)}</td>
                <td><span class="text-info">${item.views.toLocaleString()}</span></td>
                <td><span class="text-success">${item.sales.toLocaleString()}</span></td>
                <td><span class="text-success">$${item.revenue.toLocaleString()}</span></td>
                <td>${ratingDisplay}</td>
            </tr>
        `
  })

  html += `
                </tbody>
            </table>
        </div>
    `

  return html
}

function generateAuctionResultsReport(data) {
  if (!data.auction_results || data.auction_results.length === 0) {
    return `
            <div class="empty-state">
                <h3>No Auction Results Data Available</h3>
                <p>No auction results data found for the selected period</p>
            </div>
        `
  }

  let html = `
        <div class="report-table-container">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Auction ID</th>
                        <th>Product</th>
                        <th>Starting Bid</th>
                        <th>Final Bid</th>
                        <th>Status</th>
                        <th>End Date</th>
                    </tr>
                </thead>
                <tbody>
    `

  data.auction_results.forEach((item, index) => {
    const statusClass = getStatusClass(item.status)
    const statusText = formatStatusText(item.status)
    html += `
            <tr class="fade-in" style="animation-delay: ${index * 0.05}s;">
                <td><strong>#${item.auction_id}</strong></td>
                <td>${truncateText(item.product_name, 25)}</td>
                <td>$${item.starting_bid.toLocaleString()}</td>
                <td><span class="text-success">$${item.final_bid.toLocaleString()}</span></td>
                <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                <td>${formatDate(item.end_date)}</td>
            </tr>
        `
  })

  html += `
                </tbody>
            </table>
        </div>
    `

  return html
}

function generateRevenueAnalysisReport(data) {
  if (!data.revenue_analysis || data.revenue_analysis.length === 0) {
    return `
            <div class="empty-state">
                <h3>No Revenue Analysis Data Available</h3>
                <p>No revenue analysis data found for the selected period</p>
            </div>
        `
  }

  let html = `
        <div class="report-table-container">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Revenue</th>
                        <th>Percentage</th>
                        <th>Growth</th>
                    </tr>
                </thead>
                <tbody>
    `

  data.revenue_analysis.forEach((item, index) => {
    const growthClass = item.growth >= 0 ? "text-success" : "text-danger"
    const growthIcon = item.growth >= 0 ? "↗" : "↘"

    html += `
            <tr class="fade-in" style="animation-delay: ${index * 0.05}s;">
                <td><strong>${item.category}</strong></td>
                <td><span class="text-success">$${item.revenue.toLocaleString()}</span></td>
                <td>${item.percentage.toFixed(1)}%</td>
                <td class="${growthClass}">${growthIcon} ${Math.abs(item.growth).toFixed(1)}%</td>
            </tr>
        `
  })

  html += `
                </tbody>
            </table>
        </div>
    `

  return html
}

function generateInventoryStatusReport(data) {
  if (!data.inventory_status || data.inventory_status.length === 0) {
    return `
            <div class="empty-state">
                <h3>No Inventory Status Data Available</h3>
                <p>No inventory status data found for the selected period</p>
            </div>
        `
  }

  let html = `
        <div class="report-table-container">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Artwork ID</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Stock</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
    `

  data.inventory_status.forEach((item, index) => {
    const statusClass = getInventoryStatusClass(item.status)
    const statusText = formatInventoryStatusText(item.status)
    html += `
            <tr class="fade-in" style="animation-delay: ${index * 0.05}s;">
                <td><strong>#${item.artwork_id}</strong></td>
                <td>${truncateText(item.title, 30)}</td>
                <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                <td><strong>${item.stock}</strong></td>
                <td>${formatDate(item.last_updated)}</td>
            </tr>
        `
  })

  html += `
                </tbody>
            </table>
        </div>
    `

  return html
}

function updateReportSummary(data) {
  // Animate the summary values
  animateValue("totalRecords", data.total_records || 0, 0, 1000)
  animateValue("totalValue", data.total_value || 0, 0, 1000, true)
  animateValue("averageValue", data.average_value || 0, 0, 1000, true)
  animateValue("reportStatus", data.status || "Complete", 0, 1000, false, true)
}

function animateValue(elementId, targetValue, startValue, duration, isCurrency = false, isText = false) {
  const element = document.getElementById(elementId)
  const start = performance.now()
  const difference = targetValue - startValue

  function updateValue(currentTime) {
    const elapsed = currentTime - start
    const progress = Math.min(elapsed / duration, 1)

    // Easing function for smooth animation
    const easeOutQuart = 1 - Math.pow(1 - progress, 4)
    const currentValue = startValue + difference * easeOutQuart

    if (isText) {
      element.textContent = targetValue
    } else if (isCurrency) {
      element.textContent = `$${Math.floor(currentValue).toLocaleString()}`
    } else {
      element.textContent = Math.floor(currentValue).toLocaleString()
    }

    if (progress < 1 && !isText) {
      requestAnimationFrame(updateValue)
    }
  }

  requestAnimationFrame(updateValue)
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

function getInventoryStatusClass(status) {
  const statusClasses = {
    in_stock: "active",
    low_stock: "starting_soon",
    out_of_stock: "cancelled",
    discontinued: "upcoming",
  }
  return statusClasses[status] || "upcoming"
}

function formatInventoryStatusText(status) {
  const statusTexts = {
    in_stock: "In Stock",
    low_stock: "Low Stock",
    out_of_stock: "Out of Stock",
    discontinued: "Discontinued",
  }
  return statusTexts[status] || status
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

function truncateText(text, maxLength) {
  if (text.length <= maxLength) return text
  return text.substring(0, maxLength) + "..."
}

function exportReport(format) {
  if (!currentReportData) {
    showError("Please generate a report first before exporting.")
    return
  }

  const reportType = document.getElementById("reportType").value
  const fromDate = document.getElementById("fromDate").value
  const toDate = document.getElementById("toDate").value

  try {
    const url = `/admin-system/API/reports.php?type=${reportType}&from_date=${fromDate}&to_date=${toDate}&export=${format}`

    // Create a temporary link to trigger download
    const link = document.createElement("a")
    link.href = url
    link.download = `${reportType}_${fromDate}_to_${toDate}.${format}`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)

    showSuccess(`${format.toUpperCase()} export started successfully!`)
  } catch (error) {
    console.error("Error exporting report:", error)
    showError("Error exporting report", "Failed to generate export file")
  }
}

function printReport() {
  if (!currentReportData) {
    showError("Please generate a report first before printing.")
    return
  }

  // Create a print-friendly version
  const printWindow = window.open("", "_blank")
  const reportTitle = document.getElementById("reportTitle").textContent
  const reportContent = document.getElementById("reportContent").innerHTML

  printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>${reportTitle}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                h1 { color: #2c3e50; }
                .status-badge { padding: 4px 8px; border-radius: 4px; color: white; }
                .upcoming { background-color: #7f8c8d; }
                .active { background-color: #27ae60; }
                .sold { background-color: #2c3e50; }
                .cancelled { background-color: #e74c3c; }
                .starting_soon { background-color: #3498db; }
                @media print { body { margin: 0; } }
            </style>
        </head>
        <body>
            <h1>${reportTitle}</h1>
            <p><strong>Generated on:</strong> ${new Date().toLocaleString()}</p>
            <p><strong>Date Range:</strong> ${document.getElementById("fromDate").value} to ${document.getElementById("toDate").value}</p>
            ${reportContent}
        </body>
        </html>
    `)

  printWindow.document.close()
  printWindow.print()
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
