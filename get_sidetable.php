<?php
include('connection.php');

$category = 'sidetable';
$stmt = $conn->prepare("SELECT * FROM products WHERE category = ? LIMIT 1");
$stmt->bind_param("s", $category);
$stmt->execute();
$sidetable = $stmt->get_result();
?>
