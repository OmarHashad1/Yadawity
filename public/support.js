// Support Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('Support page JavaScript loaded');
    
    // FAQ Accordion functionality
    const faqQuestions = document.querySelectorAll('.faqQuestion');
    console.log('Found FAQ questions:', faqQuestions.length);
    
    faqQuestions.forEach((question, index) => {
        console.log('Setting up FAQ question', index);
        question.addEventListener('click', function() {
            console.log('FAQ question clicked:', index);
            
            const faqItem = this.closest('.faqItem');
            const isActive = faqItem.classList.contains('active');
            const icon = this.querySelector('i');
            
            console.log('Current state - isActive:', isActive);
            
            // Close all FAQ items first
            document.querySelectorAll('.faqItem').forEach(item => {
                item.classList.remove('active');
                const itemIcon = item.querySelector('.faqQuestion i');
                if (itemIcon) {
                    itemIcon.classList.remove('fa-minus');
                    itemIcon.classList.add('fa-plus');
                }
            });
            
            // If this item wasn't active, open it
            if (!isActive) {
                faqItem.classList.add('active');
                if (icon) {
                    icon.classList.remove('fa-plus');
                    icon.classList.add('fa-minus');
                }
                console.log('FAQ opened');
            } else {
                console.log('FAQ closed');
            }
        });
    });
    
    // Hero search functionality
    const heroSearchBtn = document.getElementById('heroSearchBtn');
    const heroSearchInput = document.getElementById('heroSearchInput');
    
    if (heroSearchBtn && heroSearchInput) {
        heroSearchBtn.addEventListener('click', function() {
            const searchTerm = heroSearchInput.value.trim();
            if (searchTerm) {
                // Here you would implement actual search functionality
                console.log('Searching for:', searchTerm);
                // For now, just scroll to FAQ section
                const faqSection = document.querySelector('.faqSection');
                if (faqSection) {
                    faqSection.scrollIntoView({ 
                        behavior: 'smooth' 
                    });
                }
            }
        });
        
        heroSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                heroSearchBtn.click();
            }
        });
    }
    
    // Contact form handling
    const contactForm = document.getElementById('supportContactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });
            
            // Here you would send the data to your server
            console.log('Form submitted:', data);
            
            // Show success message (you can customize this)
            alert('Thank you for your message! We\'ll get back to you within 24 hours.');
            
            // Reset form
            this.reset();
        });
    }
    
    // Smooth scrolling for help card links
    const helpCardLinks = document.querySelectorAll('.helpCardLink');
    if (helpCardLinks.length > 0) {
        helpCardLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                // You can implement navigation to specific help sections here
                const helpCard = this.closest('.helpCard');
                if (helpCard) {
                    const helpTitle = helpCard.querySelector('h3');
                    if (helpTitle) {
                        console.log('Help card clicked:', helpTitle.textContent);
                    }
                }
            });
        });
    }
    
    // Animation on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observe elements for animations
    const animatedElements = document.querySelectorAll('.helpCard, .contactMethod, .resourceCard');
    if (animatedElements.length > 0) {
        animatedElements.forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    }
});
