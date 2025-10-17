# Yadawity Modern Navbar Component

A professional, modern navbar component with glassmorphism design, smooth animations, and role-based functionality for the Yadawity Gallery website.

## Features

### 🎨 Design
- **Modern Glassmorphism**: Translucent background with backdrop blur effects
- **Smooth Animations**: CSS transitions and keyframe animations
- **Responsive Design**: Adapts to all screen sizes
- **Professional Styling**: Uses Yadawity brand colors and fonts

### 🔧 Functionality
- **Role-based Interface**: Different UI for buyers and artists
- **Artist Portal**: Special dropdown section for artist users
- **Smart Search**: Enhanced search with suggestions and animations
- **Interactive Elements**: Hover effects, focus states, and micro-interactions
- **Mobile Responsive**: Mobile-friendly design with hamburger menu

### 👥 User Types
1. **Visitor/Guest**: Basic navigation and login options
2. **Buyer**: Full shopping features (cart, wishlist, orders)
3. **Artist**: All buyer features + artist portal access

## Files Structure

```
components/Navbar/
├── navbar.html          # HTML structure
├── navbar.css           # Styling and animations
├── navbar.js            # JavaScript functionality
└── README.md           # This documentation
```

## Usage

### 1. Include the Files
```html
<!-- In your HTML head -->
<link rel="stylesheet" href="./components/Navbar/navbar.css">

<!-- Before closing body tag -->
<script src="./components/Navbar/navbar.js"></script>
```

### 2. HTML Structure
Copy the navbar HTML structure from `navbar.html` into your page.

### 3. Logo Setup
The navbar uses the logo image from `./image/Logo.png`. Make sure this file exists in your project.

## Role Management

### Setting User Role
```javascript
// Simulate login as buyer
window.NavbarController.simulateLogin('buyer');

// Simulate login as artist  
window.NavbarController.simulateLogin('artist');

// Logout
window.NavbarController.logout();
```

### Artist Portal Features
When a user is logged in as an artist, they get additional dropdown options:
- Artist Dashboard
- My Portfolio  
- Sales Analytics
- Commissions

## Customization

### Colors
Modify the CSS variables in `navbar.css`:
```css
:root {
    --primary-brown: #6b4423;
    --secondary-brown: #8b5a2b;
    --gold-accent: #d4a574;
    --artist-accent: #9b59b6;
    /* ... other variables */
}
```

### Animation Speed
Adjust transition durations:
```css
.navbar {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
```

## JavaScript API

### Available Methods
```javascript
// Update cart count with animation
NavbarController.updateCartCount();

// Update wishlist count with animation  
NavbarController.updateWishlistCount();

// Show notification
NavbarController.showNotification('Message', 'success');

// Simulate user login
NavbarController.simulateLogin('artist' | 'buyer');

// Logout user
NavbarController.logout();
```

## Responsive Breakpoints

- **Desktop**: > 1200px - Full navbar with all features
- **Tablet**: 768px - 1200px - Condensed layout
- **Mobile**: < 768px - Hamburger menu, hidden search

## Browser Support

- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

## Performance Features

- **Optimized Animations**: Uses `transform` and `opacity` for smooth performance
- **Reduced Motion**: Respects user's motion preferences
- **Efficient Event Handling**: Debounced search and optimized scroll listeners
- **Modern CSS**: Uses CSS Grid and Flexbox for layout

## Accessibility

- **Keyboard Navigation**: Full keyboard support
- **Screen Readers**: Proper ARIA labels and semantic HTML
- **Focus Indicators**: Clear focus states for all interactive elements
- **High Contrast**: Maintains readability in all themes

## Demo Panel

The component includes a demo panel (for development) that allows testing different user roles:
- Login as Buyer
- Login as Artist
- Logout

Remove the demo script section in production.

## Integration Notes

1. **Logo Path**: Update the logo path in HTML to match your project structure
2. **Navigation Links**: Modify href attributes to match your page structure  
3. **User Data**: Replace localStorage simulation with your actual user management system
4. **Search Integration**: Connect the search functionality to your backend API
5. **Cart/Wishlist**: Integrate with your e-commerce system

## License

Part of the Yadawity Gallery project. All rights reserved.
