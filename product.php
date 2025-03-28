<?php
include('connection.php'); 

// Check if the category field in products table is an integer (using categories table)
$check_column = $conn->query("SHOW COLUMNS FROM products LIKE 'category'");
$is_category_int = false;

if ($check_column->num_rows > 0) {
    $column_info = $check_column->fetch_assoc();
    $is_category_int = (strpos(strtolower($column_info['Type']), 'int') !== false);
}

// Get all categories from the Category Management system
$db_categories = [];
$categories_db_query = "SELECT category_id, category_name FROM categories ORDER BY category_name";
$categories_db_result = $conn->query($categories_db_query);

if ($categories_db_result && $categories_db_result->num_rows > 0) {
    while ($cat = $categories_db_result->fetch_assoc()) {
        $db_categories[$cat['category_id']] = $cat['category_name'];
    }
}

// Get category from URL parameter or default to 'all'
$category = isset($_GET['category']) ? $_GET['category'] : 'all';

// Define the valid categories including ones from the database
$valid_categories = [
    'accentchair', 'bedframe', 'coffeetables', 'cornerdesk', 
    'desk', 'dresser', 'officechair', 'rectangle', 
    'round', 'sidetable', 'sofas', 'square', 'all'
];

// Add category IDs from database to valid categories if using integer category
if ($is_category_int) {
    foreach ($db_categories as $id => $name) {
        $valid_categories[] = (string)$id;
    }
}

// Validate the category parameter
if (!in_array($category, $valid_categories)) {
    $category = 'all';
}

// Query products based on category
if ($category == "all") {
    if ($is_category_int) {
        $query = "SELECT p.*, c.category_name FROM products p 
                  LEFT JOIN categories c ON p.category = c.category_id"; 
    } else {
        $query = "SELECT * FROM products"; 
    }
    $products = $conn->query($query);
} else {
    if ($is_category_int && is_numeric($category)) {
        // If using category IDs
        $query = "SELECT p.*, c.category_name FROM products p 
                  LEFT JOIN categories c ON p.category = c.category_id 
                  WHERE p.category = ?";
        $stmt = $conn->prepare($query);
        $cat_id = (int)$category;
        $stmt->bind_param("i", $cat_id);
    } else {
        // If using category string
        $query = "SELECT * FROM products WHERE category = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $category);
    }
    $stmt->execute();
    $products = $stmt->get_result();
}

// Get all available categories for the filter
if ($is_category_int) {
    $categories_query = "SELECT c.category_id, c.category_name, COUNT(p.product_id) as product_count
                         FROM categories c
                         LEFT JOIN products p ON c.category_id = p.category
                         GROUP BY c.category_id
                         HAVING product_count > 0
                         ORDER BY c.category_name";
} else {
    $categories_query = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category";
}
$categories_result = $conn->query($categories_query);

session_start();

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($category); ?> - Furniture Rental</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: #f8f9fa;
            font-family: 'Cairo', sans-serif;
            margin: 0;
            padding: 0;
        }

        .products-container {
            max-width: 1200px;
            margin: 100px auto 40px;
            padding: 0 20px;
        }

        .products-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }

        .products-header h1 {
            font-size: 32px;
            color: #000;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .products-header p {
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }

        .category-filter-section {
            max-width: 1000px;
            margin: 0 auto 30px;
        }

        .filter-title {
            text-align: center;
            margin-bottom: 15px;
            color: #333;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .filter-title i {
            color: #555;
        }

        .category-filter {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
            margin: 10px 0 30px;
            padding: 20px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            position: relative;
            z-index: 1;
        }

        .filter-button {
            padding: 12px 20px;
            background-color: #f8f9fa;
            border: 1px solid #eee;
            border-radius: 30px;
            color: #333;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            position: relative;
            overflow: hidden;
        }

        .filter-button i {
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .filter-button:hover {
            background-color: #f1f1f1;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }
        
        .filter-button:hover i {
            transform: scale(1.2);
        }

        .filter-button.active {
            background-color: var(--category-color, #000);
            color: white;
            border-color: var(--category-color, #000);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .filter-button.active.all-products {
            background-image: linear-gradient(to right, #e74c3c, #8e44ad, #3498db, #2ecc71, #f1c40f);
            border-color: #333;
        }

        .filter-button.active i {
            color: white !important;
        }
        
        .filter-button::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: -100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: all 0.6s ease;
        }
        
        .filter-button:hover::after {
            left: 100%;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 40px;
        }

        .product-card {
            background-color: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            height: 100%;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(0, 0, 0, 0.04);
        }

        .product-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 20px 30px rgba(0, 0, 0, 0.1);
        }

        .product-image {
            position: relative;
            overflow: hidden;
            height: 250px;
        }

        .product-image::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50%;
            background: linear-gradient(to top, rgba(0,0,0,0.03), transparent);
            z-index: 1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .product-card:hover .product-image::after {
            opacity: 1;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.08);
        }

        .limited-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background-color: #ff5252;
            color: white;
            font-size: 12px;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 20px;
            z-index: 2;
        }

        .product-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.7), transparent);
            display: flex;
            justify-content: center;
            opacity: 0;
            transition: all 0.4s ease;
            z-index: 2;
        }

        .product-card:hover .product-overlay {
            opacity: 1;
        }

        .view-button {
            padding: 10px 24px;
            background-color: #000;
            color: white;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(20px);
            opacity: 0;
        }

        .product-card:hover .view-button {
            transform: translateY(0);
            opacity: 1;
        }

        .view-button:hover {
            background-color: #333;
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .product-info {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }

        .product-info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 20px;
            right: 20px;
            height: 1px;
            background: rgba(0, 0, 0, 0.05);
        }

        .product-info h3 {
            margin: 0 0 10px;
            font-size: 18px;
            color: #000;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .product-card:hover .product-info h3 {
            color: #ff6b6b;
        }

        .price {
            color: #000;
            font-weight: 700;
            font-size: 18px;
            margin-top: 15px;
            display: flex;
            align-items: center;
        }

        .price::before {
            content: 'DA';
            font-size: 0.8rem;
            margin-right: 0.3rem;
            opacity: 0.8;
        }

        .product-description {
            color: #666;
            font-size: 14px;
            margin: 10px 0;
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

        /* Category badge styles */
        .category-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: var(--primary-color);
            color: white;
            padding: 7px 12px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            z-index: 2;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            opacity: 0.8;
        }
        
        .category-badge i {
            margin-right: 6px;
            font-size: 0.9rem;
        }
        
        .product-card:hover .category-badge {
            transform: translateY(-3px);
            opacity: 1;
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.15);
        }
        
        /* Product category styles */
        .product-category {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .product-category i {
            color: #3498db;
            font-size: 0.9rem;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .category-filter {
                overflow-x: auto;
                justify-content: flex-start;
                padding: 15px 20px;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: thin;
                scrollbar-color: #ddd #f8f9fa;
            }
            
            .category-filter::-webkit-scrollbar {
                height: 6px;
            }
            
            .category-filter::-webkit-scrollbar-track {
                background: #f8f9fa;
                border-radius: 10px;
            }
            
            .category-filter::-webkit-scrollbar-thumb {
                background-color: #ddd;
                border-radius: 10px;
            }
            
            .filter-button {
                font-size: 14px;
                padding: 10px 15px;
                white-space: nowrap;
            }
            
            .filter-title {
                font-size: 16px;
            }
            
            .category-badge {
                padding: 4px 8px;
                top: 10px;
                left: 10px;
            }
            
            .category-badge span {
                display: none;
            }
            
            .category-badge i {
                margin-right: 0;
                font-size: 1rem;
            }
        }

        @media (max-width: 480px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .products-header h1 {
                font-size: 24px;
            }
            
            .category-filter-section {
                margin-bottom: 20px;
            }
            
            .filter-button {
                padding: 8px 12px;
                font-size: 13px;
            }
            
            .filter-button i {
                font-size: 14px;
            }
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

    <div class="products-container">
        <div class="products-header">
            <h1><?php echo ucfirst($category) != 'All' ? ucfirst($category) . ' Collection' : 'All Furniture'; ?></h1>
            <p>Discover our premium selection of furniture available for rent. Choose from a variety of styles and categories.</p>
        </div>

        <div class="category-filter-section">
            <div class="filter-title">
                <i class="fas fa-filter"></i>
                Browse by Category
            </div>
            <div class="category-filter">
                <a href="product.php?category=all" class="filter-button <?php echo $category == 'all' ? 'active' : ''; ?> all-products" 
                   style="border-color: #333;">
                    <i class="fas fa-th-large" style="color: #333;"></i>
                    All Products
                </a>
                <?php 
                // Array of category icons mapping
                $category_icons = [
                    'accentchair' => 'fas fa-chair',
                    'bedframe' => 'fas fa-bed',
                    'coffeetables' => 'fas fa-coffee',
                    'cornerdesk' => 'fas fa-laptop',
                    'desk' => 'fas fa-desktop',
                    'dresser' => 'fas fa-archive',
                    'officechair' => 'fas fa-chair',
                    'rectangle' => 'fas fa-square',
                    'round' => 'fas fa-circle',
                    'sidetable' => 'fas fa-table',
                    'sofas' => 'fas fa-couch',
                    'square' => 'fas fa-square-full',
                    // Default icon for any other categories
                    'default' => 'fas fa-tags'
                ];
                
                // Category color mapping - unique color for each category
                $category_colors = [
                    'accentchair' => '#e74c3c',  // Red
                    'bedframe' => '#9b59b6',     // Purple
                    'coffeetables' => '#3498db', // Blue
                    'cornerdesk' => '#1abc9c',   // Turquoise
                    'desk' => '#2ecc71',         // Green
                    'dresser' => '#f1c40f',      // Yellow
                    'officechair' => '#e67e22',  // Orange
                    'rectangle' => '#34495e',    // Dark blue
                    'round' => '#16a085',        // Green-blue
                    'sidetable' => '#d35400',    // Dark orange
                    'sofas' => '#8e44ad',        // Violet
                    'square' => '#2980b9',       // Royal blue
                    'default' => '#7f8c8d'       // Gray
                ];
                
                // Map for category slugs to display names
                $category_display_names = [
                    'accentchair' => 'Accent Chair',
                    'bedframe' => 'Bed Frame',
                    'coffeetables' => 'Coffee Tables',
                    'cornerdesk' => 'Corner Desk',
                    'desk' => 'Desk',
                    'dresser' => 'Dresser',
                    'officechair' => 'Office Chair',
                    'rectangle' => 'Rectangle Table',
                    'round' => 'Round Table',
                    'sidetable' => 'Side Table',
                    'sofas' => 'Sofas',
                    'square' => 'Square Table'
                ];
                
                // If using category integers, display categories from database
                if ($is_category_int && $categories_result && $categories_result->num_rows > 0) {
                    // Reset pointer
                    $categories_result->data_seek(0);
                    
                    // Display each category from the database
                    while ($cat_row = $categories_result->fetch_assoc()) {
                        $cat_id = $cat_row['category_id'];
                        $cat_name = $cat_row['category_name'];
                        $cat_slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $cat_name));
                        
                        $is_active = ($category == $cat_id) ? 'active' : '';
                        
                        // Find matching icon
                        $category_icon = $category_icons['default'];
                        foreach ($category_icons as $key => $icon) {
                            if (strpos($cat_slug, $key) !== false || strpos($key, $cat_slug) !== false) {
                                $category_icon = $icon;
                                break;
                            }
                        }
                        
                        // Find matching color
                        $category_color = $category_colors['default'];
                        foreach ($category_colors as $key => $color) {
                            if (strpos($cat_slug, $key) !== false || strpos($key, $cat_slug) !== false) {
                                $category_color = $color;
                                break;
                            }
                        }
                        
                        echo '<a href="product.php?category=' . $cat_id . '" class="filter-button ' . $is_active . '" style="border-color: ' . $category_color . '; --category-color: ' . $category_color . ';">
                            <i class="' . $category_icon . '" style="color: ' . $category_color . ';"></i>
                            ' . $cat_name . '
                        </a>';
                    }
                } else {
                    // For string categories, use our predefined list
                    foreach ($category_display_names as $cat_slug => $display_name) {
                        $is_active = ($category == $cat_slug) ? 'active' : '';
                        $category_icon = isset($category_icons[$cat_slug]) ? $category_icons[$cat_slug] : $category_icons['default'];
                        $category_color = isset($category_colors[$cat_slug]) ? $category_colors[$cat_slug] : $category_colors['default'];
                        
                        echo '<a href="product.php?category=' . $cat_slug . '" class="filter-button ' . $is_active . '" style="border-color: ' . $category_color . '; --category-color: ' . $category_color . ';">
                            <i class="' . $category_icon . '" style="color: ' . $category_color . ';"></i>
                            ' . $display_name . '
                        </a>';
                    }
                }
                ?>
            </div>
        </div>

        <div class="products-grid">
            <?php if ($products && $products->num_rows > 0): ?>
                <?php while ($row = $products->fetch_assoc()): ?>
                    <?php
                    // Get category name and icon
                    $product_category = '';
                    $category_name = '';
                    $category_icon = $category_icons['default'];
                    $category_id = null;
                    
                    if ($is_category_int) {
                        // For integer categories
                        if (isset($row['category'])) {
                            $category_id = $row['category'];
                            // Get from pre-fetched result if category_name is included in query
                            if (isset($row['category_name'])) {
                                $category_name = $row['category_name'];
                            } 
                            // Otherwise, get from our db_categories array
                            elseif (isset($db_categories[$category_id])) {
                                $category_name = $db_categories[$category_id];
                            }
                            
                            // Convert to slug for icon matching
                            $cat_slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $category_name));
                            
                            // Find matching icon
                            foreach ($category_icons as $key => $icon) {
                                if (strpos($cat_slug, $key) !== false || strpos($key, $cat_slug) !== false) {
                                    $category_icon = $icon;
                                    break;
                                }
                            }
                            
                            // Find matching color
                            $category_color = $category_colors['default'];
                            foreach ($category_colors as $key => $color) {
                                if (strpos($cat_slug, $key) !== false || strpos($key, $cat_slug) !== false) {
                                    $category_color = $color;
                                    break;
                                }
                            }
                        }
                    } else {
                        // For string categories
                        if (isset($row['category']) && !empty($row['category'])) {
                            $product_category = strtolower(trim($row['category']));
                            $category_name = isset($category_display_names[$product_category]) ? 
                                            $category_display_names[$product_category] : 
                                            ucfirst($product_category);
                            $category_icon = isset($category_icons[$product_category]) ? 
                                           $category_icons[$product_category] : 
                                           $category_icons['default'];
                            $category_color = isset($category_colors[$product_category]) ?
                                            $category_colors[$product_category] :
                                            $category_colors['default'];
                        }
                    }
                    ?>
                    <div class="product-card">
                        <div class="product-image">
                            <img src="assets/imgs/<?php echo htmlspecialchars($row['product_image']); ?>" alt="<?php echo htmlspecialchars($row['product_name']); ?>">
                            
                            <?php if (isset($row['product_quantity']) && $row['product_quantity'] <= 3): ?>
                                <div class="limited-badge">Limited Quantity</div>
                            <?php endif; ?>
                            
                            <?php if (!empty($category_name)): ?>
                                <div class="category-badge" style="background-color: <?php echo $category_color; ?>;">
                                    <i class="<?php echo $category_icon; ?>"></i>
                                    <span><?php echo htmlspecialchars($category_name); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="product-overlay">
                                <button class="view-button" onclick="window.location.href='single_product.php?product_id=<?php echo $row['product_id']; ?>'">View Details</button>
                            </div>
                        </div>
                        <div class="product-info">
                            <div>
                                <h3><?php echo htmlspecialchars($row['product_name']); ?></h3>
                                <?php if (isset($row['product_description'])): ?>
                                    <p class="product-description"><?php echo htmlspecialchars(substr($row['product_description'], 0, 80)) . (strlen($row['product_description']) > 80 ? '...' : ''); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($category_name)): ?>
                                    <p class="product-category"><i class="<?php echo $category_icon; ?>" style="color: <?php echo $category_color; ?>;"></i> <?php echo htmlspecialchars($category_name); ?></p>
                                <?php endif; ?>
                            </div>
                            <p class="price"><?php echo number_format($row['product_price']); ?> DA/Month</p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 50px 0;">
                    <i class="fas fa-search" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                    <h3>No products found</h3>
                    <p>We couldn't find any products in this category. Please try another category or check back later.</p>
                </div>
            <?php endif; ?>
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
        // Optional JavaScript for animations and interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Animate product cards on scroll
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = 1;
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });

            document.querySelectorAll('.product-card').forEach(card => {
                card.style.opacity = 0;
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.4s ease, transform 0.6s ease';
                observer.observe(card);
            });
            
            // Animate filter buttons on page load
            const filterButtons = document.querySelectorAll('.filter-button');
            filterButtons.forEach((button, index) => {
                button.style.opacity = 0;
                button.style.transform = 'translateY(10px)';
                button.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                
                setTimeout(() => {
                    button.style.opacity = 1;
                    button.style.transform = 'translateY(0)';
                }, 100 + (index * 50));
            });
        });
    </script>
</body>
</html>
