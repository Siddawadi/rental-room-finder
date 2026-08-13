<?php
// initiate_owner_payment.php
session_start();
require_once 'config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];

// Get user details
$userStmt = $pdo->prepare("SELECT name, email, phone, is_owner, owner_paid FROM users WHERE id=?");
$userStmt->execute([$current_user_id]);
$user = $userStmt->fetch();

// Check if already owner
if ($user['is_owner'] && $user['owner_paid']) {
    $_SESSION['success'] = "You are already a verified owner!";
    header('Location: home.php?owner=already_completed');
    exit;
}

$amount_paisa = OWNER_FEE_NPR * 100; // Convert to paisa (Rs. 50 = 5000 paisa)
$purchase_order_id = "OWNER_{$current_user_id}_" . time();

// Prepare Khalti payment payload
$payload = [
    "return_url" => KHALTI_RETURN_URL,
    "website_url" => KHALTI_WEBSITE_URL,
    "amount" => $amount_paisa,
    "purchase_order_id" => $purchase_order_id,
    "purchase_order_name" => "Become Property Owner",
    "customer_info" => [
        "name" => $user['name'] ?? 'Test User',
        "email" => $user['email'] ?? 'test@example.com',
        "phone" => $user['phone'] ?? '9800000003' // Use working test number
    ]
];

// Initiate payment with Khalti
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

// Check for CURL error
if ($curl_error) {
    die("CURL Error: " . $curl_error);
}

$result = json_decode($response, true);

// Check if payment initiated successfully
if ($http_code === 200 && !empty($result['payment_url'])) {
    // Save payment record
    $stmt = $pdo->prepare("INSERT INTO payments (user_id, room_id, pidx, purchase_order_id, amount, status, payment_type, created_at) 
                           VALUES (?, 0, ?, ?, ?, 'Pending', 'owner', NOW())");
    $stmt->execute([
        $current_user_id, 
        $result['pidx'], 
        $purchase_order_id, 
        $amount_paisa
    ]);

    // Redirect to Khalti payment page
    header('Location: ' . $result['payment_url']);
    exit;
} else {
    // Show error
    echo "<h3>Payment Initiation Failed</h3>";
    echo "<p><strong>HTTP Code:</strong> " . $http_code . "</p>";
    echo "<p><strong>Response:</strong></p>";
    echo "<pre>" . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) . "</pre>";
    
    if (isset($result['detail'])) {
        echo "<p><strong>Error Detail:</strong> " . htmlspecialchars($result['detail']) . "</p>";
    }
    
    error_log("Khalti Owner Payment Initiation Error: " . $response);
    exit;
}
?>