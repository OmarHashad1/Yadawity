// Pending Artworks Admin Page JS
// Loads pending artworks and handles approve/reject actions

document.addEventListener("DOMContentLoaded", () => {
  loadPendingArtworks()
  // Display user info
  const userInfo = document.getElementById("userInfo")
  userInfo.textContent = `Welcome, Admin`
  userInfo.style.opacity = "1"

  const toggleBtn = document.querySelector(".mobile-menu-toggle")
  toggleBtn.addEventListener("click", toggleSidebar)
})

function loadPendingArtworks() {
  fetch("API/pending_artworks.php")
    .then((res) => res.json())
    .then((data) => {
      if (data.success && Array.isArray(data.data)) {
        renderArtworks(data.data)
      } else {
        showError("Failed to load pending artworks.")
      }
    })
    .catch(() => showError("Network error. Could not load artworks."))
}

function renderArtworks(artworks) {
  const grid = document.getElementById("pendingArtworksGrid")
  grid.innerHTML =
    artworks.length === 0
      ? '<div class="empty-state">No pending artworks found.</div>'
      : artworks.map((artwork) => artworkCardHTML(artwork)).join("")
}

function artworkCardHTML(artwork) {
  let photo = "/image/no-photo.png";
  if (artwork.photos && artwork.photos.length > 0) {
    photo = artwork.photos[0].image_path;
    // If not an absolute path, prepend /uploads/
    if (!photo.startsWith("/") && !photo.startsWith("http")) {
      photo = "/uploads/" + photo;
    }
  }
  return `
    <div class="artwork-card">
      <img src="${photo}" alt="Artwork Photo" class="artwork-photo" />
      <div class="artwork-info">
        <div class="artwork-title">${escapeHTML(artwork.title)}</div>
        <div class="artwork-meta">Created: ${escapeHTML(artwork.created_at)}</div>
        <div class="artist-name">Artist: ${escapeHTML(artwork.first_name)} ${escapeHTML(artwork.last_name)}</div>
        <div class="artwork-meta">Email: ${escapeHTML(artwork.email)}</div>
      </div>
      <div class="artwork-actions">
        <button class="approve-btn" onclick="updateArtworkStatus(${artwork.artwork_id}, 'Approved')">Approve</button>
        <button class="reject-btn" onclick="updateArtworkStatus(${artwork.artwork_id}, 'Rejected')">Reject</button>
      </div>
    </div>
  `
}

function updateArtworkStatus(artworkId, status) {
  const Swal = window.Swal // Declare the Swal variable
  Swal.fire({
    title: `${status} Artwork?`,
    text: `Are you sure you want to mark this artwork as ${status}?`,
    icon: status === "Approved" ? "success" : "error",
    showCancelButton: true,
    confirmButtonText: status,
    cancelButtonText: "Cancel",
    confirmButtonColor: status === "Approved" ? "#27ae60" : "#e74c3c",
    cancelButtonColor: "#aaa",
  }).then((result) => {
    if (result.isConfirmed) {
      fetch("API/update_artwork_status.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ artwork_id: artworkId, status }),
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            showSuccess(data.message || "Artwork status updated.")
            loadPendingArtworks()
          } else {
            showError(data.message || "Failed to update status.")
          }
        })
        .catch(() => showError("Network error. Could not update status."))
    }
  })
}

function logout() {
  const Swal = window.Swal // Declare the Swal variable
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
      window.location.href = "logout.php"
    }
  })
}

function showError(message) {
  const Swal = window.Swal // Declare the Swal variable
  Swal.fire({
    icon: "error",
    title: "Error",
    text: message,
    confirmButtonColor: "#e74c3c",
  })
}

function showSuccess(message) {
  const Swal = window.Swal // Declare the Swal variable
  Swal.fire({
    icon: "success",
    title: "Success",
    text: message,
    confirmButtonColor: "#27ae60",
    timer: 2000,
    timerProgressBar: true,
  })
}

function escapeHTML(str) {
  if (!str) return ""
  return str.replace(
    /[&<>'"]/g,
    (c) =>
      ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
      })[c],
  )
}

function toggleSidebar() {
  const sidebar = document.getElementById("sidebar")
  const body = document.body

  if (sidebar.style.transform === "translateX(0px)" || sidebar.style.transform === "") {
    sidebar.style.transform = "translateX(-100%)"
    body.classList.remove("sidebar-open")
  } else {
    sidebar.style.transform = "translateX(0px)"
    body.classList.add("sidebar-open")
  }
}

document.addEventListener("click", (event) => {
  const sidebar = document.getElementById("sidebar")
  const toggleBtn = document.querySelector(".mobile-menu-toggle")

  if (
    window.innerWidth <= 1024 &&
    !sidebar.contains(event.target) &&
    !toggleBtn.contains(event.target) &&
    sidebar.style.transform === "translateX(0px)"
  ) {
    toggleSidebar()
  }
})
