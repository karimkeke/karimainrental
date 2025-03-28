<?php
include('connection.php'); 

$stmt = $conn->prepare("SELECT * FROM products WHERE category = 'dining'");
$stmt->execute();
$diningtables = $stmt->get_result();
?>
