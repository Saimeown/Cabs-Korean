<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$query = "SELECT *, 
          CASE 
              WHEN status = 'confirmed' AND payment_status = 'waiting_payment' THEN 'waiting_payment'
              WHEN status = 'cancelled' AND payment_status = 'pending' THEN 'declined'
              ELSE status
          END AS display_status
          FROM reservations 
          WHERE user_id = ?
          ORDER BY reservation_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$reservations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservations | CABS KOREAN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="icon" href="cabs.png" type="image/png">

    <link rel="icon" href="cabs.png" type="image/png">

    <style>
        :root {
            --primary: #ff7eb3;
            --secondary: #ff0844;
            --dark: #121212;
            --light: #e0e0e0;
            --gray: #333333;
            --success: #4CAF50;
            --warning: #FFC107;
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

             
        .navbar {
            background: rgba(18, 18, 18, 0.9);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            width: 100%;
            z-index: 1000;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 126, 179, 0.2);
        }

             
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

             
        .table-container {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow: hidden;
        }

        .reservations-table {
            width: 100%;
            border-collapse: collapse;
        }

        .reservations-table th {
            text-align: left;
            padding: 1rem;
            background: rgba(255, 126, 179, 0.1);
            color: var(--primary);
            font-weight: 600;
        }

        .reservations-table td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .reservations-table tr:last-child td {
            border-bottom: none;
        }

        .scrollable-table {
            max-height: 70vh;
            overflow-y: auto;
        }

             
        .scrollable-table::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .scrollable-table::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }

        .scrollable-table::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 3px;
        }

             
        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            width: 100px;
            text-align: center;
        }

        .status-confirmed {
            background: rgba(76, 175, 80, 0.2);
            color: var(--success);
        }

        .status-waiting_payment {
            background: rgba(255, 165, 0, 0.2);
            color: #FFA500;
        }

        .status-cancelled {
            background: rgba(244, 67, 54, 0.2);
            color: #F44336;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: var(--warning);
        }

        .status-completed {
            background: rgba(101, 144, 254, 0.2);
            color: rgba(131, 170, 255, 0.89)
        }

             
        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: rgba(255, 126, 179, 0.1);
        }

        .btn-disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

             
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: rgba(255, 255, 255, 0.5);
        }

             
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
        }

        .modal-content {
            background-color: var(--dark);
            margin: 10% auto;
            padding: 2rem;
            border: 1px solid var(--primary);
            border-radius: 8px;
            max-width: 500px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-title {
            color: var(--primary);
            font-size: 1.5rem;
        }

        .close {
            color: var(--primary);
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: var(--secondary);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--light);
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 0.75rem;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 4px;
            color: var(--light);
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .modal-footer {
            margin-top: 1.5rem;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .btn-danger {
            background: #f44336;
            color: white;
        }

        .btn-danger:hover {
            background: #d32f2f;
        }

        .time-slots {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .time-slot {
            padding: 0.5rem;
            text-align: center;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .time-slot:hover {
            background: rgba(255,126,179,0.2);
        }

        .time-slot.selected {
            background: var(--primary);
            color: white;
        }

        .time-slot.unavailable {
            background: rgba(255,255,255,0.05);
            color: rgba(255,255,255,0.3);
            cursor: not-allowed;
        }

             
        @media (max-width: 768px) {
            .reservations-table {
                display: block;
            }
            
            .reservations-table thead {
                display: none;
            }
            
            .reservations-table tr {
                display: block;
                padding: 1rem;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .reservations-table td {
                display: flex;
                justify-content: space-between;
                padding: 0.5rem 0;
                border-bottom: none;
            }
            
            .reservations-table td::before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--primary);
                margin-right: 1rem;
            }

            .modal-content {
                margin: 20% auto;
                width: 90%;
            }

            .time-slots {
                grid-template-columns: repeat(2, 1fr);
            }
        }
             
        .navbar__container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        #navbar__logo {
            display: flex;
            align-items: center;
            font-size: 1.8rem;
            font-weight: 700;
            text-decoration: none;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        #navbar__logo img {
            height: 40px;
            margin-right: 10px;
            border-radius: 50%;
        }

        .navbar__menu {
            display: flex;
            list-style: none;
            align-items: center;
        }

        .navbar__item {
            margin: 0 1rem;
        }

        .navbar__links {
            color: var(--light);
            text-decoration: none;
            font-size: 1.1rem;
            transition: color 0.3s;
            position: relative;
        }

        .navbar__links:hover {
            color: var(--primary);
        }

        .button {
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            margin-left: 0.8rem;
        }

        .logout-btn {
        background: rgba(255, 57, 133, 0.2);
        border: 1px solid rgba(255, 0, 111, 0.5);
        color:rgb(255, 107, 166);
        }

        .logout-btn:hover {
        background: rgba(255, 0, 140, 0.3);
        }

        .floating-element {
            position: absolute;
            opacity: 0.1;
            z-index: 0;
        }

        .floating-element:nth-child(1) {
            width: 300px;
            height: 300px;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            top: 10%;
            left: 5%;
            animation: float 8s ease-in-out infinite;
        }

        .floating-element:nth-child(2) {
            width: 200px;
            height: 200px;
            background: linear-gradient(45deg, var(--secondary), var(--primary));
            border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
            bottom: 15%;
            right: 10%;
            animation: float 10s ease-in-out infinite reverse;
        }

        @keyframes float {
            0% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
            100% { transform: translateY(0) rotate(0deg); }
        }
        .status-declined {
            background: rgba(169, 169, 169, 0.2);      
            color: #A9A9A9;      
        }
             
        .navbar {
        background: rgba(18, 18, 18, 0);
        padding: 1rem 2rem;
        position: sticky;
        top: 0;
        width: 100%;
        z-index: 1000;
        backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(255, 126, 179, 0.2);
        }

        .navbar__container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 1200px;
        margin: 0 auto;
        width: 100%;
        margin-left: 10rem;
        }

        #navbar__logo {
        display: flex;
        align-items: center;
        font-size: 1.8rem;
        font-weight: 700;
        text-decoration: none;
        background: linear-gradient(to right, var(--primary), var(--secondary));
        background-clip: text;
        -webkit-background-clip: text;
        color: transparent;
        }

        #navbar__logo img {
        height: 70px;
        margin-right: 10px;
        border-radius: 50%;
        }

        .navbar__menu {
        display: flex;
        list-style: none;
        align-items: center;
        margin-right: -21.5rem;
        }

        .navbar__item {
        margin: 0 1rem;
        }

        .navbar__links {
        color: var(--light);
        text-decoration: none;
        font-size: 1.1rem;
        transition: color 0.3s;
        position: relative;
        }

        .navbar__links:hover {
        color: var(--primary);
        }

        .button {
        padding: 0.6rem 1.2rem;
        border-radius: 6px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;
        margin-left: 0.8rem;
        }

        .logout-btn {
        background: rgba(255, 0, 0, 0.2);
        border: 1px solid rgba(255, 0, 0, 0.5);
        color: #ff6b6b;
        }

        .logout-btn:hover {
        background: rgba(255, 0, 0, 0.3);
        }

             
        :root {
        --primary: #ff7eb3;
        --secondary: #ff0844;
        --dark: #121212;
        --light: #e0e0e0;
        --gray: #333333;
        --success: #4CAF50;
        --warning: #FFC107;
        --info: #2196F3;
        }
        #navbar__logo:hover {
        transform: scale(1.05);
        }

        #navbar__logo img {
        margin-right: 10px;
        border-radius: 50%;
        transition: all 0.3s;
        }

        #navbar__logo:hover img {
        transform: rotate(15deg);
        }
        .btn-outline {
            width: 89px;
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
    </style>
</head>
<body>
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    <nav class="navbar">
        <div class="navbar__container">
            <a href="index.php" id="navbar__logo">
                <img src="images/cabs.png" alt="CABS KOREAN Logo">CABS
            </a>
            
            <ul class="navbar__menu">
                <li class="navbar__item">
                    <a href="menu.php" class="navbar__links">Menu</a>
                </li>
                <li class="navbar__item">
                    <a href="order.php" class="navbar__links">Order Now</a>
                </li>
                <li class="navbar__item">
                    <a href="reservation.php" class="navbar__links">Reservation</a>
                </li>
                <li class="navbar__item">
                    <a href="update_user.php" class="navbar__links">Profile</a>
                </li>
                <li class="navbar__btn">
                    <?php if ($isLoggedIn): ?>
                        <a href="logout.php" class="button logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="button">Login</a>
                        <a href="register.php" class="button">Register</a>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">My Reservations</h1>
            <a href="reservation.php" class="action-btn btn-primary">
                <i class="fas fa-plus"></i> New Reservation
            </a>
        </div>

        <div class="table-container">
            <div class="scrollable-table">
                <table class="reservations-table">
                    <thead>
                        <tr>
                            <th>Reservation #</th>
                            <th>Date & Time</th>
                            <th>Guests</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($reservations)): ?>
                            <?php foreach ($reservations as $reservation): 
                                $status = $reservation['display_status'] ?? $reservation['status'];
                                $statusClass = 'status-' . strtolower(str_replace(' ', '_', $status));
                            ?>
                                <tr>
                                    <td data-label="Reservation #"><?= $reservation['id'] ?></td>
                                    <td data-label="Date & Time">
                                        <?= date('M j, Y', strtotime($reservation['reservation_date'])) ?>
                                        <br>
                                        <?= date('g:i A', strtotime($reservation['reservation_time'])) ?>
                                    </td>
                                    <td data-label="Guests"><?= $reservation['guests'] ?></td>
                                    <td data-label="Status">
                                    <span class="status-badge <?= $statusClass ?>">
                                        <?= 
                                            $status === 'declined' ? 'Declined' : 
                                            ucwords(str_replace('_', ' ', $status)) 
                                        ?>
                                    </span>
                                    </td>
                                    <td data-label="Actions">
                                        <?php if ($status === 'waiting_payment'): ?>
                                            <a href="reservation_checkout.php?reservation_id=<?= $reservation['id'] ?>" 
                                               class="action-btn btn-primary">
                                                Pay Now
                                            </a>
                                        <?php else: ?>
                                            <a href="reservation_details.php?id=<?= $reservation['id'] ?>" 
                                               class="action-btn btn-outline">
                                                Details
                                            </a>
                                            <?php if ($reservation['status'] === 'cancelled' && $reservation['payment_status'] === 'paid'): ?>
                                                <button onclick="openRescheduleModal(<?= $reservation['id'] ?>, '<?= $reservation['table_number'] ?>')" 
                                                        class="action-btn btn-primary" style="margin-left: 0.5rem;">
                                                    Reschedule
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="empty-state">
                                    <i class="fas fa-calendar-times" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                    <p>You don't have any reservations yet.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

             <div id="rescheduleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Reschedule Reservation</h2>
                <span class="close">&times;</span>
            </div>
            <form id="rescheduleForm">
                <input type="hidden" id="reservationId" name="reservation_id">
                <input type="hidden" id="tableNumber" name="table_number">
                
                <div class="form-group">
                    <label for="newDate">New Date</label>
                    <input type="date" id="newDate" name="new_date" required min="<?= date('Y-m-d') ?>">
                </div>
                
                <div class="form-group">
                    <label>Available Time Slots</label>
                    <div class="time-slots" id="timeSlots">
                                                 </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="action-btn btn-danger" id="cancelReschedule">Cancel</button>
                    <button type="submit" class="action-btn btn-primary">Confirm Reschedule</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Open reschedule modal
        function openRescheduleModal(reservationId, tableNumber) {
            document.getElementById('reservationId').value = reservationId;
            document.getElementById('tableNumber').value = tableNumber;
            document.getElementById('rescheduleModal').style.display = 'block';
            
            // Set min date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('newDate').min = today;
            
            // Load available time slots for today by default
            loadAvailableTimeSlots(today, tableNumber);
        }

        // Close modal when clicking X or cancel button
        document.querySelector('.close').addEventListener('click', function() {
            document.getElementById('rescheduleModal').style.display = 'none';
        });

        document.getElementById('cancelReschedule').addEventListener('click', function() {
            document.getElementById('rescheduleModal').style.display = 'none';
        });

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === document.getElementById('rescheduleModal')) {
                document.getElementById('rescheduleModal').style.display = 'none';
            }
        });

        // Load available time slots when date changes
        document.getElementById('newDate').addEventListener('change', function() {
            const selectedDate = this.value;
            const tableNumber = document.getElementById('tableNumber').value;
            loadAvailableTimeSlots(selectedDate, tableNumber);
        });

        // Function to load available time slots via AJAX
        function loadAvailableTimeSlots(date, tableNumber) {
            const timeSlotsContainer = document.getElementById('timeSlots');
            timeSlotsContainer.innerHTML = '<p>Loading available time slots...</p>';
            
            // AJAX request to get available time slots
            $.ajax({
                url: 'get_available_time_slots.php',
                type: 'POST',
                data: {
                    date: date,
                    table_number: tableNumber
                },
                success: function(response) {
                    timeSlotsContainer.innerHTML = response;
                    
                    // Add click handler for time slots
                    document.querySelectorAll('.time-slot:not(.unavailable)').forEach(slot => {
                        slot.addEventListener('click', function() {
                            // Remove selected class from all slots
                            document.querySelectorAll('.time-slot').forEach(s => {
                                s.classList.remove('selected');
                            });
                            // Add selected class to clicked slot
                            this.classList.add('selected');
                        });
                    });
                },
                error: function() {
                    timeSlotsContainer.innerHTML = '<p>Error loading time slots. Please try again.</p>';
                }
            });
        }

        // Handle form submission
        document.getElementById('rescheduleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const reservationId = document.getElementById('reservationId').value;
            const newDate = document.getElementById('newDate').value;
            const selectedTimeSlot = document.querySelector('.time-slot.selected');
            
            if (!selectedTimeSlot) {
                alert('Please select a time slot');
                return;
            }
            
            const newTime = selectedTimeSlot.dataset.time;
            
            // AJAX request to reschedule
            $.ajax({
                url: 'reschedule_reservation.php',
                type: 'POST',
                data: {
                    reservation_id: reservationId,
                    new_date: newDate,
                    new_time: newTime
                },
                success: function(response) {
                    const result = JSON.parse(response);
                    if (result.success) {
                        alert('Reservation rescheduled successfully!');
                        window.location.reload();
                    } else {
                        alert('Error: ' + result.message);
                    }
                },
                error: function() {
                    alert('Error rescheduling reservation. Please try again.');
                }
            });
        });
    </script>
</body>
</html>