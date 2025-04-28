<?php
session_start();
include 'audit_logger.php';
require 'vendor/autoload.php';

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cabs_korean";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$reservation_id = isset($_GET['reservation_id']) ? (int)$_GET['reservation_id'] : 0;

if ($reservation_id <= 0) {
    die("Invalid reservation ID.");
}

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to pay for a reservation.");
}

$user_id = $_SESSION['user_id'];

$reservation_sql = "SELECT r.*, u.fullname, u.email, u.phone 
                    FROM reservations r
                    JOIN users u ON r.user_id = u.id
                    WHERE r.id = ? AND r.user_id = ?";
$reservation_stmt = $conn->prepare($reservation_sql);
$reservation_stmt->bind_param("ii", $reservation_id, $user_id);
$reservation_stmt->execute();
$reservation_result = $reservation_stmt->get_result();

if ($reservation_result->num_rows === 0) {
    die("Reservation not found or unauthorized access.");
}

$reservation = $reservation_result->fetch_assoc();
$reservation_stmt->close();

if ($reservation['payment_status'] === 'paid') {
    die("This reservation has already been paid.");
}

if ($reservation['status'] !== 'confirmed') {
    die("This reservation is not confirmed for payment.");
}

$guests = (int)$reservation['guests'];

if ($guests <= 2) {
    $amount = 100.00; 
} elseif ($guests <= 6) {
    $amount = 175.00; 
} else {
    $amount = 250.00; 
}

$update_sql = "UPDATE reservations SET amount_paid = ?, payment_status = 'waiting_payment' WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("di", $amount, $reservation_id);
$update_stmt->execute();
$update_stmt->close();

$line_items = [
    [
        'amount' => (int)($amount * 100), 
        'currency' => 'PHP',
        'name' => 'Reservation Deposit for ' . $reservation['guests'] . ' guests',
        'quantity' => 1
    ]
];

$paymongo_secret = 'sk_test_RLTxKX1MFbsCUdPc1YD4s5jp';
$auth = base64_encode($paymongo_secret . ':');

$data = [
    'data' => [
        'attributes' => [
            'line_items' => $line_items,
            'payment_method_types' => ['gcash'],
            'success_url' => 'http://localhost/cabs-korean/reservation_success.php?reservation_id='.$reservation_id,
            'cancel_url' => 'http://localhost/cabs-korean/reservation_cancel.php?reservation_id='.$reservation_id,
            'description' => 'Reservation #'.$reservation_id,
            'statement_descriptor' => 'CABS RESERVATION',
            'metadata' => [
                'reservation_id' => $reservation_id,
                'customer_name' => $reservation['name'],
                'customer_email' => $reservation['email']
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
    
    $update_sql = "UPDATE reservations SET payment_intent_id = ?, payment_link = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssi", $checkout_id, $checkout_url, $reservation_id);
    $update_stmt->execute();
    $update_stmt->close();

    log_audit_action('reservation_payment_initiated', 'reservations', $reservation_id, null, [
        'checkout_id' => $checkout_id,
        'amount' => $amount,
        'guests' => $guests
    ]);

    $_SESSION['current_reservation_id'] = $reservation_id;
    header("Location: " . $checkout_url);
    exit();
} else {
    $error = json_decode($response, true);
    error_log("PayMongo API Error: HTTP $httpCode - " . print_r($error, true));
    
    $update_sql = "UPDATE reservations SET payment_status = 'failed' WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $reservation_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    $errorMessage = "Payment processing error. Please try again later.";
    if (isset($error['errors'][0]['detail'])) {
        $errorMessage .= " Reason: " . $error['errors'][0]['detail'];
    }
    die($errorMessage);
}
?>