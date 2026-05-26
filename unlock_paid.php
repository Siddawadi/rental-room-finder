<?php
session_start();
require_once 'config.php';

$user_id = $_SESSION['user_id'] ?? null;
$room_id = $_POST['room_id'] ?? null;

if (!$user_id || !$room_id) {
    header('Location: home.php');
    exit;
}


$update = $pdo->prepare("UPDATE users SET is_paid = 1 WHERE id = ?");
$update->execute([$user_id]);

$_SESSION['is_paid'] = 1;


$insert = $pdo->prepare("INSERT INTO payments (user_id, room_id, amount, created_at) VALUES (?, ?, ?, NOW())");
$insert->execute([$user_id, $room_id, 1]);


header("Location: home.php");
exit;
