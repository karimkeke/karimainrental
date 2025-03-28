<?php
session_start();
include('connection.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('location: admin.php');
    exit;
}

// Get unread message count
$table_check = $conn->query("SHOW TABLES LIKE 'messages'");
$unread_messages_count = 0;

if ($table_check->num_rows > 0) {
    $unread_messages_query = "SELECT COUNT(*) as unread_count FROM messages WHERE is_from_admin = 0 AND is_read = 0";
    $unread_messages_result = $conn->query($unread_messages_query);
    $unread_messages_count = $unread_messages_result->fetch_assoc()['unread_count'];
}

// Initialize variables
$settings = [];
$success_message = '';
$error_message = '';

// Check if settings table exists, if not create it
$table_check = $conn->query("SHOW TABLES LIKE 'settings'");
if ($table_check->num_rows == 0) {
    $create_table_sql = "CREATE TABLE settings (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(255) NOT NULL UNIQUE,
        setting_value TEXT,
        setting_group VARCHAR(50) NOT NULL DEFAULT 'general',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_table_sql)) {
        // Insert default settings
        $default_settings = [
            ['site_name', 'Furniture Rental System', 'general'],
            ['site_description', 'Your one-stop solution for furniture rentals', 'general'],
            ['contact_email', 'contact@example.com', 'contact'],
            ['contact_phone', '+123456789', 'contact'],
            ['contact_address', '123 Main St, City, Country', 'contact'],
            ['business_hours', 'Mon-Fri: 9AM-5PM, Sat: 10AM-2PM, Sun: Closed', 'contact'],
            ['currency', 'DA', 'finance'],
            ['tax_rate', '19', 'finance'],
            ['min_rental_days', '3', 'rental'],
            ['max_rental_days', '90', 'rental'],
            ['facebook_url', 'https://facebook.com/', 'social'],
            ['instagram_url', 'https://instagram.com/', 'social'],
            ['twitter_url', 'https://twitter.com/', 'social']
        ];
        
        $insert_stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, setting_group) VALUES (?, ?, ?)");
        
        foreach ($default_settings as $setting) {
            $insert_stmt->bind_param("sss", $setting[0], $setting[1], $setting[2]);
            $insert_stmt->execute();
        }
    } else {
        $error_message = "Error creating settings table: " . $conn->error;
    }
}

// Function to get all settings
function get_all_settings($conn) {
    $result = $conn->query("SELECT * FROM settings ORDER BY setting_group, setting_key");
    $settings = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = [
                'value' => $row['setting_value'],
                'group' => $row['setting_group']
            ];
        }
    }
    
    return $settings;
}

// Function to update a setting
function update_setting($conn, $key, $value) {
    $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
    $stmt->bind_param("ss", $value, $key);
    return $stmt->execute();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        $errors = false;
        
        foreach ($_POST as $key => $value) {
            // Skip non-setting fields
            if ($key === 'update_settings') continue;
            
            // Update the setting
            if (!update_setting($conn, $key, $value)) {
                $errors = true;
            }
        }
        
        if (!$errors) {
            $success_message = "Settings updated successfully!";
        } else {
            $error_message = "Error updating some settings. Please try again.";
        }
    }
}

// Get all settings
$settings = get_all_settings($conn);

// Group settings by their group
$grouped_settings = [];
foreach ($settings as $key => $setting) {
    $grouped_settings[$setting['group']][$key] = $setting['value'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Panel</title>
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
        
        /* Settings Tabs */
        .settings-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .tab-btn {
            background-color: var(--secondary-color);
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            font-family: 'Cairo', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tab-btn:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }
        
        .tab-btn.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        /* Settings Form */
        .settings-form {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .settings-section {
            display: none;
            margin-bottom: 25px;
        }
        
        .settings-section.active {
            display: block;
        }
        
        .settings-section h3 {
            color: var(--primary-color);
            font-size: 1.2rem;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .form-group {
            margin-bottom: 20px;
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
            min-height: 100px;
            resize: vertical;
        }
        
        .form-textarea:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .settings-footer {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .save-btn {
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .save-btn:hover {
            background-color: var(--accent-color);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 70px;
                overflow: visible;
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
                    <?php echo $_SESSION['admin_name']; ?>
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
                    <a href="admin_users.php">
                        <i class="fas fa-users"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li>
                    <a href="admin_messages.php">
                        <i class="fas fa-comments"></i>
                        <span>Messages <?php if($unread_messages_count > 0): ?><span class="badge" style="background-color: #dc3545; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem;"><?php echo $unread_messages_count; ?></span><?php endif; ?></span>
                    </a>
                </li>
                <li>
                    <a href="admin_settings.php" class="active">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
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
                <h1>System Settings</h1>
                <a href="index.php" class="back-to-site">
                    <i class="fas fa-external-link-alt"></i> View Main Site
                </a>
            </div>
            
            <!-- Success or Error Messages -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Settings Tabs -->
            <div class="settings-tabs">
                <button class="tab-btn active" data-target="general">
                    <i class="fas fa-globe"></i> General Settings
                </button>
                <button class="tab-btn" data-target="contact">
                    <i class="fas fa-envelope"></i> Contact Information
                </button>
                <button class="tab-btn" data-target="finance">
                    <i class="fas fa-dollar-sign"></i> Finance Settings
                </button>
                <button class="tab-btn" data-target="rental">
                    <i class="fas fa-calendar-alt"></i> Rental Settings
                </button>
                <button class="tab-btn" data-target="social">
                    <i class="fas fa-share-alt"></i> Social Media
                </button>
            </div>
            
            <!-- Settings Form -->
            <form action="admin_settings.php" method="POST" class="settings-form">
                <!-- General Settings -->
                <div class="settings-section active" id="general-settings">
                    <h3>General Settings</h3>
                    
                    <div class="form-group">
                        <label for="site_name" class="form-label">Site Name</label>
                        <input type="text" id="site_name" name="site_name" class="form-input" value="<?php echo htmlspecialchars($grouped_settings['general']['site_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="site_description" class="form-label">Site Description</label>
                        <textarea id="site_description" name="site_description" class="form-textarea"><?php echo htmlspecialchars($grouped_settings['general']['site_description'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <!-- Contact Information -->
                <div class="settings-section" id="contact-settings">
                    <h3>Contact Information</h3>
                    
                    <div class="form-group">
                        <label for="contact_email" class="form-label">Contact Email</label>
                        <input type="email" id="contact_email" name="contact_email" class="form-input" value="<?php echo htmlspecialchars($grouped_settings['contact']['contact_email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_phone" class="form-label">Contact Phone</label>
                        <input type="text" id="contact_phone" name="contact_phone" class="form-input" value="<?php echo htmlspecialchars($grouped_settings['contact']['contact_phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_address" class="form-label">Business Address</label>
                        <textarea id="contact_address" name="contact_address" class="form-textarea"><?php echo htmlspecialchars($grouped_settings['contact']['contact_address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="business_hours" class="form-label">Business Hours</label>
                        <input type="text" id="business_hours" name="business_hours" class="form-input" value="<?php echo htmlspecialchars($grouped_settings['contact']['business_hours'] ?? ''); ?>">
                    </div>
                </div>
                
                <!-- Finance Settings -->
                <div class="settings-section" id="finance-settings">
                    <h3>Finance Settings</h3>
                    
                    <div class="form-group">
                        <label for="currency" class="form-label">Currency Symbol</label>
                        <input type="text" id="currency" name="currency" class="form-input" value="<?php echo htmlspecialchars($grouped_settings['finance']['currency'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                        <input type="number" id="tax_rate" name="tax_rate" class="form-input" min="0" max="100" step="0.01" value="<?php echo htmlspecialchars($grouped_settings['finance']['tax_rate'] ?? ''); ?>">
                    </div>
                </div>
                
                <!-- Rental Settings -->
                <div class="settings-section" id="rental-settings">
                    <h3>Rental Settings</h3>
                    
                    <div class="form-group">
                        <label for="min_rental_days" class="form-label">Minimum Rental Days</label>
                        <input type="number" id="min_rental_days" name="min_rental_days" class="form-input" min="1" value="<?php echo htmlspecialchars($grouped_settings['rental']['min_rental_days'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="max_rental_days" class="form-label">Maximum Rental Days</label>
                        <input type="number" id="max_rental_days" name="max_rental_days" class="form-input" min="1" value="<?php echo htmlspecialchars($grouped_settings['rental']['max_rental_days'] ?? ''); ?>">
                    </div>
                </div>
                
                <!-- Social Media -->
                <div class="settings-section" id="social-settings">
                    <h3>Social Media Links</h3>
                    
                    <div class="form-group">
                        <label for="facebook_url" class="form-label">Facebook URL</label>
                        <input type="url" id="facebook_url" name="facebook_url" class="form-input" value="<?php echo htmlspecialchars($grouped_settings['social']['facebook_url'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="instagram_url" class="form-label">Instagram URL</label>
                        <input type="url" id="instagram_url" name="instagram_url" class="form-input" value="<?php echo htmlspecialchars($grouped_settings['social']['instagram_url'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="twitter_url" class="form-label">Twitter URL</label>
                        <input type="url" id="twitter_url" name="twitter_url" class="form-input" value="<?php echo htmlspecialchars($grouped_settings['social']['twitter_url'] ?? ''); ?>">
                    </div>
                </div>
                
                <!-- Form Footer -->
                <div class="settings-footer">
                    <button type="submit" name="update_settings" class="save-btn">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                </div>
            </form>
        </main>
    </div>
    
    <script>
        // Tab Navigation
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-btn');
            const settingsSections = document.querySelectorAll('.settings-section');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Hide all sections
                    settingsSections.forEach(section => section.classList.remove('active'));
                    
                    // Show target section
                    const targetSection = document.getElementById(this.dataset.target + '-settings');
                    if (targetSection) {
                        targetSection.classList.add('active');
                    }
                });
            });
        });
    </script>
</body>
</html> 