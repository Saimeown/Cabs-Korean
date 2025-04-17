<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);

// Database connection with error handling
try {
    $conn = new mysqli("localhost", "root", "", "cabs_korean");
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Retrieve menu items with categories
    $menu_items = [];
    $sql = "SELECT m.*, c.name as category_name 
            FROM menu_items m
            JOIN menu_categories c ON m.category_id = c.id
            ORDER BY c.display_order, m.name";
    
    $result = $conn->query($sql);
    
    if ($result === false) {
        throw new Exception("Query failed: " . $conn->error);
    }

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $menu_items[$row['category_name']][] = $row;
        }
    } else {
        $empty_message = "Our menu is currently being updated. Please check back soon!";
    }
    
    $conn->close();
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "We're having trouble loading our menu. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CABS KOREAN - Our Menu</title>
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
      --success: #4CAF50;
      --error: #f44336;
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

         
    .hero {
      height: 40vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                  url('images/korean-food-bg.jpg') center/cover no-repeat;
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

    .menu__container {
      max-width: 1200px;
      margin: 0 auto;
      position: relative;
    }

    .menu__container h1 {
      text-align: center;
      font-size: 3.5rem;
      margin-bottom: 3rem;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      background-clip: text;
      -webkit-background-clip: text;
      color: transparent;
      font-family: 'Playfair Display', serif;
      position: relative;
      display: inline-block;
      left: 50%;
      transform: translateX(-50%);
    }

    .menu__container h1::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 0;
      width: 100%;
      height: 3px;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      border-radius: 3px;
    }

         
    .menu__section {
      margin-bottom: 5rem;
      position: relative;
    }

    .menu__section h2 {
      font-size: 2.5rem;
      margin-bottom: 2rem;
      padding-bottom: 0.5rem;
      color: var(--light);
      font-family: 'Playfair Display', serif;
      position: relative;
      display: inline-block;
    }

    .menu__section h2::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      height: 2px;
      background: linear-gradient(to right, var(--primary), var(--secondary));
    }

    .menu__items {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 2.5rem;
    }

    .menu__item {
      background: rgba(255, 255, 255, 0.05);
      border-radius: 16px;
      overflow: hidden;
      transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
      position: relative;
      border: 1px solid rgba(255, 255, 255, 0.1);
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .menu__item::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, rgba(255,126,179,0.1) 0%, rgba(255,8,68,0.1) 100%);
      opacity: 0;
      transition: opacity 0.3s;
    }

    .menu__item:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 30px rgba(255, 126, 179, 0.3);
      border-color: rgba(255, 126, 179, 0.3);
    }

    .menu__item:hover::before {
      opacity: 1;
    }

    .menu__item img {
      width: 100%;
      height: 220px;
      object-fit: cover;
      transition: transform 0.5s;
    }

    .menu__item:hover img {
      transform: scale(1.05);
    }

    .menu__item-content {
      padding: 1.5rem;
      position: relative;
      z-index: 1;
    }

    .menu__item h3 {
      font-size: 1.4rem;
      margin-bottom: 0.8rem;
      color: var(--light);
      font-weight: 600;
      position: relative;
      display: inline-block;
    }

    .menu__item h3::after {
      content: '';
      position: absolute;
      bottom: -5px;
      left: 0;
      width: 50px;
      height: 2px;
      background: var(--primary);
      transition: width 0.3s;
    }

    .menu__item:hover h3::after {
      width: 80px;
    }

    .menu__item p {
      color: rgba(255, 255, 255, 0.7);
      margin-bottom: 1.2rem;
      font-size: 0.95rem;
      line-height: 1.7;
    }

    .menu__item span {
      display: inline-block;
      font-weight: bold;
      font-size: 1.3rem;
      color: var(--primary);
      margin-top: 0.5rem;
      font-family: 'Kumbh Sans', sans-serif;
    }

         
    .popular-badge {
      position: absolute;
      top: 15px;
      right: 15px;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      color: white;
      padding: 0.3rem 1rem;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: bold;
      z-index: 2;
      box-shadow: 0 3px 10px rgba(255, 8, 68, 0.3);
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.05); }
      100% { transform: scale(1); }
    }

         
    .spicy-indicator {
      display: inline-block;
      margin-left: 10px;
      color: #ff4d4d;
      font-size: 0.9rem;
    }

         
    .error-message, .empty-message {
      text-align: center;
      padding: 2rem;
      border-radius: 8px;
      margin: 2rem 0;
      font-size: 1.1rem;
      max-width: 800px;
      margin-left: auto;
      margin-right: auto;
    }

    .error-message {
      background: rgba(244, 67, 54, 0.1);
      color: var(--error);
      border-left: 4px solid var(--error);
    }

    .empty-message {
      background: rgba(255, 255, 255, 0.05);
      color: var(--light);
      border: 1px dashed rgba(255, 255, 255, 0.2);
    }

         
    .order-float {
      position: fixed;
      bottom: 2rem;
      right: 2rem;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      color: white;
      width: 70px;
      height: 70px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      box-shadow: 0 5px 25px rgba(255, 126, 179, 0.6);
      z-index: 100;
      transition: all 0.3s;
      text-decoration: none;
      animation: float 3s ease-in-out infinite;
    }

    .order-float:hover {
      transform: scale(1.1) translateY(-5px);
      box-shadow: 0 10px 30px rgba(255, 126, 179, 0.8);
      animation: none;
    }

    .order-float .cart-count {
      position: absolute;
      top: -5px;
      right: -5px;
      background: var(--gold);
      color: var(--dark);
      width: 25px;
      height: 25px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.8rem;
      font-weight: bold;
    }

         
    .category-nav {
      position: sticky;
      top: 80px;
      z-index: 50;
      background: rgba(18, 18, 18, 0.9);
      backdrop-filter: blur(10px);
      padding: 1rem 0;
      margin-bottom: 3rem;
      border-bottom: 1px solid rgba(255, 126, 179, 0.2);
    }

    .category-nav__container {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      overflow-x: auto;
      scrollbar-width: none;      
      margin-top: 1.5em;
    }

    .category-nav__container::-webkit-scrollbar {
      display: none;      
    }

    .category-link {
      padding: 0.5rem 1.5rem;
      margin-right: 1rem;
      color: var(--light);
      text-decoration: none;
      font-weight: 500;
      border-radius: 30px;
      white-space: nowrap;
      transition: all 0.3s;
      border: 1px solid transparent;
    }

    .category-link:hover, .category-link.active {
      background: rgba(255, 126, 179, 0.1);
      color: var(--primary);
      border-color: var(--primary);
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

         
    @media (max-width: 1200px) {
      .hero h1 {
        font-size: 3.5rem;
      }
    }

    @media (max-width: 992px) {
      .menu__items {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      }
      
      .hero {
        height: 35vh;
      }
      
      .hero h1 {
        font-size: 3rem;
      }
    }

    @media (max-width: 768px) {
      .menu__container h1 {
        font-size: 2.8rem;
      }
      
      .menu__section h2 {
        font-size: 2rem;
      }
      
      .navbar__menu {
        display: none;
      }
      
      .hero h1 {
        font-size: 2.5rem;
      }
      
      .hero p {
        font-size: 1rem;
      }
    }

    @media (max-width: 576px) {
      .menu__items {
        grid-template-columns: 1fr;
      }
      
      .main {
        padding: 1rem 1rem 3rem;
      }
      
      .hero {
        height: 30vh;
        margin-bottom: 2rem;
      }
      
      .hero h1 {
        font-size: 2rem;
      }
      
      .menu__container h1 {
        font-size: 2.2rem;
        margin-bottom: 2rem;
      }
      
      .order-float {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
      }
    }

         
    .fade-in {
      animation: fadeIn 1s ease-in;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

         
    .scroll-indicator {
      position: absolute;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%);
      color: white;
      font-size: 1.5rem;
      animation: bounce 2s infinite;
    }

    @keyframes bounce {
      0%, 20%, 50%, 80%, 100% { transform: translateY(0) translateX(-50%); }
      40% { transform: translateY(-20px) translateX(-50%); }
      60% { transform: translateY(-10px) translateX(-50%); }
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
          <a href="menu_landing.php" class="navbar__links active">Menu</a>
        </li>
        <li class="navbar__item">
          <a href="location.php" class="navbar__links">Location</a>
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
      <h1 class="fade-in">Authentic Korean Flavors</h1>
      <p class="fade-in">Experience the taste of Korea with our carefully crafted menu featuring traditional recipes with a modern twist</p>
      <div class="scroll-indicator">
        <i class="fas fa-chevron-down"></i>
      </div>
    </div>
  </section>

         <div class="category-nav">
    <div class="category-nav__container">
      <?php if (!isset($error_message) && !isset($empty_message)): ?>
        <?php foreach ($menu_items as $category => $items): ?>
          <a href="#<?php echo str_replace(' ', '-', strtolower($category)); ?>" class="category-link"><?php echo htmlspecialchars($category); ?></a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

         <div class="main">
    <div class="menu__container">
      <h1>Our Menu</h1>

      <?php if (isset($error_message)): ?>
        <div class="error-message">
          <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
        </div>
      <?php elseif (isset($empty_message)): ?>
        <div class="empty-message">
          <i class="fas fa-utensils"></i> <?php echo htmlspecialchars($empty_message); ?>
        </div>
      <?php else: ?>
        <?php foreach ($menu_items as $category => $items): ?>
          <section class="menu__section" id="<?php echo str_replace(' ', '-', strtolower($category)); ?>">
            <h2><?php echo htmlspecialchars($category); ?></h2>
            <div class="menu__items">
              <?php foreach ($items as $item): ?>
                <div class="menu__item">
                  <?php if ($item['is_featured']): ?>
                    <div class="popular-badge"><i class="fas fa-star"></i> Popular</div>
                  <?php endif; ?>
                  <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'images/default-food.jpg'); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                  <div class="menu__item-content">
                    <p><?php echo htmlspecialchars($item['description']); ?></p>
                    <span>₱<?php echo htmlspecialchars(number_format($item['price'], 2)); ?></span>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

         <a href="<?php echo $isLoggedIn ? 'order.php' : 'login.php'; ?>" class="order-float">
    <i class="fas fa-shopping-cart"></i>
    <?php if($isLoggedIn): ?>
      <span class="cart-count">0</span>
    <?php endif; ?>
  </a>

         <footer class="footer">
    <div class="footer__container">
      <div class="social-links">
        <a href="#"><i class="fab fa-facebook-f"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-tiktok"></i></a>
      </div>
      <p>123 Magalang Rd, Arayat, Pampanga, Philippines</p>
      <p>Open daily from 10:00 AM to 09:00 PM</p>
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

    // Smooth scrolling for category links
    document.querySelectorAll('.category-link').forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
          window.scrollTo({
            top: targetElement.offsetTop - 100,
            behavior: 'smooth'
          });
        }
      });
    });

    // Add active class to category link when section is in view
    const sections = document.querySelectorAll('.menu__section');
    const navLinks = document.querySelectorAll('.category-link');

    window.addEventListener('scroll', function() {
      let current = '';
      
      sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.clientHeight;
        
        if (pageYOffset >= (sectionTop - 150)) {
          current = section.getAttribute('id');
        }
      });
      
      navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === `#${current}`) {
          link.classList.add('active');
        }
      });
    });

    // Menu item click animation
    document.querySelectorAll('.menu__item').forEach(item => {
      item.addEventListener('click', function() {
        this.style.transform = 'scale(0.98)';
        setTimeout(() => {
          this.style.transform = '';
        }, 200);
      });
    });

    // Initialize animations
    document.addEventListener('DOMContentLoaded', function() {
      const menuItems = document.querySelectorAll('.menu__item');
      menuItems.forEach((item, index) => {
        item.style.animationDelay = `${index * 0.1}s`;
        item.classList.add('fade-in');
      });
    });

    // Update cart count (placeholder - would be replaced with actual cart logic)
    <?php if($isLoggedIn): ?>
      // This would be replaced with actual cart count from your system
      // document.querySelector('.cart-count').textContent = '3';
    <?php endif; ?>
  </script>
</body>
</html>