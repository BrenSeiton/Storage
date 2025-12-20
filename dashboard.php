<?php
session_start();

if(!isset($_SESSION['username'])){
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>

<h1>Dashboard</h1>
<h2>Welcome, <?php echo $username; ?> (<?php echo $role; ?>)</h2>

<!-- Navigation links -->
<a href="users.php">Manage Users</a> |
<a href="products.php">Manage Products</a> |
<a href="logout.php">Logout</a>

</body>
</html>
