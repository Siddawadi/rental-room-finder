<?php
session_start();
require_once 'config.php';


if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$room_id = $_GET['id'] ?? null;
if (!$room_id) {
    header('Location: view_rooms.php');
    exit;
}


$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch();
if (!$room) {
    header('Location: view_rooms.php');
    exit;
}

$message = '';


if (isset($_POST['update'])) {
    $room_number = trim($_POST['room_number']);
    $owner_name = trim($_POST['owner_name']);
    $location = trim($_POST['location']);
    $price = trim($_POST['price']);
    $description = trim($_POST['description']);

    
    $uploaded_photos = [];
    if (!empty($_FILES['photos']['name'][0])) {
        foreach ($_FILES['photos']['name'] as $key => $name) {
            $tmp_name = $_FILES['photos']['tmp_name'][$key];
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $new_name = uniqid() . '.' . $ext;
            if (move_uploaded_file($tmp_name, "uploads/$new_name")) {
                $uploaded_photos[] = $new_name;
            }
        }
        
        $all_photos = $room['photo'] ? explode(',', $room['photo']) : [];
        $all_photos = array_merge($all_photos, $uploaded_photos);
        $photos_str = implode(',', $all_photos);
    } else {
        $photos_str = $room['photo'];
    }

   
    $stmt = $pdo->prepare("UPDATE rooms SET room_number=?, owner_name=?, location=?, price=?, description=?, photo=? WHERE id=?");
    $stmt->execute([$room_number, $owner_name, $location, $price, $description, $photos_str, $room_id]);
    $message = "Room updated successfully!";
   
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Room</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
body { font-family: 'Inter', sans-serif; background:#f4f4f4; margin:0; padding:0; }
.container { max-width:800px; margin:40px auto; padding:20px; background:#fff; border-radius:8px; box-shadow:0 5px 15px rgba(0,0,0,0.1);}
h1 { text-align:center; margin-bottom:30px; }
form { display:flex; flex-direction:column; gap:15px; }
input[type=text], input[type=number], textarea { padding:10px; font-size:1rem; border:1px solid #ccc; border-radius:5px; width:100%; }
textarea { resize: vertical; height:120px; }
button { padding:12px; font-size:1rem; background:#4facfe; color:#fff; border:none; border-radius:5px; cursor:pointer; }
button:hover { background:#3b99e0; }
.message { text-align:center; color:green; margin-bottom:10px; }
.photos img { width:100px; height:100px; object-fit:cover; margin-right:10px; border-radius:5px; }
</style>
</head>
<body>

<div class="container">
    <h1>Edit Room</h1>

    <?php if($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
        <label>Room Number</label>
        <input type="text" name="room_number" value="<?= htmlspecialchars($room['room_number']) ?>" required>

        <label>Owner Name</label>
        <input type="text" name="owner_name" value="<?= htmlspecialchars($room['owner_name']) ?>" required>

        <label>Location</label>
        <input type="text" name="location" value="<?= htmlspecialchars($room['location']) ?>" required>

        <label>Price</label>
        <input type="number" name="price" value="<?= htmlspecialchars($room['price']) ?>" required>

        <label>Description (Admin only)</label>
        <textarea name="description" required><?= htmlspecialchars($room['description']) ?></textarea>

        <label>Photos (You can add multiple)</label>
        <input type="file" name="photos[]" multiple>

        <?php if($room['photo']): ?>
            <div class="photos">
                <?php foreach(explode(',', $room['photo']) as $photo): ?>
                    <img src="uploads/<?= htmlspecialchars($photo) ?>" alt="Room Photo">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <button type="submit" name="update">Update Room</button>
    </form>
</div>

</body>
</html>
