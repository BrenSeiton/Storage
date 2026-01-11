<?php
session_start();

if(!isset($_SESSION['username'])){
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Handle admin authentication for staff users
$admin_authenticated = false;
$admin_error = "";

if(isset($_POST['admin_auth'])) {
    $admin_username = $_POST['admin_username'];
    $admin_password = $_POST['admin_password'];

    include 'database.php';

    $stmt = $connection->prepare("SELECT password, role FROM users WHERE username = ? AND role = 'Admin'");
    $stmt->bind_param("s", $admin_username);
    $stmt->execute();
    $stmt->bind_result($truepassword, $admin_role);
    $stmt->fetch();
    $stmt->close();

    if($admin_role === 'Admin' && password_verify($admin_password, $truepassword)) {
        $admin_authenticated = true;
        $_SESSION['admin_authenticated'] = true;
    } else {
        $admin_error = "Invalid admin credentials";
    }
}

if(isset($_POST['admin_logout'])) {
    unset($_SESSION['admin_authenticated']);
    $admin_authenticated = false;
}

if(isset($_SESSION['admin_authenticated'])) {
    $admin_authenticated = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Storage Inventory</title>
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
                <?php elseif($role === "Staff"): ?>
                    <div class="nav-section">
                        <h3 class="nav-title">Administration</h3>
                        <div class="nav-item staff-admin-toggle">
                            <i class="fas fa-key"></i>
                            <span>Admin Access</span>
                        </div>
                        <?php if($admin_error): ?>
                            <div class="admin-error"><?php echo htmlspecialchars($admin_error); ?></div>
                        <?php endif; ?>
                        <form method="post" class="admin-auth-form">
                            <input type="text" name="admin_username" placeholder="Admin Username" required>
                            <input type="password" name="admin_password" placeholder="Admin Password" required>
                            <button type="submit" name="admin_auth" class="btn-primary">
                                <i class="fas fa-unlock"></i>
                                Authenticate
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </nav>

            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
                <?php if($admin_authenticated): ?>
                    <form method="post" style="margin-top: 10px;">
                        <button type="submit" name="admin_logout" class="btn-secondary">
                            <i class="fas fa-sign-out-alt"></i>
                            End Admin Session
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="main-content">
            <div class="content-header">
                <h1>Welcome to Inventory Dashboard</h1>
                <p>Select a module from the sidebar to get started</p>
            </div>

            <div class="dashboard-overview">
                <div class="overview-stats">
                    <div class="stat-card">
                        <i class="fas fa-boxes stat-icon"></i>
                        <h3>Quick Actions</h3>
                        <p>Use the sidebar to navigate to different modules</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-chart-line stat-icon"></i>
                        <h3>System Status</h3>
                        <p>All systems operational</p>
                    </div>
                </div>

                <div class="recent-activity">
                    <h3>Recent Activity</h3>
                    <div class="activity-placeholder">
                        <i class="fas fa-clock"></i>
                        <p>Activity feed will be displayed here</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
