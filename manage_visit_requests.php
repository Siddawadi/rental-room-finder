<?php
// manage_visit_requests.php - For owners to manage visit requests
session_start();
require_once 'config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if user is verified owner
$userStmt = $pdo->prepare("SELECT is_owner, owner_paid, name FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch();

if (!$user['is_owner'] || !$user['owner_paid']) {
    $_SESSION['error'] = "You must be a verified owner to access this page.";
    header('Location: home.php');
    exit;
}

// Get owner's rooms
$roomsStmt = $pdo->prepare("SELECT id, room_number, location FROM rooms WHERE owner_id = ?");
$roomsStmt->execute([$user_id]);
$ownerRooms = $roomsStmt->fetchAll();
$roomIds = array_column($ownerRooms, 'id');

// Get visit requests for owner's rooms
$visitRequests = [];
if (!empty($roomIds)) {
    $placeholders = implode(',', array_fill(0, count($roomIds), '?'));
    $stmt = $pdo->prepare("
        SELECT vr.*, u.name as user_name, u.email as user_email, u.phone as user_phone, r.room_number, r.location 
        FROM visit_requests vr
        JOIN users u ON vr.user_id = u.id
        JOIN rooms r ON vr.room_id = r.id
        WHERE vr.room_id IN ($placeholders)
        ORDER BY vr.created_at DESC
    ");
    $stmt->execute($roomIds);
    $visitRequests = $stmt->fetchAll();
}

// Handle status update (accept/reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && isset($_POST['action'])) {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action'];
    
    // Verify this request belongs to owner's room
    $verifyStmt = $pdo->prepare("
        SELECT vr.*, r.owner_id 
        FROM visit_requests vr
        JOIN rooms r ON vr.room_id = r.id
        WHERE vr.id = ? AND r.owner_id = ?
    ");
    $verifyStmt->execute([$request_id, $user_id]);
    $request = $verifyStmt->fetch();
    
    if ($request) {
        if ($action === 'accept') {
            $updateStmt = $pdo->prepare("UPDATE visit_requests SET status = 'accepted' WHERE id = ?");
            $updateStmt->execute([$request_id]);
            
            // Add notification for user
            $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
            $notifStmt->execute([$request['user_id'], "Your visit request for room {$request['room_number']} has been accepted!"]);
            
            $message = "Visit request accepted!";
            $message_type = "success";
        } elseif ($action === 'reject') {
            $updateStmt = $pdo->prepare("UPDATE visit_requests SET status = 'rejected' WHERE id = ?");
            $updateStmt->execute([$request_id]);
            
            // Add notification for user
            $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
            $notifStmt->execute([$request['user_id'], "Your visit request for room {$request['room_number']} has been rejected."]);
            
            $message = "Visit request rejected.";
            $message_type = "warning";
        }
    } else {
        $message = "Invalid request.";
        $message_type = "danger";
    }
    
    // Refresh the page to show updated status
    header("Location: manage_visit_requests.php?msg=" . urlencode($message) . "&type=" . $message_type);
    exit;
}

// Handle delete
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $request_id = intval($_GET['delete']);
    
    // Verify this request belongs to owner's room
    $verifyStmt = $pdo->prepare("
        SELECT vr.*, r.owner_id 
        FROM visit_requests vr
        JOIN rooms r ON vr.room_id = r.id
        WHERE vr.id = ? AND r.owner_id = ?
    ");
    $verifyStmt->execute([$request_id, $user_id]);
    $request = $verifyStmt->fetch();
    
    if ($request) {
        $deleteStmt = $pdo->prepare("DELETE FROM visit_requests WHERE id = ?");
        $deleteStmt->execute([$request_id]);
        $message = "Visit request deleted.";
        $message_type = "success";
    } else {
        $message = "Invalid request.";
        $message_type = "danger";
    }
    
    header("Location: manage_visit_requests.php?msg=" . urlencode($message) . "&type=" . $message_type);
    exit;
}

// Get message from URL
$msg = $_GET['msg'] ?? '';
$msg_type = $_GET['type'] ?? 'info';

// Count requests by status
$pendingCount = 0;
$acceptedCount = 0;
$rejectedCount = 0;
foreach ($visitRequests as $req) {
    if (strtolower($req['status']) === 'pending') $pendingCount++;
    elseif (strtolower($req['status']) === 'accepted') $acceptedCount++;
    elseif (strtolower($req['status']) === 'rejected') $rejectedCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Visit Requests</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .back-btn:hover { background: #5a6268; }
        
        .stats {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin: 15px 0;
        }
        .stat-card {
            background: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            flex: 1;
            min-width: 120px;
            text-align: center;
        }
        .stat-card .number {
            font-size: 24px;
            font-weight: bold;
        }
        .stat-card .label {
            color: #666;
            font-size: 14px;
        }
        .stat-card.pending .number { color: #ffc107; }
        .stat-card.accepted .number { color: #28a745; }
        .stat-card.rejected .number { color: #dc3545; }
        
        .table-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        tr:hover { background: #f8f9fa; }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-accepted { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-confirmed { background: #cce5ff; color: #004085; }
        .status-cancelled { background: #e2e3e5; color: #383d41; }
        
        .btn {
            padding: 6px 14px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
            display: inline-block;
            margin: 2px;
        }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-warning:hover { background: #e0a800; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        
        .alert {
            padding: 12px 20px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .empty-state .icon { font-size: 48px; margin-bottom: 10px; }
        
        @media (max-width: 768px) {
            .stats { flex-direction: column; }
            .header { flex-direction: column; gap: 10px; }
            table { font-size: 14px; }
            th, td { padding: 8px 10px; }
            .btn { padding: 4px 10px; font-size: 12px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>📅 Manage Visit Requests</h1>
                <p style="color: #666; margin-top: 5px;">Manage visit requests for your properties</p>
            </div>
            <a href="home.php" class="back-btn">← Back to Home</a>
        </div>
        
        <?php if ($msg): ?>
            <div class="alert alert-<?= $msg_type ?>">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>
        
        <!-- Stats -->
        <div class="stats">
            <div class="stat-card pending">
                <div class="number"><?= $pendingCount ?></div>
                <div class="label">⏳ Pending</div>
            </div>
            <div class="stat-card accepted">
                <div class="number"><?= $acceptedCount ?></div>
                <div class="label">✅ Accepted</div>
            </div>
            <div class="stat-card rejected">
                <div class="number"><?= $rejectedCount ?></div>
                <div class="label">❌ Rejected</div>
            </div>
        </div>
        
        <!-- Requests Table -->
        <div class="table-container">
            <?php if (!empty($visitRequests)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Room</th>
                            <th>Location</th>
                            <th>Visit Date</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($visitRequests as $request): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($request['user_name']) ?></strong><br>
                                    <small style="color: #666;"><?= htmlspecialchars($request['user_phone'] ?? 'N/A') ?></small>
                                </td>
                                <td>#<?= htmlspecialchars($request['room_number']) ?></td>
                                <td><?= htmlspecialchars($request['location']) ?></td>
                                <td><?= date('M d, Y h:i A', strtotime($request['visit_date'])) ?></td>
                                <td><?= htmlspecialchars($request['message'] ?? 'No message') ?></td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($request['status']) ?>">
                                        <?= ucfirst($request['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (strtolower($request['status']) === 'pending'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                            <input type="hidden" name="action" value="accept">
                                            <button type="submit" class="btn btn-success" onclick="return confirm('Accept this visit request?')">✅ Accept</button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-danger" onclick="return confirm('Reject this visit request?')">❌ Reject</button>
                                        </form>
                                    <?php elseif (strtolower($request['status']) === 'accepted'): ?>
                                        <span style="color: #28a745;">✓ Accepted</span>
                                    <?php elseif (strtolower($request['status']) === 'rejected'): ?>
                                        <span style="color: #dc3545;">✗ Rejected</span>
                                    <?php elseif (strtolower($request['status']) === 'confirmed'): ?>
                                        <span style="color: #004085;">✓ Confirmed</span>
                                    <?php else: ?>
                                        <span style="color: #666;"><?= ucfirst($request['status']) ?></span>
                                    <?php endif; ?>
                                    <a href="manage_visit_requests.php?delete=<?= $request['id'] ?>" class="btn btn-secondary" onclick="return confirm('Delete this request?')">🗑 Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div class="icon">📭</div>
                    <h3>No Visit Requests</h3>
                    <p style="color: #666;">You haven't received any visit requests for your properties yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>