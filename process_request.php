<?php
session_start();
require_once 'config.php';

if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if ($id && in_array($action, ['accept', 'reject'])) {
    $status = $action === 'accept' ? 'accepted' : 'rejected';

   
    $stmt = $pdo->prepare("SELECT user_id, room_id FROM visit_requests WHERE id = ?");
    $stmt->execute([$id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($request) {
        $user_id = $request['user_id'];

       
        $room = $pdo->prepare("SELECT room_number FROM rooms WHERE id = ?");
        $room->execute([$request['room_id']]);
        $room = $room->fetch(PDO::FETCH_ASSOC);

        $room_number = $room['room_number'] ?? 'Unknown';

       
        $update = $pdo->prepare("UPDATE visit_requests SET status = ? WHERE id = ?");
        $update->execute([$status, $id]);

      
        $message = $status === 'accepted' 
                    ? "Your visit request for Room $room_number has been accepted!" 
                    : "Your visit request for Room $room_number has been rejected!";
        $notify = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $notify->execute([$user_id, $message]);
    }
}

header('Location: dashboard.php');
exit;
?>
