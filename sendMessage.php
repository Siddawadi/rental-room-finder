<?php
$mysqli = new mysqli("localhost", "root", "", "myproject");
if ($mysqli->connect_error) die("Connection failed: " . $mysqli->connect_error);

$chat_id = isset($_POST['chat_id']) ? intval($_POST['chat_id']) : 0;
$message = isset($_POST['message']) ? $_POST['message'] : '';
$sender = isset($_POST['sender']) ? $_POST['sender'] : 'user';

if (!empty($message)) {
    $stmt = $mysqli->prepare("INSERT INTO chat (chat_id, message, sender) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $chat_id, $message, $sender);
    $stmt->execute();
}
?>
