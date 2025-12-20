<?php
include 'database.php';
session_start();

if(!isset($_SESSION['username'])){
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];

error_reporting(E_ALL);
ini_set('display_errors', 1);

$query = "SELECT p.product_id, p.product_name, p.barcode, p.category, p.price, p.stock_quantity, 
                 s.supplier_name
          FROM products p
          LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id";

$result = $connection->query($query);
if(!$result){
    die("Query failed: " . $connection->error);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Product Management</title>
</head>
<body>

<h1>Product Management</h1>
<p>Welcome, <?php echo $username; ?> (<?php echo $role; ?>)</p>
<a href="add_product.php">Add Product</a> | <a href="dashboard.php">Back to Dashboard</a>

<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <th>ID</th>
        <th>Product Name</th>
        <th>Barcode</th>
        <th>Category</th>
        <th>Price</th>
        <th>Stock Quantity</th>
        <th>Supplier</th>
        <th>Actions</th>
    </tr>
    <?php 
    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['product_id']; ?></td>
            <td><?php echo $row['product_name']; ?></td>
            <td><?php echo $row['barcode']; ?></td>
            <td><?php echo $row['category']; ?></td>
            <td><?php echo $row['price']; ?></td>
            <td><?php echo $row['stock_quantity']; ?></td>
            <td><?php echo $row['supplier_name'] ?? 'No Supplier'; ?></td>
            <td>
                <a href="update_product.php?id=<?php echo $row['product_id']; ?>">Edit</a> |
                <a href="delete_product.php?id=<?php echo $row['product_id']; ?>">Delete</a>
            </td>
        </tr>
    <?php 
        endwhile;
    } else {
        echo "<tr><td colspan='8'>No products found in the database.</td></tr>";
    }
    ?>
</table>

</body>
</html>
