   // Testimonial data
   const testimonials = [
    {
        name: "Lord Edmund Blackwood",
        title: "Art Collector",
        text: "The therapeutic arts program has provided profound healing through the mastery of classical techniques.",
        image: "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop&crop=face"
    },
    {
        name: "Dr. Sarah Chen",
        title: "Art Therapist",
        text: "This program has revolutionized how I approach healing through creative expression. The results speak for themselves.",
        image: "https://images.unsplash.com/photo-1494790108755-2616b69ad1a8?w=150&h=150&fit=crop&crop=face"
    },
    {
        name: "Marcus Rodriguez",
        title: "Creative Director",
        text: "The fusion of traditional techniques with modern therapeutic approaches created a transformative experience for our team.",
        image: "https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&h=150&fit=crop&crop=face"
    },
    {
        name: "Isabella Morrison",
        title: "Museum Curator",
        text: "An extraordinary journey through the healing power of art. The program's methodology is both innovative and deeply rooted in tradition.",
        image: "https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150&h=150&fit=crop&crop=face"
    },
    {
        name: "Prof. James Wellington",
        title: "Fine Arts Professor",
        text: "Never before have I witnessed such a profound connection between artistic practice and personal transformation.",
        image: "https://images.unsplash.com/photo-1560250097-0b93528c311a?w=150&h=150&fit=crop&crop=face"
    },
    {
        name: "Maria Santos",
        title: "Wellness Coach",
        text: "The program's holistic approach to creativity and healing has opened new pathways for my clients' recovery and growth.",
        image: "https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&h=150&fit=crop&crop=face"
    },
    {
        name: "David Kim",
        title: "Gallery Owner",
        text: "This therapeutic arts initiative has created a ripple effect of positive change throughout our entire artistic community.",
        image: "https://images.unsplash.com/photo-1507591064344-4c6ce005b128?w=150&h=150&fit=crop&crop=face"
    },
    {
        name: "Elena Vasquez",
        title: "Art Student",
        text: "The program helped me discover not just my artistic voice, but also my path to emotional healing and self-discovery.",
        image: "https://images.unsplash.com/photo-1489424731084-a5d8b219a5bb?w=150&h=150&fit=crop&crop=face"
    },
    {
        name: "Robert Thompson",
        title: "Philanthropist",
        text: "Investing in this program was one of the most meaningful decisions I've made. The impact on participants is truly remarkable.",
        image: "https://images.unsplash.com/photo-1463453091185-61582044d556?w=150&h=150&fit=crop&crop=face"
    }
];

class TestimonialCarousel {
    constructor() {
        this.init();
    }

    init() {
        this.createCards();
    }

    createCards() {
        const track = document.getElementById('carouselTrack');
        track.innerHTML = '';

        // Create multiple sets for seamless infinite scroll
        const sets = 4; // Create 4 sets of cards for smooth looping
        for (let set = 0; set < sets; set++) {
            testimonials.forEach((testimonial, index) => {
                const card = document.createElement('div');
                card.className = 'testimonial-card-item';
                card.setAttribute('tabindex', '0');
                card.innerHTML = `
                    <div class="testimonial-quote-icon">"</div>
                    <div class="testimonial-profile-section">
                        <img src="${testimonial.image}" alt="${testimonial.name}" class="testimonial-profile-image" 
                             onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMzAiIGN5PSIzMCIgcj0iMzAiIGZpbGw9IiNGOEY2RjMiLz4KPHN2ZyB4PSIxNSIgeT0iMTUiIHdpZHRoPSIzMCIgaGVpZ2h0PSIzMCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSIjOTk5Ij4KPHA+dGggZD0iTTEyIDEyYzIuMjEgMCA0LTEuNzkgNC00cy0xLjc5LTQtNC00LTQgMS43OS00IDQgMS43OSA0IDQgNHptMCAyYy0yLjY3IDAtOCAxLjM0LTggNHYyaDE2di0yYzAtMi42Ni01LjMzLTQtOC00eiIvPgo8L3N2Zz4KPC9zdmc+'">
                        <div class="testimonial-profile-info">
                            <div class="testimonial-profile-name">${testimonial.name}</div>
                            <div class="testimonial-profile-title">${testimonial.title}</div>
                        </div>
                    </div>
                    <div class="testimonial-text-content">${testimonial.text}</div>
                    <div class="testimonial-star-rating">
                        <span class="testimonial-star">★</span>
                        <span class="testimonial-star">★</span>
                        <span class="testimonial-star">★</span>
                        <span class="testimonial-star">★</span>
                        <span class="testimonial-star">★</span>
                    </div>
                `;
                track.appendChild(card);
            });
        }
    }
}

// Initialize carousel when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new TestimonialCarousel();
});