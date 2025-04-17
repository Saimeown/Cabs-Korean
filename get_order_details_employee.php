<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    die("Unauthorized access");
}

$order_id = (int)$_GET['order_id'];

$query = "SELECT o.*, u.fullname as customer_name, u.email as customer_email, u.phone as customer_phone 
          FROM orders o
          LEFT JOIN users u ON o.user_id = u.id
          WHERE o.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

$items_query = "SELECT oi.*, mi.name as item_name, mi.image_url as item_image 
                FROM order_items oi
                JOIN menu_items mi ON oi.menu_item_id = mi.id
                WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_query);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$order['items'] = $items_result->fetch_all(MYSQLI_ASSOC);
$items_stmt->close();

$status_options = [
    'preparing' => 'Preparing',
    'ready' => 'Ready',
    'cancelled' => 'Cancelled',
    'completed' => 'Completed',
    'refunded' => 'Refunded'
];

if ($order['order_type'] === 'delivery') {
    $status_options['on_the_way'] = 'On the way';
    $status_options['delivered'] = 'Delivered';
    unset($status_options['ready']);
} else {
    unset($status_options['on_the_way']);
    unset($status_options['delivered']);
}

$status_options = array_merge([
    'pending' => 'Pending',
    'confirmed' => 'Confirmed'
], $status_options);

$conn->close();

$order_date = date('M j, Y g:i A', strtotime($order['order_date']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - CABS KOREAN</title>
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
            line-height: 1.5;
            padding: 10px;
        }
        
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        
        ::-webkit-scrollbar-thumb {
            background-color: var(--primary);
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(255, 126, 179, 0.1);
            border-radius: 3px;
        }
        
        .order-details-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            max-height: 65vh;
            overflow-y: auto;
            padding-right: 10px;
            scrollbar-width: thin;
            scrollbar-color: var(--primary) rgba(145, 255, 126, 0.1);
        }
        
        @media (max-width: 768px) {
            .order-details-container {
                grid-template-columns: 1fr;
            }
        }
        
        .order-section {
            margin-bottom: 1rem;
            background: rgba(40, 40, 40, 0.6);
            padding: 1.2rem;
            border-radius: 6px;
            border-left: 3px solid var(--primary);
        }
        
        .order-section-items {
            margin-bottom: 1rem;
            height: 100%;
            background: rgba(40, 40, 40, 0.6);
            padding: 1.2rem;
            border-radius: 6px;
            border-left: 3px solid var(--primary);
        }

        .section-title {
            font-size: 1.1rem;
            color: var(--primary);
            margin-bottom: 0.8rem;
            padding-bottom: 0.4rem;
            border-bottom: 1px solid rgba(143, 255, 126, 0.2);
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 0.6rem;
            padding: 0.4rem 0;
            border-bottom: 1px dashed rgba(135, 255, 126, 0.1);
            font-size: 0.9rem;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 500;
            color: #aaa;
            min-width: 100px;
            flex-shrink: 0;
        }
        
        .detail-value {
            flex: 1;
            word-break: break-word;
        }
        
        .items-table-container {
            max-height: 250px;
            overflow-y: auto;
            margin: 1rem 0;
            scrollbar-width: thin;
            scrollbar-color: var(--primary) rgba(137, 255, 126, 0.1);
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        
        .items-table th {
            position: sticky;
            top: 0;
            background: rgba(137, 255, 126, 0.2);
            color: var(--primary);
            padding: 0.6rem;
            text-align: left;
            font-weight: 600;
        }
        
        .items-table td {
            padding: 0.6rem;
            border-bottom: 1px solid rgba(135, 255, 126, 0.1);
        }
        
        .items-table tr:last-child td {
            border-bottom: none;
        }
        
        .special-instructions {
            font-size: 0.8rem;
            color: #aaa;
            margin-top: 0.2rem;
            font-style: italic;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .status-form {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(160, 255, 126, 0.2);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
            align-items: center;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            color: var(--light);
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .form-control {
            width: 50%;
            padding: 0.6rem;
            border-radius: 4px;
            border: 1px solid var(--gray);
            background: rgba(50, 50, 50, 0.8);
            color: var(--light);
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(160, 255, 126, 0.3);
        }
        
        textarea.form-control {
            min-height: 80px;
            resize: vertical;
        }
        
        .submit-btn {
            padding: 0.7rem 1.2rem;
            border-radius: 4px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(132, 255, 126, 0.3);
        }

        .submit-btn-order {
            padding: 0.7rem 1.2rem;
            border-radius: 4px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1.7rem;
            height: 40px;
            width: 160px;
            font-size: 0.9rem;
            margin-left: -18rem;
        }
        
        .submit-btn-order:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(152, 255, 126, 0.3);
        }
    </style>
</head>
<body>
    <div class="order-details-container">
        <div>
            <div class="order-section">
                <h3 class="section-title">Customer Information</h3>
                <div class="detail-row">
                    <div class="detail-label">Name:</div>
                    <div class="detail-value"><?= htmlspecialchars($order['customer_name'] ?? 'Guest') ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Email:</div>
                    <div class="detail-value"><?= htmlspecialchars($order['customer_email'] ?? $order['email']) ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Phone:</div>
                    <div class="detail-value"><?= htmlspecialchars($order['customer_phone'] ?? $order['phone']) ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Address:</div>
                    <div class="detail-value"><?= htmlspecialchars($order['delivery_address'] ?? 'Pickup') ?></div>
                </div>
            </div>

            <div class="order-section">
                <h3 class="section-title">Order Information</h3>
                <div class="detail-row">
                    <div class="detail-label">Order #:</div>
                    <div class="detail-value"><?= $order['order_number'] ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Date:</div>
                    <div class="detail-value"><?= $order_date ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Type:</div>
                    <div class="detail-value"><?= ucfirst($order['order_type']) ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Payment:</div>
                    <div class="detail-value">
                        <?= ucfirst($order['payment_method']) ?> (<?= ucfirst($order['payment_status']) ?>)
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Total:</div>
                    <div class="detail-value">₱<?= number_format($order['total_price'], 2) ?></div>
                </div>
            </div>
        </div>

        <div>
            <div class="order-section-items">
                <h3 class="section-title">Order Items</h3>
                <div class="items-table-container">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['items'] as $item): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($item['item_name']) ?>
                                        <?php if (!empty($item['special_instructions'])): ?>
                                            <div class="special-instructions">
                                                <i class="fas fa-info-circle"></i> <?= htmlspecialchars($item['special_instructions']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>₱<?= number_format($item['unit_price'], 2) ?></td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td>₱<?= number_format($item['unit_price'] * $item['quantity'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <form class="status-form" method="POST" action="employee_orders.php">
        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
        <div class="form-row">
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" class="form-control" required>
                    <?php foreach ($status_options as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $order['status'] == $value ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
                <button type="submit" name="update_status" class="submit-btn-order">
                    <i class="fas fa-save"></i> Update Status
                </button>   
        </div>
    </form>
</body>
</html>