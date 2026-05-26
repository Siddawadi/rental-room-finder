<?php
session_start();
require_once 'config.php';

// Only admin can access
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Handle cancel request
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'cancel') {
    $request_id = intval($_GET['id']);
    $stmt = $pdo->prepare("UPDATE visit_requests SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$request_id]);

    // Optionally, mark room as available
    $stmtRoom = $pdo->prepare("UPDATE rooms r 
                               JOIN visit_requests vr ON r.id = vr.room_id 
                               SET r.status = 'available' 
                               WHERE vr.id = ?");
    $stmtRoom->execute([$request_id]);

    header("Location: dashboard.php");
    exit;
}

// Logged-in admin info
$name = htmlspecialchars($_SESSION['name'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
$role = $_SESSION['role'] ?? 'admin';

// Fetch stats
$totalRooms = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalRequests = $pdo->query("SELECT COUNT(*) FROM visit_requests")->fetchColumn();

// Fetch all visit requests
$requests = $pdo->query("
    SELECT vr.*, u.name AS username, r.room_number
    FROM visit_requests vr
    JOIN users u ON vr.user_id = u.id
    JOIN rooms r ON vr.room_id = r.id
    ORDER BY vr.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard | Room Finder Nepal</title>
<!-- CSS remains exactly as your original code -->
<style>
:root {
  --primary:#4facfe;
  --bg:#f5f7fb;
  --sidebar:#1e1f26;
  --card-bg:#fff;
  --text:#333;
  --cancel-color: #e74c3c;
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
.sidebar h2 { text-align:center; margin-bottom:30px; }
.sidebar a { color:#ccc; text-decoration:none; display:block; padding:12px 25px; margin:5px 0; transition:0.3s; }
.sidebar a:hover, .sidebar a.active { background:var(--primary); color:#fff; border-radius:8px; }
.main { margin-left:250px; padding:30px; flex:1; }
.header { display:flex; justify-content:space-between; align-items:center; background:var(--card-bg); padding:15px 25px; border-radius:12px; box-shadow:0 5px 15px rgba(0,0,0,0.05); margin-bottom:20px; }
.header h1 { font-size:1.5rem; }
.header .user { font-weight:600; }
.cards { display:flex; flex-wrap:wrap; gap:20px; margin-top:30px; }
.card { flex:1 1 250px; background:var(--card-bg); border-radius:12px; padding:20px; box-shadow:0 5px 15px rgba(0,0,0,0.05); text-align:center; transition:0.3s; }
.card:hover { transform:translateY(-5px); }
.card h2 { margin-bottom:10px; color:var(--primary); }
.table-container { background:var(--card-bg); margin-top:40px; padding:20px; border-radius:12px; box-shadow:0 5px 15px rgba(0,0,0,0.05); }
.table-container h2 { margin-bottom:15px; }
table { width:100%; border-collapse:collapse; }
th, td { padding:12px; border-bottom:1px solid #ddd; text-align:left; }
th { background:#f9fafb; }
td a.btn { padding:6px 12px; border-radius:6px; color:#fff; text-decoration:none; margin-right:5px; }
.btn-cancel { background:var(--cancel-color); }
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <h2>Admin Panel</h2>
  <a href="dashboard.php" class="active">🏠 Dashboard</a>
  <a href="rooms.php">🏘️ Manage Rooms</a>
  <a href="manage_users.php">👥 Manage Users</a>
  <a href="logout.php">🚪 Logout</a>
</div>

<!-- Main Content -->
<div class="main">
  <div class="header">
    <h1>Welcome, <?= $name ?> 👋</h1>
    <div class="user"><?= ucfirst($role) ?></div>
  </div>

  <!-- Stats Cards -->
  <div class="cards">
    <div class="card">
      <h2><?= $totalRooms ?></h2>
      <p>Total Rooms</p>
    </div>
    <div class="card">
      <h2><?= $totalUsers ?></h2>
      <p>Total Users</p>
    </div>
    <div class="card">
      <h2><?= $totalRequests ?></h2>
      <p>Visit Requests</p>
    </div>
  </div>

  <!-- Visit Requests Table -->
  <div class="table-container">
    <h2>Visit Requests</h2>
    <?php if ($requests): ?>
    <table>
      <tr>
        <th>ID</th>
        <th>User</th>
        <th>Room</th>
        <th>Visit Date</th>
        <th>Status / Action</th>
      </tr>
      <?php foreach($requests as $req): ?>
      <tr>
        <td><?= $req['id'] ?></td>
        <td><?= htmlspecialchars($req['username']) ?></td>
        <td><?= htmlspecialchars($req['room_number']) ?></td>
        <td><?= htmlspecialchars($req['visit_date']) ?></td>
        <td>
          <?php if($req['status'] === 'confirmed'): ?>
            <a href="dashboard.php?action=cancel&id=<?= $req['id'] ?>" 
               onclick="return confirm('Cancel this visit request?');" 
               class="btn btn-cancel">Cancel</a>
          <?php else: ?>
            <?= ucfirst($req['status']) ?>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php else: ?>
      <p style="text-align:center;">No visit requests yet.</p>
    <?php endif; ?>
  </div>
</div>

</body>
</html>