// Login page interactivity for Yadawity design
document.addEventListener("DOMContentLoaded", () => {
  const loginForm = document.getElementById("loginForm")
  const errorMessage = document.getElementById("errorMessage")
  const submitButton = loginForm.querySelector(".login-btn")
  const inputs = loginForm.querySelectorAll(".form-input")

  // Enhanced form interactions
  inputs.forEach((input) => {
    input.addEventListener("focus", function () {
      this.parentElement.classList.add("focused")
    })
    input.addEventListener("blur", function () {
      if (!this.value) {
        this.parentElement.classList.remove("focused")
      }
    })
    input.addEventListener("input", function () {
      validateInput(this)
    })
    if (input.value) {
      input.parentElement.classList.add("focused")
    }
  })

  loginForm.addEventListener("keydown", (e) => {
    if (e.key === "Enter" && e.target.type !== "submit") {
      e.preventDefault()
      const formElements = Array.from(loginForm.elements)
      const currentIndex = formElements.indexOf(e.target)
      const nextElement = formElements[currentIndex + 1]
      if (nextElement && nextElement.type !== "submit") {
        nextElement.focus()
      } else {
        submitButton.click()
      }
    }
  })

  loginForm.addEventListener("submit", async (e) => {
    e.preventDefault()
    const email = document.getElementById("email").value
    const password = document.getElementById("password").value
    setLoadingState(true)
    hideError()
    try {
      const response = await fetch("/admin-system/API/login.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ email, password }),
      })
      const data = await response.json()
      if (response.ok && data.success) {
        localStorage.setItem("csrf_token", data.csrf_token)
        localStorage.setItem("user_id", data.user_id)
        localStorage.setItem("user_type", data.user_type)
        localStorage.setItem("user_email", data.email)
        localStorage.setItem("user_name", `${data.first_name} ${data.last_name}`)
        showSuccessState()
        setTimeout(() => {
          window.location.href = "dashboard.php"
        }, 1000)
      } else {
        setLoadingState(false)
        showError(data.error || "Login failed")
      }
    } catch (error) {
      setLoadingState(false)
      showError("Network error. Please try again.")
    }
  })

  function validateInput(input) {
    const isValid = input.checkValidity()
    const parent = input.parentElement
    parent.classList.remove("valid", "invalid")
    if (input.value) {
      if (isValid) {
        parent.classList.add("valid")
      } else {
        parent.classList.add("invalid")
      }
    }
  }

  function setLoadingState(loading) {
    const btnText = submitButton.querySelector(".btn-text")
    const btnSpinner = submitButton.querySelector(".btn-spinner")

    if (loading) {
      submitButton.classList.add("loading")
      submitButton.disabled = true
      btnText.textContent = "Signing In..."
      btnSpinner.style.display = "block"
      inputs.forEach((input) => {
        input.disabled = true
      })
    } else {
      submitButton.classList.remove("loading")
      submitButton.disabled = false
      btnText.textContent = "Sign In"
      btnSpinner.style.display = "none"
      inputs.forEach((input) => {
        input.disabled = false
      })
    }
  }

  function showSuccessState() {
    const btnText = submitButton.querySelector(".btn-text")
    const btnSpinner = submitButton.querySelector(".btn-spinner")

    submitButton.classList.remove("loading")
    submitButton.classList.add("success")
    btnText.textContent = "✓ Success!"
    btnSpinner.style.display = "none"
    submitButton.style.background = "var(--green-accent)"
    const card = document.querySelector(".login-card")
    card.style.transform = "scale(1.02)"
    card.style.boxShadow = "0 8px 32px rgba(90, 124, 101, 0.18)"
  }

  function showError(message) {
    errorMessage.textContent = message
    errorMessage.style.display = "block"
    const card = document.querySelector(".login-card")
    card.style.animation = "shake 0.5s ease-in-out"
    setTimeout(() => {
      card.style.animation = ""
    }, 500)
  }

  function hideError() {
    errorMessage.style.display = "none"
  }

  // Add shake animation keyframes dynamically
  const style = document.createElement("style")
  style.textContent = `
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
      20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
    .form-group.valid .form-input {
      border-color: var(--green-accent);
      box-shadow: 0 0 0 2px rgba(90, 124, 101, 0.15);
    }
    .form-group.invalid .form-input {
      border-color: var(--red-accent);
      box-shadow: 0 0 0 2px rgba(197, 83, 74, 0.15);
    }
    .form-group.focused .form-label {
      color: var(--gold-accent);
      transform: translateY(-2px);
    }
  `
  document.head.appendChild(style)
})

// Password toggle function
function togglePassword() {
  const passwordInput = document.getElementById("password")
  const eyeIcon = document.querySelector(".eye-icon")
  const eyeOffIcon = document.querySelector(".eye-off-icon")

  if (passwordInput.type === "password") {
    passwordInput.type = "text"
    eyeIcon.style.display = "none"
    eyeOffIcon.style.display = "block"
  } else {
    passwordInput.type = "password"
    eyeIcon.style.display = "block"
    eyeOffIcon.style.display = "none"
  }
}
