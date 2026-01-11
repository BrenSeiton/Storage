<?php
session_start();

if(!isset($_SESSION['username'])){
    header("Location: index.php");
    exit();
}

include 'database.php';

$message = "";
$user_id = null;

// Get current user ID
$stmt = $connection->prepare("SELECT user_id FROM users WHERE username = ?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
    $user_id = $user_data['user_id'];
}
$stmt->close();

// Handle new transaction
if(isset($_POST['add_transaction'])) {
    $product_id = $_POST['product_id'];
    $transaction_type = $_POST['transaction_type'];
    $quantity = (int)$_POST['quantity'];

    // Start transaction for data consistency
    $connection->begin_transaction();

    try {
        // Insert transaction record
        $stmt = $connection->prepare("INSERT INTO transaction (product_id, user_id, transaction_type, quantity, transaction_date) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iisi", $product_id, $user_id, $transaction_type, $quantity);
        $stmt->execute();
        $stmt->close();

        // Update product stock based on transaction type
        if($transaction_type === 'sale') {
            // Decrease stock for sales
            $stmt = $connection->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ? AND stock_quantity >= ?");
            $stmt->bind_param("iii", $quantity, $product_id, $quantity);
        } elseif($transaction_type === 'purchase') {
            // Increase stock for purchases
            $stmt = $connection->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?");
            $stmt->bind_param("ii", $quantity, $product_id);
        } elseif($transaction_type === 'return') {
            // Increase stock for returns (assuming customer returns)
            $stmt = $connection->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?");
            $stmt->bind_param("ii", $quantity, $product_id);
        }

        $result = $stmt->execute();
        $stmt->close();

        if($result) {
            $connection->commit();
            $message = ucfirst($transaction_type) . " transaction recorded successfully!";
        } else {
            throw new Exception("Failed to update product stock");
        }

    } catch(Exception $e) {
        $connection->rollback();
        $message = "Error: " . $e->getMessage();
    }
}

// Get products for dropdown
$products = $connection->query("SELECT product_id, product_name, barcode, stock_quantity FROM products ORDER BY product_name");

// Get recent transactions
$transactions = $connection->query("
    SELECT t.*, p.product_name, p.barcode, u.username
    FROM transaction t
    JOIN products p ON t.product_id = p.product_id
    JOIN users u ON t.user_id = u.user_id
    ORDER BY t.transaction_date DESC
    LIMIT 50
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales & Transactions - Storage Inventory</title>
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
                    <a href="transactions.php" class="nav-item active">
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
                <h1>Sales & Transactions</h1>
                <p>Record sales, purchases, and returns. Monitor transaction history.</p>
            </div>

            <?php if($message): ?>
                <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
                    <i class="fas fa-<?php echo strpos($message, 'Error') !== false ? 'exclamation-triangle' : 'check-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="transactions-layout">
                <div class="form-container">
        <div class="form-header">
            <i class="fas fa-plus-circle"></i>
            <h2>Record New Transaction</h2>
        </div>
        <form method="post" class="transaction-form">
            <div class="form-group">
                <label for="product_id">
                    <i class="fas fa-box"></i>
                    Product *
                </label>
                <select name="product_id" id="product_id" required>
                    <option value="">-- Select Product --</option>
                    <?php while($product = $products->fetch_assoc()): ?>
                        <option value="<?php echo $product['product_id']; ?>">
                            <?php echo htmlspecialchars($product['product_name']); ?>
                            (Barcode: <?php echo htmlspecialchars($product['barcode']); ?>)
                            - Stock: <?php echo $product['stock_quantity']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="transaction_type">
                    <i class="fas fa-exchange-alt"></i>
                    Transaction Type *
                </label>
                <select name="transaction_type" id="transaction_type" required>
                    <option value="">-- Select Type --</option>
                    <option value="sale">Sale (Outgoing)</option>
                    <option value="purchase">Purchase (Incoming)</option>
                    <option value="return">Return (Incoming)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="quantity">
                    <i class="fas fa-hashtag"></i>
                    Quantity *
                </label>
                <input type="number" name="quantity" id="quantity" min="1" required placeholder="Enter quantity">
            </div>

            <div class="form-actions">
                <button type="submit" name="add_transaction" class="btn-primary">
                    <i class="fas fa-save"></i>
                    Record Transaction
                </button>
            </div>
        </form>
    </div>

    <div class="table-container">
        <div class="table-header">
            <i class="fas fa-history"></i>
            <h2>Recent Transactions</h2>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th><i class="fas fa-calendar"></i> Date</th>
                    <th><i class="fas fa-box"></i> Product</th>
                    <th><i class="fas fa-tags"></i> Type</th>
                    <th><i class="fas fa-hashtag"></i> Quantity</th>
                    <th><i class="fas fa-user"></i> User</th>
                </tr>
            </thead>
            <tbody>
                <?php if($transactions->num_rows > 0): ?>
                    <?php while($transaction = $transactions->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i', strtotime($transaction['transaction_date'])); ?></td>
                            <td>
                                <?php echo htmlspecialchars($transaction['product_name']); ?><br>
                                <small class="barcode-info">Barcode: <?php echo htmlspecialchars($transaction['barcode']); ?></small>
                            </td>
                            <td>
                                <span class="transaction-badge <?php echo $transaction['transaction_type']; ?>">
                                    <?php echo ucfirst($transaction['transaction_type']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($transaction['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['username']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="no-data">
                            <i class="fas fa-inbox"></i>
                            No transactions recorded yet.
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