<?php
session_start();

if(!isset($_SESSION['username']) || ($_SESSION['role'] !== "Admin" && !isset($_SESSION['admin_authenticated']))){
    header("Location: dashboard.php");
    exit();
}

include "database.php";

// Get all suppliers
$query = "SELECT * FROM suppliers ORDER BY supplier_name ASC";
$result = mysqli_query($connection, $query);
$suppliers = [];
if($result) {
    while($row = mysqli_fetch_assoc($result)){
        $suppliers[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Management - Storage Inventory</title>
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
                        <a href="supplier.php" class="nav-item admin-nav active">
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
                <h1>Supplier Management</h1>
                <p>Manage your suppliers, add new vendors, and update supplier information.</p>
            </div>

            <div class="suppliers-layout">
                <!-- Add Supplier Form -->
                <div class="form-container">
            <div class="form-header">
                <i class="fas fa-plus-circle"></i>
                <h2>Add New Supplier</h2>
            </div>
            <form action="supplier_action.php" method="post" class="supplier-form">
                <div class="form-group">
                    <label for="supplier_name">
                        <i class="fas fa-building"></i>
                        Supplier Name *
                    </label>
                    <input type="text" name="supplier_name" id="supplier_name" placeholder="Enter supplier name" required>
                </div>

                <div class="form-group">
                    <label for="contact_number">
                        <i class="fas fa-phone"></i>
                        Contact Number *
                    </label>
                    <input type="text" name="contact_number" id="contact_number" placeholder="Enter contact number" required>
                </div>

                <div class="form-group">
                    <label for="address">
                        <i class="fas fa-map-marker-alt"></i>
                        Address *
                    </label>
                    <textarea name="address" id="address" placeholder="Enter supplier address" required rows="3"></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" name="add_supplier" class="btn-primary">
                        <i class="fas fa-save"></i>
                        Add Supplier
                    </button>
                </div>
            </form>
        </div>

        <!-- Suppliers List -->
        <div class="table-container">
            <div class="table-header">
                <i class="fas fa-list"></i>
                <h2>All Suppliers (<?php echo count($suppliers); ?>)</h2>
            </div>

            <?php if(count($suppliers) > 0): ?>
                <div class="suppliers-grid">
                    <?php foreach($suppliers as $supplier): ?>
                        <div class="supplier-card">
                            <div class="supplier-header">
                                <i class="fas fa-building supplier-icon"></i>
                                <h3><?php echo htmlspecialchars($supplier['supplier_name']); ?></h3>
                            </div>
                            <div class="supplier-details">
                                <div class="detail-item">
                                    <i class="fas fa-phone"></i>
                                    <span><?php echo htmlspecialchars($supplier['contact_number']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($supplier['address']); ?></span>
                                </div>
                            </div>
                            <div class="supplier-actions">
                                <a href="supplier_action.php?edit_id=<?php echo $supplier['supplier_id']; ?>" class="btn-edit">
                                    <i class="fas fa-edit"></i>
                                    Edit
                                </a>
                                <a href="supplier_action.php?delete_id=<?php echo $supplier['supplier_id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this supplier?')">
                                    <i class="fas fa-trash"></i>
                                    Delete
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <h3>No suppliers found</h3>
                    <p>Add your first supplier using the form above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
        </div>
    </div>
</body>
</html>
