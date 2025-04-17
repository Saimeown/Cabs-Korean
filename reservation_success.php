<?php
session_start();
include 'audit_logger.php';
require 'vendor/autoload.php';

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cabs_korean";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$reservation_id = $_GET['reservation_id'] ?? $_SESSION['current_reservation_id'] ?? 0;

try {
    $paymongo_secret = 'sk_test_RLTxKX1MFbsCUdPc1YD4s5jp';
    $auth = base64_encode($paymongo_secret . ':');
    
    $reservation_sql = "SELECT * FROM reservations WHERE id = ?";
    $stmt = $conn->prepare($reservation_sql);
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();
    $stmt->close();

    if (!$reservation) {
        throw new Exception('Reservation not found');
    }

    if (empty($reservation['payment_intent_id'])) {
        throw new Exception('Missing payment reference');
    }

    $ch = curl_init('https://api.paymongo.com/v1/checkout_sessions/' . $reservation['payment_intent_id']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . $auth
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('Failed to verify payment with PayMongo. HTTP Code: ' . $httpCode);
    }

    $responseData = json_decode($response, true);
    
    if (!isset($responseData['data']['attributes']['payments'][0]['attributes']['status'])) {
        throw new Exception('Invalid PayMongo response structure');
    }
    
    $paymentStatus = $responseData['data']['attributes']['payments'][0]['attributes']['status'];
    if ($paymentStatus !== 'paid') {
        throw new Exception('Payment not completed. Status: ' . $paymentStatus);
    }

    $update_sql = "UPDATE reservations SET 
                payment_status = 'paid',
                status = 'confirmed',
                payment_date = NOW()
                WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $stmt->close();

    $reservation_sql = "SELECT * FROM reservations WHERE id = ?";
    $stmt = $conn->prepare($reservation_sql);
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();
    $stmt->close();

    if (!$reservation) {
        throw new Exception('Failed to retrieve updated reservation data');
    }

    log_audit_action('reservation_paid', 'reservations', $reservation_id, null, [
        'payment_intent_id' => $reservation['payment_intent_id'],
        'amount_paid' => $reservation['amount_paid']
    ]);

    $success = true;
    $message = "Your payment was successful! Your reservation is confirmed.";
} catch (Exception $e) {
    $success = false;
    $message = "Payment verification failed: " . $e->getMessage();
    
    $update_sql = "UPDATE reservations SET payment_status = 'failed' WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $stmt->close();
    
    error_log("Reservation payment verification failed: " . $e->getMessage());
    
    $reservation_sql = "SELECT * FROM reservations WHERE id = ?";
    $stmt = $conn->prepare($reservation_sql);
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Confirmation - CABS KOREAN</title>
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
            --error: #F44336;
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

             
        .confirmation-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }

        .confirmation-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 3rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            max-width: 600px;
            width: 100%;
            margin: 2rem 0;
            position: relative;
            overflow: hidden;
        }

        .confirmation-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
        }

        .confirmation-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            color: <?php echo $success ? 'var(--success)' : 'var(--error)'; ?>;
        }

        .confirmation-title {
            font-size: 2rem;
            margin-bottom: 1rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        .confirmation-message {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            color: rgba(255, 255, 255, 0.8);
        }

        .reservation-details {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
        }

        .detail-group {
            margin-bottom: 1rem;
        }

        .detail-group:last-child {
            margin-bottom: 0;
        }

        .detail-label {
            color: var(--primary);
            font-weight: 600;
            display: block;
            margin-bottom: 0.3rem;
        }

        .detail-value {
            color: var(--light);
        }

        .button {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .button:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(255, 126, 179, 0.3);
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
            .confirmation-card {
                padding: 2rem 1.5rem;
            }
            
            .confirmation-title {
                font-size: 1.5rem;
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
        </div>
    </nav>

    <div class="confirmation-container">
        <div class="confirmation-card">
            <div class="confirmation-icon">
                <?php if ($success): ?>
                    <i class="fas fa-check-circle"></i>
                <?php else: ?>
                    <i class="fas fa-exclamation-circle"></i>
                <?php endif; ?>
            </div>
            
            <h1 class="confirmation-title">
                <?php echo $success ? 'Payment Successful!' : 'Payment Issue'; ?>
            </h1>
            
            <p class="confirmation-message"><?php echo $message; ?></p>
            
            <?php if (isset($reservation)): ?>
                <div class="reservation-details">
                    <div class="detail-group">
                        <span class="detail-label">Reservation Number</span>
                        <span class="detail-value">#<?php echo htmlspecialchars($reservation['id'] ?? 'N/A'); ?></span>
                    </div>
                    
                    <div class="detail-group">
                        <span class="detail-label">Date & Time</span>
                        <span class="detail-value">
                            <?php 
                            if (isset($reservation['reservation_date'])) {
                                echo date('F j, Y', strtotime($reservation['reservation_date']));
                            } else {
                                echo 'N/A';
                            }
                            ?> at 
                            <?php 
                            if (isset($reservation['reservation_time'])) {
                                echo date('g:i A', strtotime($reservation['reservation_time']));
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </span>
                    </div>
                    
                    <div class="detail-group">
                        <span class="detail-label">Guests</span>
                        <span class="detail-value"><?php echo htmlspecialchars($reservation['guests'] ?? 'N/A'); ?></span>
                    </div>
                    
                    <div class="detail-group">
                        <span class="detail-label">Payment Date</span>
                        <span class="detail-value"><?php echo date('F j, Y g:i A'); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <a href="index.php" class="button">
                <i class="fas fa-home"></i> Return to Dashboard
            </a>
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