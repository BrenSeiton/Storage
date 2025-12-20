<?php
include 'database.php';
session_start();

if(!isset($_SESSION['username'])){
    header("Location: index.php");
    exit();
}

if(isset($_POST['add'])){
    $name = $_POST['product_name'];
    $barcode = $_POST['barcode'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $stock = $_POST['stock_quantity'];
    $supplier_id = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;

    $stmt = $connection->prepare("INSERT INTO products (product_name, barcode, category, price, stock_quantity, supplier_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdis", $name, $barcode, $category, $price, $stock, $supplier_id);

    if(!$stmt->execute()){
        die("Insert failed: " . $stmt->error);
    }
    $stmt->close();

    header("Location: products.php");
    exit();
}

$suppliers = $connection->query("SELECT * FROM suppliers");
?>

<h1>Add Product</h1>
<form method="POST">
    Product Name: <input type="text" name="product_name" required><br>
    Barcode: <input type="text" name="barcode" required><br>
    Category: <input type="text" name="category"><br>
    Price: <input type="number" step="0.01" name="price" required><br>
    Stock Quantity: <input type="number" name="stock_quantity" required><br>
    Supplier: 
    <select name="supplier_id">
        <option value="">-- Select Supplier --</option>
        <?php while($row = $suppliers->fetch_assoc()): ?>
            <option value="<?php echo $row['supplier_id']; ?>"><?php echo $row['supplier_name']; ?></option>
        <?php endwhile; ?>
    </select><br>
    <button type="submit" name="add">Add Product</button>
</form>
<a href="products.php">Back to Products</a>
