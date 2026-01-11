<?php
session_start();

if(!isset($_SESSION['username']) || ($_SESSION['role'] !== "Admin" && !isset($_SESSION['admin_authenticated']))){
    header("Location: dashboard.php");
    exit();
}

include "database.php";

if(isset($_POST['add_supplier'])){
    $supplier_name = $_POST['supplier_name'];
    $contact_number = $_POST['contact_number'];
    $address = $_POST['address'];

    $query = "INSERT INTO suppliers 
              (supplier_name, contact_number, address)
              VALUES 
              ('$supplier_name', '$contact_number', '$address')";

    mysqli_query($connection, $query);
    header("Location: supplier.php");
}
?>
