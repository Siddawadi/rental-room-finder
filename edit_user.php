<?php
session_start();
require_once 'config.php';

if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

if (empty($_GET['id'])) {
    header('Location: users.php');
    exit;
}

$user_id = intval($_GET['id']);
$message = '';


$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = trim($_POST['password']);

  
    if (empty($name) || empty($email) || empty($role)) {
        $message = "All fields are required.";
    }elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
    $message = "Name can contain letters and spaces only.";
} elseif (strlen($name) > 50) {
    $message = "Name cannot be more than 50 characters.";
}

elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } elseif (!empty($password) && !preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/", $password)) {
        $message = "Password must be at least 8 characters with uppercase, lowercase, number, and special character.";
    } else {
        
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role=?, password=? WHERE id=?");
            $success = $stmt->execute([$name, $email, $role, $hashedPassword, $user_id]);
        } else {
            
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?");
            $success = $stmt->execute([$name, $email, $role, $user_id]);
        }

        if ($success) {
            $message = "User updated successfully!";
            $user['name'] = $name;
            $user['email'] = $email;
            $user['role'] = $role;
        } else {
            $message = "Failed to update user.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit User</title>
<style>
body { font-family:'Inter',sans-serif; background:#f5f7fb; margin:0; padding:20px; }
.container { max-width:500px; margin:50px auto; background:#fff; padding:25px; border-radius:12px; box-shadow:0 5px 15px rgba(0,0,0,0.05);}
input, select { width:100%; padding:10px; margin:10px 0; border-radius:6px; border:1px solid #ccc; }
button { padding:12px; background:#4facfe; color:#fff; border:none; border-radius:6px; width:100%; cursor:pointer; }
.msg { color:red; margin-bottom:10px; }
.success { color:green; }
.eye-icon { position:absolute; right:15px; top:38px; cursor:pointer; font-size:1.1rem; color:#4facfe; }
.password-wrapper { position:relative; }
</style>
</head>
<body>
<div class="container">
    <h2>Edit User</h2>
    <?php if($message): ?>
        <div class="<?= strpos($message,'successfully')!==false ? 'success':'msg' ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="post">
        <label>Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" maxlength="50" required>

        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

        <label>Role</label>
        <select name="role" required>
            <option value="user" <?= $user['role']=='user'?'selected':'' ?>>User</option>
            <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
        </select>

        <label>Password (leave blank to keep current)</label>
        <div class="password-wrapper">
            <input type="password" name="password" id="password">
            <span class="eye-icon" onclick="togglePassword('password')">&#128065;</span>
        </div>

        <button type="submit">Update User</button>
    </form>
    <a href="users.php" style="display:inline-block;margin-top:15px;">← Back to Users</a>
</div>

<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    input.type = input.type === "password" ? "text" : "password";
}
</script>
</body>
</html>
