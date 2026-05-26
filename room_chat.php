<?php
session_start();
require_once 'config.php';


if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES, 'UTF-8');


$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;


$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    die("Room not found.");
}


$pdo->prepare("UPDATE chat SET read_by_user=1 WHERE room_id=? AND user_id=? AND sender='admin'")->execute([$room_id, $user_id]);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $message = trim($_POST['message']);
    if ($message !== '') {
        $stmt = $pdo->prepare("INSERT INTO chat (room_id, user_id, username, message, sender, created_at, read_by_user) VALUES (?, ?, ?, ?, 'user', NOW(), 0)");
        $stmt->execute([$room_id, $user_id, $username, $message]);
        header("Location: room_chat.php?room_id=$room_id");
        exit;
    }
}


$stmt = $pdo->prepare("SELECT * FROM chat WHERE room_id=? AND (user_id=? OR sender='admin') ORDER BY created_at ASC");
$stmt->execute([$room_id, $user_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Room Chat - <?= htmlspecialchars($room['name']) ?></title>
<style>
body { font-family:'Inter',sans-serif; background:#f0f2f5; margin:0; padding:0; }
header { background:#4facfe; color:#fff; padding:15px 30px; display:flex; justify-content:space-between; align-items:center; }
header h1 { font-size:1.5rem; }
nav a { color:#fff; text-decoration:none; margin-left:15px; font-weight:500; }
.container { max-width:800px; margin:30px auto; padding:0 20px; }
.chat-box { background:#fff; border-radius:12px; box-shadow:0 5px 15px rgba(0,0,0,0.1); padding:20px; height:500px; display:flex; flex-direction:column; }
.chat-messages { flex:1; overflow-y:auto; margin-bottom:10px; }
.message.user { background:#e0e0e0; padding:8px 12px; border-radius:10px; margin-bottom:6px; max-width:70%; text-align:left; }
.message.admin { background:#4facfe; color:#fff; padding:8px 12px; border-radius:10px; margin-bottom:6px; max-width:70%; text-align:right; margin-left:auto; }
.message .time { display:block; font-size:0.7rem; color:#888; margin-top:2px; }
.chat-input { display:flex; }
.chat-input input { flex:1; padding:10px; border-radius:8px 0 0 8px; border:1px solid #ccc; }
.chat-input button { padding:10px 15px; border:none; background:#4facfe; color:#fff; border-radius:0 8px 8px 0; cursor:pointer; }
</style>
</head>
<body>

<header>
    <h1>Room: <?= htmlspecialchars($room['name']) ?></h1>
    <nav>
        <a href="home.php">Back to Home</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="container">
    <div class="chat-box">
        <div class="chat-messages" id="chat-messages">
            <?php foreach($messages as $msg): ?>
                <div class="message <?= $msg['sender']=='admin' ? 'admin' : 'user' ?>">
                    <?= ($msg['sender']=='admin' ? 'Admin: ' : '') . htmlspecialchars($msg['message']) ?>
                    <span class="time"><?= $msg['created_at'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <form class="chat-input" method="post">
            <input type="text" name="message" placeholder="Type a message..." autocomplete="off" required>
            <button type="submit">Send</button>
        </form>
    </div>
</div>

<script>

let chatMessages = document.getElementById('chat-messages');
chatMessages.scrollTop = chatMessages.scrollHeight;
</script>

</body>
</html>
