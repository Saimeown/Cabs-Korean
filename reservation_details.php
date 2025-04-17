<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Get reservation ID from URL
$reservation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get reservation details
$query = "SELECT r.*, u.fullname, u.email, u.phone 
          FROM reservations r
          JOIN users u ON r.user_id = u.id
          WHERE r.id = ? AND r.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $reservation_id, $_SESSION['user_id']);
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reservation) {
    header("Location: user_reservations.php");
    exit();
}

$display_status = $reservation['status'];
if ($reservation['status'] == 'confirmed' && $reservation['payment_status'] == 'waiting_payment') {
    $display_status = 'waiting_payment';
} elseif ($reservation['status'] == 'cancelled' && $reservation['payment_status'] == 'pending') {
    $display_status = 'declined';
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Details | CABS KOREAN</title>
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

        .details-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .detail-group {
            margin-bottom: 1.5rem;
        }

        .detail-label {
            color: var(--primary);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .detail-value {
            font-size: 1.1rem;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
            border-left: 3px solid var(--primary);
        }

        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            width: 140px;
            text-align: center;
        }

        .status-pending { background: rgba(255, 193, 7, 0.1); color: #FFC107; border: 1px solid #FFC107; }
        .status-confirmed { background: rgba(76, 175, 80, 0.1); color: #4CAF50; border: 1px solid #4CAF50; }
        .status-paid { background: rgba(33, 150, 243, 0.1); color: #2196F3; border: 1px solid #2196F3; }
        .status-cancelled { background: rgba(244, 67, 54, 0.1); color: #F44336; border: 1px solid #F44336; }
        .status-waiting_payment { background: rgba(255, 165, 0, 0.1); color: #FFA500; border: 1px solid #FFA500; }
        .status-completed { background: rgba(190, 81, 209, 0.1); color:rgb(223, 73, 250); border: 1px solid #9C27B0; }
        .status-declined { background: rgba(158, 158, 158, 0.1); color: #9E9E9E; border: 1px solid #9E9E9E; }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 126, 179, 0.3);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: rgba(255, 126, 179, 0.1);
            transform: translateY(-2px);
        }

        .btn-danger {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid rgba(244, 67, 54, 0.5);
            color: #F44336;
        }

        .btn-danger:hover {
            background: rgba(244, 67, 54, 0.3);
        }

        .qr-section {
            margin-top: 2rem;
            text-align: center;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            border: 1px dashed rgba(255, 126, 179, 0.3);
        }

        .qr-code {
            max-width: 200px;
            margin: 0 auto 1rem;
            display: block;
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

        @media (max-width: 768px) {
            .navbar__container {
                margin-left: 0;
            }

            .navbar__menu {
                margin-right: 0;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-btn {
                width: 100%;
                text-align: center;
                justify-content: center;
            }
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
                    <a href="reservation.php" class="navbar__links">Reservation</a>
                </li>
                <li class="navbar__item">
                    <a href="update_user.php" class="navbar__links">Profile</a>
                </li>
                <li class="navbar__btn">
                    <a href="logout.php" class="button logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Reservation Details</h1>
            <a href="user_reservations.php" class="action-btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Reservations
            </a>
        </div>

        <div class="details-card">
            <div class="details-grid">
                <div>
                    <div class="detail-group">
                        <span class="detail-label">Reservation Number</span>
                        <div class="detail-value"><?= $reservation['id'] ?></div>
                    </div>
                    
                    <div class="detail-group">
                        <span class="detail-label">Status</span>
                        <div class="detail-value">
                            <span class="status-badge status-<?= strtolower($display_status) ?>">
                                <?= 
                                    $display_status === 'declined' ? 'Declined' : 
                                    ucwords(str_replace('_', ' ', $display_status)) 
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="detail-group">
                        <span class="detail-label">Date</span>
                        <div class="detail-value">
                            <?= date('F j, Y', strtotime($reservation['reservation_date'])) ?>
                        </div>
                    </div>
                    
                    <div class="detail-group">
                        <span class="detail-label">Time</span>
                        <div class="detail-value">
                            <?= date('g:i A', strtotime($reservation['reservation_time'])) ?>
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="detail-group">
                        <span class="detail-label">Guests</span>
                        <div class="detail-value">
                            <?= $reservation['guests'] ?> person(s)
                        </div>
                    </div>
                    
                    <?php if ($reservation['table_number']): ?>
                    <div class="detail-group">
                        <span class="detail-label">Table Number</span>
                        <div class="detail-value">
                            Table <?= $reservation['table_number'] ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-group">
                        <span class="detail-label">Name</span>
                        <div class="detail-value">
                            <?= htmlspecialchars($reservation['fullname']) ?>
                        </div>
                    </div>
                    
                    <div class="detail-group">
                        <span class="detail-label">Contact</span>
                        <div class="detail-value">
                            <?= htmlspecialchars($reservation['phone']) ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($display_status === 'waiting_payment'): ?>
                <div class="qr-section">
                    <h3>Complete Your Payment</h3>
                    <p>Please complete your payment to confirm your reservation</p>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode('https://yourdomain.com/reservation_checkout.php?reservation_id='.$reservation['id']) ?>" 
                         alt="Payment QR Code" class="qr-code">
                    <a href="reservation_checkout.php?reservation_id=<?= $reservation['id'] ?>" class="action-btn btn-primary">
                        <i class="fas fa-credit-card"></i> Proceed to Payment
                    </a>
                </div>
            <?php endif; ?>

            <div class="action-buttons">
                <?php if ($display_status === 'waiting_payment'): ?>
                    <a href="reservation_checkout.php?reservation_id=<?= $reservation['id'] ?>" class="action-btn btn-primary">
                        <i class="fas fa-credit-card"></i> Pay Now
                    </a>
                <?php endif; ?>
                
                <?php if ($reservation['status'] === 'confirmed' && $reservation['payment_status'] === 'paid'): ?>
                    <a href="generate_reservation_receipt.php?reservation_id=<?= $reservation['id'] ?>" class="action-btn btn-outline">
                        <i class="fas fa-file-download"></i> Download Receipt
                    </a>
                <?php endif; ?>
                
                <?php if ($reservation['status'] !== 'cancelled' && $reservation['status'] !== 'completed'): ?>
                    <button onclick="confirmCancel()" class="action-btn btn-danger">
                        <i class="fas fa-times"></i> Cancel Reservation
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function confirmCancel() {
            if (confirm("Are you sure you want to cancel this reservation?")) {
                window.location.href = "cancel_reservation.php?id=<?= $reservation['id'] ?>";
            }
        }
    </script>
</body>
</html>