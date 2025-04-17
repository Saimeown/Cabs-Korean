<?php
include 'db_connect.php';
include 'audit_logger.php'; 
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$message = "";

if (isset($_POST['delete_pfp'])) {
    $sql = "SELECT avatar FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($currentAvatar);
    $stmt->fetch();
    $stmt->close();
    
    $sql = "UPDATE users SET avatar = NULL WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
    
    log_audit_action('profile_picture_removed', 'users', $userId, ['avatar' => $currentAvatar], ['avatar' => null]);
    
    if (!empty($currentAvatar)) {
        @unlink($currentAvatar); 
    }
    
    header("Location: update_user.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['delete_pfp'])) {
    $sql = "SELECT username, email, avatar, fullname, phone, address, city, province, postal_code FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($currentUsername, $currentEmail, $currentAvatar, $currentFullname, $currentPhone, $currentAddress, $currentCity, $currentProvince, $currentPostalCode);
    $stmt->fetch();
    $stmt->close();
    
    $old_values = [
        'username' => $currentUsername,
        'email' => $currentEmail,
        'fullname' => $currentFullname,
        'phone' => $currentPhone,
        'address' => $currentAddress,
        'city' => $currentCity,
        'province' => $currentProvince,
        'postal_code' => $currentPostalCode,
        'avatar' => $currentAvatar
    ];
    
    $profilePic = $currentAvatar; 
    
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/profile_pics/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['profile_pic']['name']);
        $targetPath = $uploadDir . $fileName;
        
        $check = getimagesize($_FILES['profile_pic']['tmp_name']);
        if ($check !== false) {
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetPath)) {
                if (!empty($currentAvatar)) {
                    @unlink($currentAvatar);
                }
                $profilePic = $targetPath;
                
                log_audit_action('profile_picture_changed', 'users', $userId, ['avatar' => $currentAvatar], ['avatar' => $targetPath]);
            }
        }
    }
    
    $username = $_POST['username'];
    $email = $_POST['email'];
    $fullname = $_POST['fullname'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $province = $_POST['province'];
    $postalCode = $_POST['postal_code'];
    
    $sql = "UPDATE users SET username = ?, email = ?, fullname = ?, phone = ?, address = ?, city = ?, province = ?, postal_code = ?";
    $params = [$username, $email, $fullname, $phone, $address, $city, $province, $postalCode];
    $types = "ssssssss";
    
    if (!empty($profilePic)) {
        $sql .= ", avatar = ?";
        $params[] = $profilePic;
        $types .= "s";
    }
    
    $password_changed = false;
    if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
        $verifySql = "SELECT password FROM users WHERE id = ?";
        $verifyStmt = $conn->prepare($verifySql);
        $verifyStmt->bind_param("i", $userId);
        $verifyStmt->execute();
        $verifyStmt->bind_result($hashedPassword);
        $verifyStmt->fetch();
        $verifyStmt->close();
        
        if (password_verify($_POST['current_password'], $hashedPassword)) {
            $newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $sql .= ", password = ?";
            $params[] = $newPassword;
            $types .= "s";
            $password_changed = true;
        } else {
            $message = "Current password is incorrect";
        }
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $userId;
    $types .= "i";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();
    
    $new_values = [
        'username' => $username,
        'email' => $email,
        'fullname' => $fullname,
        'phone' => $phone,
        'address' => $address,
        'city' => $city,
        'province' => $province,
        'postal_code' => $postalCode,
        'avatar' => $profilePic
    ];
    
    log_audit_action('profile_updated', 'users', $userId, $old_values, $new_values);
    
    if ($password_changed) {
        log_audit_action('password_changed', 'users', $userId);
    }
    
    $message = "Profile updated successfully!";
}

$sql = "SELECT username, email, avatar, fullname, phone, address, city, province, postal_code FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($username, $email, $profilePic, $fullname, $phone, $address, $city, $province, $postalCode);
$stmt->fetch();
$stmt->close();

$reservationQuery = "SELECT reservation_date, reservation_time, status, guests FROM reservations WHERE user_id = ? ORDER BY reservation_date DESC LIMIT 1";
$reservationStmt = $conn->prepare($reservationQuery);
$reservationStmt->bind_param("i", $userId);
$reservationStmt->execute();
$reservationStmt->bind_result($reservationDate, $reservationTime, $status, $guests);
$reservationStmt->fetch();
$reservationStmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - CABS KOREAN</title>
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
            position: fixed;
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

             
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 2rem 2rem;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
        }

             
        .profile-sidebar {
            background: rgba(30, 30, 30, 0.8);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 126, 179, 0.2);
            height: fit-content;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 1.5rem;
            display: block;
            border: 3px solid var(--primary);
        }

        .profile-name {
            text-align: center;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--light);
        }

        .profile-email {
            text-align: center;
            color: #aaa;
            margin-bottom: 2rem;
        }

        .profile-nav {
            list-style: none;
        }

        .profile-nav li {
            margin-bottom: 0.8rem;
        }

        .profile-nav a {
            display: block;
            padding: 0.8rem;
            color: var(--light);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .profile-nav a:hover, .profile-nav a.active {
            background: rgba(255, 126, 179, 0.1);
            color: var(--primary);
        }

        .profile-nav a i {
            margin-right: 0.8rem;
            width: 20px;
            text-align: center;
        }

             
        .profile-content {
            background: rgba(30, 30, 30, 0.8);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 126, 179, 0.2);
        }

        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 126, 179, 0.2);
        }

        .profile-title {
            font-size: 1.8rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

             
        .profile-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--light);
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 0.8rem 1rem;
            border-radius: 6px;
            border: 1px solid var(--gray);
            background: rgba(40, 40, 40, 0.8);
            color: var(--light);
            font-size: 1rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(255, 126, 179, 0.3);
        }

        .avatar-upload {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .avatar-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 6px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 126, 179, 0.3);
        }

        .btn-danger {
            background: #ff0844;
            color: white;
        }

             
        .reservation-card {
            background: rgba(40, 40, 40, 0.8);
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
            border-left: 4px solid <?php echo ($status === 'confirmed') ? '#4CAF50' : '#FFC107'; ?>;
        }

        .reservation-title {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: var(--light);
        }

        .reservation-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .reservation-status {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            background: <?php echo ($status === 'confirmed') ? 'rgba(76, 175, 80, 0.2)' : 'rgba(255, 193, 7, 0.2)'; ?>;
            color: <?php echo ($status === 'confirmed') ? '#4CAF50' : '#FFC107'; ?>;
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

             
        @media (max-width: 992px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
            
            .profile-sidebar {
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .profile-nav {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .profile-form {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
            
            .reservation-details {
                grid-template-columns: 1fr;
            }
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

    .navbar__menu {
      display: flex;
      list-style: none;
      align-items: center;
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

    .navbar__links.active::after {
      content: '';
      position: absolute;
      bottom: -5px;
      left: 0;
      width: 100%;
      height: 2px;
      background: linear-gradient(to right, var(--primary), var(--secondary));
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
      background: rgba(255, 57, 133, 0.2);
      border: 1px solid rgba(255, 0, 111, 0.5);
      color:rgb(255, 107, 166);
    }

    .logout-btn:hover {
      background: rgba(255, 0, 140, 0.3);
    }

          
     .file-input {
            display: none;
        }

             
        .upload-label {
            display: inline-block;
            background: linear-gradient(45deg,rgb(189, 66, 66), #ff0844);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s;
        }

        .upload-label:hover {
            opacity: 0.8;
        }

             
        .preview-container {
            margin-top: 20px;
            position: relative;
            display: inline-block;
        }

             
        .preview-container img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #ff7eb3;
            transition: 0.3s ease-in-out;
        }

             
        .preview-container img:hover {
            transform: scale(1.1);
            box-shadow: 0 0 10px rgba(255, 126, 179, 0.5);
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
        background: rgba(255, 57, 133, 0.2);
        border: 1px solid rgba(255, 0, 111, 0.5);
        color:rgb(255, 107, 166);
        }

        .logout-btn:hover {
        background: rgba(255, 0, 140, 0.3);
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
             <nav class="navbar">
        <div class="navbar__container">
            <a href="index.php" id="navbar__logo">
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
                    <a href="reservation.php" class="navbar__links">Reservations</a>
                </li>
                <li class="navbar__item">
                    <a href="update_user.php" class="navbar__links active">Profile</a>
                </li>
                <li class="navbar__btn">
                    <a href="logout.php" class="button logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </nav>

             <div class="profile-container">
                     <div class="profile-sidebar">
            <img src="<?php echo !empty($profilePic) ? htmlspecialchars($profilePic) : 'images/default-avatar.jpg'; ?>" 
                 alt="Profile Picture" class="profile-avatar">
            <h2 class="profile-name"><?php echo htmlspecialchars($fullname ? $fullname : $username); ?></h2>
            <p class="profile-email"><?php echo htmlspecialchars(strlen($email) > 30 ? substr($email, 0, 20) . '...' : $email); ?></p>
            
            <ul class="profile-nav">
                <li><a href="#" class="active"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="user_reservations.php"><i class="fas fa-calendar-alt"></i> Reservations</a></li>
                <li><a href="user_order_history.php"><i class="fas fa-receipt"></i> Order History</a></li>
            </ul>
        </div>

                     <div class="profile-content">
            <div class="profile-header">
                <h1 class="profile-title">Profile Settings</h1>
            </div>

            <?php if (!empty($message)): ?>
                <div class="message" style="color: var(--primary); margin-bottom: 1.5rem; padding: 0.8rem; background: rgba(255, 126, 179, 0.1); border-radius: 6px;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form class="profile-form" action="update_user.php" method="POST" enctype="multipart/form-data">
                                     <div class="form-group full-width">
                    <label>Profile Picture</label>
                    <div class="avatar-upload">
                        <img src="<?php echo !empty($profilePic) ? htmlspecialchars($profilePic) : 'images/default-avatar.jpg'; ?>" 
                             alt="Current Avatar" class="avatar-preview" id="avatarPreview">
                        <div>
                        <label for="profile_pic" class="upload-label">Choose a Photo</label>
                            <input type="file" id="profile_pic" name="profile_pic" accept="image/*" 
                                   onchange="previewImage(this, 'avatarPreview')" hidden>
                            <?php if (!empty($profilePic)) : ?>
                                <button type="submit" name="delete_pfp" class="btn btn-danger" style="margin-top: 0.5rem;">
                                    <i class="fas fa-trash"></i> Remove Photo
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                                     <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                </div>

                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($fullname); ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>"
                           pattern="^(?:\+63|0)\d{10}$">
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($address); ?>" required>
                </div>

                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($city); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="province">Province</label>
                    <input type="text" id="province" name="province" value="<?php echo htmlspecialchars($province); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="postal_code">Postal Code</label>
                    <input type="text" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($postalCode); ?>" required>
                </div>

                                     <div class="form-group full-width">
                    <h3 style="margin-bottom: 1rem; color: var(--primary);">Change Password</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div>
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password">
                        </div>
                        <div>
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password">
                        </div>
                    </div>
                </div>

                                     <div class="form-group full-width" style="text-align: right;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>

        </div>
    </div>

             <footer class="footer">
        <div class="footer__container">
            <p>&copy; <?php echo date('Y'); ?> CABS KOREAN. All Rights Reserved.</p>
        </div>
    </footer>

    <script>
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const file = input.files[0];
            const reader = new FileReader();

            reader.onload = function(e) {
                preview.src = e.target.result;
            }

            if (file) {
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>