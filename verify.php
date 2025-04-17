<?php
include 'db_connect.php';

$message = '';
$message_type = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $sql = "SELECT id, fullname FROM users WHERE verification_token = ? AND is_verified = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        $sql = "UPDATE users SET is_verified = 1, verification_token = NULL WHERE verification_token = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $token);

        if ($stmt->execute()) {
            $message = "Email verified successfully, {$user['fullname']}! You can now login.";
            $message_type = 'success';
        } else {
            $message = "Error verifying email. Please contact support.";
            $message_type = 'error';
        }
    } else {
        $message = "Invalid or expired token.";
        $message_type = 'error';
    }

    $stmt->close();
} else {
    $message = "No token provided.";
    $message_type = 'error';
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - CABS KOREAN</title>
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
            --error: #f44336;
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
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .navbar {
            background: rgba(18, 18, 18, 0.9);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
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
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: 700;
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

        .main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .verification-container {
            width: 100%;
            max-width: 600px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            text-align: center;
        }

        .verification-container::before {
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

        .verification-container::after {
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

        .verification-container h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 2rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        .message {
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .success-message {
            background: rgba(76, 175, 80, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .error-message {
            background: rgba(244, 67, 54, 0.1);
            color: var(--error);
            border-left: 4px solid var(--error);
        }

        .verification-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
        }

        .success-icon {
            color: var(--success);
        }

        .error-icon {
            color: var(--error);
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 126, 179, 0.4);
        }

        .footer {
            background: rgba(18, 18, 18, 0.9);
            padding: 1.5rem;
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
            font-size: 0.9rem;
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
            .verification-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="floating-element"></div>
    <div class="floating-element"></div>

    <nav class="navbar">
        <div class="navbar__container">
            <a href="landing.php" id="navbar__logo">
                <img src="images/cabs.jpg" alt="CABS KOREAN Logo"> CABS
            </a>
        </div>
    </nav>

    <div class="main">
        <div class="verification-container">
            <h2>Email Verification</h2>
            
            <div class="verification-icon">
                <?php if ($message_type === 'success'): ?>
                    <i class="fas fa-check-circle success-icon"></i>
                <?php else: ?>
                    <i class="fas fa-exclamation-circle error-icon"></i>
                <?php endif; ?>
            </div>
            
            <div class="message <?php echo $message_type === 'success' ? 'success-message' : 'error-message'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            
            <?php if ($message_type === 'success'): ?>
                <a href="login.php" class="btn">Go to Login</a>
            <?php else: ?>
                <a href="register.php" class="btn">Back to Registration</a>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer">
        <div class="footer__container">
            <p>&copy; <?php echo date('Y'); ?> CABS KOREAN. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html>