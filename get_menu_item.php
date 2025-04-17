<?php
include 'db_connect.php';
include 'audit_logger.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if (isset($_GET['id'])) {
    $itemId = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM menu_items WHERE id = ?");
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode($item);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Item not found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Item ID required']);
}

$stmt->close();
$conn->close();
?>