      document.addEventListener('DOMContentLoaded', function() {
        console.log('Login page loaded');
        
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const rememberMeInput = document.getElementById('remember_me');
        const loginBtn = document.getElementById('loginBtn');
        const form = document.getElementById('loginForm');
        
        // Create a named function for the login handler so we can remove it later
        function handleLogin(e) {
          e.preventDefault(); // Prevent default form submission
          
          const email = document.getElementById('email').value;
          const password = document.getElementById('password').value;
          const rememberMe = document.getElementById('remember_me').checked;
          
          // Show loading indicator
          Swal.fire({
            title: 'Signing you in...',
            text: 'Please wait while we authenticate your credentials',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            background: '#ffffff',
            color: '#2c3e50',
            customClass: {
              popup: 'animated fadeInDown',
              title: 'swal-title-custom',
              content: 'swal-content-custom'
            },
            didOpen: () => {
              Swal.showLoading();
            }
          });
          
          // Prepare form data
          const formData = new FormData();
          formData.append('email', email);
          formData.append('password', password);
          formData.append('remember_me', rememberMe.toString());
          
          // Send AJAX request to authentication endpoint
          fetch('./API/login.php?action=authenticate', {
            method: 'POST',
            body: formData
          })
          .then(response => {
            // Check if response is actually JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
              return response.text().then(text => {
                console.error('Non-JSON response:', text);
                throw new Error('Server returned non-JSON response: ' + text.substring(0, 100));
              });
            }

            // Parse JSON regardless of status code (200, 400, 401, etc.)
            return response.json();
          })
          .then(data => {
            if (data.success) {
              // Success - show welcome message and redirect
              Swal.fire({
                icon: 'success',
                title: 'Welcome Back!',
                text: data.message,
                confirmButtonText: 'Continue',
                confirmButtonColor: '#10b981',
                background: '#ffffff',
                color: '#2c3e50',
                iconColor: '#10b981',
                timer: 3000,
                timerProgressBar: true,
                showClass: {
                  popup: 'animate__animated animate__fadeInUp animate__faster'
                },
                hideClass: {
                  popup: 'animate__animated animate__fadeOutDown animate__faster'
                },
                customClass: {
                  popup: 'swal-success-popup',
                  title: 'swal-success-title',
                  content: 'swal-success-content',
                  confirmButton: 'swal-success-button'
                }
              }).then((result) => {
                // Redirect based on user type
                if (data.data && data.data.redirect_url) {
                  window.location.href = data.data.redirect_url;
                } else {
                  window.location.href = 'index.php'; // Default redirect
                }
              });
            } else {
              // Error - show error message
              Swal.fire({
                icon: 'error',
                title: 'Login Failed',
                text: data.message || 'Invalid email or password. Please try again.',
                confirmButtonText: 'Try Again',
                confirmButtonColor: '#ef4444',
                background: '#ffffff',
                color: '#2c3e50',
                iconColor: '#ef4444',
                showClass: {
                  popup: 'animate__animated animate__shakeX animate__faster'
                },
                hideClass: {
                  popup: 'animate__animated animate__fadeOutUp animate__faster'
                },
                customClass: {
                  popup: 'swal-error-popup',
                  title: 'swal-error-title',
                  content: 'swal-error-content',
                  confirmButton: 'swal-error-button'
                }
              });
            }
          })
          .catch(error => {
            console.error('Login error:', error);
            
            // More specific error messages based on error type
            let errorMessage = 'Unable to connect to the server. Please try again.';
            
            if (error.message.includes('HTTP error')) {
              errorMessage = 'Server error occurred. Please try again later.';
            } else if (error.message.includes('JSON')) {
              errorMessage = 'Invalid server response. Please contact support.';
            } else if (error.message.includes('Failed to fetch')) {
              errorMessage = 'Network connection failed. Please check your internet connection.';
            }
            
            Swal.fire({
              icon: 'error',
              title: 'Connection Error',
              text: errorMessage,
              confirmButtonText: 'Retry',
              confirmButtonColor: '#f59e0b',
              background: '#ffffff',
              color: '#2c3e50',
              iconColor: '#f59e0b',
              showClass: {
                popup: 'animate__animated animate__fadeInDown animate__faster'
              },
              hideClass: {
                popup: 'animate__animated animate__fadeOutUp animate__faster'
              },
              customClass: {
                popup: 'swal-warning-popup',
                title: 'swal-warning-title',
                content: 'swal-warning-content',
                confirmButton: 'swal-warning-button'
              }
            });
          });
        }
        
        // Attach the login handler to the form
        form.addEventListener('submit', handleLogin);
        
        // Forgot password handler
        document.querySelector('.forgot-password').addEventListener('click', function(e) {
          e.preventDefault();
          showForgotPasswordForm();
        });

        // Forgot password functionality
        function showForgotPasswordForm() {
          const formContainer = document.querySelector('.login-container form');
          
          // Remove the login event handler
          formContainer.removeEventListener('submit', handleLogin);
          
          formContainer.innerHTML = `
            <div class="form-group">
              <label for="reset-email">Email Address</label>
              <input type="email" id="reset-email" name="email" placeholder="Enter your email address" required />
            </div>
            <button type="submit" class="sign-in-btn" id="sendCodeBtn">Send Reset Code</button>
            <div class="back-buttons-container">
              <a href="#" class="back-button" onclick="showLoginForm()">Back to Login</a>
            </div>
          `;
          
          // Add the forgot password handler
          formContainer.addEventListener('submit', handleForgotPassword);
          
          // Update title
          document.querySelector('.welcome-subtitle').textContent = 'Enter your email address and we\'ll send you a code to reset your password';
        }

        // Function to restore the original login form
        function showLoginForm() {
          const formContainer = document.getElementById('loginForm');
          
          // Remove any existing event handlers
          formContainer.removeEventListener('submit', handleForgotPassword);
          
          // Restore original login form HTML with correct structure
          formContainer.innerHTML = `
            <div class="form-group">
              <label for="email">Email Address</label>
              <input type="text" id="email" name="email" placeholder="johndoe@example.com" />
            </div>

            <div class="form-group password-section">
              <label for="password">Password</label>
              <a href="#" class="forgot-password">Forgot password?</a>
              <div style="clear: both"></div>
              <div class="password-input-container">
                <input type="password" id="password" name="password" />
                <button type="button" class="password-toggle" tabindex="-1" onclick="togglePasswordVisibility('password')">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>

            <div class="form-group remember-me-section">
              <label class="remember-checkbox">
                <input type="checkbox" id="remember_me" name="remember_me" />
                <span class="checkmark"></span>
                Keep me logged in for 30 days
              </label>
            </div>

            <button type="submit" class="sign-in-btn" id="loginBtn">Login</button>
          `;
          
          // Re-attach the login handler
          formContainer.addEventListener('submit', handleLogin);
          
          // Re-attach forgot password link handler
          const forgotPasswordLink = formContainer.querySelector('.forgot-password');
          if (forgotPasswordLink) {
            forgotPasswordLink.addEventListener('click', function(e) {
              e.preventDefault();
              showForgotPasswordForm();
            });
          }
          
          // Update title
          document.querySelector('.welcome-subtitle').textContent = 'Sign in to your account to continue exploring authentic artworks and handcrafted creations';
        }

        function handleForgotPassword(e) {
          e.preventDefault();
          
          const email = document.getElementById('reset-email').value;
          
          // Show loading
          Swal.fire({
            title: 'Sending reset code...',
            text: 'Please wait while we send the code to your email',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading()
          });
          
          // Send request to forgot password API
          const formData = new FormData();
          formData.append('email', email);
          formData.append('action', 'send_code');
          
          fetch('./API/forgetPassword.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              showCodeVerificationForm(email);
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to send reset code',
                confirmButtonColor: '#ef4444'
              });
            }
          })
          .catch(error => {
            console.error('Forgot password error:', error);
            Swal.fire({
              icon: 'error',
              title: 'Connection Error',
              text: 'Unable to send reset code. Please try again.',
              confirmButtonColor: '#ef4444'
            });
          });
        }

        function showCodeVerificationForm(email) {
          const formContainer = document.querySelector('.login-container form');
          formContainer.innerHTML = `
            <div class="form-group">
              <label for="reset-code">Verification Code</label>
              <input type="text" id="reset-code" name="code" placeholder="Enter 6-digit code" maxlength="6" required />
              <small style="color: #666; font-size: 13px;">Check your email for the 6-digit verification code</small>
            </div>
            <button type="submit" class="sign-in-btn" id="verifyCodeBtn">Verify Code</button>
            <div class="back-buttons-container">
              <a href="#" class="back-button" onclick="showForgotPasswordForm()">Back to Email</a>
              <a href="#" class="back-button" onclick="showLoginForm()">Back to Login</a>
            </div>
          `;
          
          // Update form submission handler for code verification
          formContainer.removeEventListener('submit', handleForgotPassword);
          formContainer.addEventListener('submit', (e) => handleCodeVerification(e, email));
          
          Swal.fire({
            icon: 'success',
            title: 'Code Sent!',
            text: `A 6-digit verification code has been sent to ${email}`,
            confirmButtonColor: '#10b981'
          });
        }

        function handleCodeVerification(e, email) {
          e.preventDefault();
          
          const code = document.getElementById('reset-code').value;
          
          // Show loading
          Swal.fire({
            title: 'Verifying code...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading()
          });
          
          const formData = new FormData();
          formData.append('email', email);
          formData.append('code', code);
          formData.append('action', 'verify_code');
          
          fetch('./API/forgetPassword.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              showPasswordResetForm(email, code);
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Invalid Code',
                text: data.message || 'The verification code is incorrect or expired',
                confirmButtonColor: '#ef4444'
              });
            }
          })
          .catch(error => {
            console.error('Code verification error:', error);
            Swal.fire({
              icon: 'error',
              title: 'Verification Error',
              text: 'Unable to verify code. Please try again.',
              confirmButtonColor: '#ef4444'
            });
          });
        }

        function showPasswordResetForm(email, code) {
          const formContainer = document.querySelector('.login-container form');
          formContainer.innerHTML = `
            <div class="form-group">
              <label for="new-password">New Password</label>
              <div class="password-input-container">
                <input type="password" id="new-password" name="password" placeholder="Enter new password" required />
                <button type="button" class="password-toggle" onclick="togglePasswordVisibility('new-password')">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
              <div class="password-strength" id="passwordStrength">
                <div class="strength-bar">
                  <div class="strength-fill"></div>
                </div>
                <span class="strength-text">Password strength</span>
              </div>
            </div>
            <div class="form-group">
              <label for="confirm-password">Confirm New Password</label>
              <div class="password-input-container">
                <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm new password" required />
                <button type="button" class="password-toggle" onclick="togglePasswordVisibility('confirm-password')">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
              <div class="match-indicator" id="matchIndicator"></div>
            </div>
            <button type="submit" class="sign-in-btn" id="resetPasswordBtn">Reset Password</button>
            <div class="back-buttons-container">
              <a href="#" class="back-button" onclick="showLoginForm()">Back to Login</a>
            </div>
          `;
          
          // Add CSS for password indicators
          addPasswordResetStyles();
          
          // Add password strength and match validation
          const newPasswordField = document.getElementById('new-password');
          const confirmPasswordField = document.getElementById('confirm-password');
          
          newPasswordField.addEventListener('input', function() {
            updatePasswordStrength(this.value);
            checkPasswordMatch();
            validatePasswordInput(this);
          });
          
          confirmPasswordField.addEventListener('input', function() {
            checkPasswordMatch();
            validatePasswordConfirmInput(this);
          });
          
          // Update form submission handler for password reset
          formContainer.removeEventListener('submit', formContainer.lastSubmissionHandler);
          const resetHandler = (e) => handlePasswordReset(e, email, code);
          formContainer.addEventListener('submit', resetHandler);
          formContainer.lastSubmissionHandler = resetHandler;
          
          Swal.fire({
            icon: 'success',
            title: 'Code Verified!',
            text: 'Please enter your new password',
            confirmButtonColor: '#10b981'
          });
        }

        function handlePasswordReset(e, email, code) {
          e.preventDefault();
          
          const newPassword = document.getElementById('new-password').value;
          const confirmPassword = document.getElementById('confirm-password').value;
          
          if (newPassword !== confirmPassword) {
            Swal.fire({
              icon: 'error',
              title: 'Password Mismatch',
              text: 'Passwords do not match. Please try again.',
              confirmButtonColor: '#ef4444'
            });
            return;
          }
          
          if (newPassword.length < 8) {
            Swal.fire({
              icon: 'error',
              title: 'Weak Password',
              text: 'Password must be at least 8 characters long.',
              confirmButtonColor: '#ef4444'
            });
            return;
          }
          
          // Show loading
          Swal.fire({
            title: 'Resetting password...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading()
          });
          
          const formData = new FormData();
          formData.append('email', email);
          formData.append('code', code);
          formData.append('password', newPassword);
          formData.append('action', 'reset_password');
          
          fetch('./API/forgetPassword.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              Swal.fire({
                icon: 'success',
                title: 'Password Reset Successful!',
                text: 'Your password has been reset. You can now login with your new password.',
                confirmButtonColor: '#10b981'
              }).then(() => {
                showLoginForm();
              });
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Reset Failed',
                text: data.message || 'Failed to reset password',
                confirmButtonColor: '#ef4444'
              });
            }
          })
          .catch(error => {
            console.error('Password reset error:', error);
            Swal.fire({
              icon: 'error',
              title: 'Reset Error',
              text: 'Unable to reset password. Please try again.',
              confirmButtonColor: '#ef4444'
            });
          });
        }

        // Utility functions
        window.showLoginForm = function() {
          const formContainer = document.getElementById('loginForm');
          
          // Remove any existing event listeners
          const newForm = formContainer.cloneNode(false);
          formContainer.parentNode.replaceChild(newForm, formContainer);
          
          // Restore the original login form HTML
          newForm.innerHTML = `
            <div class="form-group">
              <label for="email">Email Address</label>
              <input type="text" id="email" name="email" placeholder="johndoe@example.com" />
            </div>

            <div class="form-group password-section">
              <label for="password">Password</label>
              <a href="#" class="forgot-password">Forgot password?</a>
              <div style="clear: both"></div>
              <div class="password-input-container">
                <input type="password" id="password" name="password" />
                <button type="button" class="password-toggle" tabindex="-1" onclick="togglePasswordVisibility('password')">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>

            <div class="form-group remember-me-section">
              <label class="remember-checkbox">
                <input type="checkbox" id="remember_me" name="remember_me" />
                <span class="checkmark"></span>
                Keep me logged in for 30 days
              </label>
            </div>

            <button type="submit" class="sign-in-btn" id="loginBtn">Login</button>
          `;
          
          // Re-attach the login form handler
          newForm.addEventListener('submit', handleLogin);
          
          // Re-attach forgot password click handler
          const forgotPasswordLink = newForm.querySelector('.forgot-password');
          if (forgotPasswordLink) {
            forgotPasswordLink.addEventListener('click', function(e) {
              e.preventDefault();
              showForgotPasswordForm();
            });
          }
          
          // Update the page title
          document.querySelector('.welcome-subtitle').textContent = 
            'Sign in to your account to continue exploring authentic artworks and handcrafted creations';
        }

        window.togglePasswordVisibility = function(fieldId) {
          const field = document.getElementById(fieldId);
          const icon = field.nextElementSibling.querySelector('i');
          
          if (field.type === 'password') {
            field.type = 'text';
            icon.className = 'fas fa-eye-slash';
          } else {
            field.type = 'password';
            icon.className = 'fas fa-eye';
          }
        }

        function updatePasswordStrength(password) {
          const strengthBar = document.querySelector('.strength-fill');
          const strengthText = document.querySelector('.strength-text');
          
          if (!strengthBar || !strengthText) return;
          
          let strength = 0;
          let text = 'Very Weak';
          let color = '#ff4444';
          
          if (password.length >= 8) strength += 20;
          if (password.match(/[a-z]/)) strength += 20;
          if (password.match(/[A-Z]/)) strength += 20;
          if (password.match(/[0-9]/)) strength += 20;
          if (password.match(/[^a-zA-Z0-9]/)) strength += 20;
          
          if (strength >= 80) {
            text = 'Very Strong';
            color = '#22c55e';
          } else if (strength >= 60) {
            text = 'Strong';
            color = '#84cc16';
          } else if (strength >= 40) {
            text = 'Medium';
            color = '#f59e0b';
          } else if (strength >= 20) {
            text = 'Weak';
            color = '#f97316';
          }
          
          strengthBar.style.width = strength + '%';
          strengthBar.style.backgroundColor = color;
          strengthText.textContent = text;
        }

        function checkPasswordMatch() {
          const newPassword = document.getElementById('new-password');
          const confirmPassword = document.getElementById('confirm-password');
          const matchIndicator = document.getElementById('matchIndicator');
          
          if (!newPassword || !confirmPassword || !matchIndicator) return;
          
          if (confirmPassword.value === '') {
            matchIndicator.textContent = '';
            return;
          }
          
          if (newPassword.value === confirmPassword.value) {
            matchIndicator.textContent = '✓ Passwords match';
            matchIndicator.style.color = '#22c55e';
          } else {
            matchIndicator.textContent = '✗ Passwords do not match';
            matchIndicator.style.color = '#ef4444';
          }
        }

        function addPasswordResetStyles() {
          const style = document.createElement('style');
          style.textContent = `
            .password-input-container {
              position: relative;
            }
            .password-toggle {
              position: absolute;
              right: 12px;
              top: 50%;
              transform: translateY(-50%);
              background: none;
              border: none;
              color: #666;
              cursor: pointer;
              padding: 4px;
            }
            .password-strength {
              margin-top: 8px;
            }
            .strength-bar {
              width: 100%;
              height: 4px;
              background: #e0e0e0;
              border-radius: 2px;
              overflow: hidden;
            }
            .strength-fill {
              height: 100%;
              transition: all 0.3s ease;
              border-radius: 2px;
            }
            .strength-text {
              font-size: 12px;
              margin-top: 4px;
              display: block;
            }
            .match-indicator {
              font-size: 12px;
              margin-top: 4px;
              font-weight: 500;
            }
            .back-to-login {
              text-align: center;
              margin-top: 15px;
            }
            .back-to-login a {
              color: #8b6f47;
              text-decoration: none;
              font-size: 14px;
            }
            .back-to-login a:hover {
              text-decoration: underline;
            }
            /* Password validation indicators */
            .password-input-container input.valid {
              border-color: #5a7c65;
              box-shadow: 0 0 0 3px rgba(90, 124, 101, 0.1);
            }
            .password-input-container input.invalid {
              border-color: #8b3a3a;
              box-shadow: 0 0 0 3px rgba(139, 58, 58, 0.1);
            }
            .password-input-container input.valid:focus {
              border-color: #5a7c65;
              box-shadow: 0 0 0 3px rgba(90, 124, 101, 0.2);
            }
            .password-input-container input.invalid:focus {
              border-color: #8b3a3a;
              box-shadow: 0 0 0 3px rgba(139, 58, 58, 0.2);
            }
          `;
          document.head.appendChild(style);
        }

        // Password validation functions
        function validatePasswordInput(input) {
          const password = input.value;
          const minLength = 8;
          const hasLower = /[a-z]/.test(password);
          const hasUpper = /[A-Z]/.test(password);
          const hasNumber = /[0-9]/.test(password);
          const hasSpecial = /[^a-zA-Z0-9]/.test(password);
          
          const isValid = password.length >= minLength && hasLower && hasUpper && hasNumber;
          
          if (password === '') {
            resetValidation(input);
          } else if (isValid) {
            setValid(input);
          } else {
            setInvalid(input);
          }
        }

        function validatePasswordConfirmInput(input) {
          const newPassword = document.getElementById('new-password');
          const isValid = input.value === newPassword.value && input.value !== '';
          
          if (input.value === '') {
            resetValidation(input);
          } else if (isValid) {
            setValid(input);
          } else {
            setInvalid(input);
          }
        }

        function setValid(input) {
          input.classList.remove('invalid');
          input.classList.add('valid');
        }

        function setInvalid(input) {
          input.classList.remove('valid');
          input.classList.add('invalid');
        }

        function resetValidation(input) {
          input.classList.remove('valid', 'invalid');
        }
      });