<?php
include('connection.php'); 

$category = 'dresser';
$stmt = $conn->prepare("SELECT * FROM products WHERE category = ? LIMIT 1");
$stmt->bind_param("s", $category);
$stmt->execute();
$dresser = $stmt->get_result();
?>
