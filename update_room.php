<?php
session_start();
require_once 'config.php';

if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: rooms.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$id]);
$room = $stmt->fetch();
if (!$room) {
    die("Room not found.");
}

$message = "";
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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

    if (!preg_match("/^[A-Za-z ]+$/", $owner_name)) {
        $message = "Owner name must contain only letters and spaces.";
    } elseif (strlen($owner_name) > 50) {
        $message = "Owner name cannot exceed 50 characters.";
    } elseif (!preg_match("/^\+977 ?[0-9]{9,10}$/", $owner_phone)) {
        $message = "Owner phone must be in format: +977XXXXXXXXX";
    } elseif (strlen($location) > 100) {
        $message = "Location cannot exceed 100 characters.";
    } elseif (!is_numeric($price) || $price < 0 || $price > 100000) {
        $message = "Price must be a number and cannot exceed 100,000.";
    } elseif (!ctype_digit((string)$bedrooms) || $bedrooms < 0 || $bedrooms > 3) {
        $message = "Bedrooms must be between 0 and 3.";
    } elseif (!ctype_digit((string)$bathrooms) || $bathrooms < 0 || $bathrooms > 3) {
        $message = "Bathrooms must be between 0 and 3.";
    } elseif (!ctype_digit((string)$kitchen) || $kitchen < 0 || $kitchen > 3) {
        $message = "Kitchen count must be between 0 and 3.";
    } elseif (!ctype_digit((string)$living_room) || $living_room < 0 || $living_room > 3) {
        $message = "Living room count must be between 0 and 3.";
    } elseif (str_word_count($description) > 100) {
        $message = "Description cannot exceed 100 words.";
    } elseif (!is_numeric($latitude) || !is_numeric($longitude)) {
        $message = "Please pick a valid map location.";
    }

    $photo_name = $room['photo'];
    if (!empty($_FILES["photo"]["name"])) {
        $allowed_ext = ["jpg", "jpeg", "png", "webp"];
        $ext = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_ext)) {
            $message = "Only JPG, JPEG, PNG, WEBP images are allowed.";
        } else {
            $photo_name = uniqid("room_", true) . "." . $ext;
            move_uploaded_file($_FILES["photo"]["tmp_name"], "uploads/$photo_name");
        }
    }

    if (empty($message)) {
        $update = $pdo->prepare("UPDATE rooms SET 
            owner_name=?, owner_phone=?, location=?, price=?, description=?, photo=?, 
            bedrooms=?, bathrooms=?, kitchen=?, living_room=?, floor=?,
            wifi=?, parking=?, water=?, garden=?, pets=?, electricity=?, latitude=?, longitude=?
            WHERE id=?");

        $success = $update->execute([
            $owner_name, $owner_phone, $location, $price, $description, $photo_name,
            $bedrooms, $bathrooms, $kitchen, $living_room, $floor,
            $wifi, $parking, $water, $garden, $pets, $electricity, $latitude, $longitude, $id
        ]);

        $message = $success ? "Room updated successfully!" : "Failed to update room.";

        $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id=?");
        $stmt->execute([$id]);
        $room = $stmt->fetch();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Update Room | Room Finder Nepal</title>
<style>
body{font-family:Arial;background:#f4f6f9;margin:0;padding:0}
.container{max-width:700px;margin:40px auto;background:#fff;padding:30px;border-radius:12px;box-shadow:0 12px 30px rgba(0,0,0,0.08)}
input, textarea{width:100%;padding:10px;margin:10px 0;border-radius:6px;border:1px solid #ccc}
button{width:100%;padding:12px;background:#4facfe;border:none;color:#fff;border-radius:6px;font-weight:bold;cursor:pointer}
button:hover{background:#3b8de0}
.error{color:#d00;margin-bottom:10px;}
.success{color:green;margin-bottom:10px;}
#map{width:100%;height:300px;border-radius:8px;margin-top:10px;}
.back-btn{display:inline-block;margin-bottom:15px;padding:10px 15px;background:#555;color:white;border-radius:6px;text-decoration:none;}
.back-btn:hover{background:#333}
img.preview{width:200px;height:150px;object-fit:cover;margin-bottom:10px;border-radius:6px}
</style>
</head>
<body>

<div class="container">
<a href="rooms.php" class="back-btn">← Back to Rooms</a>
<h2>Update Room</h2>

<?php if($message): ?>
<div class="<?= $success ? 'success' : 'error' ?>">
<?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">

    <label>Owner Name</label>
    <input type="text" name="owner_name" required value="<?= htmlspecialchars($room['owner_name']) ?>">

    <label>Owner Phone</label>
    <input type="text" name="owner_phone" required placeholder="+977XXXXXXXXX" value="<?= htmlspecialchars($room['owner_phone']) ?>">

    <label>Location</label>
    <input type="text" name="location" required maxlength="100" value="<?= htmlspecialchars($room['location']) ?>">

    <label>Price</label>
    <input type="number" name="price" required max="100000" value="<?= htmlspecialchars($room['price']) ?>">

    <label>Description (Max 100 words)</label>
    <textarea name="description"><?= htmlspecialchars($room['description']) ?></textarea>

    <h3>Property Details</h3>
    <label>Bedrooms (Max 3)</label>
    <input type="number" name="bedrooms" min="0" max="3" required value="<?= htmlspecialchars($room['bedrooms']) ?>">

    <label>Bathrooms (Max 3)</label>
    <input type="number" name="bathrooms" min="0" max="3" required value="<?= htmlspecialchars($room['bathrooms']) ?>">

    <label>Kitchen (Max 3)</label>
    <input type="number" name="kitchen" min="0" max="3" required value="<?= htmlspecialchars($room['kitchen']) ?>">

    <label>Living Room (Max 3)</label>
    <input type="number" name="living_room" min="0" max="3" required value="<?= htmlspecialchars($room['living_room']) ?>">

    <label>Floor</label>
    <input type="number" name="floor" value="<?= htmlspecialchars($room['floor']) ?>">

    <h3>Facilities</h3>
    <?php 
    $facilities = ['wifi'=>'WiFi','parking'=>'Parking','water'=>'Water Supply','garden'=>'Garden/Balcony','pets'=>'Pets Allowed','electricity'=>'Electricity Backup'];
    foreach($facilities as $key=>$label){
        $checked = ($room[$key]=='Available' || $room[$key]=='Allowed') ? 'checked' : '';
        echo "<label><input type='checkbox' name='$key' $checked> $label</label>";
    }
    ?>

    <label>Photo</label>
    <?php if($room['photo']): ?>
        <img class="preview" src="uploads/<?= htmlspecialchars($room['photo']) ?>" alt="Room Photo">
    <?php endif; ?>
    <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp">

    <label>Google Maps Directions URL</label>
    <input type="text" id="gmaps_url" placeholder="Paste Google Maps URL">
    <button type="button" onclick="extractCoords()">Set Map Location</button>

    <input type="hidden" name="latitude" id="latitude" required value="<?= htmlspecialchars($room['latitude']) ?>">
    <input type="hidden" name="longitude" id="longitude" required value="<?= htmlspecialchars($room['longitude']) ?>">

    <div id="map"></div>

    <button type="submit">Update Room</button>
</form>
</div>

<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap" async defer></script>
<script>
let map, marker;
function initMap() {
    const defaultLoc = {lat: <?= $room['latitude'] ?: 27.7172 ?>, lng: <?= $room['longitude'] ?: 85.3240 ?>};
    map = new google.maps.Map(document.getElementById("map"), {center: defaultLoc, zoom:15});
    marker = new google.maps.Marker({position: defaultLoc, map: map, draggable:true});

    google.maps.event.addListener(marker,"dragend", function(){
        document.getElementById("latitude").value = marker.getPosition().lat();
        document.getElementById("longitude").value = marker.getPosition().lng();
    });

    google.maps.event.addListener(map,"click", function(event){
        marker.setPosition(event.latLng);
        document.getElementById("latitude").value = event.latLng.lat();
        document.getElementById("longitude").value = event.latLng.lng();
    });
}

function extractCoords() {
    const url = document.getElementById('gmaps_url').value;
    if(!url){ alert('Please paste a Google Maps URL.'); return; }
    try{
        const atMatch = url.match(/@([0-9.\-]+),([0-9.\-]+)/);
        if(atMatch){
            setCoords(parseFloat(atMatch[1]), parseFloat(atMatch[2]));
            alert('Coordinates extracted successfully!');
            return;
        }
        const regex=/!2d([0-9.\-]+)!3d([0-9.\-]+)/g;
        const matches=[...url.matchAll(regex)];
        if(matches.length>0){
            const last=matches[matches.length-1];
            setCoords(parseFloat(last[2]), parseFloat(last[1]));
            alert('Coordinates extracted successfully!');
            return;
        }
        alert('Could not extract coordinates.');
    }catch(e){
        alert('Error parsing URL: '+e.message);
    }
}
function setCoords(lat,lng){
    document.getElementById('latitude').value=lat;
    document.getElementById('longitude').value=lng;
    if(marker && map){
        const pos={lat:lat,lng:lng};
        marker.setPosition(pos);
        map.setCenter(pos);
        map.setZoom(15);
    }
}
</script>
</body>
</html>