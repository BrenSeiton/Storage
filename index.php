<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storage Inventory Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="script.js" defer></script>
</head>
<body>
    <div class="header">
        <i class="fas fa-warehouse"></i>
        <h1>Storage Inventory System</h1>
        <p>Professional Inventory Management Solution</p>
    </div>

    <div id="form" class="container">
        <div class="welcome-content">
            <i class="fas fa-boxes welcome-icon"></i>
            <h2>Welcome to Your Inventory Hub</h2>
            <p>Manage your products, track inventory, and streamline operations with ease.</p>
        </div>
    </div>

    <div class="container nav-buttons">
        <button onclick="loadloginform()" class="login-btn">
            <i class="fas fa-sign-in-alt"></i>
            Login to Dashboard
        </button>
        <button onclick="loadregisterform()" class="register-btn">
            <i class="fas fa-user-plus"></i>
            Register New Staff
        </button>
    </div>

    <script>
        function loadloginform() {
            const formContainer = document.getElementById("form");
            formContainer.innerHTML = `
                <div class="fade-in">
                    <i class="fas fa-lock login-icon"></i>
                    <h1>Secure Login</h1>
                    <form action="login.php" method="post">
                        <input type="text" name="username" placeholder="Enter Username" required>
                        <input type="password" name="password" placeholder="Enter Password" required>
                        <button type="submit">
                            <i class="fas fa-arrow-right"></i>
                            Login
                        </button>
                    </form>
                </div>
            `;
        }

        function loadregisterform() {
            const formContainer = document.getElementById("form");
            formContainer.innerHTML = `
                <div class="fade-in">
                    <i class="fas fa-user-plus register-icon"></i>
                    <h1>Register New Staff</h1>
                    <p><em><i class="fas fa-info-circle"></i> Note: New accounts are created as "Staff" role. Only administrators can change user roles.</em></p>
                    <form action="register.php" method="post">
                        <input type="text" name="fullname" placeholder="Full Name" required>
                        <input type="text" name="username" placeholder="Username" required>
                        <input type="password" name="password" placeholder="Create Password" required>
                        <button type="submit">
                            <i class="fas fa-check"></i>
                            Register Staff Account
                        </button>
                    </form>
                </div>
            `;
        }
    </script>
</body>
</html>
