<?php
session_start();

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cabs_korean";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$menu_items = [];
$sql = "SELECT * FROM menu_items ORDER BY category_id";
$result = $conn->query($sql);

// Check if query was successful before trying to use num_rows
if ($result !== false) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $menu_items[$row['category_id']][] = $row;
        }
    }
} else {
    // Log the error and display a user-friendly message
    error_log("Database query failed: " . $conn->error);
    $error_message = "We're experiencing technical difficulties. Please try again later.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CABS KOREAN - Order Now</title>
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
      --error: #f44336;
      --success: #4CAF50;
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

    /* Navbar */
    .navbar {
      background: rgba(18, 18, 18, 0.9);
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
      background: rgba(255, 0, 0, 0.2);
      border: 1px solid rgba(255, 0, 0, 0.5);
      color: #ff6b6b;
    }

    .logout-btn:hover {
      background: rgba(255, 0, 0, 0.3);
    }

    /* Main Content */
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

    /* Menu Sections */
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
      margin: 0.5rem 0;
    }

    /* Quantity Input */
    .quantity-control {
      display: flex;
      align-items: center;
      margin-top: 1rem;
    }

    .quantity-control label {
      margin-right: 0.5rem;
      font-size: 0.9rem;
    }

    .quantity-control input {
      width: 70px;
      padding: 0.5rem;
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 6px;
      color: var(--light);
      text-align: center;
    }

    /* Submit Button */
    .submit-order__container {
      display: flex;
      justify-content: center;
      margin-top: 3rem;
    }

    .submit-order__container .button {
      padding: 1rem 2.5rem;
      font-size: 1.1rem;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      color: white;
      border: none;
      cursor: pointer;
    }

    .submit-order__container .button:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 20px rgba(255, 126, 179, 0.3);
    }

    /* Badge for popular items */
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

    /* Messages */
    .error-message {
      text-align: center;
      padding: 1.5rem;
      border-radius: 8px;
      margin: 2rem 0;
      background: rgba(244, 67, 54, 0.1);
      color: var(--error);
      border-left: 4px solid var(--error);
    }

    /* Floating Background Elements */
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

    /* Footer */
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

    /* Responsive Design */
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
    /* Navbar Styles */
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

    /* Color Variables */
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
    /* Order Type Selection Styles */
    .order-type-input {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }

    .order-type-option {
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .order-type-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 1.5rem 1rem;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 10px;
        border: 1px solid rgba(255, 126, 179, 0.1);
        transition: all 0.3s ease;
        height: 100%;
    }

    .order-type-icon {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 126, 179, 0.1);
        border-radius: 50%;
        margin-bottom: 1rem;
        font-size: 1.5rem;
        color: var(--primary);
        transition: all 0.3s ease;
    }

    .order-type-option span {
        font-weight: 500;
        color: var(--light);
        transition: all 0.3s ease;
    }

    .order-type-input:checked + .order-type-card {
        background: rgba(255, 126, 179, 0.1);
        border: 1px solid var(--primary);
        box-shadow: 0 5px 15px rgba(255, 126, 179, 0.2);
    }

    .order-type-input:checked + .order-type-card .order-type-icon {
        background: linear-gradient(to right, var(--primary), var(--secondary));
        color: white;
        transform: scale(1.1);
    }

    .order-type-input:checked + .order-type-card span {
        color: var(--primary);
        font-weight: 600;
    }

    .order-type-option:hover .order-type-card {
        border-color: rgba(255, 126, 179, 0.3);
    }

    .order-type-option:hover .order-type-icon {
        background: rgba(255, 126, 179, 0.2);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .order-type-options {
            grid-template-columns: 1fr !important;
        }
        
        .order-type-card {
            flex-direction: row;
            justify-content: flex-start;
            padding: 1rem;
            gap: 1rem;
        }
        
        .order-type-icon {
            margin-bottom: 0;
        }
    }
    /* Quantity Selector Styles */
    .quantity-control {
        display: flex;
        align-items: center;
        margin-top: 1rem;
        gap: 0.5rem;
    }

    .quantity-selector {
        display: flex;
        align-items: center;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid rgba(255, 126, 179, 0.3);
        background: rgba(255, 255, 255, 0.05);
    }

    .quantity-btn {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 126, 179, 0.1);
        border: none;
        color: var(--primary);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .quantity-btn:hover {
        background: rgba(255, 126, 179, 0.2);
    }

    .quantity-btn:active {
        transform: scale(0.95);
    }

    .quantity-btn i {
        font-size: 0.8rem;
    }

    .quantity-input {
        width: 50px;
        height: 32px;
        text-align: center;
        border: none;
        background: rgba(255, 255, 255, 0.1);
        color: var(--light);
        -moz-appearance: textfield;
        appearance: textfield;
        margin: 0;
        padding: 0;
    }

    .quantity-input::-webkit-outer-spin-button,
    .quantity-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .quantity-input:focus {
        outline: 2px solid var(--primary);
        outline-offset: -2px;
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
  <!-- Floating Background Elements -->
  <div class="floating-element"></div>
  <div class="floating-element"></div>

  <nav class="navbar">
    <div class="navbar__container">
      <a href="index.php" id="navbar__logo">
        <img src="images/cabs.png" alt="CABS KOREAN Logo">CABS
      </a>
      
      <ul class="navbar__menu">
        <li class="navbar__item">
          <a href="menu.php" class="navbar__links">Menu</a>
        </li>
        <li class="navbar__item">
          <a href="order.php" class="navbar__links active">Order Now</a>
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
      <h1>Place Your Order</h1>
      
      <?php if (isset($error_message)): ?>
        <div class="error-message">
          <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
        </div>
      <?php else: ?>
        <!-- In the form section of your order.php, replace the quantity input part with this: -->
      <form action="confirm_order.php" method="POST" onsubmit="return validateOrderForm();">
          <?php foreach ($menu_items as $category => $items): ?>
              <section class="menu__section">
                  <h2><?php echo htmlspecialchars($category); ?></h2>
                  <div class="menu__items">
                      <?php foreach ($items as $item): ?>
                          <div class="menu__item">
                              <?php if ($item['is_featured']): ?>
                                  <div class="popular-badge">Popular</div>
                              <?php endif; ?>
                              <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                              <div class="menu__item-content">
                                  <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                  <p><?php echo htmlspecialchars($item['description']); ?></p>
                                  <span>₱<?php echo htmlspecialchars(number_format($item['price'], 2)); ?></span>
                                  <div class="quantity-control">
                                    <label>Quantity:</label>
                                    <!-- Hidden fields to pass all item data -->
                                    <input type="hidden" name="items[<?php echo $item['id']; ?>][id]" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="items[<?php echo $item['id']; ?>][name]" value="<?php echo htmlspecialchars($item['name']); ?>">
                                    <input type="hidden" name="items[<?php echo $item['id']; ?>][price]" value="<?php echo $item['price']; ?>">
                                    <input type="hidden" name="items[<?php echo $item['id']; ?>][image]" value="<?php echo htmlspecialchars($item['image_url']); ?>">
                                    
                                    <div class="quantity-selector">
                                    <input type="number" name="items[<?php echo $item['id']; ?>][quantity]" min="0" value="0" class="quantity-input">
                                        <button type="button" class="quantity-btn minus" aria-label="Decrease quantity">
                                            <i class="fas fa-minus"></i>
                                        </button>
                        
                                        <button type="button" class="quantity-btn plus" aria-label="Increase quantity">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                              </div>
                          </div>
                      <?php endforeach; ?>
                  </div>
              </section>
          <?php endforeach; ?>

          <!-- Add order type selection -->
          <!-- Add order type selection -->
          <div class="order-type-selection" style="max-width: 600px; margin: 2rem auto; padding: 2rem; background: rgba(255,255,255,0.05); border-radius: 12px; border: 1px solid rgba(255, 126, 179, 0.2);">
              <h3 style="text-align: center; margin-bottom: 1.5rem; color: var(--primary); font-size: 1.5rem;">Select Order Type</h3>
              <div class="order-type-options" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                  <label class="order-type-option">
                      <input type="radio" name="order_type" value="delivery" checked class="order-type-input">
                      <div class="order-type-card">
                          <div class="order-type-icon">
                              <i class="fas fa-truck"></i>
                          </div>
                          <span>Delivery</span>
                      </div>
                  </label>
                  <label class="order-type-option">
                      <input type="radio" name="order_type" value="pickup" class="order-type-input">
                      <div class="order-type-card">
                          <div class="order-type-icon">
                              <i class="fas fa-box"></i>
                          </div>
                          <span>Pickup</span>
                      </div>
                  </label>
                  <label class="order-type-option">
                      <input type="radio" name="order_type" value="dine-in" class="order-type-input">
                      <div class="order-type-card">
                          <div class="order-type-icon">
                              <i class="fas fa-utensils"></i>
                          </div>
                          <span>Dine-in</span>
                      </div>
                  </label>
              </div>
          </div>

          <div class="submit-order__container">
              <button type="submit" class="button" id="review-order-btn" <?php echo !$isLoggedIn ? 'onclick="promptLogin(); return false;"' : ''; ?>>
                  <i class="fas fa-shopping-cart"></i> Review Order
              </button>
          </div>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <footer class="footer">
    <div class="footer__container">
      <p>&copy; <?php echo date('Y'); ?> CABS KOREAN. All Rights Reserved.</p>
    </div>
  </footer>

  <script>
    // Quantity selector functionality
    document.querySelectorAll('.quantity-selector').forEach(selector => {
        const input = selector.querySelector('.quantity-input');
        const minusBtn = selector.querySelector('.minus');
        const plusBtn = selector.querySelector('.plus');
        
        minusBtn.addEventListener('click', () => {
            let value = parseInt(input.value);
            if (value > 0) {
                input.value = value - 1;
                input.dispatchEvent(new Event('change'));
            }
        });
        
        plusBtn.addEventListener('click', () => {
            let value = parseInt(input.value);
            input.value = value + 1;
            input.dispatchEvent(new Event('change'));
        });
        
        // Visual feedback when changing value
        input.addEventListener('change', () => {
            if (parseInt(input.value) > 0) {
                selector.style.borderColor = 'var(--primary)';
                selector.style.boxShadow = '0 0 0 1px var(--primary)';
            } else {
                selector.style.borderColor = 'rgba(255, 126, 179, 0.3)';
                selector.style.boxShadow = 'none';
            }
        });
    });
    function validateOrderForm() {
      const quantities = document.querySelectorAll('input[type="number"]');
      let isSelected = false;

      quantities.forEach(input => {
        if (parseInt(input.value) > 0) {
          isSelected = true;
        }
      });

      if (!isSelected) {
        alert("Please select at least one item before submitting your order.");
        return false; 
      }

      return confirm("Are you sure you want to submit your order?");
    }

    function promptLogin() {
      alert("You need to log in to place an order. Redirecting to login page...");
      window.location.href = "login.php";
    }

    // Add animation to menu items when clicked
    document.querySelectorAll('.menu__item').forEach(item => {
      item.addEventListener('click', function(e) {
        // Don't trigger if clicking on quantity input
        if (e.target.tagName !== 'INPUT') {
          this.style.transform = 'scale(0.95)';
          setTimeout(() => {
            this.style.transform = '';
          }, 200);
        }
      });
    });
  </script>
</body>
</html>