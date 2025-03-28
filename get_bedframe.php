<?php
include('connection.php'); 

$category = 'bedframe';
$stmt = $conn->prepare("SELECT * FROM products WHERE category = ? LIMIT 1");
$stmt->bind_param("s", $category);
$stmt->execute();
$bedframe = $stmt->get_result();
?>
