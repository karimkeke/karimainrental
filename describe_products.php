<?php include 'connection.php';  = ->query('DESCRIBE products'); if() { while( = ->fetch_assoc()) { echo ['Field'] . ' (' . ['Type'] . ')<br>'; } } else { echo 'Query failed: ' . ->error; } ?>
