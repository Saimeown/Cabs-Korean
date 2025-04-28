<?php
ob_start();
session_start();
// Handle success/error messages
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}
include 'db_connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CABS KOREAN - Register</title>
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
            --error: #f44336;
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
            display: flex;
            flex-direction: column;
            min-height: 100vh;
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

             
        .main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
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

             
        .register-container {
            width: 100%;
            max-width: 900px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 3rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 126, 179, 0.2);
            z-index: 1;
            transition: all 0.3s;
            margin-top: 2em;
            margin-bottom: 5em;
        }

        .register-container:hover {
            box-shadow: 0 25px 60px rgba(255, 126, 179, 0.3);
            border-color: rgba(255, 126, 179, 0.4);
        }

        .register-container::before {
            content: "";
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            z-index: -1;
            border-radius: 18px;
            opacity: 0.3;
        }

        .register-container::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(18, 18, 18, 0.95);
            border-radius: 15px;
            z-index: -1;
        }

        .register-container h2 {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2.5rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            font-family: 'Playfair Display', serif;
            position: relative;
        }

        .register-container h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 800px;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border-radius: 3px;
        }

             
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
            font-weight: 500;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(5px);
        }

        .message i {
            margin-right: 0.5rem;
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

             
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .form-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            color: var(--light);
            font-weight: 400;
            font-size: 1rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.85rem 1.25rem;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            color: var(--light);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 126, 179, 0.2);
            background: rgba(255, 255, 255, 0.12);
        }

        .input-helper {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 0.5rem;
            display: block;
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
        }

        .toggle-password:hover {
            opacity: 1;
            color: var(--primary);
        }

             
        .password-strength {
            margin-top: 0.5rem;
            margin-bottom: 0.7rem;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
        }

        .password-strength::before {
            content: "Strength: ";
            margin-right: 0.5rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .weak {
            color: var(--error);
        }

        .moderate {
            color: var(--gold);
        }

        .strong {
            color: var(--success);
        }

             
        .password-requirements {
            margin: 0.5rem 0 1.5rem;
            padding-left: 1.5rem;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .password-requirements li {
            margin-bottom: 0.4rem;
            list-style-type: none;
            position: relative;
            padding-left: 1.5rem;
        }

        .password-requirements li::before {
            content: "•";
            color: var(--primary);
            position: absolute;
            left: 0;
        }

             
        .user-agreement {
            display: flex;
            align-items: center;
            margin: 2rem 0;
        }

        .user-agreement input {
            margin-right: 0.75rem;
            width: auto;
            accent-color: var(--primary);
        }

        .user-agreement label {
            margin-bottom: 0;
            color: rgba(255, 255, 255, 0.8);
        }

        .plain-link {
            color: var(--primary);
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 500;
        }

        .plain-link:hover {
            color: var(--secondary);
            text-decoration: underline;
        }

             
        button[type="submit"] {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        button[type="submit"]::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, var(--secondary), var(--primary));
            opacity: 0;
            transition: opacity 0.4s ease;
            z-index: -1;
        }

        button[type="submit"]:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 126, 179, 0.5);
        }

        button[type="submit"]:hover::before {
            opacity: 1;
        }

        button[type="submit"]:active {
            transform: translateY(-1px);
        }

             
        .redirect-message {
            text-align: center;
            margin-top: 2rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: 1rem;
        }

        .redirect-message a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            position: relative;
        }

        .redirect-message a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.3s;
        }

        .redirect-message a:hover {
            color: var(--secondary);
        }

        .redirect-message a:hover::after {
            width: 100%;
            background: var(--secondary);
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
            .register-container {
                padding: 2.5rem;
            }
            
            .form-row {
                gap: 1.2rem;
            }
        }

        @media (max-width: 768px) {
            .register-container {
                padding: 2rem;
            }
            
            .register-container h2 {
                font-size: 2.2rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .main {
                padding: 1.5rem;
            }
        }

        @media (max-width: 576px) {
            .register-container {
                padding: 1.5rem;
            }
            
            .register-container h2 {
                font-size: 2rem;
                margin-bottom: 1.5rem;
            }
            
            .form-group input {
                padding: 0.75rem 1rem;
            }
            
            button[type="submit"] {
                padding: 0.9rem;
            }
        }

             
        .fade-in {
            animation: fadeIn 0.8s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

             
        .form-group input:focus {
            animation: inputFocus 0.5s ease-out;
        }

        @keyframes inputFocus {
            0% { box-shadow: 0 0 0 0 rgba(255, 126, 179, 0); }
            50% { box-shadow: 0 0 0 8px rgba(255, 126, 179, 0.2); }
            100% { box-shadow: 0 0 0 3px rgba(255, 126, 179, 0.2); }
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

    .footer::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      height: 1px;      
      width: 100%;
      background: linear-gradient(to right, var(--primary), var(--secondary));
    }
    </style>
</head>

<body>
             <div class="floating-element"></div>
    <div class="floating-element"></div>

             <nav class="navbar" id="navbar">
        <div class="navbar__container">
            <a href="landing.php" id="navbar__logo">
                <img src="images/cabs.png" alt="CABS KOREAN Logo"> CABS
            </a>
        </div>
    </nav>

             <div class="main">
        <div class="register-container fade-in">
            <h2>Join Cabs Korean.</h2>
            <p style="text-align: center; color: rgba(255,255,255,0.7); margin-bottom: 2rem;">Create your account to start your Korean culinary journey</p>

            <?php if (!empty($success_message)): ?>
                <div class="message success-message fade-in">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="message error-message fade-in">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form id="register-form" action="submit_register.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="fullname">Full Name</label>
                        <input type="text" id="fullname" name="fullname" placeholder="e.g., Juan Dela Cruz" required autocomplete="name" autofocus>
                        <small class="input-helper">First and last name</small>
                    </div>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" placeholder="e.g., juandc" required autocomplete="username">
                        <small class="input-helper">Your unique identifier</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="e.g., juandc@example.com" required autocomplete="email">
                        <small class="input-helper">We'll send a verification link</small>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="09123456789" required autocomplete="tel">
                        <small class="input-helper">10-15 digits only</small>
                    </div>
                </div>

                <div class="form-row full-width">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-container">
                            <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="new-password">
                            <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                        </div>
                        <div id="password-strength" class="password-strength"></div>
                        <ul class="password-requirements">
                            <li>At least 8 characters long</li>
                            <li>Starts with an uppercase letter</li>
                            <li>Contains at least one number</li>
                            <li>Contains at least one special character (!@#$%^&*)</li>
                        </ul>
                    </div>

                    <div class="form-group">
                        <label for="confirm-password">Confirm Password</label>
                        <div class="password-container">
                            <input type="password" id="confirm-password" name="confirm-password" placeholder="••••••••" required autocomplete="new-password">
                            <i class="fas fa-eye toggle-password" id="toggleConfirmPassword"></i>
                        </div>
                    </div>
                </div>

                <div class="form-row full-width">
                    <div class="form-group">
                        <label for="address">Full Address</label>
                        <input type="text" id="address" name="address" placeholder="Street, Barangay" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" placeholder="e.g., Arayat" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="province">Province</label>
                        <input type="text" id="province" name="province" placeholder="e.g., Pampanga" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="postal_code">Postal Code</label>
                        <input type="text" id="postal_code" name="postal_code" placeholder="e.g., 2012" required>
                    </div>
                </div>

                <div class="user-agreement full-width">
                    <input type="checkbox" id="agree-terms" name="agree-terms" required>
                    <label for="agree-terms">
                        I agree to the <a href="terms-and-conditions.php" target="_blank" class="plain-link">Terms and Conditions</a>.
                    </label>
                </div>

                <button type="submit">
                    <i class="fas fa-user-plus" style="margin-right: 8px;"></i> Create Account
                </button>
            </form>

            <p class="redirect-message">Already have an account? <a href="login.php">Sign in here</a></p>
        </div>
    </div>

             <footer class="footer">
        <div class="footer__container">
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-tiktok"></i></a>
            </div>
            <p>123 Korean Street, Food District, Manila, Philippines</p>
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

        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm-password');
        
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
        
        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPassword.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });

        // Password strength indicator
        password.addEventListener('input', function() {
            const strength = getPasswordStrength(this.value);
            const strengthElement = document.getElementById('password-strength');
            
            strengthElement.textContent = strength.level;
            strengthElement.className = `password-strength ${strength.class}`;
            
            // Update requirements list
            updatePasswordRequirements(this.value);
        });

        function getPasswordStrength(password) {
            // Check length
            if (password.length === 0) return { level: '', class: '' };
            if (password.length < 6) return { level: 'Weak', class: 'weak' };
            
            // Check complexity
            const hasUpper = /[A-Z]/.test(password) && /^[A-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[!@#$%^&*]/.test(password);
            
            let score = 0;
            if (hasUpper) score++;
            if (hasNumber) score++;
            if (hasSpecial) score++;
            
            if (password.length >= 8 && score === 3) {
                return { level: 'Strong', class: 'strong' };
            } else if (password.length >= 6 && score >= 2) {
                return { level: 'Moderate', class: 'moderate' };
            } else {
                return { level: 'Weak', class: 'weak' };
            }
        }

        function updatePasswordRequirements(password) {
            const requirements = document.querySelectorAll('.password-requirements li');
            
            // Check each requirement
            const checks = [
                password.length >= 8, // At least 8 characters
                /^[A-Z]/.test(password), // Starts with uppercase
                /[0-9]/.test(password), // Contains number
                /[!@#$%^&*]/.test(password) // Contains special char
            ];
            
            requirements.forEach((req, index) => {
                if (checks[index]) {
                    req.style.color = 'var(--success)';
                    req.style.textDecoration = 'line-through';
                    req.style.opacity = '0.7';
                } else {
                    req.style.color = 'rgba(255, 255, 255, 0.6)';
                    req.style.textDecoration = 'none';
                    req.style.opacity = '1';
                }
            });
        }

        // Form validation
        document.getElementById('register-form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            const agreeTerms = document.getElementById('agree-terms').checked;
            
            // Check password match
            if (password !== confirmPassword) {
                e.preventDefault();
                document.getElementById('confirm-password').focus();
                alert('Passwords do not match!');
                return;
            }
            
            // Check password strength
            const strength = getPasswordStrength(password);
            if (strength.class === 'weak') {
                e.preventDefault();
                document.getElementById('password').focus();
                alert('Please choose a stronger password that meets all requirements.');
                return;
            }
            
            // Check terms agreement
            if (!agreeTerms) {
                e.preventDefault();
                alert('You must agree to the terms and conditions to register.');
                return;
            }
        });

        // Initialize animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate form elements sequentially
            const formGroups = document.querySelectorAll('.form-group');
            formGroups.forEach((group, index) => {
                group.style.animationDelay = `${index * 0.1}s`;
                group.classList.add('fade-in');
            });
        });
    </script>
</body>
</html>