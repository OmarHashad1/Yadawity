<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/x-icon" href="./image/YAD-05.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>About Us - Yadawity Gallery</title>
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
      href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&display=swap"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
      integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    />
    <link rel="stylesheet" href="./components/Navbar/navbar.css" />`n    <link rel="stylesheet" href="./public/homePage.css" />
    <link rel="stylesheet" href="./public/aboutPage.css" />
    <link rel="stylesheet" href="./components/BurgerMenu/burger-menu.css" />
  </head>
  <body>
    <?php include './components/includes/navbar.php'; ?>

    <?php include './components/includes/burger-menu.php'; ?>
        
      </div>
    </div>

    <div class="mobileSearchOverlay" id="mobileSearchOverlay">
      <div class="mobileSearchContainer">
        <input
          type="text"
          placeholder="Search artists, artworks..."
          class="mobileSearchInput"
          id="mobileSearchInput"
        />
        <button class="mobileSearchClose" id="mobileSearchClose">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="searchSuggestions" id="mobileSearchSuggestions"></div>
    </div>

    <!-- Main Content -->
    <main class="aboutMain">
      <!-- Hero Section -->
      <section class="aboutHero">
        <div class="aboutHeroContainer">
          <div class="aboutHeroContent">
            <h1 class="aboutHeroTitle">About Yadawity Gallery</h1>
            <p class="aboutHeroSubtitle">
              Where Art Meets Heritage - A Legacy of Creative Excellence Since 1885
            </p>
            <div class="aboutHeroDivider"></div>
            <p class="aboutHeroDescription">
              Discover the story behind our century-old commitment to nurturing artistic talent, 
              preserving cultural heritage, and creating a bridge between traditional craftsmanship 
              and contemporary artistry.
            </p>
          </div>
          <div class="aboutHeroImage">
            <img src="./image/Artist-PainterLookingAtCamera.webp" alt="Yadawity Gallery Heritage" />
          </div>
        </div>
      </section>

      <!-- Our Story Section -->
      <section class="ourStory">
        <div class="container">
          <div class="storyContent">
            <div class="storyText">
              <h2 class="sectionTitle">Our Story</h2>
              <p class="storyParagraph">
                Founded in 2025, Yadawity Gallery began as a small atelier in the heart of the city, 
                where master craftsmen gathered to share their knowledge and showcase their finest works. 
                What started as a humble workshop has evolved into a prestigious gallery that celebrates 
                the intersection of traditional artistry and modern innovation.
              </p>
              <p class="storyParagraph">
                Over the decades, we have witnessed the transformation of artistic movements, 
                the evolution of techniques, and the emergence of new voices in the art world. 
                Through it all, our commitment has remained unchanged: to provide a platform 
                where exceptional talent can flourish and art lovers can discover extraordinary pieces.
              </p>
              <p class="storyParagraph">
                Today, Yadawity Gallery stands as a testament to the enduring power of art to 
                inspire, challenge, and transform. We continue to honor our heritage while 
                embracing the future, curating collections that speak to both tradition and innovation.
              </p>
            </div>
            <div class="storyImage">
              <img src="./image/Team image.jpeg" alt="Gallery Interior" />
            </div>
          </div>
        </div>
      </section>

   

      <!-- Values Section -->
      <section class="values">
        <div class="container">
          <h2 class="sectionTitle">Our Values</h2>
          <div class="valuesGrid">
            <div class="valueCard">
              <div class="valueIcon">
                <i class="fas fa-heart"></i>
              </div>
              <h4 class="valueTitle">Passion</h4>
              <p class="valueDescription">
                We believe that great art comes from the heart. Every piece in our collection 
                reflects the passion and dedication of its creator.
              </p>
            </div>
            <div class="valueCard">
              <div class="valueIcon">
                <i class="fas fa-star"></i>
              </div>
              <h4 class="valueTitle">Excellence</h4>
              <p class="valueDescription">
                We maintain the highest standards in everything we do, from curation to 
                customer service, ensuring an exceptional experience for all.
              </p>
            </div>
            <div class="valueCard">
              <div class="valueIcon">
                <i class="fas fa-users"></i>
              </div>
              <h4 class="valueTitle">Community</h4>
              <p class="valueDescription">
                We foster a supportive community where artists and art lovers can connect, 
                learn, and grow together in their artistic journey.
              </p>
            </div>
            <div class="valueCard">
              <div class="valueIcon">
                <i class="fas fa-leaf"></i>
              </div>
              <h4 class="valueTitle">Heritage</h4>
              <p class="valueDescription">
                We honor traditional techniques while embracing innovation, preserving 
                cultural heritage for future generations.
              </p>
            </div>
            <div class="valueCard">
              <div class="valueIcon">
                <i class="fas fa-handshake"></i>
              </div>
              <h4 class="valueTitle">Integrity</h4>
              <p class="valueDescription">
                We conduct our business with honesty, transparency, and respect for all 
                our artists, customers, and partners.
              </p>
            </div>
            <div class="valueCard">
              <div class="valueIcon">
                <i class="fas fa-lightbulb"></i>
              </div>
              <h4 class="valueTitle">Innovation</h4>
              <p class="valueDescription">
                We continuously seek new ways to showcase art, support artists, and 
                enhance the art discovery experience for our community.
              </p>
            </div>
          </div>
        </div>
      </section>

      <!-- Team Section -->
      <section class="team">
        <div class="container">
          <h2 class="sectionTitle">Meet Our Team</h2>
          <p class="teamDescription">
            Behind every great gallery is a passionate team dedicated to celebrating art and supporting artists.
          </p>
          <div class="teamGrid">
            <div class="teamCard">
              <div class="teamImage">
                <img src="./image/artist-sitting-on-the-floor.jpg" alt="Art Director" />
              </div>
              <div class="teamInfo">
                <h4 class="teamName">Sarah Al-Masri</h4>
                <p class="teamRole">Art Director & Curator</p>
                <p class="teamBio">
                  With over 15 years of experience in art curation, Sarah brings a keen eye 
                  for exceptional talent and a deep understanding of artistic movements.
                </p>
              </div>
            </div>
            <div class="teamCard">
              <div class="teamImage">
                <img src="./image/Artist-PainterLookingAtCamera.webp" alt="Gallery Manager" />
              </div>
              <div class="teamInfo">
                <h4 class="teamName">Ahmed Hassan</h4>
                <p class="teamRole">Gallery Manager</p>
                <p class="teamBio">
                  Ahmed ensures that every visitor has an exceptional experience, managing 
                  our gallery operations with precision and care.
                </p>
              </div>
            </div>
            <div class="teamCard">
              <div class="teamImage">
                <img src="./image/photo-1554907984-15263bfd63bd.jpeg" alt="Artist Relations" />
              </div>
              <div class="teamInfo">
                <h4 class="teamName">Layla Mahmoud</h4>
                <p class="teamRole">Artist Relations Manager</p>
                <p class="teamBio">
                  Layla works closely with our artists, providing support and guidance 
                  to help them showcase their work and grow their careers.
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Call to Action Section -->
      <section class="aboutCta">
        <div class="container">
          <div class="ctaContent">
            <h2 class="ctaTitle">Join Our Artistic Journey</h2>
            <p class="ctaDescription">
              Whether you're an artist looking to showcase your work or an art lover seeking 
              unique pieces, we invite you to be part of our story.
            </p>
            <div class="ctaButtons">
              <a href="gallery.php" class="ctaButton ctaPrimary">Explore Gallery</a>
              <a href="support.php" class="ctaButton ctaSecondary">Get in Touch</a>
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- Footer -->
    <?php include './components/includes/footer.php'; ?>

    <script src="./components/BurgerMenu/burger-menu.js"></script>
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
