<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['username'] : 'Guest';
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

// Database connection
include 'db_connect.php';

// Fetch recent orders for the logged-in user
$recentOrders = [];
if ($isLoggedIn) {
    $query = "SELECT o.* FROM orders o WHERE o.user_id = ? ORDER BY o.order_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($order = $result->fetch_assoc()) {
        // Get order items
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

// Fetch reservations with payment status
$reservations = [];
if ($isLoggedIn) {
    $res_query = "SELECT *, 
                 CASE 
                     WHEN status = 'confirmed' AND payment_status = 'waiting_payment' THEN 'waiting_payment'
                     WHEN status = 'cancelled' AND payment_status = 'pending' THEN 'declined'
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
  <title>CABS KOREAN - Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;700&display=swap" rel="stylesheet">
  <link rel="icon" href="cabs.png" type="image/png">
  <link rel="icon" href="cabs.png" type="image/png">

  <style>
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
      min-height: 100vh;
      display: flex;
      flex-direction: column;
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

         
    .dashboard {
      flex: 1;
      padding: 2rem;
      max-width: 1200px;
      margin: 6rem auto 4rem;
      min-height: calc(100vh - 10rem);
      width: 100%;
    }

    .welcome-section {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }

    .welcome-message h1 {
      font-size: 2.5rem;
      margin-bottom: 0.5rem;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      background-clip: text;
      -webkit-background-clip: text;
      color: transparent;
    }

    .welcome-message p {
      color: rgba(255, 255, 255, 0.7);
      font-size: 1.1rem;
    }

    .quick-actions {
      display: flex;
      gap: 1rem;
    }

    .action-btn {
      padding: 0.8rem 1.5rem;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .action-btn.primary {
      background: linear-gradient(to right, var(--primary), var(--secondary));
      color: white;
    }

    .action-btn.secondary {
      background: rgba(255, 255, 255, 0.1);
      color: var(--light);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .action-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(255, 126, 179, 0.3);
    }

         
    .dashboard-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
      margin-top: 2rem;
    }

    .dashboard-card {
      background: rgba(255, 255, 255, 0.05);
      border-radius: 12px;
      padding: 1.5rem;
      border: 1px solid rgba(255, 255, 255, 0.1);
      transition: transform 0.3s, box-shadow 0.3s;
      height: 400px;      
      display: flex;
      flex-direction: column;
    }

    .dashboard-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(255, 126, 179, 0.1);
    }

    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .card-header h2 {
      font-size: 1.5rem;
      color: var(--primary);
    }

    .card-header a {
      color: var(--primary);
      text-decoration: none;
      font-size: 0.9rem;
      transition: color 0.3s;
    }

    .card-header a:hover {
      text-decoration: underline;
    }

         
    .card-content {
      overflow-y: auto;
      flex: 1;
      padding-right: 0.5rem;
    }

         
    .card-content::-webkit-scrollbar {
      width: 6px;
    }

    .card-content::-webkit-scrollbar-track {
      background: rgba(255, 255, 255, 0.05);
      border-radius: 3px;
    }

    .card-content::-webkit-scrollbar-thumb {
      background: var(--primary);
      border-radius: 3px;
    }

         
    .status-badge {
      padding: 0.3rem 0.8rem;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: bold;
    }

    .status-preparing {
      background: rgba(255, 193, 7, 0.2);
      color: var(--warning);
    }

    .status-delivered {
      background: rgba(76, 175, 80, 0.2);
      color: var(--success);
    }

    .status-completed {
      background: rgba(76, 175, 80, 0.2);
      color: var(--success);
    }

    .status-confirmed {
      background: rgba(175, 142, 76, 0.2);
      color: rgba(255, 186, 47, 0.99);
    }

    .status-pending {
      background: rgba(255, 193, 7, 0.2);
      color: var(--warning);
    }

    .status-waiting_payment {
      background: rgba(255, 165, 0, 0.2);
      color: #FFA500;
    }

    .status-cancelled {
      background: rgba(244, 67, 54, 0.2);
      color: #F44336;
    }

    .status-on_the_way {
      background: rgba(255, 193, 7, 0.2);
      color: var(--warning);
    }

         
    .order-table {
      width: 100%;
      border-collapse: collapse;
    }

    .order-table tr:not(:last-child) {
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .order-table td {
      padding: 1rem 0;
      vertical-align: top;
    }

    .order-id {
      font-weight: bold;
      color: var(--primary);
    }

    .order-date {
      color: rgba(255, 255, 255, 0.7);
      font-size: 0.9rem;
    }

    .order-type {
      color: rgba(255, 255, 255, 0.6);
      font-size: 0.8rem;
      margin-top: 0.3rem;
    }

    .payment-link {
      display: inline-block;
      margin-top: 0.5rem;
      padding: 0.3rem 0.8rem;
      background: var(--primary);
      color: white;
      border-radius: 4px;
      text-decoration: none;
      font-size: 0.8rem;
      transition: all 0.3s;
    }

    .payment-link:hover {
      background: var(--secondary);
      transform: translateY(-1px);
    }

         
    .footer {
      background: rgba(18, 18, 18, 0.9);
      padding: 2rem;
      text-align: center;
      border-top: 1px solid rgba(255, 126, 179, 0.2);
      position: relative;
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
      .welcome-section {
        flex-direction: column;
        align-items: flex-start;
        gap: 1.5rem;
      }
      
      .quick-actions {
        width: 100%;
        justify-content: space-between;
      }
    }

    @media (max-width: 768px) {
      .dashboard {
        padding: 1rem;
        margin-top: 5rem;
      }
      
      .dashboard-grid {
        grid-template-columns: 1fr;
      }
      
      .dashboard-card {
        height: 350px;
      }
    }

    @media (max-width: 480px) {
      .quick-actions {
        flex-direction: column;
      }
      
      .action-btn {
        width: 100%;
        text-align: center;
      }
    }
         
.status-badge {
  white-space: nowrap;      
  display: inline-block;      
}

     
.dashboard {
  flex: 1;
  padding: 2rem;
  max-width: 1600px;      
  margin: 6rem auto 4rem;
  min-height: calc(10vh - 10rem);
  width: 100%;
}

     
.dashboard-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2.5rem;      
  margin-top: 2rem;
}

.dashboard-card {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  padding: 1.8rem;      
  border: 1px solid rgba(255, 255, 255, 0.1);
  transition: transform 0.3s, box-shadow 0.3s;
  height: 400px;
  display: flex;
  flex-direction: column;
}

     
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.2rem;      
  padding-bottom: 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.card-header h2 {
  font-size: 1.6rem;      
}

     
.order-table {
  width: 100%;
  border-collapse: collapse;
}

.order-table td {
  padding: 1.1rem 0.5rem;      
  vertical-align: top;
  white-space: nowrap;      
}

.order-table td:nth-child(2) {
  white-space: normal;      
  max-width: 200px;      
}

     
@media (max-width: 992px) {
  .dashboard {
    max-width: 95%;
    padding: 1.5rem;
  }
  
  .dashboard-grid {
    gap: 2rem;
  }
}

@media (max-width: 768px) {
  .dashboard {
    margin-top: 5rem;
    padding: 1.2rem;
  }
  
  .dashboard-grid {
    grid-template-columns: 1fr;
    gap: 1.8rem;
  }
  
  .dashboard-card {
    padding: 1.5rem;
    height: 380px;
  }
  
  .order-table td {
    white-space: normal;      
  }
}

@media (max-width: 480px) {
  .dashboard {
    padding: 1rem;
  }
  
  .dashboard-card {
    padding: 1.2rem;
    height: 350px;
  }
  
  .card-header h2 {
    font-size: 1.4rem;
  }
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
      50% { transform: translateY(-20px) rotate(5deg); }
      100% { transform: translateY(0) rotate(0deg); }
    }
           
    .receipt-btn {
      display: inline-block;
      margin-top: 0.5rem;
      padding: 0.3rem 0.8rem;
      background: rgba(255, 126, 179, 0.2);
      color: var(--primary);
      border-radius: 4px;
      text-decoration: none;
      font-size: 0.8rem;
      transition: all 0.3s;
      border: 1px solid var(--primary);
    }
    
    .receipt-btn:hover {
      background: var(--primary);
      color: white;
    }
    
    .disabled-receipt {
      opacity: 0.5;
      cursor: not-allowed;
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .status-refunded {
      background: rgba(169, 169, 169, 0.15);
      color: #A9A9A9;
      border: 1px solid rgba(169, 169, 169, 0.25);
      text-decoration: line-through;
    }
           
    .status-badge {
      padding: 0.3rem 0.8rem;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: bold;
      white-space: nowrap;
      display: inline-block;
      width: 100px;
      text-align: center;
    }

    .status-preparing {
      background: rgba(255, 193, 7, 0.2);
      color: var(--warning);
    }

    .status-ready {
      background: rgba(76, 175, 80, 0.2);
      color: var(--success);
    }

    .status-delivered {
      background: rgba(76, 175, 80, 0.2);
      color: var(--success);
    }

    .status-completed {
      background: rgba(76, 175, 80, 0.2);
      color: var(--success);
    }

    .status-confirmed {
      background: rgba(175, 142, 76, 0.2);
      color: rgba(255, 186, 47, 0.99);
    }

    .status-pending {
      background: rgba(255, 193, 7, 0.2);
      color: var(--warning);
    }

    .status-waiting_payment {
      background: rgba(255, 165, 0, 0.2);
      color: #FFA500;
    }

    .status-cancelled {
      background: rgba(244, 67, 54, 0.2);
      color: #F44336;
    }

    .status-on_the_way {
      background: rgba(255, 255, 226, 0.31);
      color: rgba(255, 255, 226, 0.92);
    }

    .status-refunded {
      background: rgba(169, 169, 169, 0.15);
      color: #A9A9A9;
      border: 1px solid rgba(169, 169, 169, 0.25);
      text-decoration: line-through;
    }
    .status-declined {
      background: rgba(169, 169, 169, 0.2);
      color: #A9A9A9;
    }
    .status-pending { background: rgba(255, 193, 7, 0.1); color: #FFC107; border: 1px solid #FFC107; }
        .status-confirmed { background: rgba(76, 175, 80, 0.1); color: #4CAF50; border: 1px solid #4CAF50; }
        .status-paid { background: rgba(33, 150, 243, 0.1); color: #2196F3; border: 1px solid #2196F3; }
        .status-cancelled { background: rgba(244, 67, 54, 0.1); color: #F44336; border: 1px solid #F44336; }
        .status-waiting_payment { background: rgba(255, 165, 0, 0.1); color: #FFA500; border: 1px solid #FFA500; }
        .status-completed { background: rgba(190, 81, 209, 0.1); color:rgb(223, 73, 250); border: 1px solid #9C27B0; }
        .status-declined { background: rgba(158, 158, 158, 0.1); color: #9E9E9E; border: 1px solid #9E9E9E; }
        .status-refunded { background: rgba(121, 85, 72, 0.1); color: #795548; border: 1px solid #795548; }
        .status-failed { background: rgba(96, 125, 139, 0.1); color: #607D8B; border: 1px solid #607D8B; } 
        .status-preparing { background: rgba(255, 152, 0, 0.1); color: #FF9800; border: 1px solid #FF9800; }
        .status-ready { background: rgba(0, 150, 136, 0.1); color: #009688; border: 1px solid #009688; }
        .status-on_the_way { background: rgba(63, 81, 181, 0.1); color: #3F51B5; border: 1px solid #3F51B5; } 
        .status-delivered { background: rgba(139, 195, 74, 0.1); color: #8BC34A; border: 1px solid #8BC34A; }
        .status-no_show { background: rgba(233, 30, 99, 0.1); color: #E91E63; border: 1px solid #E91E63; }
        
        .navbar::after {
          content: "";
          position: absolute;
          bottom: 0;
          left: 0;
          height: 1px;      
          width: 100%;
          background: linear-gradient(to right, var(--primary), var(--secondary));
        }

        .footer::before {
          content: "";
          position: absolute;
          top: 0;
          left: 0;
          height: 1px;      
          width: 100%;
          background: linear-gradient(to right, var(--primary), var(--secondary));
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
      <a href="#" id="navbar__logo">
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

  <div class="dashboard">
    <div class="welcome-section">
      <div class="welcome-message">
        <h1>Welcome back, 맛집  <?php echo htmlspecialchars($userName); ?>!</h1>
        <p>Here's what's happening with your CABS KOREAN experience</p>
      </div>
      <div class="quick-actions">
        <a href="order.php" class="action-btn primary">
          <i class="fas fa-utensils"></i> Order Now
        </a>
        <a href="reservation.php" class="action-btn secondary">
          <i class="fas fa-calendar-alt"></i> Make Reservation
        </a>
      </div>
    </div>

             <div class="dashboard-grid">
                 <div class="dashboard-card">
        <div class="card-header">
          <h2><i class="fas fa-receipt"></i> Recent Orders</h2>
          <a href="user_order_history.php">View All</a>
        </div>
        <div class="card-content">
          <table class="order-table">
            <?php foreach ($recentOrders as $order): 
              $statusClass = 'status-' . strtolower($order['status']);
              $statusText = ucfirst($order['status']);
              
              if ($order['order_type'] == 'delivery') {
                if ($order['status'] == 'delivered') {
                    $statusText = 'Delivered';
                } elseif ($order['status'] == 'on_the_way') {
                    $statusText = 'On the way';
                }
              }
            ?>
              <tr>
                <td>
                  <div class="order-id"><?php echo htmlspecialchars($order['order_number']); ?></div>
                  <div class="order-date"><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></div>
                  <div class="order-type"><?php echo ucfirst($order['order_type']); ?></div>
                  <a href="generate_receipt.php?order_id=<?= $order['id'] ?>" class="receipt-btn">
                    <i class="fas fa-file-download"></i> Download Receipt
                  </a>
                </td>
                <td><?php echo htmlspecialchars($order['items']); ?></td>
                <td>
                  <span class="status-badge <?php echo $statusClass; ?>">
                    <?php echo $statusText; ?>
                  </span>
                </td>
                <td class="text-right">₱<?php echo number_format($order['total_price'], 2); ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($recentOrders)): ?>
              <tr>
                <td colspan="4" style="text-align: center; padding: 2rem 0;">
                  No recent orders found.
                </td>
              </tr>
            <?php endif; ?>
          </table>
        </div>
      </div>

                 <div class="dashboard-card">
        <div class="card-header">
          <h2><i class="fas fa-calendar-check"></i> Reservations</h2>
          <a href="user_reservations.php">View All</a>
        </div>
        <div class="card-content">
          <table class="order-table">
            <?php foreach ($reservations as $reservation): 
                $status = $reservation['display_status'] ?? $reservation['status'];
                $statusClass = 'status-' . strtolower($status);
            ?>
                <tr>
                    <td>
                        <div class="order-id">Reservation #<?= $reservation['id'] ?></div>
                        <div class="order-date">
                            <?= date('M j, Y', strtotime($reservation['reservation_date'])) ?> at 
                            <?= date('g:i A', strtotime($reservation['reservation_time'])) ?>
                        </div>
                        <div class="order-type"><?= $reservation['guests'] ?> guests</div>
                                                     <?php if ($reservation['payment_status'] == 'paid' && $reservation['status'] == 'confirmed'): ?>
                            <a href="generate_reservation_receipt.php?reservation_id=<?= $reservation['id'] ?>" class="receipt-btn">
                                <i class="fas fa-file-download"></i> Download Receipt
                            </a>
                        <?php else: ?>
                            <span class="receipt-btn disabled-receipt" title="Receipt available after confirmation">
                                <i class="fas fa-file-download"></i> Download Receipt
                            </span>
                        <?php endif; ?>
                        <?php if ($status == 'waiting_payment'): ?>
                            <a href="reservation_checkout.php?reservation_id=<?= $reservation['id'] ?>" class="payment-link">
                                <i class="fas fa-credit-card"></i> Complete Payment
                            </a>
                        <?php elseif ($reservation['status'] == 'paid' && isset($reservation['table_number'])): ?>
                            <div class="order-type">Table: <?= $reservation['table_number'] ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($status == 'paid'): ?>
                            <div>Status: Confirmed & Paid</div>
                        <?php elseif ($status == 'waiting_payment'): ?>
                            <div>Payment Required</div>
                        <?php else: ?>
                            <div>Status: <?= ucfirst($status) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge <?= $statusClass ?>">
                        <?php 
                        if ($status == 'waiting_payment') {
                            echo 'Payment';
                        } else {
                            echo ucfirst(str_replace('_', ' ', $status));
                        }
                        ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($reservations)): ?>
                <tr>
                    <td colspan="3" style="text-align: center; padding: 2rem 0;">
                        No upcoming reservations found.
                    </td>
                </tr>
            <?php endif; ?>
          </table>
        </div>
      </div>
    </div>
  </div>

  <footer class="footer">
    <div class="footer__container">
      <p>&copy; <?php echo date('Y'); ?> CABS KOREAN. All Rights Reserved.</p>
    </div>
  </footer>

  <script>
    // Simple animation for dashboard cards when they come into view
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = 1;
          entry.target.style.transform = 'translateY(0)';
        }
      });
    }, { threshold: 0.1 });

    document.querySelectorAll('.dashboard-card').forEach(card => {
      card.style.opacity = 0;
      card.style.transform = 'translateY(20px)';
      card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
      observer.observe(card);
    });
  </script>
</body>
</html>