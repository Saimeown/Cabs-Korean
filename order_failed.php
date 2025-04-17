<?php
session_start();
$error = isset($_GET['error']) ? $_GET['error'] : 'Payment failed. Please try again.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Failed - CABS KOREAN</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="cabs.png" type="image/png">

</head>
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
                    <a href="logout.php" class="button">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="main">
        <div class="order-failed__container">
            <h1>Order Failed</h1>
            <p><?php echo htmlspecialchars($error); ?></p>
            <a href="order.php" class="button">Try Again</a>
        </div>
    </div>

    <footer class="footer">
        <div class="footer__container">
            <p>&copy; 2024 CABS KOREAN. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html>