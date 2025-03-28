<?php
include('connection.php'); 

$category = 'officechair';
$stmt = $conn->prepare("SELECT * FROM products WHERE category = ? LIMIT 1");
$stmt->bind_param("s", $category);
$stmt->execute();
$officechair = $stmt->get_result();
?>
