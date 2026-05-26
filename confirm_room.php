<?php
session_start();
require_once 'config.php';

$user_id = $_SESSION['user_id'] ?? null;
$request_id = $_POST['request_id'] ?? null;

if (!$user_id || !$request_id) {
    header('Location: home.php');
    exit;
}

// Check if user already confirmed a room
$stmt = $pdo->prepare("SELECT COUNT(*) FROM visit_requests WHERE user_id=? AND LOWER(status)='confirmed'");
$stmt->execute([$user_id]);
if ($stmt->fetchColumn() > 0) {
    $_SESSION['error'] = "You already confirmed a room.";
    header('Location: home.php');
    exit;
}


$stmt = $pdo->prepare("SELECT room_id FROM visit_requests WHERE id=?");
$stmt->execute([$request_id]);
$room_id = $stmt->fetchColumn();


$stmt = $pdo->prepare("SELECT COUNT(*) FROM visit_requests WHERE room_id=? AND LOWER(status)='confirmed'");
$stmt->execute([$room_id]);
if ($stmt->fetchColumn() > 0) {
    $_SESSION['error'] = "This room is already confirmed by another user.";
    header('Location: home.php');
    exit;
}


$update = $pdo->prepare("UPDATE visit_requests SET status='confirmed' WHERE id=?");
$update->execute([$request_id]);


$update = $pdo->prepare("UPDATE visit_requests SET status='cancelled' WHERE room_id=? AND id<>?");
$update->execute([$room_id, $request_id]);

$_SESSION['success'] = "Room confirmed successfully!";
header('Location: home.php');
exit;
