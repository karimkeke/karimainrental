<?php
include('connection.php');

$category = 'coffeetables';
$stmt = $conn->prepare("SELECT * FROM products WHERE category = ? LIMIT 1");
$stmt->bind_param("s", $category);
$stmt->execute();
$coffeetables = $stmt->get_result();
?>
