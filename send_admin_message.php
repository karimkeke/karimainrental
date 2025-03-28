<?php
session_start();
include('connection.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not logged in as admin']);
    exit;
}

// Check if data is sent via POST
if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['message_text']) || !isset($_POST['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

// Process message
$user_id = $_POST['user_id'];
$message_text = trim($_POST['message_text']);

if (empty($message_text)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Message cannot be empty']);
    exit;
}

if (!is_numeric($user_id)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

// Verify user exists
$user_check = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
$user_check->bind_param("i", $user_id);
$user_check->execute();
$user_result = $user_check->get_result();

if ($user_result->num_rows == 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User not found']);
    exit;
}

// Insert message
$query = "INSERT INTO messages (user_id, message_text, is_from_admin) VALUES (?, ?, 1)";
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
            'is_from_admin' => 1,
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