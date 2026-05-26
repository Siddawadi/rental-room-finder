<?php
session_start();
require_once 'config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['username'] ?? 'Guest';
$message = '';

if (isset($_POST['send_request']) && isset($_POST['room_id'])) {
    $room_id = intval($_POST['room_id']);

    // Check if user already has a confirmed room
    $confirmedStmt = $pdo->prepare("SELECT * FROM visit_requests WHERE user_id = :user_id AND status = 'Confirmed'");
    $confirmedStmt->execute(['user_id' => $user_id]);
    if ($confirmedStmt->rowCount() > 0) {
        $message = "You already have a confirmed room. Cancel it before requesting another.";
    } else {
        // Check for pending requests
        $pendingStmt = $pdo->prepare("SELECT * FROM visit_requests WHERE user_id = :user_id AND status = 'Pending'");
        $pendingStmt->execute(['user_id' => $user_id]);
        if ($pendingStmt->rowCount() > 0) {
            $message = "You already have a pending visit request. Cancel it before requesting another.";
        } else {
            // Insert new visit request
            $insertStmt = $pdo->prepare("INSERT INTO visit_requests (user_id, room_id, status, created_at) VALUES (:user_id, :room_id, 'Pending', NOW())");
            if ($insertStmt->execute(['user_id' => $user_id, 'room_id' => $room_id])) {
                $message = "Visit request sent successfully!";
            } else {
                $message = "Failed to send visit request.";
            }
        }
    }
}

if (isset($_POST['cancel_request']) && isset($_POST['request_id'])) {
    $request_id = intval($_POST['request_id']);

    // Fetch the current status first
    $stmt = $pdo->prepare("SELECT status FROM visit_requests WHERE id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $request_id, 'user_id' => $user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $oldStatus = strtolower($row['status']);

        if ($oldStatus !== 'cancelled') {
            $updateStmt = $pdo->prepare("UPDATE visit_requests SET status='Cancelled' WHERE id = :id AND user_id = :user_id");
            if ($updateStmt->execute(['id' => $request_id, 'user_id' => $user_id])) {

                // Only reset is_paid if the request was Confirmed
                if ($oldStatus === 'confirmed') {
                    $pdo->prepare("UPDATE users SET is_paid=0 WHERE id=?")->execute([$user_id]);
                    $_SESSION['is_paid'] = 0;
                    $message = "Confirmed room cancelled. Your account is now unpaid.";
                } else {
                    $message = "Visit request cancelled successfully.";
                }

            } else {
                $message = "Failed to cancel request.";
            }
        } else {
            $message = "This request is already cancelled.";
        }
    } else {
        $message = "Invalid request.";
    }
}

// Fetch all visit requests for the user
$stmt = $pdo->prepare("
    SELECT vr.*, r.room_number, r.owner_name, r.location
    FROM visit_requests vr
    JOIN rooms r ON vr.room_id = r.id
    WHERE vr.user_id = :user_id
    ORDER BY vr.created_at DESC
");
$stmt->execute(['user_id' => $user_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Visit Requests</title>
<style>
body { font-family: 'Inter', sans-serif; background:#f5f5f5; margin:0; padding:0; }
header { background:#fff; padding:20px 40px; box-shadow:0 2px 8px rgba(0,0,0,0.1); display:flex; justify-content:space-between; align-items:center;}
header h1 { margin:0; color:#2c3e50;}
nav a { margin-left:15px; text-decoration:none; color:#2c3e50; font-weight:500; }
nav a.logout { background:#4facfe; color:#fff; padding:6px 12px; border-radius:5px; }
.container { max-width:900px; margin:40px auto; background:#fff; padding:25px; border-radius:10px; box-shadow:0 5px 15px rgba(0,0,0,0.1);}
h2 { margin-bottom:20px; color:#2c3e50; }
table { width:100%; border-collapse: collapse; margin-top:15px;}
th, td { border:1px solid #ddd; padding:10px; text-align:left; }
th { background:#f0f0f0; }
button.cancel { background:#d9534f; color:#fff; border:none; padding:6px 12px; border-radius:5px; cursor:pointer; }
button.disabled { background:#ccc; cursor:not-allowed; }
.message { padding:10px; background:#dff0d8; color:#3c763d; margin-bottom:15px; border-radius:5px;}
.status-pending { color:orange; font-weight:bold; }
.status-accepted { color:green; font-weight:bold; }
.status-confirmed { color:blue; font-weight:bold; }
.status-cancelled { color:red; font-weight:bold; }
</style>
</head>
<body>

<header>
    <h1>Room Finder Nepal</h1>
    <nav>
        <span><?= htmlspecialchars($user_name) ?></span>
        <a href="home.php">Home</a>
        <a href="logout.php" class="logout">Logout</a>
    </nav>
</header>

<div class="container">
    <h2>My Visit Requests</h2>

    <?php if($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if(!empty($requests)): ?>
        <table>
            <thead>
                <tr>
                    <th>Room Number</th>
                    <th>Owner</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Requested Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($requests as $r): ?>
                    <?php
                        $status = strtolower(trim($r['status'] ?? 'pending'));
                        $status_text = ucfirst($status);
                        $status_class = 'status-pending';
                        if ($status === 'accepted') $status_class = 'status-accepted';
                        elseif ($status === 'confirmed') $status_class = 'status-confirmed';
                        elseif ($status === 'cancelled') $status_class = 'status-cancelled';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($r['room_number']) ?></td>
                        <td><?= htmlspecialchars($r['owner_name']) ?></td>
                        <td><?= htmlspecialchars($r['location']) ?></td>
                        <td class="<?= $status_class ?>"><?= htmlspecialchars($status_text) ?></td>
                        <td><?= htmlspecialchars($r['created_at']) ?></td>
                        <td>
                            <?php if(in_array($status, ['pending', 'accepted'])): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                    <button type="submit" name="cancel_request" class="cancel">Cancel</button>
                                </form>
                            <?php else: ?>
                                <button class="disabled">-</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align:center;">You have no visit requests yet.</p>
    <?php endif; ?>
</div>

</body>
</html>