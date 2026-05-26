<?php
session_start();
require_once 'config.php';

if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $owner_name  = trim($_POST["owner_name"]);
    $owner_phone = trim($_POST["owner_phone"]);
    $location    = trim($_POST["location"]);
    $price       = trim($_POST["price"]);
    $description = trim($_POST["description"]);
    $latitude    = trim($_POST["latitude"]);
    $longitude   = trim($_POST["longitude"]);

    $bedrooms    = trim($_POST["bedrooms"]);
    $bathrooms   = trim($_POST["bathrooms"]);
    $kitchen     = trim($_POST["kitchen"]);
    $living_room = trim($_POST["living_room"]);
    $floor       = trim($_POST["floor"]);

    $wifi        = isset($_POST["wifi"]) ? "Available" : "Not Available";
    $parking     = isset($_POST["parking"]) ? "Available" : "Not Available";
    $water       = isset($_POST["water"]) ? "Available" : "Not Available";
    $garden      = isset($_POST["garden"]) ? "Available" : "Not Available";
    $pets        = isset($_POST["pets"]) ? "Allowed" : "Not Allowed";
    $electricity = isset($_POST["electricity"]) ? "Available" : "Not Available";

    function validateRoomCount($value, $label) {
        if ($value === "") return true;
        if (!is_numeric($value) || $value < 0 || $value > 3) {
            return "$label must be between 0 and 3.";
        }
        return true;
    }

    if (!preg_match("/^[A-Za-z ]+$/", $owner_name)) {
        $message = "Owner name must contain only letters and spaces.";
    } elseif (strlen($owner_name) > 50) {
        $message = "Owner name cannot exceed 50 characters.";
    } elseif (!preg_match("/^\+977 ?[0-9]{9,10}$/", $owner_phone)) {
        $message = "Owner phone must be in format: +977XXXXXXXXX";
    } elseif (strlen($location) > 100) {
        $message = "Location cannot exceed 100 characters.";
    } elseif (!is_numeric($price) || $price < 0 || $price > 100000) {
        $message = "Price must be between 0 and 100000.";
    } elseif (($err = validateRoomCount($bedrooms, "Bedrooms")) !== true) {
        $message = $err;
    } elseif (($err = validateRoomCount($bathrooms, "Bathrooms")) !== true) {
        $message = $err;
    } elseif (($err = validateRoomCount($kitchen, "Kitchen")) !== true) {
        $message = $err;
    } elseif (($err = validateRoomCount($living_room, "Living Room")) !== true) {
        $message = $err;
    } elseif (str_word_count($description) > 100) {
        $message = "Description cannot exceed 100 words.";
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
            if (!move_uploaded_file($_FILES["photo"]["tmp_name"], "uploads/$photo_name")) {
                $message = "Failed to upload photo.";
            }
        }
    }

    if (empty($message)) {
        $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM rooms");
        $row = $stmt->fetch();
        $next_id = $row ? $row["max_id"] + 1 : 1;
        $room_number = "RN-" . str_pad($next_id, 4, "0", STR_PAD_LEFT);

        $stmt = $pdo->prepare("INSERT INTO rooms 
        (owner_name, owner_phone, room_number, location, price, description, photo,
        bedrooms, bathrooms, kitchen, living_room, floor,
        wifi, parking, water, garden, pets, electricity, latitude, longitude)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $success = $stmt->execute([
            $owner_name, $owner_phone, $room_number, $location, $price, $description,
            $photo_name, $bedrooms, $bathrooms, $kitchen, $living_room, $floor,
            $wifi, $parking, $water, $garden, $pets, $electricity, $latitude, $longitude
        ]);

        $message = $success 
            ? "Room added successfully! Room No: $room_number" 
            : "Failed to add room.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Room | Room Finder Nepal</title>
<style>
body {font-family:Arial; background:#f4f6f9; margin:0;}
.container {max-width:700px; margin:40px auto; background:#fff; padding:30px; border-radius:12px; box-shadow:0 12px 30px rgba(0,0,0,0.08);}
input, textarea, select {width:100%; padding:10px; margin:10px 0; border-radius:6px; border:1px solid #ccc;}
button {width:100%; padding:12px; background:#4facfe; border:none; color:#fff; border-radius:6px; font-weight:bold; cursor:pointer;}
button:hover { background:#3b8de0; }
.error {color:#d00; margin-bottom:10px;}
.success {color:green; margin-bottom:10px;}
#map {width:100%; height:300px; border-radius:8px; margin-top:10px;}
.back-btn {display:inline-block; margin-bottom:15px; padding:10px 15px; background:#555; color:white; border-radius:6px; text-decoration:none;}
.back-btn:hover { background:#333; }
img#preview {max-width:100%; border-radius:8px; margin-top:10px;}
</style>
</head>
<body>

<div class="container">
<a href="dashboard.php" class="back-btn">← Back to Dashboard</a>

<h2>Add Room</h2>

<?php if ($message): ?>
    <div class="<?= $success ? 'success' : 'error' ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" id="roomForm">

    <label>Owner Name</label>
    <input type="text" name="owner_name" required>

    <label>Owner Phone</label>
    <input type="text" name="owner_phone" placeholder="+977XXXXXXXXX" required>

    <label>Location</label>
    <input type="text" name="location" maxlength="100" required>

    <label>Price (Max 100000)</label>
    <input type="number" name="price" max="100000" required>

    <label>Description (Max 100 words)</label>
    <textarea name="description" rows="4"></textarea>

    <label>Bedrooms (Max 2)</label>
    <input type="number" name="bedrooms" max="2">

    <label>Bathrooms</label>
    <input type="number" name="bathrooms">

    <label>Kitchen</label>
    <input type="number" name="kitchen">

    <label>Living Room</label>
    <input type="number" name="living_room">

    <label>Floor</label>
    <input type="number" name="floor">

    

    <h3>Facilities</h3>
    <label><input type="checkbox" name="wifi"> WiFi</label>
    <label><input type="checkbox" name="parking"> Parking</label>
    <label><input type="checkbox" name="water"> Water</label>
    <label><input type="checkbox" name="garden"> Garden</label>
    <label><input type="checkbox" name="pets"> Pets Allowed</label>
    <label><input type="checkbox" name="electricity"> Electricity Backup</label>

    <label>Photo</label>
    <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp" required>
    <img id="preview" src="#" alt="Image Preview" style="display:none;"/>

    <label>Google Maps Directions URL</label>
    <input type="text" id="gmaps_url" placeholder="Paste Google Maps URL">
    <button type="button" onclick="extractCoords()">Set Map Location</button>

    <input type="hidden" name="latitude" id="latitude" required>
    <input type="hidden" name="longitude" id="longitude" required>

    <div id="map"></div>

    <button type="submit">Add Room</button>
</form>
</div>

<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap" async defer></script>
<script>
let map, marker;
function initMap() {
    const defaultLoc = {lat:27.7172, lng:85.3240};
    map = new google.maps.Map(document.getElementById("map"), { center: defaultLoc, zoom: 13 });
    marker = new google.maps.Marker({ position: defaultLoc, map: map, draggable: true });

    google.maps.event.addListener(marker, "dragend", function() {
        document.getElementById("latitude").value = marker.getPosition().lat();
        document.getElementById("longitude").value = marker.getPosition().lng();
    });

    google.maps.event.addListener(map, "click", function(event) {
        marker.setPosition(event.latLng);
        document.getElementById("latitude").value = event.latLng.lat();
        document.getElementById("longitude").value = event.latLng.lng();
    });

    document.getElementById("latitude").value = defaultLoc.lat;
    document.getElementById("longitude").value = defaultLoc.lng;
}


function extractCoords() {
    const url = document.getElementById('gmaps_url').value;
    if (!url) { alert('Please paste a Google Maps URL.'); return; }
    try {
        const atMatch = url.match(/@([0-9.\-]+),([0-9.\-]+)/);
        if (atMatch) {
            const latitude = parseFloat(atMatch[1]);
            const longitude = parseFloat(atMatch[2]);
            setMapCoords(latitude, longitude);
            alert('Coordinates extracted successfully!');
            return;
        }
        const regex = /!2d([0-9.\-]+)!3d([0-9.\-]+)/g;
        const matches = [...url.matchAll(regex)];
        if (matches.length > 0) {
            const last = matches[matches.length-1];
            const longitude = parseFloat(last[1]);
            const latitude = parseFloat(last[2]);
            setMapCoords(latitude, longitude);
            alert('Coordinates extracted successfully!');
            return;
        }
        alert('Could not extract coordinates. Please check the URL.');
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


document.querySelector('input[name="photo"]').addEventListener('change', function(event){
    const reader = new FileReader();
    reader.onload = function(){
        const img = document.getElementById('preview');
        img.src = reader.result;
        img.style.display = 'block';
    }
    reader.readAsDataURL(event.target.files[0]);
});
</script>
</body>
</html>