<?php
include 'database.php';

$username = $_POST['username'];
$password = $_POST['password'];
$fullname = $_POST['fullname'];

// Security fix: All new users register as "Staff" by default
// Only admins can change user roles through the user management interface
$role = 'Staff';

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$statement = $connection->prepare("INSERT INTO users (full_name, username, password, role) VALUES (?, ?, ?, ?)");
$statement->bind_param("ssss", $fullname, $username, $hashed_password, $role);
$statement->execute();
$statement->close();

header("Location: index.php");
exit();
?>
