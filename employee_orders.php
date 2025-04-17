<?php
include 'audit_logger.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$message = '';
$orders = [];
$active_tab = $_GET['tab'] ?? 'orders'; 
$status_filter = $_GET['status'] ?? ($active_tab === 'delivery' ? 'ready' : 'all');

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
    } else {
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

$kitchen_status_counts = [];
$count_query = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
$count_result = $conn->query($count_query);
while ($row = $count_result->fetch_assoc()) {
    $kitchen_status_counts[$row['status']] = $row['count'];
}
$count_result->close();

$delivery_status_counts = [];
$delivery_count_query = "SELECT status, COUNT(*) as count FROM orders WHERE order_type = 'delivery' AND status IN ('ready', 'on_the_way', 'delivered') GROUP BY status";
$delivery_count_result = $conn->query($delivery_count_query);
while ($row = $delivery_count_result->fetch_assoc()) {
    $delivery_status_counts[$row['status']] = $row['count'];
}
$delivery_count_result->close();

$total_count = array_sum($kitchen_status_counts);
$total_delivery_count = array_sum($delivery_status_counts);

$query = "SELECT o.*, u.fullname as customer_name, u.email as customer_email, u.phone as customer_phone 
          FROM orders o
          LEFT JOIN users u ON o.user_id = u.id";

if ($active_tab === 'delivery') {
    $query .= " WHERE o.order_type = 'delivery'";
    if ($status_filter !== 'all') {
        $query .= " AND o.status = ?";
    }
} else {
    if ($status_filter !== 'all') {
        $query .= " WHERE o.status = ?";
    }
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
    'completed' => 'Completed'
];

$tabs_config = [
    'orders' => [
        'title' => 'Kitchen Orders',
        'statuses' => [
            'all' => 'All Orders',
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'preparing' => 'Preparing',
            'ready' => 'Ready'
        ],
        'count' => $total_count,
        'counts' => $kitchen_status_counts
    ],
    'delivery' => [
        'title' => 'Delivery Orders',
        'statuses' => [
            'all' => 'All Delivery',
            'ready' => 'Ready',
            'on_the_way' => 'On the Way',
            'delivered' => 'Delivered'
        ],
        'count' => $total_delivery_count,
        'counts' => $delivery_status_counts
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Order Management - CABS KOREAN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="icon" href="cabs.png" type="image/png">

    <style>
        :root {
            --primary: #4CAF50;
            --secondary: #2E7D32;
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
            overflow-x: hidden;
        }

        .employee-sidebar {
            width: 220px;
            background: rgba(9, 9, 9, 0.9);
            padding: 1.5rem;
            position: fixed;
            height: 100vh;
            border-right: 1px solid rgba(76, 175, 80, 0.2);
            backdrop-filter: blur(10px);
            z-index: 100;
        }

        .employee-logo {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(76, 175, 80, 0.2);
        }

        .employee-logo img {
            height: 40px;
            margin-right: 10px;
            border-radius: 50%;
        }

        .employee-logo h2 {
            font-size: 1.3rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        .employee-nav {
            list-style: none;
        }

        .employee-nav li {
            margin-bottom: 0.5rem;
        }

        .employee-nav a {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            color: var(--light);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .employee-nav a:hover, .employee-nav a.active {
            background: rgba(76, 175, 80, 0.1);
            color: var(--primary);
        }

        .employee-nav a i {
            margin-right: 0.8rem;
            width: 20px;
            text-align: center;
        }

        .employee-main {
            flex: 1;
            margin-left: 220px;
            padding: 2rem;
            width: calc(100% - 220px);
        }

        .employee-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(76, 175, 80, 0.2);
        }

        .employee-title {
            font-size: 1.5rem;
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
            border-bottom: 1px solid rgba(76, 175, 80, 0.1);
        }

        .orders-table th {
            background: rgba(76, 175, 80, 0.2);
            color: var(--primary);
            font-weight: 600;
        }

        .orders-table tr:hover {
            background: rgba(76, 175, 80, 0.05);
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
            box-shadow: 0 3px 10px rgba(76, 175, 80, 0.3);
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
            border: 1px solid rgba(76, 175, 80, 0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(76, 175, 80, 0.2);
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
            border-bottom: 1px solid rgba(76, 175, 80, 0.1);
        }

        .order-items-table th {
            background: rgba(76, 175, 80, 0.1);
            color: var(--primary);
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

        .status-tabs-container {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .table-container {
            max-height: 700px;
            overflow-y: auto;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            background: rgba(10, 10, 10, 0.96);
        }

        .table-container::-webkit-scrollbar {
            width: 6px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background-color: var(--primary);
            border-radius: 3px;
        }

        .table-container::-webkit-scrollbar-track {
            background: rgba(76, 175, 80, 0.1);
            border-radius: 3px;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(16, 16, 16, 0.8);
        }

        .orders-table th {
            position: sticky;
            top: 0;
            background: rgba(76, 175, 80, 0.2);
            z-index: 10;
        }
        
        .main-tabs {
            display: flex;
            border-bottom: 1px solid rgba(76, 175, 80, 0.2);
            margin-bottom: 1.5rem;
            overflow-x: auto;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
        }

        .main-tab {
            position: relative;
            padding: 0.8rem 1.2rem;
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
            flex-shrink: 0;
        }

        .main-tab::after {
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

        .main-tab.active {
            color: var(--primary);
        }

        .main-tab.active::after {
            transform: scaleX(1);
        }

        .main-tab:hover:not(.active) {
            color: var(--light);
        }

        .status-tabs {
            display: flex;
            border-bottom: 1px solid rgba(76, 175, 80, 0.2);
            margin-bottom: 1.5rem;
            overflow-x: auto;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
        }

        .status-tab {
            position: relative;
            padding: 0.6rem 0.8rem;
            background: none;
            border: none;
            color: #aaa;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            flex-shrink: 0;
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
            background: rgba(76, 175, 80, 0.2);
            color: var(--primary);
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
        }

        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--light);
            font-size: 1.5rem;
            cursor: pointer;
            margin-right: 1rem;
        }

        @media (max-width: 1200px) {
            .employee-main {
                margin-left: 0;
                padding: 1rem;
                width: 100%;
            }
            
            .employee-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .employee-sidebar.active {
                transform: translateX(0);
            }

            .mobile-menu-toggle {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .order-details-grid {
                grid-template-columns: 1fr;
            }
            
            .orders-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
                -webkit-overflow-scrolling: touch;
            }
            
            .orders-table th, 
            .orders-table td {
                padding: 0.6rem;
                font-size: 0.85rem;
                min-width: 120px;
            }

            .employee-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .main-tab {
                padding: 0.6rem 0.8rem;
                font-size: 0.9rem;
            }

            .status-tab {
                padding: 0.5rem 0.7rem;
                font-size: 0.8rem;
            }
        }

        @media only screen and (max-width: 430px) {
            body {
                font-size: 14px;
            }

            .employee-main {
                padding: 0.8rem;
            }

            .orders-table th, 
            .orders-table td {
                padding: 0.5rem;
                font-size: 0.8rem;
                min-width: 100px;
            }

            .btn {
                padding: 0.4rem 0.6rem;
                font-size: 0.7rem;
            }

            .status-badge {
                padding: 0.2rem 0.4rem;
                font-size: 0.7rem;
            }

            .main-tab {
                padding: 0.5rem 0.7rem;
                font-size: 0.85rem;
            }

            .status-tab {
                padding: 0.4rem 0.6rem;
                font-size: 0.75rem;
            }

            .modal-content {
                width: 95%;
                padding: 1rem;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
    </style>
</head>
<body>
<div class="employee-sidebar">
        <div class="employee-logo">
            <img src="images/employee-logo.png" alt="CABS KOREAN Logo">
            <h2>CABS STAFF</h2>
        </div>
        
        <ul class="employee-nav">
            <li><a href="employee_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="employee_reservations.php"><i class="fas fa-calendar-alt"></i> Reservations</a></li>
            <li><a href="employee_orders.php" class="active"><i class="fas fa-receipt"></i> Orders</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="employee-main">
        <div class="employee-header">
            <button class="mobile-menu-toggle" id="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="employee-title">Order Management</h1>
            <div class="employee-user">
                <span><?= htmlspecialchars($_SESSION['fullname'] ?? 'Employee') ?></span>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="main-tabs">
            <?php foreach ($tabs_config as $tab_id => $tab): ?>
                <a href="?tab=<?= $tab_id ?>&status=<?= $tab_id === 'delivery' ? 'ready' : 'all' ?>" 
                   class="main-tab <?= $active_tab === $tab_id ? 'active' : '' ?>">
                   <i class="<?= $tab_id === 'delivery' ? 'fas fa-truck' : 'fas fa-utensils' ?>"></i>
                   <?= $tab['title'] ?>
                   <span class="status-count"><?= $tab['count'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="status-tabs-container">
            <div class="status-tabs">
                <?php foreach ($tabs_config[$active_tab]['statuses'] as $status => $label): ?>
                    <a href="?tab=<?= $active_tab ?>&status=<?= $status ?>" 
                       class="status-tab <?= $status_filter === $status ? 'active' : '' ?>">
                       <?= $label ?>
                       <?php if ($status === 'all'): ?>
                           <span class="status-count"><?= $tabs_config[$active_tab]['count'] ?></span>
                       <?php else: ?>
                           <span class="status-count"><?= $tabs_config[$active_tab]['counts'][$status] ?? 0 ?></span>
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
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('order-modal');
            const closeBtn = document.querySelector('.close-modal');
            const viewButtons = document.querySelectorAll('.view-order');
            const menuToggle = document.getElementById('menu-toggle');
            const sidebar = document.querySelector('.employee-sidebar');

            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const orderId = this.getAttribute('data-order-id');
                    fetchOrderDetails(orderId);
                });
            });

            closeBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', function(e) {
                if (e.target === modal) closeModal();
            });

            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });

            function fetchOrderDetails(orderId) {
                fetch(`get_order_details_employee.php?order_id=${orderId}`)
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById('order-details-content').innerHTML = data;
                        modal.style.display = 'flex';
                    })
                    .catch(error => console.error('Error:', error));
            }

            function closeModal() {
                modal.style.display = 'none';
            }
        });
    </script>
</body>
</html>