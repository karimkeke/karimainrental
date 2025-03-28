<?php
// Fix categories table structure
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

// Check if category_description column exists
$check_column = $conn->query("SHOW COLUMNS FROM categories LIKE 'category_description'");
if ($check_column->num_rows == 0) {
    // Column doesn't exist, add it
    $add_column = $conn->query("ALTER TABLE categories ADD COLUMN category_description TEXT AFTER category_name");
    if ($add_column) {
        $messages[] = "Successfully added 'category_description' column to the categories table.";
    } else {
        $errors[] = "Error adding 'category_description' column: " . $conn->error;
    }
} else {
    $messages[] = "The 'category_description' column already exists in the categories table.";
}

// Check if categories table has required fields
$check_structure = $conn->query("DESCRIBE categories");
$columns = [];
if ($check_structure) {
    while ($row = $check_structure->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    $messages[] = "Current columns in categories table: " . implode(", ", $columns);
}

// Redirect back to categories page after 3 seconds
header("refresh:3;url=admin_categories.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Categories Table - Admin Panel</title>
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
        <h1>Fix Categories Table</h1>
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
            <p>You will be redirected to the Categories Management page in 3 seconds...</p>
            <p>If you are not redirected, <a href="admin_categories.php">click here</a> to go back manually.</p>
        </div>
    </div>
</body>
</html> 