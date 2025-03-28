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
} else {
    // Create messages table if it doesn't exist
    include('create_messages_table.php');
}

// Initialize variables
$errors = [];
$success_message = "";
$users = [];
$messages = [];
$selected_user_id = null;
$selected_user_name = "";
$selected_user_email = "";

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'send_message') {
    if (empty($_POST['message_text'])) {
        $errors[] = "Message cannot be empty";
    } elseif (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
        $errors[] = "Invalid user selected";
    } else {
        $message_text = trim($_POST['message_text']);
        $user_id = $_POST['user_id'];
        
        $query = "INSERT INTO messages (user_id, message_text, is_from_admin) VALUES (?, ?, 1)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $user_id, $message_text);
        
        if ($stmt->execute()) {
            $success_message = "Message sent successfully!";
            
            // Redirect to avoid form resubmission
            header("Location: admin_messages.php?user_id=" . $user_id . "&success=sent");
            exit;
        } else {
            $errors[] = "Error sending message: " . $conn->error;
        }
    }
}

// Handle success messages from redirects
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'sent') {
        $success_message = "Message sent successfully!";
    }
}

// Get selected user
if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $selected_user_id = $_GET['user_id'];
    
    // Get user info
    $user_query = "SELECT user_id, user_name, user_email as email FROM users WHERE user_id = ?";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("i", $selected_user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    
    if ($user_result->num_rows > 0) {
        $user_data = $user_result->fetch_assoc();
        $selected_user_name = $user_data['user_name'];
        $selected_user_email = $user_data['email'];
        
        // Mark messages from this user as read
        $update_read = "UPDATE messages SET is_read = 1 WHERE user_id = ? AND is_from_admin = 0";
        $read_stmt = $conn->prepare($update_read);
        $read_stmt->bind_param("i", $selected_user_id);
        $read_stmt->execute();
        
        // Get conversation with selected user
        $msg_query = "SELECT * FROM messages WHERE user_id = ? ORDER BY created_at ASC";
        $msg_stmt = $conn->prepare($msg_query);
        $msg_stmt->bind_param("i", $selected_user_id);
        $msg_stmt->execute();
        $messages_result = $msg_stmt->get_result();
        
        if ($messages_result->num_rows > 0) {
            while ($row = $messages_result->fetch_assoc()) {
                $messages[] = $row;
            }
        }
    } else {
        $errors[] = "User not found";
    }
}

// Get users with message counts
$users_query = "SELECT u.user_id, u.user_name, u.user_email as email, 
               MAX(m.created_at) as last_message_time,
               SUM(CASE WHEN m.is_read = 0 AND m.is_from_admin = 0 THEN 1 ELSE 0 END) as unread_count
               FROM users u
               LEFT JOIN messages m ON u.user_id = m.user_id
               GROUP BY u.user_id
               ORDER BY unread_count DESC, last_message_time DESC";
$users_result = $conn->query($users_query);

if ($users_result && $users_result->num_rows > 0) {
    while ($row = $users_result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Admin Panel</title>
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
            display: flex;
            flex-direction: column;
            height: 100vh;
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
        
        /* Messages container */
        .messages-container {
            display: flex;
            flex: 1;
            overflow: hidden;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        /* Users list */
        .users-list {
            width: 320px;
            overflow-y: auto;
            border-right: 1px solid #eee;
            background-color: #f8f9fa;
        }
        
        .users-list-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: white;
        }
        
        .users-list-header i {
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .users-search {
            padding: 15px;
            border-bottom: 1px solid #eee;
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            z-index: 10;
        }
        
        .users-search input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: white;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .users-search input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0,0,0,0.05);
        }
        
        .users-items {
            list-style: none;
        }
        
        .user-item {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
        }
        
        .user-item:hover {
            background-color: rgba(0,0,0,0.03);
        }
        
        .user-item.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            background-color: #ddd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 20px;
            color: #666;
            flex-shrink: 0;
        }
        
        .user-item.active .user-avatar {
            background-color: white;
            color: var(--primary-color);
        }
        
        .user-info {
            flex: 1;
            overflow: hidden;
        }
        
        .user-item-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            align-items: center;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 16px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-item.active .user-name,
        .user-item.active .user-email,
        .user-item.active .message-time {
            color: white;
        }
        
        .message-time {
            font-size: 0.75rem;
            color: #888;
        }
        
        .user-email {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .unread-badge {
            display: inline-block;
            background-color: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            font-size: 0.75rem;
            margin-left: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Chat area */
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: #f9f9f9;
        }
        
        .chat-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            font-weight: 600;
            display: flex;
            align-items: center;
            background-color: white;
        }
        
        .chat-header-avatar {
            width: 40px;
            height: 40px;
            background-color: #f0f0f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 18px;
            color: #666;
        }
        
        .chat-header-info h3 {
            margin: 0;
            font-size: 18px;
            color: var(--primary-color);
        }
        
        .chat-header-info p {
            margin: 3px 0 0;
            font-size: 14px;
            color: #888;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            background-image: 
                linear-gradient(rgba(255,255,255,0.8), rgba(255,255,255,0.8)),
                url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGcgZmlsbD0ibm9uZSIgZmlsbC1ydWxlPSJldmVub2RkIj48Y2lyY2xlIHN0cm9rZT0iI0YwRjBGMCIgc3Ryb2tlLXdpZHRoPSIyIiBjeD0iMTAiIGN5PSIxMCIgcj0iOCIvPjwvZz48L3N2Zz4=');
            background-repeat: repeat;
            background-size: 30px 30px;
        }
        
        .no-messages {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1;
            color: #888;
            font-size: 1.1rem;
            text-align: center;
        }
        
        .no-messages i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
            color: #ddd;
        }
        
        .message {
            max-width: 80%;
            margin-bottom: 15px;
            animation: fadeIn 0.3s ease;
            line-height: 1.5;
            position: relative;
            transition: all 0.3s ease;
            display: flex;
            align-items: flex-end;
        }
        
        .message-bubble {
            padding: 15px;
            border-radius: 16px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            flex: 1;
        }
        
        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            margin-right: 8px;
        }
        
        .admin-avatar {
            background-color: var(--primary-color);
            margin-left: 8px;
            margin-right: 0;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .message:hover {
            transform: translateY(-2px);
        }
        
        .message:hover .message-bubble {
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .message.admin {
            align-self: flex-end;
            justify-content: flex-end;
        }
        
        .message.admin .message-bubble {
            background-color: var(--primary-color);
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        .message.user {
            align-self: flex-start;
        }
        
        .message.user .message-bubble {
            background-color: white;
            border-bottom-left-radius: 4px;
        }
        
        .message-content {
            word-break: break-word;
            font-size: 15px;
        }
        
        .message-date-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 15px 0;
            width: 100%;
        }
        
        .message-date {
            background-color: rgba(0,0,0,0.05);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            color: #777;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .message-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            margin-top: 8px;
        }
        
        .message-time {
            color: #888;
            display: flex;
            align-items: center;
        }
        
        .message-time i {
            margin-right: 3px;
            font-size: 11px;
        }
        
        .message.admin .message-info {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .message-status {
            display: flex;
            align-items: center;
        }
        
        .message-status i {
            margin-right: 4px;
            font-size: 11px;
        }
        
        .message.user .message-status {
            font-weight: 600;
            color: #555;
        }
        
        .chat-input {
            border-top: 1px solid #eee;
            padding: 15px 20px;
            display: flex;
            background-color: white;
        }
        
        .chat-input form {
            display: flex;
            width: 100%;
        }
        
        .chat-input input {
            flex: 1;
            padding: 12px 18px;
            border: 1px solid #ddd;
            border-radius: 24px 0 0 24px;
            font-family: 'Cairo', sans-serif;
            font-size: 15px;
        }
        
        .chat-input input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .chat-input button {
            padding: 12px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0 24px 24px 0;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 600;
        }
        
        .chat-input button:hover {
            background-color: var(--accent-color);
            transform: translateY(-2px);
        }
        
        .chat-input button i {
            margin-right: 8px;
        }
        
        /* No user selected state */
        .no-user-selected {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1;
            color: #888;
            font-size: 1.1rem;
            text-align: center;
            padding: 20px;
        }
        
        .no-user-selected i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
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
            
            .users-list {
                width: 250px;
            }
        }
        
        @media (max-width: 576px) {
            .messages-container {
                flex-direction: column;
            }
            
            .users-list {
                width: 100%;
                max-height: 300px;
                border-right: none;
                border-bottom: 1px solid #eee;
            }
            
            .chat-messages {
                max-height: calc(100vh - 500px);
            }
        }
        
        .message-actions {
            display: flex;
            justify-content: flex-end;
            padding-top: 5px;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        
        .message:hover .message-actions {
            opacity: 1;
        }
        
        .delete-message-btn {
            background: none;
            border: none;
            color: #888;
            cursor: pointer;
            padding: 4px 8px;
            font-size: 14px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        .message.admin .delete-message-btn {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .delete-message-btn:hover {
            color: #dc3545;
            background-color: rgba(220, 53, 69, 0.1);
        }
        
        .message.admin .delete-message-btn:hover {
            color: rgba(255, 255, 255, 0.9);
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        /* Confirmation Dialog */
        .message-dialog-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        
        .message-dialog-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }
        
        .message-dialog {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transform: translateY(20px);
            transition: transform 0.3s ease;
        }
        
        .message-dialog-overlay.active .message-dialog {
            transform: translateY(0);
        }
        
        .message-dialog-title {
            font-size: 18px;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        
        .message-dialog-content {
            margin-bottom: 20px;
            color: var(--text-color);
        }
        
        .message-dialog-actions {
            display: flex;
            justify-content: flex-end;
        }
        
        .message-dialog-btn {
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .message-dialog-btn-cancel {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            color: var(--text-color);
            margin-right: 10px;
        }
        
        .message-dialog-btn-delete {
            background-color: #dc3545;
            border: 1px solid #dc3545;
            color: white;
        }
        
        .message-dialog-btn-cancel:hover {
            background-color: #e9ecef;
        }
        
        .message-dialog-btn-delete:hover {
            background-color: #c82333;
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
                    <a href="admin_messages.php" class="active">
                        <i class="fas fa-comments"></i>
                        <span>Messages <?php if($unread_messages_count > 0): ?><span class="badge" style="background-color: #dc3545; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem;"><?php echo $unread_messages_count; ?></span><?php endif; ?></span>
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
                <h1>Messages</h1>
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
                        <ul>
                            <?php foreach($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Messages interface -->
            <div class="messages-container">
                <!-- Users list -->
                <div class="users-list">
                    <div class="users-list-header">
                        <div><i class="fas fa-users"></i> Users</div>
                        <div><?php echo count($users); ?> total</div>
                    </div>
                    
                    <div class="users-search">
                        <input type="text" id="userSearch" placeholder="Search users..." onkeyup="searchUsers()">
                    </div>
                    
                    <ul class="users-items" id="usersList">
                        <?php if(!empty($users)): ?>
                            <?php foreach($users as $user): ?>
                                <li class="user-item <?php echo (isset($selected_user_id) && $selected_user_id == $user['user_id']) ? 'active' : ''; ?>" onclick="location.href='admin_messages.php?user_id=<?php echo $user['user_id']; ?>'">
                                    <div class="user-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="user-info">
                                        <div class="user-item-header">
                                            <div class="user-name"><?php echo htmlspecialchars($user['user_name']); ?></div>
                                            <div class="message-time"><?php echo $user['last_message_time'] ? date('M d, H:i', strtotime($user['last_message_time'])) : ''; ?></div>
                                        </div>
                                        <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                        <?php if(isset($user['unread_count']) && $user['unread_count'] > 0): ?>
                                            <span class="unread-badge"><?php echo $user['unread_count']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="user-item">No users found</li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Chat area -->
                <div class="chat-area">
                    <?php if(isset($selected_user_id)): ?>
                        <div class="chat-header">
                            <div class="chat-header-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="chat-header-info">
                                <h3><?php echo htmlspecialchars($selected_user_name); ?></h3>
                                <p><?php echo htmlspecialchars($selected_user_email); ?></p>
                            </div>
                        </div>
                        
                        <div class="chat-messages" id="chatMessages">
                            <?php if(empty($messages)): ?>
                                <div class="no-messages">
                                    <i class="fas fa-comments"></i>
                                    <p>No messages yet</p>
                                    <p>Start the conversation by sending a message below</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($messages as $message): ?>
                                    <div class="message <?php echo $message['is_from_admin'] ? 'admin' : 'user'; ?>">
                                        <div class="message-content">
                                            <?php echo htmlspecialchars($message['message_text']); ?>
                                        </div>
                                        <div class="message-info">
                                            <span class="message-status">
                                                <?php echo $message['is_from_admin'] ? 'Admin' : 'User'; ?>
                                            </span>
                                            <span class="message-time">
                                                <?php echo date('M d, H:i', strtotime($message['created_at'])); ?>
                                            </span>
                                        </div>
                                        <div class="message-actions">
                                            <button class="delete-message-btn" onclick="confirmDeleteMessage(<?php echo $message['id']; ?>)">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="chat-input">
                            <form id="messageForm">
                                <input type="hidden" name="action" value="send_message">
                                <input type="hidden" name="user_id" value="<?php echo $selected_user_id; ?>">
                                <input type="text" name="message_text" placeholder="Type your message here..." required>
                                <button type="submit"><i class="fas fa-paper-plane"></i> Send</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="no-user-selected">
                            <i class="fas fa-comments"></i>
                            <h3>Select a user to start messaging</h3>
                            <p>Choose a user from the list on the left to view your conversation history and send messages.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <div class="message-dialog-overlay" id="messageDialogOverlay">
        <div class="message-dialog">
            <div class="message-dialog-title">Confirm Deletion</div>
            <div class="message-dialog-content">Are you sure you want to delete this message?</div>
            <div class="message-dialog-actions">
                <button class="message-dialog-btn message-dialog-btn-cancel">Cancel</button>
                <button class="message-dialog-btn message-dialog-btn-delete">Delete</button>
            </div>
        </div>
    </div>
    
    <script>
        // Scroll to the bottom of the chat
        function scrollToBottom() {
            const chatMessages = document.getElementById('chatMessages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }
        
        // When page loads
        document.addEventListener('DOMContentLoaded', function() {
            scrollToBottom();
            
            // Set up AJAX messaging
            setupLiveMessages();
        });
        
        // Function to load messages using AJAX
        function loadMessages() {
            const selectedUserId = <?php echo isset($selected_user_id) ? $selected_user_id : 'null'; ?>;
            
            if (!selectedUserId) return;
            
            fetch('get_admin_messages.php?user_id=' + selectedUserId)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error(data.error);
                        return;
                    }
                    
                    updateMessages(data.messages);
                    updateUsersList(data.users);
                    updateUnreadCount(data.unread_count);
                })
                .catch(error => console.error('Error fetching messages:', error));
        }
        
        // Update the messages display
        function updateMessages(messages) {
            const chatContainer = document.getElementById('chatMessages');
            if (!chatContainer) return;
            
            // Don't update if no messages
            if (messages.length === 0) {
                chatContainer.innerHTML = `
                    <div class="no-messages">
                        <i class="fas fa-comments"></i>
                        <p>No messages yet</p>
                        <p>Start the conversation by sending a message below</p>
                    </div>
                `;
                return;
            }
            
            let html = '';
            
            // Get the user ID for consistent colors
            const selectedUserId = <?php echo isset($selected_user_id) ? $selected_user_id : '0'; ?>;
            
            // Generate a consistent color based on user ID
            const userColors = ['#5D8CAE', '#9655D6', '#33A1FD', '#7BBC89', '#FC766A'];
            const userColor = userColors[selectedUserId % userColors.length];
            
            messages.forEach(item => {
                if (item.type === 'date') {
                    // Date divider
                    html += `
                        <div class="message-date-divider">
                            <span class="message-date">${item.date}</span>
                        </div>
                    `;
                } else {
                    // Message
                    const isAdmin = item.is_from_admin == 1;
                    const messageClass = isAdmin ? 'admin' : 'user';
                    const messageIcon = isAdmin ? 'fa-headset' : 'fa-user';
                    
                    // Use standard color for user avatar
                    const userAvatarStyle = !isAdmin ? `style="background-color: ${userColor};"` : '';
                    
                    html += `
                        <div class="message ${messageClass}" id="message-${item.id}">
                            ${!isAdmin ? `
                            <div class="message-avatar" ${userAvatarStyle}>
                                <i class="fas ${messageIcon}"></i>
                            </div>` : ''}
                            
                            <div class="message-bubble">
                                <div class="message-content">
                                    ${item.message_text}
                                </div>
                                <div class="message-info">
                                    <span class="message-status">
                                        ${isAdmin ? 
                                            '<i class="fas fa-shield-alt"></i> Admin' : 
                                            '<i class="fas fa-check"></i> User'}
                                    </span>
                                    <span class="message-time">
                                        <i class="far fa-clock"></i> ${item.time}
                                    </span>
                                </div>
                                <div class="message-actions">
                                    <button class="delete-message-btn" onclick="confirmDeleteMessage(${item.id})">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                            
                            ${isAdmin ? `
                            <div class="message-avatar admin-avatar">
                                <i class="fas ${messageIcon}"></i>
                            </div>` : ''}
                        </div>
                    `;
                }
            });
            
            chatContainer.innerHTML = html;
            scrollToBottom();
        }
        
        // Update the users list
        function updateUsersList(users) {
            const usersList = document.getElementById('usersList');
            if (!usersList) return;
            
            const selectedUserId = <?php echo isset($selected_user_id) ? $selected_user_id : 'null'; ?>;
            
            if (users.length === 0) {
                usersList.innerHTML = '<li class="user-item">No users found</li>';
                return;
            }
            
            let html = '';
            
            // Define user colors
            const userColors = ['#5D8CAE', '#9655D6', '#33A1FD', '#7BBC89', '#FC766A'];
            
            users.forEach(user => {
                const isActive = (selectedUserId == user.user_id) ? 'active' : '';
                const lastMessageTime = user.last_message_time ? new Date(user.last_message_time).toLocaleString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                }) : '';
                
                // Generate consistent color based on user ID
                const userColor = userColors[user.user_id % userColors.length];
                const avatarStyle = isActive ? '' : `style="background-color: ${userColor};"`;
                
                html += `
                    <li class="user-item ${isActive}" onclick="location.href='admin_messages.php?user_id=${user.user_id}'">
                        <div class="user-avatar" ${avatarStyle}>
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-info">
                            <div class="user-item-header">
                                <div class="user-name">${user.user_name}</div>
                                <div class="message-time">${lastMessageTime}</div>
                            </div>
                            <div class="user-email">${user.email}</div>
                            ${user.unread_count > 0 ? `<span class="unread-badge">${user.unread_count}</span>` : ''}
                        </div>
                    </li>
                `;
            });
            
            usersList.innerHTML = html;
        }
        
        // Update unread count in the sidebar
        function updateUnreadCount(count) {
            const countElement = document.querySelector('.sidebar a.active span .badge');
            if (countElement) {
                if (count > 0) {
                    countElement.textContent = count;
                    countElement.style.display = 'inline';
                } else {
                    countElement.style.display = 'none';
                }
            }
        }
        
        // Setup live messages with periodic refresh
        function setupLiveMessages() {
            // Check if we are in a chat with a user
            const selectedUserId = <?php echo isset($selected_user_id) ? $selected_user_id : 'null'; ?>;
            if (!selectedUserId) return;
            
            // Load messages immediately
            loadMessages();
            
            // Then refresh every 5 seconds
            setInterval(loadMessages, 5000);
            
            // Set up the form to use AJAX
            const messageForm = document.getElementById('messageForm');
            if (messageForm) {
                messageForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const messageInput = this.querySelector('input[name="message_text"]');
                    const userIdInput = this.querySelector('input[name="user_id"]');
                    
                    const messageText = messageInput.value.trim();
                    const userId = userIdInput.value;
                    
                    if (messageText && userId) {
                        sendMessage(messageText, userId);
                        messageInput.value = '';
                    }
                });
            }
        }
        
        // Send message using AJAX
        function sendMessage(messageText, userId) {
            const formData = new FormData();
            formData.append('message_text', messageText);
            formData.append('user_id', userId);
            
            fetch('send_admin_message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error(data.error);
                    return;
                }
                
                if (data.success) {
                    // Immediately load messages to show the new one
                    loadMessages();
                }
            })
            .catch(error => console.error('Error sending message:', error));
        }
        
        // Search users function
        function searchUsers() {
            const input = document.getElementById('userSearch');
            const filter = input.value.toUpperCase();
            const usersList = document.getElementById('usersList');
            const users = usersList.getElementsByTagName('li');
            
            for (let i = 0; i < users.length; i++) {
                const userName = users[i].getElementsByClassName('user-name')[0];
                const userEmail = users[i].getElementsByClassName('user-email')[0];
                
                if (userName || userEmail) {
                    const nameText = userName ? userName.textContent || userName.innerText : '';
                    const emailText = userEmail ? userEmail.textContent || userEmail.innerText : '';
                    
                    if (nameText.toUpperCase().indexOf(filter) > -1 || emailText.toUpperCase().indexOf(filter) > -1) {
                        users[i].style.display = "";
                    } else {
                        users[i].style.display = "none";
                    }
                }
            }
        }
        
        // Confirm message deletion
        function confirmDeleteMessage(messageId) {
            const overlay = document.getElementById('messageDialogOverlay');
            const dialog = document.querySelector('.message-dialog');
            const cancelBtn = document.querySelector('.message-dialog-btn-cancel');
            const deleteBtn = document.querySelector('.message-dialog-btn-delete');
            
            // Remove existing event listeners to prevent duplicates
            const newCancelBtn = cancelBtn.cloneNode(true);
            const newDeleteBtn = deleteBtn.cloneNode(true);
            
            cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
            deleteBtn.parentNode.replaceChild(newDeleteBtn, deleteBtn);
            
            // Add event listeners
            newCancelBtn.addEventListener('click', function() {
                overlay.classList.remove('active');
                dialog.style.transform = 'translateY(20px)';
            });
            
            newDeleteBtn.addEventListener('click', function() {
                deleteMessage(messageId);
            });
            
            // Show dialog
            overlay.classList.add('active');
            dialog.style.transform = 'translateY(0)';
        }
        
        // Delete message using AJAX
        function deleteMessage(messageId) {
            const formData = new FormData();
            formData.append('message_id', messageId);
            
            fetch('delete_message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error(data.error);
                    return;
                }
                
                if (data.success) {
                    // Close the dialog
                    const overlay = document.getElementById('messageDialogOverlay');
                    overlay.classList.remove('active');
                    
                    // Remove the message from DOM
                    const messageElement = document.getElementById('message-' + messageId);
                    if (messageElement) {
                        messageElement.remove();
                    } else {
                        // If DOM removal fails, reload messages
                        loadMessages();
                    }
                }
            })
            .catch(error => console.error('Error deleting message:', error));
        }
    </script>
</body>
</html> 