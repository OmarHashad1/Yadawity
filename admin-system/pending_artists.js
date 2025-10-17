// Pending Artists Admin Page JS
// Loads pending artists and handles approve/reject actions

document.addEventListener("DOMContentLoaded", () => {
  loadPendingArtists()
  // Display user info
  const userInfo = document.getElementById("userInfo")
  userInfo.textContent = `Welcome, Admin`
  userInfo.style.opacity = "1"
})

function loadPendingArtists() {
  fetch("API/pending_artists.php")
    .then((res) => res.json())
    .then((data) => {
      if (data.success && Array.isArray(data.data)) {
        renderArtists(data.data)
      } else {
        showError("Failed to load pending artists.")
      }
    })
    .catch(() => showError("Network error. Could not load artists."))
}

function renderArtists(artists) {
  const grid = document.getElementById("pendingArtistsGrid")
  grid.innerHTML =
    artists.length === 0
      ? '<div class="empty-state">No pending artists found.</div>'
      : artists.map((artist) => artistCardHTML(artist)).join("")
}

function artistCardHTML(artist) {
  const photo = artist.profile_picture && artist.profile_picture !== "" ? artist.profile_picture : "image/no-photo.png"
  return `
    <div class="artist-card">
      <img src="${photo}" alt="Artist Photo" class="artist-photo" />
      <div class="artist-info">
        <div class="artist-name">${escapeHTML(artist.first_name)} ${escapeHTML(artist.last_name)}</div>
        <div class="artist-meta">Joined: ${escapeHTML(artist.created_at)}</div>
        <div class="artist-email">Email: ${escapeHTML(artist.email)}</div>
        <div class="artist-meta">Phone: ${escapeHTML(artist.phone)}</div>
      </div>
      <div class="artist-actions">
        <button class="approve-btn" onclick="updateArtistStatus(${artist.user_id}, 'approve')">Approve</button>
        <button class="reject-btn" onclick="updateArtistStatus(${artist.user_id}, 'reject')">Reject</button>
      </div>
    </div>
  `
}

function updateArtistStatus(userId, action) {
  const Swal = window.Swal // Declare the Swal variable
  Swal.fire({
    title: `${action === "approve" ? "Approve" : "Reject"} Artist?`,
    text: `Are you sure you want to ${action} this artist?`,
    icon: action === "approve" ? "success" : "error",
    showCancelButton: true,
    confirmButtonText: action.charAt(0).toUpperCase() + action.slice(1),
    cancelButtonText: "Cancel",
    confirmButtonColor: action === "approve" ? "#27ae60" : "#e74c3c",
    cancelButtonColor: "#aaa",
  }).then((result) => {
    if (result.isConfirmed) {
      fetch("API/update_artist_status.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ user_id: userId, action }),
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            showSuccess(data.message || "Artist status updated.")
            loadPendingArtists()
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
