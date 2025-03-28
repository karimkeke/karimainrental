<?php
include('connection.php');

// First check if the column already exists
$check_sql = "SHOW COLUMNS FROM orders LIKE 'payment_method'";
$result = $conn->query($check_sql);
$message = "";

if ($result && $result->num_rows > 0) {
    $message = "Payment method column already exists in the orders table.";
} else {
    // Add the column
    $sql = "ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) DEFAULT 'Cash on Delivery'";

    if ($conn->query($sql) === TRUE) {
        $message = "Payment method column added successfully! The payment system is now ready to use.";
    } else {
        $message = "Error adding column: " . $conn->error;
    }
}

$conn->close();
?> 

<!DOCTYPE html>
<html>
<head>
    <title>Database Update</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 50px;
            text-align: center;
        }
        .message {
            padding: 20px;
            background-color: #f0f0f0;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .redirect-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #000;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>Payment System Setup</h1>
    <div class="message">
        <p id="message" class="<?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
            <?php echo $message; ?>
        </p>
    </div>
    <a href="index.php" class="redirect-btn">Return to Homepage</a>
    <p>or</p>
    <a href="cart.php" class="redirect-btn">Go to Cart</a>
    
    <script>
        // This will automatically redirect after 10 seconds
        setTimeout(function() {
            window.location.href = "index.php";
        }, 10000);
    </script>
</body>
</html> 