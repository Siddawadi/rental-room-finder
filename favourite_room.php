<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Not logged in']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;

if ($room_id <= 0) {
    echo json_encode(['status' => 'error', 'msg' => 'Invalid room ID']);
    exit;
}


$stmt = $pdo->prepare("SELECT id FROM favourites WHERE user_id=? AND room_id=?");
$stmt->execute([$user_id, $room_id]);
$fav = $stmt->fetch(PDO::FETCH_ASSOC);

if ($fav) {
    
    $stmt = $pdo->prepare("DELETE FROM favourites WHERE id=?");
    $stmt->execute([$fav['id']]);
    echo json_encode(['status' => 'removed']);
} else {
    
    $stmt = $pdo->prepare("INSERT INTO favourites (user_id, room_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $room_id]);
    echo json_encode(['status' => 'added']);
}
