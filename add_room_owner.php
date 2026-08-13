<?php
// add_room_owner.php - For Owners to list rooms with Google Maps
session_start();
require_once 'config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if user is verified owner
$userStmt = $pdo->prepare("SELECT is_owner, owner_paid, name, phone FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch();

if (!$user['is_owner'] || !$user['owner_paid']) {
    $_SESSION['error'] = "You must be a verified owner to list rooms.";
    header('Location: home.php');
    exit;
}

$message = "";
$success = false;

// Preserve form data
$form_data = [
    'owner_name' => '',
    'owner_phone' => '',
    'location' => '',
    'price' => '',
    'description' => '',
    'latitude' => '27.7172',
    'longitude' => '85.3240',
    'bedrooms' => 0,
    'bathrooms' => 0,
    'kitchen' => 0,
    'living_room' => 0,
    'floor' => 0,
    'house_type' => '',
    'room_type' => '',
    'wifi' => false,
    'parking' => false,
    'water' => false,
    'garden' => false,
    'pets' => false,
    'electricity' => false
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get all form data
    $owner_name  = trim($_POST["owner_name"] ?? '');
    $owner_phone = trim($_POST["owner_phone"] ?? '');
    $location    = trim($_POST["location"] ?? '');
    $price       = trim($_POST["price"] ?? '');
    $description = trim($_POST["description"] ?? '');
    $latitude    = trim($_POST["latitude"] ?? '27.7172');
    $longitude   = trim($_POST["longitude"] ?? '85.3240');
    $house_type  = trim($_POST["house_type"] ?? '');
    $room_type   = trim($_POST["room_type"] ?? '');

    $bedrooms    = trim($_POST["bedrooms"] ?? 0);
    $bathrooms   = trim($_POST["bathrooms"] ?? 0);
    $kitchen     = trim($_POST["kitchen"] ?? 0);
    $living_room = trim($_POST["living_room"] ?? 0);
    $floor       = trim($_POST["floor"] ?? 0);

    $wifi        = isset($_POST["wifi"]);
    $parking     = isset($_POST["parking"]);
    $water       = isset($_POST["water"]);
    $garden      = isset($_POST["garden"]);
    $pets        = isset($_POST["pets"]);
    $electricity = isset($_POST["electricity"]);

    // Store form data for preservation
    $form_data = [
        'owner_name' => $owner_name,
        'owner_phone' => $owner_phone,
        'location' => $location,
        'price' => $price,
        'description' => $description,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'bedrooms' => $bedrooms,
        'bathrooms' => $bathrooms,
        'kitchen' => $kitchen,
        'living_room' => $living_room,
        'floor' => $floor,
        'house_type' => $house_type,
        'room_type' => $room_type,
        'wifi' => $wifi,
        'parking' => $parking,
        'water' => $water,
        'garden' => $garden,
        'pets' => $pets,
        'electricity' => $electricity
    ];

    // Validation
    if (empty($owner_name)) {
        $message = "Owner name is required.";
    } elseif (strlen($owner_name) > 50) {
        $message = "Owner name cannot exceed 50 characters.";
    } elseif (!preg_match("/^[A-Za-z ]+$/", $owner_name)) {
        $message = "Owner name must contain only letters and spaces.";
    } elseif (empty($owner_phone)) {
        $message = "Owner phone is required.";
    } elseif (!preg_match("/^\+977 ?[0-9]{9,10}$/", $owner_phone)) {
        $message = "Owner phone must be in format: +977XXXXXXXXX";
    } elseif (empty($location)) {
        $message = "Location is required.";
    } elseif (strlen($location) > 100) {
        $message = "Location cannot exceed 100 characters.";
    } elseif (empty($price) || !is_numeric($price) || $price < 0 || $price > 100000) {
        $message = "Price must be between 0 and 100000.";
    } elseif (str_word_count($description) > 100) {
        $message = "Description cannot exceed 100 words.";
    } elseif (!is_numeric($bedrooms) || $bedrooms < 0 || $bedrooms > 3) {
        $message = "Bedrooms must be between 0 and 3.";
    } elseif (!is_numeric($bathrooms) || $bathrooms < 0 || $bathrooms > 3) {
        $message = "Bathrooms must be between 0 and 3.";
    } elseif (!is_numeric($kitchen) || $kitchen < 0 || $kitchen > 3) {
        $message = "Kitchen must be between 0 and 3.";
    } elseif (!is_numeric($living_room) || $living_room < 0 || $living_room > 3) {
        $message = "Living Room must be between 0 and 3.";
    } elseif (!is_numeric($floor) || $floor < 0) {
        $message = "Floor must be a positive number.";
    } elseif (!is_numeric($latitude) || !is_numeric($longitude)) {
        $message = "Please pick a valid map location.";
    } elseif (!isset($_FILES["photo"]) || empty($_FILES["photo"]["name"])) {
        $message = "Room photo is required.";
    } else {
        $allowed_ext = ["jpg", "jpeg", "png", "webp"];
        $ext = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_ext)) {
            $message = "Only JPG, JPEG, PNG, WEBP images are allowed.";
        } else {
            $photo_name = uniqid("room_", true) . "." . $ext;
            // Create uploads directory if it doesn't exist
            if (!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }
            if (!move_uploaded_file($_FILES["photo"]["tmp_name"], "uploads/$photo_name")) {
                $message = "Failed to upload photo.";
            }
        }
    }

    if (empty($message)) {
        // Get next room number
        $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM rooms");
        $row = $stmt->fetch();
        $next_id = $row ? $row["max_id"] + 1 : 1;
        $room_number = "RN-" . str_pad($next_id, 4, "0", STR_PAD_LEFT);

        $wifi_val = $wifi ? "Available" : "Not Available";
        $parking_val = $parking ? "Available" : "Not Available";
        $water_val = $water ? "Available" : "Not Available";
        $garden_val = $garden ? "Available" : "Not Available";
        $pets_val = $pets ? "Allowed" : "Not Allowed";
        $electricity_val = $electricity ? "Available" : "Not Available";

        // ✅ EXACTLY MATCH YOUR DATABASE COLUMNS (27 columns, id is auto-increment)
        $stmt = $pdo->prepare("INSERT INTO rooms 
        (room_number, room_type, price, status, owner_name, owner_phone, 
        location, description, photo, bedrooms, bathrooms, kitchen, living_room, floor, 
        house_type, wifi, parking, water, garden, pets, electricity, 
        latitude, longitude, google_maps_url, owner_id)
        VALUES (?, ?, ?, 'available', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', ?)");

        $success = $stmt->execute([
            $room_number,           // room_number
            $room_type,             // room_type
            $price,                 // price
            $owner_name,            // owner_name
            $owner_phone,           // owner_phone
            $location,              // location
            $description,           // description
            $photo_name,            // photo
            $bedrooms,              // bedrooms
            $bathrooms,             // bathrooms
            $kitchen,               // kitchen
            $living_room,           // living_room
            $floor,                 // floor
            $house_type,            // house_type
            $wifi_val,              // wifi
            $parking_val,           // parking
            $water_val,             // water
            $garden_val,            // garden
            $pets_val,              // pets
            $electricity_val,       // electricity
            $latitude,              // latitude
            $longitude,             // longitude
            $user_id                // owner_id
        ]);

        if ($success) {
            $_SESSION['success'] = "Room added successfully! Room No: $room_number";
            header('Location: home.php');
            exit;
        } else {
            $message = "Failed to add room. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Room | Room Finder Nepal</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:Arial; background:#f4f6f9; padding:20px; }
.container { max-width:700px; margin:0 auto; background:#fff; padding:30px; border-radius:12px; box-shadow:0 12px 30px rgba(0,0,0,0.08); }
h2 { margin-bottom:20px; color:#2c3e50; }
.form-group { margin-bottom:15px; }
label { display:block; margin-bottom:5px; font-weight:600; color:#555; }
input, textarea, select { width:100%; padding:10px; border-radius:6px; border:1px solid #ccc; font-size:14px; }
textarea { min-height:80px; resize:vertical; }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:15px; }
.btn { padding:12px 30px; background:#4facfe; color:#fff; border:none; border-radius:6px; font-weight:bold; cursor:pointer; font-size:16px; }
.btn:hover { background:#3b8de0; }
.btn-secondary { background:#6c757d; }
.btn-secondary:hover { background:#5a6268; }
.error { color:#d00; padding:10px; background:#f8d7da; border-radius:5px; margin-bottom:15px; }
.success { color:green; padding:10px; background:#d4edda; border-radius:5px; margin-bottom:15px; }
.back-btn { display:inline-block; margin-bottom:15px; padding:10px 15px; background:#555; color:white; border-radius:6px; text-decoration:none; }
.back-btn:hover { background:#333; }
img#preview { max-width:100%; border-radius:8px; margin-top:10px; display:none; }
.section-title { margin-top:20px; padding-top:15px; border-top:2px solid #eee; color:#2c3e50; }
.checkbox-group { display:flex; flex-wrap:wrap; gap:15px; margin:10px 0; }
.checkbox-group label { display:flex; align-items:center; gap:5px; font-weight:normal; cursor:pointer; }
#map { width:100%; height:300px; border-radius:8px; margin-top:10px; }
.map-btn { padding:8px 15px; background:#28a745; color:white; border:none; border-radius:5px; cursor:pointer; margin-top:5px; }
.map-btn:hover { background:#218838; }

@media (max-width:768px) {
    .form-row { grid-template-columns:1fr; }
    .container { padding:15px; }
}
</style>
</head>
<body>

<div class="container">
<a href="home.php" class="back-btn">← Back to Home</a>

<h2>🏠 Add New Room</h2>

<?php if ($message): ?>
    <div class="<?= $success ? 'success' : 'error' ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" id="roomForm">

    <!-- Owner Info -->
    <h3 class="section-title">Owner Information</h3>
    <div class="form-row">
        <div class="form-group">
            <label>Owner Name *</label>
            <input type="text" name="owner_name" value="<?= htmlspecialchars($form_data['owner_name'] ?: $user['name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Owner Phone *</label>
            <input type="text" name="owner_phone" value="<?= htmlspecialchars($form_data['owner_phone'] ?: $user['phone'] ?? '') ?>" placeholder="+977XXXXXXXXX" required>
        </div>
    </div>

    <!-- Room Details -->
    <h3 class="section-title">Room Details</h3>
    <div class="form-group">
        <label>Room Type</label>
        <select name="room_type">
            <option value="">Select Room Type</option>
            <option value="1 BHK" <?= $form_data['room_type'] == '1 BHK' ? 'selected' : '' ?>>1 BHK</option>
            <option value="2 BHK" <?= $form_data['room_type'] == '2 BHK' ? 'selected' : '' ?>>2 BHK</option>
            <option value="3 BHK" <?= $form_data['room_type'] == '3 BHK' ? 'selected' : '' ?>>3 BHK</option>
            <option value="Apartment" <?= $form_data['room_type'] == 'Apartment' ? 'selected' : '' ?>>Apartment</option>
            <option value="Studio" <?= $form_data['room_type'] == 'Studio' ? 'selected' : '' ?>>Studio</option>
            <option value="Flat" <?= $form_data['room_type'] == 'Flat' ? 'selected' : '' ?>>Flat</option>
        </select>
    </div>

    <div class="form-group">
        <label>House Type</label>
        <select name="house_type">
            <option value="">Select</option>
            <option value="1 Room" <?= $form_data['house_type'] == '1 Room' ? 'selected' : '' ?>>1 Room</option>
            <option value="2 Rooms" <?= $form_data['house_type'] == '2 Rooms' ? 'selected' : '' ?>>2 Rooms</option>
            <option value="3 Rooms" <?= $form_data['house_type'] == '3 Rooms' ? 'selected' : '' ?>>3 Rooms</option>
            <option value="4 Rooms" <?= $form_data['house_type'] == '4 Rooms' ? 'selected' : '' ?>>4 Rooms</option>
            <option value="5+ Rooms" <?= $form_data['house_type'] == '5+ Rooms' ? 'selected' : '' ?>>5+ Rooms</option>
        </select>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>Price (Rs.) *</label>
            <input type="number" name="price" min="0" max="100000" value="<?= htmlspecialchars($form_data['price']) ?>" required placeholder="e.g., 15000">
        </div>
        <div class="form-group">
            <label>Location *</label>
            <input type="text" name="location" value="<?= htmlspecialchars($form_data['location']) ?>" required placeholder="e.g., Kathmandu, Nepal">
        </div>
    </div>

    <div class="form-group">
        <label>Description (Max 100 words)</label>
        <textarea name="description" rows="4" placeholder="Describe your room..."><?= htmlspecialchars($form_data['description']) ?></textarea>
    </div>

    <!-- Room Layout -->
    <h3 class="section-title">Room Layout</h3>
    <div class="form-row">
        <div class="form-group">
            <label>Bedrooms (Max 3)</label>
            <input type="number" name="bedrooms" min="0" max="3" value="<?= htmlspecialchars($form_data['bedrooms']) ?>">
        </div>
        <div class="form-group">
            <label>Bathrooms (Max 3)</label>
            <input type="number" name="bathrooms" min="0" max="3" value="<?= htmlspecialchars($form_data['bathrooms']) ?>">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label>Kitchen (Max 3)</label>
            <input type="number" name="kitchen" min="0" max="3" value="<?= htmlspecialchars($form_data['kitchen']) ?>">
        </div>
        <div class="form-group">
            <label>Living Room (Max 3)</label>
            <input type="number" name="living_room" min="0" max="3" value="<?= htmlspecialchars($form_data['living_room']) ?>">
        </div>
    </div>
    <div class="form-group">
        <label>Floor</label>
        <input type="number" name="floor" min="0" value="<?= htmlspecialchars($form_data['floor']) ?>">
    </div>

    <!-- Facilities -->
    <h3 class="section-title">Facilities</h3>
    <div class="checkbox-group">
        <label><input type="checkbox" name="wifi" <?= $form_data['wifi'] ? 'checked' : '' ?>> WiFi</label>
        <label><input type="checkbox" name="parking" <?= $form_data['parking'] ? 'checked' : '' ?>> Parking</label>
        <label><input type="checkbox" name="water" <?= $form_data['water'] ? 'checked' : '' ?>> Water</label>
        <label><input type="checkbox" name="garden" <?= $form_data['garden'] ? 'checked' : '' ?>> Garden</label>
        <label><input type="checkbox" name="pets" <?= $form_data['pets'] ? 'checked' : '' ?>> Pets Allowed</label>
        <label><input type="checkbox" name="electricity" <?= $form_data['electricity'] ? 'checked' : '' ?>> Electricity Backup</label>
    </div>

    <!-- Photo Upload -->
    <h3 class="section-title">Photo</h3>
    <div class="form-group">
        <label>Room Photo *</label>
        <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp" required onchange="previewImage(this)">
        <img id="preview" src="#" alt="Image Preview" style="display:none; max-width:100%; border-radius:8px; margin-top:10px;"/>
    </div>

    <!-- Location Map -->
    <h3 class="section-title">Location</h3>
    <div class="form-group">
        <label>Google Maps Directions URL</label>
        <input type="text" id="gmaps_url" placeholder="Paste Google Maps URL">
        <button type="button" class="map-btn" onclick="extractCoords()">📍 Set Map Location</button>
    </div>

    <input type="hidden" name="latitude" id="latitude" value="<?= htmlspecialchars($form_data['latitude']) ?>" required>
    <input type="hidden" name="longitude" id="longitude" value="<?= htmlspecialchars($form_data['longitude']) ?>" required>

    <div id="map"></div>

    <button type="submit" class="btn" style="margin-top:15px;">✅ List Room</button>
    <a href="home.php" class="btn btn-secondary" style="margin-left:10px;">Cancel</a>
</form>

</div>

<!-- Google Maps API -->
<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap" async defer></script>
<script>
let map, marker;

function initMap() {
    const defaultLat = parseFloat(document.getElementById('latitude').value) || 27.7172;
    const defaultLng = parseFloat(document.getElementById('longitude').value) || 85.3240;
    const defaultLoc = {lat: defaultLat, lng: defaultLng};
    
    map = new google.maps.Map(document.getElementById("map"), { 
        center: defaultLoc, 
        zoom: 13 
    });
    
    marker = new google.maps.Marker({ 
        position: defaultLoc, 
        map: map, 
        draggable: true 
    });

    google.maps.event.addListener(marker, "dragend", function() {
        document.getElementById("latitude").value = marker.getPosition().lat();
        document.getElementById("longitude").value = marker.getPosition().lng();
    });

    google.maps.event.addListener(map, "click", function(event) {
        marker.setPosition(event.latLng);
        document.getElementById("latitude").value = event.latLng.lat();
        document.getElementById("longitude").value = event.latLng.lng();
    });
}

function extractCoords() {
    const url = document.getElementById('gmaps_url').value;
    if (!url) { 
        alert('Please paste a Google Maps URL.'); 
        return; 
    }
    try {
        // Try @lat,lng format
        const atMatch = url.match(/@([0-9.\-]+),([0-9.\-]+)/);
        if (atMatch) {
            const latitude = parseFloat(atMatch[1]);
            const longitude = parseFloat(atMatch[2]);
            setMapCoords(latitude, longitude);
            alert('✅ Coordinates extracted successfully!');
            return;
        }
        // Try !2d!3d format
        const regex = /!2d([0-9.\-]+)!3d([0-9.\-]+)/g;
        const matches = [...url.matchAll(regex)];
        if (matches.length > 0) {
            const last = matches[matches.length-1];
            const longitude = parseFloat(last[1]);
            const latitude = parseFloat(last[2]);
            setMapCoords(latitude, longitude);
            alert('✅ Coordinates extracted successfully!');
            return;
        }
        alert('❌ Could not extract coordinates. Please check the URL.');
    } catch (e) {
        alert('Error parsing URL: ' + e.message);
    }
}

function setMapCoords(lat, lng){
    document.getElementById('latitude').value = lat;
    document.getElementById('longitude').value = lng;
    if(marker && map){
        const pos = {lat: lat, lng: lng};
        marker.setPosition(pos);
        map.setCenter(pos);
        map.setZoom(15);
    }
}

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