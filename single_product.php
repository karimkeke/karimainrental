<?php
include('connection.php');

if (isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];

    $stmt = $conn->prepare("SELECT * FROM products WHERE product_id=?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result();
} else {
    header("Location: product.php");
    exit();
}
?>
<?php
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
    <title>Single Product</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>

.single-product {
    margin: 120px auto;
}

.product-layout {
    display: flex;
    align-items: flex-start;
    gap: 40px;
}

.col-6 {
    flex: 1;
}

.main-img {
    width: 100%;
    max-width: 425px;
    height: 425px;
    object-fit: cover;
    border-radius: 15px;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.main-img:hover {
    transform: scale(1.02);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
}

.small-img-group {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}

.small-img {
    width: 100px;
    height: 100px;
    cursor: pointer;
    border-radius: 10px;
    object-fit: cover;
    transition: 0.3s;
    box-shadow: 0 5px 10px rgba(0, 0, 0, 0.08);
    border: 2px solid transparent;
}

.small-img:hover {
    transform: translateY(-5px);
    border-color: #000;
}

.product-details {
    flex: 1;
    max-width: 500px;
    padding: 30px;
    background-color: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07);
    position: relative;
}

.product-details h3 {
    font-size: 32px;
    margin-bottom: 15px;
    color: #333;
    font-weight: 600;
}

.product-details h2 {
    font-size: 28px;
    color: #000;
    margin-bottom: 20px;
    font-weight: 500;
}

.product-details p {
    font-size: 16px;
    color: #555;
    line-height: 1.6;
    margin-bottom: 20px;
}

.product-details label {
    font-size: 16px;
    color: #333;
    margin-right: 10px;
}

.input-group {
    display: flex;
    align-items: center;
    gap: 20px; 
    margin-bottom: 30px;
    background-color: #f9f9f9;
    padding: 20px;
    border-radius: 12px;
    box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.05);
}

.input-field {
    display: flex;
    flex-direction: column;
}

.input-field label {
    font-size: 16px;
    color: #333;
    font-weight: bold;
    margin-bottom: 8px;
}

.input-field select {
    width: 150px;
    padding: 12px 15px;
    font-size: 16px;
    border: 1px solid #ddd;
    background-color: white;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}

.input-field select:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
    outline: none;
}

.buy-btn {
    background: #000;
    color: white;
    padding: 15px 30px;
    border: none;
    cursor: pointer;
    font-size: 16px;
    border-radius: 30px;
    transition: all 0.3s ease;
    width: 100%;
    text-transform: uppercase;
    font-weight: 600;
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    margin-top: 10px;
}

.buy-btn:hover {
    background: #333;
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
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

.category-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 15px;
    border-radius: 30px;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 15px;
    color: white;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.category-badge i {
    margin-right: 8px;
}

/* Related Products Section */
.related-products {
    margin-top: 80px;
}

.related-title {
    font-size: 28px;
    font-weight: 700;
    color: #000;
    text-align: center;
    margin-bottom: 40px;
    position: relative;
}

.related-title:after {
    content: '';
    position: absolute;
    bottom: -15px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 3px;
    background-color: #000;
}

.related-products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 25px;
    margin-top: 30px;
}

.related-product-card {
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

.related-product-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
}

.related-product-image {
    position: relative;
    height: 180px;
    overflow: hidden;
}

.related-product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
}

.related-product-card:hover .related-product-image img {
    transform: scale(1.08);
}

.related-category-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.related-product-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 15px;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.7), transparent);
    display: flex;
    justify-content: center;
    opacity: 0;
    transition: all 0.4s ease;
    z-index: 2;
}

.related-product-card:hover .related-product-overlay {
    opacity: 1;
}

.related-view-btn {
    padding: 8px 20px;
    background-color: #000;
    color: white;
    border: none;
    border-radius: 30px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    transform: translateY(10px);
    opacity: 0;
    text-decoration: none;
    display: inline-block;
}

.related-product-card:hover .related-view-btn {
    transform: translateY(0);
    opacity: 1;
}

.related-view-btn:hover {
    background-color: #333;
    transform: scale(1.05);
}

.related-product-info {
    padding: 15px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.related-product-info h4 {
    margin: 0 0 10px;
    font-size: 16px;
    color: #000;
    font-weight: 600;
    transition: color 0.3s ease;
}

.related-product-card:hover .related-product-info h4 {
    color: #ff6b6b;
}

.related-price {
    color: #000;
    font-weight: 700;
    font-size: 14px;
    margin-top: 5px;
    display: flex;
    align-items: center;
}

.no-related {
    grid-column: 1 / -1;
    text-align: center;
    color: #666;
    font-size: 16px;
    padding: 30px;
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
            <a href="product.php">Home</a>
    <a href="product.php">Products</a>
    <a href="contact.php">Contact Us</a>
    <a href="cart.php" class="cart-icon">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-count">
            <?php echo isset($_SESSION['quantity']) ? $_SESSION['quantity'] : 0; ?>
        </span>
    </a>
</div>

        </div>
    </nav>

    

<section class="container single-product">
    <div class="row product-layout">
        <?php 
        // Store the category for later use with related products
        $current_category = '';
        
        while ($row = $product->fetch_assoc()) { 
            // Store category for related products
            $current_category = $row['category'];
            
            // Category mapping for icons and colors
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
                'default' => 'fas fa-tags'
            ];
            
            // Category color mapping
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
            
            // Get category name and determine icon & color
            $product_category = isset($row['category']) ? strtolower(trim($row['category'])) : '';
            $category_icon = $category_icons['default']; 
            $category_color = $category_colors['default'];
            $display_category = ucfirst($product_category);
            
            // Find matching icon and color for the category
            if (!empty($product_category)) {
                foreach ($category_icons as $key => $icon) {
                    if (strpos($product_category, $key) !== false || strpos($key, $product_category) !== false) {
                        $category_icon = $icon;
                        $display_category = ucwords(str_replace('-', ' ', $key));
                        break;
                    }
                }
                
                foreach ($category_colors as $key => $color) {
                    if (strpos($product_category, $key) !== false || strpos($key, $product_category) !== false) {
                        $category_color = $color;
                        break;
                    }
                }
            }
        ?>
            <div class="col-6">
                <img class="main-img" src="assets/imgs/<?php echo $row['product_image']; ?>" id="mainImg">
                <div class="small-img-group">
                    <img src="assets/imgs/<?php echo $row['product_image']; ?>" class="small-img">
                    <img src="assets/imgs/<?php echo $row['product_image2']; ?>" class="small-img">
                    <img src="assets/imgs/<?php echo $row['product_image3']; ?>" class="small-img">
                    <img src="assets/imgs/<?php echo $row['product_image4']; ?>" class="small-img">
                </div>
            </div>

            <div class="col-6 product-details">
                <?php if (!empty($product_category)): ?>
                <div class="category-badge" style="background-color: <?php echo $category_color; ?>">
                    <i class="<?php echo $category_icon; ?>"></i>
                    <span><?php echo $display_category; ?></span>
                </div>
                <?php endif; ?>
                
                <h3><?php echo $row['product_name']; ?></h3>
                <h2>Rent for <?php echo number_format($row['product_price']); ?> DA/MONTH</h2>
                <p><?php echo $row['product_description']; ?></p>
                <form method="POST" action="cart.php" class="form-group">
    <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
    <input type="hidden" name="product_image" value="<?php echo $row['product_image']; ?>">
    <input type="hidden" name="product_name" value="<?php echo $row['product_name']; ?>">
    <input type="hidden" name="product_price" value="<?php echo $row['product_price']; ?>">

    <div class="input-group">
        <div class="input-field">
        <label for="quantity">Quantity:</label>
<select id="quantity" name="quantity">
    <?php
    $product_id = $_GET['product_id']; 
    $query = "SELECT product_quantity FROM products WHERE product_id = $product_id";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    
    $available_quantity = $row['product_quantity'];

    for ($i = 1; $i <= $available_quantity; $i++) {
        echo "<option value='$i'>$i</option>";
    }
    ?>
</select>

        </div>

<div class="input-field">
    <label for="rental_length">Rental Length:</label>
    <select name="rental_length" id="rental_length">
        <?php 
        $rental_options = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 18, 24]; 
        foreach ($rental_options as $months) {
            echo "<option value='$months'>$months months</option>";
        }
        ?>
    </select>
</div>

    </div>

    <button class="buy-btn" type="submit" name="add_to_cart">Add to Cart</button>
</form>

            </div>
        <?php } ?>
    </div>
    
    <!-- Related Products Section -->
    <div class="related-products">
        <h2 class="related-title">Related Products</h2>
        <div class="related-products-grid">
            <?php
            // Query to get related products
            $related_stmt = $conn->prepare("SELECT * FROM products WHERE category = ? AND product_id != ? LIMIT 4");
            $related_stmt->bind_param("si", $current_category, $product_id);
            $related_stmt->execute();
            $related_products = $related_stmt->get_result();
            
            // Counter for related products
            $related_count = 0;
            
            if ($related_products && $related_products->num_rows > 0) {
                while ($related_product = $related_products->fetch_assoc()) {
                    // Get the category name and determine icon & color
                    $rel_category = isset($related_product['category']) ? strtolower(trim($related_product['category'])) : '';
                    $rel_icon = $category_icons['default'];
                    $rel_color = $category_colors['default'];
                    
                    // Find matching icon and color for the category
                    if (!empty($rel_category)) {
                        foreach ($category_icons as $key => $icon) {
                            if (strpos($rel_category, $key) !== false || strpos($key, $rel_category) !== false) {
                                $rel_icon = $icon;
                                break;
                            }
                        }
                        
                        foreach ($category_colors as $key => $color) {
                            if (strpos($rel_category, $key) !== false || strpos($key, $rel_category) !== false) {
                                $rel_color = $color;
                                break;
                            }
                        }
                    }
                    
                    $related_count++;
                    ?>
                    <div class="related-product-card">
                        <div class="related-product-image">
                            <img src="assets/imgs/<?php echo $related_product['product_image']; ?>" alt="<?php echo $related_product['product_name']; ?>">
                            <div class="related-category-badge" style="background-color: <?php echo $rel_color; ?>">
                                <i class="<?php echo $rel_icon; ?>"></i>
                            </div>
                            <div class="related-product-overlay">
                                <a href="single_product.php?product_id=<?php echo $related_product['product_id']; ?>" class="related-view-btn">View Details</a>
                            </div>
                        </div>
                        <div class="related-product-info">
                            <h4><?php echo $related_product['product_name']; ?></h4>
                            <div class="related-price">
                                <?php echo number_format($related_product['product_price']); ?> DA/MONTH
                            </div>
                        </div>
                    </div>
                    <?php
                }
            }
            
            // If no related products were found
            if ($related_count == 0) {
                echo '<div class="no-related">No related products found</div>';
            }
            ?>
        </div>
    </div>
</section>
      


    <script>
var mainImg = document.getElementById("mainImg");
var smallImgs = document.querySelectorAll(".small-img");

smallImgs.forEach(img => {
    img.addEventListener("click", function() {
        mainImg.src = img.src;
        mainImg.style.width = "425px"; 
        mainImg.style.height = "425px";
    });
});

</script>

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
</body>
</html>
