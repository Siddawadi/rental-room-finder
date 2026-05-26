<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['status'=>'error','msg'=>'Login required']);
    exit;
}

if (!isset($_POST['request_id'])) {
    echo json_encode(['status'=>'error','msg'=>'Request ID missing']);
    exit;
}

$request_id = intval($_POST['request_id']);

// Get request details
$stmt = $pdo->prepare("SELECT * FROM visit_requests WHERE id=? AND user_id=?");
$stmt->execute([$request_id, $user_id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    echo json_encode(['status'=>'error','msg'=>'Request not found']);
    exit;
}

// Cancel the request
$update = $pdo->prepare("UPDATE visit_requests SET status='cancelled' WHERE id=?");
$update->execute([$request_id]);

// Optionally, mark room as available if it was booked
$updateRoom = $pdo->prepare("UPDATE rooms SET status='available' WHERE id=?");
$updateRoom->execute([$request['room_id']]);

echo json_encode([
    'status'=>'success',
    'msg'=>'Request cancelled',
    'is_paid' => $_SESSION['is_paid'] ?? 0
]);