<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $request_id = intval($_POST['request_id']);

    try {
   
        $stmt = $pdo->prepare("SELECT * FROM visit_requests WHERE id = ? AND user_id = ?");
        $stmt->execute([$request_id, $user_id]);
        $confirmedRequest = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($confirmedRequest) {
          
            $stmt2 = $pdo->prepare("UPDATE visit_requests SET status = 'cancelled' WHERE user_id = ? AND id != ?");
            $stmt2->execute([$user_id, $request_id]);

            
            $stmt3 = $pdo->prepare("UPDATE visit_requests SET status = 'confirmed' WHERE id = ? AND user_id = ?");
            $stmt3->execute([$request_id, $user_id]);

            $stmt4 = $pdo->prepare("INSERT INTO notifications (user_id, message, created_at, is_read) VALUES (?, ?, NOW(), 0)");
            $stmt4->execute([$user_id, "Your visit request for room #{$confirmedRequest['room_id']} has been confirmed!"]);

            $_SESSION['success_msg'] = "Visit request confirmed successfully!";
        } else {
            $_SESSION['error_msg'] = "Request not found or already processed.";
        }

    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Database error: " . $e->getMessage();
    }
}

header("Location: home.php");
exit;
