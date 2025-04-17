<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CABS KOREAN - Locations</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
  <link rel="icon" href="cabs.png" type="image/png">

  <style>
    :root {
      --primary: #ff7eb3;
      --secondary: #ff0844;
      --dark: #121212;
      --light: #e0e0e0;
      --gray: #333333;
      --gold: #FFD700;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Kumbh Sans', sans-serif;
    }

    body {
      background-color: var(--dark);
      color: var(--light);
      line-height: 1.6;
      overflow-x: hidden;
    }

         
    body::-webkit-scrollbar {
      width: 8px;
    }

    body::-webkit-scrollbar-track {
      background: var(--dark);
    }

    body::-webkit-scrollbar-thumb {
      background: linear-gradient(var(--primary), var(--secondary));
      border-radius: 10px;
    }

         
    .navbar {
      background: rgba(18, 18, 18, 0.9);
      padding: 1rem 2rem;
      position: sticky;
      top: 0;
      width: 100%;
      z-index: 1000;
      backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(255, 126, 179, 0.2);
      transition: all 0.3s ease;
    }

    .navbar.scrolled {
      background: rgba(18, 18, 18, 0.95);
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
    }

    .navbar__container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      max-width: 1200px;
      margin: 0 auto;
      width: 100%;
    }

    #navbar__logo {
      display: flex;
      align-items: center;
      font-size: 2rem;
      font-weight: 700;
      text-decoration: none;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      background-clip: text;
      -webkit-background-clip: text;
      color: transparent;
      transition: all 0.3s;
    }

    #navbar__logo:hover {
      transform: scale(1.05);
    }

    #navbar__logo img {
      height: 50px;
      margin-right: 10px;
      border-radius: 50%;
      transition: all 0.3s;
    }

    #navbar__logo:hover img {
      transform: rotate(15deg);
    }

    .navbar__menu {
      display: flex;
      list-style: none;
      align-items: center;
    }

    .navbar__item {
      margin: 0 1rem;
      position: relative;
    }

    .navbar__links {
      color: var(--light);
      text-decoration: none;
      font-size: 1.1rem;
      transition: color 0.3s;
      position: relative;
      padding: 0.5rem 0;
    }

    .navbar__links:hover {
      color: var(--primary);
    }

    .navbar__links.active::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      height: 2px;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      animation: underlineGrow 0.3s ease-out;
    }

    @keyframes underlineGrow {
      from { transform: scaleX(0); }
      to { transform: scaleX(1); }
    }

    .button {
      padding: 0.6rem 1.2rem;
      border-radius: 30px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s;
      margin-left: 0.8rem;
      display: inline-block;
    }

    .button:first-child {
      background: transparent;
      border: 2px solid var(--primary);
      color: var(--primary);
    }

    .button:last-child {
      background: linear-gradient(to right, var(--primary), var(--secondary));
      color: white;
    }

    .button:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(255, 126, 179, 0.4);
    }

    .logout-btn {
      background: rgba(255, 0, 0, 0.2);
      border: 2px solid rgba(255, 0, 0, 0.5);
      color: #ff6b6b;
    }

    .logout-btn:hover {
      background: rgba(255, 0, 0, 0.3);
    }

         
    .hero {
      height: 50vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                  url('images/korean-restaurant.jpg') center/cover no-repeat;
      position: relative;
      overflow: hidden;
      margin-bottom: 3rem;
    }

    .hero::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle, transparent 20%, var(--dark) 70%);
      z-index: 1;
    }

    .hero-content {
      position: relative;
      z-index: 2;
      text-align: center;
      padding: 0 2rem;
    }

    .hero h1 {
      font-size: 4rem;
      margin-bottom: 1rem;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      background-clip: text;
      -webkit-background-clip: text;
      color: transparent;
      font-family: 'Playfair Display', serif;
      text-shadow: 2px 2px 10px rgba(0, 0, 0, 0.3);
    }

    .hero p {
      font-size: 1.2rem;
      max-width: 700px;
      margin: 0 auto 2rem;
      color: rgba(255, 255, 255, 0.9);
    }

         
    .main {
      padding: 2rem 2rem 4rem;
      min-height: 100vh;
      position: relative;
    }

    .main__container {
      max-width: 1200px;
      margin: 0 auto;
      position: relative;
    }

    .locations__header {
      text-align: center;
      margin-bottom: 4rem;
    }

    .locations__header h1 {
      font-size: 3.5rem;
      margin-bottom: 1.5rem;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      background-clip: text;
      -webkit-background-clip: text;
      color: transparent;
      font-family: 'Playfair Display', serif;
      position: relative;
      display: inline-block;
    }

    .locations__header h1::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 0;
      width: 100%;
      height: 3px;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      border-radius: 3px;
    }

    .locations__header p {
      font-size: 1.2rem;
      opacity: 0.8;
      max-width: 700px;
      margin: 0 auto;
    }

    .locations__content {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 4rem;
      align-items: center;
    }

    .location__info {
      padding: 2rem;
      background: rgba(255, 255, 255, 0.05);
      border-radius: 16px;
      border: 1px solid rgba(255, 255, 255, 0.1);
      transition: all 0.3s;
    }

    .location__info:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(255, 126, 179, 0.2);
      border-color: rgba(255, 126, 179, 0.3);
    }

    .location__info h2 {
      font-size: 2.5rem;
      margin-bottom: 1.5rem;
      color: var(--primary);
      font-family: 'Playfair Display', serif;
      position: relative;
      display: inline-block;
    }

    .location__info h2::after {
      content: '';
      position: absolute;
      bottom: -5px;
      left: 0;
      width: 80px;
      height: 2px;
      background: var(--primary);
    }

    .location__info p {
      margin-bottom: 1.5rem;
      font-size: 1.1rem;
      line-height: 1.7;
      color: rgba(255, 255, 255, 0.8);
    }

    .location__details {
      margin-top: 2rem;
    }

    .detail__item {
      display: flex;
      align-items: center;
      margin-bottom: 1.5rem;
      padding: 0.8rem;
      border-radius: 8px;
      transition: all 0.3s;
    }

    .detail__item:hover {
      background: rgba(255, 126, 179, 0.1);
      transform: translateX(5px);
    }

    .detail__item i {
      color: var(--primary);
      margin-right: 1.5rem;
      font-size: 1.5rem;
      width: 30px;
      text-align: center;
      transition: all 0.3s;
    }

    .detail__item:hover i {
      transform: scale(1.2);
    }

    .detail__item span {
      font-size: 1.1rem;
    }

    .map-container {
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
      height: 100%;
      transition: all 0.3s;
      position: relative;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .map-container:hover {
      transform: translateY(-5px);
      box-shadow: 0 25px 50px rgba(0, 0, 0, 0.6);
    }

    .map-container h2 {
      text-align: center;
      padding: 1.5rem;
      background: linear-gradient(to right, rgba(255, 126, 179, 0.1), rgba(255, 8, 68, 0.1));
      margin-bottom: 0;
      font-size: 1.5rem;
      color: var(--light);
    }

    .map-container iframe {
      width: 100%;
      height: 700px;
      border: none;
      display: block;
    }

         
    .branch-features {
      margin-top: 4rem;
      text-align: center;
    }

    .branch-features h2 {
      font-size: 2.5rem;
      margin-bottom: 2rem;
      color: var(--light);
      font-family: 'Playfair Display', serif;
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 2rem;
      margin-top: 2rem;
      margin-bottom: 5rem;
    }

    .feature-card {
      background: rgba(255, 255, 255, 0.05);
      border-radius: 12px;
      padding: 2rem;
      transition: all 0.3s;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .feature-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 30px rgba(255, 126, 179, 0.2);
      border-color: rgba(255, 126, 179, 0.3);
    }

    .feature-card i {
      font-size: 2.5rem;
      color: var(--primary);
      margin-bottom: 1.5rem;
      display: inline-block;
      transition: all 0.3s;
    }

    .feature-card:hover i {
      transform: scale(1.2);
      color: var(--secondary);
    }

    .feature-card h3 {
      font-size: 1.5rem;
      margin-bottom: 1rem;
      color: var(--light);
    }

    .feature-card p {
      color: rgba(255, 255, 255, 0.7);
      font-size: 1rem;
    }

         
    .floating-element {
      position: absolute;
      opacity: 0.1;
      z-index: 0;
      pointer-events: none;
    }

    .floating-element:nth-child(1) {
      width: 400px;
      height: 400px;
      background: linear-gradient(45deg, var(--primary), var(--secondary));
      border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
      top: 10%;
      left: 5%;
      animation: float 8s ease-in-out infinite;
      filter: blur(30px);
    }

    .floating-element:nth-child(2) {
      width: 300px;
      height: 300px;
      background: linear-gradient(45deg, var(--secondary), var(--primary));
      border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
      bottom: 15%;
      right: 10%;
      animation: float 10s ease-in-out infinite reverse;
      filter: blur(40px);
    }

    @keyframes float {
      0% { transform: translateY(0) rotate(0deg); }
      50% { transform: translateY(-30px) rotate(5deg); }
      100% { transform: translateY(0) rotate(0deg); }
    }

         
    .footer {
      background: linear-gradient(to right, rgba(18, 18, 18, 0.9), rgba(40, 40, 40, 0.9));
      padding: 3rem 2rem;
      text-align: center;
      border-top: 1px solid rgba(255, 126, 179, 0.2);
      position: relative;
    }

    .footer::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 1px;
      background: linear-gradient(to right, transparent, var(--primary), transparent);
    }

    .footer__container {
      max-width: 1200px;
      margin: 0 auto;
    }

    .footer p {
      color: var(--light);
      opacity: 0.8;
      margin-bottom: 1rem;
    }

    .social-links {
      display: flex;
      justify-content: center;
      gap: 1.5rem;
      margin-bottom: 1.5rem;
    }

    .social-links a {
      color: var(--light);
      font-size: 1.5rem;
      transition: all 0.3s;
    }

    .social-links a:hover {
      color: var(--primary);
      transform: translateY(-3px);
    }

         
    .direction-btn {
      display: inline-block;
      margin-top: 2rem;
      padding: 0.8rem 1.8rem;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      color: white;
      border-radius: 30px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s;
      box-shadow: 0 5px 15px rgba(255, 126, 179, 0.4);
    }

    .direction-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 25px rgba(255, 126, 179, 0.6);
    }

    .direction-btn i {
      margin-right: 0.5rem;
    }

         
    @media (max-width: 1200px) {
      .hero h1 {
        font-size: 3.5rem;
      }
      
      .locations__header h1 {
        font-size: 3rem;
      }
    }

    @media (max-width: 992px) {
      .locations__content {
        grid-template-columns: 1fr;
        gap: 3rem;
      }
      
      .map-container {
        order: -1;
      }
      
      .hero {
        height: 40vh;
      }
    }

    @media (max-width: 768px) {
      .navbar__menu {
        display: none;
      }

      .locations__header h1 {
        font-size: 2.5rem;
      }
      
      .location__info h2 {
        font-size: 2rem;
      }
      
      .hero h1 {
        font-size: 2.5rem;
      }
      
      .hero p {
        font-size: 1rem;
      }
    }

    @media (max-width: 576px) {
      .main {
        padding: 1rem 1rem 3rem;
      }
      
      .hero {
        height: 35vh;
        margin-bottom: 2rem;
      }
      
      .hero h1 {
        font-size: 2rem;
      }
      
      .map-container iframe {
        height: 350px;
      }
      
      .locations__header h1 {
        font-size: 2.2rem;
      }
      
      .location__info h2 {
        font-size: 1.8rem;
      }
    }

         
    .fade-in {
      animation: fadeIn 1s ease-in;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
         
    .navbar {
      background: rgba(18, 18, 18, 0);
      padding: 1rem 2rem;
      position: sticky;
      top: 0;
      width: 100%;
      z-index: 1000;
      backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(255, 126, 179, 0.2);
    }

    .navbar__container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      max-width: 1200px;
      margin: 0 auto;
      width: 100%;
      margin-left: 10rem;
    }

    #navbar__logo {
      display: flex;
      align-items: center;
      font-size: 1.8rem;
      font-weight: 700;
      text-decoration: none;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      background-clip: text;
      -webkit-background-clip: text;
      color: transparent;
    }

    #navbar__logo img {
      height: 70px;
      margin-right: 10px;
      border-radius: 50%;
    }

    .navbar__menu {
      display: flex;
      list-style: none;
      align-items: center;
      margin-right: -21.5rem;
    }

    .navbar__item {
      margin: 0 1rem;
    }

    .navbar__links {
      color: var(--light);
      text-decoration: none;
      font-size: 1.1rem;
      transition: color 0.3s;
      position: relative;
    }

    .navbar__links:hover {
      color: var(--primary);
    }

    .logout-btn {
      background: rgba(255, 0, 0, 0.2);
      border: 1px solid rgba(255, 0, 0, 0.5);
      color: #ff6b6b;
    }

    .logout-btn:hover {
      background: rgba(255, 0, 0, 0.3);
    }
    .login-button {
      padding: 0.6rem 1.2rem;
      border-radius: 100px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s;
      margin-left: 0.8rem;
      width: 140px;      
      display: inline-block;      
      text-align: center;      
      box-sizing: border-box;
    }

    .login-button:first-child {
      background: transparent;
      border: 2px solid rgb(253, 47, 95);
      color: rgb(253, 47, 95);
    }

    .login-button:last-child {
      background: linear-gradient(to right, var(--primary), var(--secondary));
      color: white;
    }

    .login-button:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(255, 126, 179, 0.4);
    }
  </style>
</head>

<body>
         <div class="floating-element"></div>
  <div class="floating-element"></div>

         <nav class="navbar" id="navbar">
    <div class="navbar__container">
      <a href="landing.php" id="navbar__logo">
        <img src="images/cabs.png" alt="CABS KOREAN Logo">CABS
      </a>
      
      <ul class="navbar__menu">
        <li class="navbar__item">
          <a href="menu_landing.php" class="navbar__links">Menu</a>
        </li>
        <li class="navbar__item">
          <a href="location.php" class="navbar__links active">Location</a>
        </li>
        <?php if($isLoggedIn): ?>
          <li class="navbar__item">
            <a href="dashboard.php" class="navbar__links">Dashboard</a>
          </li>
          <li class="navbar__btn">
            <a href="logout.php" class="button logout-btn">Logout</a>
          </li>
        <?php else: ?>
          <li class="navbar__btn">
            <a href="login.php" class="login-button">Login</a>
            <a href="register.php" class="login-button">Register</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </nav>

         <section class="hero">
    <div class="hero-content">
      <h1 class="fade-in">Find Our Restaurant</h1>
      <p class="fade-in">Discover the authentic taste of Korea at our Arayat branch</p>
    </div>
  </section>

         <div class="main">
    <div class="main__container">
      <div class="locations__header">
        <h1>OUR LOCATION</h1>
        <p>Visit us at our Arayat branch for an unforgettable Korean BBQ experience with premium ingredients and authentic flavors</p>
      </div>

      <div class="locations__content">
        <div class="map-container fade-in">
          <h2>Find Us Here</h2>
          <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d123270.38696961218!2d120.61790861472937!3d15.092324466096771!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3396e5f798d7cb91%3A0x6ab2cdea9d0e4712!2sCabs%20Korean%20Restaurant!5e0!3m2!1sen!2sph!4v1726732236700!5m2!1sen!2sph" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>

        <div class="location__info fade-in">
          <h2>Arayat Branch</h2>
          <p>Experience the best Korean BBQ in Pampanga at our Arayat location. Our restaurant offers a cozy and authentic atmosphere where you can enjoy unlimited samgyupsal with premium ingredients and traditional Korean side dishes.</p>
          
          <div class="location__details">
            <div class="detail__item">
              <i class="fas fa-map-marker-alt"></i>
              <span>123 Magalang Rd, Arayat, Pampanga, Philippines</span>
            </div>
            <div class="detail__item">
              <i class="fas fa-phone-alt"></i>
              <span>(045) 123-4567</span>
            </div>
            <div class="detail__item">
              <i class="fas fa-clock"></i>
              <span>Open Daily: 10:00 AM - 09:00 PM</span>
            </div>
            <div class="detail__item">
              <i class="fas fa-parking"></i>
              <span>Free parking available</span>
            </div>
            <div class="detail__item">
              <i class="fas fa-subway"></i>
              <span>Accessible by public transportation</span>
            </div>
          </div>
          
          <a href="https://maps.google.com?q=Cabs+Korean+Restaurant+Arayat" class="direction-btn" target="_blank">
            <i class="fas fa-directions"></i> Get Directions
          </a>
        </div>
      </div>

                 <div class="branch-features fade-in">
        <h2>Branch Features</h2>
        <div class="features-grid">
          <div class="feature-card">
            <i class="fas fa-utensils"></i>
            <h3>Authentic Cuisine</h3>
            <p>Experience traditional Korean flavors prepared by our expert chefs using authentic recipes</p>
          </div>
          <div class="feature-card">
            <i class="fas fa-wifi"></i>
            <h3>Free WiFi</h3>
            <p>Stay connected with our high-speed internet access while enjoying your meal</p>
          </div>
          <div class="feature-card">
            <i class="fas fa-users"></i>
            <h3>Group Friendly</h3>
            <p>Spacious seating arrangements perfect for family gatherings and group dinners</p>
          </div>
          <div class="feature-card">
            <i class="fas fa-wheelchair"></i>
            <h3>Accessible</h3>
            <p>Wheelchair accessible entrance and facilities for all our customers</p>
          </div>
        </div>
      </div>
    </div>
  </div>

         <footer class="footer">
    <div class="footer__container">
      <div class="social-links">
        <a href="#"><i class="fab fa-facebook-f"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-tiktok"></i></a>
      </div>
      <p>123 Magalang Rd, Arayat, Pampanga, Philippines</p>
      <p>Open daily from 10:00 AM to 9:00 PM</p>
      <p>&copy; <?php echo date('Y'); ?> CABS KOREAN. All Rights Reserved.</p>
    </div>
  </footer>

  <script>
    // Navbar scroll effect
    window.addEventListener('scroll', function() {
      const navbar = document.getElementById('navbar');
      if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    });

    // Initialize animations
    document.addEventListener('DOMContentLoaded', function() {
      const elements = document.querySelectorAll('.fade-in');
      elements.forEach((element, index) => {
        element.style.animationDelay = `${index * 0.2}s`;
      });
    });

    // Add hover effect to location details
    document.querySelectorAll('.detail__item').forEach(item => {
      item.addEventListener('mouseenter', function() {
        const icon = this.querySelector('i');
        icon.style.transform = 'scale(1.2)';
      });
      
      item.addEventListener('mouseleave', function() {
        const icon = this.querySelector('i');
        icon.style.transform = 'scale(1)';
      });
    });
  </script>
</body>
</html>