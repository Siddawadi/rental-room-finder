<?php
session_start();
require_once 'config.php';


$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['username'] ?? 'Guest';


$sort_by = $_GET['sort_by'] ?? 'price_asc';
switch ($sort_by) {
    case 'price_asc': $orderBy = "price ASC"; break;
    case 'price_desc': $orderBy = "price DESC"; break;
    case 'rooms_asc': $orderBy = "bedrooms ASC"; break;
    case 'rooms_desc': $orderBy = "bedrooms DESC"; break;
    default: $orderBy = "price ASC";
}


$perPage = 12;
$page = isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;


$stmt = $pdo->prepare("SELECT * FROM rooms ORDER BY $orderBy LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rooms = $stmt->fetchAll();


$totalStmt = $pdo->query("SELECT COUNT(*) FROM rooms");
$totalRooms = $totalStmt->fetchColumn();
$totalPages = ceil($totalRooms / $perPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Browse All Rooms</title>
<style>
body { font-family: Arial, sans-serif; background:#fafafa; margin:0; }
.container { max-width:1200px; margin:40px auto; padding:0 20px;}
h1 { text-align:center; margin-bottom:20px; }


.back-btn {
    display:inline-block;
    padding:10px 18px;
    background:#4facfe;
    color:#fff;
    border-radius:6px;
    text-decoration:none;
    font-size:15px;
    margin-bottom:25px;
}
.back-btn:hover { background:#3b99e0; }


.floating-back {
    position:fixed;
    bottom:20px;
    left:20px;
    background:#4facfe;
    color:#fff;
    padding:12px 18px;
    border-radius:50px;
    text-decoration:none;
    font-size:14px;
    box-shadow:0 4px 15px rgba(0,0,0,0.2);
}

.sort-form { text-align:center; margin-bottom:30px; }
.sort-form select { padding:10px 15px; border-radius:6px; border:1px solid #ccc; font-size:1rem; }
.sort-form button { padding:10px 20px; background:#4facfe; color:#fff; border:none; border-radius:6px; cursor:pointer; }

.grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:30px;}
.card { background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 6px 18px rgba(0,0,0,0.1); transition: transform 0.3s; }
.card:hover { transform:translateY(-5px); }
.card img { width:100%; height:200px; object-fit:cover; }
.card-content { padding:15px; }
.card-content h3 { margin-bottom:8px; color:#2c3e50; font-size:1.1rem; }
.card-content p { font-size:0.9rem; color:#555; margin-bottom:5px; }
.price { color:#4facfe; font-weight:600; margin-bottom:8px; }

.pagination { text-align:center; margin:40px 0; }
.pagination a { margin:0 5px; padding:8px 12px; background:#4facfe; color:#fff; border-radius:5px; text-decoration:none; }
.pagination a.active { background:#3b99e0; }

</style>
</head>
<body>

<div class="container">

    
    <a href="home.php" class="back-btn">← Back to Homepage</a>

    <h1>All Available Rooms</h1>

   
    <form method="GET" class="sort-form">
        <label for="sort_by">Sort by: </label>
        <select name="sort_by" id="sort_by">
            <option value="price_asc" <?= $sort_by=='price_asc'?'selected':'' ?>>Price: Low → High</option>
            <option value="price_desc" <?= $sort_by=='price_desc'?'selected':'' ?>>Price: High → Low</option>
            <option value="rooms_asc" <?= $sort_by=='rooms_asc'?'selected':'' ?>>Rooms: Low → High</option>
            <option value="rooms_desc" <?= $sort_by=='rooms_desc'?'selected':'' ?>>Rooms: High → Low</option>
        </select>
        <button type="submit">Sort</button>
    </form>

    <div class="grid">
        <?php if ($rooms): ?>
            <?php foreach ($rooms as $room): ?>
            <div class="card">
                <img src="<?= ($room['photo'] && file_exists('uploads/'.$room['photo'])) ? 'uploads/'.htmlspecialchars($room['photo']) : 'https://via.placeholder.com/400x200?text=No+Photo' ?>" alt="Room Photo">
                <div class="card-content">
                    <h3>Room #: <?= htmlspecialchars($room['room_number']) ?></h3>
                    <p><strong>Owner:</strong> <?= htmlspecialchars($room['owner_name']) ?></p>
                    <p><strong>Bedrooms:</strong> <?= htmlspecialchars($room['bedrooms'] ?? '-') ?></p>
                    <p class="price">Rs :<?= htmlspecialchars($room['price']) ?></p>
                    <a href="room_detail.php?id=<?= $room['id'] ?>" style="padding:6px 12px; background:#4facfe; color:#fff; border-radius:6px; text-decoration:none;">View Details</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center; color:#777; grid-column:1/-1;">No rooms found.</p>
        <?php endif; ?>
    </div>

    
    <?php if ($totalPages>1): ?>
        <div class="pagination">
            <?php for($i=1;$i<=$totalPages;$i++): ?>
                <a href="browse_rooms.php?page=<?= $i ?>&sort_by=<?= $sort_by ?>" class="<?= $i==$page?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

</div>


<a href="home.php" class="floating-back">← Home</a>

</body>
</html>
