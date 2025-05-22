<?php
session_start();
require __DIR__ . '/vendor/autoload.php';

use Paymongo\PaymongoClient;

try {
    $client = new PaymongoClient('');
    
    $order_id = $_GET['order_id'] ?? $_SESSION['current_order_id'] ?? 0;

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "cabs_korean";
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $order_sql = "SELECT payment_intent_id, order_type FROM orders WHERE id = ?";
    $stmt = $conn->prepare($order_sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();

    if (!$order || empty($order['payment_intent_id'])) {
        throw new Exception('Order not found or missing payment reference');
    }

    $paymentIntent = $client->paymentIntents->retrieve($order['payment_intent_id']);
    
    if ($paymentIntent->attributes->status !== 'succeeded') {
        throw new Exception('Payment not completed');
    }


    $new_status = 'preparing';

    $update_sql = "UPDATE orders SET 
                payment_status = 'paid',
                status = ?
                WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $new_status, $order_id);
    $stmt->execute();
    $stmt->close();

    $order_sql = "SELECT * FROM orders WHERE id = ?";
    $stmt = $conn->prepare($order_sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();

} catch (Exception $e) {
    error_log("Payment verification failed: " . $e->getMessage());
    header("Location: index.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - CABS KOREAN</title>
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

             
        .main {
            padding: 6rem 2rem 4rem;
            min-height: 100vh;
        }

        .success-container {
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

        .success-container::before {
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

        .success-container::after {
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

        .success-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .success-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        .success-icon {
            font-size: 4rem;
            color: var(--success);
            margin-bottom: 1rem;
        }

        .order-info {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
        }

        .order-info h2 {
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            color: var(--primary);
        }

        .order-details {
            margin-top: 1.5rem;
        }

        .order-details h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .order-items {
            margin-top: 1rem;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-name {
            flex: 2;
        }

        .item-quantity {
            flex: 1;
            text-align: center;
        }

        .item-price {
            flex: 1;
            text-align: right;
        }

        .total-summary {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid rgba(255, 126, 179, 0.3);
            text-align: right;
        }

        .total-summary p {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
        }

        .total-amount {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary);
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
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
            .success-header h1 {
                font-size: 2rem;
            }
            
            .order-info h2 {
                font-size: 1.5rem;
            }
            
            .order-item {
                flex-direction: column;
            }
            
            .item-name, .item-quantity, .item-price {
                text-align: left;
                margin-bottom: 0.3rem;
            }
            
            .main {
                padding: 5rem 1rem 3rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar__container">
            <a href="index.php" id="navbar__logo">
                <img src="images/cabs.jpg" alt="CABS KOREAN Logo">CABS
            </a>
            
            <ul class="navbar__menu">
                <li class="navbar__item">
                    <a href="index.php" class="navbar__links">Home</a>
                </li>
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
                    <a href="logout.php" class="button primary-btn">Logout</a> 
                </li>
            </ul>
        </div>
    </nav>

    <div class="main">
        <div class="success-container">
            <div class="success-header">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1>Payment Successful!</h1>
                <p>Thank you for your order. We've received your payment.</p>
            </div>

            <div class="order-info">
                <h2>Order #<?php echo htmlspecialchars($order['order_number']); ?></h2>
                
                <div class="order-details">
                    <h3>Order Summary</h3>
                    <div class="order-items">
                        <?php 
                        // Display order items
                        $items_sql = "SELECT mi.name, oi.quantity, oi.unit_price 
                                     FROM order_items oi 
                                     JOIN menu_items mi ON oi.menu_item_id = mi.id 
                                     WHERE oi.order_id = ?";
                        $stmt = $conn->prepare($items_sql);
                        $stmt->bind_param("i", $order_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        while ($item = $result->fetch_assoc()): ?>
                            <div class="order-item">
                                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="item-quantity"><?php echo $item['quantity']; ?> x</div>
                                <div class="item-price">₱<?php echo number_format($item['unit_price'], 2); ?></div>
                            </div>
                        <?php endwhile; 
                        $stmt->close();
                        ?>
                    </div>
                    
                    <div class="total-summary">
                        <p><strong>Subtotal:</strong> ₱<?php echo number_format($order['total_price'], 2); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
                        <p><strong>Payment Status:</strong> <?php echo htmlspecialchars($order['payment_status']); ?></p>
                        <p class="total-amount">Total: ₱<?php echo number_format($order['total_price'], 2); ?></p>
                    </div>
                </div>
            </div>

            <div class="action-buttons">
                <a href="index.php" class="button primary-btn">
                    <i class="fas fa-home"></i> Return to Home
                </a>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer__container">
            <p>&copy; <?php echo date('Y'); ?> CABS KOREAN. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html>
<?php $conn->close(); ?>