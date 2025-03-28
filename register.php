<?php
 session_start();
 include('connection.php');

 if (isset($_POST['register'])) {
  $name = $_POST['name'];
  $email = $_POST['email'];
  $password = $_POST['password'];
  $confirmpassword = $_POST['confirmpassword'];
  if ($password !== $confirmpassword) {
      header('Location: register.php?error=passwords do not match');
      exit();
  } else if (strlen($password) < 6) {
      header('Location: register.php?error=password must be at least 6 characters');
      exit();
  } else {
      $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_email = ?");
      $stmt->bind_param('s', $email);
      $stmt->execute();
      $result = $stmt->get_result();
      if ($result->num_rows > 0) {
          header('Location: register.php?error=user with this email already exists');
          exit();
      } else {
          $stmt = $conn->prepare("INSERT INTO users (user_name, user_email, user_password) VALUES (?, ?, ?)");
          $stmt->bind_param('sss', $name, $email, $password);
          if ($stmt->execute()) {
              $user_id = $stmt->insert_id;
              $_SESSION['user_id'] = $user_id;
              $_SESSION['user_email'] = $email;
              $_SESSION['user_name'] = $name;
              $_SESSION['logged_in'] = true;
              header("location: account.php?message=you registered successfully");
              exit();
          } else {
              header('Location: register.php?error=cannot create an account at this moment');
              exit();
          }
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
    <title>Register - Furniture Rental</title>
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

        .register-section {
            max-width: 1200px;
            margin: 100px auto 80px;
            padding: 0 20px;
            display: flex;
            align-items: flex-start;
            gap: 40px;
        }

        .register-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            order: 1;
        }

        .register-image {
            flex: 1;
            display: none;
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            height: 600px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            order: 2;
        }

        @media (min-width: 992px) {
            .register-image {
                display: block;
            }
        }

        .register-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .register-form-container {
            background-color: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 40px;
            width: 100%;
            max-width: 450px;
        }

        .register-form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .register-form-header h2 {
            font-size: 28px;
            color: #000;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .register-form-header p {
            color: #666;
            margin-top: 0;
        }

        .register-form .form-group {
            margin-bottom: 20px;
        }

        .register-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .register-form input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #eee;
            border-radius: 8px;
            font-family: 'Cairo', sans-serif;
            font-size: 15px;
            transition: border 0.3s ease;
        }

        .register-form input:focus {
            border-color: #000;
            outline: none;
        }

        .register-button {
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
        }

        .register-button:hover {
            background-color: #333;
            transform: translateY(-2px);
        }

        .register-button i {
            margin-right: 8px;
        }

        .register-footer {
            text-align: center;
            margin-top: 25px;
            color: #666;
        }

        .register-footer a {
            color: #000;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .register-footer a:hover {
            color: #555;
            text-decoration: underline;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
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

        .password-requirements {
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .register-form-container {
                padding: 30px;
            }
            
            .register-section {
                margin: 80px auto 60px;
            }
        }

        @media (max-width: 480px) {
            .register-form-container {
                padding: 20px;
            }
            
            .register-form-header h2 {
                font-size: 24px;
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

    <section class="register-section">
        <div class="register-content">
            <div class="register-form-container">
                <div class="register-form-header">
                    <h2>Create an Account</h2>
                    <p>Join us and start renting furniture today</p>
                </div>

                <?php if(isset($_GET['error'])): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if(isset($_GET['message'])): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['message']); ?>
                    </div>
                <?php endif; ?>

                <form class="register-form" action="register.php" method="POST">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Create a password" required>
                        <p class="password-requirements">Password must be at least 6 characters long</p>
                    </div>

                    <div class="form-group">
                        <label for="confirmpassword">Confirm Password</label>
                        <input type="password" id="confirmpassword" name="confirmpassword" placeholder="Confirm your password" required>
                    </div>

                    <button type="submit" class="register-button" name="register">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                </form>

                <div class="register-footer">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>

        <div class="register-image">
            <img src="https://images.unsplash.com/photo-1586023492125-27b2c045efd7?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1287&q=80" alt="Furniture">
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
            // Animate register form container
            const registerFormContainer = document.querySelector('.register-form-container');
            registerFormContainer.style.opacity = 0;
            registerFormContainer.style.transform = 'translateY(20px)';
            registerFormContainer.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            
            setTimeout(() => {
                registerFormContainer.style.opacity = 1;
                registerFormContainer.style.transform = 'translateY(0)';
            }, 200);
        });
    </script>
</body>
</html>
