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
  <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;700&display=swap" rel="stylesheet">
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
      position: relative;
    }

    .navbar__links:hover {
      color: var(--primary);
    }

    .navbar__links.active::after {
      content: '';
      position: absolute;
      bottom: -5px;
      left: 0;
      width: 100%;
      height: 2px;
      background: linear-gradient(to right, var(--primary), var(--secondary));
    }

    .button {
      padding: 0.6rem 1.2rem;
      border-radius: 6px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s;
      margin-left: 0.8rem;
    }

    .logout-btn {
      background: rgba(255, 57, 133, 0.2);
      border: 1px solid rgba(255, 0, 111, 0.5);
      color:rgb(255, 107, 166);
    }

    .logout-btn:hover {
      background: rgba(255, 0, 140, 0.3);
    }

         
    .main {
      padding: 6rem 2rem 4rem;
      min-height: 100vh;
      padding-top: 60px;
    }

    .menu__container {
      max-width: 1200px;
      margin: 0 auto;
    }

    .menu__container h1 {
      text-align: center;
      font-size: 3rem;
      margin-bottom: 3rem;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      background-clip: text;
      -webkit-background-clip: text;
      color: transparent;
    }

         
    .menu__section {
      margin-bottom: 4rem;
    }

    .menu__section h2 {
      font-size: 2rem;
      margin-bottom: 1.5rem;
      padding-bottom: 0.5rem;
      border-bottom: 2px solid rgba(255, 126, 179, 0.3);
      color: var(--primary);
    }

    .menu__items {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 2rem;
    }

    .menu__item {
      background: rgba(255, 255, 255, 0.05);
      border-radius: 12px;
      overflow: hidden;
      transition: transform 0.3s, box-shadow 0.3s;
      position: relative;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .menu__item:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(255, 126, 179, 0.2);
    }

    .menu__item img {
      width: 100%;
      height: 200px;
      object-fit: cover;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .menu__item-content {
      padding: 1.5rem;
    }

    .menu__item h3 {
      font-size: 1.3rem;
      margin-bottom: 0.5rem;
      color: var(--light);
    }

    .menu__item p {
      color: rgba(255, 255, 255, 0.7);
      margin-bottom: 1rem;
      font-size: 0.95rem;
    }

    .menu__item span {
      display: inline-block;
      font-weight: bold;
      font-size: 1.2rem;
      color: var(--primary);
      margin-top: 0.5rem;
    }

         
    .popular-badge {
      position: absolute;
      top: 15px;
      right: 15px;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      color: white;
      padding: 0.3rem 0.8rem;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: bold;
      z-index: 1;
    }

         
    .error-message, .empty-message {
      text-align: center;
      padding: 2rem;
      border-radius: 8px;
      margin: 2rem 0;
      font-size: 1.1rem;
    }

    .error-message {
      background: rgba(244, 67, 54, 0.1);
      color: var(--error);
      border-left: 4px solid var(--error);
    }

    .empty-message {
      background: rgba(255, 255, 255, 0.05);
      color: var(--light);
    }

         
    .order-float {
      position: fixed;
      bottom: 2rem;
      right: 2rem;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      color: white;
      width: 60px;
      height: 60px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      box-shadow: 0 5px 20px rgba(255, 126, 179, 0.5);
      z-index: 100;
      transition: all 0.3s;
      text-decoration: none;
    }

    .order-float:hover {
      transform: scale(1.1) translateY(-5px);
      box-shadow: 0 10px 25px rgba(255, 126, 179, 0.7);
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
      animation: float 8s ease-in-out infinite;
    }

    .floating-element:nth-child(2) {
      width: 200px;
      height: 200px;
      background: linear-gradient(45deg, var(--secondary), var(--primary));
      border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
      bottom: 15%;
      right: 10%;
      animation: float 10s ease-in-out infinite reverse;
    }

    @keyframes float {
      0% { transform: translateY(0) rotate(0deg); }
      50% { transform: translateY(-20px) rotate(5deg); }
      100% { transform: translateY(0) rotate(0deg); }
    }

         
    .footer {
      background: rgba(18, 18, 18, 0.9);
      padding: 2rem;
      text-align: center;
      border-top: 1px solid rgba(255, 126, 179, 0.2);
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
      .menu__items {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      }
    }

    @media (max-width: 768px) {
      .menu__container h1 {
        font-size: 2.5rem;
      }
      
      .menu__section h2 {
        font-size: 1.8rem;
      }
      
      .navbar__menu {
        display: none;
      }
    }

    @media (max-width: 480px) {
      .menu__items {
        grid-template-columns: 1fr;
      }
      
      .main {
        padding: 5rem 1rem 3rem;
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

    .button {
      padding: 0.6rem 1.2rem;
      border-radius: 6px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s;
      margin-left: 0.8rem;
    }

    .logout-btn {
      background: rgba(255, 57, 133, 0.2);
      border: 1px solid rgba(255, 0, 111, 0.5);
      color:rgb(255, 107, 166);
    }

    .logout-btn:hover {
      background: rgba(255, 0, 140, 0.3);
    }

         
    :root {
      --primary: #ff7eb3;
      --secondary: #ff0844;
      --dark: #121212;
      --light: #e0e0e0;
      --gray: #333333;
      --success: #4CAF50;
      --warning: #FFC107;
      --info: #2196F3;
    }
  </style>
</head>

<body>
         <div class="floating-element"></div>
  <div class="floating-element"></div>

  <nav class="navbar">
    <div class="navbar__container">
      <a href="index.php" id="navbar__logo">
        <img src="images/cabs.png" alt="CABS KOREAN Logo">CABS
      </a>
      
      <ul class="navbar__menu">
        <li class="navbar__item">
          <a href="menu.php" class="navbar__links active">Menu</a>
        </li>
        <li class="navbar__item">
          <a href="order.php" class="navbar__links">Order Now</a>
        </li>
        <li class="navbar__item">
          <a href="reservation.php" class="navbar__links">Reservation</a>
        </li>
        <li class="navbar__item">
          <a href="update_user.php" class="navbar__links">Profile</a>
        </li>
        <li class="navbar__btn">
          <?php if ($isLoggedIn): ?>
            <a href="logout.php" class="button logout-btn">
              <i class="fas fa-sign-out-alt"></i> Logout
            </a>
          <?php else: ?>
            <a href="login.php" class="button">Login</a>
            <a href="register.php" class="button">Register</a>
          <?php endif; ?>
        </li>
      </ul>
    </div>
  </nav>

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
          <section class="menu__section">
            <h2><?php echo htmlspecialchars($category); ?></h2>
            <div class="menu__items">
              <?php foreach ($items as $item): ?>
                <div class="menu__item">
                  <?php if ($item['is_featured']): ?>
                    <div class="popular-badge">Popular</div>
                  <?php endif; ?>
                  <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'images/default-food.jpg'); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                  <div class="menu__item-content">
                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
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
  </a>

  <footer class="footer">
    <div class="footer__container">
      <p>&copy; <?php echo date('Y'); ?> CABS KOREAN. All Rights Reserved.</p>
    </div>
  </footer>

  <script>
    // Add to cart functionality would go here
    document.querySelectorAll('.menu__item').forEach(item => {
      item.addEventListener('click', function() {
        // Add animation/feedback when item is clicked
        this.style.transform = 'scale(0.95)';
        setTimeout(() => {
          this.style.transform = '';
        }, 200);
      });
    });
  </script>
</body>
</html>