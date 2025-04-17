<?php
include 'audit_logger.php';
session_start();
include 'db_connect.php';
include 'email_config.php';

$isLoggedIn = isset($_SESSION['user_id']);

if ($isLoggedIn) {
    $user_id = $_SESSION['user_id'];
}

$reservationMessage = ''; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $guests = $_POST['guests'];

    $conn = new mysqli('localhost', 'root', '', 'cabs_korean');

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "INSERT INTO reservations (user_id, name, email, phone, reservation_date, reservation_time, guests, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        $reservationMessage = "Database error: " . $conn->error;
    } else {
        $stmt->bind_param("isssssi", $_SESSION['user_id'], $name, $email, $phone, $date, $time, $guests);

        if ($stmt->execute()) {
            $reservation_id = $stmt->insert_id;
            log_audit_action('reservation_created', 'reservations', $reservation_id, null, [
                'date' => $date,
                'time' => $time,
                'guests' => $guests,
                'status' => 'pending'
            ]);
            $reservationMessage = "Your reservation is pending confirmation. We will notify you once confirmed.";
            
            $subject = "Reservation Request Received";
            $body = "Dear $name,<br><br>
                    Your reservation request has been received with the following details:<br><br>
                    Date: $date<br>
                    Time: $time<br>
                    Guests: $guests<br><br>
                    We will review your request and notify you once it's confirmed.<br><br>
                    Thank you for choosing CABS KOREAN!";
            
            sendConfirmationEmail($email, $name, $subject, $body);
        } else {
            $reservationMessage = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Submitted - CABS KOREAN</title>
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
            background: rgba(30, 30, 30, 0.9);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 126, 179, 0.2);
        }

        .navbar__logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: bold;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        .navbar__logo img {
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
            margin-left: 1.5rem;
        }

        .navbar__links {
            color: var(--light);
            text-decoration: none;
            transition: color 0.3s;
        }

        .navbar__links:hover {
            color: var(--primary);
        }

        .button {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            transition: transform 0.3s, box-shadow 0.3s;
            display: inline-block;
            border: none;
            cursor: pointer;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 126, 179, 0.3);
        }

             
        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
            margin-top: 10rem;
        }

             
        .confirmation-container {
            background: rgba(40, 40, 40, 0.8);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .confirmation-icon {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        .confirmation-container h2 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        .confirmation-message {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: rgba(50, 50, 50, 0.5);
            border-radius: 8px;
            border-left: 4px solid var(--primary);
            font-size: 1.1rem;
        }

             
        .map-container {
            background: rgba(40, 40, 40, 0.8);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            height: 100%;
        }

        .map-container h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--light);
        }

        .map-container iframe {
            width: 100%;
            height: calc(100% - 3rem);
            border: none;
            border-radius: 8px;
        }

             
        .footer {
            background: rgba(30, 30, 30, 0.9);
            padding: 1.5rem;
            text-align: center;
            margin-top: 12.5rem;
            border-top: 1px solid rgba(255, 126, 179, 0.2);
        }

             
        @media (max-width: 1024px) {
            .main-content {
                grid-template-columns: 1fr;
                max-width: 800px;
            }
            
            .map-container iframe {
                height: 400px;
            }
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                padding: 1rem;
            }

            .navbar__menu {
                margin-top: 1rem;
                flex-direction: column;
                width: 100%;
            }

            .navbar__item {
                margin: 0.5rem 0;
                width: 100%;
                text-align: center;
            }

            .main-content {
                padding: 0 1rem;
                gap: 1rem;
            }

            .confirmation-container,
            .map-container {
                padding: 1.5rem;
            }
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
        <a href="index.php" class="navbar__logo">
            <img src="images/cabs.png" alt="CABS KOREAN Logo"> CABS
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
                <?php if ($isLoggedIn): ?>
                  <a href="logout.php" class="button logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                  </a>
                <?php else: ?>
                    <a href="login.php" class="button">Login</a>
                <?php endif; ?>
            </li>
        </ul>
    </nav>

             <div class="main-content">
                     <div class="confirmation-container">
            <div class="confirmation-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <h2>Reservation Submitted</h2>
            <div class="confirmation-message">
                <?php echo $reservationMessage; ?>
            </div>
            <a href="index.php" class="button">Return to Home</a>
        </div>

                     <div class="map-container">
            <h2>Find Us Here</h2>
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d123270.38696961218!2d120.61790861472937!3d15.092324466096771!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3396e5f798d7cb91%3A0x6ab2cdea9d0e4712!2sCabs%20Korean%20Restaurant!5e0!3m2!1sen!2sph!4v1726732236700!5m2!1sen!2sph" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </div>

</body>
</html>