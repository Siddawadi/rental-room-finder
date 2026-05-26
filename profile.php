<?php
session_start();
require_once 'config.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];


$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Profile</title>
    <style>
        body { font-family: Arial; background:#f4f6f9; }
        .container { max-width:500px; margin:60px auto; background:#fff; padding:25px; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.1);}
        h2 { text-align:center; margin-bottom:20px;}
        p { font-size:1.1rem; margin:10px 0;}
        .btn { display:block; width:100%; padding:12px; text-align:center; background:#4facfe; color:#fff; border:none; border-radius:6px; text-decoration:none; font-weight:bold; margin-bottom:10px;}
        .btn:hover { background:#428bca; }
        .home-btn { background:#10b981; }
        .home-btn:hover { background:#0e8c68; }
    </style>
</head>
<body>

<div class="container">
    <h2>My Profile</h2>
    <p><strong>Name:</strong> <?= htmlspecialchars($user['name']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>

    <a href="edit_profile.php" class="btn">Edit Profile</a>
    <a href="home.php" class="btn home-btn">← Back to Home</a>
</div>

</body>
</html>
