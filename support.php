<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support - Yadawity Gallery</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&display=swap"
      rel="stylesheet"
    />
    <link rel="icon" type="image/x-icon" href="./image/YAD-05.ico">
     <meta name="description" content="Find and book your perfect local art gallery experience. Explore physical galleries, meet artists, and immerse yourself in local art culture with Yadawity.">
    <meta name="keywords" content="local art gallery, art galleries, in-person art, local artists, art tours, art events, book gallery, Yadawity">
    <meta name="author" content="Yadawity">
    <meta property="og:title" content="Yadawity - Local Galleries">
    <meta property="og:description" content="Find and book your perfect local art gallery experience. Explore physical galleries, meet artists, and immerse yourself in local art culture with Yadawity.">
    <meta property="og:image" content="/image/darker_image_25_percent.jpeg">
    <meta property="og:type" content="website">
    <meta property="og:url" content="http://localhost/localGallery.php">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Yadawity - Local Galleries">
    <meta name="twitter:description" content="Find and book your perfect local art gallery experience. Explore physical galleries, meet artists, and immerse yourself in local art culture with Yadawity.">
    <meta name="twitter:image" content="/image/darker_image_25_percent.jpeg">
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
      integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    />
    <link rel="stylesheet" href="./components/Navbar/navbar.css" />
    <link rel="stylesheet" href="./public/homePage.css" />
    <link rel="stylesheet" href="./public/support.css" />
    <link rel="stylesheet" href="./components/BurgerMenu/burger-menu.css" />
</head>
<body>
    <?php include './components/includes/navbar.php'; ?>

    <?php include './components/includes/burger-menu.php'; ?>

    <!-- Hero Section -->
    <section class="supportHero">
        <div class="heroBackground">
            <img src="./image/AllentownArtMuseum_Gallery01_DiscoverLehighValley_2450c76f-4de5-402c-a060-d0a8ff3b1d37.jpg" alt="Art Gallery" class="heroBackgroundImg">
            <div class="heroOverlay"></div>
        </div>
        <div class="heroContent">
            <div class="heroText">
                <h1 class="heroTitle">How can we help you?</h1>
                <p class="heroSubtitle">Your artistic journey is our priority. Find answers, get support, or connect with our dedicated team.</p>
                
                <!-- Main Search Bar (from sessionsTherapy.php) -->
                <div class="main-search">
                    <div class="search-wrapper">
                        <input 
                            type="text"
                            class="search-input"
                            id="heroSearchInput"
                            placeholder="Search for help articles, guides, or FAQs..."
                            autocomplete="off"
                        >
                        <button class="search-btn" id="heroSearchBtn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Help Section -->
    <section class="quickHelp">
        <div class="container">
            <div class="quickHelpGrid">
                <div class="helpCard">
                    <div class="helpCardIcon">
                        <i class="fas fa-palette"></i>
                    </div>
                    <h3>Artist Support</h3>
                    <p>Get help with commissions, portfolios, and artist tools</p>
                    <a href="#" class="helpCardLink">Learn More <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="helpCard">
                    <div class="helpCardIcon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3>Order Help</h3>
                    <p>Track orders, returns, and purchase assistance</p>
                    <a href="#" class="helpCardLink">Learn More <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="helpCard">
                    <div class="helpCardIcon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3>Course Support</h3>
                    <p>Access courses, certificates, and learning materials</p>
                    <a href="#" class="helpCardLink">Learn More <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="helpCard">
                    <div class="helpCardIcon">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <h3>Account Help</h3>
                    <p>Manage your profile, settings, and subscriptions</p>
                    <a href="#" class="helpCardLink">Learn More <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faqSection">
        <div class="container">
            <div class="sectionHeader">
                <h2 class="sectionTitle">Frequently Asked Questions</h2>
                <p class="sectionSubtitle">Find quick answers to the most common questions about Yadawity Gallery</p>
            </div>
            <div class="faqContainer">
                <div class="faqItem">
                    <button class="faqQuestion">
                        <span>How do I commission a custom artwork?</span>
                        <i class="fas fa-plus"></i>
                    </button>
                    <div class="faqAnswer">
                        <p>To commission a custom artwork, browse our Featured Artists section, select an artist whose style resonates with you, and click "Request Commission." You'll be able to discuss your vision, timeline, and budget directly with the artist.</p>
                    </div>
                </div>
                <div class="faqItem">
                    <button class="faqQuestion">
                        <span>What payment methods do you accept?</span>
                        <i class="fas fa-plus"></i>
                    </button>
                    <div class="faqAnswer">
                        <p>We accept all major credit cards (Visa, MasterCard, American Express), PayPal, Apple Pay, Google Pay, and bank transfers for larger purchases. All transactions are secure and encrypted.</p>
                    </div>
                </div>
                <div class="faqItem">
                    <button class="faqQuestion">
                        <span>How do I enroll in art courses?</span>
                        <i class="fas fa-plus"></i>
                    </button>
                    <div class="faqAnswer">
                        <p>Visit our Courses section, choose from beginner to advanced levels, and click "Enroll Now." You'll get instant access to video lessons, downloadable resources, and our community forum for ongoing support.</p>
                    </div>
                </div>
                <div class="faqItem">
                    <button class="faqQuestion">
                        <span>What is your return and exchange policy?</span>
                        <i class="fas fa-plus"></i>
                    </button>
                    <div class="faqAnswer">
                        <p>We offer a 30-day return policy for original artworks in their original condition. Custom commissions are final sale. Courses can be refunded within 7 days if less than 25% of content has been accessed.</p>
                    </div>
                </div>
                <div class="faqItem">
                    <button class="faqQuestion">
                        <span>How do I become a featured artist on Yadawity?</span>
                        <i class="fas fa-plus"></i>
                    </button>
                    <div class="faqAnswer">
                        <p>Submit your portfolio through our Artist Application form. Our curatorial team reviews submissions monthly. We look for exceptional skill, unique style, and alignment with our gallery's artistic vision.</p>
                    </div>
                </div>
                <div class="faqItem">
                    <button class="faqQuestion">
                        <span>Do you ship internationally?</span>
                        <i class="fas fa-plus"></i>
                    </button>
                    <div class="faqAnswer">
                        <p>Yes, we ship worldwide! International shipping costs and delivery times vary by location. All artworks are carefully packaged and insured. Customs duties may apply depending on your country.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contactSection">
        <div class="container">
            <div class="contactGrid">
                <div class="contactInfo">
                    <div class="sectionHeader">
                        <h2 class="sectionTitle">Get in Touch</h2>
                        <p class="sectionSubtitle">Our dedicated support team is here to help you with any questions or concerns.</p>
                    </div>
                    
                    <div class="contactMethods">
                        <div class="contactMethod">
                            <div class="contactMethodIcon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contactMethodContent">
                                <h4>Email Support</h4>
                                <p>yadawity@gmail.com</p>
                                <span class="responseTime">Response within 24 hours</span>
                            </div>
                        </div>
                        <div class="contactMethod">
                            <div class="contactMethodIcon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="contactMethodContent">
                                <h4>Phone Support</h4>
                                <p>+20 1099359953</p>
                                <span class="responseTime">Mon-Fri, 9AM-6PM GMT+2</span>
                            </div>
                        </div>
                        <div class="contactMethod">
                            <div class="contactMethodIcon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <div class="contactMethodContent">
                                <h4>Live Chat</h4>
                                <p>Available 24/7</p>
                                <span class="responseTime">Instant responses</span>
                            </div>
                        </div>
                        <div class="contactMethod">
                            <div class="contactMethodIcon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contactMethodContent">
                                <h4>Visit Our Gallery</h4>
                                <p>Cairo, Egypt</p>
                                <span class="responseTime">By appointment only</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="contactForm">
                    <div class="formHeader">
                        <h3>Send us a Message</h3>
                        <p>Fill out the form below and we'll get back to you as soon as possible.</p>
                    </div>
                    <form id="supportContactForm" class="supportForm">
                        <div class="formRow">
                            <div class="formGroup">
                                <label for="firstName">First Name</label>
                                <input type="text" id="firstName" name="firstName" required>
                            </div>
                            <div class="formGroup">
                                <label for="lastName">Last Name</label>
                                <input type="text" id="lastName" name="lastName" required>
                            </div>
                        </div>
                        <div class="formGroup">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="formGroup">
                            <label for="subject">How can we help?</label>
                            <select id="subject" name="subject" required>
                                <option value="">Choose a topic</option>
                                <option value="commission">Custom Commission</option>
                                <option value="order">Order Support</option>
                                <option value="course">Course Help</option>
                                <option value="artist">Artist Application</option>
                                <option value="technical">Technical Issue</option>
                                <option value="billing">Billing Question</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="formGroup">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" rows="6" placeholder="Tell us more about how we can help you..." required></textarea>
                        </div>
                        <button type="submit" class="submitBtn">
                            <span>Send Message</span>
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Resources Section -->
    <section class="resourcesSection">
        <div class="container">
            <div class="sectionHeader">
                <h2 class="sectionTitle">Helpful Resources</h2>
                <p class="sectionSubtitle">Explore our comprehensive guides and documentation</p>
            </div>
            <div class="resourcesGrid">
                <div class="resourceCard">
                    <div class="resourceIcon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h4>Getting Started Guide</h4>
                    <p>New to Yadawity? Learn how to navigate our platform and discover amazing artworks.</p>
                    <a href="#" class="resourceLink">Read Guide</a>
                </div>
                <div class="resourceCard">
                    <div class="resourceIcon">
                        <i class="fas fa-paintbrush"></i>
                    </div>
                    <h4>Artist Handbook</h4>
                    <p>Everything artists need to know about joining and succeeding on our platform.</p>
                    <a href="#" class="resourceLink">Download PDF</a>
                </div>
                <div class="resourceCard">
                    <div class="resourceIcon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4>Safety & Security</h4>
                    <p>Learn about our security measures and how we protect your information.</p>
                    <a href="#" class="resourceLink">Learn More</a>
                </div>
                <div class="resourceCard">
                    <div class="resourceIcon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <h4>Full FAQ Database</h4>
                    <p>Browse our complete collection of frequently asked questions and answers.</p>
                    <a href="#" class="resourceLink">Browse All</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include './components/includes/footer.php'; ?>

    <script src="./components/BurgerMenu/burger-menu.js"></script>
    <script src="./public/support.js"></script>
    <script src="./app.js"></script>
    <script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/68ad7c376c34a5192ea60d8f/1j3iqqep5';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>
</body>
</html>
