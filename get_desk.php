<?php
include('connection.php');

$category = 'desk';
$stmt = $conn->prepare("SELECT * FROM products WHERE category = ? LIMIT 1");
$stmt->bind_param("s", $category);
$stmt->execute();
$desk = $stmt->get_result();
?>
