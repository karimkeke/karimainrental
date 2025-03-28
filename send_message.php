<?php
session_start();
include('connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// Check if data is sent via POST
if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['message_text'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

// Process message
$user_id = $_SESSION['user_id'];
$message_text = trim($_POST['message_text']);

if (empty($message_text)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Message cannot be empty']);
    exit;
}

// Insert message
$query = "INSERT INTO messages (user_id, message_text, is_from_admin) VALUES (?, ?, 0)";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $user_id, $message_text);

$response = [];

if ($stmt->execute()) {
    $message_id = $stmt->insert_id;
    
    // Get the inserted message details
    $msg_query = "SELECT * FROM messages WHERE id = ?";
    $msg_stmt = $conn->prepare($msg_query);
    $msg_stmt->bind_param("i", $message_id);
    $msg_stmt->execute();
    $message_result = $msg_stmt->get_result();
    $message = $message_result->fetch_assoc();
    
    $messageDate = new DateTime($message['created_at']);
    $formattedTime = $messageDate->format('H:i');
    
    $response = [
        'success' => true,
        'message' => [
            'id' => $message['id'],
            'message_text' => $message['message_text'],
            'is_from_admin' => 0,
            'is_read' => $message['is_read'],
            'time' => $formattedTime
        ]
    ];
} else {
    $response = [
        'success' => false,
        'error' => 'Error sending message: ' . $conn->error
    ];
}

// Return response
header('Content-Type: application/json');
echo json_encode($response); 