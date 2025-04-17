<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$stats = [
    'active_reservations' => 0,
    'pending_orders' => 0,
    'completed_orders' => 0
];

$stmt = $conn->prepare("SELECT COUNT(*) FROM reservations WHERE status IN ('pending', 'confirmed')");
$stmt->execute();
$stmt->bind_result($stats['active_reservations']);
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

$recent_reservations = [];
$stmt = $conn->prepare("SELECT r.*, u.fullname FROM reservations r JOIN users u ON r.user_id = u.id ORDER BY created_at DESC LIMIT 5");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Employee Dashboard - CABS KOREAN</title>
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
            --warning: #FFC107;
            --info: #2196F3;
            --danger: #F44336;
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
            transition: transform 0.3s ease;
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
            margin-bottom: 2rem;
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

        .employee-user {
            display: flex;
            align-items: center;
        }

        .employee-user img {
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
            background: linear-gradient(to right, rgba(0, 0, 0, 0.8), rgba(5, 20, 0, 0.8));
            border-radius: 50px;
            padding: 1.5rem;
            text-align: center;
            border: 1px solid var(--primary);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(76, 175, 80, 0.1);
        }

        .stat-value {
            font-size: 2.5rem;
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
            background: rgba(2, 18, 0, 0.8);
            border-radius: 40px;
            border: 1px solid rgba(9, 75, 0, 0.8);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .recent-title {
            font-size: 1.2rem;
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
            border-bottom: 1px solid rgba(76, 175, 80, 0.2);
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
            color: var(--primary);
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
            color: var(--primary);
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
            }
            
            .employee-sidebar.active {
                transform: translateX(0);
            }

            .mobile-menu-toggle {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .reservation-table th, 
            .reservation-table td {
                padding: 0.6rem;
                font-size: 0.9rem;
            }
        }

             
        @media only screen and (max-width: 430px) {
            body {
                font-size: 14px;
            }

            .employee-main {
                padding: 0.8rem;
            }

            .employee-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .stats-grid {
                gap: 1rem;
            }

            .stat-card {
                padding: 1rem;
            }

            .stat-value {
                font-size: 2rem;
            }

            .reservation-table th, 
            .reservation-table td {
                padding: 0.5rem;
                font-size: 0.85rem;
            }

            .status-badge {
                padding: 0.2rem 0.6rem;
                font-size: 0.75rem;
            }

            .recent-title {
                font-size: 1.1rem;
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
    </style>
</head>
<body>
    <div class="employee-sidebar">
        <div class="employee-logo">
            <img src="images/employee-logo.png" alt="CABS KOREAN Logo">
            <h2>CABS STAFF</h2>
        </div>
        
        <ul class="employee-nav">
            <li><a href="employee_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="employee_reservations.php"><i class="fas fa-calendar-alt"></i> Reservations</a></li>
            <li><a href="employee_orders.php"><i class="fas fa-receipt"></i> Orders</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

             <div class="employee-main">
        <div class="employee-header">
            <button class="mobile-menu-toggle" id="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="employee-title">Dashboard Overview</h1>
            <div class="employee-user">
                <span><?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Employee'); ?></span>
            </div>
        </div>

                     <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Active Reservations</div>
                <div class="stat-value"><?php echo $stats['active_reservations']; ?></div>
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
                <a href="employee_reservations.php">View All</a>
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

    <script>
        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.querySelector('.employee-sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>