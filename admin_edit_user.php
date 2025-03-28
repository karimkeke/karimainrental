<?php
session_start();
include('connection.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('location: admin.php');
    exit;
}

// Check if user ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('location: admin_users.php');
    exit;
}

$user_id = $_GET['id'];

// Initialize variables
$name = '';
$email = '';
$phone = '';
$address = '';
$city = '';
$errors = [];
$success_message = '';

// Get user data
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('location: admin_users.php');
    exit;
}

$user = $result->fetch_assoc();
$name = $user['user_name'];
$email = $user['user_email'];
$phone = isset($user['user_phone']) ? $user['user_phone'] : '';
$address = isset($user['user_address']) ? $user['user_address'] : '';
$city = isset($user['user_city']) ? $user['user_city'] : '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate name
    if (empty($_POST['name'])) {
        $errors[] = "Name is required";
    } else {
        $name = $_POST['name'];
    }
    
    // Validate email
    if (empty($_POST['email'])) {
        $errors[] = "Email is required";
    } else if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {
        // Check if email already exists (but not for this user)
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_email = ? AND user_id != ?");
        $stmt->bind_param("si", $_POST['email'], $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Email already exists. Please use a different email.";
        } else {
            $email = $_POST['email'];
        }
    }
    
    // Get phone (optional)
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    
    // Get address (optional)
    $address = isset($_POST['address']) ? $_POST['address'] : '';
    
    // Get city (optional)
    $city = isset($_POST['city']) ? $_POST['city'] : '';
    
    // Check if password is being updated
    $update_password = false;
    if (!empty($_POST['password'])) {
        $update_password = true;
        
        // Validate password
        if (strlen($_POST['password']) < 6) {
            $errors[] = "Password must be at least 6 characters";
        } else if ($_POST['password'] !== $_POST['confirm_password']) {
            $errors[] = "Passwords do not match";
        }
    }
    
    // If no errors, update user
    if (empty($errors)) {
        if ($update_password) {
            // Update user with new password
            $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            
            $query = "UPDATE users SET 
                    user_name = ?, 
                    user_email = ?, 
                    user_password = ?,
                    user_phone = ?, 
                    user_address = ?, 
                    user_city = ? 
                    WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssssssi", $name, $email, $password_hash, $phone, $address, $city, $user_id);
        } else {
            // Update user without changing password
            $query = "UPDATE users SET 
                    user_name = ?, 
                    user_email = ?, 
                    user_phone = ?, 
                    user_address = ?, 
                    user_city = ? 
                    WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssssi", $name, $email, $phone, $address, $city, $user_id);
        }
        
        if ($stmt->execute()) {
            $success_message = "User updated successfully!";
        } else {
            $errors[] = "Error updating user: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
            --header-height: 60px;
            --primary-color: #000;
            --secondary-color: #f8f9fa;
            --accent-color: #333;
            --text-color: #333;
            --light-text: #666;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --success-color: #28a745;
            --info-color: #17a2b8;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            background-color: var(--secondary-color);
            color: var(--text-color);
            min-height: 100vh;
        }
        
        /* Admin Layout */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .admin-sidebar {
            width: var(--sidebar-width);
            background-color: var(--primary-color);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header h2 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .sidebar-user {
            font-size: 0.85rem;
            opacity: 0.8;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.2);
            border-left: 4px solid white;
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .badge {
            background-color: #dc3545;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.7rem;
        }
        
        .logout-btn {
            margin-top: 20px;
            padding: 12px 20px;
            background-color: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
        }
        
        /* Main content */
        .admin-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
        }
        
        .admin-header {
            background-color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-header h1 {
            font-size: 1.5rem;
            color: var(--primary-color);
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 15px;
            background-color: var(--secondary-color);
            color: var(--text-color);
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background-color: #e9ecef;
        }
        
        .back-btn i {
            margin-right: 5px;
        }
        
        /* Alert messages */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        /* Form Styling */
        .form-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-bottom: 20px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group.full-width {
            grid-column: span 2;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .form-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-family: 'Cairo', sans-serif;
            transition: border 0.3s ease;
        }
        
        .form-input:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .form-textarea {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-family: 'Cairo', sans-serif;
            min-height: 150px;
            resize: vertical;
        }
        
        .form-textarea:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .password-section {
            margin-top: 30px;
            border-top: 1px solid #e0e0e0;
            padding-top: 20px;
        }
        
        .password-section-title {
            margin-bottom: 20px;
            color: var(--primary-color);
        }
        
        .submit-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            background-color: var(--accent-color);
            transform: translateY(-2px);
        }
        
        .submit-btn i {
            margin-right: 5px;
        }
        
        /* Field error messages */
        .field-error {
            color: var(--danger-color);
            font-size: 0.85rem;
            margin-top: 5px;
        }
        
        /* Error list */
        .error-list {
            list-style-type: none;
            padding-left: 0;
        }
        
        .error-list li {
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }
        
        .error-list li:before {
            content: "\f071";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            margin-right: 8px;
            color: var(--danger-color);
        }
        
        .help-text {
            font-size: 0.85rem;
            color: var(--light-text);
            margin-top: 5px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 70px;
            }
            
            .sidebar-header h2,
            .sidebar-user,
            .sidebar-menu span {
                display: none;
            }
            
            .admin-content {
                margin-left: 70px;
            }
            
            .sidebar-menu a {
                padding: 15px;
                justify-content: center;
            }
            
            .sidebar-menu i {
                margin-right: 0;
                font-size: 1.2rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
                <div class="sidebar-user">
                    <?php echo isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin'; ?>
                </div>
            </div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="admin_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="admin_products.php">
                        <i class="fas fa-couch"></i>
                        <span>Products</span>
                    </a>
                </li>
                <li>
                    <a href="admin_categories.php">
                        <i class="fas fa-tags"></i>
                        <span>Categories</span>
                    </a>
                </li>
                <li>
                    <a href="admin_orders.php">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Orders</span>
                    </a>
                </li>
                <li>
                    <a href="admin_users.php" class="active">
                        <i class="fas fa-users"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li>
                    <a href="admin_messages.php">
                        <i class="fas fa-comments"></i>
                        <span>Messages <?php if(isset($unread_messages_count) && $unread_messages_count > 0): ?><span class="badge"><?php echo $unread_messages_count; ?></span><?php endif; ?></span>
                    </a>
                </li>
                <li>
                    <a href="admin.php?logout=1" class="logout-btn" onclick="return confirm('Are you sure you want to logout?')">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-content">
            <div class="admin-header">
                <h1>Edit User: <?php echo htmlspecialchars($name); ?></h1>
                <a href="admin_users.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Users
                </a>
            </div>
            
            <!-- Success or Error Messages -->
            <?php if(!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if(!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong>Please fix the following errors:</strong>
                        <ul class="error-list">
                            <?php foreach($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Edit User Form -->
            <div class="form-container">
                <form action="admin_edit_user.php?id=<?php echo $user_id; ?>" method="POST">
                    <div class="form-grid">
                        <!-- Name -->
                        <div class="form-group">
                            <label for="name" class="form-label">Full Name*</label>
                            <input type="text" id="name" name="name" class="form-input" value="<?php echo htmlspecialchars($name); ?>" required>
                        </div>
                        
                        <!-- Email -->
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address*</label>
                            <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        
                        <!-- Phone -->
                        <div class="form-group">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" id="phone" name="phone" class="form-input" value="<?php echo htmlspecialchars($phone); ?>">
                        </div>
                        
                        <!-- City -->
                        <div class="form-group">
                            <label for="city" class="form-label">City</label>
                            <input type="text" id="city" name="city" class="form-input" value="<?php echo htmlspecialchars($city); ?>">
                        </div>
                        
                        <!-- Address -->
                        <div class="form-group full-width">
                            <label for="address" class="form-label">Address</label>
                            <textarea id="address" name="address" class="form-textarea"><?php echo htmlspecialchars($address); ?></textarea>
                        </div>
                        
                        <!-- Password Section -->
                        <div class="form-group full-width password-section">
                            <h3 class="password-section-title">Change Password (optional)</h3>
                            <p class="help-text">Leave blank to keep the current password</p>
                        </div>
                        
                        <!-- Password -->
                        <div class="form-group">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" id="password" name="password" class="form-input">
                            <div class="field-error" id="password-error"></div>
                        </div>
                        
                        <!-- Confirm Password -->
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input">
                            <div class="field-error" id="confirm-password-error"></div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="form-group full-width" style="text-align: center; margin-top: 20px;">
                            <button type="submit" class="submit-btn">
                                <i class="fas fa-save"></i> Update User
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script>
        // Client-side validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const passwordError = document.getElementById('password-error');
            const confirmPasswordError = document.getElementById('confirm-password-error');
            
            function checkPasswordMatch() {
                if (confirmPasswordInput.value && confirmPasswordInput.value !== passwordInput.value) {
                    confirmPasswordError.textContent = 'Passwords do not match';
                } else {
                    confirmPasswordError.textContent = '';
                }
            }
            
            // Validate password
            passwordInput.addEventListener('input', function() {
                if (this.value && this.value.length < 6) {
                    passwordError.textContent = 'Password must be at least 6 characters';
                } else {
                    passwordError.textContent = '';
                }
                checkPasswordMatch();
            });
            
            // Validate confirm password
            confirmPasswordInput.addEventListener('input', checkPasswordMatch);
            
            // Form submission validation
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                if (passwordInput.value && passwordInput.value.length < 6) {
                    passwordError.textContent = 'Password must be at least 6 characters';
                    isValid = false;
                }
                
                if (passwordInput.value && passwordInput.value !== confirmPasswordInput.value) {
                    confirmPasswordError.textContent = 'Passwords do not match';
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html> 