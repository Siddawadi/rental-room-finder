<?php
session_start();
require_once 'config.php';


if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}


$stmt = $pdo->query("SELECT * FROM rooms ORDER BY id DESC");
$rooms = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Rooms</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
       
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; color: #333; }
        .container { max-width: 1400px; margin: 50px auto; padding: 20px; background: #fff; border-radius: 12px; box-shadow: 0 12px 25px rgba(0,0,0,0.08); }
        h2 { margin-bottom: 20px; color: #1f3c88; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; overflow-x: auto; }
        th, td { padding: 12px 15px; border-bottom: 1px solid #ddd; text-align: left; vertical-align: top; }
        th { background-color: #1f3c88; color: #fff; text-transform: uppercase; letter-spacing: 0.05em; }
        tr:hover { background-color: #f5f7fa; }
        img { border-radius: 8px; object-fit: cover; }
        .button { padding: 6px 12px; border-radius: 6px; text-decoration: none; color: #fff; font-size: 0.9em; transition: all 0.2s ease; display: inline-block; }
        .edit { background-color: #28a745; }
        .edit:hover { background-color: #218838; }
        .delete { background-color: #dc3545; }
        .delete:hover { background-color: #c82333; }
        .back { display: inline-block; margin-top: 20px; text-decoration: none; color: #007bff; font-weight: 500; }
        .back:hover { text-decoration: underline; }
       
        @media screen and (max-width: 1200px) {
            table { font-size: 0.9em; }
        }
        @media screen and (max-width: 900px) {
            .container { padding: 15px; }
            th, td { padding: 8px; }
        }
        @media screen and (max-width: 700px) {
            table { display: block; overflow-x: auto; white-space: nowrap; }
        }
    </style>
</head>
<body>
<div class="container">
    <h2>All Rooms</h2>

    <?php if(count($rooms) === 0): ?>
        <p style="color: #555;">No rooms added yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Owner Name</th>
                    <th>Location</th>
                    <th>Price</th>
                    <th>Bedrooms</th>
                    <th>Bathrooms</th>
                    <th>Kitchen</th>
                    <th>Living Room</th>
                    <th>Floor</th>
                    <th>Flat Type</th>
                    <th>Facilities</th>
                    <th>Photo</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($rooms as $room): ?>
                    <tr>
                        <td><?= htmlspecialchars($room['id']) ?></td>
                        <td><?= htmlspecialchars($room['owner_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($room['location'] ?? 'N/A') ?></td>
                        <td>₹<?= number_format($room['price'] ?? 0, 2) ?></td>
                        <td><?= htmlspecialchars($room['bedrooms'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($room['bathrooms'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($room['kitchen'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($room['living_room'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($room['floor'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($room['house_type'] ?? '-') ?></td>
                        <td>
                            <?php
                                $facilities = [];
                                if(!empty($room['wifi']) && $room['wifi'] === 'Available') $facilities[] = 'WiFi';
                                if(!empty($room['parking']) && $room['parking'] === 'Available') $facilities[] = 'Parking';
                                if(!empty($room['water']) && $room['water'] === 'Available') $facilities[] = 'Water Supply';
                                if(!empty($room['garden']) && $room['garden'] === 'Available') $facilities[] = 'Garden/Balcony/Terrace';
                                if(!empty($room['pets']) && $room['pets'] === 'Allowed') $facilities[] = 'Pets Allowed';
                                if(!empty($room['electricity']) && $room['electricity'] === 'Available') $facilities[] = 'Electricity/Backup';
                                echo !empty($facilities) ? implode(', ', $facilities) : '-';
                            ?>
                        </td>
                        <td>
                            <?php if(!empty($room['photo']) && file_exists('uploads/'.$room['photo'])): ?>
                                <img src="uploads/<?= htmlspecialchars($room['photo']) ?>" alt="Room Photo" width="80" height="60">
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit_room.php?id=<?= $room['id'] ?>" class="button edit">Edit</a>
                            <a href="delete_room.php?id=<?= $room['id'] ?>" class="button delete" onclick="return confirm('Are you sure you want to delete this room?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <a href="dashboard.php" class="back">← Back to Dashboard</a>
</div>
</body>
</html>
