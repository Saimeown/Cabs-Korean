<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';
include 'audit_logger.php';

$reservation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$query = "SELECT * FROM reservations WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $reservation_id, $_SESSION['user_id']);
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reservation) {
    $_SESSION['error'] = "Reservation not found or you don't have permission to cancel it";
    header("Location: user_reservations.php");
    exit();
}

if ($reservation['status'] == 'cancelled') {
    $_SESSION['error'] = "This reservation is already cancelled";
    header("Location: reservation_details.php?id=".$reservation_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $conn->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
    $stmt->bind_param("i", $reservation_id);
    
    if ($stmt->execute()) {
        log_audit_action('reservation_cancelled', 'reservations', $reservation_id, [
            'old_status' => $reservation['status'],
            'old_payment_status' => $reservation['payment_status']
        ], [
            'new_status' => 'cancelled',
            'payment_status' => $reservation['payment_status']
        ]);
        
        $_SESSION['success'] = "Reservation #".$reservation_id." has been cancelled successfully";
        $stmt->close();
        $conn->close();
        header("Location: reservation_details.php?id=".$reservation_id);
        exit();
    } else {
        $_SESSION['error'] = "Error cancelling reservation. Please try again.";
        $stmt->close();
        $conn->close();
        header("Location: reservation_details.php?id=".$reservation_id);
        exit();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Reservation | CABS KOREAN</title>
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
            --danger: #F44336;
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

     
        .confirmation-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            border: 1px solid rgba(255, 126, 179, 0.2);
            padding: 2rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .reservation-info {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }

        .info-label {
            color: var(--primary);
            font-weight: 600;
        }

        .info-value {
            text-align: right;
        }

     
        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-align: center;
        }

        .status-confirmed {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
            border: 1px solid #4CAF50;
        }

     
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
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
            cursor: pointer;
            border: none;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #d32f2f;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(244, 67, 54, 0.3);
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

     
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-danger {
            background: rgba(244, 67, 54, 0.2);
            border-left: 4px solid var(--danger);
            color: #F44336;
        }

     
        @media (max-width: 768px) {
            .navbar__container {
                margin-left: 0;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-btn {
                width: 100%;
                justify-content: center;
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
    </style>
</head>
<body>
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
            <h1 class="page-title">Cancel Reservation</h1>
            <a href="reservation_details.php?id=<?= $reservation_id ?>" class="action-btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Details
            </a>
        </div>

        <div class="confirmation-card">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="reservation-info">
                <h3 style="margin-bottom: 1.5rem; color: var(--primary);">Reservation Details</h3>
                
                <div class="info-item">
                    <span class="info-label">Reservation #</span>
                    <span class="info-value"><?= $reservation['id'] ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Status</span>
                    <span class="info-value">
                        <span class="status-badge status-confirmed">
                            <?= ucfirst($reservation['status']) ?>
                        </span>
                    </span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Date</span>
                    <span class="info-value"><?= date('F j, Y', strtotime($reservation['reservation_date'])) ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Time</span>
                    <span class="info-value"><?= date('g:i A', strtotime($reservation['reservation_time'])) ?></span>
                </div>
                
                <?php if ($reservation['table_number']): ?>
                <div class="info-item">
                    <span class="info-label">Table Number</span>
                    <span class="info-value">Table <?= $reservation['table_number'] ?></span>
                </div>
                <?php endif; ?>
                
                <div class="info-item">
                    <span class="info-label">Guests</span>
                    <span class="info-value"><?= $reservation['guests'] ?> person(s)</span>
                </div>
            </div>

            <form method="POST">
                <h3 style="margin-bottom: 1rem; color: var(--danger);">Are you sure you want to cancel this reservation?</h3>
                <p style="margin-bottom: 1.5rem; opacity: 0.8;">This action cannot be undone. Any cancellation made is not subject to any refund policies.</p>
                
                <div class="action-buttons">
                    <button type="submit" class="action-btn btn-danger">
                        <i class="fas fa-times"></i> Confirm Cancellation
                    </button>
                    <a href="reservation_details.php?id=<?= $reservation_id ?>" class="action-btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Go Back
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>