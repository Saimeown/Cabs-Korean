<?php
session_start();
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cabs_korean";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$reservation_id = $_GET['reservation_id'];

$update_sql = "UPDATE reservations 
              SET payment_status = 'failed' 
              WHERE id = ? AND payment_status = 'pending'";
$stmt = $conn->prepare($update_sql);
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Cancelled</title>
</head>
<body>
    <div class="container">
        <h1>Payment Cancelled</h1>
        <p>Your payment was not completed. You can try again if you wish.</p>
        <a href="reservation.php">Return to Reservations</a>
    </div>
</body>
</html>