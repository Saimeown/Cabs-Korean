<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['reservation_id'])) {
    die("No reservation specified");
}

$reservation_id = $_GET['reservation_id'];
$user_id = $_SESSION['user_id'];

// Verify the reservation belongs to the user
$stmt = $conn->prepare("SELECT r.*, u.fullname, u.email, u.phone 
                       FROM reservations r 
                       JOIN users u ON r.user_id = u.id 
                       WHERE r.id = ? AND r.user_id = ?");
$stmt->bind_param("ii", $reservation_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$reservation = $result->fetch_assoc();

if (!$reservation) {
    die("Reservation not found or access denied");
}

// Only allow download for confirmed/paid reservations
if ($reservation['status'] != 'confirmed' && $reservation['status'] != 'paid') {
    die("Receipt only available for confirmed reservations");
}

// Generate PDF receipt
require_once('tcpdf/tcpdf.php');

// Create new PDF document with custom page size (80mm width, common receipt size)
$pdf = new TCPDF('P', 'mm', array(80, 200), true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('CABS KOREAN');
$pdf->SetTitle('Reservation Receipt #' . $reservation['id']);
$pdf->SetSubject('Reservation Receipt');
$pdf->SetKeywords('Receipt, Reservation, CABS');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(5, 5, 5);

// Add a page
$pdf->AddPage();

// Add logo (adjust path as needed)
$logo = 'images/cabs.JPG';
if (file_exists($logo)) {
    $pdf->Image($logo, 15, 5, 50, 0, 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
}

// Header
$pdf->SetY(20);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 5, 'CABS KOREAN', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 5, '123 Restaurant Street, Food City', 0, 1, 'C');
$pdf->Cell(0, 5, 'Phone: (123) 456-7890', 0, 1, 'C');
$pdf->Ln(5);

// Receipt title
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 5, 'RESERVATION RECEIPT', 0, 1, 'C');
$pdf->Ln(3);

// Divider line
$pdf->SetLineWidth(0.5);
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(5);

// Reservation details
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(25, 5, 'Reservation #:', 0, 0);
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(0, 5, $reservation['id'], 0, 1);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(25, 5, 'Date:', 0, 0);
$pdf->Cell(0, 5, date('M j, Y', strtotime($reservation['reservation_date'])) . ' at ' . date('g:i A', strtotime($reservation['reservation_time'])), 0, 1);
$pdf->Cell(25, 5, 'Customer:', 0, 0);
$pdf->Cell(0, 5, $reservation['fullname'], 0, 1);
$pdf->Cell(25, 5, 'Guests:', 0, 0);
$pdf->Cell(0, 5, $reservation['guests'], 0, 1);

if (isset($reservation['table_number'])) {
    $pdf->Cell(25, 5, 'Table:', 0, 0);
    $pdf->Cell(0, 5, $reservation['table_number'], 0, 1);
}

$pdf->Cell(25, 5, 'Status:', 0, 0);
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(0, 5, ucfirst($reservation['status']), 0, 1);
$pdf->Ln(5);

// Payment details if paid
if ($reservation['status'] == 'paid') {
    $pdf->SetLineWidth(0.2);
    $pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
    $pdf->Ln(3);
    
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(25, 5, 'Amount Paid:', 0, 0);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(0, 5, '₱' . number_format($reservation['amount_paid'], 2), 0, 1);
    
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(25, 5, 'Payment Date:', 0, 0);
    $pdf->Cell(0, 5, date('M j, Y g:i A', strtotime($reservation['payment_date'])), 0, 1);
    
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(25, 5, 'Payment Method:', 0, 0);
    $pdf->Cell(0, 5, ucfirst($reservation['payment_method'] ?? 'Cash'), 0, 1);
}

// Divider line
$pdf->SetLineWidth(0.5);
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(8);

// Thank you message
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 5, 'Thank you for your reservation!', 0, 1, 'C');
$pdf->Cell(0, 5, 'We look forward to serving you', 0, 1, 'C');

// Output the PDF
$pdf->Output('receipt_reservation_' . $reservation['id'] . '.pdf', 'D');