<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = isset($_GET['registered']) ? 'Account created successfully! Please log in.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $error = "Database connection error.";
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
    <link rel="stylesheet" href="assets/css/index.css">
    <title>RoutineHub - Log In</title>
</head>
<body>

    <div class="auth-card">
        <div class="auth-brand">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            RoutineHub
        </div>

       
        <p class="subtitle">Log in to track your routines and daily progress.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form action="index.php" method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="enter@email.com" required autofocus>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-submit">Log In</button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="register.php">Register Here</a>
        </div>
    </div>

</body>
</html>