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
  <title>CABS KOREAN - Authentic Korean BBQ</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;700&family=Nanum+Pen+Script&family=Noto+Sans+KR:wght@300;400;700&display=swap" rel="stylesheet">
  <link rel="icon" href="cabs.png" type="image/png">

  <style>
    :root {
      --primary: #ff7eb3;
      --secondary: #ff0844;
      --dark: #121212;
      --light: #e0e0e0;
      --gray: #333333;
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
    body::before {
      content: "";
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-image: 
        radial-gradient(circle at 10px 10px, rgba(255, 126, 179, 0.1) 2px, transparent 3px),
        radial-gradient(circle at 30px 30px, rgba(255, 126, 179, 0.1) 2px, transparent 3px);
      background-size: 40px 40px;
      z-index: -1;      
    }

         
    .navbar {
      background: rgba(18, 18, 18, 0.9);
      padding: 1rem 2rem;
      position: fixed;
      top: 0;
      width: 100%;
      z-index: 1000;
      backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(255, 126, 179, 0.2);
      position: relative;
    }

    .navbar::after {
      content: "";
      position: absolute;
      bottom: 0;
      left: 0;
      height: 2px;      
      width: 100%;
      background: linear-gradient(to right, var(--primary), var(--secondary));
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
      font-size: 1.8rem;
      font-weight: 700;
      text-decoration: none;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      background-clip: text;
      -webkit-background-clip: text;
      color: transparent;
    }

    #navbar__logo img {
      height: 40px;
      margin-right: 10px;
      border-radius: 50%;
    }

    .navbar__menu {
      display: flex;
      list-style: none;
      align-items: center;
    }

    .navbar__item {
      margin: 0 1rem;
    }

    .navbar__links {
      color: var(--light);
      text-decoration: none;
      font-size: 1.1rem;
      transition: color 0.3s;
    }

    .navbar__links:hover {
      color: var(--primary);
    }

    .button {
      padding: 0.6rem 1.2rem;
      border-radius: 6px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s;
      margin-left: 0.8rem;
    }

    .button:first-child {
      background: transparent;
      border: 1px solid var(--primary);
      color: var(--primary);
    }

    .button:last-child {
      background: linear-gradient(to right, var(--primary), var(--secondary));
      color: white;
    }

    .button:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(255, 126, 179, 0.4);
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

         
    .main {
      min-height: 78vh;
      display: flex;
      align-items: center;
      padding: 8rem 2rem 4rem;
      position: relative;
      overflow: hidden;
      padding-top: 50px;
      padding-bottom: 50px;
    }

    .main::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-image: 
        radial-gradient(circle at 10px 10px, rgba(255, 126, 179, 0.1) 2px, transparent 3px),
        radial-gradient(circle at 30px 30px, rgba(255, 126, 179, 0.1) 2px, transparent 3px);
      background-size: 40px 40px;
      z-index: 0;
    }

    .main__container {
      max-width: 1200px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 3rem;
      align-items: center;
      position: relative;
      z-index: 1;      
    }

    .main__content {
      z-index: 2;
    }

    .main__content h1 {
      font-size: 4rem;
      margin-bottom: 1rem;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      background-clip: text;
      -webkit-background-clip: text;
      color: transparent;
    }

    .main__content h1::after {
      content: "한국 바베큐";
      display: block;
      font-family: 'Nanum Pen Script', cursive;
      font-size: 2.5rem;
      margin-top: -1.6rem;
      color: var(--light);
      opacity: 1;
    }

    .main__content h2 {
      font-size: 2.5rem;
      margin-bottom: 0rem;
      color: var(--light);
      font-family: 'Nanum Pen Script', cursive;
    }

    .main__content p {
      font-size: 1.2rem;
      margin-bottom: 2rem;
      opacity: 0.9;
    }

    .cta-button {
      display: inline-block;
      padding: 0.8rem 2rem;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      color: white;
      border-radius: 30px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s;
      position: relative;
      overflow: hidden;
      border: none;
      font-family: 'Kumbh Sans', sans-serif;
    }

    .cta-button:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 20px rgba(255, 126, 179, 0.3);
    }

         
    .carousel {
      position: relative;
      width: 100%;
      max-width: 600px;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.5);
      z-index: 1;
    }

    .carousel__img-container {
      display: flex;
      transition: transform 0.5s ease;
      width: 100%;
      height: 400px;
    }

    .carousel__img {
      min-width: 100%;
      object-fit: cover;
      border-radius: 12px;
    }

    .carousel__btn {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background: rgba(178, 0, 74, 0.8);
      color: white;
      border: none;
      border-radius:30%;
      width: 40px;
      height: 40px;
      font-size: 1.2rem;
      cursor: pointer;
      transition: all 0.3s;
      z-index: 10;
    }

    .carousel__btn:hover {
      background: rgba(0, 0, 0, 0.5);
    }

    .carousel__btn--prev {
      left: 15px;
    }

    .carousel__btn--next {
      right: 15px;
    }

         
    .floating-element {
      position: absolute;
      opacity: 0.1;
      z-index: 0;      
    }

    .floating-element:nth-child(1) {
      width: 300px;
      height: 300px;
      background: linear-gradient(45deg, var(--primary), var(--secondary));
      border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
      top: 10%;
      left: 5%;
      animation: float 2s ease-in-out infinite;
    }

    .floating-element:nth-child(2) {
      width: 200px;
      height: 200px;
      background: linear-gradient(45deg, var(--secondary), var(--primary));
      border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
      bottom: 15%;
      right: 10%;
      animation: float 3s ease-in-out infinite reverse;
    }

    @keyframes float {
      0% { transform: translateY(0) rotate(0deg); }
      50% { transform: translateY(-50px) rotate(5deg); }
      100% { transform: translateY(0) rotate(0deg); }
    }

         
    .footer {
      background: rgba(18, 18, 18, 0.9);
      padding: 2rem;
      text-align: center;
      border-top: 1px solid rgba(255, 126, 179, 0.2);
      position: relative;
    }

    .footer::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      height: 2px;      
      width: 100%;
      background: linear-gradient(to right, var(--primary), var(--secondary));
    }

    .footer__container {
      max-width: 1200px;
      margin: 0 auto;
    }

    .footer p {
      color: var(--light);
      opacity: 0.7;
    }

         
    @media (max-width: 992px) {
      .main__container {
        grid-template-columns: 1fr;
        text-align: center;
      }

      .main__content h1 {
        font-size: 3rem;
      }

      .main__content h1::after {
        font-size: 2rem;
      }

      .main__content h2 {
        font-size: 1rem;
      }

      .carousel {
        margin: 0 auto;
      }
    }

    @media (max-width: 768px) {
      .navbar__menu {
        display: none;
      }

      .main {
        padding-top: 6rem;
      }

      .main__content h1 {
        font-size: 2.5rem;
      }

      .main__content h1::after {
        font-size: 1.5rem;
      }

      .main__content h2 {
        font-size: 2rem;
      }
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
    #navbar__logo:hover {
      transform: scale(1.05);
    }

    #navbar__logo img {
      margin-right: 10px;
      border-radius: 50%;
      transition: all 0.3s;
    }

    #navbar__logo:hover img {
      transform: rotate(15deg);
    }
  </style>
</head>

<body>
         <div class="floating-element"></div>
  <div class="floating-element"></div>

  <nav class="navbar">
    <div class="navbar__container">
      <a href="landing.php" id="navbar__logo">
        <img src="images/cabs.png" alt="CABS KOREAN Logo">CABS
      </a>
      
      <ul class="navbar__menu">
        <li class="navbar__item">
          <a href="menu_landing.php" class="navbar__links">Menu</a>
        </li>
        <li class="navbar__item">
          <a href="location.php" class="navbar__links">Locations</a>
        </li>
          <li class="navbar__btn">
            <a href="login.php" class="login-button">Login</a>
            <a href="register.php" class="login-button">Register</a>
          </li>
      </ul>
    </div>
  </nav>

  <div class="main">
    <div class="main__container">
      <div class="main__content">
        <h1>CABS KOREAN</h1>
        <h2>UNLIMITED SAMGYUPSAL EXPERIENCE</h2>
        <p>Authentic Korean BBQ with premium ingredients, <br>unforgettable flavors, and sizzling table-side grilling.</p>
        <a href="menu_landing.php" class="cta-button">Explore Menu</a>
      </div>

      <div class="carousel">
        <div class="carousel__img-container">
          <img src="images/carousel3.jpg" alt="CABS Korean Restaurant" class="carousel__img">
          <img src="images/cabs2.jpg" alt="CABS Korean Interior" class="carousel__img">
          <img src="images/carousel2.jpg" alt="CABS Korean BBQ" class="carousel__img">
        </div>
        <button class="carousel__btn carousel__btn--prev" onclick="moveSlide(-1)">&#10094;</button>
        <button class="carousel__btn carousel__btn--next" onclick="moveSlide(1)">&#10095;</button>
      </div>
    </div>
  </div>

  <footer class="footer">
    <div class="footer__container">
      <p>&copy; <?php echo date('Y'); ?> CABS KOREAN. All Rights Reserved.</p>
    </div>
  </footer>

  <script>
    // Carousel functionality
    let currentSlide = 0;
    const slides = document.querySelectorAll('.carousel__img');
    const slideCount = slides.length;
    const carouselContainer = document.querySelector('.carousel__img-container');
    let slideInterval;

    function moveSlide(direction) {
      currentSlide += direction;
      
      if (currentSlide < 0) {
        currentSlide = slideCount - 1;
      } else if (currentSlide >= slideCount) {
        currentSlide = 0;
      }
      
      carouselContainer.style.transform = `translateX(-${currentSlide * 100}%)`;
      resetInterval();
    }

    function resetInterval() {
      clearInterval(slideInterval);
      slideInterval = setInterval(() => moveSlide(1), 4000);
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
      moveSlide(0);
      slideInterval = setInterval(() => moveSlide(1), 4000);
      
      // Pause on hover
      const carousel = document.querySelector('.carousel');
      carousel.addEventListener('mouseenter', () => clearInterval(slideInterval));
      carousel.addEventListener('mouseleave', resetInterval);
    });
  </script>
</body>
</html>