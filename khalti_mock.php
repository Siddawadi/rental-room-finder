<?php
session_start();
require_once 'config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$pidx = $_GET['pidx'] ?? '';
$room_id = intval($_GET['room_id'] ?? 0);

if (!$pidx || !$room_id) die("Invalid mock session.");
?>
<!DOCTYPE html>
<html>
<head><title>Mock Khalti Checkout</title>
<style>
body { font-family:sans-serif; background:#5C2D91; display:flex; align-items:center; justify-content:center; height:100vh; margin:0; }
.box { background:#fff; padding:30px; border-radius:10px; width:320px; text-align:center; }
button { padding:10px 20px; margin:8px; border:none; border-radius:6px; cursor:pointer; color:#fff; }
.success { background:#27ae60; }
.fail { background:#c0392b; }
</style>
</head>
<body>
<div class="box">
    <h3>Mock Khalti Checkout</h3>
    <p>pidx: <?= htmlspecialchars($pidx) ?></p>
    <p>Local stand-in for Khalti's hosted page while sandbox test accounts are locked.</p>
    <form method="POST" action="khalti_callback.php">
        <input type="hidden" name="pidx" value="<?= htmlspecialchars($pidx) ?>">
        <input type="hidden" name="room_id" value="<?= $room_id ?>">
        <button type="submit" name="mock_result" value="success" class="success">Simulate Success</button>
        <button type="submit" name="mock_result" value="failed" class="fail">Simulate Failure</button>
    </form>
</div>
</body>
</html>