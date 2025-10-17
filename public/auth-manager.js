// Frontend Authentication Manager
class AuthManager {
    constructor() {
        this.currentUser = null;
        this.isInitialized = false;
    }

    async initialize() {
        if (this.isInitialized) return;
        
        try {
            await this.checkServerAuthentication();
            this.isInitialized = true;
        } catch (error) {
            console.error('Auth initialization failed:', error);
            this.currentUser = null;
        }
    }

    async checkServerAuthentication() {
        try {
            const response = await fetch('API/checkCredential.php', {
                method: 'GET',
                credentials: 'include', // Include cookies
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success && data.authenticated) {
                this.currentUser = {
                    id: data.user_id,
                    name: `${data.first_name} ${data.last_name}`,
                    email: data.email,
                    role: data.user_type,
                    isLoggedIn: true
                };
                
                // Store in localStorage for quick access
                localStorage.setItem('currentUser', JSON.stringify(this.currentUser));
                
                return this.currentUser;
            } else {
                this.currentUser = null;
                localStorage.removeItem('currentUser');
                return null;
            }
        } catch (error) {
            console.error('Authentication check failed:', error);
            this.currentUser = null;
            localStorage.removeItem('currentUser');
            return null;
        }
    }

    isAuthenticated() {
        return this.currentUser && this.currentUser.isLoggedIn;
    }

    hasRole(role) {
        return this.isAuthenticated() && this.currentUser.role === role;
    }

    isAdmin() {
        return this.hasRole('admin');
    }

    isArtist() {
        return this.hasRole('artist');
    }

    async logout() {
        try {
            // Call server logout
            await fetch('API/logout.php', {
                method: 'POST',
                credentials: 'include'
            });
        } catch (error) {
            console.error('Logout request failed:', error);
        }
        
        // Clear local state regardless
        this.currentUser = null;
        localStorage.removeItem('currentUser');
        
        // Redirect to home
        window.location.href = 'index.php';
    }

    requireAuthentication() {
        if (!this.isAuthenticated()) {
            window.location.href = `login.php?redirect=${encodeURIComponent(window.location.pathname)}`;
            return false;
        }
        return true;
    }

    requireRole(role) {
        if (!this.requireAuthentication()) return false;
        
        if (!this.hasRole(role)) {
            window.location.href = 'error403.php';
            return false;
        }
        return true;
    }

    requireAdmin() {
        return this.requireRole('admin');
    }
}

// Global auth instance
window.authManager = new AuthManager();

// Initialize on page load
document.addEventListener('DOMContentLoaded', async function() {
    await window.authManager.initialize();
    
    // Update UI based on authentication state
    if (typeof updateUserInterface === 'function') {
        updateUserInterface();
    }
});

// Protect admin pages
if (window.location.pathname.includes('admin-')) {
    document.addEventListener('DOMContentLoaded', function() {
        if (!window.authManager.requireAdmin()) {
            return; // Already redirected
        }
    });
}
