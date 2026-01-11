<?php
session_start();

if(!isset($_SESSION['username'])){
    header("Location: index.php");
    exit();
}

include 'database.php';

$message = "";
$product = null;

if(isset($_POST['barcode'])) {
    $barcode = trim($_POST['barcode']);

    // Search for product by barcode
    $stmt = $connection->prepare("SELECT p.*, s.supplier_name FROM products p LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id WHERE p.barcode = ?");
    $stmt->bind_param("s", $barcode);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        $message = "Product found: " . $product['product_name'];
    } else {
        $message = "Product not found with barcode: " . $barcode;
    }
    $stmt->close();
}

if(isset($_POST['update_stock'])) {
    $product_id = $_POST['product_id'];
    $new_quantity = (int)$_POST['new_quantity'];

    $stmt = $connection->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?");
    $stmt->bind_param("ii", $new_quantity, $product_id);

    if($stmt->execute()) {
        $message = "Stock updated successfully!";
        // Refresh product data
        $stmt2 = $connection->prepare("SELECT p.*, s.supplier_name FROM products p LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id WHERE p.product_id = ?");
        $stmt2->bind_param("i", $product_id);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        $product = $result2->fetch_assoc();
        $stmt2->close();
    } else {
        $message = "Error updating stock.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Scanner - Storage Inventory</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
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
                    <a href="barcode_scanner.php" class="nav-item active">
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
                <h1>Barcode Scanner</h1>
                <p>Scan product barcodes to quickly access and update inventory information.</p>
            </div>

            <?php if($message): ?>
                <div class="message <?php echo strpos($message, 'Error') !== false || strpos($message, 'not found') !== false ? 'error' : (strpos($message, 'updated') !== false ? 'success' : 'warning'); ?>">
                    <i class="fas fa-<?php echo strpos($message, 'Error') !== false || strpos($message, 'not found') !== false ? 'exclamation-triangle' : (strpos($message, 'updated') !== false ? 'check-circle' : 'info-circle'); ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="scanner-controls">
        <button id="start-scan" class="btn-primary">
            <i class="fas fa-camera"></i>
            Start Camera Scan
        </button>
        <button id="stop-scan" class="btn-danger" style="display:none;">
            <i class="fas fa-stop"></i>
            Stop Scan
        </button>

        <div class="manual-search">
            <form method="post">
                <div class="form-group">
                    <label for="manual-barcode">
                        <i class="fas fa-keyboard"></i>
                        Or enter barcode manually
                    </label>
                    <input type="text" id="manual-barcode" name="barcode" placeholder="Enter barcode here" required>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-search"></i>
                    Search Product
                </button>
            </form>
        </div>
    </div>

    <div id="scanner-container" class="scanner-container">
        <div id="scanner" class="scanner-view"></div>
        <div class="scanner-overlay">
            <i class="fas fa-crosshairs scanner-target"></i>
            <p>Position barcode within the frame</p>
        </div>
    </div>

    <?php if($product): ?>
    <div class="product-details-card">
        <div class="card-header">
            <i class="fas fa-box-open"></i>
            <h2>Product Details</h2>
        </div>
        <div class="product-info-grid">
            <div class="info-item">
                <label><i class="fas fa-hashtag"></i> Product ID</label>
                <span><?php echo htmlspecialchars($product['product_id']); ?></span>
            </div>
            <div class="info-item">
                <label><i class="fas fa-tag"></i> Product Name</label>
                <span><?php echo htmlspecialchars($product['product_name']); ?></span>
            </div>
            <div class="info-item">
                <label><i class="fas fa-barcode"></i> Barcode</label>
                <span><?php echo htmlspecialchars($product['barcode']); ?></span>
            </div>
            <div class="info-item">
                <label><i class="fas fa-list"></i> Category</label>
                <span><?php echo htmlspecialchars($product['category']); ?></span>
            </div>
            <div class="info-item">
                <label><i class="fas fa-dollar-sign"></i> Price</label>
                <span>$<?php echo number_format($product['price'], 2); ?></span>
            </div>
            <div class="info-item">
                <label><i class="fas fa-cubes"></i> Current Stock</label>
                <span id="current-stock" class="stock-count <?php echo $product['stock_quantity'] < 10 ? 'low-stock' : 'normal-stock'; ?>">
                    <?php echo htmlspecialchars($product['stock_quantity']); ?>
                </span>
            </div>
            <div class="info-item full-width">
                <label><i class="fas fa-truck"></i> Supplier</label>
                <span><?php echo htmlspecialchars($product['supplier_name'] ?: 'N/A'); ?></span>
            </div>
        </div>

        <div class="stock-update-section">
            <h3><i class="fas fa-edit"></i> Update Stock</h3>
            <form method="post" class="stock-update-form">
                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                <div class="form-group">
                    <label for="new_quantity">
                        <i class="fas fa-plus-minus"></i>
                        New Quantity
                    </label>
                    <input type="number" id="new_quantity" name="new_quantity" value="<?php echo htmlspecialchars($product['stock_quantity']); ?>" min="0" required>
                </div>
                <button type="submit" name="update_stock" class="btn-primary">
                    <i class="fas fa-save"></i>
                    Update Stock
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const startButton = document.getElementById('start-scan');
        const stopButton = document.getElementById('stop-scan');
        const scannerContainer = document.getElementById('scanner-container');
        const manualInput = document.getElementById('manual-barcode');

        let scanning = false;

        startButton.addEventListener('click', function() {
            if (!scanning) {
                startScanning();
            }
        });

        stopButton.addEventListener('click', function() {
            if (scanning) {
                stopScanning();
            }
        });

        function startScanning() {
            scannerContainer.style.display = 'block';
            startButton.style.display = 'none';
            stopButton.style.display = 'inline-flex';

            Quagga.init({
                inputStream: {
                    name: "Live",
                    type: "LiveStream",
                    target: document.querySelector('#scanner'),
                    constraints: {
                        width: 640,
                        height: 480,
                        facingMode: "environment" // Use back camera
                    }
                },
                locator: {
                    patchSize: "medium",
                    halfSample: true
                },
                numOfWorkers: 2,
                decoder: {
                    readers: ["code_128_reader", "ean_reader", "ean_8_reader", "code_39_reader", "upc_reader", "upc_e_reader"]
                },
                locate: true
            }, function(err) {
                if (err) {
                    console.error(err);
                    alert('Error initializing camera: ' + err.message);
                    stopScanning();
                    return;
                }
                Quagga.start();
                scanning = true;
            });

            Quagga.onDetected(function(result) {
                const code = result.codeResult.code;
                console.log('Barcode detected:', code);

                // Stop scanning and submit the form
                stopScanning();

                // Set the barcode value and submit
                manualInput.value = code;
                manualInput.form.submit();
            });
        }

        function stopScanning() {
            if (scanning) {
                Quagga.stop();
                scanning = false;
            }
            scannerContainer.style.display = 'none';
            startButton.style.display = 'inline-flex';
            stopButton.style.display = 'none';
        }

        // Handle camera permission errors
        navigator.mediaDevices.getUserMedia({ video: true })
            .catch(function(err) {
                console.error('Camera permission denied:', err);
                alert('Camera access is required for barcode scanning. Please allow camera permissions and try again.');
            });
    });
    </script>
</body>
</html>