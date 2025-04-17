<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cabs_korean";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$order_type = $_POST['order_type'] ?? 'delivery';
$items = [];
$subtotal = 0;
$delivery_fee = 0;

if (isset($_POST['items']) && is_array($_POST['items'])) {
    foreach ($_POST['items'] as $item_id => $item_data) {
        $quantity = (int)$item_data['quantity'];
        if ($quantity > 0) {
            $price = (float)$item_data['price'];
            $total = $price * $quantity;
            
            $items[] = [
                'id' => $item_id,
                'name' => $item_data['name'],
                'price' => $price,
                'quantity' => $quantity,
                'total' => $total,
                'image' => $item_data['image']
            ];
            
            $subtotal += $total;
        }
    }
}

if ($order_type === 'delivery') {
    $user_sql = "SELECT city FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $_SESSION['user_id']);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_city = $user_result->fetch_assoc()['city'];
    $user_stmt->close();

    $fee_sql = "SELECT base_fee FROM delivery_fees WHERE city = ?";
    $fee_stmt = $conn->prepare($fee_sql);
    $fee_stmt->bind_param("s", $user_city);
    $fee_stmt->execute();
    $fee_result = $fee_stmt->get_result();
    
    if ($fee_result->num_rows > 0) {
        $fee = $fee_result->fetch_assoc();
        $delivery_fee = $fee['base_fee'];
    } else {
        $delivery_fee = 50.00;
    }
    $fee_stmt->close();
}

$total_price = $subtotal + $delivery_fee;
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CABS KOREAN - Confirm Order</title>
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
    }

    .navbar__links:hover {
      color: var(--primary);
    }

    .button {
      padding: 0.8rem 1.5rem;
      border-radius: 8px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s;
      cursor: pointer;
      border: none;
      font-size: 1rem;
    }

    .primary-btn {
      background: linear-gradient(to right, var(--primary), var(--secondary));
      color: white;
    }

    .primary-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 20px rgba(255, 126, 179, 0.3);
    }

    .secondary-btn {
      background: rgba(255, 255, 255, 0.1);
      color: var(--light);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .secondary-btn:hover {
      background: rgba(255, 255, 255, 0.2);
    }

         
    .main {
      padding: 6rem 2rem 4rem;
      min-height: 100vh;
    }

    .order-container {
      max-width: 800px;
      margin: 0 auto;
      background: rgba(255, 255, 255, 0.05);
      border-radius: 12px;
      padding: 2.5rem;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
      position: relative;
      overflow: hidden;
      border: 1px solid rgba(255, 126, 179, 0.1);
    }

    .order-container::before {
      content: "";
      position: absolute;
      top: -2px;
      left: -2px;
      right: -2px;
      bottom: -2px;
      background: linear-gradient(45deg, var(--primary), var(--secondary));
      z-index: -1;
      border-radius: 14px;
    }

    .order-container::after {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(18, 18, 18, 0.95);
      border-radius: 10px;
      z-index: -1;
    }

    .order-header {
      text-align: center;
      margin-bottom: 2rem;
    }

    .order-header h2 {
      font-size: 2.5rem;
      margin-bottom: 1rem;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      background-clip: text;
      -webkit-background-clip: text;
      color: transparent;
    }

    .order-type {
      font-size: 1.2rem;
      margin-bottom: 1.5rem;
      color: var(--primary);
    }

         
    .order-items {
      margin-bottom: 2rem;
    }

    .order-item {
      display: flex;
      align-items: center;
      padding: 1rem 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .order-item:last-child {
      border-bottom: none;
    }

    .item-image {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 8px;
      margin-right: 1.5rem;
    }

    .item-details {
      flex: 1;
    }

    .item-name {
      font-size: 1.2rem;
      margin-bottom: 0.5rem;
    }

    .item-price {
      color: rgba(255, 255, 255, 0.7);
    }

    .item-quantity {
      font-weight: bold;
      color: var(--primary);
    }

    .item-total {
      font-weight: bold;
      min-width: 120px;
      text-align: right;
    }

         
    .order-summary {
      margin-top: 2rem;
      padding-top: 1.5rem;
      border-top: 2px solid rgba(255, 126, 179, 0.3);
    }

    .total-cost {
      font-size: 1.5rem;
      text-align: right;
      margin-bottom: 2rem;
    }

    .total-cost strong {
      color: var(--primary);
    }

         
    .button-container {
      display: flex;
      justify-content: space-between;
      margin-top: 2rem;
      gap: 1rem;
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

         
    @media (max-width: 768px) {
      .order-item {
        flex-direction: column;
        align-items: flex-start;
      }

      .item-image {
        margin-bottom: 1rem;
      }

      .item-total {
        text-align: left;
        margin-top: 0.5rem;
        width: 100%;
      }

      .button-container {
        flex-direction: column;
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
      
     
    </div>
  </nav>

  <div class="main">
    <div class="order-container">
      <div class="order-header">
        <h2>Review Your Order</h2>
        <p class="order-type"><strong>Order Type:</strong> <?php echo htmlspecialchars($order_type); ?></p>
        <?php if ($order_type === 'delivery'): ?>
        <p class="delivery-info">
            <i class="fas fa-truck"></i> 
            Delivery to your address
        </p>
        <?php endif; ?>
      </div>

      <?php if (empty($items)): ?>
        <div class="empty-order" style="text-align: center; padding: 2rem;">
          <p style="font-size: 1.2rem; margin-bottom: 1rem;">Your order is empty.</p>
          <a href="order.php" class="button primary-btn">Back to Menu</a>
        </div>
      <?php else: ?>
        <div class="order-items">
          <?php foreach ($items as $item): ?>
            <div class="order-item">
              <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image">
              <div class="item-details">
                <h3 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                <p class="item-price">₱<?php echo number_format($item['price'], 2); ?> each</p>
                <p class="item-quantity">Quantity: <?php echo $item['quantity']; ?></p>
              </div>
              <div class="item-total">
                ₱<?php echo number_format($item['total'], 2); ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="price-breakdown">
          <div class="price-row">
            <span class="price-label">Subtotal:</span>
            <span class="price-value">₱<?php echo number_format($subtotal, 2); ?></span>
          </div>
          
          <?php if ($order_type === 'delivery'): ?>
          <div class="price-row">
            <span class="price-label">Delivery Fee:</span>
            <span class="price-value">₱<?php echo number_format($delivery_fee, 2); ?></span>
          </div>
          <?php endif; ?>

          <div class="price-row total-row">
            <span class="price-label">Total:</span>
            <span class="price-value">₱<?php echo number_format($total_price, 2); ?></span>
          </div>
        </div>

        <form action="checkout_session.php" method="POST">
          <input type="hidden" name="order_type" value="<?php echo $order_type; ?>">
          <input type="hidden" name="total_cost" value="<?php echo $total_price; ?>">
          
          <?php foreach ($items as $index => $item): ?>
              <input type="hidden" name="items[<?php echo $index; ?>][id]" value="<?php echo $item['id']; ?>">
              <input type="hidden" name="items[<?php echo $index; ?>][name]" value="<?php echo htmlspecialchars($item['name']); ?>">
              <input type="hidden" name="items[<?php echo $index; ?>][price]" value="<?php echo $item['price']; ?>">
              <input type="hidden" name="items[<?php echo $index; ?>][quantity]" value="<?php echo $item['quantity']; ?>">
          <?php endforeach; ?>

          <div class="button-container">
            <button type="submit" class="button primary-btn">
              <i class="fas fa-credit-card"></i> Pay ₱<?php echo number_format($total_price, 2); ?>
            </button>
              <button type="button" class="button secondary-btn" onclick="window.location.href='order.php';">
                <i class="fas fa-edit"></i> Edit Order
              </button>
            </div>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <footer class="footer">
    <div class="footer__container">
      <p>&copy; <?php echo date('Y'); ?> CABS KOREAN. All Rights Reserved.</p>
    </div>
  </footer>
</body>
</html>