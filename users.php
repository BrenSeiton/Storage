<?php
include 'database.php';
session_start();

if($_SESSION['role'] != 'Admin' && !isset($_SESSION['admin_authenticated'])){
    echo "Access Denied! Admin privileges required.";
    exit();
}

if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    $stmt = $connection->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: users.php");
    exit();
}

$result = $connection->query("SELECT * FROM users");
?>

<h2>User List</h2>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Full Name</th>
        <th>Username</th>
        <th>Role</th>
        <th>Actions</th>
    </tr>
    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?php echo $row['user_id']; ?></td>
        <td><?php echo $row['full_name']; ?></td>
        <td><?php echo $row['username']; ?></td>
        <td><?php echo $row['role']; ?></td>
        <td>
            <a href="update_user.php?id=<?php echo $row['user_id']; ?>">Edit</a> |
            <a href="users.php?delete=<?php echo $row['user_id']; ?>">Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
