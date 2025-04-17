<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['username'] : 'Guest';
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

include 'db_connect.php';

$fullname = '';
$email = '';
$phone = '';

if ($isLoggedIn) {
    $user_query = "SELECT fullname, email, phone FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("i", $userId);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    
    if ($user_data = $user_result->fetch_assoc()) {
        $fullname = $user_data['fullname'];
        $email = $user_data['email'];
        $phone = $user_data['phone'];
    }
    $user_stmt->close();
}

$timeSlots = [
    '11:00:00', '11:30:00', 
    '12:00:00', '12:30:00',
    '13:00:00', '13:30:00',
    '17:00:00', '17:30:00',
    '18:00:00', '18:30:00',
    '19:00:00', '19:30:00',
    '20:00:00', '20:30:00'
];

$recentOrders = [];
if ($isLoggedIn) {
    $query = "SELECT o.* FROM orders o WHERE o.user_id = ? ORDER BY o.order_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($order = $result->fetch_assoc()) {
        $items_query = "SELECT oi.*, mi.name as item_name 
                        FROM order_items oi
                        JOIN menu_items mi ON oi.menu_item_id = mi.id
                        WHERE oi.order_id = ?";
        $items_stmt = $conn->prepare($items_query);
        $items_stmt->bind_param("i", $order['id']);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        $items = [];
        while ($item = $items_result->fetch_assoc()) {
            $items[] = $item['item_name'] . ' (x' . $item['quantity'] . ')';
        }
        $items_stmt->close();
        
        $order['items'] = implode(', ', $items);
        $recentOrders[] = $order;
    }
    $stmt->close();
}

$reservations = [];
if ($isLoggedIn) {
    $res_query = "SELECT *, 
                 CASE 
                     WHEN status = 'confirmed' AND payment_status = 'waiting_payment' THEN 'waiting_payment'
                     ELSE status
                 END AS display_status
                 FROM reservations 
                 WHERE user_id = ? AND reservation_date >= CURDATE() 
                 ORDER BY reservation_date ASC";
    $res_stmt = $conn->prepare($res_query);
    $res_stmt->bind_param("i", $userId);
    $res_stmt->execute();
    $res_result = $res_stmt->get_result();
    
    while ($reservation = $res_result->fetch_assoc()) {
        $reservations[] = $reservation;
    }
    $res_stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CABS KOREAN - Reservations</title>
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
      position: fixed;
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
      position: relative;
    }

    .navbar__links {
      color: var(--light);
      text-decoration: none;
      font-size: 1.1rem;
      transition: color 0.3s;
      padding: 0.5rem 0;
      position: relative;
      display: inline-block;
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
      transform-origin: bottom center;
      transition: transform 0.3s ease;
    }

         
    .main {
      min-height: auto;
      height: calc(100vh - 120px);
      display: flex;
      align-items: center;
      padding: 6rem 2rem 2rem;
      position: relative;
      overflow: hidden;
      padding-top: 0px;
    }

    .main__container {
      max-width: 1200px;
      margin: 0 auto;
      width: 100%;
    }

    .input-row {
      display: flex;
      gap: 1.5rem;
      margin-bottom: 1rem;
    }

    .input-group {
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .reservation__header {
      text-align: center;
      margin-bottom: 1.5rem;
    }

    .reservation__header h1 {
      font-size: 2.8rem;
      margin-bottom: 0.5rem;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      background-clip: text;
      -webkit-background-clip: text;
      color: transparent;
    }

    .reservation__header p {
      font-size: 1.1rem;
      opacity: 0.8;
      max-width: 700px;
      margin: 0 auto;
    }

    .reservation-container {
      max-width: 700px;
      margin: 0 auto;
      background: rgba(30, 30, 30, 0.8);
      padding: 2.5rem;
      border-radius: 12px;
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 126, 179, 0.2);
    }

    .reservation-container h2 {
      text-align: center;
      margin-bottom: 2rem;
      font-size: 2rem;
      color: var(--primary);
    }

    #reservation-form {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    #reservation-form label {
      font-size: 1rem;
      color: var(--light);
      margin-bottom: -0.3rem;
    }

    #reservation-form input {
      padding: 0.7rem 1rem;
      border-radius: 6px;
      border: 1px solid var(--gray);
      background: rgba(40, 40, 40, 0.8);
      color: var(--light);
      font-size: 0.95rem;
      transition: all 0.3s;
      width: 100%;
    }

    #reservation-form input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 2px rgba(255, 126, 179, 0.3);
    }

    #reservation-form input[readonly] {
      background: rgba(60, 60, 60, 0.8);
      color: #999;
    }

    #reservation-form button {
      padding: 0.8rem;
      border-radius: 6px;
      border: none;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      color: white;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      margin-top: 1rem;
    }

    #reservation-form button:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 20px rgba(255, 126, 179, 0.3);
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

         
    @media (max-width: 768px) {
      .input-row {
        flex-direction: column;
        gap: 1rem;
      }

      .navbar__menu {
        display: none;
        height: auto;
        min-height: calc(100vh - 120px);
        padding: 5rem 1rem 1rem;
      }

      .main {
        padding-top: 6rem;
      }

      .reservation__header h1 {
        font-size: 2.2rem;
      }

      .reservation-container {
        padding: 1.5rem;
        margin: 0 1rem;
      }
    }

    input {
      margin-top: 10px;
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

    .input-group select {
      padding: 0.7rem 1rem;
      border-radius: 6px;
      border: 1px solid var(--gray);
      background: rgba(40, 40, 40, 0.8);
      color: var(--light);
      font-size: 0.95rem;
      transition: all 0.3s;
      width: 100%;
      appearance: none;
      -webkit-appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23ff7eb3' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 1rem center;
      background-size: 12px;
      cursor: pointer;
      margin-top: 10px;
    }

    .input-group select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 2px rgba(255, 126, 179, 0.3);
    }

         
    .input-group select option {
      background: rgba(30, 30, 30, 0.9);
      color: var(--light);
      padding: 0.5rem;
    }

         
    .input-group select option:hover {
      background: var(--primary);
      color: var(--dark);
    }
         
    .date-input {
      position: relative;
    }

         
    .date-input::-webkit-calendar-picker-indicator {
      filter: invert(40%) sepia(60%) saturate(1000%) hue-rotate(300deg) brightness(100%) contrast(100%);
      cursor: pointer;
    }

         
    .date-input {
      color-scheme: dark;
    }

         
    .input-group select {
           
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23ff7eb3' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
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
      <a href="index.php" id="navbar__logo">
        <img src="images/cabs.png" alt="CABS KOREAN Logo">CABS
      </a>
      
      <ul class="navbar__menu">
        <li class="navbar__item">
          <a href="menu.php" class="navbar__links">Menu</a>
        </li>
        <li class="navbar__item">
          <a href="order.php" class="navbar__links">Order Now</a>
        </li>
        <li class="navbar__item">
          <a href="reservation.php" class="navbar__links active">Reservation</a>
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
    <div class="main__container">
      <div class="reservation__header">
        <h1>RESERVATIONS</h1>
        <p>Book your table in advance to ensure the best Korean BBQ experience</p>
      </div>

      <div class="reservation-container">
        <h2>Make a Reservation</h2>
        <form id="reservation-form" action="confirm_reservation.php" method="POST">
          <div class="input-row">
            <div class="input-group">
              <label for="name">Name:</label>
              <input type="text" id="name" name="name" 
                     value="<?php echo isset($fullname) ? htmlspecialchars($fullname) : ''; ?>" 
                     required <?php echo $isLoggedIn ? 'readonly' : ''; ?>>
            </div>
            <div class="input-group">
              <label for="email">Email:</label>
              <input type="email" id="email" name="email" 
                     value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" 
                     required <?php echo $isLoggedIn ? 'readonly' : ''; ?>>
            </div>
          </div>

          <div class="input-row">
            <div class="input-group">
              <label for="phone">Phone:</label>
              <input type="tel" id="phone" name="phone" 
                     value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>" 
                     pattern="^(?:\+63|0)\d{10}$" required 
                     <?php echo $isLoggedIn ? 'readonly' : ''; ?>>
            </div>
            <div class="input-group">
              <label for="guests">Guests:</label>
              <input type="number" id="guests" name="guests" min="1" max="20" required>
            </div>
          </div>

          <div class="input-row">
            <div class="input-group">
              <label for="date">Date:</label>
              <input type="date" id="date" name="date" min="<?= date('Y-m-d'); ?>" required class="date-input">
            </div>
            <div class="input-group">
              <label for="time">Time:</label>
              <select id="time" name="time" required>
                <?php foreach ($timeSlots as $slot): ?>
                  <option value="<?= $slot ?>"><?= date('g:i A', strtotime($slot)) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <button type="submit" id="confirm-reservation-btn" <?php echo !$isLoggedIn ? 'onclick="promptLogin(); return false;"' : ''; ?>>Confirm Reservation</button>
        </form>
      </div>
    </div>
  </div>

  <footer class="footer">
    <div class="footer__container">
      <p>&copy; <?php echo date('Y'); ?> CABS KOREAN. All Rights Reserved.</p>
    </div>
  </footer>

  <script>
    function promptLogin() {
      alert("You need to log in to confirm a reservation. Redirecting to login page...");
      window.location.href = "login.php";
    }
  </script>
</body>
</html>