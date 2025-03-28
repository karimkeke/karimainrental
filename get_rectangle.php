<?php
include('connection.php'); 

$category = 'rectangle';
$stmt = $conn->prepare("SELECT * FROM products WHERE category = ? LIMIT 1");
$stmt->bind_param("s", $category);
$stmt->execute();
$rectangle = $stmt->get_result();
?>
