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

// Handle product deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = $_GET['delete'];
    
    // Get product images to delete files
    $image_query = "SELECT product_image, product_image2, product_image3, product_image4 FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($image_query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product_images = $result->fetch_assoc();
        
        // Delete the product from database
        $delete_query = "DELETE FROM products WHERE product_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $product_id);
        
        if ($stmt->execute()) {
            // Success message
            $success_message = "Product has been deleted successfully.";
            
            // Delete image files
            $image_dir = "assets/imgs/";
            if (!empty($product_images['product_image']) && file_exists($image_dir . $product_images['product_image'])) {
                unlink($image_dir . $product_images['product_image']);
            }
            if (!empty($product_images['product_image2']) && file_exists($image_dir . $product_images['product_image2'])) {
                unlink($image_dir . $product_images['product_image2']);
            }
            if (!empty($product_images['product_image3']) && file_exists($image_dir . $product_images['product_image3'])) {
                unlink($image_dir . $product_images['product_image3']);
            }
            if (!empty($product_images['product_image4']) && file_exists($image_dir . $product_images['product_image4'])) {
                unlink($image_dir . $product_images['product_image4']);
            }
        } else {
            $error_message = "Failed to delete product. Error: " . $conn->error;
        }
    } else {
        $error_message = "Product not found.";
    }
}

// Get filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$low_stock = isset($_GET['filter']) && $_GET['filter'] == 'low_stock';

// Build query
$query = "SELECT p.*, c.category_name FROM products p 
          LEFT JOIN categories c ON p.category = c.category_id 
          WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (p.product_name LIKE ? OR p.product_description LIKE ?)";
    $search_param = "%" . $search . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($category_filter)) {
    $query .= " AND p.category = ?";
    $params[] = $category_filter;
    $types .= "i";
}

if ($low_stock) {
    $query .= " AND p.product_quantity <= 3";
}

$query .= " ORDER BY p.created_at DESC";

// Prepare and execute the query
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$products = $stmt->get_result();

// Get all categories for filter dropdown
$categories_query = "SELECT category_id, category_name FROM categories ORDER BY category_name";
$categories_result = $conn->query($categories_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Admin Panel</title>
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
        
        /* Products management */
        .products-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .add-product-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .add-product-btn:hover {
            background-color: var(--accent-color);
            transform: translateY(-2px);
        }
        
        .add-product-btn i {
            margin-right: 8px;
        }
        
        .search-filter-container {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .search-box {
            display: flex;
            align-items: center;
        }
        
        .search-input {
            padding: 8px 15px;
            border: 1px solid #eee;
            border-radius: 8px 0 0 8px;
            font-family: 'Cairo', sans-serif;
            width: 250px;
        }
        
        .search-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 0 8px 8px 0;
            cursor: pointer;
        }
        
        .category-filter {
            padding: 8px 15px;
            border: 1px solid #eee;
            border-radius: 8px;
            font-family: 'Cairo', sans-serif;
        }
        
        /* Products table */
        .products-table-container {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .products-table th,
        .products-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .products-table th {
            background-color: rgba(0, 0, 0, 0.02);
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .products-table tr:last-child td {
            border-bottom: none;
        }
        
        .products-table tr:hover {
            background-color: rgba(0, 0, 0, 0.01);
        }
        
        .product-thumbnail {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .product-actions {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s ease;
        }
        
        .edit-btn {
            background-color: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
        }
        
        .edit-btn:hover {
            background-color: rgba(23, 162, 184, 0.2);
        }
        
        .delete-btn {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .delete-btn:hover {
            background-color: rgba(220, 53, 69, 0.2);
        }
        
        .action-btn i {
            margin-right: 5px;
        }
        
        .stock-warning {
            color: var(--danger-color);
            font-weight: 600;
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
        
        /* Modal styles */
        .modal-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1100;
            align-items: center;
            justify-content: center;
        }
        
        .modal-container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h2 {
            font-size: 1.5rem;
            color: var(--primary-color);
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--light-text);
            transition: color 0.3s ease;
        }
        
        .modal-close:hover {
            color: var(--danger-color);
        }
        
        .modal-body {
            margin-bottom: 20px;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .modal-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-confirm {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-cancel {
            background-color: var(--light-text);
            color: white;
        }
        
        .modal-btn:hover {
            opacity: 0.9;
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
            
            .products-table th:nth-child(3),
            .products-table td:nth-child(3),
            .products-table th:nth-child(4),
            .products-table td:nth-child(4) {
                display: none;
            }
        }
        
        @media (max-width: 576px) {
            .products-actions {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-filter-container {
                width: 100%;
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-box {
                width: 100%;
            }
            
            .search-input {
                width: 100%;
            }
            
            .category-filter {
                width: 100%;
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
                    <a href="admin_products.php" class="active">
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
                <h1>Product Management</h1>
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
            
            <!-- Products Actions -->
            <div class="products-actions">
                <a href="admin_add_product.php" class="add-product-btn">
                    <i class="fas fa-plus"></i> Add New Product
                </a>
                
                <div class="search-filter-container">
                    <form action="admin_products.php" method="GET" class="search-box">
                        <input type="text" name="search" class="search-input" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="search-button">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                    
                    <form action="admin_products.php" method="GET">
                        <select name="category" class="category-filter" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php while($cat = $categories_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($cat['category_id']); ?>" <?php echo $category_filter == $cat['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($cat['category_name'])); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </form>
                    
                    <?php if($low_stock): ?>
                        <a href="admin_products.php" style="text-decoration: none; color: #dc3545; display: flex; align-items: center;">
                            <i class="fas fa-times-circle" style="margin-right: 5px;"></i> Clear low stock filter
                        </a>
                    <?php else: ?>
                        <a href="admin_products.php?filter=low_stock" style="text-decoration: none; color: #ffc107; display: flex; align-items: center;">
                            <i class="fas fa-exclamation-triangle" style="margin-right: 5px;"></i> Show low stock only
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Products Table -->
            <div class="products-table-container">
                <?php if($products && $products->num_rows > 0): ?>
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($product = $products->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo file_exists('assets/imgs/' . $product['product_image']) ? 'assets/imgs/' . $product['product_image'] : 'https://via.placeholder.com/60x60' ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="product-thumbnail">
                                    </td>
                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($product['category_name'])); ?></td>
                                    <td><?php echo number_format($product['product_price']); ?> DA</td>
                                    <td class="<?php echo $product['product_quantity'] <= 3 ? 'stock-warning' : ''; ?>">
                                        <?php echo $product['product_quantity']; ?> units
                                    </td>
                                    <td>
                                        <div class="product-actions">
                                            <a href="admin_edit_product.php?id=<?php echo $product['product_id']; ?>" class="action-btn edit-btn">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <button class="action-btn delete-btn" onclick="confirmDelete(<?php echo $product['product_id']; ?>, '<?php echo htmlspecialchars(addslashes($product['product_name'])); ?>')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-box-open"></i>
                        <h3>No products found</h3>
                        <p>Try adjusting your search or filters, or add a new product.</p>
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
    
    <!-- Delete Confirmation Modal -->
    <div class="modal-backdrop" id="deleteModal">
        <div class="modal-container">
            <div class="modal-header">
                <h2>Confirm Deletion</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the product <strong id="productName"></strong>?</p>
                <p>This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn btn-cancel" onclick="closeModal()">Cancel</button>
                <button class="modal-btn btn-confirm" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
    
    <script>
        // Delete confirmation modal
        function confirmDelete(productId, productName) {
            document.getElementById('productName').innerText = productName;
            document.getElementById('confirmDeleteBtn').onclick = function() {
                window.location.href = 'admin_products.php?delete=' + productId;
            };
            document.getElementById('deleteModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html> 