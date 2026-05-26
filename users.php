<?php
session_start();
require_once 'config.php';

// Only admin can access
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}


$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);


if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$delete_id]);
    header("Location: users.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users | Admin</title>
<style>
body { font-family: 'Inter', sans-serif; background:#f5f7fb; margin:0; padding:20px; }
h1 { margin-bottom:20px; }
table { width:100%; border-collapse: collapse; background:#fff; border-radius:10px; overflow:hidden; }
th, td { padding:12px; border-bottom:1px solid #ddd; text-align:left; }
th { background:#f0f0f0; }
a.btn { padding:6px 12px; border-radius:6px; color:#fff; text-decoration:none; margin-right:5px; }
.btn-edit { background:#4facfe; }
.btn-delete { background:#e74c3c; }
</style>
</head>
<body>

<h1>Manage Users</h1>
<table>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Action</th>
    </tr>
    <?php foreach($users as $user): ?>
    <tr>
        <td><?= $user['id'] ?></td>
        <td><?= htmlspecialchars($user['name']) ?></td>
        <td><?= htmlspecialchars($user['email']) ?></td>
        <td><?= htmlspecialchars($user['role']) ?></td>
        <td>
            <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-edit">Edit</a>
            <a href="users.php?delete=<?= $user['id'] ?>" onclick="return confirm('Are you sure you want to delete this user?');" class="btn btn-delete">Delete</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<a href="dashboard.php" style="display:inline-block;margin-top:20px;">← Back to Dashboard</a>
</body>
</html>
