<?php
include 'db_connect.php';

session_start();

$error = "";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$max_attempts = 5;
$lockout_time = 300; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid form submission. Please try again.";
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= $max_attempts) {
            if (time() - $_SESSION['last_attempt_time'] < $lockout_time) {
                $remaining_time = ceil(($lockout_time - (time() - $_SESSION['last_attempt_time'])) / 60);
                $error = "Too many failed attempts. Please try again in $remaining_time minutes.";
            } else {
                unset($_SESSION['login_attempts']);
                unset($_SESSION['last_attempt_time']);
            }
        }

        if (empty($error)) {
            $sql = "SELECT id, username, password, role, email, is_verified, status FROM users WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($userId, $dbUsername, $hashedPassword, $role, $email, $isVerified, $isActive);
                $stmt->fetch();

                if (!$isActive) {
                    $error = "This account has been deactivated. Please contact support.";
                }
                elseif (password_verify($password, $hashedPassword)) {
                    if ($isVerified) {
                        unset($_SESSION['login_attempts']);
                        unset($_SESSION['last_attempt_time']);

                        session_regenerate_id(true);

                        $_SESSION['user_id'] = $userId;
                        $_SESSION['username'] = $dbUsername;
                        $_SESSION['role'] = $role;
                        $_SESSION['email'] = $email;
                        $_SESSION['last_activity'] = time();
                        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
                        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

                        if ($role === 'admin') {
                            header("Location: admin_dashboard.php");
                        } elseif ($role === 'employee'){
                            header("Location: employee_dashboard.php");
                        } else {
                            header("Location: index.php");
                        }
                        exit();
                    } else {
                        $error = "Your email is not verified. Please check your email for the verification link.";
                    }
                } else {
                    $_SESSION['login_attempts'] = isset($_SESSION['login_attempts']) ? $_SESSION['login_attempts'] + 1 : 1;
                    $_SESSION['last_attempt_time'] = time();
                    $error = "Invalid username or password!";
                }
            } else {
                $_SESSION['login_attempts'] = isset($_SESSION['login_attempts']) ? $_SESSION['login_attempts'] + 1 : 1;
                $_SESSION['last_attempt_time'] = time();
                $error = "Invalid username or password!";
            }
            $stmt->close();
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CABS KOREAN - Login</title>
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
            --hanbok-red: #e84c3d;
            --hanbok-blue: #3498db;
            --hanbok-yellow: #f1c40f;
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
            overflow-x: hidden;
        }

             
        .korean-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(255, 126, 179, 0.03) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(255, 8, 68, 0.03) 0%, transparent 20%),
                linear-gradient(45deg, transparent 48%, rgba(255, 126, 179, 0.03) 48%, rgba(255, 126, 179, 0.03) 52%, transparent 52%),
                linear-gradient(-45deg, transparent 48%, rgba(255, 8, 68, 0.03) 48%, rgba(255, 8, 68, 0.03) 52%, transparent 52%);
            background-size: 100px 100px;
            z-index: -1;
            opacity: 0.3;
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

        .login-container {
            width: 100%;
            max-width: 450px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border: 0.5px solid var(--primary);
        }

        .login-container::before {
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

        .login-container::after {
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

        .login-container h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 2rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        .error-message {
            color: #ff6b6b;
            background: rgba(255, 0, 0, 0.1);
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            text-align: center;
            border-left: 4px solid #ff6b6b;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--light);
            font-weight: 400;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            color: var(--light);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(255, 126, 179, 0.3);
        }

        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--light);
            opacity: 0.7;
            transition: opacity 0.3s;
        }

        .toggle-password:hover {
            opacity: 1;
        }

        button[type="submit"] {
            width: 50%;
            padding: 0.75rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
            display: block;
            margin: 0.5rem auto 0;
            margin-top: 3em;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 126, 179, 0.4);
        }

        .redirect-message {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--light);
            opacity: 0.8;
        }

        .redirect-message a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .redirect-message a:hover {
            color: var(--secondary);
            text-decoration: underline;
        }

        .footer {
            background: rgba(18, 18, 18, 0.9);
            text-align: center;
            border-top: 1px solid rgba(255, 126, 179, 0.2);
            position: relative;
            min-height: 107px;      
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .footer__container {
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        .footer p {
            color: var(--light);
            opacity: 0.7;
            font-size: 0.9rem;
            margin: 0.5rem 0;      
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

        .login-options {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
        }

        .remember-me input {
            margin-right: 0.5rem;
        }

        .forgot-password a {
            color: var(--primary);
            text-decoration: none;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .login-container {
                padding: 1.5rem;
            }

            .navbar__container {
                padding: 0 1rem;
            }
        }

        .floating-element {
            position: absolute;
            opacity: 0.1;
            z-index: 0;
            animation: float 8s ease-in-out infinite;
            pointer-events: none; 
        }

        .floating-element:nth-child(1) {
            width: 300px;
            height: 300px;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            top: 10%;
            left: 5%;
        }

        .floating-element:nth-child(2) {
            width: 200px;
            height: 200px;
            background: linear-gradient(45deg, var(--secondary), var(--primary));
            border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
            bottom: 15%;
            right: 10%;
            animation-delay: -4s;      
        }

        .floating-element:nth-child(3) {
            width: 150px;
            height: 150px;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            border-radius: 40% 60% 60% 40% / 40% 40% 60% 60%;
            top: 30%;
            right: 15%;
            animation-delay: 1s;
            opacity: 0.08;
        }

        .floating-element:nth-child(4) {
            width: 250px;
            height: 250px;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            border-radius: 50% 50% 30% 70% / 50% 30% 70% 50%;
            bottom: 25%;
            left: 15%;
            animation-delay: 3s;
            opacity: 0.12;
        }

        .floating-element:nth-child(5) {
            width: 180px;
            height: 180px;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            border-radius: 70% 30% 50% 50% / 30% 70% 30% 70%;
            top: 65%;
            left: 25%;
            animation-delay: 2s;
            opacity: 0.09;
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

        .logout-btn {
            background: rgba(255, 0, 0, 0.2);
            border: 1px solid rgba(255, 0, 0, 0.5);
            color: #ff6b6b;
        }

        .logout-btn:hover {
            background: rgba(255, 0, 0, 0.3);
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

             
        .korean-symbol {
            position: absolute;
            font-size: 3rem;
            opacity: 0.05;
            z-index: -1;
        }

        .symbol-top-right {
            top: -1rem;
            right: -1rem;
        }

        .symbol-bottom-left {
            bottom: -1rem;
            left: -1rem;
        }
             
        .login-container {
            width: 100%;
            max-width: 450px;
            background: linear-gradient(145deg, rgba(219, 19, 62, 0.39) 0%, rgba(147, 0, 0, 0.9) 100%);
            border-radius: 20px;
            padding: 3rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 126, 180, 0.85);
            transform-style: preserve-3d;
            perspective: 1000px;
            z-index: 10;
        }

        .login-container::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at center, 
                        rgba(255, 126, 179, 0.15) 0%, 
                        transparent 70%);
            animation: rotate-gradient 20s linear infinite;
            z-index: -1;
        }

        .login-container::after {
            content: "";
            position: absolute;
            inset: 2px;
            border-radius: 18px;
            background: linear-gradient(145deg, rgba(20, 20, 20, 0.95) 0%, rgba(25, 25, 25, 0.98) 100%);
            z-index: -1;
        }

        @keyframes rotate-gradient {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .login-container h2 {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2.2rem;
            font-weight: 700;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            letter-spacing: 1px;
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .login-container h2::after {
            content: "";
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border-radius: 3px;
        }

        .error-message {
            color: #ff6b6b;
            background: rgba(255, 0, 0, 0.1);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            border-left: 4px solid #ff6b6b;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 1.75rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            color: var(--light);
            font-weight: 500;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
        }

        .form-group input {
            width: 100%;
            padding: 1rem 1.25rem;
            background: rgba(15, 15, 15, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: var(--light);
            font-size: 1rem;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 126, 179, 0.2);
            background: rgba(15, 15, 15, 0.9);
            transform: translateY(-2px);
        }

        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--light);
            opacity: 0.7;
            transition: all 0.3s;
            background: rgba(0, 0, 0, 0.3);
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .toggle-password:hover {
            opacity: 1;
            background: rgba(255, 126, 179, 0.2);
            color: var(--primary);
        }

        button[type="submit"] {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            margin-top: 1.5rem;
            display: block;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            box-shadow: 0 4px 15px rgba(255, 126, 179, 0.3);
            position: relative;
            overflow: hidden;
        }

        button[type="submit"]::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                        transparent, 
                        rgba(255, 255, 255, 0.2), 
                        transparent);
            transition: all 0.6s ease;
        }

        button[type="submit"]:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 126, 179, 0.4);
        }

        button[type="submit"]:hover::before {
            left: 100%;
        }

        .redirect-message {
            text-align: center;
            margin-top: 2rem;
            color: var(--light);
            opacity: 0.8;
            font-size: 0.95rem;
        }

        .redirect-message a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            position: relative;
            display: inline-block;
        }

        .redirect-message a::after {
            content: "";
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--secondary);
            transition: width 0.3s ease;
        }

        .redirect-message a:hover {
            color: var(--secondary);
        }

        .redirect-message a:hover::after {
            width: 100%;
        }

        .login-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .remember-me input {
            appearance: none;
            width: 18px;
            height: 18px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 4px;
            background: rgba(15, 15, 15, 0.7);
            position: relative;
            cursor: pointer;
            transition: all 0.2s;
        }

        .remember-me input:checked {
            background: var(--primary);
            border-color: var(--primary);
        }

        .remember-me input:checked::after {
            content: "✓";
            position: absolute;
            color: white;
            font-size: 0.8rem;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .forgot-password a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-block;
        }

        .forgot-password a:hover {
            color: var(--secondary);
            transform: translateX(3px);
        }

             
.login-particles {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    pointer-events: none;
    z-index: -1;
    overflow: hidden;
}

.login-particle {
    position: absolute;
    background: linear-gradient(to right, var(--primary), var(--secondary));
    border-radius: 50%;
    opacity: 0.15;      
    filter: blur(20px);      
    animation: float-particle 15s infinite linear;
    z-index: -1;
}

             
        .login-particle:nth-child(1) {
            width: 120px;
            height: 120px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .login-particle:nth-child(2) {
            width: 180px;
            height: 180px;
            bottom: 15%;
            right: 10%;
            animation-delay: 2s;
        }

        .login-particle:nth-child(3) {
            width: 90px;
            height: 90px;
            top: 40%;
            right: 20%;
            animation-delay: 4s;
        }

        @keyframes float-particle {
            0% { 
                transform: translateY(0) rotate(0deg) scale(1);
                opacity: 0.1;
            }
            50% { 
                transform: translateY(-50px) rotate(180deg) scale(1.2);
                opacity: 0.2;
            }
            100% { 
                transform: translateY(0) rotate(360deg) scale(1);
                opacity: 0.1;
            }
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
    <div class="korean-pattern"></div>
    
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    
             <div class="korean-symbol symbol-top-right">한</div>
    <div class="korean-symbol symbol-bottom-left">식</div>
    
    <nav class="navbar">
        <div class="navbar__container">
            <a href="landing.php" id="navbar__logo">
                <img src="images/cabs.png" alt="CABS KOREAN Logo"> CABS
            </a>
        </div>
    </nav>

    <div class="main">
        <div class="login-container">
            <form id="login-form" action="login.php" method="POST">
                <h2>Login</h2>
                <?php if (!empty($error)) : ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div> 
                <?php endif; ?>
                
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autocomplete="username" autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" required autocomplete="current-password">
                        <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                    </div>
                </div>
                
                <div class="login-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <div class="forgot-password">
                        <a href="forgot_password.php">Forgot password?</a>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn">Login</button>
            </form>
            <p class="redirect-message">Don't have an account? <a href="register.php">Register here</a></p>
            <div class="login-particles">
                <div class="login-particle" style="width: 100px; height: 100px; top: 20%; left: 10%; animation-delay: 0s;"></div>
                <div class="login-particle" style="width: 150px; height: 150px; bottom: 15%; right: 10%; animation-delay: 2s;"></div>
                <div class="login-particle" style="width: 80px; height: 80px; top: 40%; right: 20%; animation-delay: 4s;"></div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer__container">
            <p>&copy; <?php echo date('Y'); ?> CABS KOREAN. All Rights Reserved.</p>
        </div>
    </footer>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });

        document.getElementById('login-form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
            }
        });

        document.getElementById('username').focus();
    </script>
</body>
</html>