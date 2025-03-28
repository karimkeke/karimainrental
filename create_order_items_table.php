<?php
// Include database connection
include('connection.php');

// Check if table already exists
$table_exists = $conn->query("SHOW TABLES LIKE 'order_items'");
if ($table_exists->num_rows > 0) {
    echo "The order_items table already exists.";
    exit;
}

// SQL to create order_items table
$sql = "CREATE TABLE order_items (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id INT(11) NOT NULL,
    product_id INT(11) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_price DECIMAL(10,2) NOT NULL,
    product_quantity INT(11) NOT NULL,
    product_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (order_id),
    INDEX (product_id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
)";

// Execute query
if ($conn->query($sql) === TRUE) {
    echo "Table 'order_items' created successfully! You can now add products to orders.";
} else {
    echo "Error creating table: " . $conn->error;
}

// Close connection
$conn->close();
?> 