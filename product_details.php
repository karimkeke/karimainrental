<?php
session_start();
include('connection.php');

// Calculate total items in cart
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

calculatetotalcart();

// Check if product ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$product_id = $_GET['id'];

// Check if the category field in products table is an integer (using categories table)
$check_column = $conn->query("SHOW COLUMNS FROM products LIKE 'category'");
$is_category_int = false;

if ($check_column->num_rows > 0) {
    $column_info = $check_column->fetch_assoc();
    $is_category_int = (strpos(strtolower($column_info['Type']), 'int') !== false);
}

// Prepare the SQL query based on the category field type
if ($is_category_int) {
    // If category is integer (using categories table)
    $sql = "SELECT p.*, c.category_name 
            FROM products p
            LEFT JOIN categories c ON p.category = c.category_id
            WHERE p.product_id = ?";
} else {
    // If category is still a string (fallback)
    $sql = "SELECT * FROM products WHERE product_id = ?";
}

// Prepare and execute the query
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if product exists
if($result->num_rows == 0) {
    header('Location: index.php');
    exit;
}

$product = $result->fetch_assoc();

// Handle Add to Cart
if(isset($_POST['add_to_cart'])) {
    // Get form data
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Validate quantity
    if($quantity < 1) {
        $quantity = 1;
    }
    
    if($quantity > $product['product_quantity']) {
        $quantity = $product['product_quantity'];
    }
    
    // Prepare product for cart
    $cart_item = [
        'product_id' => $product['product_id'],
        'product_name' => $product['product_name'],
        'product_image' => $product['product_image'],
        'product_price' => $product['product_price'],
        'product_quantity' => $quantity
    ];
    
    // Check if product is already in cart
    if(isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        $product_exists = false;
        
        foreach($_SESSION['cart'] as $key => $item) {
            if($item['product_id'] == $product['product_id']) {
                // Update quantity
                $_SESSION['cart'][$key]['product_quantity'] += $quantity;
                $product_exists = true;
                break;
            }
        }
        
        if(!$product_exists) {
            // Add new product to cart
            $_SESSION['cart'][] = $cart_item;
        }
    } else {
        // Create cart with first product
        $_SESSION['cart'] = [$cart_item];
    }
    
    // Update cart totals
    calculatetotalcart();
    
    // Redirect to prevent form resubmission
    header('Location: product_details.php?id=' . $product_id . '&added=1');
    exit;
}

// Get added to cart message
$added_to_cart = isset($_GET['added']) && $_GET['added'] == 1;

// Get related products
$related_products = [];

if ($is_category_int && isset($product['category'])) {
    // Get products from the same category
    $related_sql = "SELECT p.* FROM products p 
                    WHERE p.category = ? AND p.product_id != ? 
                    LIMIT 4";
    $related_stmt = $conn->prepare($related_sql);
    $related_stmt->bind_param("ii", $product['category'], $product_id);
    $related_stmt->execute();
    $related_result = $related_stmt->get_result();
    
    while($related = $related_result->fetch_assoc()) {
        $related_products[] = $related;
    }
} elseif (!$is_category_int && isset($product['category'])) {
    // Get products with the same category name
    $related_sql = "SELECT * FROM products 
                    WHERE category = ? AND product_id != ? 
                    LIMIT 4";
    $related_stmt = $conn->prepare($related_sql);
    $related_stmt->bind_param("si", $product['category'], $product_id);
    $related_stmt->execute();
    $related_result = $related_stmt->get_result();
    
    while($related = $related_result->fetch_assoc()) {
        $related_products[] = $related;
    }
}

// If no related products found, get random products
if(empty($related_products)) {
    $random_sql = "SELECT * FROM products 
                   WHERE product_id != ? 
                   ORDER BY RAND() 
                   LIMIT 4";
    $random_stmt = $conn->prepare($random_sql);
    $random_stmt->bind_param("i", $product_id);
    $random_stmt->execute();
    $random_result = $random_stmt->get_result();
    
    while($random = $random_result->fetch_assoc()) {
        $related_products[] = $random;
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['product_name']); ?> - Furniture Rental</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Product Details Styles */
        .product-details {
            padding: 80px 0;
        }
        
        .product-container {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .product-images {
            flex: 1;
            min-width: 300px;
        }
        
        .main-image {
            width: 100%;
            height: 400px;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .main-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .thumbnail-container {
            display: flex;
            gap: 10px;
        }
        
        .thumbnail {
            width: 80px;
            height: 80px;
            border-radius: 5px;
            cursor: pointer;
            overflow: hidden;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .thumbnail.active {
            border-color: #000;
        }
        
        .product-info {
            flex: 1;
            min-width: 300px;
        }
        
        .product-title {
            font-size: 2.2rem;
            margin-bottom: 15px;
            color: #000;
        }
        
        .product-category {
            display: inline-block;
            background-color: #f0f0f0;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            color: #777;
            margin-bottom: 15px;
        }
        
        .product-price {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #000;
        }
        
        .product-description {
            margin-bottom: 30px;
            line-height: 1.6;
            color: #555;
        }
        
        .product-meta {
            margin-bottom: 30px;
        }
        
        .meta-item {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        
        .meta-label {
            font-weight: bold;
            min-width: 120px;
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .quantity-label {
            font-weight: bold;
        }
        
        .quantity-input {
            width: 100px;
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .add-to-cart-btn {
            background-color: #000;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .add-to-cart-btn:hover {
            background-color: #333;
            transform: translateY(-2px);
        }
        
        .add-to-cart-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .related-products {
            margin-top: 80px;
            padding: 0 20px;
        }
        
        .related-title {
            text-align: center;
            margin-bottom: 40px;
            font-size: 2rem;
        }
        
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        @media (max-width: 768px) {
            .product-container {
                flex-direction: column;
            }
            
            .main-image {
                height: 300px;
            }
            
            .product-title {
                font-size: 1.8rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo">Furniture Rental</a>
            <div class="nav-menu">
                <a href="index.php">Home</a>
                <a href="index.php#categories">Products</a>
                <a href="index.php#about">About Us</a>
                <a href="index.php#contact">Contact</a>
                <a href="#" class="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if(isset($_SESSION['quantity']) && $_SESSION['quantity'] > 0): ?>
                    <span class="cart-count"><?php echo $_SESSION['quantity']; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Product Details Section -->
    <section class="product-details">
        <div class="product-container">
            <div class="product-images">
                <div class="main-image">
                    <img src="assets/imgs/<?php echo htmlspecialchars($product['product_image']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" id="main-product-image">
                </div>
                
                <div class="thumbnail-container">
                    <?php if(!empty($product['product_image'])): ?>
                    <div class="thumbnail active" data-image="<?php echo htmlspecialchars($product['product_image']); ?>">
                        <img src="assets/imgs/<?php echo htmlspecialchars($product['product_image']); ?>" alt="Thumbnail 1">
                    </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($product['product_image2'])): ?>
                    <div class="thumbnail" data-image="<?php echo htmlspecialchars($product['product_image2']); ?>">
                        <img src="assets/imgs/<?php echo htmlspecialchars($product['product_image2']); ?>" alt="Thumbnail 2">
                    </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($product['product_image3'])): ?>
                    <div class="thumbnail" data-image="<?php echo htmlspecialchars($product['product_image3']); ?>">
                        <img src="assets/imgs/<?php echo htmlspecialchars($product['product_image3']); ?>" alt="Thumbnail 3">
                    </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($product['product_image4'])): ?>
                    <div class="thumbnail" data-image="<?php echo htmlspecialchars($product['product_image4']); ?>">
                        <img src="assets/imgs/<?php echo htmlspecialchars($product['product_image4']); ?>" alt="Thumbnail 4">
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="product-info">
                <?php if($added_to_cart): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    Product has been added to your cart!
                </div>
                <?php endif; ?>
                
                <h1 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h1>
                
                <?php if(isset($product['category_name'])): ?>
                <span class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></span>
                <?php elseif(isset($product['category'])): ?>
                <span class="product-category"><?php echo htmlspecialchars($product['category']); ?></span>
                <?php endif; ?>
                
                <div class="product-price"><?php echo number_format($product['product_price']); ?> DA / month</div>
                
                <div class="product-description">
                    <?php echo nl2br(htmlspecialchars($product['product_description'])); ?>
                </div>
                
                <div class="product-meta">
                    <div class="meta-item">
                        <span class="meta-label">Availability:</span>
                        <span><?php echo $product['product_quantity'] > 0 ? 'In Stock (' . $product['product_quantity'] . ' available)' : 'Out of Stock'; ?></span>
                    </div>
                </div>
                
                <form method="post" action="">
                    <div class="quantity-selector">
                        <label for="quantity" class="quantity-label">Quantity:</label>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['product_quantity']; ?>" class="quantity-input">
                    </div>
                    
                    <button type="submit" name="add_to_cart" class="add-to-cart-btn" <?php echo $product['product_quantity'] <= 0 ? 'disabled' : ''; ?>>
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                </form>
            </div>
        </div>
    </section>
    
    <!-- Related Products -->
    <?php if(!empty($related_products)): ?>
    <section class="related-products">
        <h2 class="related-title">Related Products</h2>
        
        <div class="related-grid">
            <?php foreach($related_products as $related): ?>
            <div class="product-card">
                <div class="product-image">
                    <img src="assets/imgs/<?php echo htmlspecialchars($related['product_image']); ?>" alt="<?php echo htmlspecialchars($related['product_name']); ?>">
                    <div class="product-overlay">
                        <button class="rent-button" onclick="window.location.href='product_details.php?id=<?php echo $related['product_id']; ?>'">View Details</button>
                    </div>
                </div>
                <div class="product-info">
                    <h3><?php echo htmlspecialchars($related['product_name']); ?></h3>
                    <p class="product-price"><?php echo number_format($related['product_price']); ?> DA / month</p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
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
        // Image gallery functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mainImage = document.getElementById('main-product-image');
            const thumbnails = document.querySelectorAll('.thumbnail');
            
            thumbnails.forEach(thumbnail => {
                thumbnail.addEventListener('click', function() {
                    // Update main image
                    const imagePath = this.getAttribute('data-image');
                    mainImage.src = 'assets/imgs/' + imagePath;
                    
                    // Update active state
                    thumbnails.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        });
    </script>
</body>
</html> 