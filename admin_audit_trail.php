<?php
include 'audit_logger.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 6;
$offset = ($page - 1) * $per_page;

$count_stmt = $conn->prepare("SELECT COUNT(*) FROM audit_log");
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_row()[0];
$count_stmt->close();

$query = "SELECT a.*, u.username, u.fullname, u.avatar, u.role AS user_role
          FROM audit_log a
          LEFT JOIN users u ON a.user_id = u.id
          ORDER BY a.created_at DESC
          LIMIT ?, ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $offset, $per_page);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail - CABS KOREAN</title>
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
            overflow: hidden; 
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

        .admin-user img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 0.8rem;
        }

        .audit-container {
            background: linear-gradient(135deg, rgba(30, 30, 30, 0.87) 0%, rgba(40, 40, 40, 0.7) 100%);
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 126, 179, 0.2);
        }

        .audit-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }

        .audit-table th, .audit-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 126, 179, 0.1);
        }

        .audit-table th {
            background: rgba(255, 126, 179, 0.1);
            color: var(--primary);
            font-weight: 600;
        }

        .audit-table tr:hover {
            background: rgba(255, 126, 179, 0.05);
        }

        .audit-user {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .audit-user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }

        .audit-details {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .audit-details:hover {
            white-space: normal;
            overflow: visible;
            position: relative;
            z-index: 100;
            background: var(--dark);
            padding: 0.5rem;
            border-radius: 6px;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
        }

        .audit-pagination {
            display: flex;
            justify-content: start;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }

        .audit-page-btn {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            background: rgba(255, 126, 179, 0.1);
            color: var(--primary);
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            min-width: auto; 
            width: 10px;
        }


        .audit-page-btn:hover, .audit-page-btn.active {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            transform: translateY(-2px);
        }

        .audit-page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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

        .status-admin {
            background: rgba(255, 126, 179, 0.2);
            color: var(--primary);
        }

        .status-employee {
            background: rgba(33, 150, 243, 0.2);
            color: #2196F3;
        }

        .status-user {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
        }

        @media (max-width: 992px) {
            .audit-table {
                display: block;
                overflow-x: auto;
            }
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
            
            .audit-table th, .audit-table td {
                padding: 0.8rem;
                font-size: 0.9rem;
            }
        }
        .audit-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
            table-layout: fixed;
        }
        .audit-table th:nth-child(1),
        .audit-table td:nth-child(1) {
            width: 160px; 
        }

        .audit-table th:nth-child(2),
        .audit-table td:nth-child(2) {
            width: 220px; 
        }

        .audit-table th:nth-child(3),
        .audit-table td:nth-child(3) {
            width: 180px; 
        }

        .audit-table th:nth-child(4),
        .audit-table td:nth-child(4) {
            width: 260px; 
        }

        .audit-table th:nth-child(5),
        .audit-table td:nth-child(5) {
            width: 160px; 
        }
        .audit-table th,
        .audit-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 126, 179, 0.1);
            white-space: nowrap;
            width: 150px; 
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .audit-page-btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            background: rgba(255, 126, 179, 0.1);
            color: var(--primary);
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 600;
            border: none;
            cursor: pointer;
            min-width: 110px;
            display: inline-flex;
            justify-content: center;
            gap: 0.4rem;
        }

        .audit-page-btn:hover:not(:disabled),
        .audit-page-btn.active:not(:disabled) {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            transform: translateY(-2px);
        }

        .audit-page-btn:disabled {
            background: rgba(255, 126, 179, 0.05); 
            color: var(--primary);
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
            
        .audit-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        .pagination-nav {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .pagination-pages {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0 0.5rem;
        }

        .audit-page-btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            background: rgba(255, 126, 179, 0.1);
            color: var(--primary);
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 600;
            border: none;
            cursor: pointer;
            min-width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .audit-page-btn:hover:not(:disabled),
        .audit-page-btn.active {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            box-shadow: 0 4px 8px rgba(255, 126, 179, 0.3);
        }

        .audit-page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: rgba(255, 126, 179, 0.05);
        }

        .pagination-ellipsis {
            color: var(--light);
            padding: 0 0.5rem;
            display: flex;
            align-items: center;
            height: 40px;
        }

        .pagination-nav-btn {
            min-width: 100px;
        }

        .status-employee { background: rgba(33, 150, 243, 0.1); color: #2196F3; border: 1px solid #2196F3; }
        .status-admin { background:rgba(213, 104, 196, 0.1)6); color:rgb(250, 73, 185); border: 1px solid rgb(219, 99, 163); }
        .status-user { background: rgba(158, 158, 158, 0.1); color: #9E9E9E; border: 1px solid #9E9E9E; }

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
            <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="admin_audit_trail.php" class="active"><i class="fas fa-clipboard-list"></i> Logs</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="admin-main">
        <div class="admin-header">
            <h1 class="admin-title">Activity Logs</h1>
            <div class="admin-user">
                <span><?= htmlspecialchars($_SESSION['fullname'] ?? 'Admin') ?></span>
            </div>
        </div>

        <div class="audit-container">
            <table class="audit-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= date('M j, Y g:i A', strtotime($log['created_at'])) ?></td>
                            <td>
                                <div class="audit-user">
                                    <?php if ($log['user_id']): ?>
                                        <img src="<?= !empty($log['avatar']) ? htmlspecialchars($log['avatar']) : '../images/default-avatar.jpg' ?>" alt="User Avatar" class="audit-user-avatar">
                                        <div>
                                            <div><?= htmlspecialchars($log['fullname'] ?? $log['username'] ?? 'User #'.$log['user_id']) ?></div>
                                            <span class="status-badge status-<?= $log['user_role'] ?>">
                                                <?= ucfirst($log['user_role'] ?? 'guest') ?>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <span>Guest</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span style="font-weight: 500;"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $log['action_type']))) ?></span>
                            </td>
                            <td class="audit-details" title="Old: <?= htmlspecialchars($log['old_values']) ?>\nNew: <?= htmlspecialchars($log['new_values']) ?>">
                                <?php if ($log['table_affected']): ?>
                                    <span style="color: var(--primary);"><?= $log['table_affected'] ?></span>
                                    <?= $log['record_id'] ? '<span style="color: #aaa;">(ID: '.$log['record_id'].')</span>' : '' ?>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($log['ip_address']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="audit-pagination">
                <div class="pagination-nav">
                    <button class="audit-page-btn pagination-nav-btn" <?= $page <= 1 ? 'disabled' : '' ?> onclick="window.location.href='?page=<?= $page - 1 ?>'">
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>
                </div>

                <div class="pagination-pages">
                    <?php 
                    $total_pages = ceil($total / $per_page);
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1): ?>
                        <button class="audit-page-btn" onclick="window.location.href='?page=1'">1</button>
                        <?php if ($start_page > 2): ?>
                            <span class="pagination-ellipsis">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <button class="audit-page-btn <?= $i == $page ? 'active' : '' ?>" onclick="window.location.href='?page=<?= $i ?>'">
                            <?= $i ?>
                        </button>
                    <?php endfor; ?>
                    
                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span class="pagination-ellipsis">...</span>
                        <?php endif; ?>
                        <button class="audit-page-btn" onclick="window.location.href='?page=<?= $total_pages ?>'">
                            <?= $total_pages ?>
                        </button>
                    <?php endif; ?>
                </div>

                <div class="pagination-nav">
                    <button class="audit-page-btn pagination-nav-btn" <?= $page >= $total_pages ? 'disabled' : '' ?> onclick="window.location.href='?page=<?= $page + 1 ?>'">
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>