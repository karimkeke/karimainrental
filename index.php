<?php
session_start();
include('connection.php'); // Add database connection


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
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Furniture Rental - Premium Furniture Rental Services</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
<style>
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

/* Message icon styles */
.message-icon {
    position: relative;
    font-size: 22px;
    color: #000;
    text-decoration: none;
    margin-left: 20px;
}

.message-icon i {
    font-size: 24px;
    transition: all 0.3s ease;
}

.message-icon:hover i {
    transform: translateY(-2px);
    color: #555;
}

.message-count {
    position: absolute;
    top: -8px;
    right: -10px;
    background-color: #ff6b6b;
    color: white;
    font-size: 13px;
    font-weight: bold;
    border-radius: 50%;
    padding: 5px 10px;
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(255, 107, 107, 0.4);
    }
    70% {
        box-shadow: 0 0 0 8px rgba(255, 107, 107, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(255, 107, 107, 0);
    }
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

/* Enhanced Category Tabs */
.categories-tabs {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 40px;
}

.tab-button {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    background-color: #f8f9fa;
    border: 2px solid transparent;
    border-radius: 50px;
    cursor: pointer;
    transition: all 0.3s ease;
    color: #555;
    font-weight: 600;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.tab-button i {
    margin-right: 8px;
    font-size: 1.1rem;
}

.tab-button:hover {
    background-color: #f1f1f1;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.tab-button.active {
    background-color: #000;
    color: white;
    border-color: #000;
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
}

/* Category Badge Styles */
.product-image {
    position: relative;
    overflow: hidden;
}

.category-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    display: flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 50px;
    color: white;
    font-size: 0.8rem;
    font-weight: 600;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    z-index: 2;
    backdrop-filter: blur(4px);
    transition: all 0.3s ease;
}

.category-badge i {
    margin-right: 5px;
    font-size: 0.9rem;
}

.product-card:hover .category-badge {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
}

@media (max-width: 768px) {
    .categories-tabs {
        gap: 8px;
    }
    
    .tab-button {
        padding: 10px 15px;
        font-size: 0.9rem;
    }
    
    .tab-button span {
        display: none;
    }
    
    .tab-button i {
        margin-right: 0;
        font-size: 1.2rem;
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

.section-title {
    font-size: 32px;
    font-weight: 700;
    text-align: center;
    margin-bottom: 10px;
    color: #000;
    position: relative;
}

.section-subtitle {
    font-size: 16px;
    color: #666;
    text-align: center;
    margin-bottom: 30px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.products-section {
    padding: 80px 0;
    background-color: #f8f9fa;
}

.featured-products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
    margin-top: 40px;
}

.featured-product-card {
    background-color: #fff;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    height: 100%;
    display: flex;
    flex-direction: column;
    border: 1px solid rgba(0, 0, 0, 0.04);
    position: relative;
}

.featured-product-card:hover {
    transform: translateY(-12px);
    box-shadow: 0 20px 30px rgba(0, 0, 0, 0.1);
}

.featured-product-image {
    position: relative;
    height: 250px;
    overflow: hidden;
}

.featured-product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
}

.featured-product-card:hover .featured-product-image img {
    transform: scale(1.08);
}

.featured-category-badge {
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
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
}

.featured-category-badge i {
    margin-right: 6px;
    font-size: 0.9rem;
}

.featured-product-card:hover .featured-category-badge {
    transform: translateY(-3px);
    box-shadow: 0 5px 12px rgba(0, 0, 0, 0.2);
}

.limited-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    background-color: #FF5252;
    color: white;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 30px;
    z-index: 2;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(255, 82, 82, 0.4);
    }
    70% {
        box-shadow: 0 0 0 8px rgba(255, 82, 82, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(255, 82, 82, 0);
    }
}

.featured-product-overlay {
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

.featured-product-card:hover .featured-product-overlay {
    opacity: 1;
}

.view-details-btn {
    padding: 10px 24px;
    background-color: #000;
    color: white;
    border: none;
    border-radius: 30px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    transform: translateY(20px);
    opacity: 0;
    text-decoration: none;
    display: inline-block;
}

.featured-product-card:hover .view-details-btn {
    transform: translateY(0);
    opacity: 1;
}

.view-details-btn:hover {
    background-color: #333;
    transform: scale(1.05);
}

.featured-product-info {
    padding: 20px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
}

.featured-product-info h3 {
    margin: 0 0 15px;
    font-size: 18px;
    color: #000;
    font-weight: 600;
    transition: color 0.3s ease;
}

.featured-product-card:hover .featured-product-info h3 {
    color: #ff6b6b;
}

.featured-product-price {
    display: flex;
    align-items: baseline;
    margin-top: 10px;
}

.price-amount {
    font-size: 20px;
    font-weight: 700;
    color: #000;
}

.price-period {
    font-size: 14px;
    color: #666;
    margin-left: 5px;
}

.no-products {
    grid-column: 1 / -1;
    text-align: center;
    padding: 50px 0;
    color: #666;
}

.no-products i {
    font-size: 48px;
    color: #ddd;
    margin-bottom: 20px;
    display: block;
}

.view-all-container {
    text-align: center;
    margin-top: 40px;
}

.view-all-btn {
    display: inline-block;
    padding: 12px 30px;
    background-color: transparent;
    color: #000;
    border: 2px solid #000;
    border-radius: 30px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}

.view-all-btn i {
    margin-left: 8px;
    transition: transform 0.3s ease;
}

.view-all-btn:hover {
    background-color: #000;
    color: white;
}

.view-all-btn:hover i {
    transform: translateX(5px);
}

/* Update tab button styles to match the filter buttons in product.php */
.tab-button {
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

.tab-button i {
    font-size: 16px;
    transition: all 0.3s ease;
}

.tab-button:hover {
    background-color: #f1f1f1;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
}

.tab-button:hover i {
    transform: scale(1.2);
}

.tab-button.active {
    background-color: var(--category-color, #000);
    color: white;
    border-color: var(--category-color, #000);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.tab-button.active.all-products {
    background-image: linear-gradient(to right, #FF6B6B, #4ECDC4, #6A0572, #1A535C);
    border-color: #333;
}

.tab-button.active i {
    color: white !important;
}

.tab-button::after {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: -100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: all 0.6s ease;
}

.tab-button:hover::after {
    left: 100%;
}

@media (max-width: 768px) {
    .featured-products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    .featured-product-image {
        height: 180px;
    }
    
    .tab-button {
        padding: 10px 15px;
        font-size: 14px;
    }
    
    .section-title {
        font-size: 28px;
    }
    
    .section-subtitle {
        font-size: 14px;
    }
    
    .featured-category-badge span {
        display: none;
    }
}

@media (max-width: 480px) {
    .featured-products-grid {
        grid-template-columns: 1fr;
    }
    
    .tab-button span {
        display: none;
    }
    
    .tab-button i {
        margin-right: 0;
        font-size: 18px;
    }
    
    .view-all-btn {
        padding: 10px 20px;
        font-size: 14px;
    }
    
    .featured-product-info h3 {
        font-size: 16px;
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
    <?php if(isset($_SESSION['user_id'])): ?>
    <a href="user_messages.php" class="message-icon">
        <i class="fas fa-envelope"></i>
        <?php
        // Get unread message count if logged in
        $unread_count = 0;
        if(isset($conn)) {
            $user_id = $_SESSION['user_id'];
            // Check if messages table exists
            $table_check = $conn->query("SHOW TABLES LIKE 'messages'");
            if ($table_check->num_rows == 0) {
                // Create the messages table
                $create_table = "CREATE TABLE messages (
                    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT(11) NOT NULL,
                    message_text TEXT NOT NULL,
                    is_from_admin TINYINT(1) NOT NULL DEFAULT 0,
                    is_read TINYINT(1) NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                $conn->query($create_table);
            }
            
            // Now get unread messages
            $unread_query = "SELECT COUNT(*) as unread FROM messages WHERE user_id = ? AND is_from_admin = 1 AND is_read = 0";
            $unread_stmt = $conn->prepare($unread_query);
            $unread_stmt->bind_param("i", $user_id);
            $unread_stmt->execute();
            $unread_result = $unread_stmt->get_result();
            $unread_count = $unread_result->fetch_assoc()['unread'];
        }
        ?>
        <?php if($unread_count > 0): ?>
        <span class="message-count">
            <?php echo $unread_count; ?>
        </span>
        <?php endif; ?>
    </a>
    <?php endif; ?>
    <?php if(!isset($_SESSION['user_name'])): ?>
    <button class="register-btn" onclick="window.location.href='register.php'">Register</button>
    <?php endif; ?>
</div>

        </div>
    </nav>
    <div class="menu-backdrop"></div>


    <header id="home" class="hero">
        <div class="hero-overlay"></div>
        <div class="container hero-content">
            <div class="hero-text">
                <h1 class="hero-title">Rent Furniture for Your Home Easily</h1>
                <p class="hero-description">We offer a wide range of premium furniture rentals at affordable prices. Choose from our diverse collection of living rooms, bedrooms, and dining tables.</p>
                <div class="hero-buttons">
                    <a href="#categories" class="primary-btn">Browse Products</a>
                    <a href="contact.php" class="secondary-btn">Contact Us</a>
                </div>
                <div class="hero-features">
                    <div class="feature">
                        <i class="fas fa-truck"></i>
                        <span>Fast Delivery</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-shield-alt"></i>
                        <span>Quality Guarantee</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-hand-holding-usd"></i>
                        <span>Competitive Prices</span>
                    </div>
                </div>
            </div>
            <div class="hero-image">
                <img src="https://images.unsplash.com/photo-1556228453-efd6c1ff04f6?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80" alt="Luxurious Furniture">
            </div>
        </div>
    </header>

    <section id="categories" class="products-section">
    <div class="container">
        <h2 class="section-title">Our Featured Products</h2>
        <p class="section-subtitle">Discover our most popular furniture pieces available for rent</p>

        <div class="categories-tabs">
            <button class="tab-button active all-products" data-category="all">
                <i class="fas fa-border-all"></i>
                <span>All Products</span>
            </button>
            <button class="tab-button" data-category="living" style="--category-color: #FF6B6B;">
                <i class="fas fa-couch"></i>
                <span>Living Room</span>
            </button>
            <button class="tab-button" data-category="dining" style="--category-color: #4ECDC4;">
                <i class="fas fa-utensils"></i>
                <span>Dining Room</span>
            </button>
            <button class="tab-button" data-category="bed" style="--category-color: #6A0572;">
                <i class="fas fa-bed"></i>
                <span>Bedroom</span>
            </button>
            <button class="tab-button" data-category="office" style="--category-color: #1A535C;">
                <i class="fas fa-briefcase"></i>
                <span>Office</span>
            </button>
        </div>

        <div class="featured-products-grid">
            <?php 
                // Include the featured products file
                include('featured_products.php');
                
                // Check if we have products
                if($featured_products && $featured_products->num_rows > 0) {
                    // Display all featured products
                    while($product = $featured_products->fetch_assoc()) {
                        // Determine the category for filtering
                        $data_category = "all"; // Default category
                        
                        // Map product categories to display categories
                        $category_mapping = [
                            // Living Room
                            'accentchair' => 'living',
                            'sofas' => 'living',
                            'coffeetables' => 'living',
                            
                            // Dining Room
                            'round' => 'dining',
                            'rectangle' => 'dining',
                            'square' => 'dining',
                            'sidetable' => 'dining',
                            
                            // Bedroom
                            'bedframe' => 'bed',
                            'dresser' => 'bed',
                            
                            // Office
                            'desk' => 'office',
                            'cornerdesk' => 'office',
                            'officechair' => 'office'
                        ];
                        
                        // Get the product category from the enhanced category info
                        $product_category = '';
                        
                        if(isset($product['category_info'])) {
                            // Use the new enhanced category info
                            $product_category = isset($product['category_info']['slug']) ? 
                                               strtolower(trim($product['category_info']['slug'])) : '';
                        } elseif(isset($product['category'])) {
                            // Fallback to original category field
                            $product_category = strtolower(trim($product['category']));
                        } elseif(isset($product['category_name'])) {
                            // Another fallback for category_name
                            $product_category = strtolower(trim($product['category_name']));
                        }
                        
                        // Map to display category if it exists in our mapping
                        if(isset($category_mapping[$product_category])) {
                            $data_category = $category_mapping[$product_category];
                        } else {
                            // Fallback mapping for categories that might not exactly match
                            if(strpos($product_category, 'sofa') !== false || 
                               strpos($product_category, 'chair') !== false || 
                               strpos($product_category, 'coffee') !== false || 
                               strpos($product_category, 'living') !== false) {
                                $data_category = 'living';
                            } elseif(strpos($product_category, 'table') !== false || 
                                    strpos($product_category, 'dining') !== false || 
                                    strpos($product_category, 'round') !== false || 
                                    strpos($product_category, 'rectangle') !== false || 
                                    strpos($product_category, 'square') !== false) {
                                $data_category = 'dining';
                            } elseif(strpos($product_category, 'bed') !== false || 
                                    strpos($product_category, 'dresser') !== false || 
                                    strpos($product_category, 'night') !== false) {
                                $data_category = 'bed';
                            } elseif(strpos($product_category, 'desk') !== false || 
                                    strpos($product_category, 'office') !== false || 
                                    strpos($product_category, 'corner') !== false || 
                                    strpos($product_category, 'work') !== false) {
                                $data_category = 'office';
                            }
                        }
                        
                        // Set category color based on data_category
                        $category_color = "#888888"; // Default color
                        $category_icon = "fas fa-tag"; // Default icon
                        
                        switch($data_category) {
                            case 'living':
                                $category_color = "#FF6B6B";
                                $category_icon = "fas fa-couch";
                                break;
                            case 'dining':
                                $category_color = "#4ECDC4";
                                $category_icon = "fas fa-utensils";
                                break;
                            case 'bed':
                                $category_color = "#6A0572";
                                $category_icon = "fas fa-bed";
                                break;
                            case 'office':
                                $category_color = "#1A535C";
                                $category_icon = "fas fa-briefcase";
                                break;
                        }
                        
                        // Get category display name
                        $category_display_name = '';
                        if(isset($product['category_info']) && isset($product['category_info']['name'])) {
                            $category_display_name = $product['category_info']['name'];
                        } else {
                            // Fallback to display name based on data_category
                            $display_names = [
                                'living' => 'Living Room',
                                'dining' => 'Dining Room',
                                'bed' => 'Bedroom',
                                'office' => 'Office'
                            ];
                            if(isset($display_names[$data_category])) {
                                $category_display_name = $display_names[$data_category];
                            }
                        }
            ?>
            <div class="featured-product-card" data-category="<?php echo $data_category; ?>">
                <div class="featured-product-image">
                    <img src="assets/imgs/<?php echo htmlspecialchars($product['product_image']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                    
                    <div class="featured-category-badge" style="background-color: <?php echo $category_color; ?>">
                        <i class="<?php echo $category_icon; ?>"></i>
                        <span><?php echo $category_display_name; ?></span>
                    </div>
                    
                    <?php if (isset($product['product_quantity']) && $product['product_quantity'] <= 3): ?>
                    <div class="limited-badge">Limited Stock</div>
                    <?php endif; ?>
                    
                    <div class="featured-product-overlay">
                        <a href="single_product.php?product_id=<?php echo $product['product_id']; ?>" class="view-details-btn">View Details</a>
                    </div>
                </div>
                <div class="featured-product-info">
                    <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                    <div class="featured-product-price">
                        <span class="price-amount"><?php echo number_format($product['product_price']); ?></span>
                        <span class="price-period">DA/month</span>
                    </div>
                </div>
            </div>
            <?php 
                    }
                } else {
                    echo '<div class="no-products"><i class="fas fa-search"></i><p>No featured products available at this time.</p></div>';
                }
            ?>
        </div>
        
        <div class="view-all-container">
            <a href="product.php" class="view-all-btn">View All Products <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
</section>

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
    <script>
        // Enhanced category filtering with animations
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const productCards = document.querySelectorAll('.featured-product-card');
            
            // Initialize - show all products with animation
            productCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.4s ease-out';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100 + (index * 50)); // Staggered animation
            });
            
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    // Update active button state
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                    
                    const category = button.getAttribute('data-category');
                    let delay = 0;
                    let visibleCount = 0;
                    
                    // First hide all products
                    productCards.forEach(card => {
                        card.style.opacity = '0';
                        card.style.transform = 'translateY(20px)';
                    });
                    
                    // Then show matching products with staggered animation
                    setTimeout(() => {
                        productCards.forEach(card => {
                            if (category === 'all' || card.getAttribute('data-category') === category) {
                                visibleCount++;
                                card.style.display = '';
                                
                                setTimeout(() => {
                                    card.style.opacity = '1';
                                    card.style.transform = 'translateY(0)';
                                }, delay);
                                
                                delay += 50; // Staggered effect
                            } else {
                                setTimeout(() => {
                                    card.style.display = 'none';
                                }, 300);
                            }
                        });
                        
                        // Show "no products" message if no products are visible
                        const noProductsMessage = document.querySelector('.no-products');
                        if (noProductsMessage) {
                            if (visibleCount === 0) {
                                noProductsMessage.style.display = 'block';
                                setTimeout(() => {
                                    noProductsMessage.style.opacity = '1';
                                }, 300);
                            } else {
                                noProductsMessage.style.display = 'none';
                                noProductsMessage.style.opacity = '0';
                            }
                        }
                    }, 300);
                });
            });
            
            // Add hover effects for product cards
            productCards.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    const badge = card.querySelector('.featured-category-badge');
                    if (badge) {
                        badge.style.transform = 'translateY(-3px)';
                    }
                });
                
                card.addEventListener('mouseleave', () => {
                    const badge = card.querySelector('.featured-category-badge');
                    if (badge) {
                        badge.style.transform = '';
                    }
                });
            });
        });
    </script>
</body>
</html>
