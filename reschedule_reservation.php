<?php
session_start();
include 'db_connect.php';

$reservationId = $_POST['reservation_id'];
$newDate = $_POST['new_date'];
$newTime = $_POST['new_time'];

$query = "UPDATE reservations 
          SET reservation_date = ?, reservation_time = ?, status = 'confirmed'
          WHERE id = ? AND user_id = ? AND status = 'cancelled' AND payment_status = 'paid'";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssii", $newDate, $newTime, $reservationId, $_SESSION['user_id']);
$success = $stmt->execute();
$stmt->close();

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Reservation rescheduled']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to reschedule reservation']);
}

$conn->close();
?>