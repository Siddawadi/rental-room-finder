<?php
session_start();
require_once 'config.php';


if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}


$message = '';


if (isset($_GET['delete_room'])) {
    $room_id = intval($_GET['delete_room']);
    try {
        
        $pdo->prepare("DELETE FROM favourites WHERE room_id = ?")->execute([$room_id]);
        $pdo->prepare("DELETE FROM visit_requests WHERE room_id = ?")->execute([$room_id]);

       
        $pdo->prepare("DELETE FROM rooms WHERE id = ?")->execute([$room_id]);
        $message = "Room deleted successfully!";
    } catch (PDOException $e) {
        $message = "Error deleting room: " . $e->getMessage();
    }
}


$stmt = $pdo->query("SELECT * FROM rooms ORDER BY id DESC");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Manage Rooms | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body { font-family:Arial, sans-serif; background:#f4f6f9; margin:0; padding:0; }
.container { max-width:1000px; margin:30px auto; padding:20px; background:#fff; border-radius:12px; box-shadow:0 5px 20px rgba(0,0,0,0.05); }
h2 { margin-bottom:20px; }
table { width:100%; border-collapse: collapse; }
th, td { padding:12px; border-bottom:1px solid #ddd; text-align:left; }
th { background:#f9fafb; }
a.btn { padding:6px 12px; border-radius:6px; color:#fff; text-decoration:none; margin-right:5px; }
.btn-edit { background:#4facfe; }
.btn-delete { background:#e74c3c; }
.add-btn { background:#4facfe; color:#fff; padding:10px 20px; border-radius:6px; text-decoration:none; display:inline-block; margin-bottom:20px; }
.back-btn { background:#555; color:#fff; padding:10px 20px; border-radius:6px; text-decoration:none; display:inline-block; margin-bottom:15px; }
.msg { color:green; margin-bottom:15px; }
</style>
</head>
<body>

<div class="container">
    <h2>Manage Rooms</h2>

    
    <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>

    <?php if($message): ?>
        <div class="msg"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

   
    <a href="add_room.php" class="add-btn">+ Add New Room</a>

    <?php if($rooms): ?>
    <table>
        <tr>
            <th>ID</th>
            <th>Room Number</th>
            <th>Owner</th>
            <th>Location</th>
            <th>Price</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php foreach($rooms as $room): ?>
        <tr>
            <td><?= $room['id'] ?></td>
            <td><?= htmlspecialchars($room['room_number']) ?></td>
            <td><?= htmlspecialchars($room['owner_name']) ?></td>
            <td><?= htmlspecialchars($room['location']) ?></td>
            <td>₹<?= htmlspecialchars($room['price']) ?></td>
            <td><?= htmlspecialchars($room['status']) ?></td>
            <td>
                <a href="update_room.php?id=<?= $room['id'] ?>" class="btn btn-edit">Edit</a>
                <a href="rooms.php?delete_room=<?= $room['id'] ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this room?');">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
        <p>No rooms found.</p>
    <?php endif; ?>
</div>

</body>
</html>
