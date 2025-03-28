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
if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['message_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

// Process deletion
$message_id = $_POST['message_id'];

if (!is_numeric($message_id)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid message ID']);
    exit;
}

// Delete the message
$query = "DELETE FROM messages WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $message_id);

$response = [];

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $response = [
            'success' => true,
            'message' => 'Message deleted successfully',
            'message_id' => $message_id
        ];
    } else {
        $response = [
            'success' => false,
            'error' => 'Message not found'
        ];
    }
} else {
    $response = [
        'success' => false,
        'error' => 'Error deleting message: ' . $conn->error
    ];
}

// Return response
header('Content-Type: application/json');
echo json_encode($response); 