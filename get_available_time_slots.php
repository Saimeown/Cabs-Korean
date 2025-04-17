<?php
include 'db_connect.php';

$date = $_POST['date'];
$tableNumber = $_POST['table_number'];

$timeSlots = [
    '10:00:00', '10:30:00', '11:00:00', 
    '11:30:00', '12:00:00', '12:30:00', '13:00:00',
    '13:30:00', '14:00:00', '14:30:00', '15:00:00',
    '15:30:00', '16:00:00', '16:30:00', '17:00:00',
    '17:30:00', '18:00:00', '18:30:00', '19:00:00',
    '19:30:00', '20:00:00', '20:30:00', '21:00:00'
];

$query = "SELECT reservation_time FROM reservations 
          WHERE table_number = ? 
          AND reservation_date = ? 
          AND status NOT IN ('cancelled', 'completed')";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $tableNumber, $date);
$stmt->execute();
$result = $stmt->get_result();
$bookedTimes = [];
while ($row = $result->fetch_assoc()) {
    $bookedTimes[] = $row['reservation_time'];
}
$stmt->close();

foreach ($timeSlots as $time) {
    $formattedTime = date("g:i A", strtotime($time));
    $isAvailable = !in_array($time, $bookedTimes);
    
    echo '<div class="time-slot ' . ($isAvailable ? '' : 'unavailable') . '" 
          data-time="' . $time . '">' . $formattedTime . '</div>';
}

$conn->close();
?>