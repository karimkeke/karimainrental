<?php
session_start();

if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $product_image = $_POST['product_image'];
    $quantity = $_POST['quantity'];
    $rental_length = isset($_POST['rental_length']) && is_numeric($_POST['rental_length']) && $_POST['rental_length'] > 0 
                    ? $_POST['rental_length'] 
                    : 1; // Default to 1 month if not specified or invalid

    $cart_item = [
        'product_id' => $product_id,
        'product_name' => $product_name,
        'product_price' => $product_price,
        'product_image' => $product_image,
        'product_quantity' => $quantity,
        'rental_length' => $rental_length 
    ];
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] == $product_id) {
            $item['product_quantity'] += $quantity;
            $item['rental_length'] = $rental_length; 
            $found = true;
            break;
        }
    }

    if (!$found) {
        $_SESSION['cart'][] = $cart_item; 
    }

 
    calculatetotalcart();

    header("Location: cart.php");
    exit();
}

// Handle edit_quantity action
if (isset($_POST['edit_quantity'])) {
    $product_id = $_POST['product_id'];
    $new_quantity = $_POST['product_quantity'];
    
    if ($new_quantity > 0) {
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] == $product_id) {
                $item['product_quantity'] = $new_quantity;
                // Ensure rental_length is valid
                if (!isset($item['rental_length']) || empty($item['rental_length']) || !is_numeric($item['rental_length']) || $item['rental_length'] <= 0) {
                    $item['rental_length'] = 1;
                }
                break;
            }
        }
        
        calculatetotalcart();
    }
    
    header("Location: cart.php");
    exit();
}

// Handle remove_product action
if (isset($_POST['remove_product'])) {
    $product_id = $_POST['product_id'];
    
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['product_id'] == $product_id) {
            unset($_SESSION['cart'][$key]);
            break;
        }
    }
    
    // Reindex the array after removal
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    
    calculatetotalcart();
    
    header("Location: cart.php");
    exit();
}

function calculatetotalcart() {
    $total = 0;
    $total_quantity = 0;

    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $key => $product) {
            // Ensure rental_length is valid
            if (!isset($product['rental_length']) || empty($product['rental_length']) || !is_numeric($product['rental_length']) || $product['rental_length'] <= 0) {
                $_SESSION['cart'][$key]['rental_length'] = 1;
                $rental_length = 1;
            } else {
                $rental_length = $product['rental_length'];
            }
            
            $total += $product['product_price'] * $product['product_quantity'] * $rental_length;
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
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    .cart-container {
        background-color: #ffffff;
        padding: 70px;
        border-radius: 6px;
        box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.1);
        max-width: 900px;
        margin: 150px auto;
    }

    .cart-title {
        font-size: 32px;
        font-weight: 700;
        color:#000; 
        margin-bottom: 30px;
        text-align: center;
    }

    .cart-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 15px;
        background-color: transparent;
    }

    .cart-table th {
        background-color:  #000; 
        color: white;
        padding: 15px;
        font-size: 16px;
        font-weight: 600;
        text-align: center;
    }

    .cart-table td {
        background-color: #F5F5F5;
        padding: 15px;
        text-align: center;
        font-size: 14px;
        color: #333;
        border-radius: 8px;
        box-shadow: 0px 3px 10px rgba(0, 0, 0, 0.05);
    }

    .cart-table img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 8px;
        margin-right: 15px;
    }

    .btn-remove {
        background-color: #000; 
        color: white;
        padding: 8px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .btn-remove:hover {
        background-color:#000 ;
    }

    .btn-edit {
        background-color:  #000; 
        color: white;
        padding: 8px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .btn-edit:hover {
        background-color: #CD853F;
    }

    .cart-total {
        font-size: 24px;
        font-weight: 700;
        color: #000;
        text-align: right;
        margin-top: 30px;
    }

    .checkout-btn {
        background-color: #000; 
        color: white;
        padding: 15px 30px;
        border: none;
        border-radius: 8px;
        font-size: 18px;
        font-weight: 600;
        cursor: pointer;
        width: 100%;
        transition: background-color 0.3s ease;
    }

    .checkout-btn:hover {
        background-color: #A0522D;
    }

    .empty-cart-message {
        font-size: 18px;
        color: #666;
        text-align: center;
        padding: 30px;
    }

    .product-info {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .product-info p {
        margin: 0;
        font-weight: 600;
        color:  #000; 
    }

    .product-info small {
        color: #666;
        font-size: 12px;
    }

    .quantity-input {
        width: 60px;
        padding: 5px;
        border: 1px solid #D2B48C; 
        border-radius: 5px;
        text-align: center;
        font-size: 14px;
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


    <div class="cart-container">
        <h2 class="cart-title">Your Shopping Cart</h2>
        
        <?php if(isset($_SESSION['checkout_error'])): ?>
            <div class="error-message" style="color: red; margin-bottom: 15px; text-align: center;">
                <?php 
                    echo $_SESSION['checkout_error']; 
                    unset($_SESSION['checkout_error']); 
                ?>
                <p style="margin-top: 10px;">
                    <a href="add_payment_column.php" style="background-color: #000; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px;">
                        Fix Payment System
                    </a>
                </p>
            </div>
        <?php endif; ?>
        
        <table class="cart-table">
            <tr>
                <th>Product</th>
                <th>Quantity</th>
            
                <th>Action</th>
            </tr>

            <?php if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                <?php foreach($_SESSION['cart'] as $key => $value): ?>
                    <tr>
                        <td>
                            <div class="product-info">
                                <img src="assets/imgs/<?php echo htmlspecialchars($value['product_image']); ?>">
                                <div>
                                    <p><?php echo htmlspecialchars($value['product_name']); ?></p>
                                    <small><?php echo number_format($value['product_price']); ?> DA</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <form method="POST" action="cart.php">
                                <input type="hidden" name="product_id" value="<?php echo $value['product_id']; ?>">
                                <input type="number" name="product_quantity" value="<?php echo $value['product_quantity']; ?>" min="1" class="quantity-input">
                                <input type="submit" name="edit_quantity" class="btn-edit" value="Edit">
                            </form>
                        </td>
                      
                        <td>
                            <form method="POST" action="cart.php">
                                <input type="hidden" name="product_id" value="<?php echo $value['product_id']; ?>">
                                <input type="submit" name="remove_product" class="btn-remove" value="Remove">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="empty-cart-message">Your cart is empty.</td>
                </tr>
            <?php endif; ?>
        </table>

        <div class="cart-total">
            Total: <?php echo isset($_SESSION['total']) ? number_format($_SESSION['total']) : '0'; ?> DA
        </div>

        <?php if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
            <form method="POST" action="payment_methods.php">
                <input type="submit" class="checkout-btn" name="proceed_to_payment" value="Proceed to Payment">
            </form>
        <?php endif; ?>
    </div>

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
</body>
</html>