<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['order_id'])) {
    die("No order specified");
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Verify the order belongs to the user
$stmt = $conn->prepare("SELECT o.*, u.fullname, u.email, u.phone 
                       FROM orders o 
                       JOIN users u ON o.user_id = u.id 
                       WHERE o.id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    die("Order not found or access denied");
}

// Get order items
$items_stmt = $conn->prepare("SELECT oi.*, mi.name as item_name, mi.price as item_price
                             FROM order_items oi
                             JOIN menu_items mi ON oi.menu_item_id = mi.id
                             WHERE oi.order_id = ?");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$items = $items_result->fetch_all(MYSQLI_ASSOC);

// Generate PDF receipt
require_once('tcpdf/tcpdf.php');

// Create new PDF document with custom page size (80mm width, auto height)
$pdf = new TCPDF('P', 'mm', array(80, 297), true, 'UTF-8', false); // 297mm is A4 height

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('CABS KOREAN');
$pdf->SetTitle('Order Receipt #' . $order['order_number']);
$pdf->SetSubject('Order Receipt');
$pdf->SetKeywords('Receipt, Order, CABS');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins (left, top, right)
$pdf->SetMargins(5, 5, 5);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 8);

// Add logo at the top center
$logo = 'images/cabs.jpg';
if (file_exists($logo)) {
    $pdf->Image($logo, 15, 5, 50, 0, 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
    $pdf->Ln(20); // Space after logo
}

// Restaurant information (centered)
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 5, 'CABS KOREAN RESTAURANT', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 4, '123 Korean Street, Food City', 0, 1, 'C');
$pdf->Cell(0, 4, 'Phone: (123) 456-7890', 0, 1, 'C');
$pdf->Cell(0, 4, 'www.cabskorean.com', 0, 1, 'C');
$pdf->Ln(5);

// Receipt title
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 5, 'ORDER RECEIPT', 0, 1, 'C');
$pdf->Ln(3);

// Divider line
$pdf->SetLineWidth(0.5);
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(5);

// Order details (left-aligned)
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(25, 5, 'Order #:', 0, 0);
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(0, 5, $order['order_number'], 0, 1);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(25, 5, 'Date:', 0, 0);
$pdf->Cell(0, 5, date('M j, Y g:i A', strtotime($order['order_date'])), 0, 1);
$pdf->Cell(25, 5, 'Customer:', 0, 0);
$pdf->Cell(0, 5, $order['fullname'], 0, 1);
$pdf->Cell(25, 5, 'Order Type:', 0, 0);
$pdf->Cell(0, 5, ucfirst($order['order_type']), 0, 1);
$pdf->Ln(5);

// Items table header
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(35, 5, 'ITEM', 0, 0);
$pdf->Cell(10, 5, 'QTY', 0, 0, 'C');
$pdf->Cell(15, 5, 'PRICE', 0, 0, 'R');
$pdf->Cell(15, 5, 'TOTAL', 0, 1, 'R');
$pdf->SetLineWidth(0.2);
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(2);

// Items table rows
$pdf->SetFont('helvetica', '', 8);
foreach ($items as $item) {
    $price = $item['item_price'] ?? 0;
    $quantity = $item['quantity'] ?? 1;
    $total = $price * $quantity;
    
    // Item name (with word wrap)
    $pdf->MultiCell(35, 5, $item['item_name'], 0, 'L', false, 0);
    $pdf->Cell(10, 5, $quantity, 0, 0, 'C');
    $pdf->Cell(15, 5, number_format($price, 2), 0, 0, 'R');
    $pdf->Cell(15, 5, number_format($total, 2), 0, 1, 'R');
    
    // Add small space between items
    $pdf->Ln(1);
}

// Divider line
$pdf->SetLineWidth(0.5);
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(3);

// Order totals
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(50, 5, 'Subtotal:', 0, 0, 'R');
$pdf->Cell(20, 5, number_format($order['total_price'], 2), 0, 1, 'R');

// Add any additional charges here if needed
// $pdf->Cell(50, 5, 'Delivery Fee:', 0, 0, 'R');
// $pdf->Cell(20, 5, '₱' . number_format($order['delivery_fee'], 2), 0, 1, 'R');

$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(50, 6, 'TOTAL:', 0, 0, 'R');
$pdf->Cell(20, 6, number_format($order['total_price'], 2), 0, 1, 'R');
$pdf->Ln(5);

// Payment method
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(25, 5, 'Payment Method:', 0, 0);
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(0, 5, ucfirst($order['payment_method'] ?? 'Cash'), 0, 1);
$pdf->Ln(8);

// Thank you message
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 5, 'Thank you for dining with us!', 0, 1, 'C');
$pdf->Cell(0, 5, 'Please visit us again', 0, 1, 'C');
$pdf->Ln(5);

// Footer note
$pdf->SetFont('helvetica', '', 6);
$pdf->Cell(0, 4, 'This receipt is computer generated and does not', 0, 1, 'C');
$pdf->Cell(0, 4, 'require a signature for validation', 0, 1, 'C');

// Output the PDF
$pdf->Output('receipt_order_' . $order['order_number'] . '.pdf', 'D');