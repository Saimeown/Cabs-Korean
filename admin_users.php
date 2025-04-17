<?php
session_start();
include 'audit_logger.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$message = '';
$users = [];
$compact_view = isset($_COOKIE['compact_view']) ? $_COOKIE['compact_view'] === 'true' : false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['toggle_view'])) {
        $compact_view = !$compact_view;
        setcookie('compact_view', $compact_view ? 'true' : 'false', time() + (86400 * 30), "/");
    }

    if (isset($_POST['toggle_submit'])) {
        $compact_view = isset($_POST['toggle_view']);
        setcookie('compact_view', $compact_view ? 'true' : 'false', time() + (86400 * 30), "/");
    }
    if (isset($_POST['update_user'])) {
        $user_id = $_POST['user_id'];
        $role = $_POST['role'];
        $status = $_POST['status'];

        $old_values = [];
        $stmt = $conn->prepare("SELECT role, status FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $old_values = $row;
        }
        $stmt->close();

        $stmt = $conn->prepare("UPDATE users SET role = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssi", $role, $status, $user_id);
        $stmt->execute();
        $stmt->close();

        log_audit_action('user_updated', 'users', $user_id, $old_values, [
            'role' => $role,
            'status' => $status
        ]);

        $message = "User updated successfully!";
    }

    if (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];

        $old_values = [];
        $stmt = $conn->prepare("SELECT role, status FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $old_values = $row;
        }
        $stmt->close();

        $stmt = $conn->prepare("UPDATE users SET status = 'deleted' WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        log_audit_action('user_deleted', 'users', $user_id, $old_values, ['status' => 'deleted']);

        $message = "User marked as deleted!";
    }
}

$role_filter = isset($_GET['role_filter']) ? $_GET['role_filter'] : 'all';

$query = "SELECT id, fullname, username, email, phone, role, status, avatar, created_at 
          FROM users 
          WHERE status != 'deleted' 
          " . ($role_filter !== 'all' ? "AND role = '$role_filter'" : "") . "
          ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - CABS KOREAN</title>
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
            display: flex;
            min-height: 100vh;
        }

     
        .admin-sidebar {
            width: 280px;
            background: rgba(25, 25, 25, 0.95);
            padding: 2rem 1.5rem;
            position: fixed;
            height: 100vh;
            border-right: 1px solid rgba(255, 126, 179, 0.2);
            backdrop-filter: blur(10px);
            overflow-y: auto;
            z-index: 100;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .admin-logo {
            display: flex;
            align-items: center;
            padding: 0.5rem 0;
            margin-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 126, 179, 0.2);
        }

        .admin-logo img {
            height: 45px;
            width: 45px;
            margin-right: 12px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgb(255, 126, 180);
        }

        .admin-logo h2 {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            margin: 0;
        }

        .admin-nav {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .admin-nav li {
            margin: 0;
        }

        .admin-nav a {
            display: flex;
            align-items: center;
            padding: 0.85rem 1.25rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .admin-nav a:hover {
            background: rgba(255, 126, 179, 0.15);
            color: var(--primary);
            transform: translateX(5px);
        }

        .admin-nav a.active {
            background: rgba(255, 126, 179, 0.2);
            color: var(--primary);
            box-shadow: 0 4px 12px rgba(255, 126, 179, 0.1);
        }

        .admin-nav a i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

     
        .admin-nav li:last-child a {
            margin-top: 1.5rem;
            background: rgba(255, 0, 0, 0.1);
            color: #ff6b6b;
            border: 1px solid rgba(255, 0, 0, 0.2);
        }

        .admin-nav li:last-child a:hover {
            background: rgba(255, 0, 0, 0.2);
            color: #ff5252;
        }

     
        .admin-main {
            flex: 1;
            margin-left: 280px;
            padding: 2.5rem;
            min-height: 100vh;
            background-color: var(--dark);
            transition: margin-left 0.3s ease;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 126, 179, 0.2);
        }

        .admin-title {
            font-size: 1.8rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        .admin-user {
            display: flex;
            align-items: center;
        }

     
        .view-toggle {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .toggle-label {
            margin-right: 1rem;
            font-size: 0.9rem;
            color: #aaa;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 126, 179, 0.2);
            transition: .4s;
            border-radius: 24px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: var(--primary);
        }

        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }

     
        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 6px;
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
            border-left: 4px solid #4CAF50;
        }

     
        .users-container {
            max-width: 1200px;
            margin: 0 auto;
            display: <?= $compact_view ? 'none' : 'block' ?>;
        }

        .user-card {
            background: rgba(40, 40, 40, 0.8);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            display: grid;
            grid-template-columns: 100px 1fr;
            gap: 1.5rem;
            border-left: 4px solid;
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
        }

        .user-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .info-group label {
            display: block;
            color: #aaa;
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }

        .info-group p {
            color: var(--light);
            font-weight: 500;
        }

        .user-form {
            grid-column: 1 / -1;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 126, 179, 0.1);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--light);
        }

        select {
            width: 100%;
            padding: 0.8rem;
            border-radius: 6px;
            border: 1px solid var(--gray);
            background: rgba(50, 50, 50, 0.8);
            color: var(--light);
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
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

        .btn-danger {
            background: rgba(244, 67, 54, 0.8);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 126, 179, 0.3);
        }

     
        .status-active { border-color: #4CAF50; }
        .status-suspended { border-color: #FFC107; }
        .status-deleted { border-color: #F44336; }

        .role-admin { background: rgba(255, 126, 179, 0.1); }
        .role-employee { background: rgba(33, 150, 243, 0.1); }

     
        .compact-users-container {
            display: <?= $compact_view ? 'block' : 'none' ?>;
            max-height: calc(100vh - 180px);
            overflow-y: auto;
            background: linear-gradient(135deg, rgba(30, 30, 30, 0.87) 0%, rgba(40, 40, 40, 0.7) 100%);
            border-radius: 8px;
            border: 1px solid rgba(255, 126, 179, 0.1);
        }

        .compact-users-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .compact-users-table th {
            position: sticky;
            top: 0;
            background: rgba(255, 126, 179, 0.2);
            color: var(--primary);
            padding: 0.8rem;
            text-align: left;
            font-weight: 600;
        }

        .compact-users-table td {
            padding: 0.8rem;
            border-bottom: 1px solid rgba(255, 126, 179, 0.1);
            vertical-align: middle;
        }

        .compact-users-table tr:last-child td {
            border-bottom: none;
        }

        .compact-users-table tr:hover {
            background: rgba(255, 126, 179, 0.05);
        }

        .compact-user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }

        .compact-status-badge {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            width: 100px;
            text-align: center;
        }

        .compact-status-active { 
            background: rgba(76, 175, 80, 0.1); 
            color: #4CAF50; 
            border: 1px solid #4CAF50; 
        }
        .compact-status-suspended { 
            background: rgba(255, 193, 7, 0.1); 
            color: #FFC107; 
            border: 1px solid #FFC107; 
        }

        .compact-role-badge {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            width: 100px;
            text-align: center;
        }

        .compact-role-admin { 
            background: rgba(213, 104, 196, 0.1); 
            color: rgb(250, 73, 185); 
            border: 1px solid rgb(219, 99, 163); 
        }
        .compact-role-employee { 
            background: rgba(33, 150, 243, 0.1); 
            color: #2196F3; 
            border: 1px solid #2196F3; 
        }
        .compact-role-user { 
            background: rgba(158, 158, 158, 0.1); 
            color: #9E9E9E; 
            border: 1px solid #9E9E9E; 
        }

        .compact-action-btn {
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            border: none;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .compact-btn-primary {
            background: rgba(255, 126, 179, 0.2);
            color: var(--primary);
        }

        .compact-btn-danger {
            background: rgba(244, 67, 54, 0.2);
            color: #F44336;
        }

        .compact-action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .compact-inline-form {
            display: inline-block;
            margin: 0;
        }

        .compact-select {
            padding: 0.4rem;
            border-radius: 4px;
            border: 1px solid var(--gray);
            background: rgba(50, 50, 50, 0.8);
            color: var(--light);
            font-size: 0.8rem;
        }

     
        .role-filter-form {
            position: relative;
        }

     
        @media (max-width: 992px) {
            .admin-sidebar {
                width: 240px;
                padding: 1.5rem 1rem;
            }
            
            .admin-main {
                margin-left: 240px;
                padding: 2rem;
            }
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 1rem;
            }
            
            .admin-nav {
                flex-direction: row;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .admin-nav li {
                flex: 1 0 calc(50% - 0.5rem);
            }
            
            .admin-nav a {
                padding: 0.75rem;
                justify-content: center;
            }
            
            .admin-nav a i {
                margin-right: 0;
                margin-bottom: 0.25rem;
                display: block;
            }
            
            .admin-nav a span {
                display: none;
            }
            
            .admin-main {
                margin-left: 0;
                padding: 1.5rem;
            }
            
            .user-card {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .compact-users-table th, 
            .compact-users-table td {
                padding: 0.6rem;
            }
            
            .view-toggle, .role-filter-form {
                width: 100%;
            }
            
            .view-toggle {
                margin-top: 1rem;
            }
            
            .role-filter-form {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="admin-sidebar">
        <div class="admin-logo">
            <img src="images/cabs.png" alt="CABS KOREAN Logo">
            <h2>CABS ADMIN</h2>
        </div>
        
        <ul class="admin-nav">
            <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="admin_menu.php"><i class="fas fa-utensils"></i> Menu</a></li>
            <li><a href="admin_reservations.php"><i class="fas fa-calendar-alt"></i> Reservations</a></li>
            <li><a href="admin_orders.php"><i class="fas fa-receipt"></i> Orders</a></li>
            <li><a href="admin_users.php" class="active"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="admin_audit_trail.php"><i class="fas fa-clipboard-list"></i> Logs</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="admin-main">
        <div class="admin-header">
            <h1 class="admin-title">User Management</h1>
            <div class="admin-user">
                <span><?= htmlspecialchars($_SESSION['fullname'] ?? 'Admin') ?></span>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div style="display: flex; align-items: center; gap: 1.5rem; margin-bottom: 1.5rem;">
            <form method="GET" class="role-filter-form" style="display: flex; align-items: center; gap: 0.8rem;">
                <label for="role_filter" style="font-size: 0.9rem; color: #aaa;">Role:</label>
                <div class="custom-select" style="position: relative;">
                    <select name="role_filter" id="role_filter" onchange="this.form.submit()" 
                            style="appearance: none; padding: 0.6rem 2.5rem 0.6rem 1rem; border-radius: 6px; 
                                   border: 1px solid rgba(255, 126, 179, 0.3); background: rgba(40, 40, 40, 0.8); 
                                   color: var(--light); cursor: pointer; font-size: 0.9rem; min-width: 150px;">
                        <option value="all" <?= $role_filter === 'all' ? 'selected' : '' ?>>All Users</option>
                        <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admins</option>
                        <option value="employee" <?= $role_filter === 'employee' ? 'selected' : '' ?>>Employees</option>
                        <option value="user" <?= $role_filter === 'user' ? 'selected' : '' ?>>Customers</option>
                    </select>
                    <div style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); 
                                pointer-events: none; color: var(--primary);">
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
            </form>

            <form method="POST" class="view-toggle" style="margin-bottom: 0;">
                <span class="toggle-label">Compact View:</span>
                <label class="toggle-switch">
                    <input type="checkbox" name="toggle_view" <?= $compact_view ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
            </form>
        </div>

                     <div class="users-container">
            <?php foreach ($users as $user): ?>
                <div class="user-card status-<?= $user['status'] ?> <?= $user['role'] === 'admin' ? 'role-admin' : ($user['role'] === 'employee' ? 'role-employee' : '') ?>">
                    <div>
                        <img src="<?= 
                            !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : 
                            ($user['role'] === 'admin' ? 'images/admin-avatar.png' : 
                            ($user['role'] === 'employee' ? 'images/employee-logo.png' : 'images/default-avatar.jpg')) 
                        ?>" alt="User Avatar" class="user-avatar">
                    </div>
                    
                    <div class="user-info">
                        <div class="info-group">
                            <label>Name</label>
                            <p><?= htmlspecialchars($user['fullname']) ?></p>
                        </div>
                        
                        <div class="info-group">
                            <label>Username</label>
                            <p><?= htmlspecialchars($user['username']) ?></p>
                        </div>
                        
                        <div class="info-group">
                            <label>Email</label>
                            <p><?= htmlspecialchars($user['email']) ?></p>
                        </div>
                        <br>
                        <div class="info-group">
                            <label>Phone</label>
                            <p><?= htmlspecialchars($user['phone']) ?></p>
                        </div>
                        
                        <div class="info-group">
                            <label>Registered</label>
                            <p><?= date('M j, Y', strtotime($user['created_at'])) ?></p>
                        </div>
                        
                        <form class="user-form" method="POST">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Role</label>
                                    <select name="role">
                                        <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                        <option value="employee" <?= $user['role'] === 'employee' ? 'selected' : '' ?>>Employee</option>
                                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status">
                                        <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="suspended" <?= $user['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="btn-group">
                                <button type="submit" name="update_user" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update User
                                </button>
                                
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <button type="submit" name="delete_user" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?')">
                                        <i class="fas fa-trash"></i> Delete User
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

                     <div class="compact-users-container">
            <table class="compact-users-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Contact</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.8rem;">
                                <img src="<?= 
                                    !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : 
                                    ($user['role'] === 'admin' ? 'images/admin-avatar.png' : 
                                    ($user['role'] === 'employee' ? 'images/employee-logo.png' : 'images/default-avatar.jpg')) 
                                ?>" alt="User Avatar" class="compact-user-avatar">
                                <div>
                                    <div style="font-weight: 500;"><?= htmlspecialchars($user['fullname']) ?></div>
                                    <div style="font-size: 0.8rem; color: #aaa;">@<?= htmlspecialchars($user['username']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="margin-bottom: 0.3rem;"><?= htmlspecialchars($user['email']) ?></div>
                            <div style="font-size: 0.8rem;"><?= htmlspecialchars($user['phone']) ?></div>
                        </td>
                        <td>
                            <span class="compact-role-badge compact-role-<?= $user['role'] ?>">
                                <?= ucfirst($user['role']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="compact-status-badge compact-status-<?= $user['status'] ?>">
                                <?= ucfirst($user['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?= date('M j, Y', strtotime($user['created_at'])) ?>
                        </td>
                        <td>
                            <form class="compact-inline-form" method="POST">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <div style="display: flex; gap: 0.5rem;">
                                    <select name="role" class="compact-select" style="width: 100px;">
                                        <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                        <option value="employee" <?= $user['role'] === 'employee' ? 'selected' : '' ?>>Employee</option>
                                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                    <select name="status" class="compact-select" style="width: 100px;">
                                        <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="suspended" <?= $user['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                                    </select>
                                </div>
                                <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                                    <button type="submit" name="update_user" class="compact-action-btn compact-btn-primary">
                                        <i class="fas fa-save"></i> Save
                                    </button>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button type="submit" name="delete_user" class="compact-action-btn compact-btn-danger" 
                                                onclick="return confirm('Are you sure you want to delete this user?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.querySelector('.toggle-switch input').addEventListener('change', function() {
            const hiddenField = document.createElement('input');
            hiddenField.type = 'hidden';
            hiddenField.name = 'toggle_submit';
            hiddenField.value = '1';
            this.form.appendChild(hiddenField);
            this.form.submit();
        });

        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const roleFilter = document.getElementById('role_filter').value;
                if (roleFilter && roleFilter !== 'all') {
                    const hiddenField = document.createElement('input');
                    hiddenField.type = 'hidden';
                    hiddenField.name = 'role_filter';
                    hiddenField.value = roleFilter;
                    this.appendChild(hiddenField);
                }
            });
        });
    </script>
</body>
</html>