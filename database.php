<?php
$hostname = 'localhost';
$serverusername = 'root';
$dbpassword = '';
$databasename = "c3_inventory"; 

$connection = mysqli_connect($hostname, $serverusername, $dbpassword, $databasename);
if(!$connection){
    die("<p style='color:red;'>Failed to Connect. Please check your connection: " . mysqli_connect_error() . "</p>");
}
?>
