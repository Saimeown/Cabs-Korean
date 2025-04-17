<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$confirmationMessage = ''; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $guests = $_POST['guests'];
} else {
    header("Location: reservation.php"); 
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Confirmation - CABS KOREAN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
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
            --gold: #FFD700;
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

             
        body::-webkit-scrollbar {
            width: 8px;
        }

        body::-webkit-scrollbar-track {
            background: var(--dark);
        }

        body::-webkit-scrollbar-thumb {
            background: linear-gradient(var(--primary), var(--secondary));
            border-radius: 10px;
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
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            background: rgba(18, 18, 18, 0.95);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
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
            font-size: 2rem;
            font-weight: 700;
            text-decoration: none;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            transition: all 0.3s;
        }

        #navbar__logo:hover {
            transform: scale(1.05);
        }

        #navbar__logo img {
            height: 50px;
            margin-right: 10px;
            border-radius: 50%;
            transition: all 0.3s;
        }

        #navbar__logo:hover img {
            transform: rotate(15deg);
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
            position: relative;
            padding: 0.5rem 0;
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
            animation: underlineGrow 0.3s ease-out;
        }

        @keyframes underlineGrow {
            from { transform: scaleX(0); }
            to { transform: scaleX(1); }
        }

        .button {
            padding: 0.8rem 1.5rem;
            border-radius: 30px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
            text-align: center;
        }

        .button.primary {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
        }

        .button.secondary {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 126, 179, 0.4);
        }

        .logout-btn {
            background: rgba(255, 0, 0, 0.2);
            border: 2px solid rgba(255, 0, 0, 0.5);
            color: #ff6b6b;
        }

        .logout-btn:hover {
            background: rgba(255, 0, 0, 0.3);
        }

             
        .main {
            min-height: calc(100vh - 150px);
            padding: 4rem 2rem;
            position: relative;
        }

             
        .floating-element {
            position: absolute;
            opacity: 0.1;
            z-index: 0;
            pointer-events: none;
        }

        .floating-element:nth-child(1) {
            width: 400px;
            height: 400px;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            top: 10%;
            left: 5%;
            animation: float 8s ease-in-out infinite;
            filter: blur(30px);
        }

        .floating-element:nth-child(2) {
            width: 300px;
            height: 300px;
            background: linear-gradient(45deg, var(--secondary), var(--primary));
            border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
            bottom: 15%;
            right: 10%;
            animation: float 10s ease-in-out infinite reverse;
            filter: blur(40px);
        }

        @keyframes float {
            0% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(5deg); }
            100% { transform: translateY(0) rotate(0deg); }
        }

             
        .reservation-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 3rem;
            background: rgb(26, 26, 26);
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 1;
            transition: all 0.3s;
        }

        .reservation-container:hover {
            box-shadow: 0 25px 50px rgba(255, 126, 179, 0.3);
            border-color: rgba(255, 126, 179, 0.4);
        }

        .reservation-container::before {
            content: "";
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            z-index: -1;
            border-radius: 18px;
            opacity: 0.3;
        }

        .reservation-container h2 {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            font-family: 'Playfair Display', serif;
            text-align: center;
            position: relative;
        }

        .reservation-container h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border-radius: 3px;
        }

        .reservation-details {
            margin-bottom: 3rem;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            border-left: 4px solid var(--primary);
        }

        .reservation-details p {
            margin: 1rem 0;
            font-size: 1.1rem;
            padding: 0.5rem 0;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.1);
        }

        .reservation-details strong {
            color: var(--primary);
            display: inline-block;
            width: 180px;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 2rem;
        }

             
        .footer {
            background: linear-gradient(to right, rgba(18, 18, 18, 0.9), rgba(40, 40, 40, 0.9));
            padding: 2rem;
            text-align: center;
            border-top: 1px solid rgba(255, 126, 179, 0.2);
            position: relative;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 1px;
            background: linear-gradient(to right, transparent, var(--primary), transparent);
        }

        .footer__container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer p {
            color: var(--light);
            opacity: 0.8;
            margin-bottom: 1rem;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .social-links a {
            color: var(--light);
            font-size: 1.5rem;
            transition: all 0.3s;
        }

        .social-links a:hover {
            color: var(--primary);
            transform: translateY(-3px);
        }

             
        @media (max-width: 992px) {
            .reservation-container {
                padding: 2.5rem;
            }
        }

        @media (max-width: 768px) {
            .reservation-container {
                padding: 2rem;
                margin: 2rem 1rem;
            }
            
            .reservation-container h2 {
                font-size: 2.2rem;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 1rem;
            }
            
            .button {
                width: 100%;
            }
            
            .reservation-details p {
                font-size: 1rem;
            }
            
            .reservation-details strong {
                width: 120px;
                display: block;
                margin-bottom: 0.3rem;
            }
        }

        @media (max-width: 576px) {
            .reservation-container {
                padding: 1.5rem;
            }
            
            .reservation-container h2 {
                font-size: 2rem;
            }
            
            .main {
                padding: 2rem 1rem;
            }
        }

             
        .fade-in {
            animation: fadeIn 0.8s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
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

    .logout-btn {
      background: rgba(255, 0, 0, 0.2);
      border: 1px solid rgba(255, 0, 0, 0.5);
      color: #ff6b6b;
    }

    .logout-btn:hover {
      background: rgba(255, 0, 0, 0.3);
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
    <!-- Floating Background Elements -->
    <div class="floating-element"></div>
    <div class="floating-element"></div>

    <!-- Navbar - Consistent with other pages -->
    <nav class="navbar" id="navbar">
        <div class="navbar__container">
            <a href="index.php" id="navbar__logo">
                <img src="images/cabs.png" alt="CABS KOREAN Logo"> CABS
            </a>
            
     
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main">
        <div class="reservation-container fade-in">
            <h2>Reservation Confirmation</h2>
            
            <div class="reservation-details">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($name); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($phone); ?></p>
                <p><strong>Date:</strong> <?php echo htmlspecialchars($date); ?></p>
                <p><strong>Time:</strong> <?php echo htmlspecialchars($time); ?></p>
                <p><strong>Number of Guests:</strong> <?php echo htmlspecialchars($guests); ?></p>
            </div>

            <form action="submit_reservation.php" method="POST">
                <input type="hidden" name="name" value="<?php echo htmlspecialchars($name); ?>">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <input type="hidden" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                <input type="hidden" name="date" value="<?php echo htmlspecialchars($date); ?>">
                <input type="hidden" name="time" value="<?php echo htmlspecialchars($time); ?>">
                <input type="hidden" name="guests" value="<?php echo htmlspecialchars($guests); ?>">
                
                <div class="action-buttons">
                    <button type="submit" class="button primary">
                        <i class="fas fa-calendar-check" style="margin-right: 8px;"></i> Confirm Reservation
                    </button>
                    <button type="button" class="button secondary" onclick="window.location.href='reservation.php';">
                        <i class="fas fa-edit" style="margin-right: 8px;"></i> Edit Details
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer__container">
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-tiktok"></i></a>
            </div>
            <p>123 Magalang Rd, Arayat, Pampanga, Philippines</p>
            <p>&copy; <?php echo date('Y'); ?> CABS KOREAN. All Rights Reserved.</p>
        </div>
    </footer>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Initialize animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate reservation container
            const container = document.querySelector('.reservation-container');
            container.style.animationDelay = '0.2s';
            container.classList.add('fade-in');
        });
    </script>
</body>
</html>