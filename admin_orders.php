<?php
session_start();
include('connection.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('location: admin.php');
    exit;
}

// Get unread message count
$table_check = $conn->query("SHOW TABLES LIKE 'messages'");
$unread_messages_count = 0;

if ($table_check->num_rows > 0) {
    $unread_messages_query = "SELECT COUNT(*) as unread_count FROM messages WHERE is_from_admin = 0 AND is_read = 0";
    $unread_messages_result = $conn->query($unread_messages_query);
    $unread_messages_count = $unread_messages_result->fetch_assoc()['unread_count'];
}

// Check if order_status column exists in orders table, and create it if it doesn't
$check_column = $conn->query("SHOW COLUMNS FROM orders LIKE 'order_status'");
if ($check_column->num_rows == 0) {
    // Column doesn't exist, let's create it
    $add_column = $conn->query("ALTER TABLE orders ADD COLUMN order_status VARCHAR(50) DEFAULT 'pending'");
    if (!$add_column) {
        $error_message = "Error setting up order status system: " . $conn->error;
    } else {
        $success_message = "Order status column added successfully.";
    }
}

// Handle order status update
if (isset($_GET['update_status']) && is_numeric($_GET['update_status']) && isset($_GET['status'])) {
    $order_id = $_GET['update_status'];
    $new_status = $_GET['status'];
    
    // Valid status values
    $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'canceled'];
    
    if (in_array($new_status, $valid_statuses)) {
        // Update the order status
        $update_query = "UPDATE orders SET order_status = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $new_status, $order_id);
        
        if ($stmt->execute()) {
            $success_message = "Order #" . $order_id . " status updated to '" . ucfirst($new_status) . "'.";
        } else {
            $error_message = "Failed to update order status. Error: " . $conn->error;
        }
    } else {
        $error_message = "Invalid status value.";
    }
}

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build the query based on filters
$query = "SELECT o.*, u.user_name, u.user_email, 
          (SELECT SUM(total_price) FROM orders WHERE user_id = o.user_id) as customer_lifetime_value
          FROM orders o
          LEFT JOIN users u ON o.user_id = u.user_id
          WHERE 1=1";

$params = [];
$types = "";

if (!empty($filter)) {
    $query .= " AND o.order_status = ?";
    $params[] = $filter;
    $types .= "s";
}

if (!empty($search)) {
    $search_term = "%$search%";
    $query .= " AND (u.user_name LIKE ? OR u.user_email LIKE ? OR o.id LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

if (!empty($date_from)) {
    $query .= " AND o.created_at >= ?";
    $params[] = $date_from . ' 00:00:00';
    $types .= "s";
}

if (!empty($date_to)) {
    $query .= " AND o.created_at <= ?";
    $params[] = $date_to . ' 23:59:59';
    $types .= "s";
}

$query .= " ORDER BY o.created_at DESC";

// Prepare and execute the query
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$orders = $stmt->get_result();

// Get order statistics
$stats_query = "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN order_status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
                SUM(CASE WHEN order_status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
                SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                SUM(CASE WHEN order_status = 'canceled' THEN 1 ELSE 0 END) as canceled_orders,
                SUM(total_price) as total_revenue
                FROM orders";

$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Admin Panel</title>
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
        
        /* Order Stats */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .revenue-stats {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.5rem;
        }
        
        .stat-icon.total-orders {
            background-color: rgba(23, 162, 184, 0.1);
            color: var(--info-color);
        }
        
        .stat-icon.pending-orders {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
        }
        
        .stat-icon.processing-orders {
            background-color: rgba(255, 122, 69, 0.1);
            color: #ff7a45;
        }
        
        .stat-icon.shipped-orders {
            background-color: rgba(0, 123, 255, 0.1);
            color: #007bff;
        }
        
        .stat-icon.delivered-orders {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }
        
        .stat-icon.canceled-orders {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }
        
        .stat-icon.total-revenue {
            background-color: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }
        
        .stat-info h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-info p {
            font-size: 0.85rem;
            color: var(--light-text);
        }
        
        /* Order Filters */
        .filters-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .filters-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-color);
        }
        
        .filter-select,
        .filter-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-family: 'Cairo', sans-serif;
        }
        
        .filter-select:focus,
        .filter-input:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .filter-buttons {
            display: flex;
            justify-content: flex-end;
            margin-top: 15px;
            gap: 10px;
        }
        
        .filter-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .apply-btn {
            background-color: var(--primary-color);
            color: white;
        }
        
        .reset-btn {
            background-color: #e9ecef;
            color: var(--text-color);
        }
        
        .filter-btn:hover {
            opacity: 0.9;
        }
        
        /* Orders table */
        .orders-table-container {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table th,
        .orders-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .orders-table th {
            background-color: rgba(0, 0, 0, 0.02);
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .orders-table tr:last-child td {
            border-bottom: none;
        }
        
        .orders-table tr:hover {
            background-color: rgba(0, 0, 0, 0.01);
        }
        
        .order-status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-pending {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
        }
        
        .status-processing {
            background-color: rgba(255, 122, 69, 0.1);
            color: #ff7a45;
        }
        
        .status-shipped {
            background-color: rgba(0, 123, 255, 0.1);
            color: #007bff;
        }
        
        .status-delivered {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }
        
        .status-canceled {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }
        
        .order-actions {
            display: flex;
            gap: 5px;
        }
        
        .action-btn {
            padding: 4px 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s ease;
        }
        
        .view-btn {
            background-color: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
        }
        
        .view-btn:hover {
            background-color: rgba(23, 162, 184, 0.2);
        }
        
        .action-btn i {
            margin-right: 5px;
        }
        
        /* Status Update Dropdown */
        .status-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .status-btn {
            background-color: rgba(108, 117, 125, 0.1);
            color: #6c757d;
            padding: 4px 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
        }
        
        .status-btn:hover {
            background-color: rgba(108, 117, 125, 0.2);
        }
        
        .status-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            z-index: 1;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .status-dropdown:hover .status-dropdown-content {
            display: block;
        }
        
        .status-option {
            color: var(--text-color);
            padding: 8px 16px;
            text-decoration: none;
            display: block;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }
        
        .status-option:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .status-option.pending {
            color: var(--warning-color);
        }
        
        .status-option.processing {
            color: #ff7a45;
        }
        
        .status-option.shipped {
            color: #007bff;
        }
        
        .status-option.delivered {
            color: var(--success-color);
        }
        
        .status-option.canceled {
            color: var(--danger-color);
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .pagination a {
            display: inline-block;
            padding: 8px 16px;
            margin: 0 4px;
            text-decoration: none;
            color: var(--primary-color);
            background-color: white;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .pagination a:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }
        
        .pagination a.active {
            background-color: var(--primary-color);
            color: white;
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
        
        /* No results */
        .no-results {
            text-align: center;
            padding: 30px;
            color: var(--light-text);
        }
        
        .no-results i {
            font-size: 3rem;
            margin-bottom: 15px;
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
            
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .orders-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
            
            .stats-container,
            .revenue-stats {
                grid-template-columns: 1fr;
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
                    <?php echo $_SESSION['admin_name']; ?>
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
                <h1>Order Management</h1>
                <a href="index.php" class="back-to-site">
                    <i class="fas fa-external-link-alt"></i> View Main Site
                </a>
            </div>
            
            <!-- Success or Error Messages -->
            <?php if(isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Order Stats -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon total-orders">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_orders'] ?? 0; ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon pending-orders">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['pending_orders'] ?? 0; ?></h3>
                        <p>Pending Orders</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon processing-orders">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['processing_orders'] ?? 0; ?></h3>
                        <p>Processing Orders</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon shipped-orders">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['shipped_orders'] ?? 0; ?></h3>
                        <p>Shipped Orders</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon delivered-orders">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['delivered_orders'] ?? 0; ?></h3>
                        <p>Delivered Orders</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon canceled-orders">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['canceled_orders'] ?? 0; ?></h3>
                        <p>Canceled Orders</p>
                    </div>
                </div>
                
                <div class="revenue-stats">
                    <div class="stat-card">
                        <div class="stat-icon total-revenue">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['total_revenue'] ?? 0); ?> DA</h3>
                            <p>Total Revenue</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Order Filters -->
            <div class="filters-container">
                <h3 class="filters-title">Filter Orders</h3>
                <form action="admin_orders.php" method="GET">
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label for="filter">Status</label>
                            <select id="filter" name="filter" class="filter-select">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo $filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo $filter == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo $filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="canceled" <?php echo $filter == 'canceled' ? 'selected' : ''; ?>>Canceled</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="search">Search</label>
                            <input type="text" id="search" name="search" class="filter-input" placeholder="Order #, Customer name or email" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_from">Date From</label>
                            <input type="date" id="date_from" name="date_from" class="filter-input" value="<?php echo $date_from; ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_to">Date To</label>
                            <input type="date" id="date_to" name="date_to" class="filter-input" value="<?php echo $date_to; ?>">
                        </div>
                    </div>
                    
                    <div class="filter-buttons">
                        <?php if(!empty($filter) || !empty($search) || !empty($date_from) || !empty($date_to)): ?>
                            <a href="admin_orders.php" class="filter-btn reset-btn">Reset Filters</a>
                        <?php endif; ?>
                        <button type="submit" class="filter-btn apply-btn">Apply Filters</button>
                    </div>
                </form>
            </div>
            
            <!-- Orders Table -->
            <div class="orders-table-container">
                <?php if($orders && $orders->num_rows > 0): ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($order = $orders->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td>
                                        <div>
                                            <?php echo htmlspecialchars($order['user_name'] ?? 'Unknown'); ?>
                                        </div>
                                        <div style="font-size: 0.85rem; color: var(--light-text);">
                                            <?php echo htmlspecialchars($order['user_email'] ?? ''); ?>
                                        </div>
                                        <div style="font-size: 0.85rem; color: var(--success-color);">
                                            Lifetime: <?php echo number_format($order['customer_lifetime_value'] ?? 0); ?> DA
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                            $product_query = "SELECT p.product_name, o.quantity, o.rental_length FROM orders o
                                                              JOIN products p ON o.product_id = p.product_id
                                                              WHERE o.id = ?";
                                            $product_stmt = $conn->prepare($product_query);
                                            $product_stmt->bind_param("i", $order['id']);
                                            $product_stmt->execute();
                                            $products_result = $product_stmt->get_result();
                                            
                                            if($products_result && $products_result->num_rows > 0) {
                                                $product = $products_result->fetch_assoc();
                                                echo htmlspecialchars($product['product_name']);
                                                echo " <small>x" . $product['quantity'] . "</small>";
                                                echo "<br><small>Rental: " . $product['rental_length'] . " days</small>";
                                            } else {
                                                echo "Unknown product";
                                            }
                                        ?>
                                    </td>
                                    <td><?php echo number_format($order['total_price']); ?> DA</td>
                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <span class="order-status status-<?php echo $order['order_status'] ?? 'pending'; ?>">
                                            <?php echo ucfirst($order['order_status'] ?? 'Pending'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="order-actions">
                                            <a href="admin_view_order.php?id=<?php echo $order['id']; ?>" class="action-btn view-btn">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            
                                            <div class="status-dropdown">
                                                <button class="status-btn">
                                                    <i class="fas fa-cog"></i> Status <i class="fas fa-caret-down" style="margin-left: 3px;"></i>
                                                </button>
                                                <div class="status-dropdown-content">
                                                    <a href="admin_orders.php?update_status=<?php echo $order['id']; ?>&status=pending" class="status-option pending">
                                                        <i class="fas fa-clock"></i> Pending
                                                    </a>
                                                    <a href="admin_orders.php?update_status=<?php echo $order['id']; ?>&status=processing" class="status-option processing">
                                                        <i class="fas fa-cogs"></i> Processing
                                                    </a>
                                                    <a href="admin_orders.php?update_status=<?php echo $order['id']; ?>&status=shipped" class="status-option shipped">
                                                        <i class="fas fa-truck"></i> Shipped
                                                    </a>
                                                    <a href="admin_orders.php?update_status=<?php echo $order['id']; ?>&status=delivered" class="status-option delivered">
                                                        <i class="fas fa-check-circle"></i> Delivered
                                                    </a>
                                                    <a href="admin_orders.php?update_status=<?php echo $order['id']; ?>&status=canceled" class="status-option canceled">
                                                        <i class="fas fa-times-circle"></i> Canceled
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>No orders found</h3>
                        <p>Try adjusting your filters or check back later.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Pagination (simplified, can be enhanced for a real application) -->
            <div class="pagination">
                <a href="#">&laquo;</a>
                <a href="#" class="active">1</a>
                <a href="#">2</a>
                <a href="#">3</a>
                <a href="#">&raquo;</a>
            </div>
        </main>
    </div>
</body>
</html> 