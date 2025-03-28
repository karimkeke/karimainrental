<?php
include('connection.php'); 

$category = 'cornerdesk';
$stmt = $conn->prepare("SELECT * FROM products WHERE category = ? LIMIT 1");
$stmt->bind_param("s", $category);
$stmt->execute();
$cornerdesk = $stmt->get_result();
?>
