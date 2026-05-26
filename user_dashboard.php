<?php
session_start();
require_once 'config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];


$notifications = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$notifications->execute([$user_id]);
$notifications = $notifications->fetchAll(PDO::FETCH_ASSOC);


$mark_read = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$mark_read->execute([$user_id]);
?>

<h2>Notifications</h2>
<?php if($notifications): ?>
<ul>
    <?php foreach($notifications as $note): ?>
    <li>
        <?= htmlspecialchars($note['message']) ?>
        <small style="color:gray; font-size:0.8rem;">(<?= $note['created_at'] ?>)</small>
    </li>
    <?php endforeach; ?>
</ul>
<?php else: ?>
<p>No notifications.</p>
<?php endif; ?>
