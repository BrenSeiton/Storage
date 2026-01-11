<?php
include 'database.php';
session_start();

if(!isset($_SESSION['username'])){
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];

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

<?php
// Check if this is an AJAX request
$isAjax = isset($_GET['ajax']);
?>

<?php if(!$isAjax): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Storage Inventory</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar would go here, but since it's AJAX loaded, we skip -->
        <div class="main-content">
<?php endif; ?>

            <div class="content-header">
                <h1>Add New Product</h1>
                <p>Enter the details for the new product to add to your inventory.</p>
            </div>

            <div class="form-container">
                <form method="POST" class="product-form">
                    <div class="form-group">
                        <label for="product_name">
                            <i class="fas fa-tag"></i>
                            Product Name *
                        </label>
                        <input type="text" id="product_name" name="product_name" required placeholder="Enter product name">
                    </div>

                    <div class="form-group">
                        <label for="barcode">
                            <i class="fas fa-barcode"></i>
                            Barcode *
                        </label>
                        <input type="text" id="barcode" name="barcode" required placeholder="Enter barcode">
                    </div>

                    <div class="form-group">
                        <label for="category">
                            <i class="fas fa-list"></i>
                            Category
                        </label>
                        <input type="text" id="category" name="category" placeholder="Enter category">
                    </div>

                    <div class="form-group">
                        <label for="price">
                            <i class="fas fa-dollar-sign"></i>
                            Price *
                        </label>
                        <input type="number" id="price" step="0.01" name="price" required placeholder="0.00">
                    </div>

                    <div class="form-group">
                        <label for="stock_quantity">
                            <i class="fas fa-cubes"></i>
                            Stock Quantity *
                        </label>
                        <input type="number" id="stock_quantity" name="stock_quantity" required placeholder="0">
                    </div>

                    <div class="form-group">
                        <label for="supplier_id">
                            <i class="fas fa-truck"></i>
                            Supplier
                        </label>
                        <select id="supplier_id" name="supplier_id">
                            <option value="">-- Select Supplier --</option>
                            <?php while($row = $suppliers->fetch_assoc()): ?>
                                <option value="<?php echo $row['supplier_id']; ?>"><?php echo htmlspecialchars($row['supplier_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="add" class="btn-primary">
                            <i class="fas fa-save"></i>
                            Add Product
                        </button>
                        <a href="products.php" class="btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Back to Products
                        </a>
                    </div>
                </form>
            </div>

<?php if(!$isAjax): ?>
        </div>
    </div>
</body>
</html>
<?php endif; ?>
