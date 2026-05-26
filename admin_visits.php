<?php
require_once 'config.php';
session_start();

// admin lai matrai allow garna 
if ($_SESSION['role'] !== 'admin') {
    header('Location: home.php');
    exit;
}

// visit request fetch garna
try {
    $stmt = $pdo->query("
        SELECT v.*, u.username, r.room_number
        FROM visit_requests v
        JOIN users u ON v.user_id = u.id
        JOIN rooms r ON v.room_id = r.id
        ORDER BY v.created_at DESC
    ");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $requests = [];
    echo "<p style='color:red;'>Error fetching visit requests: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - Visit Requests</title>
<style>
body { font-family: Arial, sans-serif; background: #f5f5f5; color: #333; padding: 30px; }
h2 { text-align: center; margin-bottom: 20px; }
table { width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #4facfe; color: #fff; }
tr:hover { background: #f1f1f1; }
</style>
</head>
<body>

<h2>All Visit Requests</h2>

<?php if ($requests): ?>
<table>
    <tr>
        <th>ID</th>
        <th>User</th>
        <th>Room</th>
        <th>Date Requested</th>
        <th>Status</th>
    </tr>
    <?php foreach($requests as $req): ?>
    <tr>
        <td><?= $req['id'] ?></td>
        <td><?= htmlspecialchars($req['username']) ?></td>
        <td><?= htmlspecialchars($req['room_number']) ?></td>
        <td><?= htmlspecialchars($req['visit_date']) ?></td>
        <td><?= htmlspecialchars($req['status']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php else: ?>
    <p style="text-align:center;">No visit requests found.</p>
<?php endif; ?>

</body>
</html>
