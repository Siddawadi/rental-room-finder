<?php
session_start();
require_once 'config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];


if (isset($_POST['cancel_visit'])) {
    $visit_id = intval($_POST['visit_id']);
    $stmt = $pdo->prepare("UPDATE visit_requests SET status='cancelled' WHERE id=? AND user_id=?");
    $stmt->execute([$visit_id, $user_id]);
}


$stmt = $pdo->prepare("
    SELECT v.id AS visit_id, v.status, r.room_number, r.location, r.price, r.photo
    FROM visit_requests v
    JOIN rooms r ON v.room_id = r.id
    WHERE v.user_id = ?
    ORDER BY v.created_at DESC
");
$stmt->execute([$user_id]);
$visits = $stmt->fetchAll();


$favStmt = $pdo->prepare("
    SELECT r.id, r.room_number, r.location, r.price, r.photo
    FROM favourites f
    JOIN rooms r ON f.room_id = r.id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
");
$favStmt->execute([$user_id]);
$favRooms = $favStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Visits & Favourites</title>
<style>
.card { border:1px solid #ddd; padding:15px; margin:10px; border-radius:8px; display:flex; align-items:center; }
.card img { width:120px; height:80px; object-fit:cover; margin-right:15px; }
.button { padding:6px 12px; border:none; border-radius:6px; cursor:pointer; background:#f44336; color:#fff; }
</style>
</head>
<body>
<h2>My Visit Requests</h2>
<?php if($visits): ?>
    <?php foreach($visits as $v): ?>
        <div class="card">
            <img src="<?= $v['photo'] ? 'uploads/'.$v['photo'] : 'https://via.placeholder.com/120x80' ?>" alt="">
            <div>
                <strong>Room #: <?= htmlspecialchars($v['room_number']) ?></strong><br>
                <strong>Location:</strong> <?= htmlspecialchars($v['location']) ?><br>
                <strong>Price:</strong> ₹<?= htmlspecialchars($v['price']) ?><br>
                <strong>Status:</strong> <?= htmlspecialchars($v['status']) ?><br>
                <?php if($v['status']=='pending'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="visit_id" value="<?= $v['visit_id'] ?>">
                        <button type="submit" name="cancel_visit" class="button">Cancel Visit</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>You have no visit requests.</p>
<?php endif; ?>

<h2>My Favourite Rooms</h2>
<?php if($favRooms): ?>
    <?php foreach($favRooms as $r): ?>
        <div class="card">
            <img src="<?= $r['photo'] ? 'uploads/'.$r['photo'] : 'https://via.placeholder.com/120x80' ?>" alt="">
            <div>
                <strong>Room #: <?= htmlspecialchars($r['room_number']) ?></strong><br>
                <strong>Location:</strong> <?= htmlspecialchars($r['location']) ?><br>
                <strong>Price:</strong> ₹<?= htmlspecialchars($r['price']) ?><br>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>You have no favourite rooms.</p>
<?php endif; ?>
</body>
</html>
