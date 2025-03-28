<?php
session_start();
include('connection.php');
if (isset($_GET['logout'])) {
    // Clear all session variables
    $_SESSION = array();
    
    // If a session cookie is used, clear it as well
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-42000, '/');
    }
    
    // Clear "remember me" cookies if they exist
    if (isset($_COOKIE['user_email'])) {
        setcookie('user_email', '', time()-42000, '/');
        setcookie('user_remember', '', time()-42000, '/');
    }
    
    // Destroy the session
    session_destroy(); 
    
    // Redirect to login page with a message
    header('location: login.php?message=You have been successfully logged out');
    exit;
}
if (!isset($_SESSION['logged_in'])) {
    header('location: login.php');
    exit;
}
if(isset($_POST['change_password'])){
    $password = $_POST['password'];
    $confirmpassword = $_POST['confirmpassword'];
    $user_email = $_SESSION['user_email'];

    if ($password !== $confirmpassword) {
        header('location: accountdetails.php?error=Passwords don\'t match');
        exit;
    } elseif (strlen($password) < 6) {
        header('location: accountdetails.php?error=Password must be at least 6 characters');
        exit;
    } else {
        $stmt = $conn->prepare("UPDATE users SET user_password=? WHERE user_email=?");
        $stmt->bind_param('ss', $password, $user_email);
        if($stmt->execute()) {
            header('location: accountdetails.php?message=Password updated successfully');
            exit;
        } else {
            header('location: accountdetails.php?error=Couldn\'t update password');
            exit;
        }
    }
}

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

// Calculate cart totals for the header
if(isset($_SESSION['cart'])) {
    calculatetotalcart();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Details - Furniture Rental</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Cairo', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            position: relative;
        }

        .account-details-section {
            max-width: 1200px;
            margin: 100px auto 80px;
            padding: 0 20px;
            display: flex;
            align-items: flex-start;
            gap: 40px;
        }

        .profile-sidebar {
            flex: 0 0 300px;
            background-color: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background-color: #f0f0f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            transition: transform 0.3s ease;
        }

        .profile-avatar:hover {
            transform: scale(1.05);
        }

        .profile-avatar i {
            font-size: 60px;
            color: #000;
        }

        .profile-name {
            font-size: 22px;
            font-weight: 700;
            color: #000;
            margin-bottom: 8px;
        }

        .profile-email {
            font-size: 14px;
            color: #666;
            margin-bottom: 25px;
        }

        .profile-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .profile-menu li {
            margin-bottom: 12px;
        }

        .profile-menu a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .profile-menu a:hover {
            background-color: #f8f9fa;
            color: #000;
        }

        .profile-menu a.active {
            background-color: #f0f0f0;
            color: #000;
            font-weight: 600;
        }

        .profile-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .password-form-container {
            flex: 1;
            background-color: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 40px;
        }

        .password-form-header {
            margin-bottom: 30px;
        }

        .password-form-header h2 {
            font-size: 24px;
            color: #000;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .password-form-header p {
            color: #666;
            margin-top: 0;
            line-height: 1.5;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-message i {
            margin-right: 10px;
            font-size: 18px;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .success-message i {
            margin-right: 10px;
            font-size: 18px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #eee;
            border-radius: 8px;
            font-family: 'Cairo', sans-serif;
            font-size: 15px;
            transition: border 0.3s ease;
        }

        .form-group input:focus {
            border-color: #000;
            outline: none;
        }

        .password-requirements {
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }

        .update-button {
            width: 100%;
            padding: 14px;
            background-color: #000;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .update-button:hover {
            background-color: #333;
            transform: translateY(-2px);
        }

        .update-button i {
            margin-right: 8px;
        }

        .divider {
            height: 1px;
            background-color: #eee;
            margin: 20px 0;
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

        @media (max-width: 992px) {
            .account-details-section {
                flex-direction: column;
            }
            
            .profile-sidebar {
                width: 100%;
                flex: none;
                margin-bottom: 30px;
            }
            
            .password-form-container {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .password-form-container {
                padding: 30px;
            }
            
            .account-details-section {
                margin: 80px auto 60px;
            }
        }

        @media (max-width: 480px) {
            .password-form-container {
                padding: 20px;
            }
            
            .password-form-header h2 {
                font-size: 22px;
            }
            
            .profile-avatar {
                width: 100px;
                height: 100px;
            }
            
            .profile-avatar i {
                font-size: 50px;
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
                            <p>Hi, <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                            <a href="accountdetails.php">Account Details</a>
                            <a href="my_orders.php">My Orders</a>
                            <a href="account.php">My Account</a>
                            <a href="accountdetails.php?logout=1">Logout</a>
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

    <section class="account-details-section">
        <div class="profile-sidebar">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h2 class="profile-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h2>
                <p class="profile-email"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
            </div>
            
            <div class="divider"></div>
            
            <ul class="profile-menu">
                <li><a href="account.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="my_orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a></li>
                <li><a href="accountdetails.php" class="active"><i class="fas fa-user-cog"></i> Account Settings</a></li>
                <li><a href="accountdetails.php?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <div class="password-form-container">
            <div class="password-form-header">
                <h2>Change Your Password</h2>
                <p>Update your password to maintain account security. Your new password must be at least 6 characters long.</p>
            </div>
            
            <?php if(isset($_GET['error'])): ?>
                <div class="error-message"> 
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($_GET['error']); ?> 
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['message'])): ?>
                <div class="success-message"> 
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_GET['message']); ?> 
                </div>
            <?php endif; ?>
            
            <form id="account-form" method="POST" action="accountdetails.php">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your new password" required>
                    <p class="password-requirements">Password must be at least 6 characters long</p>
                </div>
                
                <div class="form-group">
                    <label for="confirmpassword">Confirm New Password</label>
                    <input type="password" id="confirmpassword" name="confirmpassword" placeholder="Confirm your new password" required>
                </div>
                
                <button type="submit" class="update-button" name="change_password">
                    <i class="fas fa-key"></i> Update Password
                </button>
            </form>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animate form containers
            const profileSidebar = document.querySelector('.profile-sidebar');
            const passwordFormContainer = document.querySelector('.password-form-container');
            
            profileSidebar.style.opacity = 0;
            profileSidebar.style.transform = 'translateY(20px)';
            profileSidebar.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            
            passwordFormContainer.style.opacity = 0;
            passwordFormContainer.style.transform = 'translateY(20px)';
            passwordFormContainer.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            
            setTimeout(() => {
                profileSidebar.style.opacity = 1;
                profileSidebar.style.transform = 'translateY(0)';
            }, 200);
            
            setTimeout(() => {
                passwordFormContainer.style.opacity = 1;
                passwordFormContainer.style.transform = 'translateY(0)';
            }, 400);
        });
    </script>
</body>
</html> 