<?php
session_start();
require_once 'config.php';

$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['username'] ?? 'Guest';

$rooms = [];
try {
    $stmt = $pdo->query("SELECT * FROM rooms ORDER BY id DESC"); 
    $rooms = $stmt->fetchAll(); 
} catch (PDOException $e) {
    $rooms = [];
    $error_message = "Rooms table not found or empty."; 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All Rooms - Room Finder Nepal</title>
<style>
body { font-family: 'Inter', sans-serif; background:#fafafa; color:#333; margin:0; padding:0;}
.container { max-width:1200px; margin:50px auto; padding:0 20px;}
h1 { text-align:center; margin-bottom:30px; }
.grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:30px;}
.card { background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 6px 18px rgba(0,0,0,0.1);}
.card img { width:100%; height:200px; object-fit:cover;}
.card-content { padding:20px;}
.card-content h3 { font-size:1.2rem; color:#2c3e50; margin-bottom:10px;}
.price { font-weight:600; color:#4facfe; margin-bottom:12px;}
.card-content a { display:inline-block; margin-top:10px; padding:8px 15px; border-radius:6px; background:#4facfe; color:#fff; text-decoration:none;}
</style>
</head>
<body>

<div class="container">
    <h1>All Rooms Available</h1>
    <div class="grid">
        <?php if ($rooms): ?>
            <?php foreach ($rooms as $room): ?>
            <div class="card">
                <img src="<?= ($room['photo'] && file_exists('uploads/'.$room['photo'])) ? 'uploads/'.htmlspecialchars($room['photo']) : 'https://via.placeholder.com/400x200?text=No+Photo' ?>" alt="Room Photo">
                <div class="card-content">
                    <h3>Room #: <?= htmlspecialchars($room['room_number']) ?></h3>
                    <p><strong>Owner:</strong> <?= htmlspecialchars($room['owner_name']) ?></p>
                    <p><strong>Location:</strong> <?= htmlspecialchars($room['location']) ?></p>
                    <p class="price">₹<?= htmlspecialchars($room['price']) ?></p>
                    <a href="room_detail.php?id=<?= $room['id'] ?>">View Details</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="grid-column:1/-1; text-align:center; color:#777;">No rooms found.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
