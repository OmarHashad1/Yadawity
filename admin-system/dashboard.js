// Enhanced Yadawity Dashboard JavaScript - Modern & Optimized
class YadawityDashboard {
  constructor() {
    this.isLoading = false
    this.animationQueue = []
    this.retryCount = 0
    this.maxRetries = 3

    this.init()
  }

  async init() {
    // Wait for DOM to be fully loaded
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", () => this.initializeApp())
    } else {
      this.initializeApp()
    }
  }

  initializeApp() {
    // Check authentication first
    if (!this.checkAuthentication()) {
      return
    }

    // Initialize all components
    this.setupUserInterface()
    this.setupEventListeners()
    this.loadDashboardData()
    this.setupAnimations()
  }

  checkAuthentication() {
    const token = localStorage.getItem("csrf_token")
    if (!token) {
      this.redirectToLogin()
      return false
    }
    return true
  }

  setupUserInterface() {
    // Display user info with smooth animation
    this.displayUserInfo()

    // Highlight current page in navigation
    this.highlightCurrentPage()

    // Setup responsive behavior
    this.setupResponsiveLayout()
  }

  displayUserInfo() {
    const userInfo = document.getElementById("userInfo")
    if (!userInfo) return

    const userName = localStorage.getItem("user_name") || "Admin"
    userInfo.textContent = `Welcome, ${userName}`

    // Smooth fade-in animation
    userInfo.style.opacity = "0"
    requestAnimationFrame(() => {
      userInfo.style.transition = "opacity 0.5s ease-in-out"
      userInfo.style.opacity = "1"
    })
  }

  highlightCurrentPage() {
    const currentPage = window.location.pathname.split("/").pop()
    const navLinks = document.querySelectorAll(".sidebar-links a")

    navLinks.forEach((link) => {
      const href = link.getAttribute("href")
      if (href === currentPage || (currentPage === "" && href === "dashboard.php")) {
        link.classList.add("active")
      }
    })
  }

  setupResponsiveLayout() {
    // Add mobile menu toggle if needed
    const sidebarToggle = document.getElementById("sidebarToggle")
    if (sidebarToggle) {
      sidebarToggle.addEventListener("click", this.toggleSidebar.bind(this))
    }

    // Handle window resize
    window.addEventListener("resize", this.debounce(this.handleResize.bind(this), 250))
  }

  toggleSidebar(event) {
    document.body.classList.toggle("sidebar-open")

    // Add ripple effect
    this.createRippleEffect(event.target, event)
  }

  createRippleEffect(element, event) {
    const ripple = document.createElement("span")
    const rect = element.getBoundingClientRect()
    const size = Math.max(rect.width, rect.height)

    ripple.style.cssText = `
      position: absolute;
      border-radius: 50%;
      background: rgba(255,255,255,0.3);
      transform: scale(0);
      animation: ripple 0.6s linear;
      pointer-events: none;
      width: ${size}px;
      height: ${size}px;
      left: ${rect.width / 2 - size / 2}px;
      top: ${rect.height / 2 - size / 2}px;
    `

    element.style.position = "relative"
    element.appendChild(ripple)

    setTimeout(() => ripple.remove(), 600)
  }

  setupEventListeners() {
    // Logout button
    const logoutBtn = document.querySelector(".logout-btn")
    if (logoutBtn) {
      logoutBtn.addEventListener("click", this.handleLogout.bind(this))
    }

    // Add smooth scroll behavior
    document.documentElement.style.scrollBehavior = "smooth"

    // Handle visibility change for performance
    document.addEventListener("visibilitychange", this.handleVisibilityChange.bind(this))
  }

  setupAnimations() {
    // Intersection Observer for scroll animations
    const observerOptions = {
      threshold: 0.1,
      rootMargin: "0px 0px -50px 0px",
    }

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = "1"
          entry.target.style.transform = "translateY(0)"
        }
      })
    }, observerOptions)

    // Observe metric cards for scroll animations
    document.querySelectorAll(".metric-card, .summary-card").forEach((card) => {
      observer.observe(card)
    })
  }

  async loadDashboardData() {
    if (this.isLoading) return

    this.isLoading = true
    this.showLoadingState()

    try {
      const response = await this.fetchWithRetry("/admin-system/API/dashboard.php")

      if (response.status === 401 || response.status === 403) {
        this.handleUnauthorized()
        return
      }

      const data = await response.json()

      if (response.ok && data.data) {
        this.hideLoadingState()
        await this.updateDashboardMetrics(data.data)
      } else {
        throw new Error(data.error || "Failed to load dashboard data")
      }
    } catch (error) {
      console.error("Error loading dashboard data:", error)
      this.showErrorState()

      // Retry logic
      if (this.retryCount < this.maxRetries) {
        this.retryCount++
        setTimeout(() => this.loadDashboardData(), 2000 * this.retryCount)
      }
    } finally {
      this.isLoading = false
    }
  }

  async fetchWithRetry(url, options = {}, retries = 3) {
    const defaultOptions = {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-Token": localStorage.getItem("csrf_token"),
      },
      ...options,
    }

    for (let i = 0; i <= retries; i++) {
      try {
        const response = await fetch(url, defaultOptions)
        if (response.ok || response.status === 401 || response.status === 403) {
          return response
        }
        throw new Error(`HTTP ${response.status}`)
      } catch (error) {
        if (i === retries) throw error
        await this.delay(1000 * (i + 1))
      }
    }
  }

  showLoadingState() {
    const metricElements = document.querySelectorAll('[id$="Total"], [id*="users"], [id*="artworks"], [id*="auctions"]')
    metricElements.forEach((element) => {
      const card = element.closest(".summary-card, .metric-card")
      if (card) {
        card.classList.add("loading")
      }
    })
  }

  hideLoadingState() {
    document.querySelectorAll(".loading").forEach((element) => {
      element.classList.remove("loading")
    })
  }

  async updateDashboardMetrics(metrics) {
    const animations = [
      () => this.animateNumber("usersTotal", metrics.users_total || 0),
      () => this.animateNumber("artworksTotal", metrics.artworks_total || 0),
      () => this.animateNumber("ordersTotal", metrics.orders_total || 0),
      () => this.animateRevenue("revenueTotal", metrics.revenue_total || 0),
    ]

    // Stagger animations for better visual effect
    for (let i = 0; i < animations.length; i++) {
      setTimeout(animations[i], i * 150)
    }

    // Update detailed metrics with delays
    setTimeout(() => {
      this.animateNumber("usersArtists", metrics.users_artists || 0)
      this.animateNumber("usersBuyers", metrics.users_buyers || 0)
    }, 600)

    setTimeout(() => {
      this.animateNumber("artworksAvailable", metrics.artworks_available || 0)
      this.animateNumber("artworksOnAuction", metrics.artworks_on_auction || 0)
    }, 800)

    setTimeout(() => {
      this.updateOrdersByStatus(metrics.orders_by_status)
    }, 1000)

    setTimeout(() => {
      this.animateNumber("auctionsActive", metrics.auctions_active || 0)
      this.animateNumber("auctionsUpcoming", metrics.auctions_upcoming || 0)
    }, 1200)
  }

  animateNumber(elementId, targetValue, duration = 1200) {
    const element = document.getElementById(elementId)
    if (!element) return

    const startValue = 0
    const startTime = performance.now()

    const updateNumber = (currentTime) => {
      const elapsed = currentTime - startTime
      const progress = Math.min(elapsed / duration, 1)

      // Smooth easing function
      const easeOutCubic = 1 - Math.pow(1 - progress, 3)
      const currentValue = Math.floor(startValue + (targetValue - startValue) * easeOutCubic)

      element.textContent = currentValue.toLocaleString()

      if (progress < 1) {
        requestAnimationFrame(updateNumber)
      } else {
        element.textContent = targetValue.toLocaleString()
      }
    }

    requestAnimationFrame(updateNumber)
  }

  animateRevenue(elementId, targetValue, duration = 1200) {
    const element = document.getElementById(elementId)
    if (!element) return

    const startValue = 0
    const startTime = performance.now()

    const updateRevenue = (currentTime) => {
      const elapsed = currentTime - startTime
      const progress = Math.min(elapsed / duration, 1)

      const easeOutCubic = 1 - Math.pow(1 - progress, 3)
      const currentValue = Math.floor(startValue + (targetValue - startValue) * easeOutCubic)

      element.textContent = `$${currentValue.toLocaleString()}`

      if (progress < 1) {
        requestAnimationFrame(updateRevenue)
      } else {
        element.textContent = `$${targetValue.toLocaleString()}`
      }
    }

    requestAnimationFrame(updateRevenue)
  }

  updateOrdersByStatus(ordersByStatus) {
    const container = document.getElementById("ordersByStatus")
    if (!container) return

    if (!ordersByStatus || Object.keys(ordersByStatus).length === 0) {
      container.innerHTML = '<p class="text-muted">No orders data available</p>'
      return
    }

    const statusItems = Object.entries(ordersByStatus).map(([status, count]) => {
      const statusClass = this.getStatusClass(status)
      return `
        <div class="d-flex justify-content-between align-items-center mb-2" 
             style="opacity: 0; transform: translateX(-20px);">
          <span class="badge ${statusClass}">${this.capitalizeFirst(status)}</span>
          <span class="fw-bold">${count}</span>
        </div>
      `
    })

    container.innerHTML = statusItems.join("")

    // Animate each status item
    const items = container.querySelectorAll(".d-flex")
    items.forEach((item, index) => {
      setTimeout(() => {
        item.style.transition = "opacity 0.4s ease-out, transform 0.4s ease-out"
        item.style.opacity = "1"
        item.style.transform = "translateX(0)"
      }, index * 100)
    })
  }

  getStatusClass(status) {
    const statusClasses = {
      pending: "bg-warning",
      confirmed: "bg-info",
      shipped: "bg-primary",
      delivered: "bg-success",
      cancelled: "bg-danger",
    }
    return statusClasses[status.toLowerCase()] || "bg-secondary"
  }

  capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase()
  }

  showErrorState() {
    const metricElements = document.querySelectorAll('[id$="Total"], [id*="users"], [id*="artworks"], [id*="auctions"]')
    metricElements.forEach((element) => {
      const card = element.closest(".summary-card, .metric-card")
      if (card) {
        card.classList.remove("loading")
      }
      element.textContent = "Error"
      /* Updated error color to match brown theme */
      element.style.color = "#c44536"
    })
  }

  handleUnauthorized() {
    localStorage.clear()
    this.redirectToLogin()
  }

  redirectToLogin() {
    // Smooth fade out before redirect
    document.body.style.transition = "opacity 0.3s ease-out"
    document.body.style.opacity = "0"

    setTimeout(() => {
      window.location.href = "login.php"
    }, 300)
  }

  async handleLogout(event) {
    const logoutBtn = event.target
    const originalText = logoutBtn.textContent

    // Update button state
    logoutBtn.textContent = "Logging out..."
    logoutBtn.disabled = true

    try {
      await fetch("/admin-system/API/logout.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-Token": localStorage.getItem("csrf_token"),
        },
      })
    } catch (error) {
      console.error("Logout error:", error)
    } finally {
      // Clear storage and redirect regardless of API response
      localStorage.clear()
      this.redirectToLogin()
    }
  }

  handleVisibilityChange() {
    if (document.hidden) {
      // Pause animations when tab is not visible
      document.querySelectorAll("*").forEach((el) => {
        el.style.animationPlayState = "paused"
      })
    } else {
      // Resume animations when tab becomes visible
      document.querySelectorAll("*").forEach((el) => {
        el.style.animationPlayState = "running"
      })
    }
  }

  handleResize() {
    // Handle responsive layout changes
    const isMobile = window.innerWidth <= 768
    document.body.classList.toggle("mobile-layout", isMobile)
  }

  // Utility functions
  debounce(func, wait) {
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

  delay(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms))
  }
}

// Initialize dashboard when script loads
const dashboard = new YadawityDashboard()

// Global logout function for backward compatibility
window.logout = (event) => dashboard.handleLogout(event)
