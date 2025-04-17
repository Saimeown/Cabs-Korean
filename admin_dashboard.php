<?php
// admin_dashboard.php
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Get stats for dashboard
$stats = [
    'total_users' => 0,
    'active_reservations' => 0,
    'pending_orders' => 0,
    'completed_orders' => 0,
    'waiting_payment' => 0,
    'total_revenue' => 0
];

$stmt = $conn->prepare("SELECT COUNT(*) FROM users");
$stmt->execute();
$stmt->bind_result($stats['total_users']);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) FROM reservations WHERE status IN ('pending', 'confirmed')");
$stmt->execute();
$stmt->bind_result($stats['active_reservations']);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) FROM reservations WHERE status = 'confirmed' AND payment_status = 'waiting_payment'");
$stmt->execute();
$stmt->bind_result($stats['waiting_payment']);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
$stmt->execute();
$stmt->bind_result($stats['pending_orders']);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE status = 'completed'");
$stmt->execute();
$stmt->bind_result($stats['completed_orders']);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("SELECT SUM(total_price) FROM orders WHERE payment_status = 'paid'");
$stmt->execute();
$stmt->bind_result($stats['total_revenue']);
$stmt->fetch();
$stmt->close();

$recent_reservations = [];
$stmt = $conn->prepare("SELECT r.*, u.fullname FROM reservations r JOIN users u ON r.user_id = u.id ORDER BY created_at DESC LIMIT 4");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_reservations[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CABS KOREAN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="icon" href="cabs.png" type="image/png">
    <style>
        :root {
            --primary: #ff7eb3;
            --secondary: #ff0844;
            --dark: #121212;
            --light: #e0e0e0;
            --gray:rgb(0, 0, 0);
            --success: #4CAF50;
            --warning: #FFC107;
            --info: #2196F3;
            --danger: #F44336;
            --purple: #9C27B0;
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
            border-bottom: 2px solid rgba(255, 126, 179, 0.2);
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

        .admin-user img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 0.8rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(40, 40, 40, 0.8);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            border-left: 4px solid var(--primary);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(255, 126, 179, 0.1);
        }

        .stat-value {
            font-size: 3rem;
            font-weight: 700;
            margin: 0.5rem 0;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        .stat-label {
            color: #aaa;
            font-size: 0.9rem;
        }

        .recent-card {
            background: rgba(40, 40, 40, 0.8);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .recent-title {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: var(--light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .recent-title a {
            color: var(--primary);
            font-size: 0.9rem;
            text-decoration: none;
        }

        .recent-title a:hover {
            text-decoration: underline;
        }

        .reservation-table {
            width: 100%;
            border-collapse: collapse;
        }

        .reservation-table th {
            text-align: left;
            padding: 0.8rem;
            color: var(--primary);
            border-bottom: 1px solid rgba(255, 126, 179, 0.2);
        }

        .reservation-table td {
            padding: 0.8rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .reservation-table tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            display: inline-block;
            width: 100px;
            text-align: center;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: var(--warning);
        }

        .status-confirmed {
            background: rgba(76, 175, 80, 0.2);
            color: var(--success);
        }

        .status-waiting_payment {
            background: rgba(255, 165, 0, 0.2);
            color: #FFA500;
        }

        .status-paid {
            background: rgba(33, 150, 243, 0.2);
            color: var(--info);
        }

        .status-cancelled {
            background: rgba(244, 67, 54, 0.2);
            color: var(--danger);
        }

        .status-completed {
            background: rgba(76, 175, 80, 0.2);
            color: var(--success);
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .admin-main {
                margin-left: 0;
            }
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
    margin-left: 280px; 
    padding: 2.5rem; 
    min-height: 100vh;
    background-color: var(--dark);
    transition: margin-left 0.3s ease;
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
    
    .admin-main {
        margin-left: 0;
        padding: 1.5rem;
    }
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


.admin-main {
    flex: 1;
    margin-left: 280px;
    padding: 2rem 3rem;
    min-height: 100vh;
    background-color: #0f0f0f;
    transition: margin-left 0.3s ease;
    position: relative;
    overflow: hidden;
}

.admin-main::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle at 75% 25%, rgba(255, 126, 179, 0.03) 0%, transparent 50%);
    pointer-events: none;
    z-index: 0;
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid rgba(255, 126, 179, 0.15);
    position: relative;
    z-index: 1;
}

.admin-title {
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(to right, var(--primary), var(--secondary));
    background-clip: text;
    -webkit-background-clip: text;
    color: transparent;
    letter-spacing: 0.5px;
}

.admin-user {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.admin-user-info {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    background: rgba(255, 255, 255, 0.05);
    padding: 0.6rem 1rem;
    border-radius: 50px;
    border: 1px solid rgba(255, 126, 179, 0.1);
}

.admin-user img {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid rgba(255, 126, 179, 0.3);
}

.admin-user span {
    font-weight: 500;
    color: var(--light);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
    position: relative;
    z-index: 1;
}

.stat-card {
    background: linear-gradient(135deg, rgb(26, 26, 26) 0%, rgba(102, 28, 44, 0.7) 100%);
    border-radius: 20px;
    padding: 1.75rem;
    text-align: center;
    border: 2px solid rgb(233, 38, 100);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    position: relative;
    overflow: hidden;
}



.stat-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(255, 126, 179, 0.15);
    border-color: rgba(255, 126, 179, 0.2);
}

.stat-value {
    font-size: 2.75rem;
    font-weight: 800;
    margin: 0.5rem 0;
    background: linear-gradient(to right, var(--primary), var(--secondary));
    background-clip: text;
    -webkit-background-clip: text;
    color: transparent;
    letter-spacing: 1px;
}

.stat-label {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.95rem;
    font-weight: 400;
    letter-spacing: 0.5px;
}

.recent-card {
    background: linear-gradient(135deg, rgba(30, 30, 30, 0.7) 0%, rgba(40, 40, 40, 0.7) 100%);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    position: relative;
    z-index: 1;
}

.recent-title {
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
    color: var(--light);
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
}

.recent-title a {
    color: var(--primary);
    font-size: 0.95rem;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s;
}

.recent-title a:hover {
    color: var(--secondary);
    text-decoration: none;
    gap: 0.7rem;
}

.recent-title a i {
    transition: all 0.3s;
}

.recent-title a:hover i {
    transform: translateX(3px);
}

.reservation-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.reservation-table th {
    text-align: left;
    padding: 1rem;
    color: var(--primary);
    border-bottom: 1px solid rgba(255, 126, 179, 0.2);
    font-weight: 500;
    letter-spacing: 0.5px;
}

.reservation-table td {
    padding: 1.25rem 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    vertical-align: middle;
}

.reservation-table tr:last-child td {
    border-bottom: none;
}

.reservation-table tr:hover td {
    background: rgba(255, 126, 179, 0.03);
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 100px;
    letter-spacing: 0.5px;
}

.status-badge i {
    margin-right: 0.5rem;
    font-size: 0.8rem;
}

.floating-circle {
    position: absolute;
    border-radius: 50%;
    filter: blur(60px);
    opacity: 0.15;
    z-index: 0;
}

.floating-circle-1 {
    width: 300px;
    height: 300px;
    background: var(--primary);
    top: -100px;
    right: -100px;
}

.floating-circle-2 {
    width: 400px;
    height: 400px;
    background: var(--secondary);
    bottom: -150px;
    left: -150px;
}

@media (max-width: 1200px) {
    .admin-main {
        padding: 2rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
}

@media (max-width: 992px) {
    .admin-sidebar {
        width: 240px;
    }
    
    .admin-main {
        margin-left: 240px;
        padding: 1.75rem;
    }
}

@media (max-width: 768px) {
    .admin-sidebar {
        width: 100%;
        position: relative;
    }
    
    .admin-main {
        margin-left: 0;
        padding: 1.5rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .admin-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
}

@media (max-width: 576px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .admin-main {
        padding: 1.25rem;
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
            <li><a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="admin_menu.php"><i class="fas fa-utensils"></i> Menu</a></li>
            <li><a href="admin_reservations.php"><i class="fas fa-calendar-alt"></i> Reservations</a></li>
            <li><a href="admin_orders.php"><i class="fas fa-receipt"></i> Orders</a></li>
            <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="admin_audit_trail.php"><i class="fas fa-clipboard-list"></i> Logs</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

      
    <div class="admin-main">
        <div class="admin-header">
            <h1 class="admin-title">Dashboard </h1>
            <div class="admin-user">
                <span><?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Admin'); ?></span>
            </div>
        </div>

          
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Users</div>
                <div class="stat-value"><?php echo $stats['total_users']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Active Reservations</div>
                <div class="stat-value"><?php echo $stats['active_reservations']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Waiting Payment</div>
                <div class="stat-value"><?php echo $stats['waiting_payment']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pending Orders</div>
                <div class="stat-value"><?php echo $stats['pending_orders']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Completed Orders</div>
                <div class="stat-value"><?php echo $stats['completed_orders']; ?></div>
            </div>
        </div>

          
        <div class="recent-card">
            <h2 class="recent-title">
                Recent Reservations
                <a href="admin_reservations.php">View All</a>
            </h2>
            <table class="reservation-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Date/Time</th>
                        <th>Guests</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_reservations as $reservation): 
                        $status = $reservation['status'];
                        if ($status == 'confirmed' && $reservation['payment_status'] == 'waiting_payment') {
                            $status = 'waiting_payment';
                        }
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($reservation['fullname']) ?></td>
                            <td>
                                <?= date('M j, Y', strtotime($reservation['reservation_date'])) ?><br>
                                <?= date('g:i A', strtotime($reservation['reservation_time'])) ?>
                            </td>
                            <td><?= $reservation['guests'] ?></td>
                            <td>
                                <span class="status-badge status-<?= strtolower($status) ?>">
                                    <?= ucfirst(str_replace('_', ' ', $status)) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recent_reservations)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 2rem 0;">
                                No recent reservations found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>