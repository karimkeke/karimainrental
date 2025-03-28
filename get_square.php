<?php
include('connection.php');

$category = 'square';
$stmt = $conn->prepare("SELECT * FROM products WHERE category = ? LIMIT 1");
$stmt->bind_param("s", $category);
$stmt->execute();
$square = $stmt->get_result();
?>
