<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($email) && !empty($password)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, points) VALUES (?, ?, ?, 0)");
            $stmt->execute([$username, $email, $hashed_password]);

            header("Location: index.php?registered=1");
            exit();
        } catch (PDOException $e) {
            $error = "Email or username already registered.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <link rel="stylesheet" href="assets/css/register.css">
    <title>RoutineHub - Create Account</title>
   
</head>
<body>

    <div class="auth-card">
        <div class="auth-brand">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            RoutineHub
        </div>

        <h2>Create an Account</h2>
        <p class="subtitle">Start building better habits today.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Juan" required autofocus>
            </div>
            

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="enter@email.com" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-submit">Register</button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="index.php">Log In Here</a>
        </div>
    </div>

</body>
</html>