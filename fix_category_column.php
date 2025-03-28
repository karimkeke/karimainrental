<?php
// Fix category column type in products table
session_start();
include('connection.php');

// Check if admin is logged in for security
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('location: admin.php');
    exit;
}

// Initialize variables
$messages = [];
$errors = [];

// Get current column info
$check_column = $conn->query("SHOW COLUMNS FROM products LIKE 'category'");
if ($check_column->num_rows > 0) {
    $column_info = $check_column->fetch_assoc();
    $current_type = $column_info['Type'];
    
    // Check if the column needs to be modified
    if (strpos(strtolower($current_type), 'int') === false) {
        $messages[] = "Current category column type is: " . $current_type;
        
        // Step 1: Get all categories to map names to IDs
        $categories = [];
        $categories_result = $conn->query("SELECT category_id, category_name FROM categories");
        if ($categories_result && $categories_result->num_rows > 0) {
            while ($row = $categories_result->fetch_assoc()) {
                $categories[$row['category_name']] = $row['category_id'];
            }
            $messages[] = "Found " . count($categories) . " categories for mapping.";
        } else {
            // Create categories table if it doesn't exist
            $check_table = $conn->query("SHOW TABLES LIKE 'categories'");
            if ($check_table->num_rows == 0) {
                $create_table_sql = "CREATE TABLE categories (
                    category_id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    category_name VARCHAR(255) NOT NULL UNIQUE,
                    category_description TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                
                if ($conn->query($create_table_sql)) {
                    $messages[] = "Categories table created successfully.";
                    
                    // Insert default category
                    $insert_default = $conn->query("INSERT INTO categories (category_name, category_description) VALUES ('Uncategorized', 'Default category for all products')");
                    if ($insert_default) {
                        $messages[] = "Default category added.";
                        $categories['Uncategorized'] = 1;
                    }
                } else {
                    $errors[] = "Error creating categories table: " . $conn->error;
                }
            } else {
                $messages[] = "No categories found. Will create a backup column and convert to integer type.";
            }
        }
        
        // Step 2: Create a backup of the current category column
        if ($conn->query("ALTER TABLE products ADD COLUMN category_backup VARCHAR(255)")) {
            $messages[] = "Created backup column category_backup.";
            
            // Copy current values to backup
            if ($conn->query("UPDATE products SET category_backup = category")) {
                $messages[] = "Backed up current category values.";
                
                // Step 3: Get all unique categories from products
                $product_categories = [];
                $unique_categories = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL");
                if ($unique_categories && $unique_categories->num_rows > 0) {
                    while ($row = $unique_categories->fetch_assoc()) {
                        $product_categories[] = $row['category'];
                    }
                    $messages[] = "Found " . count($product_categories) . " unique categories in products.";
                    
                    // Step 4: Add any missing categories to the categories table
                    foreach ($product_categories as $cat_name) {
                        if (!empty($cat_name) && !isset($categories[$cat_name])) {
                            $stmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
                            $stmt->bind_param("s", $cat_name);
                            if ($stmt->execute()) {
                                $categories[$cat_name] = $conn->insert_id;
                                $messages[] = "Added category: " . $cat_name;
                            }
                        }
                    }
                }
                
                // Step 5: Convert category column to INT
                if ($conn->query("ALTER TABLE products MODIFY COLUMN category INT(11) DEFAULT NULL")) {
                    $messages[] = "Successfully converted category column to INT(11).";
                    
                    // Step 6: Update products with correct category IDs
                    $products_updated = 0;
                    $products_result = $conn->query("SELECT product_id, category_backup FROM products");
                    if ($products_result) {
                        while ($product = $products_result->fetch_assoc()) {
                            $category_name = $product['category_backup'];
                            $product_id = $product['product_id'];
                            
                            if (!empty($category_name) && isset($categories[$category_name])) {
                                $category_id = $categories[$category_name];
                                $update_stmt = $conn->prepare("UPDATE products SET category = ? WHERE product_id = ?");
                                $update_stmt->bind_param("ii", $category_id, $product_id);
                                if ($update_stmt->execute()) {
                                    $products_updated++;
                                }
                            }
                        }
                        $messages[] = "Updated $products_updated products with correct category IDs.";
                    }
                    
                    $messages[] = "Category column fixed successfully! You can now use the category management system properly.";
                } else {
                    $errors[] = "Error converting category column: " . $conn->error;
                }
            } else {
                $errors[] = "Error backing up category values: " . $conn->error;
            }
        } else {
            $errors[] = "Error creating backup column: " . $conn->error;
        }
    } else {
        $messages[] = "Category column is already the correct type: " . $current_type;
    }
} else {
    $errors[] = "Category column not found in products table.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Category Column - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #000;
            --secondary-color: #f8f9fa;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            background-color: var(--secondary-color);
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header {
            background-color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin: 0;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #333;
        }
        
        .btn i {
            margin-right: 5px;
        }
        
        .content {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .alert-info {
            background-color: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
        }
        
        ul {
            padding-left: 20px;
        }
        
        li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Fix Category Column</h1>
        <a href="admin_categories.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Back to Categories
        </a>
    </div>
    
    <div class="content">
        <h2>Results</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h3>Errors:</h3>
                <ul>
                    <?php foreach($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($messages)): ?>
            <div class="alert alert-success">
                <h3>Process Log:</h3>
                <ul>
                    <?php foreach($messages as $message): ?>
                        <li><?php echo htmlspecialchars($message); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="alert alert-info">
            <h3>Next Steps:</h3>
            <p>After fixing the category column:</p>
            <ol>
                <li>Go to the <a href="admin_categories.php">Category Management</a> page to manage your categories</li>
                <li>Review your products and ensure they are assigned to the correct categories</li>
                <li>You may remove the backup column (category_backup) when you are satisfied with the results</li>
            </ol>
        </div>
    </div>
</body>
</html> 