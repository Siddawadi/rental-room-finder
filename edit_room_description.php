<?php
session_start();
require_once 'config.php';

if ($_SESSION['role'] !== 'admin') {
    die("Access denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = intval($_POST['room_id']);
    $description = trim($_POST['description']);

    
    $stmt = $pdo->prepare("UPDATE rooms SET description = ? WHERE id = ?");
    $stmt->execute([$description, $room_id]);

   
    if(!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['name'] as $key => $name) {
            $tmp_name = $_FILES['images']['tmp_name'][$key];
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $new_name = uniqid().'_'.time().'.'.$ext;
            move_uploaded_file($tmp_name, 'uploads/'.$new_name);

            $stmt = $pdo->prepare("INSERT INTO room_images (room_id, image_path) VALUES (?, ?)");
            $stmt->execute([$room_id, $new_name]);
        }
    }

    header("Location: room_detail.php?id=$room_id");
    exit;
}
?>
