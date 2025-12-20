<?php
include 'database.php';
session_start();

if(!isset($_SESSION['username'])){
    header("Location: index.php");
    exit();
}

$id = $_GET['id'] ?? null;
if(!$id){
    die("No product selected!");
}

if(isset($_POST['update'])){
    $name = $_POST['product_name'];
    $barcode = $_POST['barcode'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $stock = $_POST['stock_quantity'];
    $supplier_id = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;

    $stmt = $connection->prepare("UPDATE products SET product_name=?, barcode=?, category=?, price=?, stock_quantity=?, supplier_id=? WHERE product_id=?");
    $stmt->bind_param("sssdisi", $name, $barcode, $category, $price, $stock, $supplier_id, $id);

    if(!$stmt->execute()){
        die("Update failed: " . $stmt->error);
    }
    $stmt->close();

    header("Location: products.php");
    exit();
}

$stmt = $connection->prepare("SELECT * FROM products WHERE product_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

$suppliers = $connection->query("SELECT * FROM suppliers");
?>

<h1>Edit Product</h1>
<form method="POST">
    Product Name: <input type="text" name="product_name" value="<?php echo $product['product_name']; ?>" required><br>
    Barcode: <input type="text" name="barcode" value="<?php echo $product['barcode']; ?>" required><br>
    Category: <input type="text" name="category" value="<?php echo $product['category']; ?>"><br>
    Price: <input type="number" step="0.01" name="price" value="<?php echo $product['price']; ?>" required><br>
    Stock Quantity: <input type="number" name="stock_quantity" value="<?php echo $product['stock_quantity']; ?>" required><br>
    Supplier: 
    <select name="supplier_id">
        <option value="">-- Select Supplie
