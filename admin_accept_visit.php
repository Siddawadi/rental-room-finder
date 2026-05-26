<?php
session_start();
require_once 'config.php';


if(!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']){
    die("Access denied.");
}

if(isset($_POST['visit_id'])){
    $visit_id = intval($_POST['visit_id']);

   
    $stmt = $pdo->prepare("UPDATE visit_requests SET status='accepted' WHERE id=?");
    if($stmt->execute([$visit_id])){
        echo "Visit request accepted.";
    } else {
        echo "Failed to accept request.";
    }
}
?>
