<?php
session_start();
include('connection.php'); // Added database connection

// Handle form submission
$message_sent = false;
$error_message = "";

if (isset($_POST['submit'])) {
    // Basic form validation
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $subject = filter_var($_POST['subject'], FILTER_SANITIZE_STRING);
    $message = filter_var($_POST['message'], FILTER_SANITIZE_STRING);
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = "Please fill in all fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address";
    } else {
        // Store the message in the database
        $query = "INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)";
        
        // Check if contact_messages table exists, if not create it
        $table_check = $conn->query("SHOW TABLES LIKE 'contact_messages'");
        if ($table_check->num_rows == 0) {
            $create_table = "CREATE TABLE contact_messages (
                id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $conn->query($create_table);
        }
        
        // Insert the message
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("ssss", $name, $email, $subject, $message);
            
            if ($stmt->execute()) {
                $message_sent = true;
                
                // If user is logged in, also add to messages system
                if (isset($_SESSION['user_id'])) {
                    $user_id = $_SESSION['user_id'];
                    $contact_message = "Contact form submission: " . $subject . "\n" . $message;
                    
                    // Check if messages table exists
                    $msg_table_check = $conn->query("SHOW TABLES LIKE 'messages'");
                    if ($msg_table_check->num_rows > 0) {
                        $msg_query = "INSERT INTO messages (user_id, message_text, is_from_admin) VALUES (?, ?, 0)";
                        $msg_stmt = $conn->prepare($msg_query);
                        $msg_stmt->bind_param("is", $user_id, $contact_message);
                        $msg_stmt->execute();
                    }
                }
                
                // Send email notification (in a real application)
                // mail('admin@furniture-rental.dz', 'New Contact Form: ' . $subject, $message, 'From: ' . $email);
            } else {
                $error_message = "Error sending message: " . $conn->error;
            }
        } else {
            $error_message = "Database error: " . $conn->error;
        }
    }
}

function calculatetotalcart() {
    $total = 0;
    $total_quantity = 0;

    if(isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach($_SESSION['cart'] as $product) {
            $total += $product['product_price'] * $product['product_quantity'];
            $total_quantity += $product['product_quantity'];
        }
    }

    $_SESSION['total'] = $total;
    $_SESSION['quantity'] = $total_quantity;
}

// Calculate cart totals for the header
calculatetotalcart();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Furniture Rental</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Cairo', sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            background-color: #f5f5f5;
        }

        .contact-container {
            max-width: 1200px;
            margin: 120px auto 60px;
            padding: 0 20px;
        }

        .contact-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .contact-header h1 {
            font-size: 36px;
            color: #333;
            margin-bottom: 15px;
            font-weight: 700;
            position: relative;
            display: inline-block;
            animation: fadeInDown 0.8s ease;
        }
        
        .contact-header h1::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -10px;
            width: 80px;
            height: 3px;
            background-color: #ff6b6b;
            transform: translateX(-50%);
        }

        .contact-header p {
            color: #666;
            max-width: 700px;
            margin: 20px auto 0;
            font-size: 16px;
            line-height: 1.6;
            animation: fadeIn 1s ease;
        }

        .contact-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        .contact-form-container {
            background-color: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            padding: 40px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .contact-form-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
        }

        .contact-form-header {
            margin-bottom: 30px;
        }

        .contact-form-header h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
            position: relative;
            padding-left: 15px;
        }
        
        .contact-form-header h2::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 5px;
            height: 25px;
            background-color: #ff6b6b;
            transform: translateY(-50%);
            border-radius: 2px;
        }

        .contact-form-header p {
            color: #666;
            font-size: 15px;
            line-height: 1.5;
        }

        .contact-form .form-group {
            margin-bottom: 25px;
        }

        .contact-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 15px;
        }

        .contact-form input,
        .contact-form textarea {
            width: 100%;
            padding: 14px 18px;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            font-family: 'Cairo', sans-serif;
            font-size: 15px;
            transition: all 0.3s ease;
            background-color: #f9f9f9;
        }

        .contact-form input:focus,
        .contact-form textarea:focus {
            border-color: #ff6b6b;
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
            background-color: #fff;
        }

        .contact-form textarea {
            min-height: 150px;
            resize: vertical;
        }

        .contact-form button {
            padding: 14px 30px;
            background-color: #ff6b6b;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-block;
            width: 100%;
        }

        .contact-form button:hover {
            background-color: #ff5252;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.2);
        }

        .contact-form button i {
            margin-right: 8px;
        }

        .contact-info-container {
            background-color: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            padding: 40px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .contact-info-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
        }

        .contact-info-header {
            margin-bottom: 30px;
        }

        .contact-info-header h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
            position: relative;
            padding-left: 15px;
        }
        
        .contact-info-header h2::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 5px;
            height: 25px;
            background-color: #ff6b6b;
            transform: translateY(-50%);
            border-radius: 2px;
        }

        .contact-info-header p {
            color: #666;
            font-size: 15px;
            line-height: 1.5;
        }

        .contact-info-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 25px;
            transition: transform 0.2s ease;
        }
        
        .contact-info-item:hover {
            transform: translateX(5px);
        }

        .contact-info-icon {
            background-color: rgba(255, 107, 107, 0.1);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }
        
        .contact-info-item:hover .contact-info-icon {
            background-color: #ff6b6b;
        }

        .contact-info-icon i {
            font-size: 20px;
            color: #ff6b6b;
            transition: all 0.3s ease;
        }
        
        .contact-info-item:hover .contact-info-icon i {
            color: white;
        }

        .contact-info-content h3 {
            margin: 0 0 5px;
            font-size: 18px;
            color: #333;
        }

        .contact-info-content p {
            margin: 0;
            color: #666;
            line-height: 1.5;
            font-size: 15px;
        }
        
        .contact-info-content p a {
            color: #ff6b6b;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-top: 5px;
            transition: all 0.3s ease;
        }
        
        .contact-info-content p a:hover {
            color: #ff5252;
        }
        
        .contact-info-content p a i {
            margin-left: 5px;
            transition: transform 0.3s ease;
        }
        
        .contact-info-content p a:hover i {
            transform: translateX(3px);
        }

        .contact-social {
            margin-top: 35px;
        }

        .contact-social h3 {
            margin-bottom: 15px;
            font-size: 18px;
            color: #333;
            position: relative;
            padding-bottom: 10px;
        }
        
        .contact-social h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 2px;
            background-color: #ff6b6b;
        }

        .social-icons {
            display: flex;
            gap: 12px;
        }

        .social-icon {
            width: 40px;
            height: 40px;
            background-color: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .social-icon:hover {
            background-color: #ff6b6b;
            color: white;
            transform: translateY(-5px);
            box-shadow: 0 5px 10px rgba(255, 107, 107, 0.3);
        }

        .contact-map {
            margin-top: 50px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            height: 400px;
            position: relative;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .contact-map:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
        }

        .contact-map iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .cart-icon {
            position: relative;
            font-size: 22px;
            color: #000;
            text-decoration: none;
            margin-left: 20px;
        }

        .cart-icon i {
            font-size: 26px;
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -10px;
            background-color: red;
            color: white;
            font-size: 13px;
            font-weight: bold;
            border-radius: 50%;
            padding: 5px 10px;
        }

        /* Account dropdown styles */
        .account-icon {
            position: relative;
            font-size: 22px;
            color: #000;
            text-decoration: none;
            margin-left: 20px;
        }

        .account-icon i {
            font-size: 26px;
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            min-width: 160px; 
            padding: 8px 0;
            z-index: 100;
        }

        .dropdown-content a {
            display: block;
            padding: 8px 12px;
            font-size: 14px; 
            color: #333;
            text-decoration: none;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .dropdown-content p {
            padding: 8px 12px;
            font-weight: bold;
            font-size: 14px;
            color: #555;
            margin: 0;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        /* Updated Navigation Styles */
        .container-nav {
            width: 100%;
            background-color: white;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        
        .navigation-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 5%;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .brand {
            display: flex;
            align-items: center;
        }
        
        .brand img {
            height: 40px;
            object-fit: contain;
        }
        
        .brand h2 {
            color: #333;
            font-size: 1.5rem;
            margin: 0;
            font-weight: 700;
        }
        
        .nav-links {
            display: flex;
            gap: 30px;
        }
        
        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            padding: 5px 0;
            position: relative;
            font-size: 16px;
            transition: color 0.3s ease;
        }
        
        .nav-links a:hover {
            color: #ff6b6b;
        }
        
        .nav-links a.active {
            color: #ff6b6b;
            font-weight: 600;
        }
        
        .nav-links a.active::after,
        .nav-links a:hover::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 2px;
            background-color: #ff6b6b;
            bottom: -5px;
            left: 0;
            transform: scaleX(1);
            transition: transform 0.3s ease;
        }
        
        .nav-links a:not(.active)::after {
            transform: scaleX(0);
        }
        
        .nav-links a:hover::after {
            transform: scaleX(1);
        }
        
        /* Contact Header Styling */
        @media (max-width: 768px) {
            .navigation-bar {
                flex-wrap: wrap;
                padding: 15px 20px;
            }
            
            .nav-links {
                order: 3;
                width: 100%;
                margin-top: 15px;
                justify-content: center;
                gap: 15px;
            }
            
            .contact-container {
                margin-top: 150px;
            }
            
            .contact-content {
                grid-template-columns: 1fr;
            }
            
            .contact-form-container,
            .contact-info-container {
                padding: 30px;
            }
            
            .contact-header h1 {
                font-size: 28px;
            }
        }

        @media (max-width: 480px) {
            .contact-form-container,
            .contact-info-container {
                padding: 20px;
            }
            
            .contact-header h1 {
                font-size: 24px;
            }
            
            .contact-form button {
                width: 100%;
            }
            
            .nav-links {
                gap: 10px;
                font-size: 14px;
            }
            
            .contact-container {
                margin-top: 170px;
            }
        }

        /* Alert Styles */
        .alert {
            padding: 18px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: flex-start;
            font-weight: 500;
            animation: slideIn 0.4s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert i {
            margin-right: 15px;
            font-size: 1.2rem;
            margin-top: 2px;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.08);
            border-left: 4px solid #28a745;
            color: #1e7e34;
        }
        
        .alert-error {
            background-color: rgba(220, 53, 69, 0.08);
            border-left: 4px solid #dc3545;
            color: #bd2130;
        }
        
        /* Badge Styles */
        .badge {
            display: inline-block;
            background-color: #dc3545;
            color: white;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
            min-width: 18px;
            text-align: center;
        }
        
        .footer {
            background-color: #000;
            color: white;
            padding: 40px 0 20px;
            margin-top: 60px;
        }
        
        .footer .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .footer-section {
            flex: 1;
            min-width: 200px;
        }
        
        .footer-section h3 {
            font-size: 18px;
            margin-bottom: 15px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-section h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 2px;
            background-color: #ff6b6b;
        }
        
        .footer-section p {
            margin-bottom: 10px;
            font-size: 14px;
            opacity: 0.8;
            display: flex;
            align-items: center;
        }
        
        .footer-section p i {
            margin-right: 10px;
            min-width: 20px;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }
        
        .social-links a {
            color: white;
            font-size: 18px;
            transition: all 0.3s ease;
        }
        
        .social-links a:hover {
            color: #ff6b6b;
            transform: translateY(-3px);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 14px;
            opacity: 0.7;
        }

        .auth-section {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .auth-button {
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .login {
            color: #ff6b6b;
            border: 1px solid #ff6b6b;
        }
        
        .login:hover {
            background-color: rgba(255, 107, 107, 0.1);
        }
        
        .signup {
            background-color: #ff6b6b;
            color: white;
        }
        
        .signup:hover {
            background-color: #ff5252;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 107, 107, 0.2);
        }
        
        .dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropbtn {
            background-color: white;
            color: #333;
            border: none;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            padding: 8px 12px;
            border-radius: 5px;
        }
        
        .dropbtn:hover {
            background-color: #f8f9fa;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 180px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            z-index: 1000;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
            top: 120%;
        }
        
        .dropdown-content a {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: all 0.2s ease;
            font-size: 14px;
        }
        
        .dropdown-content a:hover {
            background-color: #f8f9fa;
            padding-left: 20px;
        }
        
        .dropdown:hover .dropdown-content {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Contact Header Styling Enhancement */
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container-nav">
        <div class="navigation-bar">
            <div class="brand">
                <a href="index.php">
                    <img src="assets/images/logo.png" alt="Furniture Rental" onerror="this.onerror=null; this.parentElement.innerHTML='<h2>Furniture Rental</h2>';">
                </a>
            </div>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="product.php">Products</a>
                <a href="contact.php" class="active">Contact</a>
                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <?php 
                    // Get count of unread contact messages
                    $unread_count = 0;
                    $unread_query = "SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0";
                    $result = $conn->query($unread_query);
                    if ($result && $row = $result->fetch_assoc()) {
                        $unread_count = $row['count'];
                    }
                    ?>
                    <a href="admin_dashboard.php">Admin Dashboard</a>
                    <a href="admin_messages.php">Messages 
                        <?php if ($unread_count > 0): ?>
                            <span class="badge"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            </div>
            <div class="auth-section">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="dropdown">
                        <button class="dropbtn">
                            <i class="fas fa-user-circle"></i> <?php echo $_SESSION['user_name']; ?>
                        </button>
                        <div class="dropdown-content">
                            <a href="account.php">My Account</a>
                            <a href="my_orders.php">My Orders</a>
                            <a href="user_messages.php">My Messages</a>
                            <a href="account.php?logout=1">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="auth-button login">Login</a>
                    <a href="register.php" class="auth-button signup">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="contact-container">
        <div class="contact-header">
            <h1>Contact Us</h1>
            <p>Need help with your furniture rental or have a question? We're here to assist you. Reach out to us using the contact information below or fill out the form.</p>
        </div>

        <div class="contact-content">
            <div class="contact-form-container">
                <div class="contact-form-header">
                    <h2>Send Us a Message</h2>
                    <p>Fill out the form below and we'll get back to you as soon as possible.</p>
                </div>

                <?php if ($message_sent): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Thank you for your message! We'll get back to you soon.
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form class="contact-form" method="post" action="contact.php">
                    <div class="form-group">
                        <label for="name">Your Name</label>
                        <input type="text" id="name" name="name" placeholder="Enter your name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" placeholder="Enter subject" value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" placeholder="Enter your message here..." required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    </div>

                    <button type="submit" name="submit">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>

            <div class="contact-info-container">
                <div class="contact-info-header">
                    <h2>Contact Information</h2>
                    <p>You can reach us directly using the following information.</p>
                </div>

                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="contact-info-content">
                        <h3>Our Location</h3>
                        <p>123 Furniture Street, Algiers, Algeria</p>
                    </div>
                </div>

                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <div class="contact-info-content">
                        <h3>Phone Number</h3>
                        <p>+213 XX XXX XXXX</p>
                        <p>Monday - Friday: 9am - 5pm</p>
                    </div>
                </div>

                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="contact-info-content">
                        <h3>Email Address</h3>
                        <p>info@furniture-rental.dz</p>
                        <p>support@furniture-rental.dz</p>
                    </div>
                </div>

                <?php if(isset($_SESSION['user_id'])): ?>
                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="contact-info-content">
                        <h3>Direct Message</h3>
                        <p>You can also send us a direct message through your account.</p>
                        <p><a href="user_messages.php">Open Messaging <i class="fas fa-arrow-right"></i></a></p>
                    </div>
                </div>
                <?php endif; ?>

                <div class="contact-social">
                    <h3>Connect With Us</h3>
                    <div class="social-icons">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="contact-map">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d102239.59252536311!2d3.0160503087597566!3d36.73707399187726!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x128fb26977ea659f%3A0x3e45661dd15c3ce3!2sAlgiers%2C%20Algeria!5e0!3m2!1sen!2sus!4v1606231230318!5m2!1sen!2sus" allowfullscreen="" loading="lazy"></iframe>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Contact Us</h3>
                    <p><i class="fas fa-phone"></i> +213 XX XXX XXXX</p>
                    <p><i class="fas fa-envelope"></i> info@furniture-rental.dz</p>
                </div>
                <div class="footer-section">
                    <h3>Follow Us</h3>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Furniture Rental. All Rights Reserved</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animate form and info containers on page load
            const formContainer = document.querySelector('.contact-form-container');
            const infoContainer = document.querySelector('.contact-info-container');
            
            formContainer.style.opacity = 0;
            formContainer.style.transform = 'translateY(20px)';
            formContainer.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            
            infoContainer.style.opacity = 0;
            infoContainer.style.transform = 'translateY(20px)';
            infoContainer.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            
            setTimeout(() => {
                formContainer.style.opacity = 1;
                formContainer.style.transform = 'translateY(0)';
            }, 300);
            
            setTimeout(() => {
                infoContainer.style.opacity = 1;
                infoContainer.style.transform = 'translateY(0)';
            }, 500);
        });
    </script>
</body>
</html> 