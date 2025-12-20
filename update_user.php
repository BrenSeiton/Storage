<?php
include 'database.php';
session_start();

if($_SESSION['role'] != 'Admin'){
    echo "Access Denied!";
    exit();
}

$id = $_GET['id'] ?? null;

if(!$id){
    echo "No user selected!";
    exit();
}

if(isset($_POST['update'])){
    $fullname = $_POST['fullname'];
    $username = $_POST['username'];
    $role = $_POST['role'];

    $stmt = $connection->prepare("UPDATE users SET full_name=?, username=?, role=? WHERE user_id=?");
    $stmt->bind_param("sssi", $fullname, $username, $role, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: users.php");
    exit();
}

$stmt = $connection->prepare("SELECT full_name, username, role FROM users WHERE user_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($fullname, $username, $role);
$stmt->fetch();
$stmt->close();
?>

<h2>Edit User</h2>
<form method="POST">
    Full Name: <input type="text" name="fullname" value="<?php echo $fullname; ?>"><br>
    Username: <input type="text" name="username" value="<?php echo $username; ?>"><br>
    Role: 
    <select name="role">
        <option value="Admin" <?php if($role=="Admin") echo "selected"; ?>>Admin</option>
        <option value="Staff" <?php if($role=="Staff") echo "selected"; ?>>Staff</option>
        <option value="Cashier" <?php if($role=="Cashier") echo "selected"; ?>>Cashier</option>
    </select><br>
    <button type="submit" name="update">Update</button>
</form>
