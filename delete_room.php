<?php
session_start();
require_once 'config.php';

if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

if (empty($_GET['id'])) {
    header('Location: view_rooms.php');
    exit;
}

$room_id = intval($_GET['id']);


$stmt = $pdo->prepare("DELETE FROM favourites WHERE room_id = ?");
$stmt->execute([$room_id]);


$stmt = $pdo->prepare("SELECT photo FROM rooms WHERE id = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch();
if ($room && !empty($room['photo']) && file_exists('uploads/'.$room['photo'])) {
    unlink('uploads/'.$room['photo']);
}


$stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
if ($stmt->execute([$room_id])) {
    header('Location: view_rooms.php');
    exit;
} else {
    die("Failed to delete room.");
}
?>
