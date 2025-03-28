<?php
include('connection.php'); 

$category = 'round';
$stmt = $conn->prepare("SELECT * FROM products WHERE category = ? LIMIT 1");
$stmt->bind_param("s", $category);
$stmt->execute();
$round = $stmt->get_result();
?>
