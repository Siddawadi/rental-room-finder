<?php
// my_rooms.php
session_start();
require_once 'config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// to Check if user is owner
$userStmt = $pdo->prepare("SELECT is_owner, owner_paid FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch();

if (!$user['is_owner'] || !$user['owner_paid']) {
    header('Location: home.php');
    exit;
}

// Handle delete
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $room_id = intval($_GET['delete']);
    // Verify ownership before deleting
    $verifyStmt = $pdo->prepare("SELECT id FROM rooms WHERE id = ? AND owner_id = ?");
    $verifyStmt->execute([$room_id, $user_id]);
    if ($verifyStmt->fetch()) {
        $deleteStmt = $pdo->prepare("DELETE FROM rooms WHERE id = ? AND owner_id = ?");
        $deleteStmt->execute([$room_id, $user_id]);
        $_SESSION['success'] = "Room deleted successfully!";
        header('Location: my_rooms.php?deleted=1');
        exit;
    }
}

$stmt = $pdo->prepare("SELECT * FROM rooms WHERE owner_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$rooms = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Rooms</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 20px; }
        .header h1 { color: #2c3e50; }
        .header-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-left: 4px solid #28a745; }
        .card .room-number { font-size: 18px; font-weight: bold; color: #2c3e50; }
        .card .price { font-size: 16px; color: #28a745; font-weight: bold; }
        .card .location { color: #666; }
        .card .status-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-available { background: #d4edda; color: #155724; }
        .status-booked { background: #f8d7da; color: #721c24; }
        .btn { padding: 8px 15px; border-radius: 5px; text-decoration: none; display: inline-block; margin: 5px 5px 5px 0; border: none; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0069d9; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-warning:hover { background: #e0a800; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        .back { margin-top: 20px; display: inline-block; color: #007bff; text-decoration: none; }
        .back:hover { text-decoration: underline; }
        .alert { padding: 12px 20px; border-radius: 5px; margin-bottom: 15px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .empty-state { text-align: center; padding: 40px; color: #666; }
        .empty-state .icon { font-size: 48px; margin-bottom: 10px; }
        
        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 My Rooms</h1>
            <div class="header-actions">
                <a href="add_room_owner.php" class="btn btn-success">➕ Add New Room</a>
                <a href="home.php" class="btn btn-secondary">🏠 Back to Home</a>
            </div>
        </div>
        
        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
            <div class="alert alert-success">✅ Room deleted successfully!</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
            <div class="alert alert-success">✅ Room updated successfully!</div>
        <?php endif; ?>
        
        <div class="grid">
            <?php if ($rooms): ?>
                <?php foreach ($rooms as $room): ?>
                    <div class="card">
                        <div class="room-number">Room #<?= htmlspecialchars($room['room_number']) ?></div>
                        <div class="location">📍 <?= htmlspecialchars($room['location']) ?></div>
                        <div class="price">💰 Rs. <?= number_format($room['price'], 2) ?></div>
                        <p>🛏 <?= $room['bedrooms'] ?> Bed • 🚿 <?= $room['bathrooms'] ?> Bath</p>
                        <p>Status: <span class="status-badge status-<?= $room['status'] ?>"><?= ucfirst($room['status']) ?></span></p>
                        <div style="margin-top: 10px;">
                            <a href="room_detail.php?id=<?= $room['id'] ?>" class="btn btn-primary">👁 View</a>
                            <a href="edit_room_owner.php?id=<?= $room['id'] ?>" class="btn btn-warning">✏️ Edit</a>
                            <a href="my_rooms.php?delete=<?= $room['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this room? This action cannot be undone.')">🗑 Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state" style="grid-column: 1/-1;">
                    <div class="icon">🏠</div>
                    <h3>No Rooms Listed</h3>
                    <p>You haven't listed any rooms yet.</p>
                    <a href="add_room_owner.php" class="btn btn-success" style="margin-top: 10px;">➕ Add Your First Room</a>
                </div>
            <?php endif; ?>
        </div>
        
        <a href="home.php" class="back">← Back to Home</a>
    </div>
</body>
</html>