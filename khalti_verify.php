<?php
session_start();
require_once 'config.php';


if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];


$data = json_decode(file_get_contents('php://input'), true);

$token = $data['token'] ?? '';
$amount = $data['amount'] ?? 0;


$khalti_secret_key = 'YOUR_KHALTI_SECRET_KEY';


$verify_url = "https://khalti.com/api/v2/payment/verify/";
$ch = curl_init($verify_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'token' => $token,
    'amount' => $amount
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Key $khalti_secret_key"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);


if (!empty($result['idx'])) {

    $stmt = $pdo->prepare("UPDATE users SET is_paid = 1 WHERE id = ?");
    $stmt->execute([$user_id]);

    echo json_encode(['success' => true, 'message' => 'Payment verified successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Payment verification failed']);
}
?>
