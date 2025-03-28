<?php
include('connection.php');

$category = 'accentchair';
$stmt = $conn->prepare("SELECT * FROM products WHERE category = ? LIMIT 1");
$stmt->bind_param("s", $category);
$stmt->execute();
$accentchair = $stmt->get_result();
?>
