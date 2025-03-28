<?php
include('connection.php');

// Check if the table already exists
$table_check = $conn->query("SHOW TABLES LIKE 'messages'");
$table_exists = $table_check->num_rows > 0;

if (!$table_exists) {
    // Create the messages table
    $create_table = "CREATE TABLE messages (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        message_text TEXT NOT NULL,
        is_from_admin TINYINT(1) NOT NULL DEFAULT 0,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_table) === TRUE) {
        echo "<div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f0f0f0; border-radius: 5px;'>";
        echo "<h2 style='color: #28a745;'>Messages table created successfully!</h2>";
        echo "<p>The messages table has been created in the database.</p>";
        echo "<p><a href='admin_messages.php' style='color: #0066cc; text-decoration: none;'>Go to Admin Messages</a></p>";
        echo "<p><a href='user_messages.php' style='color: #0066cc; text-decoration: none;'>Go to User Messages</a></p>";
        echo "</div>";
    } else {
        echo "<div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f0f0f0; border-radius: 5px;'>";
        echo "<h2 style='color: #dc3545;'>Error creating messages table: " . $conn->error . "</h2>";
        echo "</div>";
    }
} else {
    echo "<div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f0f0f0; border-radius: 5px;'>";
    echo "<h2 style='color: #ffc107;'>Messages table already exists</h2>";
    echo "<p>The messages table is already set up in the database.</p>";
    echo "<p><a href='admin_messages.php' style='color: #0066cc; text-decoration: none;'>Go to Admin Messages</a></p>";
    echo "<p><a href='user_messages.php' style='color: #0066cc; text-decoration: none;'>Go to User Messages</a></p>";
    echo "</div>";
}
?> 