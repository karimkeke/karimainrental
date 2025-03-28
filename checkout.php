<?php
session_start();
include('connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if cart exists and is not empty
if (!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
    header("Location: cart.php");
    exit();
}

// Check if payment has been processed
if (!isset($_SESSION['payment_processed']) || $_SESSION['payment_processed'] !== true) {
    header("Location: payment_methods.php");
    exit();
}

// Check if payment_method column exists in orders table, and create it if it doesn't
$check_column = $conn->query("SHOW COLUMNS FROM orders LIKE 'payment_method'");
if ($check_column->num_rows == 0) {
    // Column doesn't exist, let's create it
    $add_column = $conn->query("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) DEFAULT 'Cash on Delivery'");
    if (!$add_column) {
        $_SESSION['checkout_error'] = "Error setting up payment system: " . $conn->error;
        header("Location: cart.php");
        exit();
    }
}

$user_id = $_SESSION['user_id'];
$payment_method = isset($_SESSION['payment_method']) ? $_SESSION['payment_method'] : 'Cash on Delivery';

// Format the payment method for display in the database
switch($payment_method) {
    case 'credit_card':
        $payment_method_display = 'Credit Card';
        break;
    case 'paypal':
        $payment_method_display = 'PayPal';
        break;
    case 'cash_on_delivery':
        $payment_method_display = 'Cash on Delivery';
        break;
    default:
        $payment_method_display = 'Cash on Delivery';
}

$total_price = 0;
$order_items = [];

// Check and fix any cart items with missing or invalid rental_length
foreach ($_SESSION['cart'] as $key => $item) {
    // Ensure rental_length is set and is a valid positive number
    if (!isset($item['rental_length']) || empty($item['rental_length']) || !is_numeric($item['rental_length']) || $item['rental_length'] <= 0) {
        // Set a default rental length of 1 month if missing or invalid
        $_SESSION['cart'][$key]['rental_length'] = 1;
    }
}

foreach ($_SESSION['cart'] as $item) {
    $product_id = $item['product_id'];
    $quantity = $item['product_quantity'];
    // Ensure rental_length is a valid integer
    $rental_length = isset($item['rental_length']) && is_numeric($item['rental_length']) ? (int)$item['rental_length'] : 1;
    $product_price = $item['product_price'];

    $subtotal = $product_price * $quantity * $rental_length; 
    $total_price += $subtotal;
    $order_items[] = [
        'product_id' => $product_id,
        'quantity' => $quantity,
        'rental_length' => $rental_length,
        'subtotal' => $subtotal
    ];
}

$transaction_success = true;
$conn->begin_transaction();

try {
    foreach ($order_items as $order) {
        $stmt = $conn->prepare("INSERT INTO orders (user_id, product_id, quantity, rental_length, total_price, payment_method, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("iiiids", $user_id, $order['product_id'], $order['quantity'], $order['rental_length'], $order['subtotal'], $payment_method_display);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        // Check product availability before updating
        $check_stmt = $conn->prepare("SELECT product_quantity FROM products WHERE product_id = ?");
        $check_stmt->bind_param("i", $order['product_id']);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $product = $result->fetch_assoc();
        
        if ($product['product_quantity'] < $order['quantity']) {
            throw new Exception("Not enough inventory for product ID: " . $order['product_id']);
        }
        
        // Update product quantity
        $update_stmt = $conn->prepare("UPDATE products SET product_quantity = product_quantity - ? WHERE product_id = ?");
        if (!$update_stmt) {
            throw new Exception("Prepare update failed: " . $conn->error);
        }
        
        $update_stmt->bind_param("ii", $order['quantity'], $order['product_id']);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Execute update failed: " . $update_stmt->error);
        }
    }
    
    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $transaction_success = false;
    $_SESSION['checkout_error'] = "An error occurred while processing your order: " . $e->getMessage();
    header("Location: cart.php");
    exit();
}

// Clear cart and payment session data only if the transaction was successful
if ($transaction_success) {
    unset($_SESSION['cart']);
    unset($_SESSION['total']);
    unset($_SESSION['quantity']);
    unset($_SESSION['payment_method']);
    unset($_SESSION['payment_processed']);

    header("Location: success.php");
    exit();
}
?>
