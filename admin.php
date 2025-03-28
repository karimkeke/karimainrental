<?php
session_start();
include('connection.php');

// If already logged in as admin, redirect to dashboard
if(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('location: admin_dashboard.php');
    exit;
}

// Check if logout was requested
if(isset($_GET['logout'])) {
    // Clear all admin session variables
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_name']);
    unset($_SESSION['admin_email']);
    
    // Optional: destroy the entire session
    session_unset();
    session_destroy();
    
    // Redirect to login page with a success message
    header('location: admin.php?logout_success=1');
    exit;
}

// Check if logout was successful
if(isset($_GET['logout_success'])) {
    $logout_success = 'You have been successfully logged out.';
}

// Initialize variables
$email = '';
$login_error = '';
$setup_message = '';

// Function to check if column exists in a table
function column_exists($conn, $table, $column) {
    $sql = "SHOW COLUMNS FROM `$table` LIKE '$column'";
    $result = $conn->query($sql);
    return $result->num_rows > 0;
}

// Check if the is_admin column exists in the users table
if (!column_exists($conn, 'users', 'is_admin')) {
    // Add is_admin column to users table
    $alter_table_sql = "ALTER TABLE `users` ADD COLUMN `is_admin` TINYINT(1) NOT NULL DEFAULT 0";
    if ($conn->query($alter_table_sql) === TRUE) {
        $setup_message = "Admin system setup: Added 'is_admin' column to users table.";
    } else {
        $setup_message = "Error setting up admin system: " . $conn->error;
    }
}

// Check if admin account exists
$admin_check_sql = "SELECT * FROM users WHERE user_email = 'admin@gmail.com'";
$admin_result = $conn->query($admin_check_sql);

if ($admin_result->num_rows == 0) {
    // Admin account doesn't exist, create it
    $admin_email = 'admin@gmail.com';
    $admin_password = password_hash('admin', PASSWORD_DEFAULT); // Hash the password
    $admin_name = 'Administrator';
    
    $insert_admin_sql = "INSERT INTO users (user_name, user_email, user_password, is_admin) VALUES (?, ?, ?, 1)";
    $stmt = $conn->prepare($insert_admin_sql);
    $stmt->bind_param("sss", $admin_name, $admin_email, $admin_password);
    
    if ($stmt->execute()) {
        $setup_message .= " Admin account created successfully with email: admin@gmail.com and password: admin";
    } else {
        $setup_message .= " Error creating admin account: " . $conn->error;
    }
} else {
    // Admin account exists, ensure it has admin privileges
    $admin_user = $admin_result->fetch_assoc();
    if (!isset($admin_user['is_admin']) || $admin_user['is_admin'] != 1) {
        $update_admin_sql = "UPDATE users SET is_admin = 1 WHERE user_email = 'admin@gmail.com'";
        if ($conn->query($update_admin_sql) === TRUE) {
            $setup_message .= " Admin privileges granted to existing admin account.";
        }
    }
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    // Validate form data
    if (empty($email) || empty($password)) {
        $login_error = 'Please enter both email and password.';
    } else {
        // Check user credentials
        $query = "SELECT * FROM users WHERE user_email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verify password (handling both legacy plain passwords and hashed passwords)
            $password_verified = false;
            
            if (password_verify($password, $user['user_password'])) {
                $password_verified = true;
            } elseif ($password === $user['user_password']) { 
                // Legacy plain text password match (for backwards compatibility)
                $password_verified = true;
                
                // Upgrade to hashed password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_query = "UPDATE users SET user_password = ? WHERE user_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("si", $hashed_password, $user['user_id']);
                $update_stmt->execute();
            }
            
            if ($password_verified) {
                // Check if user is an admin
                if (isset($user['is_admin']) && $user['is_admin'] == 1) {
                    // Admin login successful
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $user['user_id'];
                    $_SESSION['admin_name'] = $user['user_name'];
                    $_SESSION['admin_email'] = $user['user_email'];
                    
                    // Redirect to dashboard
                    header('location: admin_dashboard.php');
                    exit;
                } else {
                    $login_error = 'You do not have admin privileges.';
                }
            } else {
                $login_error = 'Invalid email or password.';
            }
        } else {
            $login_error = 'Invalid email or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        
        .login-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 400px;
            overflow: hidden;
        }
        
        .login-header {
            background-color: #000;
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }
        
        .login-header p {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .login-form {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: 'Cairo', sans-serif;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: #000;
            outline: none;
        }
        
        .btn-login {
            width: 100%;
            background-color: #000;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            font-family: 'Cairo', sans-serif;
        }
        
        .btn-login:hover {
            background-color: #333;
        }
        
        /* Alert styles */
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }
        
        .alert i {
            margin-right: 8px;
            font-size: 1rem;
        }
        
        .back-to-site {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-to-site a {
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
        }
        
        .back-to-site a:hover {
            color: #000;
        }
        
        .back-to-site a i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Admin Panel</h1>
            <p>Furniture Rental System</p>
        </div>
        
        <div class="login-form">
            <?php if (!empty($login_error)): ?>
                <div class="alert alert-danger">
                    <?php echo $login_error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($setup_message)): ?>
                <div class="alert alert-success">
                    <?php echo $setup_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($logout_success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $logout_success; ?>
                </div>
            <?php endif; ?>
            
            <form action="admin.php" method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn-login">Login</button>
            </form>
            
            <div class="back-to-site">
                <a href="index.php">
                    <i class="fas fa-arrow-left"></i> Back to Main Site
                </a>
            </div>
        </div>
    </div>
</body>
</html> 