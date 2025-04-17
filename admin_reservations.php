<?php
include 'audit_logger.php';
session_start();
include 'db_connect.php';
include 'email_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_reservation'])) {
    $reservation_id = $_POST['reservation_id'];
    $status = $_POST['status'];
    $table_number = $_POST['table_number'] ?? null;

if ($status == 'confirmed') {
    if (!isset($_POST['table_number']) || empty($_POST['table_number'])) {
        $_SESSION['error'] = "You must select a table to confirm a reservation.";
        header("Location: admin_reservations.php");
        exit();
    }
    
    $table_number = $_POST['table_number'];
    $stmt = $conn->prepare("SELECT id FROM restaurant_tables WHERE table_number = ?");
    $stmt->bind_param("s", $table_number);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows === 0) {
        $_SESSION['error'] = "The selected table does not exist.";
        header("Location: admin_reservations.php");
        exit();
    }
    $stmt->close();
}

$get_reservation_stmt = $conn->prepare("SELECT * FROM reservations WHERE id = ?");
$get_reservation_stmt->bind_param("i", $reservation_id);
$get_reservation_stmt->execute();
$reservation = $get_reservation_stmt->get_result()->fetch_assoc();
$get_reservation_stmt->close();


    if ($status == 'confirmed' && !empty($table_number)) {
        $check_table_stmt = $conn->prepare("
            SELECT id FROM reservations 
            WHERE table_number = ? 
            AND reservation_date = ? 
            AND reservation_time = ? 
            AND status = 'confirmed' 
            AND id != ?
        ");
        $check_table_stmt->bind_param("sssi", 
            $table_number,
            $reservation['reservation_date'],
            $reservation['reservation_time'],
            $reservation_id
        );
        $check_table_stmt->execute();
        $existing_reservation = $check_table_stmt->get_result()->fetch_assoc();
        $check_table_stmt->close();
        
        if ($existing_reservation) {
            $_SESSION['error'] = "Table $table_number is already booked for this time slot.";
            header("Location: admin_reservations.php");
            exit();
        }
    }

    $payment_status = $reservation['payment_status'];
    if ($status == 'confirmed' && $reservation['status'] != 'confirmed') {
        $payment_status = 'waiting_payment';
    }

    $stmt = $conn->prepare("UPDATE reservations SET status = ?, payment_status = ?, table_number = ? WHERE id = ?");
    $stmt->bind_param("sssi", $status, $payment_status, $table_number, $reservation_id);
    
    if ($stmt->execute()) {
        log_audit_action('reservation_status_changed', 'reservations', $reservation_id, [
            'old_status' => $reservation['status'],
            'old_payment_status' => $reservation['payment_status']
        ], [
            'new_status' => $status,
            'new_payment_status' => $payment_status,
            'table_number' => $table_number
        ]);

        if ($status == 'confirmed' && $reservation['status'] != 'confirmed') {
            $payment_link = "http://yourdomain.com/reservation_checkout.php?reservation_id=$reservation_id";
            $qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=".urlencode($payment_link);
            
            $subject = "Reservation Confirmed - Payment Required";
            $body = "Dear {$reservation['name']},<br><br>
                    Your reservation has been confirmed!<br><br>
                    <strong>Payment Status:</strong> Waiting Payment<br><br>
                    Details:<br>
                    Date: {$reservation['reservation_date']}<br>
                    Time: {$reservation['reservation_time']}<br>
                    Guests: {$reservation['guests']}<br>
                    Table: $table_number<br><br>
                    Please complete your payment to secure your reservation:<br>
                    <a href='$payment_link'>Click here to pay</a><br><br>
                    Or scan this QR code:<br>
                    <img src='$qr_code_url' alt='Payment QR Code'><br><br>
                    Payment must be completed within 24 hours or your reservation will be cancelled.<br><br>
                    Thank you!";
            
            sendConfirmationEmail($reservation['email'], $reservation['name'], $subject, $body);
        }
    }
    $stmt->close();
}

$pending_reservations = [];
$waiting_payment_reservations = [];
$paid_reservations = [];
$completed_reservations = [];
$cancelled_reservations = [];

$query = "SELECT r.*, u.fullname, u.phone as user_phone, u.email as user_email 
          FROM reservations r
          LEFT JOIN users u ON r.user_id = u.id
          ORDER BY r.reservation_date DESC, r.reservation_time DESC";
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    if ($row['status'] == 'pending') {
        $pending_reservations[] = $row;
    } elseif ($row['status'] == 'confirmed' && $row['payment_status'] == 'waiting_payment') {
        $waiting_payment_reservations[] = $row;
    } elseif ($row['payment_status'] == 'paid' && $row['status'] == 'confirmed') {
        $paid_reservations[] = $row;
    } elseif ($row['status'] == 'completed') {
        $completed_reservations[] = $row;
    } elseif ($row['status'] == 'cancelled') {
        $cancelled_reservations[] = $row;
    }
}

$tables_by_capacity = [
    '10' => [],
    '6' => [],
    '2' => []
];

$stmt = $conn->prepare("SELECT * FROM restaurant_tables ORDER BY capacity DESC, table_number");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $tables_by_capacity[$row['capacity']][] = $row;
}
$stmt->close();

foreach ($pending_reservations as &$reservation) {
    $stmt = $conn->prepare("
        SELECT table_number FROM reservations 
        WHERE reservation_date = ? 
        AND reservation_time = ? 
        AND status = 'confirmed'
        AND id != ?
    ");
    $stmt->bind_param("ssi", 
        $reservation['reservation_date'],
        $reservation['reservation_time'],
        $reservation['id']
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $reservation['reserved_tables'] = [];
    while ($row = $result->fetch_assoc()) {
        $reservation['reserved_tables'][] = $row['table_number'];
    }
    $stmt->close();
}
unset($reservation);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations - CABS KOREAN</title>
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

                     .reservations-table-container {
            max-height: 65vh;
            overflow-y: auto;
            margin-bottom: 2rem;
            border-radius: 8px;
            background: linear-gradient(135deg, rgba(30, 30, 30, 0.86) 0%, rgba(40, 40, 40, 0.7) 100%);

        }

                     .reservations-table-container::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .reservations-table-container::-webkit-scrollbar-track {
            background: rgba(40, 40, 40, 0.8);
        }

        .reservations-table-container::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 3px;
        }

        .reservations-table-container::-webkit-scrollbar-thumb:hover {
            background: var(--secondary);
        }

                     .reservations-table {
            width: 100%;
            border-collapse: collapse;
        }

        .reservations-table th {
            background: rgba(255, 126, 179, 0.2);
            color: var(--primary);
            padding: 0.8rem 1rem;
            text-align: left;
            font-weight: 600;
            position: sticky;
            top: 0;
        }

        .reservations-table td {
            padding: 0.8rem 1rem;
            border-bottom: 1px solid rgba(255, 126, 179, 0.1);
            vertical-align: top;
        }

        .reservations-table tr:last-child td {
            border-bottom: none;
        }

        .reservations-table tr:hover {
            background: rgba(255, 126, 179, 0.05);
        }

                     .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            width: 100px;
            text-align: center;
        }

        .status-pending { background: rgba(255, 193, 7, 0.1); color: #FFC107; border: 1px solid #FFC107; }
        .status-confirmed { background: rgba(76, 175, 80, 0.1); color: #4CAF50; border: 1px solid #4CAF50; }
        .status-paid { background: rgba(33, 150, 243, 0.1); color: #2196F3; border: 1px solid #2196F3; }
        .status-cancelled { background: rgba(244, 67, 54, 0.1); color: #F44336; border: 1px solid #F44336; }
        .status-waiting_payment { background: rgba(255, 165, 0, 0.1); color: #FFA500; border: 1px solid #FFA500; }
        .status-completed { background: rgba(156, 39, 176, 0.1); color: #9C27B0; border: 1px solid #9C27B0; }

                     .compact-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .compact-form select, 
        .compact-form input {
            padding: 0.5rem;
            border-radius: 4px;
            border: 1px solid var(--gray);
            background: rgba(50, 50, 50, 0.8);
            color: var(--light);
            font-size: 0.9rem;
        }

        .compact-form select {
            min-width: 120px;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
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
            padding: 0.3rem 0.7rem;
            font-size: 0.8rem;
        }

                     .customer-info {
            font-size: 0.9rem;
        }

        .customer-name {
            font-weight: 600;
            margin-bottom: 0.2rem;
        }

        .customer-contact {
            color: #aaa;
            font-size: 0.8rem;
        }

                     .reservation-datetime {
            font-size: 0.9rem;
        }

                     .empty-state {
            text-align: center;
            padding: 2rem;
            color: #aaa;
            background: rgba(40, 40, 40, 0.5);
            border-radius: 8px;
        }

                     .table-options {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .table-option-group {
            border: 1px solid rgba(255, 126, 179, 0.2);
            border-radius: 6px;
            padding: 0.5rem;
        }
        
        .table-option-group h4 {
            margin-bottom: 0.5rem;
            color: var(--primary);
        }
        
        .table-options-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .table-option {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            background: rgba(50, 50, 50, 0.8);
            border: 1px solid var(--gray);
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .table-option:hover {
            border-color: var(--primary);
        }
        
        .table-option.selected {
            background: rgba(255, 126, 179, 0.2);
            border-color: var(--primary);
            color: var(--primary);
        }
        
        .table-option.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            text-decoration: line-through;
            border-color: #F44336 !important;
        }
        
        .reserved-badge {
            font-size: 0.7rem;
            color: #F44336;
            margin-left: 0.3rem;
        }

                     .error-message {
            background: rgba(244, 67, 54, 0.2);
            color: #F44336;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            border-left: 4px solid #F44336;
        }

                     .table-error {
            background: rgba(244, 67, 54, 0.2);
            color: #F44336;
            padding: 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-top: 0.5rem;
            display: none;
        }

                     .tab-nav {
            display: flex;
            border-bottom: 1px solid rgba(255, 126, 179, 0.2);
            margin-bottom: 1.5rem;
        }

        .tab-btn {
            position: relative;
            padding: 0.8rem 1.5rem;
            background: none;
            border: none;
            color: #aaa;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .tab-btn::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--primary);
            transform: scaleX(0);
            transition: transform 0.3s;
        }

        .tab-btn.active {
            color: var(--primary);
        }

        .tab-btn.active::after {
            transform: scaleX(1);
        }

        .tab-btn:hover:not(.active) {
            color: var(--light);
        }

        .tab-count {
            background: rgba(255, 126, 179, 0.2);
            color: var(--primary);
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.8rem;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
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
        }

        @media (max-width: 768px) {
            .compact-form {
                flex-wrap: wrap;
            }
            
            .reservations-table td, 
            .reservations-table th {
                padding: 0.5rem;
            }

            .tab-btn {
                padding: 0.8rem;
                font-size: 0.85rem;
            }
        }
        
        .tab-btn.completed-tab i {
            color: #9C27B0;
        }
        
        .tab-btn.cancelled-tab i {
            color: #F44336;
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
    border: 2px solid rgb(255, 126, 180);}

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

     .admin-sidebar::-webkit-scrollbar {
    width: 6px;
}

.admin-sidebar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
}

.admin-sidebar::-webkit-scrollbar-thumb {
    background: rgba(255, 126, 179, 0.3);
    border-radius: 3px;
}

.admin-sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 126, 179, 0.5);
}

     @media (max-width: 992px) {
    .admin-sidebar {
        width: 240px;
        padding: 1.5rem 1rem;
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
    }
}
     .admin-main {
    flex: 1;
    margin-left: 280px;          padding: 2.5rem;          min-height: 100vh;
    background-color: var(--dark);
    transition: margin-left 0.3s ease;
}

     @media (max-width: 992px) {
    .admin-sidebar {
        width: 240px;
        padding: 1.5rem 1rem;
    }
    
    .admin-main {
        margin-left: 240px;              padding: 2rem;
    }
}

@media (max-width: 768px) {
    .admin-sidebar {
        width: 100%;
        height: auto;
        position: relative;
        padding: 1rem;
    }
    
    .admin-main {
        margin-left: 0;
        padding: 1.5rem;
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
        <li><a href="admin_reservations.php" class="active"><i class="fas fa-calendar-alt"></i> Reservations</a></li>
        <li><a href="admin_orders.php"><i class="fas fa-receipt"></i> Orders</a></li>
        <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
        <li><a href="admin_audit_trail.php"><i class="fas fa-clipboard-list"></i> Logs</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<div class="admin-main">
    <div class="admin-header">
        <h1 class="admin-title">Reservations Management</h1>
        <div class="admin-user">
            <span><?= htmlspecialchars($_SESSION['fullname'] ?? 'Admin') ?></span>
        </div>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error'] ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="reservations-tabs">
        <div class="tab-nav">
            <button class="tab-btn active" onclick="switchTab('pending')">
                <i class="fas fa-clock"></i> Pending
                <span class="tab-count"><?= count($pending_reservations) ?></span>
            </button>
            <button class="tab-btn" onclick="switchTab('waiting')">
                <i class="fas fa-hourglass-half"></i> Waiting Payment
                <span class="tab-count"><?= count($waiting_payment_reservations) ?></span>
            </button>
            <button class="tab-btn" onclick="switchTab('paid')">
                <i class="fas fa-check-circle"></i> Paid
                <span class="tab-count"><?= count($paid_reservations) ?></span>
            </button>
            <button class="tab-btn" onclick="switchTab('completed')">
                <i class="fas fa-calendar-check"></i> Completed
                <span class="tab-count"><?= count($completed_reservations) ?></span>
            </button>
            <button class="tab-btn" onclick="switchTab('cancelled')">
                <i class="fas fa-times-circle"></i> Cancelled
                <span class="tab-count"><?= count($cancelled_reservations) ?></span>
            </button>
        </div>
        
                     <div class="tab-content active" id="pending-tab">
            <?php if (!empty($pending_reservations)): ?>
                <div class="reservations-table-container">
                    <table class="reservations-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Date & Time</th>
                                <th>Guests</th>
                                <th>Table</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_reservations as $reservation): ?>
                            <tr>
                                <td>
                                    <div class="customer-info">
                                        <div class="customer-name"><?= htmlspecialchars($reservation['name']) ?></div>
                                        <div class="customer-contact">
                                            <?= htmlspecialchars($reservation['user_email']) ?><br>
                                            <?= htmlspecialchars($reservation['user_phone']) ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="reservation-datetime">
                                    <?= date('M j, Y', strtotime($reservation['reservation_date'])) ?><br>
                                    <?= date('g:i A', strtotime($reservation['reservation_time'])) ?>
                                </td>
                                <td><?= $reservation['guests'] ?></td>
                                <td>
                                    <div class="table-options" id="tables-<?= $reservation['id'] ?>">
                                        <?php foreach ($tables_by_capacity as $capacity => $tables): ?>
                                            <?php if ($reservation['guests'] <= $capacity): ?>
                                                <div class="table-option-group">
                                                    <h4><?= $capacity ?>-seat Tables</h4>
                                                    <div class="table-options-list">
                                                        <?php foreach ($tables as $table): 
                                                            $is_reserved = in_array($table['table_number'], $reservation['reserved_tables']);
                                                            $is_selected = $reservation['table_number'] == $table['table_number'];
                                                        ?>
                                                            <label class="table-option 
                                                                <?= $is_selected ? 'selected' : '' ?>
                                                                <?= $is_reserved ? 'disabled' : '' ?>">
                                                                <input type="radio" 
                                                                    name="table_number" 
                                                                    form="form-<?= $reservation['id'] ?>" 
                                                                    value="<?= $table['table_number'] ?>"
                                                                    <?= $is_selected ? 'checked' : '' ?>
                                                                    <?= $is_reserved ? 'disabled' : '' ?>
                                                                    required>
                                                                Table <?= $table['table_number'] ?>
                                                                <?php if ($is_reserved): ?>
                                                                    <span class="reserved-badge">(Reserved)</span>
                                                                <?php endif; ?>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                    <div id="table-error-<?= $reservation['id'] ?>" class="table-error">
                                        Please select a table
                                    </div>
                                </td>
                                <td><span class="status-badge status-pending">Pending</span></td>
                                <td>
                                    <form id="form-<?= $reservation['id'] ?>" class="compact-form" method="POST">
                                        <input type="hidden" name="reservation_id" value="<?= $reservation['id'] ?>">
                                        <select name="status" required>
                                            <option value="pending" <?= $reservation['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="confirmed" <?= $reservation['status'] == 'confirmed' ? 'selected' : '' ?>>Confirm</option>
                                            <option value="cancelled" <?= $reservation['status'] == 'cancelled' ? 'selected' : '' ?>>Decline</option>
                                        </select>
                                        <button type="submit" name="update_reservation" class="btn btn-primary btn-sm">
                                            <i class="fas fa-save"></i> Update
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-check fa-2x" style="margin-bottom: 1rem;"></i>
                    <p>No pending reservations found.</p>
                </div>
            <?php endif; ?>
        </div>
        
                     <div class="tab-content" id="waiting-tab">
            <?php if (!empty($waiting_payment_reservations)): ?>
                <div class="reservations-table-container">
                    <table class="reservations-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Date & Time</th>
                                <th>Guests</th>
                                <th>Table</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($waiting_payment_reservations as $reservation): ?>
                            <tr>
                                <td>
                                    <div class="customer-info">
                                        <div class="customer-name"><?= htmlspecialchars($reservation['name']) ?></div>
                                        <div class="customer-contact">
                                            <?= htmlspecialchars($reservation['user_email']) ?><br>
                                            <?= htmlspecialchars($reservation['user_phone']) ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="reservation-datetime">
                                    <?= date('M j, Y', strtotime($reservation['reservation_date'])) ?><br>
                                    <?= date('g:i A', strtotime($reservation['reservation_time'])) ?>
                                </td>
                                <td><?= $reservation['guests'] ?></td>
                                <td>Table <?= $reservation['table_number'] ?></td>
                                <td><span class="status-badge status-waiting_payment">Waiting Payment</span></td>
                                <td>
                                    <form id="form-<?= $reservation['id'] ?>" class="compact-form" method="POST">
                                        <input type="hidden" name="reservation_id" value="<?= $reservation['id'] ?>">
                                        <input type="hidden" name="table_number" value="<?= $reservation['table_number'] ?>">
                                        <select name="status" required>
                                            <option value="confirmed" <?= $reservation['status'] == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                            <option value="completed" <?= $reservation['status'] == 'completed' ? 'selected' : '' ?>>Complete</option>
                                            <option value="cancelled" <?= $reservation['status'] == 'cancelled' ? 'selected' : '' ?>>Cancel</option>
                                        </select>
                                        <button type="submit" name="update_reservation" class="btn btn-primary btn-sm">
                                            <i class="fas fa-save"></i> Update
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle fa-2x" style="margin-bottom: 1rem;"></i>
                    <p>No reservations waiting for payment.</p>
                </div>
            <?php endif; ?>
        </div>
        
                     <div class="tab-content" id="paid-tab">
            <?php if (!empty($paid_reservations)): ?>
                <div class="reservations-table-container">
                    <table class="reservations-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Date & Time</th>
                                <th>Guests</th>
                                <th>Table</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paid_reservations as $reservation): ?>
                            <tr>
                                <td>
                                    <div class="customer-info">
                                        <div class="customer-name"><?= htmlspecialchars($reservation['name']) ?></div>
                                        <div class="customer-contact">
                                            <?= htmlspecialchars($reservation['user_email']) ?><br>
                                            <?= htmlspecialchars($reservation['user_phone']) ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="reservation-datetime">
                                    <?= date('M j, Y', strtotime($reservation['reservation_date'])) ?><br>
                                    <?= date('g:i A', strtotime($reservation['reservation_time'])) ?>
                                </td>
                                <td><?= $reservation['guests'] ?></td>
                                <td>Table <?= $reservation['table_number'] ?></td>
                                <td><span class="status-badge status-confirmed">Confirmed</span></td>
                                <td>
                                    <span class="status-badge status-paid">Paid</span><br>
                                    <small><?= date('M j', strtotime($reservation['payment_date'])) ?></small>
                                </td>
                                <td>
                                    <form id="form-<?= $reservation['id'] ?>" class="compact-form" method="POST">
                                        <input type="hidden" name="reservation_id" value="<?= $reservation['id'] ?>">
                                        <input type="hidden" name="table_number" value="<?= $reservation['table_number'] ?>">
                                        <select name="status" required>
                                            <option value="confirmed" <?= $reservation['status'] == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                            <option value="completed" <?= $reservation['status'] == 'completed' ? 'selected' : '' ?>>Complete</option>
                                            <option value="cancelled" <?= $reservation['status'] == 'cancelled' ? 'selected' : '' ?>>Cancel</option>
                                        </select>
                                        <button type="submit" name="update_reservation" class="btn btn-primary btn-sm">
                                            <i class="fas fa-save"></i> Update
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-dollar-sign fa-2x" style="margin-bottom: 1rem;"></i>
                    <p>No paid reservations found.</p>
                </div>
            <?php endif; ?>
        </div>
        
                     <div class="tab-content" id="completed-tab">
            <?php if (!empty($completed_reservations)): ?>
                <div class="reservations-table-container">
                    <table class="reservations-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Date & Time</th>
                                <th>Guests</th>
                                <th>Table</th>
                                <th>Status</th>
                                <th>Payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($completed_reservations as $reservation): ?>
                            <tr>
                                <td>
                                    <div class="customer-info">
                                        <div class="customer-name"><?= htmlspecialchars($reservation['name']) ?></div>
                                        <div class="customer-contact">
                                            <?= htmlspecialchars($reservation['user_email']) ?><br>
                                            <?= htmlspecialchars($reservation['user_phone']) ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="reservation-datetime">
                                    <?= date('M j, Y', strtotime($reservation['reservation_date'])) ?><br>
                                    <?= date('g:i A', strtotime($reservation['reservation_time'])) ?>
                                </td>
                                <td><?= $reservation['guests'] ?></td>
                                <td>Table <?= $reservation['table_number'] ?></td>
                                <td><span class="status-badge status-completed">Completed</span></td>
                                <td>
                                    <span class="status-badge status-paid">Paid</span><br>
                                    <small><?= date('M j', strtotime($reservation['payment_date'])) ?></small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-check fa-2x" style="margin-bottom: 1rem;"></i>
                    <p>No completed reservations found.</p>
                </div>
            <?php endif; ?>
        </div>
        
                     <div class="tab-content" id="cancelled-tab">
            <?php if (!empty($cancelled_reservations)): ?>
                <div class="reservations-table-container">
                    <table class="reservations-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Date & Time</th>
                                <th>Guests</th>
                                <th>Table</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cancelled_reservations as $reservation): ?>
                            <tr>
                                <td>
                                    <div class="customer-info">
                                        <div class="customer-name"><?= htmlspecialchars($reservation['name']) ?></div>
                                        <div class="customer-contact">
                                            <?= htmlspecialchars($reservation['user_email']) ?><br>
                                            <?= htmlspecialchars($reservation['user_phone']) ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="reservation-datetime">
                                    <?= date('M j, Y', strtotime($reservation['reservation_date'])) ?><br>
                                    <?= date('g:i A', strtotime($reservation['reservation_time'])) ?>
                                </td>
                                <td><?= $reservation['guests'] ?></td>
                                <td><?= $reservation['table_number'] ? 'Table '.$reservation['table_number'] : 'N/A' ?></td>
                                <td><span class="status-badge status-cancelled">Cancelled</span></td>
                                <td>
                                    <?php if ($reservation['payment_status'] == 'paid'): ?>
                                        <span class="status-badge status-paid">Paid</span><br>
                                        <small><?= date('M j', strtotime($reservation['payment_date'])) ?></small>
                                    <?php else: ?>
                                        <span class="status-badge">Not Paid</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($reservation['payment_status'] == 'paid'): ?>
                                        <form id="form-<?= $reservation['id'] ?>" class="compact-form" method="POST">
                                            <input type="hidden" name="reservation_id" value="<?= $reservation['id'] ?>">
                                            <input type="hidden" name="table_number" value="<?= $reservation['table_number'] ?>">
                                            <select name="status" required>
                                                <option value="cancelled" selected>Cancelled</option>
                                                <option value="confirmed">Re-activate</option>
                                            </select>
                                            <button type="submit" name="update_reservation" class="btn btn-primary btn-sm">
                                                <i class="fas fa-save"></i> Update
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="status-badge">No actions</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-times fa-2x" style="margin-bottom: 1rem;"></i>
                    <p>No cancelled reservations found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Tab switching function
    function switchTab(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Remove active class from all buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Show selected tab content
        document.getElementById(tabName + '-tab').classList.add('active');
        
        // Activate clicked button
        event.currentTarget.classList.add('active');
        
        // Update URL hash
        window.location.hash = tabName;
    }

    // Highlight selected table options
    document.querySelectorAll('.table-option input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.disabled) return;
            
            const parent = this.closest('.table-option');
            document.querySelectorAll('.table-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            parent.classList.add('selected');
        });
    });

    // Form validation for table selection
document.querySelectorAll('form[id^="form-"]').forEach(form => {
    form.addEventListener('submit', function(e) {
        const statusSelect = this.querySelector('select[name="status"]');
        const tableRadios = this.querySelectorAll('input[name="table_number"]:not(:disabled)');
        const checkedRadio = Array.from(tableRadios).find(radio => radio.checked);
        const errorDiv = document.getElementById(`table-error-${this.id.split('-')[1]}`);
        
        // Reset error state
        if (errorDiv) errorDiv.style.display = 'none';
        
        // Validate table selection for confirmed status
        if (statusSelect.value === 'confirmed') {
            // Check if there are any enabled radio buttons
            if (tableRadios.length > 0 && !checkedRadio) {
                e.preventDefault();
                if (errorDiv) {
                    errorDiv.style.display = 'block';
                    errorDiv.textContent = "Please select an available table";
                }
                
                // Scroll to the error
                errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
        }
    });
});

    // Check URL hash on page load
    function checkHash() {
        const hash = window.location.hash.substring(1);
        if (hash && ['pending', 'waiting', 'paid', 'completed', 'cancelled'].includes(hash)) {
            // Find the button with the matching data-tab and click it
            document.querySelector(`.tab-btn[onclick*="${hash}"]`).click();
        }
    }

    // Initialize tabs
    window.addEventListener('load', function() {
        checkHash();
        
        // If no hash or invalid hash, default to pending tab
        if (!window.location.hash || !['pending', 'waiting', 'paid', 'completed', 'cancelled'].includes(window.location.hash.substring(1))) {
            document.querySelector('.tab-btn[onclick*="pending"]').click();
        }
    });

    // Listen for hash changes
    window.addEventListener('hashchange', checkHash);
</script>
</body>
</html>