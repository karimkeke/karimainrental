<?php
session_start();
include('connection.php');

// Redirect if not logged in
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('location: login.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    // Clear all session variables
    $_SESSION = array();
    
    // If a session cookie is used, clear it as well
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-42000, '/');
    }
    
    // Clear "remember me" cookies if they exist
    if (isset($_COOKIE['user_email'])) {
        setcookie('user_email', '', time()-42000, '/');
        setcookie('user_remember', '', time()-42000, '/');
    }
    
    // Destroy the session
    session_destroy(); 
    
    // Redirect to login page with a message
    header('location: login.php?message=You have been successfully logged out');
    exit;
}

// Fetch user information
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Count user orders
$orders_query = "SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?";
$stmt = $conn->prepare($orders_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();
$order_data = $orders_result->fetch_assoc();
$order_count = $order_data['order_count'];

// Get unread message count
$unread_count = 0;
$recent_messages = [];

// Check if messages table exists
$table_check = $conn->query("SHOW TABLES LIKE 'messages'");
if ($table_check->num_rows > 0) {
    // Count unread messages
    $unread_query = "SELECT COUNT(*) as unread FROM messages WHERE user_id = ? AND is_from_admin = 1 AND is_read = 0";
    $unread_stmt = $conn->prepare($unread_query);
    $unread_stmt->bind_param("i", $user_id);
    $unread_stmt->execute();
    $unread_result = $unread_stmt->get_result();
    $unread_count = $unread_result->fetch_assoc()['unread'];
    
    // Get recent messages (last 3)
    $recent_query = "SELECT * FROM messages WHERE user_id = ? ORDER BY created_at DESC LIMIT 3";
    $recent_stmt = $conn->prepare($recent_query);
    $recent_stmt->bind_param("i", $user_id);
    $recent_stmt->execute();
    $recent_result = $recent_stmt->get_result();
    
    while ($message = $recent_result->fetch_assoc()) {
        $recent_messages[] = $message;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Furniture Rental</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Cairo', sans-serif;
            margin: 0;
            padding: 0;
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

        .account-container {
            max-width: 1000px;
            margin: 80px auto 40px;
            padding: 0 20px;
        }

        .account-header {
            background-color: #fff;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
        }

        .account-avatar {
            width: 100px;
            height: 100px;
            background-color: #f0f0f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 30px;
        }

        .account-avatar i {
            font-size: 50px;
            color: #000;
        }

        .account-user-info h2 {
            margin: 0 0 5px;
            font-size: 24px;
            color: #000;
        }

        .account-user-info p {
            margin: 0;
            color: #666;
            font-size: 16px;
        }

        .account-sections {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .account-section {
            background-color: #fff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .account-section:hover {
            transform: translateY(-5px);
        }

        .account-section-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .account-section-icon {
            width: 50px;
            height: 50px;
            background-color: #f0f0f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }

        .account-section-icon i {
            font-size: 24px;
            color: #000;
        }

        .account-section-title {
            margin: 0;
            font-size: 18px;
            color: #000;
        }

        .account-section-content {
            color: #666;
            font-size: 15px;
            line-height: 1.5;
        }

        .account-section-link {
            display: block;
            margin-top: 15px;
            text-align: right;
            color: #000;
            font-weight: 600;
            text-decoration: none;
        }

        .account-section-link:hover {
            text-decoration: underline;
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
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="logo">
                <h1>Furniture Rental</h1>
            </div>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="product.php">Products</a>
                <a href="contact.php">Contact Us</a>
                <div class="dropdown" style="display: inline-block;">
                    <a href="#" class="account-icon">
                        <i class="fas fa-user"></i>
                    </a>
                    <div class="dropdown-content">
                        <?php if(isset($_SESSION['user_name'])): ?>
                            <p>Hi, <?php echo $_SESSION['user_name']; ?></p>
                            <a href="accountdetails.php">Account Details</a>
                            <a href="my_orders.php">My Orders</a>
                            <a href="user_messages.php">Messages <?php if($unread_count > 0): ?><span style="background-color: #dc3545; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem;"><?php echo $unread_count; ?></span><?php endif; ?></a>
                            <a href="account.php">My Account</a>
                            <a href="account.php?logout=1" name="logout" id="logout-btn">Logout</a>
                        <?php else: ?>
                            <a href="login.php">Login</a>
                            <a href="register.php">Create an Account</a>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="cart.php" class="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count">
                        <?php echo isset($_SESSION['quantity']) ? $_SESSION['quantity'] : 0; ?>
                    </span>
                </a>
            </div>
        </div>
    </nav>

    <div class="account-container">
        <div class="account-header">
            <div class="account-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="account-user-info">
                <h2>Welcome, <?php echo $_SESSION['user_name']; ?></h2>
                <p><?php echo $_SESSION['user_email']; ?></p>
            </div>
        </div>

        <div class="account-sections">
            <div class="account-section">
                <div class="account-section-header">
                    <div class="account-section-icon">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <h3 class="account-section-title">Account Settings</h3>
                </div>
                <div class="account-section-content">
                    <p>Update your personal information and manage your account settings.</p>
                </div>
                <a href="accountdetails.php" class="account-section-link">Manage Settings <i class="fas fa-chevron-right"></i></a>
            </div>

            <div class="account-section">
                <div class="account-section-header">
                    <div class="account-section-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <h3 class="account-section-title">My Orders</h3>
                </div>
                <div class="account-section-content">
                    <p>You have <?php echo $order_count; ?> order(s). View and track your orders here.</p>
                </div>
                <a href="my_orders.php" class="account-section-link">View Orders <i class="fas fa-chevron-right"></i></a>
            </div>

            <div class="account-section">
                <div class="account-section-header">
                    <div class="account-section-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3 class="account-section-title">
                        Messages 
                        <?php if($unread_count > 0): ?>
                            <span style="background-color: #dc3545; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem; margin-left: 5px;">
                                <?php echo $unread_count; ?> new
                            </span>
                        <?php endif; ?>
                    </h3>
                </div>
                <div class="account-section-content">
                    <?php if(empty($recent_messages)): ?>
                        <p>You have no messages yet. Need help with something? Send us a message!</p>
                    <?php else: ?>
                        <p>Recent messages:</p>
                        <div style="margin-top: 10px; margin-bottom: 10px;">
                            <?php foreach($recent_messages as $message): ?>
                                <div style="padding: 10px; margin-bottom: 8px; border-radius: 8px; <?php echo $message['is_from_admin'] ? 'background-color: #f1f1f1;' : 'background-color: #f8f9fa; border-left: 3px solid #000;'; ?>">
                                    <div style="font-size: 0.8rem; color: #888; margin-bottom: 3px;">
                                        <?php 
                                            $time = new DateTime($message['created_at']);
                                            echo $message['is_from_admin'] ? 'Admin' : 'You';
                                            echo ' - ' . $time->format('M d, H:i'); 
                                        ?>
                                    </div>
                                    <div style="color: #333; font-size: 0.9rem;">
                                        <?php 
                                            echo strlen($message['message_text']) > 60 ? 
                                                htmlspecialchars(substr($message['message_text'], 0, 60)) . '...' : 
                                                htmlspecialchars($message['message_text']); 
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <a href="user_messages.php" class="account-section-link">View Messages <i class="fas fa-chevron-right"></i></a>
            </div>

            <div class="account-section">
                <div class="account-section-header">
                    <div class="account-section-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3 class="account-section-title">Shopping Cart</h3>
                </div>
                <div class="account-section-content">
                    <p>You have <?php echo isset($_SESSION['quantity']) ? $_SESSION['quantity'] : 0; ?> item(s) in your cart.</p>
                </div>
                <a href="cart.php" class="account-section-link">View Cart <i class="fas fa-chevron-right"></i></a>
            </div>
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

    <script src="script.js"></script>
</body>
</html>