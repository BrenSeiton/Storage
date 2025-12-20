<?php
include 'database.php';
session_start();

if(!isset($_SESSION['username'])){
    header("Location: index.php");
    exit();
}

$id = $_GET['id'] ?? null;
if($id){
    $stmt = $connection->prepare("DELETE FROM products WHERE product_id=?");
    $stmt->bind_param("i", $id);

    if(!$stmt->execute()){
        die("Delete failed: " . $stmt->error);
    }
    $stmt->close();
}

header("Location: products.php");
exit();
