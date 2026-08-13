<?php
// khalti_callback.php
session_start();
require_once 'config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];
$pidx = $_GET['pidx'] ?? '';
$purchase_order_id = $_GET['purchase_order_id'] ?? '';

if (empty($pidx) || empty($purchase_order_id)) {
    die("Invalid payment response.");
}

// ✅ DETERMINE PAYMENT TYPE
$room_id = 0;
$payment_type = 'room'; // default
$is_owner_payment = false;

// Check if it's an owner payment (starts with OWNER_)
if (strpos($purchase_order_id, 'OWNER_') === 0) {
    $is_owner_payment = true;
    $payment_type = 'owner';
} 
// Check if it's a room payment (starts with ROOM)
elseif (preg_match('/^ROOM(\d+)_/', $purchase_order_id, $m)) {
    $room_id = intval($m[1]);
    $payment_type = 'room';
} else {
    die("Invalid payment type. Purchase Order ID: " . htmlspecialchars($purchase_order_id));
}

// VERIFY PAYMENT WITH KHALTI
$ch = curl_init(KHALTI_BASE_URL . "epayment/lookup/");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode(["pidx" => $pidx]),
    CURLOPT_HTTPHEADER => [
        "Authorization: key " . KHALTI_SECRET_KEY,
        "Content-Type: application/json"
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

// PROCESS PAYMENT STATUS
if ($http_code === 200 && !empty($result['status'])) {
    if ($result['status'] === 'Completed') {
        // Verify payment belongs to user
        $verifyStmt = $pdo->prepare("SELECT id, user_id, status, payment_type FROM payments WHERE pidx = ? AND purchase_order_id = ?");
        $verifyStmt->execute([$pidx, $purchase_order_id]);
        $payment = $verifyStmt->fetch();
        
        if (!$payment) {
            die("Payment record not found.");
        }
        
        if ($payment['user_id'] != $current_user_id) {
            die("Unauthorized access.");
        }
        
        if ($payment['status'] === 'Completed') {
            // Already processed
            if ($payment['payment_type'] === 'owner') {
                header("Location: home.php?owner=already_completed");
            } else {
                header("Location: room_detail.php?id=$room_id&payment=already_completed");
            }
            exit;
        }
        
        // ✅ UPDATE PAYMENT
        $updateStmt = $pdo->prepare("UPDATE payments 
                                     SET status = 'Completed', 
                                         transaction_id = ?,
                                         completed_at = NOW()
                                     WHERE pidx = ?");
        $updateStmt->execute([$result['transaction_id'] ?? null, $pidx]);
        
        // ✅ HANDLE DIFFERENT PAYMENT TYPES
        if ($payment['payment_type'] === 'owner' || $is_owner_payment) {
            // ✅ OWNER PAYMENT - Update user as owner
            $userStmt = $pdo->prepare("UPDATE users 
                                       SET is_owner = 1, 
                                           owner_paid = 1, 
                                           owner_payment_date = NOW(),
                                           owner_verified = 1 
                                       WHERE id = ?");
            $userStmt->execute([$current_user_id]);
            $_SESSION['is_owner'] = 1;
            $_SESSION['owner_paid'] = 1;
            
            // Update any existing properties from pending to active
            $propStmt = $pdo->prepare("UPDATE rooms SET status = 'available' WHERE owner_id = ? AND status = 'pending'");
            $propStmt->execute([$current_user_id]);
            
            header("Location: home.php?owner=success");
            exit;
        } else {
            // ✅ ROOM PAYMENT - Update room access
            $userStmt = $pdo->prepare("UPDATE users SET is_paid = 1 WHERE id = ?");
            $userStmt->execute([$current_user_id]);
            $_SESSION['is_paid'] = 1;
            
            header("Location: room_detail.php?id=$room_id&payment=success");
            exit;
        }
    } else {
        // Payment pending or failed
        $updateStmt = $pdo->prepare("UPDATE payments SET status = ? WHERE pidx = ?");
        $updateStmt->execute([$result['status'], $pidx]);
        
        if ($payment_type === 'owner') {
            header("Location: home.php?owner=" . strtolower($result['status']));
        } else {
            header("Location: room_detail.php?id=$room_id&payment=" . strtolower($result['status']));
        }
        exit;
    }
} else {
    // Verification failed
    $updateStmt = $pdo->prepare("UPDATE payments SET status = 'Failed' WHERE pidx = ?");
    $updateStmt->execute([$pidx]);
    
    if ($payment_type === 'owner') {
        header("Location: home.php?owner=failed");
    } else {
        header("Location: room_detail.php?id=$room_id&payment=failed");
    }
    exit;
}
?>