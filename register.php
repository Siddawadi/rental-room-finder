<?php
session_start();
require_once 'config.php';


$name     = $_POST['name'] ?? '';
$email    = $_POST['email'] ?? '';
$phone_no = $_POST['phone_no'] ?? '';
$message  = '';

if (isset($_POST['register'])) {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $phone_no = trim($_POST['phone_no'] ?? '');
    
  

if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($phone_no)) {
    $message = "All fields are required.";
} elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
    $message = "Name can contain letters and spaces only.";
} elseif (strlen($name) > 50) {
    $message = "Name cannot exceed 50 characters.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $message = "Please enter a valid email address.";
} elseif ($password !== $confirm_password) {
    $message = "Passwords do not match.";
} elseif (!preg_match("/^\+977\s\d{10}$/", $phone_no)) {
    $message = "Phone number must start with +977, followed by a space, and then 10 digits.";
} elseif (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/", $password)) {
    $message = "Password must be at least 8 characters with uppercase, lowercase, number, and special character.";
} 
else {
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone_no = ?");
        $stmt->execute([$email, $phone_no]);
        $existing = $stmt->fetch();

        if ($existing) {
            if ($existing['email'] === $email) $message = "Email already registered.";
            elseif ($existing['phone_no'] === $phone_no) $message = "Phone number already registered.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone_no) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashed, $phone_no]);

            header("Location: index.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Room Finder Nepal - Register</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --primary-color: #1e88e5;
    --primary-hover: #1565c0;
    --background-gradient: linear-gradient(135deg, #e3f2fd, #bbdefb);
    --input-bg: #f7f9fc;
    --input-border: #d9e4f0;
    --text-color: #333;
    --error-color: #d32f2f;
    --border-radius: 0.6rem;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Inter', sans-serif;
}

body {
    background: var(--background-gradient);
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

.register-form-wrapper {
    background: #fff;
    padding: 2.5rem 2.2rem;
    border-radius: 1rem;
    width: 100%;
    max-width: 450px;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
    transition: all 0.3s ease-in-out;
}

.register-form-wrapper:hover {
    transform: translateY(-5px);
    box-shadow: 0 16px 50px rgba(0, 0, 0, 0.16);
}

h2 {
    text-align: center;
    margin-bottom: 1.5rem;
    font-weight: 700;
    font-size: 1.75rem;
    color: var(--text-color);
}

.form-group {
    margin-bottom: 1.25rem;
    position: relative;
}

label {
    display: block;
    margin-bottom: 0.4rem;
    font-size: 0.95rem;
    font-weight: 500;
    color: var(--text-color);
}

.input-field {
    width: 100%;
    padding: 0.75rem 2.5rem 0.75rem 1rem;
    border-radius: var(--border-radius);
    border: 1px solid var(--input-border);
    background: var(--input-bg);
    font-size: 0.95rem;
    color: var(--text-color);
    transition: border-color 0.25s, background 0.25s;
}

.input-field:focus {
    border-color: var(--primary-color);
    outline: none;
    background: #fff;
}

.eye-icon {
    position: absolute;
    top: 50%;
    right: 12px;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 1.2rem;
    color: var(--primary-color);
}

.register-button {
    width: 100%;
    padding: 0.85rem;
    border: none;
    border-radius: var(--border-radius);
    background: var(--primary-color);
    color: #fff;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s;
}

.register-button:hover {
    background: var(--primary-hover);
}

.message {
    color: var(--error-color);
    text-align: center;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

.footer-text {
    text-align: center;
    margin-top: 1rem;
    font-size: 0.9rem;
}

.footer-text a {
    color: var(--primary-color);
    font-weight: 600;
    text-decoration: none;
}

.footer-text a:hover {
    text-decoration: underline;
}
</style>
</head>

<body>
<div class="register-form-wrapper">
    <h2>Create Your Account</h2>

    <?php if($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" class="input-field"
                   value="<?= htmlspecialchars($name ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" class="input-field"
                   value="<?= htmlspecialchars($email ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="input-field" required>
            <span class="eye-icon" onclick="togglePassword('password')">&#128065;</span>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="input-field" required>
            <span class="eye-icon" onclick="togglePassword('confirm_password')">&#128065;</span>
        </div>

        <div class="form-group">
            <label for="phone_no">Phone Number</label>
            <input type="text" id="phone_no" name="phone_no" class="input-field"
                   value="<?= htmlspecialchars($phone_no ?? '') ?>" required>
        </div>

        <button type="submit" name="register" class="register-button">Register</button>
    </form>

    <p class="footer-text">
        Already have an account? 
        <a href="index.php">Log in</a>
    </p>
</div>

<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    input.type = input.type === "password" ? "text" : "password";
}
</script>
</body>
</html>
