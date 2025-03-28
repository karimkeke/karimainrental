<?php
include('connection.php');

// Check if the messages table exists
$table_check = $conn->query("SHOW TABLES LIKE 'messages'");
$table_exists = $table_check->num_rows > 0;

if (!$table_exists) {
    echo "<div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f0f0f0; border-radius: 5px;'>";
    echo "<h2 style='color: #dc3545;'>Error: Messages table does not exist</h2>";
    echo "<p>Please run the create_messages_table.php script first.</p>";
    echo "<p><a href='create_messages_table.php' style='color: #0066cc; text-decoration: none;'>Create Messages Table</a></p>";
    echo "</div>";
    exit;
}

// Get user IDs from the database
$users_query = "SELECT user_id FROM users LIMIT 10";
$users_result = $conn->query($users_query);

if ($users_result->num_rows == 0) {
    echo "<div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f0f0f0; border-radius: 5px;'>";
    echo "<h2 style='color: #dc3545;'>Error: No users found</h2>";
    echo "<p>Please add some users to the database first.</p>";
    echo "</div>";
    exit;
}

// Sample messages
$user_messages = [
    "Hello, I'm interested in renting the wooden dining table. Is it available next month?",
    "I need to change my delivery address for order #12345. Can you help me with that?",
    "The sofa I received has a small scratch on the side. What should I do?",
    "Do you offer any discounts for long-term rentals?",
    "Can I extend my current rental period by another month?",
    "I'm looking for a bedroom set that would accommodate a small room. Any recommendations?",
    "What's your cancellation policy for rentals?",
    "Do you have any outdoor furniture available?",
    "I noticed the office desk I wanted is out of stock. When will it be available again?",
    "Are your furniture pieces pet-friendly?"
];

$admin_responses = [
    "Hello! Yes, the wooden dining table is available for rental next month. Would you like to place a reservation?",
    "I'd be happy to help you change the delivery address. Please provide your new address details.",
    "I'm sorry to hear about the scratch. Please send a photo to our email, and we'll arrange a replacement.",
    "Yes, we offer a 15% discount for rentals longer than 3 months. I can apply that to your order.",
    "Of course! We can extend your rental period. Would you like me to process that for you now?",
    "For a small bedroom, I'd recommend our compact bedroom set which includes a queen bed, a small dresser, and a nightstand. Would you like to see photos?",
    "Our cancellation policy allows free cancellation up to 48 hours before delivery. After that, a 25% fee applies.",
    "Yes, we have several outdoor furniture sets including patio tables, chairs, and lounge sets. What type are you looking for?",
    "The office desk will be back in stock next week. Would you like me to reserve one for you?",
    "All our furniture is pet-friendly and treated with stain-resistant coatings. However, we recommend using throws or covers for extra protection."
];

// Insert sample messages
$messages_added = 0;
$users = [];

while ($row = $users_result->fetch_assoc()) {
    $users[] = $row['user_id'];
}

foreach ($users as $index => $user_id) {
    if (isset($user_messages[$index])) {
        // Add user message
        $stmt = $conn->prepare("INSERT INTO messages (user_id, message_text, is_from_admin, is_read) VALUES (?, ?, 0, 1)");
        $stmt->bind_param("is", $user_id, $user_messages[$index]);
        $stmt->execute();
        $messages_added++;
        
        // Add admin response
        if (isset($admin_responses[$index])) {
            $stmt = $conn->prepare("INSERT INTO messages (user_id, message_text, is_from_admin, is_read) VALUES (?, ?, 1, 0)");
            $stmt->bind_param("is", $user_id, $admin_responses[$index]);
            $stmt->execute();
            $messages_added++;
        }
    }
}

// Output results
echo "<div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f0f0f0; border-radius: 5px;'>";
echo "<h2 style='color: #28a745;'>Sample messages added successfully!</h2>";
echo "<p>Added $messages_added messages to the database.</p>";
echo "<p><a href='admin_messages.php' style='color: #0066cc; text-decoration: none;'>Go to Admin Messages</a></p>";
echo "<p><a href='user_messages.php' style='color: #0066cc; text-decoration: none;'>Go to User Messages</a></p>";
echo "</div>";
?> 