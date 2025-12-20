<?php
include 'database.php';

$username = $_POST['username'];
$password = $_POST['password'];
$fullname = $_POST['fullname'];
$role = $_POST['role'];

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$statement = $connection->prepare("INSERT INTO users (full_name, username, password, role) VALUES (?, ?, ?, ?)");
$statement->bind_param("ssss", $fullname, $username, $hashed_password, $role);
$statement->execute();
$statement->close();

header("Location: index.php");
exit();
?>
