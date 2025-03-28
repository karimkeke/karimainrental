<?php
$conn = mysqli_connect('localhost', 'root', '', 'php_project');

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
