<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$query = "SELECT o.* FROM orders o WHERE o.user_id = ? ORDER BY o.order_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($orders as &$order) {
    $items_query = "SELECT oi.*, mi.name as item_name 
                   FROM order_items oi
                   JOIN menu_items mi ON oi.menu_item_id = mi.id
                   WHERE oi.order_id = ?";
    $items_stmt = $conn->prepare($items_query);
    $items_stmt->bind_param("i", $order['id']);
    $items_stmt->execute();
    $order['items'] = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $items_stmt->close();
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History | CABS KOREAN</title>
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
            --warning: #FFC107;
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

             
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

             
        .table-container {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow: hidden;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th {
            text-align: left;
            padding: 1rem;
            background: rgba(255, 126, 179, 0.1);
            color: var(--primary);
            font-weight: 600;
        }

        .orders-table td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .orders-table tr:last-child td {
            border-bottom: none;
        }

        .scrollable-table {
            max-height: 70vh;
            overflow-y: auto;
        }

             
        .scrollable-table::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .scrollable-table::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }

        .scrollable-table::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 3px;
        }

             
        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            width: 100px;
            text-align: center;
        }

        .status-delivered {
            background: rgba(76, 175, 80, 0.2);
            color: var(--success);
        }

        .status-ready {
            background: rgba(76, 175, 80, 0.2);
            color: var(--success);
        }

        .status-preparing {
            background: rgba(255, 193, 7, 0.2);
            color: var(--warning);
        }

        .status-on_the_way {
            background: rgba(255, 193, 7, 0.2);
            color: var(--warning);
        }

        .status-cancelled {
            background: rgba(244, 67, 54, 0.2);
            color: #F44336;
        }

             
        .items-list {
            list-style: none;
        }

        .items-list li {
            margin-bottom: 0.3rem;
        }

        .item-quantity {
            color: var(--primary);
            font-weight: 600;
            margin-right: 0.5rem;
        }

             
        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: rgba(255, 126, 179, 0.1);
        }

        .receipt-btn {
            background: var(--primary);
            color: white;
        }

        .receipt-btn:hover {
            background: var(--secondary);
        }

             
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: rgba(255, 255, 255, 0.5);
        }

             
        @media (max-width: 768px) {
            .orders-table {
                display: block;
            }
            
            .orders-table thead {
                display: none;
            }
            
            .orders-table tr {
                display: block;
                padding: 1rem;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .orders-table td {
                display: flex;
                justify-content: space-between;
                padding: 0.5rem 0;
                border-bottom: none;
            }
            
            .orders-table td::before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--primary);
                margin-right: 1rem;
            }
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
        background: rgba(255, 0, 0, 0.2);
        border: 1px solid rgba(255, 0, 0, 0.5);
        color: #ff6b6b;
        }

        .logout-btn:hover {
        background: rgba(255, 0, 0, 0.3);
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
        .navbar::after {
          content: "";
          position: absolute;
          bottom: 0;
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

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Order History</h1>
            <a href="order.php" class="action-btn receipt-btn">
                <i class="fas fa-utensils"></i> New Order
            </a>
        </div>

        <div class="table-container">
            <div class="scrollable-table">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($orders)): ?>
                            <?php foreach ($orders as $order): 
                                $statusClass = 'status-' . strtolower(str_replace(' ', '_', $order['status']));
                            ?>
                                <tr>
                                    <td data-label="Order #"><?= $order['order_number'] ?></td>
                                    <td data-label="Date">
                                        <?= date('M j, Y', strtotime($order['order_date'])) ?>
                                        <br>
                                        <?= date('g:i A', strtotime($order['order_date'])) ?>
                                    </td>
                                    <td data-label="Items">
                                        <ul class="items-list">
                                            <?php foreach ($order['items'] as $item): ?>
                                                <li>
                                                    <span class="item-quantity">x<?= $item['quantity'] ?></span>
                                                    <?= $item['item_name'] ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </td>
                                    <td data-label="Total">₱<?= number_format($order['total_price'], 2) ?></td>
                                    <td data-label="Status">
                                        <span class="status-badge <?= $statusClass ?>">
                                            <?= ucwords(str_replace('_', ' ', $order['status'])) ?>
                                        </span>
                                    </td>
                                    <td data-label="Actions">
                                        <a href="generate_receipt.php?order_id=<?= $order['id'] ?>" 
                                           class="action-btn btn-outline">
                                            <i class="fas fa-file-download"></i> Receipt
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <i class="fas fa-utensils" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                    <p>You don't have any orders yet.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>