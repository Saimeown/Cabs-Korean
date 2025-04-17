<?php
session_start();

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cabs_korean";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = $_SESSION['email']; 
$user_id = $_SESSION['user_id'];
$order_type = $_POST['order_type'];
$items = [];
$total_cost = 0;

// Insert into orders table first
$order_stmt = $conn->prepare("INSERT INTO orders (user_id, order_type, email) VALUES (?, ?, ?)");
if (!$order_stmt) {
    die("Prepare failed (orders): " . $conn->error);
}
$order_stmt->bind_param("iss", $user_id, $order_type, $email);
$order_stmt->execute();
$order_id = $order_stmt->insert_id; // Get the last inserted order ID
$order_stmt->close();

if ($order_id) {
  foreach ($_POST['item_name'] as $index => $item_name) {
    $item_price = $_POST['item_price'][$index];
    $item_quantity = $_POST['item_quantity'][$index];
    $item_total = $_POST['item_total'][$index];

    // Fetch the menu item ID using item name
    $menu_stmt = $conn->prepare("SELECT id FROM menu_items WHERE name = ?");
    if (!$menu_stmt) {
        die("Prepare failed (menu_items): " . $conn->error);
    }
    $menu_stmt->bind_param("s", $item_name);
    $menu_stmt->execute();
    $menu_stmt->bind_result($item_id);
    $menu_stmt->fetch();
    $menu_stmt->close();

    if ($item_id) {
        // Insert order item including menu_item_name
        $order_item_stmt = $conn->prepare("INSERT INTO order_items (order_id, item_id, menu_item_name, quantity, price) VALUES (?, ?, ?, ?, ?)");
        if (!$order_item_stmt) {
            die("Prepare failed (order_items): " . $conn->error);
        }
        $order_item_stmt->bind_param("iissi", $order_id, $item_id, $item_name, $item_quantity, $item_price);
        $order_item_stmt->execute();
        $order_item_stmt->close();
    }

    $total_cost += $item_total;
}

    $order_message = "<h1>Your Order has been received!</h1>
    <p>We will notify you once your order is confirmed.</p>
    <p>Total Cost: Php " . number_format($total_cost, 2) . "</p>
    <p>Order Type: " . htmlspecialchars($order_type) . "</p>
    <p>Look for the confirmation in your email: 
    <a href='mailto:$email' style='color: #007bff; text-decoration: none; font-weight: bold; transition: color 0.3s ease;'>
    $email</a>.</p>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CABS KOREAN</title>
  <link rel="stylesheet" href="style.css">
  <script src="https://kit.fontawesome.com/f921d29dfc.js" crossorigin="anonymous"></script>
  <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@700&display=swap" rel="stylesheet">
  <link rel="icon" href="cabs.png" type="image/png">


  <body>
    <nav class="navbar">
      <div class="navbar__contianer">
      <a href="index.php" id="navbar__logo">
        <img src="images/cabs.jpg" alt="CABS KOREAN Logo"> CABS
    </a>
        <div class="navbar__toggle" id="mobile-menu">
          <span class="bar"></span>
          <span class="bar"></span>
          <span class="bar"></span>
        </div>
    
        <ul class="navbar__menu">
        <li class="navbar__item">
  <a href="menu.php" class="navbar__links">Menu</a>
</li>
<li class="navbar__item">
  <a href="order.php" class="navbar__links">Order Now</a>
</li>
<li class="navbar__item">
  <a href="reservation.php" class="navbar__links">Reservation</a>
</li>

<li class="navbar__btn">
  <?php if ($isLoggedIn): ?>
    <a href="logout.php" class="button">Logout</a>
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
        <div class="order-message">
          <?php echo $order_message; ?>
        </div>
      </div>
    </div>

    <footer class="footer">
        <div class="footer__container">
            <p>&copy; 2024 CABS KOREAN. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="app.js"></script>
</body>
</html>
