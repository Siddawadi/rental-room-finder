<?php
require_once 'config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}


$isAdmin = ($_SESSION['role'] === 'admin');


$stmt = $pdo->query("SELECT * FROM rooms ORDER BY id DESC");
$rooms = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>View Rooms</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        .container { max-width: 1000px; margin: 30px auto; padding: 20px; }
        h2 { text-align: center; }
        .room { background: #fff; padding: 20px; margin-bottom: 20px; border-radius: 10px; box-shadow: 0 6px 18px rgba(0,0,0,0.06); display: flex; gap: 20px; }
        .room img { width: 200px; height: 150px; object-fit: cover; border-radius: 8px; }
        .details { flex: 1; }
        .details p { margin: 5px 0; }
        .btn { display: inline-block; padding: 8px 12px; margin-right: 10px; background: #4facfe; color: #fff; border-radius: 6px; text-decoration: none; font-weight: bold; }
        .btn.delete { background: #dc3545; }
        .btn:hover { opacity: 0.9; }
        a.back { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #007bff; }
    </style>
</head>
<body>
<div class="container">
    <h2>Available Rooms</h2>

    <a class="back" href="dashboard.php">← Back to Dashboard</a>

    <?php if (!$rooms): ?>
        <p>No rooms available.</p>
    <?php else: ?>
        <?php foreach($rooms as $room): ?>
            <div class="room">
                <img src="uploads/<?= htmlspecialchars($room['photo']) ?>" alt="Room Photo">
                <div class="details">
                    <p><strong>Owner Name:</strong> <?= htmlspecialchars($room['owner_name']) ?></p>
                    <p><strong>Room Number:</strong> <?= htmlspecialchars($room['room_number']) ?></p>
                    <p><strong>Capacity:</strong> <?= htmlspecialchars($room['capacity']) ?></p>
                    <p><strong>Location:</strong> <?= htmlspecialchars($room['location']) ?></p>
                    <p><strong>Price:</strong> Rs <?= htmlspecialchars($room['price']) ?></p>
                    <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($room['description'])) ?></p>

                    <?php if($isAdmin): ?>
                        <a class="btn" href="update_room.php?id=<?= $room['id'] ?>">Edit</a>
                        <a class="btn delete" href="delete_room.php?id=<?= $room['id'] ?>" onclick="return confirm('Are you sure you want to delete this room?')">Delete</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
