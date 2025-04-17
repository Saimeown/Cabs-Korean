<?php
include 'db_connect.php';

if (isset($_GET['id'])) {
    $categoryId = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM menu_categories WHERE id = ?");
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Category not found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Category ID required']);
}
?>