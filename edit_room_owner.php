<?php
// edit_room_owner.php - For Owners to edit their rooms
session_start();
require_once 'config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$room_id = intval($_GET['id'] ?? 0);

if (!$room_id) {
    header('Location: my_rooms.php');
    exit;
}

// Check if user is verified owner
$userStmt = $pdo->prepare("SELECT is_owner, owner_paid, name, phone FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch();

if (!$user['is_owner'] || !$user['owner_paid']) {
    header('Location: home.php');
    exit;
}

// Get room details - verify ownership
$roomStmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ? AND owner_id = ?");
$roomStmt->execute([$room_id, $user_id]);
$room = $roomStmt->fetch();

if (!$room) {
    $_SESSION['error'] = "Room not found or you don't have permission to edit it.";
    header('Location: my_rooms.php');
    exit;
}

$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $location    = trim($_POST["location"] ?? '');
    $price       = trim($_POST["price"] ?? '');
    $description = trim($_POST["description"] ?? '');
    $status      = trim($_POST["status"] ?? 'available');
    $bedrooms    = trim($_POST["bedrooms"] ?? 0);
    $bathrooms   = trim($_POST["bathrooms"] ?? 0);
    $kitchen     = trim($_POST["kitchen"] ?? 0);
    $living_room = trim($_POST["living_room"] ?? 0);
    $floor       = trim($_POST["floor"] ?? 0);
    $house_type  = trim($_POST["house_type"] ?? '');
    $room_type   = trim($_POST["room_type"] ?? '');

    $wifi        = isset($_POST["wifi"]) ? "Available" : "Not Available";
    $parking     = isset($_POST["parking"]) ? "Available" : "Not Available";
    $water       = isset($_POST["water"]) ? "Available" : "Not Available";
    $garden      = isset($_POST["garden"]) ? "Available" : "Not Available";
    $pets        = isset($_POST["pets"]) ? "Allowed" : "Not Allowed";
    $electricity = isset($_POST["electricity"]) ? "Available" : "Not Available";

    // Validation
    if (empty($location)) {
        $message = "Location is required.";
    } elseif (!is_numeric($price) || $price < 0 || $price > 100000) {
        $message = "Price must be between 0 and 100000.";
    } elseif (!is_numeric($bedrooms) || $bedrooms < 0 || $bedrooms > 3) {
        $message = "Bedrooms must be between 0 and 3.";
    } elseif (!is_numeric($bathrooms) || $bathrooms < 0 || $bathrooms > 3) {
        $message = "Bathrooms must be between 0 and 3.";
    } elseif (!is_numeric($kitchen) || $kitchen < 0 || $kitchen > 3) {
        $message = "Kitchen must be between 0 and 3.";
    } elseif (!is_numeric($living_room) || $living_room < 0 || $living_room > 3) {
        $message = "Living Room must be between 0 and 3.";
    } else {
        // Handle photo upload if new photo is provided
        $photo_name = $room['photo']; // Keep existing photo
        if (isset($_FILES["photo"]) && !empty($_FILES["photo"]["name"])) {
            $allowed_ext = ["jpg", "jpeg", "png", "webp"];
            $ext = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_ext)) {
                $message = "Only JPG, JPEG, PNG, WEBP images are allowed.";
            } else {
                $photo_name = uniqid("room_", true) . "." . $ext;
                if (!is_dir('uploads')) {
                    mkdir('uploads', 0777, true);
                }
                if (!move_uploaded_file($_FILES["photo"]["tmp_name"], "uploads/$photo_name")) {
                    $message = "Failed to upload photo.";
                }
            }
        }
    }

    if (empty($message)) {
        $stmt = $pdo->prepare("UPDATE rooms SET 
            location = ?, price = ?, description = ?, status = ?,
            bedrooms = ?, bathrooms = ?, kitchen = ?, living_room = ?, floor = ?,
            house_type = ?, room_type = ?,
            wifi = ?, parking = ?, water = ?, garden = ?, pets = ?, electricity = ?,
            photo = ?
            WHERE id = ? AND owner_id = ?");

        $success = $stmt->execute([
            $location, $price, $description, $status,
            $bedrooms, $bathrooms, $kitchen, $living_room, $floor,
            $house_type, $room_type,
            $wifi, $parking, $water, $garden, $pets, $electricity,
            $photo_name,
            $room_id, $user_id
        ]);

        if ($success) {
            $_SESSION['success'] = "Room updated successfully!";
            header('Location: my_rooms.php?updated=1');
            exit;
        } else {
            $message = "Failed to update room.";
        }
    }
}

// Parse existing facilities for checkbox display
$wifi_checked = $room['wifi'] == 'Available';
$parking_checked = $room['parking'] == 'Available';
$water_checked = $room['water'] == 'Available';
$garden_checked = $room['garden'] == 'Available';
$pets_checked = $room['pets'] == 'Allowed';
$electricity_checked = $room['electricity'] == 'Available';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Room</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial; background: #f0f2f5; padding: 20px; }
        .container { max-width: 700px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { margin-bottom: 20px; color: #2c3e50; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        textarea { min-height: 80px; resize: vertical; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .btn { padding: 12px 30px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #0069d9; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .error { color: #dc3545; padding: 10px; background: #f8d7da; border-radius: 5px; margin-bottom: 15px; }
        .success { color: #155724; padding: 10px; background: #d4edda; border-radius: 5px; margin-bottom: 15px; }
        .back { margin-top: 15px; display: inline-block; color: #007bff; text-decoration: none; }
        .back:hover { text-decoration: underline; }
        .section-title { margin-top: 20px; padding-top: 15px; border-top: 2px solid #eee; color: #2c3e50; }
        .checkbox-group { display: flex; flex-wrap: wrap; gap: 15px; margin: 10px 0; }
        .checkbox-group label { display: flex; align-items: center; gap: 5px; font-weight: normal; cursor: pointer; }
        img#preview { max-width: 100%; border-radius: 8px; margin-top: 10px; display: <?= $room['photo'] ? 'block' : 'none' ?>; }
        .current-photo { margin: 10px 0; }
        .current-photo img { max-width: 200px; border-radius: 8px; }
        
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .container { padding: 15px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✏️ Edit Room</h1>
        
        <?php if ($message): ?>
            <div class="<?= $success ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <!-- Room Details -->
            <h3 class="section-title">Room Details</h3>
            
            <div class="form-group">
                <label>Room Number</label>
                <input type="text" value="<?= htmlspecialchars($room['room_number']) ?>" disabled style="background: #f0f0f0;">
            </div>
            
            <div class="form-group">
                <label>Room Type</label>
                <select name="room_type">
                    <option value="">Select Room Type</option>
                    <option value="1 BHK" <?= $room['room_type'] == '1 BHK' ? 'selected' : '' ?>>1 BHK</option>
                    <option value="2 BHK" <?= $room['room_type'] == '2 BHK' ? 'selected' : '' ?>>2 BHK</option>
                    <option value="3 BHK" <?= $room['room_type'] == '3 BHK' ? 'selected' : '' ?>>3 BHK</option>
                    <option value="Apartment" <?= $room['room_type'] == 'Apartment' ? 'selected' : '' ?>>Apartment</option>
                    <option value="Studio" <?= $room['room_type'] == 'Studio' ? 'selected' : '' ?>>Studio</option>
                    <option value="Flat" <?= $room['room_type'] == 'Flat' ? 'selected' : '' ?>>Flat</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>House Type</label>
                <select name="house_type">
                    <option value="">Select</option>
                    <option value="1 Room" <?= $room['house_type'] == '1 Room' ? 'selected' : '' ?>>1 Room</option>
                    <option value="2 Rooms" <?= $room['house_type'] == '2 Rooms' ? 'selected' : '' ?>>2 Rooms</option>
                    <option value="3 Rooms" <?= $room['house_type'] == '3 Rooms' ? 'selected' : '' ?>>3 Rooms</option>
                    <option value="4 Rooms" <?= $room['house_type'] == '4 Rooms' ? 'selected' : '' ?>>4 Rooms</option>
                    <option value="5+ Rooms" <?= $room['house_type'] == '5+ Rooms' ? 'selected' : '' ?>>5+ Rooms</option>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Price (Rs.) *</label>
                    <input type="number" name="price" min="0" max="100000" value="<?= htmlspecialchars($room['price']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Location *</label>
                    <input type="text" name="location" value="<?= htmlspecialchars($room['location']) ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4"><?= htmlspecialchars($room['description']) ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="available" <?= $room['status'] == 'available' ? 'selected' : '' ?>>Available</option>
                    <option value="booked" <?= $room['status'] == 'booked' ? 'selected' : '' ?>>Booked</option>
                </select>
            </div>
            
            <!-- Room Layout -->
            <h3 class="section-title">Room Layout</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Bedrooms (Max 3)</label>
                    <input type="number" name="bedrooms" min="0" max="3" value="<?= htmlspecialchars($room['bedrooms']) ?>">
                </div>
                <div class="form-group">
                    <label>Bathrooms (Max 3)</label>
                    <input type="number" name="bathrooms" min="0" max="3" value="<?= htmlspecialchars($room['bathrooms']) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Kitchen (Max 3)</label>
                    <input type="number" name="kitchen" min="0" max="3" value="<?= htmlspecialchars($room['kitchen']) ?>">
                </div>
                <div class="form-group">
                    <label>Living Room (Max 3)</label>
                    <input type="number" name="living_room" min="0" max="3" value="<?= htmlspecialchars($room['living_room']) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Floor</label>
                <input type="number" name="floor" min="0" value="<?= htmlspecialchars($room['floor']) ?>">
            </div>
            
            <!-- Facilities -->
            <h3 class="section-title">Facilities</h3>
            <div class="checkbox-group">
                <label><input type="checkbox" name="wifi" <?= $wifi_checked ? 'checked' : '' ?>> WiFi</label>
                <label><input type="checkbox" name="parking" <?= $parking_checked ? 'checked' : '' ?>> Parking</label>
                <label><input type="checkbox" name="water" <?= $water_checked ? 'checked' : '' ?>> Water</label>
                <label><input type="checkbox" name="garden" <?= $garden_checked ? 'checked' : '' ?>> Garden</label>
                <label><input type="checkbox" name="pets" <?= $pets_checked ? 'checked' : '' ?>> Pets Allowed</label>
                <label><input type="checkbox" name="electricity" <?= $electricity_checked ? 'checked' : '' ?>> Electricity Backup</label>
            </div>
            
            <!-- Photo -->
            <h3 class="section-title">Photo</h3>
            <?php if ($room['photo'] && file_exists('uploads/' . $room['photo'])): ?>
                <div class="current-photo">
                    <label>Current Photo</label>
                    <br>
                    <img src="uploads/<?= htmlspecialchars($room['photo']) ?>" alt="Room Photo">
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label>Change Photo (leave empty to keep current)</label>
                <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp" onchange="previewImage(this)">
                <img id="preview" src="#" alt="Image Preview"/>
            </div>
            
            <button type="submit" class="btn btn-success">✅ Update Room</button>
            <a href="my_rooms.php" class="btn btn-secondary">Cancel</a>
        </form>
        
        <a href="my_rooms.php" class="back">← Back to My Rooms</a>
    </div>
    
    <script>
    function previewImage(input) {
        const preview = document.getElementById('preview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    </script>
</body>
</html>