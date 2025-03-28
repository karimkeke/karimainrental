<?php
include('connection.php');

// Set maximum execution time to handle potentially large amounts of data
ini_set('max_execution_time', 300); // 5 minutes

// Start with a success message
$message = "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1);'>";
$message .= "<h1 style='color: #000; margin-bottom: 20px;'>Category Management Fix</h1>";

// 1. Check if the categories table exists, if not create it
$check_categories_table = $conn->query("SHOW TABLES LIKE 'categories'");
if ($check_categories_table->num_rows == 0) {
    // Create the categories table
    $create_table_sql = "CREATE TABLE categories (
        category_id INT(11) AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(255) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_table_sql)) {
        $message .= "<p style='color: green;'>✓ Created categories table successfully.</p>";
    } else {
        $message .= "<p style='color: red;'>✗ Error creating categories table: " . $conn->error . "</p>";
        $message .= "</div>";
        echo $message;
        exit;
    }
}

// 2. Check the current structure of the products table to get the category field type
$check_products_category = $conn->query("SHOW COLUMNS FROM products LIKE 'category'");
if ($check_products_category->num_rows > 0) {
    $category_column = $check_products_category->fetch_assoc();
    $message .= "<p>Current category field type: " . $category_column['Type'] . "</p>";
    
    // If the category field is varchar, we need to migrate the data
    if (strpos($category_column['Type'], 'varchar') !== false) {
        // 3. Get all distinct categories from products
        $get_categories = $conn->query("SELECT DISTINCT category FROM products WHERE category != ''");
        if ($get_categories->num_rows > 0) {
            $message .= "<p>Found " . $get_categories->num_rows . " distinct categories in products table.</p>";
            
            // 4. Insert these categories into the categories table
            $categories_added = 0;
            while ($row = $get_categories->fetch_assoc()) {
                $category_name = $row['category'];
                
                // Check if this category already exists in the categories table
                $check_category = $conn->prepare("SELECT category_id FROM categories WHERE category_name = ?");
                $check_category->bind_param("s", $category_name);
                $check_category->execute();
                $result = $check_category->get_result();
                
                if ($result->num_rows == 0) {
                    // Insert the category
                    $insert_category = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
                    $insert_category->bind_param("s", $category_name);
                    if ($insert_category->execute()) {
                        $categories_added++;
                    }
                }
            }
            
            $message .= "<p style='color: green;'>✓ Added $categories_added new categories to categories table.</p>";
            
            // 5. Create a backup of the products table
            $backup_query = "CREATE TABLE products_backup LIKE products";
            if ($conn->query($backup_query)) {
                $conn->query("INSERT INTO products_backup SELECT * FROM products");
                $message .= "<p style='color: green;'>✓ Created backup of products table (products_backup).</p>";
                
                // 6. Add a temporary column for the category ID
                $add_column_query = "ALTER TABLE products ADD COLUMN temp_category_id INT(11) AFTER category";
                if ($conn->query($add_column_query)) {
                    $message .= "<p style='color: green;'>✓ Added temporary category_id column.</p>";
                    
                    // 7. Update the temp_category_id column to match category_id from categories table
                    $update_query = "UPDATE products p 
                                    JOIN categories c ON p.category = c.category_name
                                    SET p.temp_category_id = c.category_id";
                    
                    if ($conn->query($update_query)) {
                        $message .= "<p style='color: green;'>✓ Updated " . $conn->affected_rows . " products with proper category IDs.</p>";
                        
                        // 8. Change the schema: drop the old category column and rename the new one
                        $alter_query = "ALTER TABLE products 
                                       DROP COLUMN category, 
                                       CHANGE COLUMN temp_category_id category INT(11) NOT NULL";
                        
                        if ($conn->query($alter_query)) {
                            $message .= "<p style='color: green;'>✓ Successfully changed the category column in products table to use integer IDs.</p>";
                        } else {
                            $message .= "<p style='color: red;'>✗ Error changing category column: " . $conn->error . "</p>";
                        }
                    } else {
                        $message .= "<p style='color: red;'>✗ Error updating category IDs: " . $conn->error . "</p>";
                    }
                } else {
                    $message .= "<p style='color: red;'>✗ Error adding temporary column: " . $conn->error . "</p>";
                }
            } else {
                $message .= "<p style='color: red;'>✗ Error creating backup table: " . $conn->error . "</p>";
            }
        } else {
            $message .= "<p>No categories found in products table.</p>";
        }
    } else {
        $message .= "<p style='color: green;'>✓ Category field is already an integer type. No migration needed.</p>";
    }
} else {
    $message .= "<p style='color: red;'>✗ Category column not found in products table.</p>";
}

// Final message
$message .= "<p style='margin-top: 30px;'><a href='admin_categories.php' style='display: inline-block; padding: 10px 20px; background-color: #000; color: white; text-decoration: none; border-radius: 5px;'>Go to Category Management</a></p>";
$message .= "</div>";

echo $message;
?> 