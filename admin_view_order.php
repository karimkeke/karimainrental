<?php
session_start();
include('connection.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('location: admin.php');
    exit;
}

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('location: admin_orders.php');
    exit;
}

$order_id = $_GET['id'];
$success_message = '';
$errors = [];

// Handle status update if submitted
if (isset($_POST['update_status'])) {
    $new_status = $_POST['order_status'];
    
    // Validate status
    $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (in_array($new_status, $valid_statuses)) {
        $update_query = "UPDATE orders SET order_status = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $new_status, $order_id);
        
        if ($stmt->execute()) {
            $success_message = "Order status updated successfully!";
        } else {
            $errors[] = "Error updating order status: " . $conn->error;
        }
    } else {
        $errors[] = "Invalid order status provided";
    }
}

// Get order details
$query = "SELECT orders.*, users.user_name, users.user_email 
          FROM orders
          LEFT JOIN users ON orders.user_id = users.user_id
          WHERE orders.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('location: admin_orders.php');
    exit;
}

$order = $result->fetch_assoc();

// Get order items
$order_items = [];

try {
    // Check if order_items table exists and create it if not
    $table_check = $conn->query("SHOW TABLES LIKE 'order_items'");
    if ($table_check->num_rows == 0) {
        // Table doesn't exist, create it
        $create_table_sql = "CREATE TABLE order_items (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            order_id INT(11) NOT NULL,
            product_id INT(11) NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            product_price DECIMAL(10,2) NOT NULL,
            product_quantity INT(11) NOT NULL,
            product_image VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (order_id),
            INDEX (product_id)
        )";
        
        if (!$conn->query($create_table_sql)) {
            throw new Exception("Failed to create order_items table: " . $conn->error);
        }
        
        $success_message = "Order items table was automatically created. You can now view order details properly.";
    }
    
    // Continue with the original query
    $items_query = "SELECT order_items.*, products.product_name, products.product_image 
                    FROM order_items
                    JOIN products ON order_items.product_id = products.product_id
                    WHERE order_items.order_id = ?";

    $stmt = $conn->prepare($items_query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $items_result = $stmt->get_result();
    
    if ($items_result->num_rows > 0) {
        while ($item = $items_result->fetch_assoc()) {
            $order_items[] = $item;
        }
    }
} catch (Exception $e) {
    // Handle any errors
    $errors[] = "Order items details are not available: " . $e->getMessage();
}

// Function to format date
function formatDate($date) {
    return date("F j, Y, g:i a", strtotime($date));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order #<?php echo $order_id; ?> - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
            --header-height: 60px;
            --primary-color: #000;
            --secondary-color: #f8f9fa;
            --accent-color: #333;
            --text-color: #333;
            --light-text: #666;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --success-color: #28a745;
            --info-color: #17a2b8;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            background-color: var(--secondary-color);
            color: var(--text-color);
            min-height: 100vh;
        }
        
        /* Admin Layout */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .admin-sidebar {
            width: var(--sidebar-width);
            background-color: var(--primary-color);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header h2 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .sidebar-user {
            font-size: 0.85rem;
            opacity: 0.8;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.2);
            border-left: 4px solid white;
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .logout-btn {
            margin-top: 20px;
            padding: 12px 20px;
            background-color: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
        }
        
        /* Main content */
        .admin-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
        }
        
        .admin-header {
            background-color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-header h1 {
            font-size: 1.5rem;
            color: var(--primary-color);
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 15px;
            background-color: var(--secondary-color);
            color: var(--text-color);
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background-color: #e9ecef;
        }
        
        .back-btn i {
            margin-right: 5px;
        }
        
        /* Alert messages */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        /* Order details styling */
        .section-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .order-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--light-text);
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .info-value {
            font-size: 1rem;
            margin-bottom: 15px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        
        .status-pending {
            background-color: rgba(255, 193, 7, 0.2);
            color: #d39e00;
        }
        
        .status-processing {
            background-color: rgba(23, 162, 184, 0.2);
            color: #138496;
        }
        
        .status-shipped {
            background-color: rgba(40, 167, 69, 0.2);
            color: #1e7e34;
        }
        
        .status-delivered {
            background-color: rgba(40, 167, 69, 0.2);
            color: #1e7e34;
        }
        
        .status-cancelled {
            background-color: rgba(220, 53, 69, 0.2);
            color: #bd2130;
        }
        
        /* Items table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .items-table th,
        .items-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .items-table th {
            font-weight: 600;
            color: var(--light-text);
            font-size: 0.9rem;
            text-transform: uppercase;
            background-color: #f8f9fa;
        }
        
        .items-table tr:last-child td {
            border-bottom: none;
        }
        
        .product-info {
            display: flex;
            align-items: center;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            border-radius: 4px;
            object-fit: cover;
            margin-right: 15px;
        }
        
        .product-name {
            font-weight: 600;
        }
        
        .item-price,
        .item-quantity,
        .item-total {
            font-weight: 500;
        }
        
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
            font-size: 1.1rem;
        }
        
        /* Status update form */
        .status-form {
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .form-select {
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-family: 'Cairo', sans-serif;
            min-width: 200px;
        }
        
        .submit-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            background-color: var(--accent-color);
        }
        
        .submit-btn i {
            margin-right: 5px;
        }
        
        /* Print button */
        .print-btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 15px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-left: 10px;
            border: none;
            font-family: 'Cairo', sans-serif;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
        }
        
        .print-btn:hover {
            background-color: var(--accent-color);
        }
        
        .print-btn i {
            margin-right: 5px;
        }
        
        /* Payment details */
        .payment-method {
            text-transform: capitalize;
            font-weight: 500;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 70px;
                overflow: visible;
            }
            
            .sidebar-header h2,
            .sidebar-user,
            .sidebar-menu span {
                display: none;
            }
            
            .admin-content {
                margin-left: 70px;
            }
            
            .sidebar-menu a {
                padding: 15px;
                justify-content: center;
            }
            
            .sidebar-menu i {
                margin-right: 0;
                font-size: 1.2rem;
            }
            
            .order-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .items-table {
                font-size: 0.85rem;
            }
            
            .items-table th,
            .items-table td {
                padding: 10px;
            }
            
            .product-image {
                width: 40px;
                height: 40px;
            }
            
            .status-form {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .form-select {
                width: 100%;
            }
        }
        
        @media print {
            .admin-sidebar,
            .admin-header,
            .status-form,
            .alert {
                display: none;
            }
            
            .admin-content {
                margin-left: 0;
                padding: 0;
            }
            
            body {
                background-color: white;
            }
            
            .section-container {
                box-shadow: none;
                margin-bottom: 15px;
                break-inside: avoid;
            }
            
            .section-title {
                font-size: 14pt;
            }
            
            .info-label {
                font-size: 10pt;
            }
            
            .info-value {
                font-size: 12pt;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
                <div class="sidebar-user">
                    <?php echo isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin'; ?>
                </div>
            </div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="admin_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="admin_products.php">
                        <i class="fas fa-couch"></i>
                        <span>Products</span>
                    </a>
                </li>
                <li>
                    <a href="admin_categories.php">
                        <i class="fas fa-tags"></i>
                        <span>Categories</span>
                    </a>
                </li>
                <li>
                    <a href="admin_orders.php" class="active">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Orders</span>
                    </a>
                </li>
                <li>
                    <a href="admin_users.php">
                        <i class="fas fa-users"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li>
                    <a href="admin_messages.php">
                        <i class="fas fa-comments"></i>
                        <span>Messages <?php if(isset($unread_messages_count) && $unread_messages_count > 0): ?><span class="badge" style="background-color: #dc3545; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem;"><?php echo $unread_messages_count; ?></span><?php endif; ?></span>
                    </a>
                </li>
                <li>
                    <a href="admin.php?logout=1" class="logout-btn" onclick="return confirm('Are you sure you want to logout?')">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-content">
            <div class="admin-header">
                <h1>Order #<?php echo $order_id; ?> Details</h1>
                <div>
                    <a href="admin_orders.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back to Orders
                    </a>
                    <button onclick="window.print()" class="print-btn">
                        <i class="fas fa-print"></i> Print Order
                    </button>
                </div>
            </div>
            
            <!-- Success or Error Messages -->
            <?php if(!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if(!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong>Please fix the following errors:</strong>
                        <ul class="error-list">
                            <?php foreach($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="section-container">
                <h3 class="section-title">Order Information</h3>
                <div class="order-grid">
                    <div>
                        <div class="info-label">Order Date</div>
                        <div class="info-value">
                            <?php 
                            if (isset($order['order_date'])) {
                                echo formatDate($order['order_date']);
                            } else if (isset($order['created_at'])) {
                                echo formatDate($order['created_at']);
                            } else {
                                echo "Date not available";
                            }
                            ?>
                        </div>
                        
                        <div class="info-label">Order Status</div>
                        <div class="info-value">
                            <span class="status-badge status-<?php echo strtolower($order['order_status']); ?>">
                                <?php echo ucfirst($order['order_status']); ?>
                            </span>
                        </div>
                        
                        <div class="info-label">Payment Method</div>
                        <div class="info-value payment-method"><?php echo isset($order['payment_method']) ? $order['payment_method'] : 'Cash on Delivery'; ?></div>
                    </div>
                    
                    <div>
                        <div class="info-label">Customer Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['user_name']); ?></div>
                        
                        <div class="info-label">Contact Information</div>
                        <div class="info-value">
                            Email: <?php echo htmlspecialchars($order['user_email']); ?><br>
                            <?php if (isset($order['user_phone'])): ?>
                            Phone: <?php echo htmlspecialchars($order['user_phone']); ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="info-label">Shipping Address</div>
                        <div class="info-value">
                            <?php
                            $address_display = 'N/A';
                            if (isset($order['user_address']) && !empty($order['user_address'])) {
                                $address_display = htmlspecialchars($order['user_address']);
                                if (isset($order['user_city']) && !empty($order['user_city'])) {
                                    $address_display .= ', ' . htmlspecialchars($order['user_city']);
                                }
                            }
                            echo $address_display;
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Order Status Update Form -->
                <form method="POST" action="admin_view_order.php?id=<?php echo $order_id; ?>" class="status-form">
                    <select name="order_status" class="form-select">
                        <option value="pending" <?php echo $order['order_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $order['order_status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="shipped" <?php echo $order['order_status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo $order['order_status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $order['order_status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    <button type="submit" name="update_status" class="submit-btn">
                        <i class="fas fa-save"></i> Update Status
                    </button>
                </form>
            </div>
            
            <div class="section-container">
                <h3 class="section-title">Order Items</h3>
                
                <?php if (empty($order_items)): ?>
                    <p>No items found for this order.</p>
                <?php else: ?>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $subtotal = 0;
                            foreach ($order_items as $item): 
                                $item_total = $item['product_price'] * $item['product_quantity'];
                                $subtotal += $item_total;
                            ?>
                                <tr>
                                    <td>
                                        <div class="product-info">
                                            <?php if (!empty($item['product_image']) && file_exists('assets/imgs/' . $item['product_image'])): ?>
                                                <img src="assets/imgs/<?php echo $item['product_image']; ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="product-image">
                                            <?php else: ?>
                                                <div class="product-image" style="background-color: #eee; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-image" style="color: #ccc;"></i>
                                                </div>
                                            <?php endif; ?>
                                            <span class="product-name"><?php echo htmlspecialchars($item['product_name']); ?></span>
                                        </div>
                                    </td>
                                    <td class="item-price"><?php echo number_format($item['product_price'], 2); ?> DA</td>
                                    <td class="item-quantity"><?php echo $item['product_quantity']; ?></td>
                                    <td class="item-total"><?php echo number_format($item_total, 2); ?> DA</td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <!-- Order Totals -->
                            <tr class="total-row">
                                <td colspan="3" style="text-align: right;">Subtotal:</td>
                                <td><?php echo number_format($subtotal, 2); ?> DA</td>
                            </tr>
                            <?php
                            // Calculate shipping if applicable
                            $shipping = isset($order['shipping_cost']) ? $order['shipping_cost'] : 0;
                            ?>
                            <tr class="total-row">
                                <td colspan="3" style="text-align: right;">Shipping:</td>
                                <td><?php echo number_format($shipping, 2); ?> DA</td>
                            </tr>
                            <tr class="total-row">
                                <td colspan="3" style="text-align: right;">Total:</td>
                                <td><?php echo number_format($subtotal + $shipping, 2); ?> DA</td>
                            </tr>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <?php if (isset($order['order_notes']) && !empty($order['order_notes'])): ?>
            <div class="section-container">
                <h3 class="section-title">Order Notes</h3>
                <p><?php echo nl2br(htmlspecialchars($order['order_notes'])); ?></p>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html> 