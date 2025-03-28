<?php
session_start();
include('connection.php');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch all orders for the current user
$query = "SELECT orders.*, products.product_name, products.product_image 
          FROM orders 
          JOIN products ON orders.product_id = products.product_id
          WHERE orders.user_id = ? 
          ORDER BY orders.created_at DESC";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Furniture Rental</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .orders-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 1000px;
            margin: 100px auto;
        }

        .orders-title {
            font-size: 28px;
            font-weight: 700;
            color: #000;
            margin-bottom: 30px;
            text-align: center;
        }

        .order-card {
            background-color: #f9f9f9;
            border-radius: 8px;
            margin-bottom: 25px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        .order-header {
            background-color: #000;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-date {
            font-size: 14px;
        }

        .order-items {
            padding: 0;
        }

        .order-item {
            display: flex;
            padding: 20px;
            border-bottom: 1px solid #eee;
            align-items: center;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-item-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
            margin-right: 20px;
        }

        .order-item-details {
            flex: 1;
        }

        .order-item-name {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 16px;
        }

        .order-item-info {
            color: #555;
            font-size: 14px;
            margin-bottom: 3px;
        }

        .order-payment {
            padding: 15px 20px;
            background-color: #f2f2f2;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
        }
        
        .order-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            background-color: #4CAF50;
            color: white;
        }

        .empty-orders {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .empty-orders i {
            font-size: 60px;
            margin-bottom: 20px;
            color: #ddd;
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
                            <a href="account.php">My Account</a>
                            <a href="account.php?logout=1">Logout</a>
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

    <div class="orders-container">
        <h2 class="orders-title">My Orders</h2>
        
        <?php if (!empty($orders)): ?>
            <?php 
            // Group orders by created_at date
            $groupedOrders = [];
            foreach ($orders as $order) {
                $date = date('Y-m-d', strtotime($order['created_at']));
                if (!isset($groupedOrders[$date])) {
                    $groupedOrders[$date] = [];
                }
                $groupedOrders[$date][] = $order;
            }
            ?>

            <?php foreach ($groupedOrders as $date => $dateOrders): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-id">Order Date</div>
                        <div class="order-date"><?php echo date('F d, Y', strtotime($date)); ?></div>
                    </div>
                    <div class="order-items">
                        <?php foreach ($dateOrders as $order): ?>
                            <div class="order-item">
                                <img src="assets/imgs/<?php echo htmlspecialchars($order['product_image']); ?>" alt="<?php echo htmlspecialchars($order['product_name']); ?>" class="order-item-image">
                                <div class="order-item-details">
                                    <div class="order-item-name"><?php echo htmlspecialchars($order['product_name']); ?></div>
                                    <div class="order-item-info">Quantity: <?php echo htmlspecialchars($order['quantity']); ?></div>
                                    <div class="order-item-info">Rental Length: <?php echo htmlspecialchars($order['rental_length']); ?> Month(s)</div>
                                    <div class="order-item-info">Total: <?php echo number_format($order['total_price']); ?> DA</div>
                                    <div class="order-item-info">
                                        Payment Method: <?php echo isset($order['payment_method']) && $order['payment_method'] ? htmlspecialchars($order['payment_method']) : 'Cash on Delivery'; ?>
                                    </div>
                                    <div class="order-item-info">
                                        Status: <span class="order-status">Confirmed</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="order-payment">
                        <div>Total Items: <?php echo count($dateOrders); ?></div>
                        <div>
                            Total Amount: <?php 
                                $totalAmount = array_sum(array_column($dateOrders, 'total_price'));
                                echo number_format($totalAmount); 
                            ?> DA
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-orders">
                <i class="fas fa-shopping-bag"></i>
                <h3>You haven't placed any orders yet</h3>
                <p>Once you place an order, you'll see the details here.</p>
                <a href="product.php" class="primary-btn" style="display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #000; color: white; text-decoration: none; border-radius: 4px;">Start Shopping</a>
            </div>
        <?php endif; ?>
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