<?php
ob_start();
session_start();
        require 'phpmailer/src/Exception.php';
        require 'phpmailer/src/PHPMailer.php';
        require 'phpmailer/src/SMTP.php';

        use PHPMailer\PHPMailer\PHPMailer;
        use PHPMailer\PHPMailer\Exception;
include 'db_connect.php';

// Initialize variables
$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Invalid CSRF token. Please try again.";
        header("Location: register.php?error=" . urlencode($error_message));
        exit;
    }

    // Get form data
    $fullname = $_POST['fullname'] ?? '';
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm-password'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $province = $_POST['province'] ?? '';
    $postal_code = $_POST['postal_code'] ?? '';

    // Validate inputs
    if (empty($fullname) || empty($username) || empty($email) || empty($phone) || 
        empty($password) || empty($confirmPassword) || empty($address) || 
        empty($city) || empty($province) || empty($postal_code)) {
        $error_message = "All fields are required.";
        header("Location: register.php?error=" . urlencode($error_message));
        exit;
    }

    if ($password !== $confirmPassword) {
        $error_message = "Passwords do not match!";
        header("Location: register.php?error=" . urlencode($error_message));
        exit;
    }

    // Check password strength
    if (!preg_match('/^[A-Z].*[0-9].*[!@#$%^&*]/', $password) || strlen($password) < 8) {
        $error_message = "Password must start with uppercase, contain a number and special character, and be at least 8 characters long.";
        header("Location: register.php?error=" . urlencode($error_message));
        exit;
    }

    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $error_message = "Username or email already exists.";
        header("Location: register.php?error=" . urlencode($error_message));
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    // Generate verification token
    $verification_token = bin2hex(random_bytes(32));
    
    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (fullname, username, email, phone, password, address, city, province, postal_code, verification_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssss", $fullname, $username, $email, $phone, $hashedPassword, $address, $city, $province, $postal_code, $verification_token);
    
    if ($stmt->execute()) {
        // Send verification email
        
        
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; 
            $mail->SMTPAuth = true;
            $mail->Username = 'traquenaandrei@gmail.com'; 
            $mail->Password = 'gnvj xunc rqmn cspv'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            // Recipients
            $mail->setFrom('no-reply@cabs-korean.com', 'CABS KOREAN');
            $mail->addAddress($email, $fullname);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Verify your CABS KOREAN account';
            $mail->Body    = "Hi $fullname,<br><br>Please click the following link to verify your account:<br><br>
                            <a href='http://localhost/cabs-korean/verify.php?token=$verification_token'>Verify Account</a><br><br>
                            Thanks,<br>The CABS KOREAN Team";
            
            $mail->send();
            $success_message = "Registration successful! Please check your email to verify your account.";
            header("Location: register.php?success=" . urlencode($success_message));
        } catch (Exception $e) {
            $error_message = "Registration successful but verification email could not be sent. Please contact support.";
            header("Location: register.php?error=" . urlencode($error_message));
        }
    } else {
        $error_message = "Registration failed. Please try again.";
        header("Location: register.php?error=" . urlencode($error_message));
    }

    $stmt->close();
    $conn->close();
    exit;
}
?>