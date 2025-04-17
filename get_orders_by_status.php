<?php
include 'db_connect.php';
session_start();

// Verify admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access");
}

$status_filter = $_GET['status'] ?? 'all';

// Build base query
$query = "SELECT o.*, u.fullname as customer_name, u.email as customer_email, u.phone as customer_phone 
          FROM orders o
          LEFT JOIN users u ON o.user_id = u.id";

// Add status filter if not 'all'
if ($status_filter !== 'all') {
    $query .= " WHERE o.status = ?";
}

$query .= " ORDER BY o.order_date DESC LIMIT 50";

$stmt = $conn->prepare($query);

// Bind parameter if filtering by status
if ($status_filter !== 'all') {
    $stmt->bind_param("s", $status_filter);
}

$stmt->execute();
$result = $stmt->get_result();

$orders = [];
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
?>

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
        <?php foreach ($orders as $index => $order): ?>
        <tr style="animation: fadeIn 0.3s ease; animation-fill-mode: both; animation-delay: <?= ($index + 1) * 0.1 ?>s;">
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