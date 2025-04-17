<?php
include 'audit_logger.php';
session_start();
require 'vendor/autoload.php';

use Paymongo\Phaymongo\Paymongo;

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cabs_korean";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$order_number = 'ORD-' . strtoupper(uniqid());
$order_type = $_POST['order_type'];
$status = 'pending';
$payment_status = 'pending';

$subtotal = 0;
foreach ($_POST['items'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$user_sql = "SELECT fullname, email, phone, address, city FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

if ($order_type === 'delivery') {
    if (empty($user['address'])) {
        die("Delivery address is required for delivery orders. Please update your profile with your address.");
    }
    if (empty($user['city'])) {
        die("City information is required for delivery orders. Please update your profile.");
    }
}

$delivery_fee = 0.00;
if ($order_type === 'delivery') {
    $fee_sql = "SELECT base_fee FROM delivery_fees WHERE city = ?";
    $fee_stmt = $conn->prepare($fee_sql);
    $fee_stmt->bind_param("s", $user['city']);
    $fee_stmt->execute();
    $fee_result = $fee_stmt->get_result();
    
    if ($fee_result->num_rows > 0) {
        $fee = $fee_result->fetch_assoc();
        $delivery_fee = $fee['base_fee'];
    } else {
        $delivery_fee = 50.00;
    }
    $fee_stmt->close();
}

$total_price = $subtotal + $delivery_fee;
$payment_method = 'card';

$order_sql = "INSERT INTO orders (
    user_id, 
    order_number, 
    status, 
    email, 
    phone, 
    payment_status, 
    subtotal,
    total_price, 
    payment_method, 
    order_type, 
    delivery_fee,
    delivery_address
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param(
    "isssssddssds", 
    $user_id,
    $order_number,
    $status,
    $user['email'],
    $user['phone'],
    $payment_status,
    $subtotal,
    $total_price,
    $payment_method,
    $order_type,
    $delivery_fee,
    $user['address']
);

$order_stmt->execute();
$order_id = $order_stmt->insert_id;
$order_stmt->close();

log_audit_action('order_created', 'orders', $order_id, null, [
    'order_number' => $order_number,
    'status' => $status,
    'total_price' => $total_price,
    'order_type' => $order_type
]);

foreach ($_POST['items'] as $item) {
    $item_sql = "INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price, special_instructions) 
                 VALUES (?, ?, ?, ?, ?)";
    $item_stmt = $conn->prepare($item_sql);
    $special_instructions = $item['special_instructions'] ?? null;
    $item_stmt->bind_param("iiids", $order_id, $item['id'], $item['quantity'], $item['price'], $special_instructions);
    $item_stmt->execute();
    $item_stmt->close();
}

$line_items = [];
foreach ($_POST['items'] as $item) {
    $line_items[] = [
        'amount' => (int)($item['price'] * 100), 
        'currency' => 'PHP',
        'name' => $item['name'],
        'quantity' => (int)$item['quantity']
    ];
}

if ($delivery_fee > 0) {
    $line_items[] = [
        'amount' => (int)($delivery_fee * 100),
        'currency' => 'PHP',
        'name' => 'Delivery Fee',
        'quantity' => 1
    ];
}

$paymongo_secret = 'sk_test_RLTxKX1MFbsCUdPc1YD4s5jp';
$auth = base64_encode($paymongo_secret . ':');

$data = [
    'data' => [
        'attributes' => [
            'line_items' => $line_items,
            'payment_method_types' => ['card', 'gcash', 'grab_pay'],
            'success_url' => 'http://localhost/CABS/success.php?order_id='.$order_id,
            'cancel_url' => 'http://localhost/CABS/cancel.php?order_id='.$order_id,
            'description' => 'Order #'.$order_number,
            'statement_descriptor' => 'CABS KOREAN',
            'metadata' => [
                'order_id' => $order_id,
                'customer_name' => $user['fullname'],
                'customer_email' => $user['email']
            ]
        ]
    ]
];

$ch = curl_init('https://api.paymongo.com/v1/checkout_sessions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Basic ' . $auth
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $responseData = json_decode($response, true);
    $checkout_id = $responseData['data']['id'];
    $checkout_url = $responseData['data']['attributes']['checkout_url'];
    
    $update_sql = "UPDATE orders SET payment_intent_id = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $checkout_id, $order_id);
    $update_stmt->execute();
    $update_stmt->close();

    $_SESSION['current_order_id'] = $order_id;
    header("Location: " . $checkout_url);
    exit();
} else {
    $error = json_decode($response, true);
    error_log("PayMongo API Error: HTTP $httpCode - " . print_r($error, true));
    
    $errorMessage = "Payment processing error. Please try again later.";
    if (isset($error['errors'][0]['detail'])) {
        $errorMessage .= " Reason: " . $error['errors'][0]['detail'];
    }
    die($errorMessage);
}
?>