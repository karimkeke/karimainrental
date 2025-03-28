<?php
include('connection.php'); 

$category = 'sofas';
$stmt = $conn->prepare("SELECT * FROM products WHERE category = ? LIMIT 1");
$stmt->bind_param("s", $category);
$stmt->execute();
$sofas = $stmt->get_result();
?>
