<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';
include 'audit_logger.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_category'])) {
        $name = $conn->real_escape_string($_POST['name']);
        $description = $conn->real_escape_string($_POST['description']);
        $display_order = (int)$_POST['display_order'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        $stmt = $conn->prepare("INSERT INTO menu_categories (name, description, display_order, is_active) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $name, $description, $display_order, $is_active);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Category added successfully";
        } else {
            $_SESSION['error'] = "Error adding category: " . $conn->error;
        }
        $stmt->close();
        header("Location: admin_menu.php");
        exit();
    }

    if (isset($_POST['add_item'])) {
        $name = $conn->real_escape_string($_POST['item_name']);
        $description = $conn->real_escape_string($_POST['item_description']);
        $price = (float)$_POST['item_price'];
        $category_id = (int)$_POST['item_category'];
        $is_available = isset($_POST['item_available']) ? 1 : 0;
        $is_featured = isset($_POST['item_featured']) ? 1 : 0;
        $image_url = 'images/default-food.jpg';

        if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/menu_items/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = uniqid() . '_' . basename($_FILES['item_image']['name']);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['item_image']['tmp_name'], $targetPath)) {
                $image_url = $targetPath;
            }
        }

        $stmt = $conn->prepare("INSERT INTO menu_items (name, description, price, image_url, category_id, is_available, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsiii", $name, $description, $price, $image_url, $category_id, $is_available, $is_featured);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Menu item added successfully";
        } else {
            $_SESSION['error'] = "Error adding menu item: " . $conn->error;
        }
        $stmt->close();
        header("Location: admin_menu.php");
        exit();
    }

    if (isset($_POST['edit_item'])) {
        $item_id = (int)$_POST['item_id'];
        $name = $conn->real_escape_string($_POST['edit_name']);
        $description = $conn->real_escape_string($_POST['edit_description']);
        $price = (float)$_POST['edit_price'];
        $category_id = (int)$_POST['edit_category'];
        $is_available = isset($_POST['edit_available']) ? 1 : 0;
        $is_featured = isset($_POST['edit_featured']) ? 1 : 0;
        
        $stmt = $conn->prepare("SELECT image_url FROM menu_items WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_image = $result->fetch_assoc()['image_url'];
        $stmt->close();
        
        $image_url = $current_image;
        
        if (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] == UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/menu_items/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = uniqid() . '_' . basename($_FILES['edit_image']['name']);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['edit_image']['tmp_name'], $targetPath)) {
                if ($image_url !== 'images/default-food.jpg' && file_exists($image_url)) {
                    unlink($image_url);
                }
                $image_url = $targetPath;
            }
        }

        $stmt = $conn->prepare("UPDATE menu_items SET name=?, description=?, price=?, image_url=?, category_id=?, is_available=?, is_featured=? WHERE id=?");
        $stmt->bind_param("ssdsiiii", $name, $description, $price, $image_url, $category_id, $is_available, $is_featured, $item_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Menu item updated successfully";
        } else {
            $_SESSION['error'] = "Error updating menu item: " . $conn->error;
        }
        $stmt->close();
        header("Location: admin_menu.php");
        exit();
    }

    if (isset($_POST['delete_item'])) {
        $item_id = (int)$_POST['item_id'];
        
        $stmt = $conn->prepare("SELECT * FROM menu_items WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        
        if ($stmt->execute()) {
            if ($item['image_url'] !== 'images/default-food.jpg' && file_exists($item['image_url'])) {
                unlink($item['image_url']);
            }
            $_SESSION['success'] = "Menu item deleted successfully";
        } else {
            $_SESSION['error'] = "Error deleting menu item: " . $conn->error;
        }
        $stmt->close();
        header("Location: admin_menu.php");
        exit();
    }

    if (isset($_POST['edit_category'])) {
        $category_id = (int)$_POST['category_id'];
        $name = $conn->real_escape_string($_POST['edit_category_name']);
        $description = $conn->real_escape_string($_POST['edit_category_description']);
        $display_order = (int)$_POST['edit_category_order'];
        $is_active = isset($_POST['edit_category_active']) ? 1 : 0;

        $stmt = $conn->prepare("UPDATE menu_categories SET name=?, description=?, display_order=?, is_active=? WHERE id=?");
        $stmt->bind_param("ssiii", $name, $description, $display_order, $is_active, $category_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Category updated successfully";
        } else {
            $_SESSION['error'] = "Error updating category: " . $conn->error;
        }
        $stmt->close();
        header("Location: admin_menu.php");
        exit();
    }

    if (isset($_POST['delete_category'])) {
        $category_id = (int)$_POST['delete_category'];
        
        $stmt = $conn->prepare("SELECT * FROM menu_categories WHERE id = ?");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $category = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $stmt = $conn->prepare("DELETE FROM menu_categories WHERE id = ?");
        $stmt->bind_param("i", $category_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Category deleted successfully";
        } else {
            $_SESSION['error'] = "Error deleting category: " . $conn->error;
        }
        $stmt->close();
        header("Location: admin_menu.php");
        exit();
    }
}

if (isset($_GET['generate_qr'])) {
    $menuUrl = urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/cabs-korean/menu_landing.php");
    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=$menuUrl";
    
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="cabs_menu_qr.png"');
    readfile($qrUrl);
    exit();
}

$categories = [];
$stmt = $conn->prepare("SELECT * FROM menu_categories ORDER BY display_order");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}
$stmt->close();

$menu_items = [];
$stmt = $conn->prepare("SELECT m.*, c.name as category_name FROM menu_items m JOIN menu_categories c ON m.category_id = c.id ORDER BY m.category_id, m.name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $menu_items[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management - CABS KOREAN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="icon" href="cabs.png" type="image/png">
    <style>
        :root {
            --primary: #ff7eb3;
            --secondary: #ff0844;
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
            line-height: 1.6;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .admin-sidebar {
            width: 250px;
            background: rgba(30, 30, 30, 0.9);
            padding: 1.5rem;
            position: fixed;
            height: 100vh;
            border-right: 1px solid rgba(255, 126, 179, 0.2);
            backdrop-filter: blur(10px);
        }

        .admin-logo {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 126, 179, 0.2);
        }

        .admin-logo img {
            height: 40px;
            margin-right: 10px;
            border-radius: 50%;
        }

        .admin-logo h2 {
            font-size: 1.5rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        .admin-nav {
            list-style: none;
        }

        .admin-nav li {
            margin-bottom: 0.5rem;
        }

        .admin-nav a {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            color: var(--light);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .admin-nav a:hover, .admin-nav a.active {
            background: rgba(255, 126, 179, 0.1);
            color: var(--primary);
        }

        .admin-nav a i {
            margin-right: 0.8rem;
            width: 20px;
            text-align: center;
        }

        .admin-main {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 126, 179, 0.2);
        }

        .admin-title {
            font-size: 1.8rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        .admin-user {
            display: flex;
            align-items: center;
        }

        .admin-user img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 0.8rem;
        }

        .admin-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .admin-card {
            background: linear-gradient(135deg, rgba(30, 30, 30, 0.86) 0%, rgba(40, 40, 40, 0.7) 100%);
            border-radius: 10px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 126, 179, 0.2);
            margin-bottom: 2rem;
        }

        .card-header {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(255, 126, 179, 0.2);
        }

            
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 126, 179, 0.1);
        }

        th {
            background: #402c34;
            color: var(--primary);
            font-weight: 600;
            position: sticky;
            top: 0;
        }

        tr:hover {
            background: rgba(255, 126, 179, 0.05);
        }

        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
        }

            
        .badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            width: 100px;
            text-align: center;
            margin-top: 5px;
        }

        .badge-success {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
        }

        .badge-danger {
            background: rgba(244, 67, 54, 0.2);
            color: #F44336;
        }

        .badge-warning {
            background: rgba(255, 193, 7, 0.2);
            color: #FFC107;
        }

            
        .btn {
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(to right, rgba(167, 86, 120, 0.75), rgba(157, 0, 65, 1));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 126, 179, 0.3);
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.9rem;
        }

        .btn-icon {
            padding: 0.5rem;
            border-radius: 4px;
        }

            
        .btn-qr {
            background: linear-gradient(to right, #4CAF50, #2E7D32);
            color: white;
            text-decoration: none;
        }

        .btn-qr:hover {
            background: linear-gradient(to right, #3e8e41, #1B5E20);
        }

            
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--light);
        }

        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border-radius: 6px;
            border: 1px solid var(--gray);
            background: rgba(50, 50, 50, 0.8);
            color: var(--light);
            font-size: 1rem;
        }

        .form-group textarea {
            min-height: 100px;
        }

            
        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            margin-right: 0.8rem;
            appearance: none;
            -webkit-appearance: none;
            background: rgba(50, 50, 50, 0.8);
            border: 1px solid var(--gray);
            border-radius: 4px;
            cursor: pointer;
            position: relative;
        }

        .form-check-input:checked {
            background: var(--primary);
            border-color: var(--primary);
        }

        .form-check-input:checked::after {
            content: "✓";
            position: absolute;
            color: white;
            font-size: 12px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

            
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: rgba(40, 40, 40, 0.95);
            border-radius: 10px;
            padding: 2rem;
            width: 90%;
            max-width: 600px;
            border: 1px solid var(--primary);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 126, 179, 0.2);
        }

        .modal-title {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .close-modal {
            background: none;
            border: none;
            color: var(--light);
            font-size: 1.5rem;
            cursor: pointer;
        }

            
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border-left: 4px solid #dc3545;
        }

            
        .scrollable-table-container {
            max-height: 500px;     
            overflow-y: auto;
            border: 1px solid rgba(255, 126, 179, 0.2);
            border-radius: 6px;
            margin-top: 1rem;
        }

            
        .scrollable-table-container table {
            position: relative;
        }

        .scrollable-table-container th {
            position: sticky;
            top: 0;
            z-index: 10;
            background: #402c34;
        }

            
        .scrollable-table-container {
            margin-bottom: 1rem;
        }

            
        @media (max-width: 1200px) {
            .admin-content {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }
            
            .admin-main {
                margin-left: 0;
            }
            
            .admin-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
            
        .table-wrapper::-webkit-scrollbar,
        .admin-card::-webkit-scrollbar {
            width: 6px;     
            height: 6px;     
        }

        .table-wrapper::-webkit-scrollbar-track,
        .admin-card::-webkit-scrollbar-track {
            background: rgba(255, 126, 179, 0.05);     
            border-radius: 3px;
        }

        .table-wrapper::-webkit-scrollbar-thumb,
        .admin-card::-webkit-scrollbar-thumb {
            background: rgba(255, 126, 179, 0.5);     
            border-radius: 3px;
            transition: background 0.3s;
        }

        .table-wrapper::-webkit-scrollbar-thumb:hover,
        .admin-card::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 126, 179, 0.7);     
        }

            
        .table-wrapper,
        .admin-card {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 126, 179, 0.5) rgba(255, 126, 179, 0.05);
        }
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            background: transparent;
            color: var(--light);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 126, 179, 0.3);
        }

            
        .btn-primary {
            background: rgba(255, 126, 179, 0.1);
            color: var(--primary);
            border: 1px solid rgba(255, 126, 179, 0.2);
        }

        .btn-primary:hover {
            background: rgba(255, 126, 179, 0.2);
        }

            
        .btn-qr {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
            border: 1px solid rgba(76, 175, 80, 0.2);
        }

        .btn-qr:hover {
            background: rgba(76, 175, 80, 0.2);
        }

            
        .btn-danger {
            background: rgba(244, 67, 54, 0.1);
            color: #F44336;
            border: 1px solid rgba(244, 67, 54, 0.2);
        }

        .btn-danger:hover {
            background: rgba(244, 67, 54, 0.2);
        }

            
        .btn-icon {
            padding: 0.5rem;
            width: 32px;
            height: 32px;
            justify-content: center;
            border-radius: 4px;
        }

            
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }

            
        button[type="submit"] {
            background: rgba(255, 126, 179, 0.15);
            color: var(--primary);
            border: 1px solid rgba(255, 126, 179, 0.3);
        }

        button[type="submit"]:hover {
            background: rgba(255, 126, 179, 0.25);
        }

            
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: rgba(255, 255, 255, 0.05);
        }

            
        .button-group {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

            
        .modal-content .btn {
            margin-top: 1rem;
        }
        .status-pending { background: rgba(255, 193, 7, 0.1); color: #FFC107; border: 1px solid #FFC107; }
        .status-confirmed { background: rgba(76, 175, 80, 0.1); color: #4CAF50; border: 1px solid #4CAF50; }
        .status-paid { background: rgba(33, 150, 243, 0.1); color: #2196F3; border: 1px solid #2196F3; }
        .status-cancelled { background: rgba(244, 67, 54, 0.1); color: #F44336; border: 1px solid #F44336; }
        .status-waiting_payment { background: rgba(255, 165, 0, 0.1); color: #FFA500; border: 1px solid #FFA500; }
        .status-completed { background: rgba(190, 81, 209, 0.1); color:rgb(223, 73, 250); border: 1px solid #9C27B0; }
        .status-declined { background: rgba(158, 158, 158, 0.1); color: #9E9E9E; border: 1px solid #9E9E9E; }
        .status-refunded { background: rgba(121, 85, 72, 0.1); color: #795548; border: 1px solid #795548; }
        .status-failed { background: rgba(96, 125, 139, 0.1); color: #607D8B; border: 1px solid #607D8B; } 
        .status-preparing { background: rgba(255, 152, 0, 0.1); color: #FF9800; border: 1px solid #FF9800; }
        .status-ready { background: rgba(0, 150, 136, 0.1); color: #009688; border: 1px solid #009688; }
        .status-on_the_way { background: rgba(63, 81, 181, 0.1); color: #3F51B5; border: 1px solid #3F51B5; } 
        .status-delivered { background: rgba(139, 195, 74, 0.1); color: #8BC34A; border: 1px solid #8BC34A; }
        .status-no_show { background: rgba(233, 30, 99, 0.1); color: #E91E63; border: 1px solid #E91E63; }
        .badge-success { background: rgba(76, 175, 80, 0.1); color: #4CAF50; border: 1px solid #4CAF50; }
        .badge-warning { background: rgba(255, 193, 7, 0.1); color: #FFC107; border: 1px solid #FFC107; }

            
.admin-sidebar {
    width: 280px;
    background: rgba(25, 25, 25, 0.95);
    padding: 2rem 1.5rem;
    position: fixed;
    height: 100vh;
    border-right: 1px solid rgba(255, 126, 179, 0.2);
    backdrop-filter: blur(10px);
    overflow-y: auto;
    z-index: 100;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.admin-logo {
    display: flex;
    align-items: center;
    padding: 0.5rem 0;
    margin-bottom: 1rem;
    border-bottom: 1px solid rgba(255, 126, 179, 0.2);
}

.admin-logo img {
    height: 45px;
    width: 45px;
    margin-right: 12px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid rgb(255, 126, 180);}

.admin-logo h2 {
    font-size: 1.5rem;
    font-weight: 700;
    background: linear-gradient(to right, var(--primary), var(--secondary));
    background-clip: text;
    -webkit-background-clip: text;
    color: transparent;
    margin: 0;
}

.admin-nav {
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-top: 1rem;
}

.admin-nav li {
    margin: 0;
}

.admin-nav a {
    display: flex;
    align-items: center;
    padding: 0.85rem 1.25rem;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
    font-size: 0.95rem;
    font-weight: 500;
}

.admin-nav a:hover {
    background: rgba(255, 126, 179, 0.15);
    color: var(--primary);
    transform: translateX(5px);
}

.admin-nav a.active {
    background: rgba(255, 126, 179, 0.2);
    color: var(--primary);
    box-shadow: 0 4px 12px rgba(255, 126, 179, 0.1);
}

.admin-nav a i {
    margin-right: 12px;
    width: 20px;
    text-align: center;
    font-size: 1.1rem;
}

    
.admin-nav li:last-child a {
    margin-top: 1.5rem;
    background: rgba(255, 0, 0, 0.1);
    color: #ff6b6b;
    border: 1px solid rgba(255, 0, 0, 0.2);
}

.admin-nav li:last-child a:hover {
    background: rgba(255, 0, 0, 0.2);
    color: #ff5252;
}

    
.admin-sidebar::-webkit-scrollbar {
    width: 6px;
}

.admin-sidebar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
}

.admin-sidebar::-webkit-scrollbar-thumb {
    background: rgba(255, 126, 179, 0.3);
    border-radius: 3px;
}

.admin-sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 126, 179, 0.5);
}

    
@media (max-width: 992px) {
    .admin-sidebar {
        width: 240px;
        padding: 1.5rem 1rem;
    }
}

@media (max-width: 768px) {
    .admin-sidebar {
        width: 100%;
        height: auto;
        position: relative;
        padding: 1rem;
    }
    
    .admin-nav {
        flex-direction: row;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .admin-nav li {
        flex: 1 0 calc(50% - 0.5rem);
    }
    
    .admin-nav a {
        padding: 0.75rem;
        justify-content: center;
    }
    
    .admin-nav a i {
        margin-right: 0;
        margin-bottom: 0.25rem;
        display: block;
    }
    
    .admin-nav a span {
        display: none;
    }
    
    .admin-main {
        margin-left: 0;
    }
}

    
.admin-main {
    flex: 1;
    margin-left: 280px;     
    padding: 2.5rem;     
    min-height: 100vh;
    background-color: var(--dark);
    transition: margin-left 0.3s ease;
}

    
@media (max-width: 992px) {
    .admin-sidebar {
        width: 240px;
        padding: 1.5rem 1rem;
    }
    
    .admin-main {
        margin-left: 240px;     
        padding: 2rem;
    }
}

@media (max-width: 768px) {
    .admin-sidebar {
        width: 100%;
        height: auto;
        position: relative;
        padding: 1rem;
    }
    
    .admin-main {
        margin-left: 0;
        padding: 1.5rem;
    }
}
    
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    overflow-y: auto;     
    padding: 20px;     
}

.modal-content {
    background: rgba(40, 40, 40, 0.95);
    border-radius: 10px;
    padding: 1.5rem;     
    width: 90%;     
    max-width: 500px;     
    border: 1px solid var(--primary);
    max-height: 90vh;     
    overflow-y: auto;     
    box-shadow: 0 5px 20px rgba(0,0,0,0.3);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;     
    padding-bottom: 0.75rem;     
    border-bottom: 1px solid rgba(255, 126, 179, 0.2);
}

.modal-title {
    font-size: 1.3rem;     
    color: var(--primary);
    margin: 0;
}

.close-modal {
    background: none;
    border: none;
    color: var(--light);
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.5rem;
    line-height: 1;
}

    
.modal .form-group {
    margin-bottom: 1rem;     
}

.modal .form-group label {
    font-size: 0.9rem;     
    margin-bottom: 0.3rem;
}

.modal .form-group input,
.modal .form-group select,
.modal .form-group textarea {
    padding: 0.6rem;     
    font-size: 0.9rem;
}

.modal .form-group textarea {
    min-height: 80px;     
}

.modal button[type="submit"] {
    padding: 0.6rem 1rem;     
    font-size: 0.9rem;
    margin-top: 0.5rem;     
}

    
#currentImageContainer {
    margin: 0.5rem 0;
}

#currentImageContainer img {
    max-width: 150px;     
    max-height: 100px;
}

    
@media (max-width: 600px) {
    .modal-content {
        width: 95%;
        padding: 1rem;
    }
    
    .modal-title {
        font-size: 1.1rem;
    }
    
    .modal .form-group {
        margin-bottom: 0.8rem;
    }
}
    
.modal-content::-webkit-scrollbar {
    width: 4px;     
}

.modal-content::-webkit-scrollbar-track {
    background: transparent;     
}

.modal-content::-webkit-scrollbar-thumb {
    background-color: #ff80ab;     
    border-radius: 10px;
}

    
.modal-content::-webkit-scrollbar-thumb:hover {
    background-color: #ff4081;
}

    </style>
</head>
<body>
    <div class="admin-container">
             
        <div class="admin-sidebar">
            <div class="admin-logo">
                <img src="images/cabs.png" alt="CABS KOREAN Logo">
                <h2>CABS ADMIN</h2>
            </div>
            
            <ul class="admin-nav">
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="admin_menu.php" class="active"><i class="fas fa-utensils"></i> Menu</a></li>
                <li><a href="admin_reservations.php"><i class="fas fa-calendar-alt"></i> Reservations</a></li>
                <li><a href="admin_orders.php"><i class="fas fa-receipt"></i> Orders</a></li>
                <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="admin_audit_trail.php"><i class="fas fa-clipboard-list"></i> Logs</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

             
        <div class="admin-main">
            <div class="admin-header">
                <div>
                    <h1 class="admin-title">Menu Management</h1>
                    <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                        <a href="?generate_qr=1" class="btn btn-primary btn-qr">
                            <i class="fas fa-qrcode"></i> Generate QR Code
                        </a>
                        <button class="btn btn-primary" onclick="openModal('addCategoryModal')">
                            <i class="fas fa-plus"></i> Add Category
                        </button>
                        <button class="btn btn-primary" onclick="openModal('addItemModal')">
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                    </div>
                </div>
                <div class="admin-user">
                    <span><?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Admin'); ?></span>
                </div>
            </div>

                 
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="admin-content">
                     
                <div class="admin-card">
                    <h2 class="card-header">Menu Categories</h2>
                    <div class="scrollable-table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Order</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td><?php echo htmlspecialchars($category['description']); ?></td>
                                        <td><?php echo $category['display_order']; ?></td>
                                        <td>
                                            <span class="badge <?php echo $category['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                                <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-primary btn-sm btn-icon" onclick="openEditCategoryModal(<?php echo $category['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="delete_category" value="<?php echo $category['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm btn-icon" onclick="return confirm('Delete this category?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                     
                <div class="admin-card">
                    <h2 class="card-header">Menu Items</h2>
                    <div class="scrollable-table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($menu_items as $item): ?>
                                    <tr>
                                        <td><img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="item-image"></td>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                        <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                        <td>
                                            <span class="badge <?php echo $item['is_available'] ? 'badge-success' : 'badge-danger'; ?>">
                                                <?php echo $item['is_available'] ? 'Available' : 'Unavailable'; ?>
                                            </span>
                                            <?php if ($item['is_featured']): ?>
                                                <span class="badge badge-warning">Featured</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-primary btn-sm btn-icon" onclick="openEditItemModal(<?php echo $item['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="delete_item" value="1">
                                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm btn-icon" onclick="return confirm('Delete this item?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

         
    <div id="addCategoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New Category</h2>
                <button class="close-modal" onclick="closeModal('addCategoryModal')">&times;</button>
            </div>
            <form method="POST" action="admin_menu.php">
                <div class="form-group">
                    <label for="name">Category Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"></textarea>
                </div>
                <div class="form-group">
                    <label for="display_order">Display Order</label>
                    <input type="number" id="display_order" name="display_order" value="0" required>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
                <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
            </form>
        </div>
    </div>

         
    <div id="addItemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New Menu Item</h2>
                <button class="close-modal" onclick="closeModal('addItemModal')">&times;</button>
            </div>
            <form method="POST" action="admin_menu.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="item_name">Item Name</label>
                    <input type="text" id="item_name" name="item_name" required>
                </div>
                <div class="form-group">
                    <label for="item_description">Description</label>
                    <textarea id="item_description" name="item_description" required></textarea>
                </div>
                <div class="form-group">
                    <label for="item_price">Price</label>
                    <input type="number" id="item_price" name="item_price" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="item_category">Category</label>
                    <select id="item_category" name="item_category" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="item_image">Image</label>
                    <input type="file" id="item_image" name="item_image" accept="image/*">
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="item_available" name="item_available" checked>
                    <label class="form-check-label" for="item_available">Available</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="item_featured" name="item_featured">
                    <label class="form-check-label" for="item_featured">Featured</label>
                </div>
                <button type="submit" name="add_item" class="btn btn-primary">Add Menu Item</button>
            </form>
        </div>
    </div>

         
    <div id="editItemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Menu Item</h2>
                <button class="close-modal" onclick="closeModal('editItemModal')">&times;</button>
            </div>
            <form id="editItemForm" method="POST" action="admin_menu.php" enctype="multipart/form-data">
                <input type="hidden" name="edit_item" value="1">
                <input type="hidden" name="item_id" id="edit_item_id">
                <div class="form-group">
                    <label for="edit_name">Item Name</label>
                    <input type="text" id="edit_name" name="edit_name" required>
                </div>
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="edit_description" required></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_price">Price</label>
                    <input type="number" id="edit_price" name="edit_price" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="edit_category">Category</label>
                    <select id="edit_category" name="edit_category" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_image">Change Image</label>
                    <input type="file" id="edit_image" name="edit_image" accept="image/*">
                    <div id="currentImageContainer" style="margin-top: 10px;"></div>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="edit_available" name="edit_available" value="1">
                    <label class="form-check-label" for="edit_available">Available</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="edit_featured" name="edit_featured" value="1">
                    <label class="form-check-label" for="edit_featured">Featured</label>
                </div>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>

         
    <div id="editCategoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Category</h2>
                <button class="close-modal" onclick="closeModal('editCategoryModal')">&times;</button>
            </div>
            <form method="POST" action="admin_menu.php">
                <input type="hidden" name="edit_category" value="1">
                <input type="hidden" name="category_id" id="edit_category_id">
                <div class="form-group">
                    <label for="edit_category_name">Category Name</label>
                    <input type="text" id="edit_category_name" name="edit_category_name" required>
                </div>
                <div class="form-group">
                    <label for="edit_category_description">Description</label>
                    <textarea id="edit_category_description" name="edit_category_description"></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_category_order">Display Order</label>
                    <input type="number" id="edit_category_order" name="edit_category_order" required>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="edit_category_active" name="edit_category_active">
                    <label class="form-check-label" for="edit_category_active">Active</label>
                </div>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Edit Item Modal
        function openEditItemModal(itemId) {
            <?php 
                $items_data = [];
                foreach ($menu_items as $item) {
                    $items_data[$item['id']] = $item;
                }
            ?>
            const items = <?php echo json_encode($items_data); ?>;
            const item = items[itemId];
            
            if (item) {
                document.getElementById('edit_item_id').value = item.id;
                document.getElementById('edit_name').value = item.name;
                document.getElementById('edit_description').value = item.description;
                document.getElementById('edit_price').value = item.price;
                document.getElementById('edit_category').value = item.category_id;
                document.getElementById('edit_available').checked = item.is_available == 1;
                document.getElementById('edit_featured').checked = item.is_featured == 1;
                
                const imageContainer = document.getElementById('currentImageContainer');
                imageContainer.innerHTML = `
                    <p>Current Image:</p>
                    <img src="${item.image_url}" style="max-width: 200px; max-height: 150px; border-radius: 4px;">
                `;
                
                openModal('editItemModal');
            } else {
                alert('Item not found');
            }
        }

        // Edit Category Modal
        function openEditCategoryModal(categoryId) {
            <?php 
                $cats_data = [];
                foreach ($categories as $cat) {
                    $cats_data[$cat['id']] = $cat;
                }
            ?>
            const categories = <?php echo json_encode($cats_data); ?>;
            const category = categories[categoryId];
            
            if (category) {
                document.getElementById('edit_category_id').value = category.id;
                document.getElementById('edit_category_name').value = category.name;
                document.getElementById('edit_category_description').value = category.description;
                document.getElementById('edit_category_order').value = category.display_order;
                document.getElementById('edit_category_active').checked = category.is_active == 1;
                
                openModal('editCategoryModal');
            } else {
                alert('Category not found');
            }
        }
    </script>
</body>
</html>