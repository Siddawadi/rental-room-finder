<?php
session_start();
require_once 'config.php';


if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$message = '';
$error_msg = '';
if (isset($_GET['delete_user'])) {
    $del_id = intval($_GET['delete_user']);
    try {
   
        $pdo->prepare("DELETE FROM favourites WHERE user_id = ?")->execute([$del_id]);
        
        $pdo->prepare("DELETE FROM visit_requests WHERE user_id = ?")->execute([$del_id]);
       
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$del_id]);
        $message = "User deleted successfully!";
    } catch (PDOException $e) {
        $error_msg = "Error deleting user: " . $e->getMessage();
    }
}


$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);


$name = htmlspecialchars($_SESSION['name'] ?? 'User', ENT_QUOTES, 'UTF-8');
$role = $_SESSION['role'] ?? 'user';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users | Admin</title>
<style>
:root {
  --primary:#4facfe;
  --bg:#f5f7fb;
  --sidebar:#1e1f26;
  --card-bg:#fff;
  --text:#333;
}
body {
  margin:0;
  font-family:'Inter',sans-serif;
  background:var(--bg);
  color:var(--text);
  display:flex;
}
.sidebar {
  width:250px;
  background:var(--sidebar);
  color:#fff;
  height:100vh;
  position:fixed;
  display:flex;
  flex-direction:column;
  padding:20px 0;
}
.sidebar h2 {
  text-align:center;
  margin-bottom:30px;
}
.sidebar a {
  color:#ccc;
  text-decoration:none;
  display:block;
  padding:12px 25px;
  margin:5px 0;
  transition:0.3s;
}
.sidebar a:hover, .sidebar a.active {
  background:var(--primary);
  color:#fff;
  border-radius:8px;
}
.main {
  margin-left:250px;
  padding:30px;
  flex:1;
}
.header {
  display:flex;
  justify-content:space-between;
  align-items:center;
  background:var(--card-bg);
  padding:15px 25px;
  border-radius:12px;
  box-shadow:0 5px 15px rgba(0,0,0,0.05);
  margin-bottom:20px;
}
.header h1 { font-size:1.5rem; }
.header .user { font-weight:600; }
.container {
  background:var(--card-bg);
  padding:20px;
  border-radius:12px;
  box-shadow:0 5px 15px rgba(0,0,0,0.05);
}
table { width:100%; border-collapse:collapse; margin-top:15px; }
th, td { padding:12px; border-bottom:1px solid #ddd; text-align:left; }
th { background:#f9fafb; }
a.btn { padding:6px 12px; border-radius:6px; color:#fff; text-decoration:none; margin-right:5px; }
.btn-edit { background:#4facfe; }
.btn-delete { background:#e74c3c; }
.add-btn { background:var(--primary); color:#fff; padding:10px 20px; border-radius:6px; text-decoration:none; display:inline-block; margin-bottom:15px; }
.msg { color:green; margin-bottom:15px; }
.error { color:red; margin-bottom:15px; }
</style>
</head>
<body>


<div class="sidebar">
  <h2>Admin Panel</h2>
  <a href="dashboard.php">🏠 Dashboard</a>
  <a href="rooms.php">🏘️ Manage Rooms</a>
  <a href="manage_users.php" class="active">👥 Manage Users</a>
  <a href="logout.php">🚪 Logout</a>
</div>


<div class="main">
  <div class="header">
    <h1>Welcome, <?= $name ?> 👋</h1>
    <div class="user"><?= ucfirst($role) ?></div>
  </div>

  <div class="container">
    <h2>Manage Users</h2>

    <?php if($message): ?>
      <div class="msg"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if($error_msg): ?>
      <div class="error"><?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>

    <a href="add_user.php" class="add-btn">+ Add New User</a>
    
    <?php if($users): ?>
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
          <a href="manage_users.php?delete_user=<?= $user['id'] ?>" onclick="return confirm('Are you sure you want to delete this user?');" class="btn btn-delete">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php else: ?>
      <p style="text-align:center;">No users found.</p>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
