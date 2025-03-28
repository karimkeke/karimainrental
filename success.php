<?php
session_start();
include('connection.php');

if (!isset($_SESSION['logged_in'])) {
    header('location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
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
    <title>Order Success</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    body {
    background-color: #f8f9fa;
    font-family: 'Cairo', sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh; 
    margin: 0;
}

.container {
    max-width: 500px; 
    width: 100%;
    padding: 15px;
}

.card {
    border-radius: 8px;
    box-shadow: 0px 3px 6px rgba(0, 0, 0, 0.1);
    padding: 15px;
    text-align: center;
}

.success-icon {
    font-size: 40px;
    color: #28a745;
    margin-bottom: 15px;
    animation: pop 0.5s ease-in-out;
}

@keyframes pop {
    0% { transform: scale(0.5); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

.orders-container {
    max-height: 300px;
    overflow-y: auto;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 6px;
    margin-top: 15px;
}

.order-summary {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    margin-bottom: 15px; 
    padding: 10px; 
    background-color: #fff; 
    border-radius: 6px;
    box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.1); 
}

.order-summary img {
    width: 120px;
    height: 120px;
    border-radius: 6px;
    object-fit: cover;
}

.order-summary .details {
    flex: 1;
    text-align: left;
}

.order-summary .details h4 {
    font-size: 18px;
    margin-bottom: 10px;
    color: #333;
}

.order-summary .details p {
    font-size: 14px;
    color: #555;
    margin-bottom: 8px;
}

.order-summary .details .status {
    color: red;
    font-weight: bold;
}

.btn-primary {
    background-color: #ff6b6b;
    border: none;
    padding: 10px 18px;
    font-size: 15px;
    border-radius: 6px;
    transition: 0.3s;
    width: 100%;
}

.btn-primary:hover {
    background-color: #ff4757;
}

      
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <i class="fas fa-check-circle success-icon"></i>
        <h2 class="text-success">Order Placed Successfully!</h2>
        <p>Thank you for your order. Your payment has been processed.</p>

        <?php if (!empty($orders)): ?>
            <div class="orders-container">
                <?php foreach ($orders as $order): ?>
                    <div class="order-summary">
                        <img src="assets/imgs/<?php echo htmlspecialchars($order['product_image']); ?>" alt="Product Image">
                        <div class="details">
                            <h4>Order Details</h4>
                            <p><strong>Product:</strong> <?php echo htmlspecialchars($order['product_name']); ?></p>
                            <p><strong>Quantity:</strong> <?php echo htmlspecialchars($order['quantity']); ?></p>
                            <p><strong>Rental Length:</strong> <?php echo htmlspecialchars($order['rental_length']); ?> Months</p>
                            <p><strong>Total Price:</strong> <?php echo htmlspecialchars($order['total_price']); ?> DA</p>
                            <p><strong>Payment Method:</strong> <?php echo isset($order['payment_method']) && $order['payment_method'] ? htmlspecialchars($order['payment_method']) : 'Cash on Delivery'; ?></p>
                            <p><strong>Status:</strong> <span class="status">Order Confirmed</span></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <a href="index.php" class="btn btn-primary mt-4">Continue Shopping <i class="fas fa-shopping-cart ms-2"></i></a>
        <?php else: ?>
            <h3>No orders found.</h3>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>