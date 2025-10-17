# Yadawity Navbar Component

A professional, reusable navbar component for the Yadawity artist platform with classical design aesthetics.

## Features

- **Responsive Design**: Works seamlessly across all device sizes
- **Classical Aesthetic**: Brown and gold color scheme with elegant typography
- **Custom Logo**: Hand-drawn butterfly logo integrated as SVG
- **Search Functionality**: Both desktop and mobile search interfaces
- **User Actions**: Cart, wishlist, and user account dropdown
- **Smooth Animations**: Hover effects and transitions
- **Mobile-First**: Collapsible mobile menu with overlay search

## File Structure

```
components/
├── navbar.html       # Complete navbar HTML structure
├── navbar.css        # All navbar styles and responsive design
├── navbar.js         # Interactive functionality
└── README.md         # This documentation
```

## Quick Integration

### Method 1: Include as Standalone Component

1. **Copy the navbar files** to your project's `components/` directory
2. **Include the navbar** in your HTML pages:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Page - Yadawity</title>
    
    <!-- Required External Dependencies -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Navbar Component Styles -->
    <link rel="stylesheet" href="components/navbar.css">
    
    <!-- Your page styles -->
    <link rel="stylesheet" href="your-styles.css">
</head>
<body>
    <!-- Include Navbar -->
    <div id="navbar-container"></div>
    
    <!-- Your page content -->
    <main style="margin-top: 90px;">
        <!-- Your content here -->
    </main>
    
    <!-- Scripts -->
    <script src="components/navbar.js"></script>
    <script>
        // Load navbar component
        fetch('components/navbar.html')
            .then(response => response.text())
            .then(html => {
                document.getElementById('navbar-container').innerHTML = html;
                // Navbar will auto-initialize
            })
            .catch(error => console.error('Error loading navbar:', error));
    </script>
</body>
</html>
```

### Method 2: Direct HTML Include

For simpler projects, you can directly copy the navbar HTML from `navbar.html` into your pages:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Dependencies same as above -->
    <link rel="stylesheet" href="components/navbar.css">
</head>
<body>
    <!-- Paste the navbar HTML directly here -->
    <!-- Copy from components/navbar.html -->
    
    <main style="margin-top: 90px;">
        <!-- Your content -->
    </main>
    
    <script src="components/navbar.js"></script>
</body>
</html>
```

## Customization

### Update Navigation Links

Edit the navigation menu in `navbar.html`:

```html
<div class="nav-menu">
    <a href="index.html" class="nav-link active">
        <i class="fas fa-home"></i> Home
    </a>
    <a href="explore.html" class="nav-link">
        <i class="fas fa-compass"></i> Explore
    </a>
    <!-- Add your own links -->
    <a href="your-page.html" class="nav-link">
        <i class="fas fa-your-icon"></i> Your Page
    </a>
</div>
```

### Update Logo and Branding

To change the logo, replace the SVG in the `.logo-icon` section:

```html
<div class="logo-icon">
    <!-- Replace this SVG with your own -->
    <svg viewBox="0 0 24 24" fill="currentColor">
        <!-- Your SVG path here -->
    </svg>
</div>
```

### Customize Colors

All colors are defined as CSS custom properties in `navbar.css`:

```css
:root {
    --primary-brown: #6b4423;    /* Main brand color */
    --secondary-brown: #8b5a2b;  /* Secondary brand color */
    --gold-accent: #d4a574;      /* Gold accents */
    --light-gold: #f4e6d3;       /* Light gold backgrounds */
    /* Modify these to match your brand */
}
```

## JavaScript API

The navbar exposes several functions for dynamic updates:

```javascript
// Update cart count
YadawityNavbar.updateCartCount(5);

// Update wishlist count
YadawityNavbar.updateWishlistCount(12);

// Set active page programmatically
YadawityNavbar.setActivePage();

// Trigger search programmatically
YadawityNavbar.performSearch('landscape art');

// Open mobile search overlay
YadawityNavbar.openMobileSearch();
```

## Browser Support

- **Modern Browsers**: Chrome 60+, Firefox 60+, Safari 12+, Edge 79+
- **Mobile**: iOS Safari 12+, Chrome Mobile 60+
- **Features**: CSS Grid, Flexbox, CSS Custom Properties, ES6+

## Dependencies

### Required External Libraries:
- **Google Fonts**: Playfair Display, Inter
- **Font Awesome**: 6.4.0+ for icons

### Optional Enhancements:
- **Intersection Observer**: For scroll animations (auto-polyfilled)
- **CSS Backdrop Filter**: For glass morphism effects

## Performance Notes

- **CSS**: ~15KB minified
- **JavaScript**: ~8KB minified
- **Load Time**: < 100ms on modern connections
- **Mobile Optimized**: Touch-friendly interactions

## Integration Examples

### With Static Sites
```html
<!-- Simple include for static HTML sites -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('components/navbar.html')
        .then(response => response.text())
        .then(html => {
            document.querySelector('#navbar').innerHTML = html;
        });
});
</script>
```

### With React/Vue/Angular
For modern frameworks, you can convert the HTML structure to components and import the CSS:

```javascript
// Example React integration
import './components/navbar.css';

function App() {
    return (
        <div>
            <YadawityNavbar />
            <main style={{marginTop: '90px'}}>
                {/* Your content */}
            </main>
        </div>
    );
}
```

## Troubleshooting

### Common Issues:

1. **Navbar overlapping content**: Add `margin-top: 90px` to your main content
2. **Icons not showing**: Ensure Font Awesome CSS is loaded
3. **Fonts not loading**: Check Google Fonts connection
4. **Mobile menu not working**: Verify JavaScript is loaded after HTML

### Debug Mode:
Add this to your console to check navbar status:
```javascript
console.log('Navbar loaded:', !!window.YadawityNavbar);
```

## Support

For questions or customization help, refer to the main Yadawity project documentation or create an issue in the project repository.
