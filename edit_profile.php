<?php
session_start();
require_once 'config.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";


$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) die("User not found.");

if (isset($_POST['update'])) {

    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm_password']);

    
    if (empty($name)) {
        $error = "Name cannot be empty.";
    } elseif (strlen($name) > 50) {
        $error = "Name cannot exceed 50 characters.";
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $error = "Name can contain letters and spaces only.";
    }

  
    elseif (empty($email)) {
        $error = "Email cannot be empty.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    }

    
    elseif (!empty($password)) {

        if (strlen($password) < 8) {
            $error = "Password must be at least 8 characters.";
        } elseif (!preg_match("/[A-Z]/", $password)) {
            $error = "Password must include at least one uppercase letter.";
        } elseif (!preg_match("/[a-z]/", $password)) {
            $error = "Password must include at least one lowercase letter.";
        } elseif (!preg_match("/[0-9]/", $password)) {
            $error = "Password must include at least one number.";
        } elseif (!preg_match("/[\W_]/", $password)) {
            $error = "Password must include at least one special character.";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match.";
        }
    }

   
    if (empty($error)) {

        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET name=?, email=?, password=? WHERE id=?");
            $update->execute([$name, $email, $hashed, $user_id]);
        } else {
            $update = $pdo->prepare("UPDATE users SET name=?, email=? WHERE id=?");
            $update->execute([$name, $email, $user_id]);
        }

        $message = "Profile updated successfully!";
        $user['name'] = $name;
        $user['email'] = $email;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile</title>
    <style>
        body { font-family: Arial; background:#f4f6f9; }
        .container { max-width:500px; margin:60px auto; background:#fff; padding:25px; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.1);}
        h2 { text-align:center; margin-bottom:20px;}
        input { width:100%; padding:10px; margin-bottom:15px; border-radius:6px; border:1px solid #ccc;}
        button { width:100%; padding:12px; background:#4facfe; color:#fff; border:none; border-radius:6px; font-weight:bold;}
        button:hover { background:#428bca; cursor:pointer;}
        .msg { text-align:center; padding:10px; border-radius:6px; margin-bottom:10px;}
        .success { background:#d4edda; color:#155724;}
        .error { background:#f8d7da; color:#721c24;}
        a.back { display:block; text-align:center; margin-top:15px; text-decoration:none; color:#007bff;}
        a.back:hover { text-decoration:underline; }
    </style>
</head>
<body>

<div class="container">
    <h2>Edit Profile</h2>

    <?php if ($message): ?><div class="msg success"><?= $message ?></div><?php endif; ?>
    <?php if ($error): ?><div class="msg error"><?= $error ?></div><?php endif; ?>

    <form method="post" id="profileForm" onsubmit="return validateForm();">
        <label>Name</label>
        <input type="text" name="name" id="name" maxlength="50" 
               value="<?= htmlspecialchars($user['name']); ?>" required>

        <label>Email</label>
        <input type="email" name="email" id="email" 
               value="<?= htmlspecialchars($user['email']); ?>" required>

        <label>New Password (optional)</label>
        <input type="password" name="password" id="password" placeholder="Leave blank to keep old password">

        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" id="confirm_password" placeholder="Re-enter new password">

        <button type="submit" name="update">Update Profile</button>
    </form>

    <a href="profile.php" class="back">← Back to Profile</a>
</div>

<script>
function validateForm() {
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;

    if (name.length > 50) {
        alert("Name cannot exceed 50 characters.");
        return false;
    }
    if (!/^[A-Za-z\s]+$/.test(name)) {
        alert("Name can contain letters and spaces only.");
        return false;
    }

    const emailPattern = /^[^ ]+@[^ ]+\.[a-z]{2,}$/i;
    if (!emailPattern.test(email)) {
        alert("Invalid email format.");
        return false;
    }

    if (password) {

        if (password.length < 8) {
            alert("Password must be at least 8 characters.");
            return false;
        }
        
        if (!/[A-Z]/.test(password)) {
            alert("Password must include at least one uppercase letter.");
            return false;
        }
        if (!/[a-z]/.test(password)) {
            alert("Password must include at least one lowercase letter.");
            return false;
        }
        if (!/[0-9]/.test(password)) {
            alert("Password must include at least one number.");
            return false;
        }
        if (!/[\W_]/.test(password)) {
            alert("Password must include at least one special character.");
            return false;
        }
        if (password !== confirm) {
            alert("Passwords do not match.");
            return false;
        }
    }

    return true;
}
</script>

</body>
</html>
