<?php
include 'audit_logger.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$message = '';
$orders = [];
$status_filter = $_GET['status'] ?? 'all';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $old_data = $result->fetch_assoc();
    $stmt->close();

    if ($status == 'delivered') {
        $stmt = $conn->prepare("UPDATE orders SET status = ?, delivered_at = NOW() WHERE id = ?");
    } 
    elseif ($status == 'refunded') {
        $stmt = $conn->prepare("UPDATE orders SET status = ?, payment_status = 'refunded' WHERE id = ?");
    }
    else {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    }
    
    $stmt->bind_param("si", $status, $order_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        log_audit_action('order_status_changed', 'orders', $order_id, [
            'old_status' => $old_data['status']
        ], [
            'new_status' => $status
        ]);

        $message = "Order #$order_id updated successfully!";
    }
    $stmt->close();
}

$status_counts = [];
$count_query = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
$count_result = $conn->query($count_query);
while ($row = $count_result->fetch_assoc()) {
    $status_counts[$row['status']] = $row['count'];
}
$count_result->close();

$total_count = array_sum($status_counts);

$query = "SELECT o.*, u.fullname as customer_name, u.email as customer_email, u.phone as customer_phone 
          FROM orders o
          LEFT JOIN users u ON o.user_id = u.id";

if ($status_filter !== 'all') {
    $query .= " WHERE o.status = ?";
}

$query .= " ORDER BY o.order_date DESC LIMIT 50";

$stmt = $conn->prepare($query);

if ($status_filter !== 'all') {
    $stmt->bind_param("s", $status_filter);
}

$stmt->execute();
$result = $stmt->get_result();

while ($order = $result->fetch_assoc()) {
    $items_query = "SELECT oi.*, mi.name as item_name 
                    FROM order_items oi
                    JOIN menu_items mi ON oi.menu_item_id = mi.id
                    WHERE oi.order_id = ?";
    $items_stmt = $conn->prepare($items_query);
    $items_stmt->bind_param("i", $order['id']);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    $order['items'] = $items_result->fetch_all(MYSQLI_ASSOC);
    $items_stmt->close();
    
    $orders[] = $order;
}
$stmt->close();
$conn->close();

$status_display = [
    'pending' => 'Pending',
    'confirmed' => 'Confirmed',
    'preparing' => 'Preparing',
    'ready' => 'Ready',
    'on_the_way' => 'On the way',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled',
    'refunded' => 'Refunded',
    'completed' => 'Completed'
];

$status_tabs = [
    'all' => 'All Orders',
    'pending' => 'Pending',
    'confirmed' => 'Confirmed',
    'preparing' => 'Preparing',
    'ready' => 'Ready',
    'on_the_way' => 'On the Way',
    'delivered' => 'Delivered'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - CABS KOREAN</title>
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
            margin-bottom: 1.5rem;
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

                     .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
            background: rgba(40, 40, 40, 0.8);
            border-radius: 8px;
            overflow: hidden;
        }

        .orders-table th, .orders-table td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 126, 179, 0.1);
        }

        .orders-table th {
            background: rgba(255, 126, 179, 0.2);
            color: var(--primary);
            font-weight: 600;
        }

        .orders-table tr:hover {
            background: rgba(255, 126, 179, 0.05);
        }

                     .status-badge {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            width: 100px;
            text-align: center;
        }

        .status-pending { background: rgba(255, 193, 7, 0.1); color: #FFC107; }
        .status-confirmed { background: rgba(76, 175, 80, 0.1); color: #4CAF50; }
        .status-preparing { background: rgba(33, 150, 243, 0.1); color: #2196F3; }
        .status-ready { background: rgba(156, 39, 176, 0.1); color: #9C27B0; }
        .status-on_the_way { background: rgba(255, 152, 0, 0.1); color: #FF9800; }
        .status-delivered { background: rgba(96, 125, 139, 0.1); color: #607D8B; }
        .status-cancelled { background: rgba(244, 67, 54, 0.1); color: #F44336; }
        .status-refunded { background: rgba(156, 39, 176, 0.1); color: #9C27B0; }
        .status-completed { background: rgba(76, 175, 80, 0.1); color: #4CAF50; }

                     .btn {
            padding: 0.5rem 0.8rem;
            border-radius: 4px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.8rem;
        }

        .btn-primary {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(255, 126, 179, 0.3);
        }

        .btn-sm {
            padding: 0.3rem 0.6rem;
            font-size: 0.75rem;
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
            padding: 1.5rem;
            border-radius: 8px;
            width: 90%;
            max-width: 1200px;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid rgba(255, 126, 179, 0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 126, 179, 0.2);
        }

        .modal-title {
            font-size: 1.3rem;
            color: var(--primary);
        }


        .close-modal {
            background: none;
            border: none;
            color: var(--light);
            font-size: 1.5rem;
            cursor: pointer;
        }

        .order-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .order-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }

        .order-items-table th, 
        .order-items-table td {
            padding: 0.6rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 126, 179, 0.1);
        }

        .order-items-table th {
            background: rgba(255, 126, 179, 0.1);
            color: var(--primary);
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
            .order-details-grid {
                grid-template-columns: 1fr;
            }
            
            .orders-table th, 
            .orders-table td {
                padding: 0.6rem;
                font-size: 0.9rem;
            }
        }

                     .message {
            padding: 0.8rem;
            margin-bottom: 1rem;
            border-radius: 6px;
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
            border-left: 4px solid #4CAF50;
            font-size: 0.9rem;
        }

                     .pagination {
            display: flex;
            justify-content: center;
            margin-top: 1.5rem;
            gap: 0.5rem;
        }

        .page-link {
            padding: 0.5rem 0.8rem;
            border-radius: 4px;
            background: rgba(40, 40, 40, 0.8);
            color: var(--light);
            text-decoration: none;
            transition: all 0.2s;
        }

        .page-link:hover, .page-link.active {
            background: rgba(255, 126, 179, 0.2);
            color: var(--primary);
        }
                     .status-tabs-container {
            position: relative;
            margin-bottom: 1.5rem;
        }

        

                     .table-container {
            max-height: 700px;
            overflow-y: auto;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, rgba(30, 30, 30, 0.86) 0%, rgba(40, 40, 40, 0.7) 100%);
        }

        .table-container::-webkit-scrollbar {
            width: 6px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background-color: var(--primary);
            border-radius: 3px;
        }

        .table-container::-webkit-scrollbar-track {
            background: rgba(255, 126, 179, 0.1);
            border-radius: 3px;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(40, 40, 40, 0.8);
        }

        .orders-table th {
            position: sticky;
            top: 0;
            background: rgba(255, 126, 179, 0.2);
            z-index: 10;
        }
                     .status-tabs {
            display: flex;
            border-bottom: 1px solid rgba(255, 126, 179, 0.2);
            margin-bottom: 1.5rem;
            margin-top: 1.8rem;
        }

        .status-tab {
            position: relative;
            padding: 0.8rem 1.5rem;
            background: none;
            border: none;
            color: #aaa;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .status-tab::after {
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

        .status-tab.active {
            color: var(--primary);
        }

        .status-tab.active::after {
            transform: scaleX(1);
        }

        .status-tab:hover:not(.active) {
            color: var(--light);
        }

        .status-count {
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
                     .status-tabs {
            display: flex;
            border-bottom: 1px solid rgba(255, 126, 179, 0.2);
            margin-bottom: 1.5rem;
            margin-top: 1.8rem;
        }

        .status-tab {
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

        .status-tab::after {
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

        .status-tab.active {
            color: var(--primary);
        }

        .status-tab.active::after {
            transform: scaleX(1);
        }

        .status-tab:hover:not(.active) {
            color: var(--light);
        }

        .status-count {
            background: rgba(255, 126, 179, 0.2);
            color: var(--primary);
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.8rem;
        }

                     @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .table-container {
            max-height: 700px;
            overflow-y: auto;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, rgba(30, 30, 30, 0.87) 0%, rgba(40, 40, 40, 0.7) 100%);
            animation: fadeIn 0.5s ease;
        }

                     .orders-table tbody tr {
            animation: fadeIn 0.3s ease;
            animation-fill-mode: both;
            background: linear-gradient(135deg, rgba(30, 30, 30, 0.87) 0%, rgba(40, 40, 40, 0.7) 100%);

        }

                     .orders-table tbody tr:nth-child(1) { animation-delay: 0.1s; }
        .orders-table tbody tr:nth-child(2) { animation-delay: 0.2s; }
        .orders-table tbody tr:nth-child(3) { animation-delay: 0.3s; }
        .orders-table tbody tr:nth-child(4) { animation-delay: 0.4s; }
        .orders-table tbody tr:nth-child(5) { animation-delay: 0.5s; }
        .orders-table tbody tr:nth-child(n+6) { animation-delay: 0.6s; }

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
            animation: fadeIn 0.3s ease;
        }
        .status-pending { background: rgba(255, 193, 7, 0.1); color: #FFC107; border: 1px solid #FFC107; }
        .status-confirmed { background: rgba(76, 175, 80, 0.1); color: #4CAF50; border: 1px solid #4CAF50; }
        .status-paid { background: rgba(33, 150, 243, 0.1); color: #2196F3; border: 1px solid #2196F3; }
        .status-cancelled { background: rgba(244, 67, 54, 0.1); color: #F44336; border: 1px solid #F44336; }
        .status-waiting_payment { background: rgba(255, 165, 0, 0.1); color: #FFA500; border: 1px solid #FFA500; }
        .status-completed { background: rgba(190, 81, 209, 0.1); color:rgb(223, 73, 250); border: 1px solid #9C27B0; }
        .status-declined { background: rgba(158, 158, 158, 0.1); color: #9E9E9E; border: 1px solid #9E9E9E; }
        .status-refunded { background: rgba(121, 85, 72, 0.1); color: #795548; border: 1px solid #795548; }
        .status-failed { background: rgba(96, 125, 139, 0.1); color: #607D8B; border: 1px solid #607D8B; } 
        .status-preparing { background: rgba(255, 152, 0, 0.1); color: #FF9800; border: 1px solid #FF9800; }
        .status-ready { background: rgba(0, 150, 136, 0.1); color: #009688; border: 1px solid #009688; }
        .status-on_the_way { background: rgba(63, 81, 181, 0.1); color: #3F51B5; border: 1px solid #3F51B5; } 
        .status-delivered { background: rgba(139, 195, 74, 0.1); color: #8BC34A; border: 1px solid #8BC34A; }
        .status-no_show { background: rgba(233, 30, 99, 0.1); color: #E91E63; border: 1px solid #E91E63; }

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
            <li><a href="admin_reservations.php"><i class="fas fa-calendar-alt"></i> Reservations</a></li>
            <li><a href="admin_orders.php" class="active"><i class="fas fa-receipt"></i> Orders</a></li>
            <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="admin_audit_trail.php"><i class="fas fa-clipboard-list"></i> Logs</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="admin-main">
        <div class="admin-header">
            <h1 class="admin-title">Order Management</h1>
            <div class="admin-user">
                <span><?= htmlspecialchars($_SESSION['fullname'] ?? 'Admin') ?></span>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

              
        <div class="status-tabs-container">
            <div class="status-tabs">
                <?php foreach ($status_tabs as $status => $label): ?>
                    <a href="?status=<?= $status ?>" 
                       class="status-tab <?= $status_filter === $status ? 'active' : '' ?>">
                       <?= $label ?>
                       <?php if ($status === 'all'): ?>
                           <span class="status-count"><?= $total_count ?></span>
                       <?php else: ?>
                           <span class="status-count"><?= $status_counts[$status] ?? 0 ?></span>
                       <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

             
        <div class="table-container">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= $order['order_number'] ?></td>
                        <td>
                            <div style="font-weight: 500;"><?= htmlspecialchars($order['customer_name'] ?? 'Guest') ?></div>
                            <div style="font-size: 0.8rem; color: #aaa;">
                                <?= $order['order_type'] === 'delivery' ? 'Delivery' : 'Pickup' ?>
                            </div>
                        </td>
                        <td><?= date('M j, g:i A', strtotime($order['order_date'])) ?></td>
                        <td><?= count($order['items']) ?> item<?= count($order['items']) !== 1 ? 's' : '' ?></td>
                        <td>₱<?= number_format($order['total_price'], 2) ?></td>
                        <td>
                            <span class="status-badge status-<?= $order['status'] ?>">
                                <?= $status_display[$order['status']] ?? ucfirst($order['status']) ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-primary btn-sm view-order" 
                                    data-order-id="<?= $order['id'] ?>">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

             
        <div class="modal" id="order-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title">Order Details</h2>
                    <button class="close-modal">&times;</button>
                </div>
                <div id="order-details-content">
                         
                </div>
            </div>
        </div>
    </div>

    <script>
    // Enhanced JavaScript for smooth tab switching
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('order-modal');
        const closeBtn = document.querySelector('.close-modal');
        const viewButtons = document.querySelectorAll('.view-order');
        const statusTabs = document.querySelectorAll('.status-tab');
        const tableContainer = document.querySelector('.table-container');
        const ordersTable = document.querySelector('.orders-table tbody');

        // Tab switching with AJAX
        statusTabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Don't do anything if already active
                if (this.classList.contains('active')) return;
                
                // Get the status filter
                const status = this.getAttribute('href').split('=')[1];
                
                // Show loading state
                const oldHeight = tableContainer.offsetHeight;
                tableContainer.innerHTML = `
                    <div style="display: flex; justify-content: center; align-items: center; height: ${oldHeight}px;">
                        <i class="fas fa-spinner fa-spin fa-2x" style="color: var(--primary);"></i>
                    </div>
                `;
                
                // Update active tab
                statusTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                /// In your admin_orders.php, update the fetch success handler:
                fetch(`get_orders_by_status.php?status=${status}`)
                    .then(response => response.text())
                    .then(data => {
                        // Animate out old content
                        tableContainer.style.opacity = '0';
                        tableContainer.style.transform = 'translateY(10px)';
                        
                        setTimeout(() => {
                            // Replace with new content
                            tableContainer.innerHTML = data;
                            
                            // Animate in new content
                            tableContainer.style.opacity = '1';
                            tableContainer.style.transform = 'translateY(0)';
                            
                            // Reattach event listeners to new view buttons
                            document.querySelectorAll('.view-order').forEach(btn => {
                                btn.addEventListener('click', function() {
                                    const orderId = this.getAttribute('data-order-id');
                                    fetchOrderDetails(orderId);
                                });
                            });
                        }, 200);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        tableContainer.innerHTML = `
                            <div class="error-message" style="margin: 1rem;">
                                <i class="fas fa-exclamation-circle"></i> 
                                Failed to load orders. Please try again.
                            </div>
                        `;
                    });
            });
        });

        // Order modal functionality
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order-id');
                fetchOrderDetails(orderId);
            });
        });

        // Close modal
        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });

        function fetchOrderDetails(orderId) {
            // Show loading state
            document.getElementById('order-details-content').innerHTML = `
                <div style="text-align: center; padding: 2rem;">
                    <i class="fas fa-spinner fa-spin fa-2x" style="color: var(--primary);"></i>
                    <p>Loading order details...</p>
                </div>
            `;
            
            modal.style.display = 'flex';
            
            fetch(`get_order_details.php?order_id=${orderId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('order-details-content').innerHTML = data;
                    
                    // Animate content in
                    const content = document.querySelector('.modal-content');
                    content.style.animation = 'none';
                    void content.offsetWidth; // Trigger reflow
                    content.style.animation = 'fadeIn 0.3s ease';
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('order-details-content').innerHTML = `
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i> 
                            Failed to load order details. Please try again.
                        </div>
                    `;
                });
        }

        function closeModal() {
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.style.display = 'none';
                modal.style.opacity = '1';
            }, 300);
        }
    });
</script>
</body>
</html>