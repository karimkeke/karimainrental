<?php
session_start();
include('connection.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not logged in as admin']);
    exit;
}

// Check if user_id is provided
if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

$selected_user_id = $_GET['user_id'];

// Get user info
$user_query = "SELECT user_id, user_name, user_email as email FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $selected_user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result->num_rows == 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User not found']);
    exit;
}

$user_data = $user_result->fetch_assoc();

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

$messages = [];
if ($messages_result->num_rows > 0) {
    while ($row = $messages_result->fetch_assoc()) {
        $messages[] = $row;
    }
}

// Format dates for display
$formatted_messages = [];
$currentDate = null;

foreach ($messages as $message) {
    $messageDate = new DateTime($message['created_at']);
    $formattedDate = $messageDate->format('F j, Y');
    $formattedTime = $messageDate->format('H:i');
    
    // Add date marker if this is a new date
    if ($currentDate !== $formattedDate) {
        $currentDate = $formattedDate;
        $formatted_messages[] = [
            'type' => 'date',
            'date' => $formattedDate
        ];
    }
    
    // Add message
    $formatted_messages[] = [
        'type' => 'message',
        'id' => $message['id'],
        'is_from_admin' => $message['is_from_admin'],
        'message_text' => $message['message_text'],
        'is_read' => $message['is_read'],
        'time' => $formattedTime
    ];
}

// Get users with message counts for sidebar
$users_query = "SELECT u.user_id, u.user_name, u.user_email as email, 
               MAX(m.created_at) as last_message_time,
               SUM(CASE WHEN m.is_read = 0 AND m.is_from_admin = 0 THEN 1 ELSE 0 END) as unread_count
               FROM users u
               LEFT JOIN messages m ON u.user_id = m.user_id
               GROUP BY u.user_id
               ORDER BY unread_count DESC, last_message_time DESC";
$users_result = $conn->query($users_query);

$users = [];
if ($users_result && $users_result->num_rows > 0) {
    while ($row = $users_result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Get total unread message count
$unread_messages_query = "SELECT COUNT(*) as unread_count FROM messages WHERE is_from_admin = 0 AND is_read = 0";
$unread_messages_result = $conn->query($unread_messages_query);
$unread_messages_count = $unread_messages_result->fetch_assoc()['unread_count'];

// Return response
header('Content-Type: application/json');
echo json_encode([
    'user' => $user_data,
    'messages' => $formatted_messages,
    'users' => $users,
    'unread_count' => $unread_messages_count,
    'user_id' => $selected_user_id
]); 