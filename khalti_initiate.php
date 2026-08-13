<?php
// initiate_payment.php
session_start();
require_once 'config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$room_id = intval($_GET['room_id'] ?? 0);
if (!$room_id) {
    header('Location: home.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];

// Check if already paid
$checkStmt = $pdo->prepare("SELECT id FROM payments WHERE user_id=? AND room_id=? AND status='Completed'");
$checkStmt->execute([$current_user_id, $room_id]);
if ($checkStmt->fetch()) {
    $_SESSION['error'] = "You have already unlocked this room.";
    header("Location: room_details.php?id=$room_id");
    exit;
}

// Get user details
$userStmt = $pdo->prepare("SELECT name, email, phone FROM users WHERE id=?");
$userStmt->execute([$current_user_id]);
$user = $userStmt->fetch();

$amount_paisa = UNLOCK_FEE_NPR * 100;
$purchase_order_id = "ROOM{$room_id}_{$current_user_id}_" . time();

// ✅ REAL KHALTI PAYMENT PAYLOAD
$payload = [
    "return_url" => KHALTI_RETURN_URL,
    "website_url" => KHALTI_WEBSITE_URL,
    "amount" => $amount_paisa, // 1000 paisa = Rs. 10
    "purchase_order_id" => $purchase_order_id,
    "purchase_order_name" => "Unlock Room #$room_id",
    "customer_info" => [
        "name" => $user['name'] ?? 'Test User',
        "email" => $user['email'] ?? 'test@example.com',
        "phone" => $user['phone'] ?? '9800000001' // ✅ Use 9800000001 for testing
    ]
];

// ✅ INITIATE PAYMENT WITH REAL KHALTI API
$ch = curl_init(KHALTI_BASE_URL . "epayment/initiate/");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        "Authorization: key " . KHALTI_SECRET_KEY,
        "Content-Type: application/json"
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false // Only for localhost
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Debug: Check if request worked
if ($curl_error) {
    die("CURL Error: " . $curl_error);
}

$result = json_decode($response, true);

// ✅ CHECK IF SUCCESSFUL
if ($http_code === 200 && !empty($result['payment_url'])) {
    // Save payment record
    $stmt = $pdo->prepare("INSERT INTO payments (user_id, room_id, pidx, purchase_order_id, amount, status, created_at) 
                           VALUES (?,?,?,?,?, 'Pending', NOW())");
    $stmt->execute([
        $current_user_id, 
        $room_id, 
        $result['pidx'], 
        $purchase_order_id, 
        $amount_paisa
    ]);

    // ✅ REDIRECT TO KHALTI PAYMENT PAGE
    header('Location: ' . $result['payment_url']);
    exit;
} else {
    // ❌ SHOW ERROR
    echo "<h3>Payment Initiation Failed</h3>";
    echo "<p><strong>HTTP Code:</strong> " . $http_code . "</p>";
    echo "<p><strong>Response:</strong></p>";
    echo "<pre>" . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) . "</pre>";
    
    if (isset($result['detail'])) {
        echo "<p><strong>Error Detail:</strong> " . htmlspecialchars($result['detail']) . "</p>";
    }
    
    error_log("Khalti Initiation Error: " . $response);
    exit;
}
?>