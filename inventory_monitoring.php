<?php
session_start();

if(!isset($_SESSION['username'])){
    header("Location: index.php");
    exit();
}

include 'database.php';

$message = "";

// Get low stock threshold from database
$threshold_query = "SELECT setting_value FROM inventory_settings WHERE setting_key = 'low_stock_threshold'";
$threshold_result = $connection->query($threshold_query);
$low_stock_threshold = $threshold_result->fetch_assoc()['setting_value'] ?? 10;

// Handle threshold update
if(isset($_POST['update_threshold'])) {
    $new_threshold = (int)$_POST['threshold'];
    $update_query = "INSERT INTO inventory_settings (setting_key, setting_value) VALUES ('low_stock_threshold', ?) ON DUPLICATE KEY UPDATE setting_value = ?";
    $stmt = $connection->prepare($update_query);
    $stmt->bind_param("is", $new_threshold, $new_threshold);
    if($stmt->execute()) {
        $low_stock_threshold = $new_threshold;
        $message = "Low stock threshold updated to $low_stock_threshold units.";
    } else {
        $message = "Error updating threshold.";
    }
    $stmt->close();
}

// Get current stock levels
$stock_query = "SELECT p.product_id, p.product_name, p.barcode, p.category, p.price, p.stock_quantity,
                       s.supplier_name
                FROM products p
                LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                ORDER BY p.stock_quantity ASC";

$stock_result = $connection->query($stock_query);

// Get low stock items
$low_stock_query = "SELECT p.product_id, p.product_name, p.barcode, p.category, p.stock_quantity,
                           s.supplier_name
                    FROM products p
                    LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                    WHERE p.stock_quantity <= $low_stock_threshold
                    ORDER BY p.stock_quantity ASC";

$low_stock_result = $connection->query($low_stock_query);

// Get out of stock items
$out_of_stock_query = "SELECT p.product_id, p.product_name, p.barcode, p.category,
                              s.supplier_name
                       FROM products p
                       LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                       WHERE p.stock_quantity = 0
                       ORDER BY p.product_name ASC";

$out_of_stock_result = $connection->query($out_of_stock_query);

// Get recent stock movements (last 20 transactions)
$movements_query = "SELECT t.transaction_id, t.transaction_type, t.quantity, t.transaction_date,
                           p.product_name, p.barcode, u.username
                    FROM transaction t
                    JOIN products p ON t.product_id = p.product_id
                    JOIN users u ON t.user_id = u.user_id
                    ORDER BY t.transaction_date DESC
                    LIMIT 20";

$movements_result = $connection->query($movements_query);

// Calculate stock statistics
$total_products = $stock_result->num_rows;
$low_stock_count = $low_stock_result->num_rows;
$out_of_stock_count = $out_of_stock_result->num_rows;

// Calculate total inventory value
$value_query = "SELECT SUM(p.stock_quantity * p.price) as total_value FROM products p";
$value_result = $connection->query($value_query);
$total_value = $value_result->fetch_assoc()['total_value'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Monitoring - Storage Inventory</title>
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
                    <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <span class="user-role"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <!-- Core Modules -->
                <div class="nav-section">
                    <h3 class="nav-title">Main Modules</h3>
                    <a href="products.php" class="nav-item">
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
                    <a href="inventory_monitoring.php" class="nav-item active">
                        <i class="fas fa-chart-line"></i>
                        <span>Inventory Monitoring</span>
                    </a>
                    <a href="reports_analytics.php" class="nav-item">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports & Analytics</span>
                    </a>
                </div>

                <!-- Admin Controls -->
                <?php if($_SESSION['role'] === "Admin"): ?>
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
            <div class="content-header">
                <h1>Inventory Monitoring</h1>
                <p>Monitor stock levels, track low inventory items, and manage stock thresholds.</p>
            </div>

            <?php if($message): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Stock Statistics Dashboard -->
            <div class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-boxes stat-icon"></i>
            <h3>Total Products</h3>
            <div class="stat-number total-products"><?php echo htmlspecialchars($total_products); ?></div>
        </div>
        <div class="stat-card">
            <i class="fas fa-exclamation-triangle stat-icon"></i>
            <h3>Low Stock Items</h3>
            <div class="stat-number low-stock"><?php echo htmlspecialchars($low_stock_count); ?></div>
        </div>
        <div class="stat-card">
            <i class="fas fa-times-circle stat-icon"></i>
            <h3>Out of Stock</h3>
            <div class="stat-number out-of-stock"><?php echo htmlspecialchars($out_of_stock_count); ?></div>
        </div>
        <div class="stat-card">
            <i class="fas fa-dollar-sign stat-icon"></i>
            <h3>Total Inventory Value</h3>
            <div class="stat-number total-value">$<?php echo number_format($total_value, 2); ?></div>
        </div>
    </div>

    <!-- Low Stock Threshold Configuration -->
    <div class="form-container">
        <div class="form-header">
            <i class="fas fa-cog"></i>
            <h2>Low Stock Alert Settings</h2>
        </div>
        <form method="post" class="threshold-form">
            <div class="form-group">
                <label for="threshold">
                    <i class="fas fa-bell"></i>
                    Alert when stock falls below
                </label>
                <div class="threshold-input-group">
                    <input type="number" id="threshold" name="threshold" value="<?php echo htmlspecialchars($low_stock_threshold); ?>" min="0" required>
                    <span class="unit-label">units</span>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" name="update_threshold" class="btn-primary">
                    <i class="fas fa-save"></i>
                    Update Threshold
                </button>
            </div>
        </form>
    </div>

    <!-- Stock Alerts -->
    <?php if($low_stock_count > 0 || $out_of_stock_count > 0): ?>
    <div class="alerts-section">
        <div class="alert-header">
            <i class="fas fa-exclamation-triangle"></i>
            <h2>Stock Alerts</h2>
        </div>

        <?php if($out_of_stock_count > 0): ?>
        <div class="alert-card critical">
            <h3><i class="fas fa-times-circle"></i> Out of Stock Items</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-tag"></i> Product Name</th>
                            <th><i class="fas fa-barcode"></i> Barcode</th>
                            <th><i class="fas fa-list"></i> Category</th>
                            <th><i class="fas fa-truck"></i> Supplier</th>
                            <th><i class="fas fa-info-circle"></i> Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($item = $out_of_stock_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['barcode']); ?></td>
                                <td><?php echo htmlspecialchars($item['category']); ?></td>
                                <td><?php echo htmlspecialchars($item['supplier_name'] ?? 'N/A'); ?></td>
                                <td><span class="status-badge status-out">OUT OF STOCK</span></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if($low_stock_count > 0): ?>
        <div class="alert-card warning">
            <h3><i class="fas fa-exclamation-triangle"></i> Low Stock Items (≤ <?php echo htmlspecialchars($low_stock_threshold); ?> units)</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-tag"></i> Product Name</th>
                            <th><i class="fas fa-barcode"></i> Barcode</th>
                            <th><i class="fas fa-list"></i> Category</th>
                            <th><i class="fas fa-cubes"></i> Current Stock</th>
                            <th><i class="fas fa-truck"></i> Supplier</th>
                            <th><i class="fas fa-info-circle"></i> Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($item = $low_stock_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['barcode']); ?></td>
                                <td><?php echo htmlspecialchars($item['category']); ?></td>
                                <td><?php echo htmlspecialchars($item['stock_quantity']); ?></td>
                                <td><?php echo htmlspecialchars($item['supplier_name'] ?? 'N/A'); ?></td>
                                <td><span class="status-badge status-low">LOW STOCK</span></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Current Stock Overview and Recent Movements Layout -->
    <div class="inventory-tables-layout">
        <!-- Current Stock Overview -->
    <div class="table-container">
        <div class="table-header">
            <i class="fas fa-warehouse"></i>
            <h2>Current Stock Overview</h2>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th><i class="fas fa-tag"></i> Product Name</th>
                    <th><i class="fas fa-barcode"></i> Barcode</th>
                    <th><i class="fas fa-list"></i> Category</th>
                    <th><i class="fas fa-cubes"></i> Stock Quantity</th>
                    <th><i class="fas fa-dollar-sign"></i> Unit Price</th>
                    <th><i class="fas fa-calculator"></i> Total Value</th>
                    <th><i class="fas fa-truck"></i> Supplier</th>
                    <th><i class="fas fa-info-circle"></i> Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stock_result->data_seek(0); // Reset result pointer
                while($product = $stock_result->fetch_assoc()):
                    $status_class = 'status-normal';
                    $status_text = 'NORMAL';
                    if($product['stock_quantity'] == 0) {
                        $status_class = 'status-out';
                        $status_text = 'OUT OF STOCK';
                    } elseif($product['stock_quantity'] <= $low_stock_threshold) {
                        $status_class = 'status-low';
                        $status_text = 'LOW STOCK';
                    }
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($product['barcode']); ?></td>
                        <td><?php echo htmlspecialchars($product['category']); ?></td>
                        <td><?php echo htmlspecialchars($product['stock_quantity']); ?></td>
                        <td>$<?php echo number_format($product['price'], 2); ?></td>
                        <td>$<?php echo number_format($product['stock_quantity'] * $product['price'], 2); ?></td>
                        <td><?php echo htmlspecialchars($product['supplier_name'] ?? 'N/A'); ?></td>
                        <td><span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($status_text); ?></span></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Recent Stock Movements -->
    <div class="table-container">
        <div class="table-header">
            <i class="fas fa-history"></i>
            <h2>Recent Stock Movements</h2>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th><i class="fas fa-calendar"></i> Date & Time</th>
                    <th><i class="fas fa-box"></i> Product</th>
                    <th><i class="fas fa-exchange-alt"></i> Transaction Type</th>
                    <th><i class="fas fa-hashtag"></i> Quantity</th>
                    <th><i class="fas fa-user"></i> User</th>
                </tr>
            </thead>
            <tbody>
                <?php if($movements_result->num_rows > 0): ?>
                    <?php while($movement = $movements_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i', strtotime($movement['transaction_date'])); ?></td>
                            <td>
                                <?php echo htmlspecialchars($movement['product_name']); ?><br>
                                <small class="barcode-info">Barcode: <?php echo htmlspecialchars($movement['barcode']); ?></small>
                            </td>
                            <td>
                                <span class="transaction-badge <?php echo $movement['transaction_type']; ?>">
                                    <?php echo ucfirst($movement['transaction_type']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($movement['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($movement['username']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="no-data">
                            <i class="fas fa-inbox"></i>
                            No recent stock movements.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    </div>
        </div>
    </div>
</body>
</html>