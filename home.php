<?php
session_start();
require_once 'config.php';

// Store logged-in user info
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['username'] ?? 'Guest';
$isPaidUser = $_SESSION['is_paid'] ?? 0;

// Get complete user details including owner status
$userDetails = [];
if ($user_id) {
    $userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $userStmt->execute([$user_id]);
    $userDetails = $userStmt->fetch(PDO::FETCH_ASSOC);
}

$is_owner = $userDetails['is_owner'] ?? 0;
$owner_paid = $userDetails['owner_paid'] ?? 0;
$is_admin = $userDetails['is_admin'] ?? 0;

// Handle notifications
$notifications = [];
if ($user_id) {
    $notifStmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
    $notifStmt->execute([$user_id]);
    $notifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC);

    $mark_read = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $mark_read->execute([$user_id]);
}

// Pagination
$perPage = 12;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// Fetch user's favourite rooms
$favRooms = [];
if ($user_id) {
    $favStmt = $pdo->prepare("SELECT room_id FROM favourites WHERE user_id=?");
    $favStmt->execute([$user_id]);
    $favRooms = $favStmt->fetchAll(PDO::FETCH_COLUMN);
}

// Fetch all visit requests
$visitRequests = [];
if ($user_id) {
    $reqStmt = $pdo->prepare("SELECT * FROM visit_requests WHERE user_id = ?");
    $reqStmt->execute([$user_id]);
    $visitRequests = $reqStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Check owner status from session/URL
$owner_status = $_GET['owner'] ?? '';

$alreadyConfirmed = false;
$confirmedRoomId = null;
foreach ($visitRequests as $req) {
    if (strtolower($req['status']) === 'confirmed') {
        $alreadyConfirmed = true;
        $confirmedRoomId = $req['room_id'];
        break;
    }
}

function getRequestStatus($visitRequests, $roomId) {
    foreach ($visitRequests as $req) {
        if ($req['room_id'] == $roomId) {
            $status = strtolower($req['status']);
            if ($status === 'cancelled') continue;
            return $status;
        }
    }
    return null;
}

function getRequestId($visitRequests, $roomId) {
    foreach ($visitRequests as $req) {
        if ($req['room_id'] == $roomId && strtolower($req['status']) != 'cancelled') {
            return $req['id'];
        }
    }
    return null;
}

function isRoomTaken($pdo, $roomId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM visit_requests WHERE room_id=? AND LOWER(status)='confirmed'");
    $stmt->execute([$roomId]);
    return $stmt->fetchColumn() > 0;
}

$query = '';
$error_message = '';
$isFavouritesView = !empty($_GET['favourites']) && $_GET['favourites'] == 1;

try {
    if ($isFavouritesView && $user_id) {
        if ($favRooms) {
            $placeholders = implode(',', array_fill(0, count($favRooms), '?'));
            $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id IN ($placeholders) ORDER BY id DESC LIMIT $perPage OFFSET $offset");
            $stmt->execute($favRooms);
            $rooms = $stmt->fetchAll();
        } else {
            $rooms = [];
            $error_message = "You have no favourite rooms yet.";
        }
    } elseif (!empty($_GET['q'])) {
        $query = trim($_GET['q']);
        $stmt = $pdo->prepare("SELECT * FROM rooms 
            WHERE location LIKE :q1 
            OR description LIKE :q2 
            OR owner_name LIKE :q3 
            OR room_number LIKE :q4 
            OR price LIKE :q5 
            ORDER BY id DESC LIMIT $perPage OFFSET $offset");
        $stmt->execute([
            'q1' => "%$query%",
            'q2' => "%$query%",
            'q3' => "%$query%",
            'q4' => "%$query%",
            'q5' => "%$query%",
        ]);
        $rooms = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM rooms ORDER BY id DESC LIMIT $perPage OFFSET $offset");
        $stmt->execute();
        $rooms = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $rooms = [];
    $error_message = "Rooms table not found or empty.";
}

$totalStmt = $pdo->query("SELECT COUNT(*) FROM rooms");
$totalRooms = $totalStmt->fetchColumn();
$totalPages = ceil($totalRooms / $perPage);

// Get owner's rooms count
$ownerRoomCount = 0;
if ($user_id && $is_owner && $owner_paid) {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE owner_id = ?");
    $countStmt->execute([$user_id]);
    $ownerRoomCount = $countStmt->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Room Finder Nepal - Home</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family: 'Inter', sans-serif; }
body { background-color: #fafafa; color: #333; line-height: 1.5; }

/* ── HEADER ── */
header {
    background:#fff;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    position: relative;
}
header h1 { font-size: 1.4rem; color: #2c3e50; }

/* Hamburger button */
.hamburger {
    display: none;
    flex-direction: column;
    cursor: pointer;
    gap: 5px;
    background: none;
    border: none;
    padding: 5px;
}
.hamburger span {
    display: block;
    width: 25px;
    height: 3px;
    background: #2c3e50;
    border-radius: 3px;
    transition: 0.3s;
}

/* Desktop nav */
nav {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 5px;
}
nav span { font-weight: 500; margin-right: 10px; color: #2c3e50; }
nav a {
    color: #2c3e50;
    text-decoration: none;
    font-weight: 500;
    padding: 6px 10px;
    border-radius: 6px;
    transition: color 0.2s;
    font-size: 0.9rem;
}
nav a:hover { color: #4facfe; }
nav a.logout {
    background: #4facfe;
    color: #fff;
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 600;
}
nav a.logout:hover { background: #3b99e0; color:#fff; }
nav a.owner-badge {
    background: #28a745;
    color: #fff;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* ── HERO ── */
.hero {
    background: url('https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=1470&q=80') center/cover no-repeat;
    height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}
.hero::after {
    content:"";
    position: absolute;
    top:0; left:0;
    width:100%; height:100%;
    background: rgba(0,0,0,0.35);
}
.hero-content {
    position: relative;
    text-align: center;
    color: #fff;
    z-index: 1;
    padding: 0 15px;
    width: 100%;
}
.hero-content h1 { font-size: 2.2rem; font-weight: 600; margin-bottom: 20px; }

.search-bar { margin-top: 20px; display: flex; justify-content: center; }
.search-bar input {
    width: 60%;
    max-width: 500px;
    padding: 12px 15px;
    border: none;
    border-radius: 8px 0 0 8px;
    font-size: 1rem;
    font-size: 16px; /* prevent iOS zoom */
}
.search-bar button {
    padding: 12px 20px;
    border: none;
    background-color: #4facfe;
    color: #fff;
    border-radius: 0 8px 8px 0;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s;
    white-space: nowrap;
}
.search-bar button:hover { background-color: #3b99e0; }

/* ── CONTAINER ── */
.container { max-width: 1200px; margin: 50px auto; padding: 0 20px; }
h2.section-title { text-align: center; margin-bottom: 40px; font-size: 2rem; color: #2c3e50; }

/* ── OWNER DASHBOARD ── */
.owner-dashboard {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 40px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    border-left: 4px solid #28a745;
}
.owner-dashboard h3 {
    color: #2c3e50;
    margin-bottom: 15px;
    font-size: 1.3rem;
}
.owner-dashboard .stats {
    display: flex;
    gap: 30px;
    flex-wrap: wrap;
    margin: 15px 0;
}
.owner-dashboard .stat {
    background: #f8f9fa;
    padding: 10px 20px;
    border-radius: 8px;
}
.owner-dashboard .stat strong {
    font-size: 1.2rem;
    color: #28a745;
}
.owner-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 15px;
}
.owner-actions .btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
    display: inline-block;
}
.btn-primary { background: #007bff; color: white; }
.btn-primary:hover { background: #0069d9; }
.btn-success { background: #28a745; color: white; }
.btn-success:hover { background: #218838; }
.btn-warning { background: #ffc107; color: #333; }
.btn-warning:hover { background: #e0a800; }
.btn-secondary { background: #6c757d; color: white; }
.btn-secondary:hover { background: #5a6268; }

.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
}
.alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
.alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
.alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

/* ── GRID ── */
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 30px;
}

/* ── CARD ── */
.card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 6px 18px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
    position: relative;
}
.card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
.card img { width: 100%; height: 200px; object-fit: cover; }
.card-content { padding: 20px; }
.card-content h3 { font-size: 1.2rem; color: #2c3e50; margin-bottom: 10px; }
.card-content p { font-size: 0.95rem; color: #555; margin-bottom: 8px; }
.price { font-weight: 600; color: #4facfe; margin-bottom: 12px; }

.card-buttons { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.card-content a,
.card-content button,
.card-content form button {
    display: inline-block;
    padding: 8px 15px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: opacity 0.2s;
    font-size: 0.875rem;
}
.card-content a { background: #4facfe; color: #fff; }
.card-content button.fav { background: #4facfe; color: #fff; }
.card-content button.fav.favourited { background: #ffc107; color: #000; }
.card-content form button.chat { background: #10b981; color: #fff; }
.card-content form button.cancel { background: #e53935; color: #fff; }

.button-all {
    display: inline-block;
    margin-top: 20px;
    padding: 10px 20px;
    background: #4facfe;
    color: #fff;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: background 0.2s;
}
.button-all:hover { background: #3b99e0; }

/* ── PAGINATION ── */
.pagination { text-align: center; margin-top: 40px; }
.pagination a {
    margin: 3px;
    padding: 8px 12px;
    background: #4facfe;
    color: #fff;
    border-radius: 5px;
    text-decoration: none;
    display: inline-block;
}
.pagination a.active { background: #3b99e0; }

footer { text-align: center; margin: 60px 0 20px; color: #777; font-size: 0.9rem; }

/* ════════════════════════════
   MOBILE STYLES (max 768px)
════════════════════════════ */
@media (max-width: 768px) {

    /* Header */
    header { padding: 12px 16px; }
    header h1 { font-size: 1.1rem; }

    .hamburger { display: flex; }

    nav {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background: #fff;
        flex-direction: column;
        align-items: flex-start;
        padding: 10px 16px 16px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 999;
        gap: 4px;
    }
    nav.open { display: flex; }
    nav span { margin-bottom: 6px; font-weight: 600; border-bottom: 1px solid #eee; width: 100%; padding-bottom: 8px; }
    nav a { width: 100%; padding: 10px 8px; border-radius: 6px; font-size: 1rem; }
    nav a:hover { background: #f0f7ff; }
    nav a.logout { text-align: center; margin-top: 6px; }

    /* Hero */
    .hero { height: 280px; }
    .hero-content h1 { font-size: 1.4rem; margin-bottom: 14px; }
    .search-bar input { width: 70%; padding: 10px 12px; font-size: 15px; }
    .search-bar button { padding: 10px 14px; font-size: 0.875rem; }

    /* Container */
    .container { margin: 30px auto; padding: 0 12px; }
    h2.section-title { font-size: 1.5rem; margin-bottom: 25px; }

    .grid { grid-template-columns: 1fr; gap: 20px; }

    .card img { height: 180px; }
    .card-content { padding: 14px; }
    .card-content h3 { font-size: 1rem; }
    .card-content p { font-size: 0.875rem; }

    .card-buttons { flex-direction: column; }
    .card-content a,
    .card-content button,
    .card-content form,
    .card-content form button {
        width: 100%;
        text-align: center;
    }

    .pagination a { padding: 7px 10px; font-size: 0.875rem; }
    
    .owner-dashboard .stats {
        flex-direction: column;
        gap: 10px;
    }
}

@media (max-width: 400px) {
    header h1 { font-size: 1rem; }
    .hero { height: 240px; }
    .hero-content h1 { font-size: 1.2rem; }
    .search-bar { flex-direction: column; align-items: center; gap: 8px; }
    .search-bar input { width: 100%; border-radius: 8px; }
    .search-bar button { width: 100%; border-radius: 8px; }
}
</style>
</head>
<body>

<header>
    <h1>Room Finder Nepal</h1>

    <button class="hamburger" id="hamburger" aria-label="Toggle menu">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <nav id="main-nav">
        <span><?= $user_id ? htmlspecialchars($user_name) : 'Welcome!' ?></span>
        <?php if ($is_owner && $owner_paid): ?>
            <span class="owner-badge">🏠 Owner</span>
        <?php endif; ?>
        <a href="home.php">Home</a>
        <a href="home.php#rooms">Browse Rooms</a>
        <?php if ($user_id): ?>
            <?php if ($is_owner && $owner_paid): ?>
    <a href="add_room_owner.php" style="color: #28a745; font-weight: 600;">➕ List Room</a>
    <a href="my_rooms.php">📋 My Rooms</a>
<?php endif; ?>
            <a href="profile.php">My Profile</a>
            <a href="edit_profile.php">Edit Profile</a>
            <a href="home.php?favourites=1">❤️ Favourites</a>
            <a href="visit_requests.php">📅 Visit Requests</a>
            <a href="logout.php" class="logout">Logout</a>
        <?php else: ?>
            <a href="index.php" class="logout">Login/Register</a>
        <?php endif; ?>
    </nav>
</header>

<div class="hero">
    <div class="hero-content">
        <h1>Discover the Best Rooms in Kathmandu</h1>
        <form method="GET" action="home.php" class="search-bar">
            <input type="text" name="q" placeholder="Search by location, owner, price..." value="<?= htmlspecialchars($query) ?>">
            <button type="submit">Search</button>
        </form>
    </div>
</div>

<div class="container" id="rooms">

    <?php if($notifications): ?>
        <div style="margin-bottom:20px;">
            <?php foreach($notifications as $note): ?>
                <div style="padding:10px; margin-bottom:5px; background:#dff0d8; border-radius:5px; color:#3c763d; text-align:center;">
                    <?= htmlspecialchars($note['message']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- OWNER DASHBOARD -->
    <?php if ($user_id): ?>
        <div class="owner-dashboard">
            <h3>🏠 Owner Dashboard</h3>
            
            <?php if ($owner_status === 'success'): ?>
                <div class="alert alert-success">
                    ✅ <strong>Payment Successful!</strong> You are now a verified owner!
                </div>
            <?php elseif ($owner_status === 'failed'): ?>
                <div class="alert alert-danger">
                    ❌ <strong>Payment Failed!</strong> Please try again.
                </div>
            <?php elseif ($owner_status === 'already_completed'): ?>
                <div class="alert alert-info">
                    ℹ️ You are already a verified owner.
                </div>
            <?php endif; ?>
            
            <?php if (!$is_owner): ?>
                <!-- Not an owner yet -->
                <div class="alert alert-info">
                    <strong>🏠 Become a Property Owner!</strong>
                    <p>Pay Rs. <?php echo OWNER_FEE_NPR; ?> to list your properties on our platform.</p>
                    <a href="initiate_owner_payment.php" class="btn btn-success" style="margin-top: 10px;">
                        💳 Pay Rs. <?php echo OWNER_FEE_NPR; ?> to Become Owner
                    </a>
                </div>
                
            <?php elseif ($is_owner && !$owner_paid): ?>
                <!-- Owner but payment pending -->
                <div class="alert alert-warning">
                    <strong>⏳ Owner Verification Pending</strong>
                    <p>Your payment to become an owner is being processed.</p>
                    <a href="initiate_owner_payment.php" class="btn btn-warning" style="margin-top: 10px;">
                        💳 Complete Payment
                    </a>
                </div>
                
            <?php elseif ($is_owner && $owner_paid): ?>
                <!-- Verified Owner -->
                <div class="alert alert-success">
                    <strong>✅ You are a Verified Owner!</strong>
                    <p>You can now list and manage your properties.</p>
                </div>
                
                <div class="stats">
                    <div class="stat">
                        <strong><?= $ownerRoomCount ?></strong> Rooms Listed
                    </div>
                    <div class="stat">
                        <strong>0</strong> Active Bookings
                    </div>
                    <div class="stat">
                        <strong>0</strong> Visit Requests
                    </div>
                </div>
                
              <!-- In the owner actions section -->

<div class="owner-actions">
    <a href="add_room_owner.php" class="btn btn-primary">➕ Add New Room</a>
    <a href="my_rooms.php" class="btn btn-secondary">📋 My Rooms</a>
    <a href="manage_visit_requests.php" class="btn btn-warning">📅 Visit Requests</a>
</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <p style="text-align:center; color:red; font-weight:bold;"><?= htmlspecialchars($error_message) ?></p>
    <?php endif; ?>

    <h2 class="section-title">
        <?= $isFavouritesView ? "Your Favourite Rooms" : ($query ? "Results for '".htmlspecialchars($query)."'" : "Latest Rooms Available") ?>
    </h2>

    <div class="grid">
        <?php if ($rooms): ?>
            <?php foreach ($rooms as $room):
                $isFav = in_array($room['id'], $favRooms);
                $reqId = getRequestId($visitRequests, $room['id']);
                $status = getRequestStatus($visitRequests, $room['id']);
                $roomConfirmed = isRoomTaken($pdo, $room['id']);
                $userConfirmed = ($alreadyConfirmed && $confirmedRoomId == $room['id']);
            ?>
            <div class="card">
                <img src="<?= ($room['photo'] && file_exists('uploads/'.$room['photo'])) ? 'uploads/'.htmlspecialchars($room['photo']) : 'https://via.placeholder.com/400x200?text=No+Photo' ?>" alt="Room Photo">
                <div class="card-content">
                    <h3>Room #: <?= htmlspecialchars($room['room_number']) ?></h3>
                    <p><strong>Owner:</strong> <?= htmlspecialchars($room['owner_name']) ?></p>
                    <p><strong>Location:</strong> <?= htmlspecialchars($room['location']) ?></p>
                    <p class="price">Rs: <?= htmlspecialchars($room['price']) ?></p>
                    <p><?= nl2br(htmlspecialchars(strlen($room['description']) > 100 ? substr($room['description'],0,100).'...' : $room['description'])) ?></p>

                    <div class="card-buttons">
                        <a href="room_detail.php?id=<?= $room['id'] ?>">View Details</a>

                        <?php if ($user_id): ?>
                            <?php if (!$isPaidUser): ?>
                                <button class="chat" style="background:#ccc; cursor:not-allowed;" title="Paid users only">
                                    Visit Request (Paid Only)
                                </button>
                            <?php elseif ($roomConfirmed && !$userConfirmed): ?>
                                <button class="chat" style="background:#ccc; cursor:not-allowed;">Room Already Confirmed</button>
                            <?php elseif ($userConfirmed): ?>
                                <form method="POST" action="cancel_confirm.php" style="display:inline;">
                                    <input type="hidden" name="request_id" value="<?= $reqId ?>">
                                    <button type="button" class="cancel" data-request="<?= $reqId ?>">Cancel Confirm</button>
                                </form>
                            <?php elseif ($status === 'pending' || $status === 'accepted'): ?>
                                <form method="POST" action="confirm_room.php" style="display:inline;">
                                    <input type="hidden" name="request_id" value="<?= $reqId ?>">
                                    <button type="submit" class="chat" style="background:#ff9800;">Confirm Room</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="visit_requests.php" style="display:inline;">
                                    <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                    <button type="submit" name="send_request" class="chat">Send Visit Request</button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <button onclick="alert('You must login to request a visit');" class="chat" style="background:#ccc; cursor:not-allowed;">
                                Send Visit Request
                            </button>
                        <?php endif; ?>

                        <button class="fav <?= $isFav ? 'favourited' : '' ?>" data-room="<?= $room['id'] ?>">
                            <?= $isFav ? '★ Favourite' : '☆ Favourite' ?>
                        </button>
                    </div>

                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="grid-column:1/-1; text-align:center; color:#777;">No rooms found.</p>
        <?php endif; ?>
    </div>

    <div style="text-align:center; margin-top:20px;">
        <a href="browse_rooms.php" class="button-all">View All Rooms</a>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for($i=1;$i<=$totalPages;$i++): ?>
                <a href="home.php?page=<?= $i ?>" class="<?= $i==$page?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<footer>
    &copy; <?= date('Y') ?> Room Finder Nepal
</footer>

<script>
// ── Hamburger menu toggle ──
document.getElementById('hamburger').addEventListener('click', () => {
    document.getElementById('main-nav').classList.toggle('open');
});

// Close nav when a link is clicked (mobile)
document.querySelectorAll('#main-nav a').forEach(link => {
    link.addEventListener('click', () => {
        document.getElementById('main-nav').classList.remove('open');
    });
});

document.addEventListener('DOMContentLoaded', () => {

    // Favourite rooms
    document.querySelectorAll('.fav').forEach(button => {
        button.addEventListener('click', () => {
            <?php if (!$user_id): ?>
                alert('You must login to favourite a room');
                return;
            <?php else: ?>
                const roomId = button.dataset.room;
                fetch('favourite_room.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'room_id=' + roomId
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'added') {
                        button.textContent = '★ Favourite';
                        button.classList.add('favourited');
                    } else if (data.status === 'removed') {
                        button.textContent = '☆ Favourite';
                        button.classList.remove('favourited');
                    } else {
                        alert('Error: ' + (data.msg || 'Unknown error'));
                    }
                })
                .catch(err => { console.error(err); alert('Request failed.'); });
            <?php endif; ?>
        });
    });

    // Cancel confirm
    document.querySelectorAll('.cancel').forEach(button => {
        button.addEventListener('click', e => {
            e.preventDefault();
            const requestId = button.dataset.request;
            if (!confirm('Are you sure you want to cancel this confirmed room?')) return;

            fetch('cancel_confirm.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'request_id=' + requestId
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    button.textContent = 'Cancelled';
                    button.style.background = '#ccc';
                    button.style.cursor = 'not-allowed';
                    button.disabled = true;

                    const card = button.closest('.card');
                    const chatBtn = card.querySelector('.chat');
                    if (data.is_paid == 0) {
                        chatBtn.textContent = 'Send Visit Request (Paid Only)';
                        chatBtn.style.background = '#ccc';
                        chatBtn.style.cursor = 'not-allowed';
                        chatBtn.disabled = true;
                    } else {
                        chatBtn.textContent = 'Send Visit Request';
                        chatBtn.style.background = '#10b981';
                        chatBtn.style.cursor = 'pointer';
                        chatBtn.disabled = false;
                    }
                } else {
                    alert('Error: ' + (data.msg || 'Unknown'));
                }
            })
            .catch(err => { console.error(err); alert('Request failed'); });
        });
    });
});
</script>

</body> </html>