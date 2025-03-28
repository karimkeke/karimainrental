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

// Initialize variables
$errors = [];
$success_message = "";
$categories = [];
$products = [];
$has_description_column = false;

// Handle success messages from redirects
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'added':
            $name = isset($_GET['name']) ? urldecode($_GET['name']) : 'Category';
            $success_message = "{$name} has been added successfully!";
            break;
        case 'edited':
            $name = isset($_GET['name']) ? urldecode($_GET['name']) : 'Category';
            $success_message = "{$name} has been updated successfully!";
            break;
        case 'deleted':
            $success_message = "Category has been deleted successfully!";
            break;
        case 'assigned':
            $name = isset($_GET['name']) ? urldecode($_GET['name']) : 'Selected category';
            $count = isset($_GET['count']) ? intval($_GET['count']) : 0;
            $success_message = "Successfully assigned $count products to $name.";
            break;
    }
}

// Check if categories table exists and has required columns
$check_table = $conn->query("SHOW TABLES LIKE 'categories'");
if ($check_table->num_rows == 0) {
    $create_table_sql = "CREATE TABLE categories (
        category_id INT(11) AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(255) NOT NULL UNIQUE,
        category_description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_table_sql)) {
        $success_message = "Categories table created successfully.";
        $has_description_column = true;
        
        // Insert default category
        $insert_default = $conn->query("INSERT INTO categories (category_name, category_description) VALUES ('Uncategorized', 'Default category for all products')");
        if ($insert_default) {
            $success_message .= " Default category added.";
        }
    } else {
        $errors[] = "Error creating categories table: " . $conn->error;
    }
} else {
    // Check if category_description column exists
    $check_description = $conn->query("SHOW COLUMNS FROM categories LIKE 'category_description'");
    $has_description_column = ($check_description->num_rows > 0);
    
    if (!$has_description_column) {
        $errors[] = "The 'category_description' column is missing in the categories table. <a href='fix_categories_table.php' class='btn btn-success' style='margin-left: 10px;'>Click here to fix</a>";
    }
}

// Check if products table has category column of correct type
$check_column = $conn->query("SHOW COLUMNS FROM products LIKE 'category'");
if ($check_column->num_rows > 0) {
    $column_info = $check_column->fetch_assoc();
    if (strpos(strtolower($column_info['Type']), 'int') === false) {
        // Category column exists but is not INT type - add link to fix script
        $errors[] = "Products table has 'category' column but it's not the correct type. <a href='fix_category_column.php' class='btn btn-success' style='margin-left: 10px;'>Click here to fix</a>";
    }
} else {
    // Add category column if it doesn't exist
    $add_column = $conn->query("ALTER TABLE products ADD COLUMN category INT(11) DEFAULT NULL");
    if ($add_column) {
        $success_message .= " Category column added to products table.";
    } else {
        $errors[] = "Unable to add category column to products table: " . $conn->error;
    }
}

// Handle category deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $category_id = $_GET['delete'];
    
    // Check if category is in use by products
    $check_usage = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category = ?");
    $check_usage->bind_param("i", $category_id);
    $check_usage->execute();
    $result = $check_usage->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        $errors[] = "Cannot delete category because it is used by {$row['count']} products. Reassign products first.";
    } else {
        // Get category name for message
        $name_stmt = $conn->prepare("SELECT category_name FROM categories WHERE category_id = ?");
        $name_stmt->bind_param("i", $category_id);
        $name_stmt->execute();
        $name_result = $name_stmt->get_result();
        $category_name = ($name_result->num_rows > 0) ? $name_result->fetch_assoc()['category_name'] : 'Unknown';
        
        // Delete the category
        $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->bind_param("i", $category_id);
        
        if ($stmt->execute()) {
            // Redirect to avoid refresh issues
            header("Location: admin_categories.php?success=deleted&name=" . urlencode($category_name));
            exit;
        } else {
            $errors[] = "Error deleting category: " . $conn->error;
        }
    }
}

// Handle category form submission (add/edit)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'add_category') {
        // Add new category
        if (empty($_POST['category_name'])) {
            $errors[] = "Category name is required";
        } else {
            $name = trim($_POST['category_name']);
            if (strlen($name) < 2) {
                $errors[] = "Category name must be at least 2 characters long";
            } elseif (strlen($name) > 50) {
                $errors[] = "Category name must be less than 50 characters long";
            } else {
                $description = isset($_POST['category_description']) ? trim($_POST['category_description']) : '';
                
                if ($has_description_column) {
                    $stmt = $conn->prepare("INSERT INTO categories (category_name, category_description) VALUES (?, ?)");
                    $stmt->bind_param("ss", $name, $description);
                } else {
                    $stmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
                    $stmt->bind_param("s", $name);
                }
                
                if ($stmt->execute()) {
                    $success_message = "Category '{$name}' added successfully!";
                    // Redirect to avoid form resubmission
                    header("Location: admin_categories.php?success=added&name=" . urlencode($name));
                    exit;
                } else {
                    if ($conn->errno == 1062) { // Duplicate entry error
                        $errors[] = "Category name '{$name}' already exists. Please use a different name.";
                    } else {
                        $errors[] = "Error adding category: " . $conn->error;
                    }
                }
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'edit_category') {
        // Edit existing category
        if (empty($_POST['category_name']) || !isset($_POST['category_id']) || !is_numeric($_POST['category_id'])) {
            $errors[] = "Category name and ID are required";
        } else {
            $name = trim($_POST['category_name']);
            if (strlen($name) < 2) {
                $errors[] = "Category name must be at least 2 characters long";
            } elseif (strlen($name) > 50) {
                $errors[] = "Category name must be less than 50 characters long";
            } else {
                $description = isset($_POST['category_description']) ? trim($_POST['category_description']) : '';
                $category_id = $_POST['category_id'];
                
                if ($has_description_column) {
                    $stmt = $conn->prepare("UPDATE categories SET category_name = ?, category_description = ? WHERE category_id = ?");
                    $stmt->bind_param("ssi", $name, $description, $category_id);
                } else {
                    $stmt = $conn->prepare("UPDATE categories SET category_name = ? WHERE category_id = ?");
                    $stmt->bind_param("si", $name, $category_id);
                }
                
                if ($stmt->execute()) {
                    // Redirect to avoid form resubmission
                    header("Location: admin_categories.php?success=edited&name=" . urlencode($name));
                    exit;
                } else {
                    if ($conn->errno == 1062) { // Duplicate entry error
                        $errors[] = "Category name '{$name}' already exists. Please use a different name.";
                    } else {
                        $errors[] = "Error updating category: " . $conn->error;
                    }
                }
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'assign_products') {
        // Assign products to category
        if (!isset($_POST['category_id']) || !is_numeric($_POST['category_id'])) {
            $errors[] = "Category ID is required";
        } elseif (!isset($_POST['product_ids']) || !is_array($_POST['product_ids']) || empty($_POST['product_ids'])) {
            $errors[] = "Please select at least one product";
        } else {
            $category_id = $_POST['category_id'];
            $product_ids = $_POST['product_ids'];
            
            // Get category name for message
            $name_stmt = $conn->prepare("SELECT category_name FROM categories WHERE category_id = ?");
            $name_stmt->bind_param("i", $category_id);
            $name_stmt->execute();
            $name_result = $name_stmt->get_result();
            $category_name = ($name_result->num_rows > 0) ? $name_result->fetch_assoc()['category_name'] : 'Selected category';
            
            // First, reset all products from this category that are not in the selection
            $reset_stmt = $conn->prepare("UPDATE products SET category = NULL WHERE category = ? AND product_id NOT IN (" . implode(',', array_fill(0, count($product_ids), '?')) . ")");
            $reset_params = array_merge(["i"], $product_ids);
            $reset_types = str_repeat("i", count($product_ids));
            $reset_stmt->bind_param("i" . $reset_types, $category_id, ...$product_ids);
            $reset_stmt->execute();
            
            // Update products
            $stmt = $conn->prepare("UPDATE products SET category = ? WHERE product_id = ?");
            $updated_count = 0;
            
            foreach ($product_ids as $product_id) {
                $stmt->bind_param("ii", $category_id, $product_id);
                if ($stmt->execute()) {
                    $updated_count++;
                }
            }
            
            if ($updated_count > 0) {
                // Redirect to avoid form resubmission
                header("Location: admin_categories.php?success=assigned&name=" . urlencode($category_name) . "&count=" . $updated_count);
                exit;
            } else {
                $errors[] = "Error assigning products to category: " . $conn->error;
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'assign_unassigned') {
        if (!isset($_POST['target_category']) || !is_numeric($_POST['target_category'])) {
            $errors[] = "Target category ID is required";
        } else {
            $target_category = $_POST['target_category'];
            
            // Get category name for message
            $name_stmt = $conn->prepare("SELECT category_name FROM categories WHERE category_id = ?");
            $name_stmt->bind_param("i", $target_category);
            $name_stmt->execute();
            $name_result = $name_stmt->get_result();
            $category_name = ($name_result->num_rows > 0) ? $name_result->fetch_assoc()['category_name'] : 'Selected category';
            
            // Update all unassigned products
            $stmt = $conn->prepare("UPDATE products SET category = ? WHERE category IS NULL OR category = 0");
            $stmt->bind_param("i", $target_category);
            
            if ($stmt->execute()) {
                $update_count = $stmt->affected_rows;
                // Redirect to avoid form resubmission
                header("Location: admin_categories.php?success=assigned&name=" . urlencode($category_name) . "&count=" . $update_count);
                exit;
            } else {
                $errors[] = "Error assigning unassigned products: " . $conn->error;
            }
        }
    }
}

// Get all categories
$categories_result = $conn->query("SELECT c.*, 
                                   (SELECT COUNT(*) FROM products WHERE category = c.category_id) as product_count 
                                   FROM categories c
                                   ORDER BY c.category_name");

if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Get all products for assignment
$products_result = $conn->query("SELECT product_id, product_name, category FROM products ORDER BY product_name");
if ($products_result) {
    while ($row = $products_result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Get unassigned products (category is 0, NULL, or category doesn't exist)
$unassigned_products = [];
foreach ($products as $product) {
    $is_assigned = false;
    foreach ($categories as $category) {
        if ($product['category'] == $category['category_id']) {
            $is_assigned = true;
            break;
        }
    }
    if (!$is_assigned) {
        $unassigned_products[] = $product;
    }
}

// Get data for editing
$edit_category = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $category_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM categories WHERE category_id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_category = $result->fetch_assoc();
    }
}

// Get data for assigning products
$assign_category = null;
if (isset($_GET['assign']) && is_numeric($_GET['assign'])) {
    $category_id = $_GET['assign'];
    $stmt = $conn->prepare("SELECT * FROM categories WHERE category_id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $assign_category = $result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management - Admin Panel</title>
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
        
        .admin-header .actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--accent-color);
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            color: var(--text-color);
        }
        
        .btn-secondary:hover {
            background-color: #e9ecef;
        }
        
        .btn-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn-info {
            background-color: var(--info-color);
            color: white;
        }
        
        .btn-info:hover {
            background-color: #138496;
        }
        
        .btn i {
            margin-right: 5px;
        }
        
        /* Alert messages */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 1.2rem;
            margin-top: 2px;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        /* Categories table */
        .table-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .table th {
            font-weight: 600;
            color: var(--light-text);
            font-size: 0.9rem;
            text-transform: uppercase;
            background-color: #f8f9fa;
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .table tr:hover {
            background-color: #f8f9fa;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-primary {
            background-color: rgba(0, 0, 0, 0.1);
            color: var(--primary-color);
        }
        
        .badge-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .badge-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        /* Forms */
        .form-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .form-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-color);
        }
        
        .form-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-family: 'Cairo', sans-serif;
            transition: border 0.3s ease;
        }
        
        .form-input:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .form-textarea {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-family: 'Cairo', sans-serif;
            min-height: 100px;
            resize: vertical;
        }
        
        .form-textarea:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        .product-list-container {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 10px;
            margin-top: 10px;
        }
        
        .product-list {
            list-style: none;
        }
        
        .product-list li {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .product-list li:last-child {
            border-bottom: none;
        }
        
        .product-list .product-checkbox {
            margin-right: 8px;
        }
        
        .product-category {
            font-size: 0.85rem;
            color: var(--light-text);
            margin-left: 25px;
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
            
            .admin-header {
                flex-direction: column;
                align-items: start;
                gap: 10px;
            }
            
            .admin-header .actions {
                width: 100%;
                justify-content: space-between;
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
                    <a href="admin_categories.php" class="active">
                        <i class="fas fa-tags"></i>
                        <span>Categories</span>
                    </a>
                </li>
                <li>
                    <a href="admin_orders.php">
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
                <h1>Category Management</h1>
                <div class="actions">
                    <a href="admin_categories.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> All Categories
                    </a>
                    <a href="admin_categories.php?action=add" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add New Category
                    </a>
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
                        <ul>
                            <?php foreach($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['action']) && $_GET['action'] == 'add' || isset($edit_category)): ?>
                <!-- Add/Edit Category Form -->
                <div class="form-container">
                    <h3 class="form-title"><?php echo isset($edit_category) ? 'Edit Category' : 'Add New Category'; ?></h3>
                    
                    <form method="POST" action="admin_categories.php">
                        <input type="hidden" name="action" value="<?php echo isset($edit_category) ? 'edit_category' : 'add_category'; ?>">
                        <?php if (isset($edit_category)): ?>
                            <input type="hidden" name="category_id" value="<?php echo $edit_category['category_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="category_name" class="form-label">Category Name</label>
                            <input type="text" id="category_name" name="category_name" class="form-input" 
                                   value="<?php echo isset($edit_category) ? htmlspecialchars($edit_category['category_name']) : ''; ?>" required>
                        </div>
                        
                        <?php if ($has_description_column): ?>
                        <div class="form-group">
                            <label for="category_description" class="form-label">Description (Optional)</label>
                            <textarea id="category_description" name="category_description" class="form-textarea"><?php echo isset($edit_category) ? htmlspecialchars($edit_category['category_description']) : ''; ?></textarea>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info" style="margin-bottom: 20px;">
                            <p><i class="fas fa-info-circle"></i> Description field is unavailable. <a href="fix_categories_table.php">Fix the table structure</a> to enable descriptions.</p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-actions">
                            <a href="admin_categories.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo isset($edit_category) ? 'Update Category' : 'Add Category'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php elseif (isset($assign_category)): ?>
                <!-- Assign Products to Category Form -->
                <div class="form-container">
                    <h3 class="form-title">Assign Products to: <?php echo htmlspecialchars($assign_category['category_name']); ?></h3>
                    
                    <form method="POST" action="admin_categories.php">
                        <input type="hidden" name="action" value="assign_products">
                        <input type="hidden" name="category_id" value="<?php echo $assign_category['category_id']; ?>">
                        
                        <div class="form-group">
                            <label class="form-label">Select Products</label>
                            <div class="product-list-container">
                                <ul class="product-list">
                                    <?php foreach($products as $product): ?>
                                        <li>
                                            <label>
                                                <input type="checkbox" name="product_ids[]" value="<?php echo $product['product_id']; ?>" 
                                                       class="product-checkbox" <?php echo ($product['category'] == $assign_category['category_id']) ? 'checked' : ''; ?>>
                                                <?php echo htmlspecialchars($product['product_name']); ?>
                                            </label>
                                            <div class="product-category">
                                                Currently in: 
                                                <?php 
                                                    $found = false;
                                                    foreach($categories as $cat) {
                                                        if ($product['category'] == $cat['category_id']) {
                                                            echo htmlspecialchars($cat['category_name']);
                                                            $found = true;
                                                            break;
                                                        }
                                                    }
                                                    if (!$found) echo 'Uncategorized';
                                                ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($products)): ?>
                                        <li>No products available.</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <a href="admin_categories.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Product Assignments
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- Categories List -->
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Category Name</th>
                                <th>Description</th>
                                <th>Products</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">No categories found. Add your first category!</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($categories as $category): ?>
                                    <tr>
                                        <td><?php echo $category['category_id']; ?></td>
                                        <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                        <td><?php echo !empty($category['category_description']) ? htmlspecialchars(substr($category['category_description'], 0, 50)) . (strlen($category['category_description']) > 50 ? '...' : '') : '-'; ?></td>
                                        <td>
                                            <span class="badge <?php echo $category['product_count'] > 0 ? 'badge-success' : 'badge-primary'; ?>">
                                                <?php echo $category['product_count']; ?> products
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="admin_categories.php?assign=<?php echo $category['category_id']; ?>" class="btn btn-info btn-sm" title="Assign Products">
                                                    <i class="fas fa-link"></i>
                                                </a>
                                                <a href="admin_categories.php?edit=<?php echo $category['category_id']; ?>" class="btn btn-primary btn-sm" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="admin_categories.php?delete=<?php echo $category['category_id']; ?>" class="btn btn-danger btn-sm" title="Delete" 
                                                   onclick="return confirm('Are you sure you want to delete this category?');">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Unassigned Products -->
                <?php if (!empty($unassigned_products)): ?>
                <div class="form-container">
                    <h3 class="form-title">Unassigned Products</h3>
                    <p>The following products are not assigned to any category:</p>
                    
                    <div class="product-list-container">
                        <ul class="product-list">
                            <?php foreach($unassigned_products as $product): ?>
                                <li><?php echo htmlspecialchars($product['product_name']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <!-- Bulk assign unassigned products form -->
                    <form method="POST" action="admin_categories.php" style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px;">
                        <input type="hidden" name="action" value="assign_unassigned">
                        
                        <div class="form-group">
                            <label for="target_category" class="form-label">Assign all unassigned products to:</label>
                            <select id="target_category" name="target_category" class="form-input" required>
                                <option value="">Select a category</option>
                                <?php foreach($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>">
                                        <?php echo htmlspecialchars($category['category_name']); ?> 
                                        (<?php echo $category['product_count']; ?> products)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <a href="admin_products.php" class="btn btn-secondary">
                                <i class="fas fa-couch"></i> Manage Products
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-link"></i> Assign All Unassigned Products
                            </button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</body>
</html> 