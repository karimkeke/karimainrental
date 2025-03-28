<?php
session_start();
include('connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get messages for this user
$msg_query = "SELECT * FROM messages WHERE user_id = ? ORDER BY created_at ASC";
$msg_stmt = $conn->prepare($msg_query);
$msg_stmt->bind_param("i", $user_id);
$msg_stmt->execute();
$messages_result = $msg_stmt->get_result();

$messages = [];
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

// Get unread message count
$unread_query = "SELECT COUNT(*) as unread FROM messages WHERE user_id = ? AND is_from_admin = 1 AND is_read = 0";
$unread_stmt = $conn->prepare($unread_query);
$unread_stmt->bind_param("i", $user_id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['unread'];

// Return response
header('Content-Type: application/json');
echo json_encode([
    'messages' => $formatted_messages,
    'unread_count' => $unread_count,
    'user_id' => $user_id
]); 