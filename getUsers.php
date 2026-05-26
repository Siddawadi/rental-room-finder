<?php
$mysqli = new mysqli("localhost","root","","myproject");
$result = $mysqli->query("SELECT user_id, username FROM users ORDER BY user_id ASC");

$users = array();
while($row=$result->fetch_assoc()){
    $users[]=$row;
}
header('Content-Type: application/json');
echo json_encode($users);
?>
