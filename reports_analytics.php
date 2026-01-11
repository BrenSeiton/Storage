<?php
session_start();

if(!isset($_SESSION['username'])){
    header("Location: index.php");
    exit();
}

include 'database.php';

// Get date range parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month

// Sales Summary Report
$sales_query = "SELECT
    SUM(CASE WHEN t.transaction_type = 'sale' THEN t.quantity ELSE 0 END) as total_sold,
    SUM(CASE WHEN t.transaction_type = 'sale' THEN t.quantity * p.price ELSE 0 END) as total_revenue,
    SUM(CASE WHEN t.transaction_type = 'purchase' THEN t.quantity ELSE 0 END) as total_purchased,
    SUM(CASE WHEN t.transaction_type = 'return' THEN t.quantity ELSE 0 END) as total_returns,
    COUNT(DISTINCT CASE WHEN t.transaction_type = 'sale' THEN t.transaction_id END) as total_sale_transactions,
    COUNT(DISTINCT CASE WHEN t.transaction_type = 'purchase' THEN t.transaction_id END) as total_purchase_transactions
FROM transaction t
JOIN products p ON t.product_id = p.product_id
WHERE DATE(t.transaction_date) BETWEEN ? AND ?";

$stmt = $connection->prepare($sales_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$sales_summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Top Selling Products
$top_products_query = "SELECT
    p.product_name,
    p.barcode,
    p.category,
    SUM(t.quantity) as total_quantity,
    SUM(t.quantity * p.price) as total_revenue,
    COUNT(t.transaction_id) as transaction_count,
    AVG(t.quantity) as avg_quantity_per_transaction
FROM transaction t
JOIN products p ON t.product_id = p.product_id
WHERE t.transaction_type = 'sale' AND DATE(t.transaction_date) BETWEEN ? AND ?
GROUP BY p.product_id, p.product_name, p.barcode, p.category
ORDER BY total_revenue DESC
LIMIT 10";

$stmt = $connection->prepare($top_products_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$top_products = $stmt->get_result();
$stmt->close();

// Inventory Performance Report
$inventory_query = "SELECT
    p.product_id,
    p.product_name,
    p.barcode,
    p.category,
    p.stock_quantity as current_stock,
    p.price,
    COALESCE(sold.total_sold, 0) as total_sold,
    COALESCE(purchased.total_purchased, 0) as total_purchased,
    ROUND(COALESCE(sold.total_sold, 0) / GREATEST(p.stock_quantity + COALESCE(sold.total_sold, 0), 1), 2) as turnover_ratio,
    CASE
        WHEN p.stock_quantity = 0 THEN 'Out of Stock'
        WHEN p.stock_quantity <= 10 THEN 'Low Stock'
        ELSE 'Normal'
    END as stock_status
FROM products p
LEFT JOIN (
    SELECT product_id, SUM(quantity) as total_sold
    FROM transaction
    WHERE transaction_type = 'sale' AND DATE(transaction_date) BETWEEN ? AND ?
    GROUP BY product_id
) sold ON p.product_id = sold.product_id
LEFT JOIN (
    SELECT product_id, SUM(quantity) as total_purchased
    FROM transaction
    WHERE transaction_type = 'purchase' AND DATE(transaction_date) BETWEEN ? AND ?
    GROUP BY product_id
) purchased ON p.product_id = purchased.product_id
ORDER BY turnover_ratio DESC, p.stock_quantity ASC";

$stmt = $connection->prepare($inventory_query);
$stmt->bind_param("ssss", $start_date, $end_date, $start_date, $end_date);
$stmt->execute();
$inventory_performance = $stmt->get_result();
$stmt->close();

// Category Performance
$category_query = "SELECT
    p.category,
    COUNT(DISTINCT p.product_id) as product_count,
    SUM(p.stock_quantity) as total_stock,
    SUM(p.stock_quantity * p.price) as inventory_value,
    COALESCE(sales.total_sold, 0) as total_sold,
    COALESCE(sales.total_revenue, 0) as total_revenue
FROM products p
LEFT JOIN (
    SELECT p.product_id, SUM(t.quantity) as total_sold, SUM(t.quantity * p.price) as total_revenue
    FROM transaction t
    JOIN products p ON t.product_id = p.product_id
    WHERE t.transaction_type = 'sale' AND DATE(t.transaction_date) BETWEEN ? AND ?
    GROUP BY p.product_id
) sales ON p.product_id = sales.product_id
GROUP BY p.category
ORDER BY total_revenue DESC";

$stmt = $connection->prepare($category_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$category_performance = $stmt->get_result();
$stmt->close();

// Daily Sales Trend (last 30 days)
$daily_sales_query = "SELECT
    DATE(t.transaction_date) as sale_date,
    SUM(t.quantity) as daily_quantity,
    SUM(t.quantity * p.price) as daily_revenue,
    COUNT(DISTINCT t.transaction_id) as transaction_count
FROM transaction t
JOIN products p ON t.product_id = p.product_id
WHERE t.transaction_type = 'sale' AND DATE(t.transaction_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(t.transaction_date)
ORDER BY sale_date DESC";

$daily_sales = $connection->query($daily_sales_query);

// Calculate some additional metrics
$total_inventory_value = 0;
$inventory_result = $connection->query("SELECT SUM(stock_quantity * price) as total FROM products");
if($inventory_result) {
    $total_inventory_value = $inventory_result->fetch_assoc()['total'] ?? 0;
}
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
    <title>Reports & Analytics - Storage Inventory</title>
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
                    <a href="inventory_monitoring.php" class="nav-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Inventory Monitoring</span>
                    </a>
                    <a href="reports_analytics.php" class="nav-item active">
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
<?php endif; ?>

            <div class="content-header">
                <h1>Reports & Analytics</h1>
                <p>Generate comprehensive reports and analyze your inventory data.</p>
            </div>

            <!-- Date Range Filter -->
            <div class="form-container">
        <div class="form-header">
            <i class="fas fa-calendar-alt"></i>
            <h2>Date Range Filter</h2>
        </div>
        <form method="get" class="filter-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">
                        <i class="fas fa-calendar-start"></i>
                        Start Date
                    </label>
                    <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                </div>

                <div class="form-group">
                    <label for="end_date">
                        <i class="fas fa-calendar-end"></i>
                        End Date
                    </label>
                    <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-filter"></i>
                    Apply Filter
                </button>
                <a href="reports_analytics.php" class="btn-secondary">
                    <i class="fas fa-undo"></i>
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Key Metrics -->
    <div class="report-section">
        <div class="section-header">
            <i class="fas fa-chart-line"></i>
            <h2>Key Performance Metrics</h2>
        </div>
        <div class="metrics-grid">
            <div class="metric-card">
                <h4>Total Revenue</h4>
                <div class="value revenue">$<?php echo number_format($sales_summary['total_revenue'] ?? 0, 2); ?></div>
            </div>
            <div class="metric-card">
                <h4>Items Sold</h4>
                <div class="value sales"><?php echo number_format($sales_summary['total_sold'] ?? 0); ?></div>
            </div>
            <div class="metric-card">
                <h4>Sale Transactions</h4>
                <div class="value sales"><?php echo $sales_summary['total_sale_transactions'] ?? 0; ?></div>
            </div>
            <div class="metric-card">
                <h4>Items Purchased</h4>
                <div class="value inventory"><?php echo number_format($sales_summary['total_purchased'] ?? 0); ?></div>
            </div>
            <div class="metric-card">
                <h4>Items Returned</h4>
                <div class="value returns"><?php echo number_format($sales_summary['total_returns'] ?? 0); ?></div>
            </div>
            <div class="metric-card">
                <h4>Total Inventory Value</h4>
                <div class="value inventory">$<?php echo number_format($total_inventory_value, 2); ?></div>
            </div>
        </div>
    </div>

<!-- Top Performing Products -->
<div class="report-section">
        <div class="section-header">
            <i class="fas fa-trophy"></i>
            <h2>Top Performing Products</h2>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><i class="fas fa-tag"></i> Product Name</th>
                        <th><i class="fas fa-barcode"></i> Barcode</th>
                        <th><i class="fas fa-list"></i> Category</th>
                        <th><i class="fas fa-shopping-cart"></i> Units Sold</th>
                        <th><i class="fas fa-dollar-sign"></i> Revenue</th>
                        <th><i class="fas fa-exchange-alt"></i> Transactions</th>
                        <th><i class="fas fa-chart-line"></i> Avg per Transaction</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($top_products->num_rows > 0): ?>
                        <?php while($product = $top_products->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['barcode']); ?></td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td><?php echo $product['total_quantity']; ?></td>
                                <td>$<?php echo number_format($product['total_revenue'], 2); ?></td>
                                <td><?php echo $product['transaction_count']; ?></td>
                                <td><?php echo number_format($product['avg_quantity_per_transaction'], 1); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="no-data">
                                <i class="fas fa-chart-line"></i>
                                No sales data for the selected period.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
<!-- Inventory Performance -->
<div class="report-section">
    <div class="section-header">
        <i class="fas fa-boxes"></i>
        <h2>Inventory Performance Analysis</h2>
    </div>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th><i class="fas fa-tag"></i> Product Name</th>
                    <th><i class="fas fa-barcode"></i> Barcode</th>
                    <th><i class="fas fa-list"></i> Category</th>
                    <th><i class="fas fa-cubes"></i> Current Stock</th>
                    <th><i class="fas fa-shopping-cart"></i> Units Sold</th>
                    <th><i class="fas fa-chart-line"></i> Turnover Ratio</th>
                    <th><i class="fas fa-info-circle"></i> Stock Status</th>
                    <th><i class="fas fa-dollar-sign"></i> Inventory Value</th>
                </tr>
            </thead>
                <th>Inventory Value</th>
            </tr>
        </thead>
        <tbody>
            <?php while($item = $inventory_performance->fetch_assoc()): ?>
                <?php
                $turnover_class = 'low-turnover';
                if($item['turnover_ratio'] > 0.5) $turnover_class = 'high-turnover';
                elseif($item['turnover_ratio'] > 0.2) $turnover_class = 'medium-turnover';

                $status_class = 'normal-stock';
                if($item['stock_status'] == 'Out of Stock') $status_class = 'out-of-stock';
                elseif($item['stock_status'] == 'Low Stock') $status_class = 'low-stock';
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($item['barcode']); ?></td>
                    <td><?php echo htmlspecialchars($item['category']); ?></td>
                    <td><?php echo $item['current_stock']; ?></td>
                    <td><?php echo $item['total_sold']; ?></td>
                    <td><span class="performance-indicator <?php echo $turnover_class; ?>"><?php echo $item['turnover_ratio']; ?></span></td>
                    <td><span class="performance-indicator <?php echo $status_class; ?>"><?php echo $item['stock_status']; ?></span></td>
                    <td>$<?php echo number_format($item['current_stock'] * $item['price'], 2); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Category Performance -->
<div class="report-section">
    <div class="section-header">
        <i class="fas fa-folder"></i>
        <h2>Category Performance</h2>
    </div>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th><i class="fas fa-folder"></i> Category</th>
                    <th><i class="fas fa-box"></i> Products</th>
                    <th><i class="fas fa-cubes"></i> Total Stock</th>
                    <th><i class="fas fa-dollar-sign"></i> Inventory Value</th>
                    <th><i class="fas fa-shopping-cart"></i> Units Sold</th>
                    <th><i class="fas fa-chart-line"></i> Revenue</th>
                    <th><i class="fas fa-calculator"></i> Avg Revenue per Product</th>
                </tr>
            </thead>
        <tbody>
            <?php while($category = $category_performance->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($category['category'] ?: 'Uncategorized'); ?></td>
                    <td><?php echo $category['product_count']; ?></td>
                    <td><?php echo $category['total_stock']; ?></td>
                    <td>$<?php echo number_format($category['inventory_value'], 2); ?></td>
                    <td><?php echo $category['total_sold']; ?></td>
                    <td>$<?php echo number_format($category['total_revenue'], 2); ?></td>
                    <td>$<?php echo $category['product_count'] > 0 ? number_format($category['total_revenue'] / $category['product_count'], 2) : '0.00'; ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Daily Sales Trend -->
<div class="report-section">
    <div class="section-header">
        <i class="fas fa-chart-line"></i>
        <h2>Daily Sales Trend (Last 30 Days)</h2>
    </div>
    <div class="chart-placeholder">
        <i class="fas fa-chart-bar"></i>
        <h4>Sales Trend Chart</h4>
        <p>Interactive chart visualization would be displayed here</p>
        <p><em>Future enhancement: Add Chart.js or similar library for visual charts</em></p>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th><i class="fas fa-calendar"></i> Date</th>
                    <th><i class="fas fa-shopping-cart"></i> Daily Quantity</th>
                    <th><i class="fas fa-dollar-sign"></i> Daily Revenue</th>
                    <th><i class="fas fa-exchange-alt"></i> Transactions</th>
                    <th><i class="fas fa-calculator"></i> Avg per Transaction</th>
                </tr>
            </thead>
        <tbody>
            <?php while($day = $daily_sales->fetch_assoc()): ?>
                <tr>
                    <td><?php echo date('M d, Y', strtotime($day['sale_date'])); ?></td>
                    <td><?php echo $day['daily_quantity']; ?></td>
                    <td>$<?php echo number_format($day['daily_revenue'], 2); ?></td>
                    <td><?php echo $day['transaction_count']; ?></td>
                    <td>$<?php echo $day['transaction_count'] > 0 ? number_format($day['daily_revenue'] / $day['transaction_count'], 2) : '0.00'; ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Export Options -->
<div class="report-section">
    <div class="section-header">
        <i class="fas fa-download"></i>
        <h2>Export Reports</h2>
    </div>
    <p>Export functionality can be added here for CSV/PDF reports.</p>
    <div class="export-actions">
        <button class="btn-secondary export-btn" onclick="alert('Export functionality would be implemented here')">
            <i class="fas fa-file-csv"></i>
            Export to CSV
        </button>
        <button class="btn-primary" onclick="window.print()">
            <i class="fas fa-print"></i>
            Print Report
        </button>
    </div>
</div>

<?php if(!$isAjax): ?>
        </div>
    </div>
</body>
</html>
<?php endif; ?>