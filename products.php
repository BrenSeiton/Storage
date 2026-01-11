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

// Handle admin authentication for staff users
$admin_authenticated = false;
if(isset($_SESSION['admin_authenticated'])) {
    $admin_authenticated = true;
}

$query = "SELECT p.product_id, p.product_name, p.barcode, p.category, p.price, p.stock_quantity,
                 s.supplier_name
          FROM products p
          LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id";

$result = $connection->query($query);
if(!$result){
    die("Query failed: " . $connection->error);
}

// Check if this is an AJAX request
$isAjax = isset($_GET['ajax']);
?>

<?php if(!$isAjax): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Storage Inventory</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-tachometer-alt sidebar-logo"></i>
                <h2>Inventory System</h2>
            </div>

            <div class="sidebar-user">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <span class="username"><?php echo htmlspecialchars($username); ?></span>
                    <span class="user-role"><?php echo htmlspecialchars($role); ?></span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <!-- Core Modules -->
                <div class="nav-section">
                    <h3 class="nav-title">Main Modules</h3>
                    <a href="products.php" class="nav-item active">
                        <i class="fas fa-boxes"></i>
                        <span>Product Management</span>
                    </a>
                    <a href="barcode_scanner.php" class="nav-item">
                        <i class="fas fa-barcode"></i>
                        <span>Barcode Scanner</span>
                    </a>
                    <a href="transactions.php" class="nav-item">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Sales & Transactions</span>
                    </a>
                    <a href="inventory_monitoring.php" class="nav-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Inventory Monitoring</span>
                    </a>
                    <a href="reports_analytics.php" class="nav-item">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports & Analytics</span>
                    </a>
                </div>

                <!-- Admin Controls -->
                <?php if($role === "Admin" || $admin_authenticated): ?>
                    <div class="nav-section">
                        <h3 class="nav-title">Administration</h3>
                        <a href="users.php" class="nav-item admin-nav">
                            <i class="fas fa-users-cog"></i>
                            <span>User Management</span>
                        </a>
                        <a href="supplier.php" class="nav-item admin-nav">
                            <i class="fas fa-truck"></i>
                            <span>Supplier Management</span>
                        </a>
                        <?php if($admin_authenticated): ?>
                            <div class="admin-status">
                                <i class="fas fa-shield-alt"></i>
                                <span>Admin Access Active</span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </nav>

            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="main-content">
<?php endif; ?>

            <div class="content-header">
                <h1>Product Management</h1>
                <p>Manage your inventory items, add new products, and update stock levels</p>
            </div>

            <div class="action-buttons">
                <a href="add_product.php" class="btn-primary">
                    <i class="fas fa-plus"></i>
                    Add New Product
                </a>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-tag"></i> Product Name</th>
                            <th><i class="fas fa-barcode"></i> Barcode</th>
                            <th><i class="fas fa-list"></i> Category</th>
                            <th><i class="fas fa-dollar-sign"></i> Price</th>
                            <th><i class="fas fa-cubes"></i> Stock Quantity</th>
                            <th><i class="fas fa-truck"></i> Supplier</th>
                            <th><i class="fas fa-cogs"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if($result->num_rows > 0){
                            while($row = $result->fetch_assoc()):
                                $stockClass = $row['stock_quantity'] < 10 ? 'low-stock' : 'normal-stock';
                        ?>
                        <tr class="<?php echo $stockClass; ?>">
                            <td><?php echo htmlspecialchars($row['product_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['barcode']); ?></td>
                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                            <td>$<?php echo number_format($row['price'], 2); ?></td>
                            <td>
                                <span class="stock-badge <?php echo $stockClass; ?>">
                                    <?php echo htmlspecialchars($row['stock_quantity']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['supplier_name'] ?? 'No Supplier'); ?></td>
                            <td class="actions">
                                <a href="update_product.php?id=<?php echo $row['product_id']; ?>" class="btn-edit" title="Edit Product">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete_product.php?id=<?php echo $row['product_id']; ?>" class="btn-delete" title="Delete Product" onclick="return confirm('Are you sure you want to delete this product?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php
                            endwhile;
                        } else {
                            echo "<tr><td colspan='8' class='no-data'><i class='fas fa-inbox'></i> No products found in the database.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

<?php if(!$isAjax): ?>
        </div>
    </div>
</body>
</html>
<?php endif; ?>
