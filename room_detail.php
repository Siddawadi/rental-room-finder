<?php
session_start();
require_once 'config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$room_id = intval($_GET['id'] ?? 0);
if (!$room_id) {
    header('Location: home.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];


$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch();
if (!$room) die("Room not found.");


$userStmt = $pdo->prepare("SELECT is_paid FROM users WHERE id=?");
$userStmt->execute([$current_user_id]);
$user = $userStmt->fetch();
$is_paid = $user['is_paid'] ?? 0;

$payment_status = $_GET['payment'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Room #<?= htmlspecialchars($room['room_number']) ?> Details</title>
<style>
body { font-family: 'Inter', sans-serif; background:#f5f5f5; margin:0; }
.container { max-width:900px; margin:30px auto; background:#fff; padding:25px; border-radius:10px; box-shadow:0 5px 15px rgba(0,0,0,0.1);}
h2 { color:#2c3e50; margin-bottom:15px; }
p { color:#555; margin-bottom:10px; }
.price { color:#4facfe; font-weight:600; font-size:1.2em; margin-bottom:15px; }
img { max-width:100%; border-radius:8px; margin-bottom:20px; }
a.button, button.button { display:inline-block; padding:10px 20px; background:#4facfe; color:#fff; border-radius:6px; text-decoration:none; margin-top:10px; border:none; cursor:pointer; }
a.button:hover, button.button:hover { background:#3b99e0; }
.section { margin-bottom:20px; }
.section h3 { border-bottom:1px solid #ddd; padding-bottom:5px; margin-bottom:10px; color:#2c3e50; }
.details-table { width:100%; border-collapse: collapse; }
.details-table th, .details-table td { text-align:left; padding:8px; border-bottom:1px solid #ddd; }
ul.facilities { list-style: none; padding:0; }
ul.facilities li { background:#f0f8ff; padding:8px 12px; margin-bottom:5px; border-radius:5px; display:inline-block; margin-right:10px; }
.map-container iframe { width:100%; height:400px; border:0; border-radius:10px; }
.locked { filter: blur(4px); pointer-events: none; }
.locked-msg { background:#ffebcc; padding:10px; border-radius:6px; color:#8a6d3b; margin-top:10px; }
</style>
</head>
<body>

<div class="container">
    <h2>Room #: <?= htmlspecialchars($room['room_number']) ?></h2>
    <img src="<?= isset($room['photo']) && $room['photo'] && file_exists('uploads/'.$room['photo']) ? 'uploads/'.htmlspecialchars($room['photo']) : 'https://via.placeholder.com/800x400?text=No+Photo' ?>" alt="Room Photo">
    <p class="price">₹<?= htmlspecialchars($room['price']) ?> / Month</p>

   
    <div class="section">
        <h3>Facilities</h3>
        <ul class="facilities">
            <li>WiFi: <?= htmlspecialchars($room['wifi'] ?? 'Not Available') ?></li>
            <li>Parking: <?= htmlspecialchars($room['parking'] ?? 'Not Available') ?></li>
            <li>Water Supply: <?= htmlspecialchars($room['water'] ?? 'Not Available') ?></li>
        </ul>
    </div>

    <div class="section">
        <h3>Property Details</h3>
        <table class="details-table">
            <tr><th>Bedrooms</th><td><?= htmlspecialchars($room['bedrooms'] ?? '') ?></td></tr>
            <tr><th>Bathrooms</th><td><?= htmlspecialchars($room['bathrooms'] ?? '') ?></td></tr>
            <tr><th>Kitchen</th><td><?= htmlspecialchars($room['kitchen'] ?? '') ?></td></tr>
            <tr><th>Living Room</th><td><?= htmlspecialchars($room['living_room'] ?? '') ?></td></tr>
            <tr><th>Floor</th><td><?= htmlspecialchars($room['floor'] ?? '') ?></td></tr>
            <tr><th>House Type</th><td><?= htmlspecialchars($room['house_type'] ?? '') ?></td></tr>
            <tr><th>Property ID</th><td><?= htmlspecialchars($room['id']) ?></td></tr>
        </table>
    </div>


    <div class="section">
        <h3>Description</h3>
        <p><?= nl2br(htmlspecialchars($room['description'] ?? '')) ?></p>
    </div>

    <div class="section">
        <h3>Location</h3>
        <?php if($is_paid): ?>
            <?php if(!empty($room['google_maps_url'])): ?>
                <iframe src="<?= htmlspecialchars($room['google_maps_url']) ?>&output=embed" allowfullscreen="" loading="lazy"></iframe>
            <?php elseif(!empty($room['latitude']) && !empty($room['longitude'])): ?>
                <iframe src="https://www.google.com/maps?q=<?= htmlspecialchars($room['latitude']) ?>,<?= htmlspecialchars($room['longitude']) ?>&hl=es;z=15&output=embed"></iframe>
            <?php else: ?>
                <p>No location available.</p>
            <?php endif; ?>
        <?php else: ?>
            <div class="map-container locked">
                <iframe src="https://www.google.com/maps?q=0,0&hl=es;z=2&output=embed"></iframe>
            </div>
            <div class="locked-msg">Location is locked. Pay to unlock.</div>
        <?php endif; ?>
    </div>

    
    <div class="section">
        <h3>Contact Owner</h3>
        <?php if($is_paid): ?>
            <p><strong>Name:</strong> <?= htmlspecialchars($room['owner_name'] ?? '') ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($room['owner_phone'] ?? '') ?></p>
            <?php if($payment_status === 'success'): ?>
                <div style="color:green;">Payment successful! Owner details and location unlocked.</div>
            <?php endif; ?>
        <?php else: ?>
            <p>Owner details are locked for unpaid users.</p>
            <?php if($payment_status === 'failed'): ?>
                <div style="color:red;">Payment failed or was cancelled. Please try again.</div>
            <?php endif; ?>
            <a href="khalti_initiate.php?room_id=<?= $room_id ?>" class="button">Pay Rs <?= UNLOCK_FEE_NPR ?> to Unlock Owner & Location (Khalti)</a>
        <?php endif; ?>
    </div>

    <a href="home.php" class="button">Back to Home</a>
</div>

</body>
</html>