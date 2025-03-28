<?php
session_start();
include('connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=user_messages.php');
    exit;
}

// Check if messages table exists
$table_check = $conn->query("SHOW TABLES LIKE 'messages'");
$table_exists = $table_check->num_rows > 0;

if (!$table_exists) {
    // Create messages table
    include('create_messages_table.php');
    // Refresh the page
    header('Location: user_messages.php');
    exit;
}

// Initialize variables
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$messages = [];
$errors = [];
$success_message = "";

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'send_message') {
    if (empty($_POST['message_text'])) {
        $errors[] = "Message cannot be empty";
    } else {
        $message_text = trim($_POST['message_text']);
        
        $query = "INSERT INTO messages (user_id, message_text, is_from_admin) VALUES (?, ?, 0)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $user_id, $message_text);
        
        if ($stmt->execute()) {
            $success_message = "Message sent successfully!";
            
            // Redirect to avoid form resubmission
            header("Location: user_messages.php?success=sent");
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

// Get conversation for this user
$msg_query = "SELECT * FROM messages WHERE user_id = ? ORDER BY created_at ASC";
$msg_stmt = $conn->prepare($msg_query);
$msg_stmt->bind_param("i", $user_id);
$msg_stmt->execute();
$messages_result = $msg_stmt->get_result();

if ($messages_result->num_rows > 0) {
    while ($row = $messages_result->fetch_assoc()) {
        $messages[] = $row;
    }
    
    // Mark admin messages as read
    $update_read = "UPDATE messages SET is_read = 1 WHERE user_id = ? AND is_from_admin = 1 AND is_read = 0";
    $read_stmt = $conn->prepare($update_read);
    $read_stmt->bind_param("i", $user_id);
    $read_stmt->execute();
}

// Get unread message count for navbar badge
$unread_query = "SELECT COUNT(*) as unread FROM messages WHERE user_id = ? AND is_from_admin = 1 AND is_read = 0";
$unread_stmt = $conn->prepare($unread_query);
$unread_stmt->bind_param("i", $user_id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['unread'];

// Calculate cart total for header
function calculatetotalcart() {
    $total = 0;
    $total_quantity = 0;

    if(isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach($_SESSION['cart'] as $product) {
            $total += $product['product_price'] * $product['product_quantity'];
            $total_quantity += $product['product_quantity'];
        }
    }

    $_SESSION['total'] = $total;
    $_SESSION['quantity'] = $total_quantity;
}

calculatetotalcart();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Furniture Rental</title>
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
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Navigation */
        nav {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        nav .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .logo h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #000;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
        }
        
        .nav-links a {
            color: #333;
            text-decoration: none;
            margin-left: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .nav-links a:hover {
            color: #000;
        }
        
        .dropdown {
            position: relative;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 200px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            z-index: 1;
            right: 0;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .dropdown-content a, .dropdown-content p {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }
        
        .dropdown-content a:hover {
            background-color: #f8f9fa;
        }
        
        .dropdown:hover .dropdown-content {
            display: block;
        }
        
        .account-icon, .cart-icon {
            font-size: 1.2rem;
            position: relative;
        }
        
        .cart-count {
            position: absolute;
            top: -10px;
            right: -10px;
            background-color: #000;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        /* Messages container */
        .messages-container {
            max-width: 900px;
            margin: 30px auto;
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            flex: 1;
            margin-bottom: 30px;
        }
        
        .messages-header {
            padding: 25px 30px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #fff;
        }
        
        .messages-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #000;
            display: flex;
            align-items: center;
        }
        
        .messages-header h2 i {
            margin-right: 10px;
            color: #555;
        }
        
        .messages-status {
            font-size: 0.9rem;
            color: #888;
        }
        
        /* Alert messages */
        .alert {
            padding: 15px 30px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            border-left: 5px solid;
        }
        
        .alert i {
            margin-right: 15px;
            font-size: 1.5rem;
        }
        
        .alert-success {
            background-color: #dff6e1;
            border-color: #28a745;
            color: #24863b;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        
        /* Chat messages */
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
            height: 300px;
            color: #888;
            text-align: center;
        }
        
        .no-messages i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #ddd;
        }
        
        /* Message styling */
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
            background-color: #000;
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
            background-color: #000;
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
        
        /* Chat input */
        .chat-input {
            border-top: 1px solid #f0f0f0;
            padding: 20px 30px;
            background-color: #fff;
        }
        
        .chat-input form {
            display: flex;
            align-items: center;
        }
        
        .chat-input input {
            flex: 1;
            padding: 15px 20px;
            border: 1px solid #eaeaea;
            border-radius: 24px;
            font-size: 1rem;
            outline: none;
            transition: all 0.3s ease;
        }
        
        .chat-input input:focus {
            border-color: #000;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
        }
        
        .chat-input button {
            background-color: #000;
            color: white;
            border: none;
            border-radius: 24px;
            padding: 15px 30px;
            margin-left: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }
        
        .chat-input button:hover {
            background-color: #333;
            transform: translateY(-2px);
        }
        
        .chat-input button i {
            margin-right: 8px;
        }
        
        /* Footer */
        footer {
            background-color: #000;
            color: white;
            padding: 30px 0;
            margin-top: auto;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .footer-section {
            margin-bottom: 20px;
        }
        
        .footer-section h3 {
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        
        .footer-section p {
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        
        .social-links a {
            color: white;
            font-size: 1.2rem;
            margin-right: 15px;
        }
        
        .footer-bottom {
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
            margin-top: 20px;
            font-size: 0.85rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .messages-container {
                margin: 20px;
                max-width: none;
            }
            
            .footer-content {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="container">
            <div class="logo">
                <h1>Furniture Rental</h1>
            </div>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="product.php">Products</a>
                <a href="contact.php">Contact Us</a>
                <div class="dropdown" style="display: inline-block;">
                    <a href="#" class="account-icon">
                        <i class="fas fa-user"></i>
                    </a>
                    <div class="dropdown-content">
                        <?php if(isset($_SESSION['user_name'])): ?>
                            <p>Hi, <?php echo $_SESSION['user_name']; ?></p>
                            <a href="accountdetails.php">Account Details</a>
                            <a href="my_orders.php">My Orders</a>
                            <a href="user_messages.php">Messages <?php if($unread_count > 0): ?><span style="background-color: #dc3545; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem;"><?php echo $unread_count; ?></span><?php endif; ?></a>
                            <a href="account.php">My Account</a>
                            <a href="account.php?logout=1">Logout</a>
                        <?php else: ?>
                            <a href="login.php">Login</a>
                            <a href="register.php">Create an Account</a>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="cart.php" class="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count">
                        <?php echo isset($_SESSION['quantity']) ? $_SESSION['quantity'] : 0; ?>
                    </span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Messages Section -->
    <div class="messages-container">
        <div class="messages-header">
            <h2><i class="fas fa-comments"></i> My Messages</h2>
            <div class="messages-status">
                <?php if(!empty($messages)): ?>
                    <span>Last message: <?php 
                        $last_message = end($messages);
                        $time = new DateTime($last_message['created_at']);
                        echo $time->format('M d, H:i');
                    ?></span>
                <?php else: ?>
                    <span>No messages yet</span>
                <?php endif; ?>
            </div>
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
                    <strong>Error:</strong>
                    <ul>
                        <?php foreach($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Chat Messages -->
        <div class="chat-messages" id="chatMessages">
            <?php if(empty($messages)): ?>
                <div class="no-messages">
                    <i class="fas fa-comments"></i>
                    <p>No messages yet</p>
                    <p>Start the conversation by sending a message below</p>
                </div>
            <?php else: ?>
                <?php 
                $currentDate = null;
                foreach($messages as $message): 
                    $messageDate = new DateTime($message['created_at']);
                    $formattedDate = $messageDate->format('F j, Y');
                    
                    // Add date divider if this is a new date
                    if($currentDate !== $formattedDate):
                        $currentDate = $formattedDate;
                ?>
                    <div class="message-date-divider">
                        <span class="message-date"><?php echo $formattedDate; ?></span>
                    </div>
                <?php endif; ?>
                
                    <div class="message <?php echo $message['is_from_admin'] ? 'admin' : 'user'; ?>">
                        <div class="message-content">
                            <?php echo htmlspecialchars($message['message_text']); ?>
                        </div>
                        <div class="message-info">
                            <span class="message-status">
                                <?php if(!$message['is_from_admin']): ?>
                                    <?php if($message['is_read']): ?>
                                        <i class="fas fa-check-double"></i> Read
                                    <?php else: ?>
                                        <i class="fas fa-check"></i> Delivered
                                    <?php endif; ?>
                                <?php else: ?>
                                    Admin
                                <?php endif; ?>
                            </span>
                            <span class="message-time">
                                <?php echo $messageDate->format('H:i'); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Message Input -->
        <div class="chat-input">
            <form id="messageForm">
                <input type="hidden" name="action" value="send_message">
                <input type="text" name="message_text" placeholder="Type your message here..." required>
                <button type="submit"><i class="fas fa-paper-plane"></i> Send</button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Contact Us</h3>
                    <p><i class="fas fa-phone"></i> +213 XX XXX XXXX</p>
                    <p><i class="fas fa-envelope"></i> info@furniture-rental.dz</p>
                </div>
                <div class="footer-section">
                    <h3>Follow Us</h3>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 Furniture Rental. All Rights Reserved</p>
            </div>
        </div>
    </footer>

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
            
            // Set up auto-refresh for messages
            setupLiveMessages();
        });
        
        // Function to load messages using AJAX
        function loadMessages() {
            fetch('get_messages.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error(data.error);
                        return;
                    }
                    
                    updateMessages(data.messages);
                    updateUnreadCount(data.unread_count);
                })
                .catch(error => console.error('Error fetching messages:', error));
        }
        
        // Update the messages display
        function updateMessages(messages) {
            const chatContainer = document.getElementById('chatMessages');
            
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
            
            // Get the current user ID from session for consistent colors
            const currentUserId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '0'; ?>;
            
            // Generate a consistent color based on user ID
            const userColors = ['#5D8CAE', '#9655D6', '#33A1FD', '#7BBC89', '#FC766A'];
            const userColor = userColors[currentUserId % userColors.length];
            
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
                    
                    // Use the standard color for user avatar
                    const userAvatarStyle = !isAdmin ? `style="background-color: ${userColor};"` : '';
                    
                    html += `
                        <div class="message ${messageClass}">
                            ${!isAdmin ? `
                            <div class="message-avatar" style="background-color: ${userColor};">
                                <i class="fas ${messageIcon}"></i>
                            </div>` : ''}
                            
                            <div class="message-bubble">
                                <div class="message-content">
                                    ${item.message_text}
                                </div>
                                <div class="message-info">
                                    <span class="message-status">
                                        ${!isAdmin ? 
                                            (item.is_read ? '<i class="fas fa-check-double"></i> Read' : '<i class="fas fa-check"></i> Delivered') : 
                                            '<i class="fas fa-shield-alt"></i> Admin'}
                                    </span>
                                    <span class="message-time">
                                        <i class="far fa-clock"></i> ${item.time}
                                    </span>
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
        
        // Update unread count in the navbar
        function updateUnreadCount(count) {
            const countElements = document.querySelectorAll('.message-count');
            countElements.forEach(el => {
                if (count > 0) {
                    el.style.display = 'block';
                    el.textContent = count;
                } else {
                    el.style.display = 'none';
                }
            });
        }
        
        // Setup live messages with periodic refresh
        function setupLiveMessages() {
            // Load messages immediately
            loadMessages();
            
            // Then refresh every 5 seconds
            setInterval(loadMessages, 5000);
            
            // Set up the form to use AJAX
            const messageForm = document.querySelector('.chat-input form');
            if (messageForm) {
                messageForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const messageInput = this.querySelector('input[name="message_text"]');
                    const messageText = messageInput.value.trim();
                    
                    if (messageText) {
                        sendMessage(messageText);
                        messageInput.value = '';
                    }
                });
            }
        }
        
        // Send message using AJAX
        function sendMessage(messageText) {
            const formData = new FormData();
            formData.append('message_text', messageText);
            
            fetch('send_message.php', {
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
    </script>
</body>
</html> 