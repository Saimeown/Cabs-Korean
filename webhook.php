<?php
include 'db_connect.php';
require 'vendor/autoload.php';

$payload = file_get_contents('php://input');
$event = json_decode($payload, true);

if ($event['type'] === 'checkout_session.payment.paid') {
    $session_id = $event['data']['attributes']['data']['id'];
    $order_id = $event['data']['attributes']['data']['attributes']['metadata']['order_id'];
    
    $stmt = $conn->prepare("UPDATE orders SET status = 'paid', payment_status = 'paid' WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    
    http_response_code(200);
    exit();
}
?>