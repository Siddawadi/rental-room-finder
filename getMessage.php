<?php
$mysqli = new mysqli("localhost", "root", "", "myproject");

if ($mysqli->connect_error) die("Connection failed: " . $mysqli->connect_error);

// get chat_id (user's chat)
$chat_id = isset($_GET['chat_id']) ? intval($_GET['chat_id']) : 0;

$sql = "SELECT message, sender, timestamp FROM chat WHERE chat_id=? ORDER BY id ASC";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $chat_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = array();
while($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

header('Content-Type: application/json');
echo json_encode($messages);
?>
