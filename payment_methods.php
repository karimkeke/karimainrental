<?php
session_start();
include('connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if cart exists and is not empty
if (!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
    header("Location: cart.php");
    exit();
}

// Ensure all cart items have a valid rental_length
foreach ($_SESSION['cart'] as $key => $item) {
    if (!isset($item['rental_length']) || empty($item['rental_length']) || !is_numeric($item['rental_length']) || $item['rental_length'] <= 0) {
        // Set a default rental length of 1 month if missing or invalid
        $_SESSION['cart'][$key]['rental_length'] = 1;
    }
}

// Process payment method selection
if (isset($_POST['process_payment'])) {
    $_SESSION['payment_method'] = $_POST['payment_method'];
    
    // Check which payment method was selected
    if ($_POST['payment_method'] == 'credit_card') {
        // For credit card, we'd validate the card details here
        // In a real application, you'd use a payment processor API
        // For now, we'll just simulate a successful payment
        if (isset($_POST['card_number']) && !empty($_POST['card_number']) && 
            isset($_POST['card_expiry']) && !empty($_POST['card_expiry']) && 
            isset($_POST['card_cvv']) && !empty($_POST['card_cvv'])) {
            // Simulate payment processing
            $_SESSION['payment_processed'] = true;
            header("Location: checkout.php");
            exit();
        } else {
            $error = "Please fill in all credit card details.";
        }
    } else if ($_POST['payment_method'] == 'paypal') {
        // For PayPal, we'd redirect to PayPal's API
        // For now, we'll just simulate a successful payment
        $_SESSION['payment_processed'] = true;
        header("Location: checkout.php");
        exit();
    } else if ($_POST['payment_method'] == 'cash_on_delivery') {
        // For cash on delivery, just proceed to checkout
        $_SESSION['payment_processed'] = true;
        header("Location: checkout.php");
        exit();
    }
}

// Calculate cart totals
$total_price = 0;
foreach ($_SESSION['cart'] as $item) {
    $product_price = $item['product_price'];
    $quantity = $item['product_quantity'];
    $rental_length = $item['rental_length'];
    
    $subtotal = $product_price * $quantity * $rental_length;
    $total_price += $subtotal;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Methods</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .payment-container {
            background-color: #ffffff;
            padding: 70px;
            border-radius: 6px;
            box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 900px;
            margin: 150px auto;
        }

        .payment-title {
            font-size: 32px;
            font-weight: 700;
            color:#000; 
            margin-bottom: 30px;
            text-align: center;
        }

        .payment-methods {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .payment-method {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-method:hover {
            border-color: #000;
        }

        .payment-method.active {
            border-color: #000;
            background-color: #f9f9f9;
        }

        .payment-method-header {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .payment-method-header i {
            font-size: 24px;
        }

        .payment-method-body {
            margin-top: 15px;
            display: none;
        }

        .payment-method.active .payment-method-body {
            display: block;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .checkout-btn {
            background-color: #000;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 30px;
            border-radius: 4px;
        }

        .checkout-btn:hover {
            background-color: #333;
        }

        .error-message {
            color: red;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php include('header.php'); ?>

    <div class="payment-container">
        <h1 class="payment-title">Select Payment Method</h1>
        
        <?php if (isset($error)) { ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php } ?>
        
        <div class="order-summary">
            <h2>Order Summary</h2>
            <p>Total: <?php echo number_format($total_price, 2); ?> DA</p>
        </div>

        <form method="post" id="payment-form">
            <div class="payment-methods">
                <div class="payment-method" data-method="credit_card">
                    <div class="payment-method-header">
                        <input type="radio" name="payment_method" value="credit_card" id="credit_card" required>
                        <label for="credit_card">
                            <i class="fas fa-credit-card"></i> Credit Card
                        </label>
                    </div>
                    <div class="payment-method-body">
                        <div class="form-group">
                            <label for="card_number">Card Number</label>
                            <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" pattern="[0-9\s]{13,19}" title="Card number must be between 13 and 19 digits">
                        </div>
                        <div class="form-group">
                            <label for="card_expiry">Expiry Date</label>
                            <input type="text" id="card_expiry" name="card_expiry" placeholder="MM/YY" pattern="(0[1-9]|1[0-2])\/([0-9]{2})" title="Expiry date in format MM/YY">
                        </div>
                        <div class="form-group">
                            <label for="card_cvv">CVV</label>
                            <input type="text" id="card_cvv" name="card_cvv" placeholder="123" pattern="[0-9]{3,4}" title="CVV must be 3 or 4 digits">
                        </div>
                    </div>
                </div>

                <div class="payment-method" data-method="paypal">
                    <div class="payment-method-header">
                        <input type="radio" name="payment_method" value="paypal" id="paypal" required>
                        <label for="paypal">
                            <i class="fab fa-paypal"></i> PayPal
                        </label>
                    </div>
                    <div class="payment-method-body">
                        <p>You will be redirected to PayPal to complete your payment.</p>
                    </div>
                </div>

                <div class="payment-method active" data-method="cash_on_delivery">
                    <div class="payment-method-header">
                        <input type="radio" name="payment_method" value="cash_on_delivery" id="cash_on_delivery" checked required>
                        <label for="cash_on_delivery">
                            <i class="fas fa-money-bill-wave"></i> Cash on Delivery
                        </label>
                    </div>
                    <div class="payment-method-body" style="display: block;">
                        <p>Pay when you receive your items.</p>
                    </div>
                </div>
            </div>

            <button type="submit" name="process_payment" class="checkout-btn">Proceed to Checkout</button>
        </form>
    </div>

    <?php include('footer.php'); ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const paymentMethods = document.querySelectorAll('.payment-method');
            
            paymentMethods.forEach(method => {
                const radio = method.querySelector('input[type="radio"]');
                
                method.addEventListener('click', () => {
                    // First, deactivate all
                    paymentMethods.forEach(m => m.classList.remove('active'));
                    
                    // Then activate the clicked one
                    method.classList.add('active');
                    radio.checked = true;
                });
            });
        });
    </script>
</body>
</html> 