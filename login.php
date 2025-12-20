<?php
session_start();
include 'database.php';

$username = $_POST['username'];
$password = $_POST['password'];

$statement = $connection->prepare("SELECT password, role FROM users WHERE username = ?");
$statement->bind_param("s", $username);
$statement->execute();
$statement->bind_result($truepassword, $role);
$statement->fetch();
$statement->close();

if(password_verify($password, $truepassword)){
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    header("Location: dashboard.php");
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>
