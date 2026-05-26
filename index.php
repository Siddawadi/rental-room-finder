<?php
ini_set('display_errors', 1); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);

session_start();
require_once 'config.php';

$message = '';


if (!empty($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $message = "Email and password are required.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['is_paid'] = $user['is_paid']; 

            if (!empty($_SESSION['redirect_after_login'])) {
                $redirect_url = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header("Location: $redirect_url");
                exit;
            }

           
            if ($user['role'] === 'admin') {
                header('Location: dashboard.php');
            } else {
                header('Location: home.php');
            }
            exit;
        } else {
            $message = "Invalid login credentials.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Room Finder Nepal - Login</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
:root {
    --bg-color: rgba(187, 219, 232, 1);
    --card-color: #ffffff;
    --input-color: #f2f2f2;
    --text-dark: #000000;
    --text-muted: #555555;
    --border-color: #cccccc;
    --accent-color: #4f46e5;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Inter', sans-serif; background-color: var(--bg-color); color: var(--text-dark); min-height: 100vh; display: flex; justify-content: center; align-items: center; }
#login-container { width: 100%; max-width: 400px; padding: 1rem; }
.login-form-wrapper { background-color: var(--card-color); padding: 2rem; border-radius: 1rem; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
.title-text { font-size: 2rem; text-align: center; margin-bottom: 1.5rem; font-weight: 700; }
.form-group { margin-bottom: 1rem; }
.input-label { display: block; margin-bottom: 0.3rem; color: var(--text-muted); font-weight: 500; }
.input-field { width: 100%; padding: 0.6rem 1rem; background-color: var(--input-color); border: 1px solid var(--border-color); border-radius: 0.5rem; color: var(--text-dark); }
.input-field:focus { border-color: var(--accent-color); outline: none; box-shadow: 0 0 3px var(--accent-color); }
.login-button { width: 100%; padding: 0.7rem; font-size: 1rem; border-radius: 9999px; color: #ffffff; background-color: var(--accent-color); border: none; cursor: pointer; margin-top: 1.2rem; transition: 0.2s; }
.login-button:hover { opacity: 0.9; }
.message { color: #ff3b3b; text-align: center; margin-bottom: 1rem; }
.register-link-text { margin-top: 1.5rem; text-align: center; font-size: 0.875rem; color: var(--text-muted); }
.register-link { color: var(--accent-color); text-decoration: none; }
.register-link:hover { text-decoration: underline; }
.show-password { display: flex; align-items: center; margin-top: 0.5rem; cursor: pointer; font-size: 0.875rem; color: var(--text-muted); }
.show-password input { margin-right: 0.5rem; }
</style>
</head>

<body>
<div id="login-container">
    <div class="login-form-wrapper">

        <h2 class="title-text">Welcome Back</h2>

        <?php if($message): ?>
            <p class="message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email" class="input-label">Email</label>
                <input type="text" id="email" name="email" class="input-field"
                       placeholder="you@example.com" required>
            </div>

            <div class="form-group">
                <label for="password" class="input-label">Password</label>
                <input type="password" id="password" name="password" class="input-field"
                       placeholder="Enter password" required>
                <label class="show-password">
                    <input type="checkbox" onclick="togglePassword()"> Show Password
                </label>
            </div>

            <button type="submit" name="login" class="login-button">LOGIN</button>
        </form>

        <p class="register-link-text">
            Don't have an account yet?
            <a href="register.php" class="register-link">Sign up</a>
        </p>

    </div>
</div>

<script>
function togglePassword() {
    const pwField = document.getElementById('password');
    pwField.type = pwField.type === 'password' ? 'text' : 'password';
}
</script>

</body>
</html>
