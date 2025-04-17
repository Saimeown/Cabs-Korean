<?php
include 'audit_logger.php';
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_table'])) {
        $table_number = $_POST['table_number'];
        $capacity = $_POST['capacity'];
        $description = $_POST['description'];
        
        $stmt = $conn->prepare("INSERT INTO restaurant_tables (table_number, capacity, description) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $table_number, $capacity, $description);
        
        if ($stmt->execute()) {
            $table_id = $stmt->insert_id;
            log_audit_action('table_added', 'restaurant_tables', $table_id, [], [
                'table_number' => $table_number,
                'capacity' => $capacity,
                'description' => $description
            ]);
            $_SESSION['success'] = "Table added successfully!";
        } else {
            $_SESSION['error'] = "Error adding table: " . $conn->error;
        }
        $stmt->close();
    }
    
    elseif (isset($_POST['update_table'])) {
        $table_id = $_POST['table_id'];
        $table_number = $_POST['table_number'];
        $capacity = $_POST['capacity'];
        $description = $_POST['description'];
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        
        $stmt = $conn->prepare("SELECT * FROM restaurant_tables WHERE id = ?");
        $stmt->bind_param("i", $table_id);
        $stmt->execute();
        $old_table = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $stmt = $conn->prepare("UPDATE restaurant_tables SET table_number = ?, capacity = ?, description = ?, is_available = ? WHERE id = ?");
        $stmt->bind_param("sisi", $table_number, $capacity, $description, $is_available, $table_id);
        
        if ($stmt->execute()) {
            log_audit_action('table_updated', 'restaurant_tables', $table_id, [
                'old_table_number' => $old_table['table_number'],
                'old_capacity' => $old_table['capacity'],
                'old_description' => $old_table['description'],
                'old_is_available' => $old_table['is_available']
            ], [
                'new_table_number' => $table_number,
                'new_capacity' => $capacity,
                'new_description' => $description,
                'new_is_available' => $is_available
            ]);
            $_SESSION['success'] = "Table updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating table: " . $conn->error;
        }
        $stmt->close();
    }
    
    elseif (isset($_POST['delete_table'])) {
        $table_id = $_POST['table_id'];
        
        $stmt = $conn->prepare("SELECT * FROM restaurant_tables WHERE id = ?");
        $stmt->bind_param("i", $table_id);
        $stmt->execute();
        $old_table = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $stmt = $conn->prepare("SELECT COUNT(*) as reservation_count FROM reservations WHERE table_number = ?");
        $stmt->bind_param("s", $old_table['table_number']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($result['reservation_count'] > 0) {
            $_SESSION['error'] = "Cannot delete table - it has existing reservations!";
        } else {
            $stmt = $conn->prepare("DELETE FROM restaurant_tables WHERE id = ?");
            $stmt->bind_param("i", $table_id);
            
            if ($stmt->execute()) {
                log_audit_action('table_deleted', 'restaurant_tables', $table_id, [
                    'table_number' => $old_table['table_number'],
                    'capacity' => $old_table['capacity'],
                    'description' => $old_table['description']
                ], []);
                $_SESSION['success'] = "Table deleted successfully!";
            } else {
                $_SESSION['error'] = "Error deleting table: " . $conn->error;
            }
            $stmt->close();
        }
    }
    
    header("Location: admin_tables.php");
    exit();
}

$tables = [];
$stmt = $conn->prepare("SELECT * FROM restaurant_tables ORDER BY capacity DESC, table_number");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $tables[] = $row;
}
$stmt->close();

$today = date('Y-m-d');
$reservations_today = [];
$stmt = $conn->prepare("
    SELECT r.table_number, r.reservation_time, r.status, r.guests, u.fullname 
    FROM reservations r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.reservation_date = ? AND r.table_number IS NOT NULL
    ORDER BY r.reservation_time
");
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $reservations_today[$row['table_number']][] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table Management - CABS KOREAN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;700&display=swap" rel="stylesheet">
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
            width: 250px;
            background: rgba(30, 30, 30, 0.9);
            padding: 1.5rem;
            position: fixed;
            height: 100vh;
            border-right: 1px solid rgba(255, 126, 179, 0.2);
            backdrop-filter: blur(10px);
        }

        .admin-logo {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 126, 179, 0.2);
        }

        .admin-logo img {
            height: 40px;
            margin-right: 10px;
            border-radius: 50%;
        }

        .admin-logo h2 {
            font-size: 1.5rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        .admin-nav {
            list-style: none;
        }

        .admin-nav li {
            margin-bottom: 0.5rem;
        }

        .admin-nav a {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            color: var(--light);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .admin-nav a:hover, .admin-nav a.active {
            background: rgba(255, 126, 179, 0.1);
            color: var(--primary);
        }

        .admin-nav a i {
            margin-right: 0.8rem;
            width: 20px;
            text-align: center;
        }

                     .admin-main {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
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

                     .tabs {
            display: flex;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 126, 179, 0.2);
        }

        .tab {
            padding: 0.8rem 1.5rem;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab.active {
            border-bottom-color: var(--primary);
            color: var(--primary);
        }

        .tab:hover:not(.active) {
            background: rgba(255, 126, 179, 0.1);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

                     .tables-container {
            background: rgba(40, 40, 40, 0.8);
            border-radius: 8px;
            overflow: hidden;
        }

        .tables-list {
            width: 100%;
            border-collapse: collapse;
        }

        .tables-list th {
            background: rgba(255, 126, 179, 0.2);
            color: var(--primary);
            padding: 0.8rem 1rem;
            text-align: left;
            font-weight: 600;
        }

        .tables-list td {
            padding: 0.8rem 1rem;
            border-bottom: 1px solid rgba(255, 126, 179, 0.1);
            vertical-align: middle;
        }

        .tables-list tr:last-child td {
            border-bottom: none;
        }

        .tables-list tr:hover {
            background: rgba(255, 126, 179, 0.05);
        }

                     .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-available { background: rgba(76, 175, 80, 0.1); color: #4CAF50; border: 1px solid #4CAF50; }
        .status-unavailable { background: rgba(244, 67, 54, 0.1); color: #F44336; border: 1px solid #F44336; }

                     .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--light);
        }

        .form-control {
            width: 100%;
            padding: 0.8rem;
            border-radius: 6px;
            border: 1px solid var(--gray);
            background: rgba(50, 50, 50, 0.8);
            color: var(--light);
        }

        .form-inline {
            display: flex;
            gap: 1rem;
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
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 126, 179, 0.3);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .btn-danger {
            background: rgba(244, 67, 54, 0.8);
            color: white;
        }

        .btn-danger:hover {
            background: rgba(244, 67, 54, 1);
        }

                     .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--dark);
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            border: 1px solid rgba(255, 126, 179, 0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 126, 179, 0.2);
        }

        .modal-title {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .close-modal {
            background: none;
            border: none;
            color: var(--light);
            font-size: 1.5rem;
            cursor: pointer;
        }

                     .floor-plan {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .floor-plan-section {
            background: rgba(40, 40, 40, 0.8);
            border-radius: 8px;
            padding: 1.5rem;
        }

        .floor-plan-title {
            font-size: 1.2rem;
            color: var(--primary);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(255, 126, 179, 0.2);
        }

        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 1rem;
        }

        .table-item {
            position: relative;
            background: rgba(50, 50, 50, 0.8);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            border: 2px solid;
            transition: all 0.3s;
            cursor: pointer;
        }

        .table-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(255, 126, 179, 0.2);
        }

        .table-item.available {
            border-color: #4CAF50;
        }

        .table-item.reserved {
            border-color: #FFC107;
        }

        .table-item.booked {
            border-color: #F44336;
        }

        .table-number {
            font-weight: bold;
            font-size: 1.2rem;
            margin-bottom: 0.3rem;
        }

        .table-capacity {
            font-size: 0.8rem;
            color: #aaa;
        }

        .table-status {
            position: absolute;
            top: 0.3rem;
            right: 0.3rem;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
        }

        .table-reservations {
            margin-top: 0.5rem;
            font-size: 0.8rem;
        }

        .reservation-item {
            padding: 0.3rem;
            margin-bottom: 0.3rem;
            border-radius: 4px;
            background: rgba(0, 0, 0, 0.2);
        }

        .reservation-time {
            font-weight: bold;
        }

        .reservation-name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

                     @media (max-width: 1200px) {
            .admin-main {
                margin-left: 0;
                padding: 1rem;
            }
            
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 1000;
            }
            
            .admin-sidebar.active {
                transform: translateX(0);
            }
            
            .tables-list {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }

        @media (max-width: 768px) {
            .tabs {
                flex-direction: column;
            }
            
            .tab {
                border-bottom: none;
                border-left: 3px solid transparent;
            }
            
            .tab.active {
                border-bottom: none;
                border-left-color: var(--primary);
            }
            
            .form-inline {
                flex-direction: column;
                gap: 0.5rem;
            }
        }

                     .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            border-color: #4CAF50;
            color: #4CAF50;
        }

        .alert-error {
            background: rgba(244, 67, 54, 0.2);
            border-color: #F44336;
            color: #F44336;
        }
    </style>
</head>
<body>
<div class="admin-sidebar">
        <div class="admin-logo">
            <img src="images/cabs.jpg" alt="CABS KOREAN Logo">
            <h2>CABS ADMIN</h2>
        </div>
        
        <ul class="admin-nav">
            <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="admin_menu.php"><i class="fas fa-utensils"></i> Menu</a></li>
            <li><a href="admin_reservations.php"><i class="fas fa-calendar-alt"></i> Reservations</a></li>
            <li><a href="admin_orders.php"><i class="fas fa-receipt"></i> Orders</a></li>
            <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="admin_audit_trail.php"><i class="fas fa-clipboard-list"></i> Logs</a></li>
            <li><a href="admin_tables.php" class="active"><i class="fas fa-chair"></i> Tables</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="admin-main">
        <div class="admin-header">
            <h1 class="admin-title">Table Management</h1>
            <div class="admin-user">
                <span><?= htmlspecialchars($_SESSION['fullname'] ?? 'Admin') ?></span>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error'] ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="tabs">
            <div class="tab active" data-tab="manage">Manage Tables</div>
            <div class="tab" data-tab="floor-plan">Floor Plan</div>
            <div class="tab" data-tab="status">Table Status</div>
        </div>

        <div class="tab-content active" id="manage">
            <div class="tables-container">
                <table class="tables-list">
                    <thead>
                        <tr>
                            <th>Table Number</th>
                            <th>Capacity</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tables as $table): ?>
                        <tr>
                            <td><?= htmlspecialchars($table['table_number']) ?></td>
                            <td><?= $table['capacity'] ?></td>
                            <td><?= htmlspecialchars($table['description']) ?></td>
                            <td>
                                <span class="status-badge status-<?= $table['is_available'] ? 'available' : 'unavailable' ?>">
                                    <?= $table['is_available'] ? 'Available' : 'Unavailable' ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-primary btn-sm edit-table" 
                                        data-id="<?= $table['id'] ?>"
                                        data-number="<?= $table['table_number'] ?>"
                                        data-capacity="<?= $table['capacity'] ?>"
                                        data-description="<?= htmlspecialchars($table['description']) ?>"
                                        data-available="<?= $table['is_available'] ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-danger btn-sm delete-table" 
                                        data-id="<?= $table['id'] ?>"
                                        data-number="<?= $table['table_number'] ?>">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <button class="btn btn-primary" id="add-table-btn" style="margin-top: 1.5rem;">
                <i class="fas fa-plus"></i> Add New Table
            </button>
        </div>

                     <div class="tab-content" id="floor-plan">
            <h2>Today's Floor Plan (<?= date('F j, Y') ?>)</h2>
            
            <div class="floor-plan">
                                     <div class="floor-plan-section">
                    <h3 class="floor-plan-title">Large Tables (10 seats)</h3>
                    <div class="tables-grid">
                        <?php foreach ($tables as $table): ?>
                            <?php if ($table['capacity'] == 10): ?>
                                <?php 
                                    $status = 'available';
                                    $reservations = $reservations_today[$table['table_number']] ?? [];
                                    if (!empty($reservations)) {
                                        $status = count($reservations) > 1 ? 'booked' : 'reserved';
                                    }
                                ?>
                                <div class="table-item <?= $status ?>" data-table="<?= $table['table_number'] ?>">
                                    <div class="table-number">Table <?= $table['table_number'] ?></div>
                                    <div class="table-capacity"><?= $table['capacity'] ?> seats</div>
                                    <div class="table-status"><?= ucfirst($status) ?></div>
                                    
                                    <?php if (!empty($reservations)): ?>
                                        <div class="table-reservations">
                                            <?php foreach ($reservations as $reservation): ?>
                                                <div class="reservation-item">
                                                    <div class="reservation-time"><?= date('g:i A', strtotime($reservation['reservation_time'])) ?></div>
                                                    <div class="reservation-name" title="<?= htmlspecialchars($reservation['fullname']) ?>">
                                                        <?= htmlspecialchars($reservation['fullname']) ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                                     <div class="floor-plan-section">
                    <h3 class="floor-plan-title">Medium Tables (6 seats)</h3>
                    <div class="tables-grid">
                        <?php foreach ($tables as $table): ?>
                            <?php if ($table['capacity'] == 6): ?>
                                <?php 
                                    $status = 'available';
                                    $reservations = $reservations_today[$table['table_number']] ?? [];
                                    if (!empty($reservations)) {
                                        $status = count($reservations) > 1 ? 'booked' : 'reserved';
                                    }
                                ?>
                                <div class="table-item <?= $status ?>" data-table="<?= $table['table_number'] ?>">
                                    <div class="table-number">Table <?= $table['table_number'] ?></div>
                                    <div class="table-capacity"><?= $table['capacity'] ?> seats</div>
                                    <div class="table-status"><?= ucfirst($status) ?></div>
                                    
                                    <?php if (!empty($reservations)): ?>
                                        <div class="table-reservations">
                                            <?php foreach ($reservations as $reservation): ?>
                                                <div class="reservation-item">
                                                    <div class="reservation-time"><?= date('g:i A', strtotime($reservation['reservation_time'])) ?></div>
                                                    <div class="reservation-name" title="<?= htmlspecialchars($reservation['fullname']) ?>">
                                                        <?= htmlspecialchars($reservation['fullname']) ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                                     <div class="floor-plan-section">
                    <h3 class="floor-plan-title">Small Tables (2 seats)</h3>
                    <div class="tables-grid">
                        <?php foreach ($tables as $table): ?>
                            <?php if ($table['capacity'] == 2): ?>
                                <?php 
                                    $status = 'available';
                                    $reservations = $reservations_today[$table['table_number']] ?? [];
                                    if (!empty($reservations)) {
                                        $status = count($reservations) > 1 ? 'booked' : 'reserved';
                                    }
                                ?>
                                <div class="table-item <?= $status ?>" data-table="<?= $table['table_number'] ?>">
                                    <div class="table-number">Table <?= $table['table_number'] ?></div>
                                    <div class="table-capacity"><?= $table['capacity'] ?> seats</div>
                                    <div class="table-status"><?= ucfirst($status) ?></div>
                                    
                                    <?php if (!empty($reservations)): ?>
                                        <div class="table-reservations">
                                            <?php foreach ($reservations as $reservation): ?>
                                                <div class="reservation-item">
                                                    <div class="reservation-time"><?= date('g:i A', strtotime($reservation['reservation_time'])) ?></div>
                                                    <div class="reservation-name" title="<?= htmlspecialchars($reservation['fullname']) ?>">
                                                        <?= htmlspecialchars($reservation['fullname']) ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

                     <div class="tab-content" id="status">
            <h2>Table Status Overview</h2>
            
            <div class="floor-plan">
                <?php 
                $time_slots = [
                    '11:00:00' => '11:00 AM',
                    '12:00:00' => '12:00 PM',
                    '13:00:00' => '1:00 PM',
                    '14:00:00' => '2:00 PM',
                    '17:00:00' => '5:00 PM',
                    '18:00:00' => '6:00 PM',
                    '19:00:00' => '7:00 PM',
                    '20:00:00' => '8:00 PM',
                    '21:00:00' => '9:00 PM'
                ];
                ?>
                
                <?php foreach ($time_slots as $time => $label): ?>
                    <div class="floor-plan-section">
                        <h3 class="floor-plan-title"><?= $label ?></h3>
                        <div class="tables-grid">
                            <?php foreach ($tables as $table): ?>
                                <?php
                                // Reconnect to database since we closed earlier
                                $conn = new mysqli('localhost', 'username', 'password', 'database');
                                $stmt = $conn->prepare("
                                    SELECT r.*, u.fullname 
                                    FROM reservations r
                                    LEFT JOIN users u ON r.user_id = u.id
                                    WHERE r.reservation_date = ? 
                                    AND r.table_number = ?
                                    AND r.reservation_time <= ?
                                    AND DATE_ADD(r.reservation_time, INTERVAL 1 HOUR) > ?
                                    AND r.status = 'confirmed'
                                ");
                                $stmt->bind_param("ssss", $today, $table['table_number'], $time, $time);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $reservation = $result->fetch_assoc();
                                $stmt->close();
                                $conn->close();
                                ?>
                                
                                <div class="table-item <?= $reservation ? 'booked' : ($table['is_available'] ? 'available' : 'unavailable') ?>">
                                    <div class="table-number">Table <?= $table['table_number'] ?></div>
                                    <div class="table-capacity"><?= $table['capacity'] ?> seats</div>
                                    <div class="table-status">
                                        <?= $reservation ? 'Booked' : ($table['is_available'] ? 'Available' : 'Unavailable') ?>
                                    </div>
                                    
                                    <?php if ($reservation): ?>
                                        <div class="table-reservations">
                                            <div class="reservation-item">
                                                <div class="reservation-name">
                                                    <?= htmlspecialchars($reservation['fullname']) ?>
                                                </div>
                                                <div class="reservation-time">
                                                    <?= $reservation['guests'] ?> guests
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

             <div class="modal" id="add-table-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New Table</h2>
                <button class="close-modal">&times;</button>
            </div>
            <form method="POST" id="add-table-form">
                <div class="form-group">
                    <label for="table_number">Table Number</label>
                    <input type="text" class="form-control" id="table_number" name="table_number" required>
                </div>
                <div class="form-group">
                    <label for="capacity">Capacity</label>
                    <select class="form-control" id="capacity" name="capacity" required>
                        <option value="2">2 seats</option>
                        <option value="6">6 seats</option>
                        <option value="10">10 seats</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <input type="text" class="form-control" id="description" name="description">
                </div>
                <div class="form-inline">
                    <button type="submit" name="add_table" class="btn btn-primary">Add Table</button>
                    <button type="button" class="btn close-modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

             <div class="modal" id="edit-table-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Table</h2>
                <button class="close-modal">&times;</button>
            </div>
            <form method="POST" id="edit-table-form">
                <input type="hidden" id="edit_table_id" name="table_id">
                <div class="form-group">
                    <label for="edit_table_number">Table Number</label>
                    <input type="text" class="form-control" id="edit_table_number" name="table_number" required>
                </div>
                <div class="form-group">
                    <label for="edit_capacity">Capacity</label>
                    <select class="form-control" id="edit_capacity" name="capacity" required>
                        <option value="2">2 seats</option>
                        <option value="6">6 seats</option>
                        <option value="10">10 seats</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <input type="text" class="form-control" id="edit_description" name="description">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="edit_is_available" name="is_available" value="1" <?= isset($table['is_available']) && $table['is_available'] ? 'checked' : '' ?>> Available
                    </label>
                </div>
                <div class="form-inline">
                    <button type="submit" name="update_table" class="btn btn-primary">Update Table</button>
                    <button type="button" class="btn close-modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

             <div class="modal" id="delete-table-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Delete Table</h2>
                <button class="close-modal">&times;</button>
            </div>
            <form method="POST" id="delete-table-form">
                <input type="hidden" id="delete_table_id" name="table_id">
                <p>Are you sure you want to delete table <span id="delete_table_number"></span>?</p>
                <div class="form-inline">
                    <button type="submit" name="delete_table" class="btn btn-danger">Delete</button>
                    <button type="button" class="btn close-modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM fully loaded and parsed');

    // Tab functionality
    const tabs = document.querySelectorAll('.tab');
    console.log('Tabs found:', tabs.length);

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            console.log('Tab clicked:', tabId);

            // Remove active class from all tabs and content
            document.querySelectorAll('.tab, .tab-content').forEach(element => {
                element.classList.remove('active');
            });

            // Add active class to clicked tab and corresponding content
            this.classList.add('active');
            const content = document.getElementById(tabId);
            if (content) {
                content.classList.add('active');
                console.log('Content shown:', tabId);
            } else {
                console.error('Content not found for tab:', tabId);
            }
        });
    });

    // Modal handling functions
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            console.log('Modal opened:', modalId);
        } else {
            console.error('Modal not found:', modalId);
        }
    }

    function closeModal() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = 'none';
        });
        console.log('All modals closed');
    }

    // Add Table Button
    document.getElementById('add-table-btn')?.addEventListener('click', () => openModal('add-table-modal'));

    // Edit Table Buttons
    document.querySelectorAll('.edit-table').forEach(button => {
        button.addEventListener('click', function() {
            const data = {
                id: this.dataset.id,
                number: this.dataset.number,
                capacity: this.dataset.capacity,
                description: this.dataset.description,
                available: this.dataset.available === '1'
            };
            document.getElementById('edit_table_id').value = data.id;
            document.getElementById('edit_table_number').value = data.number;
            document.getElementById('edit_capacity').value = data.capacity;
            document.getElementById('edit_description').value = data.description;
            document.getElementById('edit_is_available').checked = data.available;
            openModal('edit-table-modal');
        });
    });

    // Delete Table Buttons
    document.querySelectorAll('.delete-table').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('delete_table_id').value = this.dataset.id;
            document.getElementById('delete_table_number').textContent = this.dataset.number;
            openModal('delete-table-modal');
        });
    });

    // Close Modal Buttons
    document.querySelectorAll('.close-modal').forEach(button => {
        button.addEventListener('click', closeModal);
    });

    // Close modal on outside click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    });
});
</script>
</body>
</html>