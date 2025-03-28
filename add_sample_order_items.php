<?php
// Include database connection
include('connection.php');

// First, check if we have orders in the orders table
$check_orders = $conn->query("SELECT id FROM orders LIMIT 1");
if ($check_orders->num_rows == 0) {
    echo "Error: No orders found in the database. Please create at least one order first.";
    exit;
}

// Check if we have products in the products table
$check_products = $conn->query("SELECT product_id, product_name, product_price, product_image FROM products LIMIT 5");
if ($check_products->num_rows == 0) {
    echo "Error: No products found in the database. Please add products first.";
    exit;
}

// Get order IDs
$orders = [];
$orders_result = $conn->query("SELECT id FROM orders ORDER BY id");
while ($row = $orders_result->fetch_assoc()) {
    $orders[] = $row['id'];
}

// Get products
$products = [];
while ($row = $check_products->fetch_assoc()) {
    $products[] = $row;
}

// Check if order_items table exists
$table_exists = $conn->query("SHOW TABLES LIKE 'order_items'");
if ($table_exists->num_rows == 0) {
    echo "The order_items table doesn't exist. Please run create_order_items_table.php first.";
    exit;
}

// Check if order_items already has data
$check_items = $conn->query("SELECT id FROM order_items LIMIT 1");
if ($check_items->num_rows > 0) {
    echo "There are already items in the order_items table. Do you want to add more? <br>";
    echo "<a href='add_sample_order_items.php?confirm=yes'>Yes, add more items</a> | ";
    echo "<a href='admin_orders.php'>No, go back to orders</a>";
    
    if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
        exit;
    }
}

// Insert sample order items
$counter = 0;
$stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, product_price, product_quantity, product_image) VALUES (?, ?, ?, ?, ?, ?)");

foreach ($orders as $order_id) {
    // Add 1-3 products to each order
    $num_products = rand(1, 3);
    
    for ($i = 0; $i < $num_products; $i++) {
        // Pick a random product
        $product = $products[array_rand($products)];
        $quantity = rand(1, 3);
        
        $stmt->bind_param("iisdis", 
            $order_id, 
            $product['product_id'],
            $product['product_name'],
            $product['product_price'],
            $quantity,
            $product['product_image']
        );
        
        if ($stmt->execute()) {
            $counter++;
        } else {
            echo "Error adding item: " . $stmt->error . "<br>";
        }
    }
}

echo "Successfully added $counter sample order items! <br>";
echo "<a href='admin_orders.php'>Go to Orders</a>";

// Close connection
$conn->close();
?> 