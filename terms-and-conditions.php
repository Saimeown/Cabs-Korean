<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions</title>
    <link rel="icon" href="cabs.png" type="image/png">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;700&display=swap');

        * {
            box-sizing: border-box;
        }

        body {
            background-color: #121212;
            color: #e0e0e0;
            font-family: 'Kumbh Sans', sans-serif;
            line-height: 1.8;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            overflow-x: hidden;
        }

        .container-wrapper {
            width: 100%;
            max-width: 630px; 
            padding: 3px; 
            background: linear-gradient(to right, #ff7eb3, #ff0844);
            border-radius: 15px;
            margin: 0 auto;
            margin-top: 5em;
        }

        .container {
            width: 100%;
            max-height: 70vh;
            padding: 40px;
            background: rgba(18, 18, 18, 0.95);
            border-radius: 12px;
            text-align: center;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #ff7eb3 #121212;
        }

        .container::-webkit-scrollbar {
            width: 8px;
        }
        .container::-webkit-scrollbar-thumb {
            background: #ff7eb3;
            border-radius: 10px;
        }
        .container::-webkit-scrollbar-track {
            background: #121212;
        }

        h1 {
            text-align: center;
            background: linear-gradient(to right, #ff7eb3, #ff0844);
            background-clip: text;
            color: transparent;
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 15px;
            line-height: 1.3;
            padding: 0 10px;
        }

        .divider {
            width: calc(100% - 20px);
            height: 2px;
            background: linear-gradient(to right, #ff7eb3, #ff0844);
            margin: 20px auto;
            border-radius: 5px;
        }

        ol {
            padding-left: 25px;
            text-align: left;
            margin: 0;
            padding-right: 15px;
        }

        ol li {
            margin: 12px 0;
            font-size: 1.1rem;
            word-wrap: break-word;
        }

        p {
            font-size: 1.1rem;
            text-align: justify;
            margin: 0 10px 20px 10px;
            word-wrap: break-word;
        }

        .footer {
            color: #ffffff;
            text-align: center;
            padding: 15px 0;
            margin-top: 20px;
            font-size: 0.9rem;
            width: 100%;
            display: flex;
            justify-content: center;
        }

        .highlight {
            color: #ff7eb3;
            font-weight: bold;
        }

        .back-button {
            display: inline-block;
            margin-top: 25px;
            padding: 12px 25px;
            font-size: 1rem;
            color: #fff;
            background: linear-gradient(to right, #ff0844, #ff7eb3);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s ease, transform 0.2s ease;
            opacity: 0.5;
            pointer-events: none;
        }

        .back-button.active {
            opacity: 1;
            pointer-events: auto;
        }

        .back-button:hover {
            background: linear-gradient(to right, #ff7eb3, #ff0844);
            transform: scale(1.05);
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px 0;
            margin-top: 50px;
        }

        .checkbox-container input[type="checkbox"] {
            appearance: none;
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            border: 2px solid #ff7eb3;
            border-radius: 4px;
            margin-right: 10px;
            position: relative;
            cursor: pointer;
        }

        .checkbox-container input[type="checkbox"]:checked {
            background-color: #ff0844;
            border-color: #ff0844;
        }

        .checkbox-container input[type="checkbox"]:checked::after {
            content: "✓";
            position: absolute;
            color: white;
            font-size: 14px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .checkbox-container label {
            cursor: pointer;
            font-size: 1rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 30px 20px;
                max-height: 80vh;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            ol li, p {
                font-size: 1rem;
            }
            
            .container-wrapper {
                max-width: 95%;
            }
        }
    </style>
</head>
<body>
    <div class="container-wrapper">
        <div class="container">
            <h1>Terms and Conditions</h1>
            <p>Welcome to <span class="highlight">CABS KOREAN</span>. By accessing or using our services, you agree to comply with the following terms and conditions:</p>
            
            <div class="divider"></div>

            <ol>
                <li><strong>Account Responsibility:</strong> You must provide accurate and complete information during registration and keep your credentials secure.</li>
                <li><strong>Ordering Process:</strong> Orders are confirmed only after payment is received. You must review your order before finalizing.</li>
                <li><strong>Table Reservations:</strong> Reservations must be made at least 30 minutes in advance. Late arrivals exceeding 15 minutes may result in cancellation.</li>
                <li><strong>Order Cancellations:</strong> Orders cannot be canceled once food preparation has started. Cancellations before this stage may be eligible for a partial refund.</li>
                <li><strong>Refund Policy:</strong> Refunds are only issued for orders that cannot be fulfilled due to restaurant issues. Refunds take 3-5 business days.</li>
                <li><strong>Delivery and Pickup:</strong> Estimated delivery times are subject to change based on traffic and restaurant workload. Pickup orders should be collected within 30 minutes of the ready notification.</li>
                <li><strong>Prohibited Activities:</strong> You may not use our services for any illegal, unauthorized, or fraudulent activities.</li>
                <li><strong>Content Ownership:</strong> All content provided by CABS KOREAN is owned by us and may not be redistributed without permission.</li>
                <li><strong>Service Modifications:</strong> We reserve the right to update, modify, or discontinue any part of the service without prior notice.</li>
                <li><strong>Allergy Disclaimer:</strong> Our food may contain allergens. Customers must review ingredient lists before ordering.</li>
                <li><strong>Payment Security:</strong> All online payments are securely processed. We do not store credit card details.</li>
                <li><strong>User Conduct:</strong> Harassment, abuse, or any harmful behavior towards other users or staff will result in account suspension.</li>
                <li><strong>Privacy Policy:</strong> Your personal data is handled with care and in compliance with applicable privacy laws.</li>
                <li><strong>Account Termination:</strong> We have the right to suspend or terminate your account if you violate these terms.</li>
                <li><strong>Changes to Terms:</strong> These terms may be updated periodically. Continued use of our services constitutes acceptance of any changes.</li>
            </ol>
            
            <p>By agreeing to these terms, you acknowledge that you have read, understood, and accepted our policies.</p>
            
            <div class="checkbox-container">
                <input type="checkbox" id="agree-checkbox">
                <label for="agree-checkbox">I agree to the terms and conditions</label>
            </div>
            
            <a href="register.php" class="back-button" id="back-button">Back to Registration</a>
        </div>
    </div>
    
    <div class="footer">
        <p>&copy; 2025 CABS KOREAN. All rights reserved.</p>
    </div>

    <script>
        const checkbox = document.getElementById('agree-checkbox');
        const backButton = document.getElementById('back-button');

        checkbox.addEventListener('change', function() {
            if (this.checked) {
                backButton.classList.add('active');
            } else {
                backButton.classList.remove('active');
            }
        });
    </script>
</body>
</html>