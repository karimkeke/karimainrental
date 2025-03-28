<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Furniture Rental</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
.dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    background-color: white;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    min-width: 160px; 
    padding: 8px 0;
    z-index: 100;
}

.dropdown-content a {
    display: block;
    padding: 8px 12px;
    font-size: 14px; 
    color: #333;
    text-decoration: none;
}

.dropdown-content a:hover {
    background-color: #f1f1f1;
}

.dropdown-content p {
    padding: 8px 12px;
    font-weight: bold;
    font-size: 14px; 
    color: #555;
}


.dropdown:hover .dropdown-content {
    display: block;
}

    </style>
</head>
<body>

    <nav class="navbar">
        <div class="container">
            <div class="logo">
                <h1>Furniture Rental</h1>
            </div>
            <div class="nav-links">
                <div class="icons">
                    <div class="dropdown">
                        <a href="#" class="account-icon"><i class="fas fa-user"></i></a>
                        <div class="dropdown-content">
                            <?php if(isset($_SESSION['user_name'])): ?>
                                <p>Hi, <?php echo $_SESSION['user_name']; ?></p>
                                <a href="accountdetails.php">Account Details</a>
                                <a href="my_orders.php">My Orders</a>
                                <?php 
                                // Get unread message count
                                $unread_count = 0;
                                if(isset($_SESSION['user_id'])) {
                                    $user_id = $_SESSION['user_id'];
                                    $table_check = $conn->query("SHOW TABLES LIKE 'messages'");
                                    if ($table_check->num_rows > 0) {
                                        $unread_query = "SELECT COUNT(*) as unread FROM messages WHERE user_id = ? AND is_from_admin = 1 AND is_read = 0";
                                        $unread_stmt = $conn->prepare($unread_query);
                                        $unread_stmt->bind_param("i", $user_id);
                                        $unread_stmt->execute();
                                        $unread_result = $unread_stmt->get_result();
                                        $unread_count = $unread_result->fetch_assoc()['unread'];
                                    }
                                }
                                ?>
                                <a href="user_messages.php">Messages <?php if($unread_count > 0): ?><span style="background-color: #dc3545; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem;"><?php echo $unread_count; ?></span><?php endif; ?></a>
                                <a href="logout.php">Sign Out</a>
                            <?php else: ?>
                                <a href="login.php">Login</a>
                                <a href="register.php">Create an Account</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="search.php"><i class="fas fa-search"></i></a>
                    <a href="cart.php"><i class="fas fa-shopping-cart"></i></a>
                </div>
            </div>
        </div>
    </nav>