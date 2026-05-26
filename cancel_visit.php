<?php
session_start();
require_once 'config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if (isset($_POST['cancel_visit'])) {
    $user_id = $_SESSION['user_id'];
    $visit_id = $_POST['visit_id'];


    $check = $pdo->prepare("SELECT * FROM visit_requests WHERE id=? AND user_id=?");
    $check->execute([$visit_id, $user_id]);
    $visit = $check->fetch();

    if ($visit) {
        
        $stmt = $pdo->prepare("DELETE FROM visit_requests WHERE id=?");
        if ($stmt->execute([$visit_id])) {
            echo "<script>alert('Visit request canceled successfully.');window.location='home.php';</script>";
        } else {
            echo "<script>alert('Failed to cancel visit request.');window.location='home.php';</script>";
        }

        
    } else {
        echo "<script>alert('Visit request not found.');window.location='home.php';</script>";
    }
} else {
    header('Location: home.php');
    exit;
}
?>
