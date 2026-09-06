<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/db.php';

$action = $_POST['action'] ?? '';

if ($action === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $requested_role = $_POST['role'] ?? 'user';
    
    // Define secret key required to register as admin
    $admin_secret_key = 'ADMIN123SECRET'; 

    if (empty($username) || empty($email) || empty($password)) {
        die("Please fill in all required fields.");
    }

    $role = 'user';
    if ($requested_role === 'admin') {
        $provided_key = $_POST['admin_key'] ?? '';
        if ($provided_key !== $admin_secret_key) {
            die("Unauthorized: Invalid Admin Security Passkey.");
        }
        $role = 'admin';
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $hashedPassword, $role]);

        header("Location: ../index.php?registered=success");
        exit;
    } catch (PDOException $e) {
        die("Error registering user: " . $e->getMessage());
    }
}

if ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['profile_pic'] = $user['profile_pic'];

        if ($user['role'] === 'admin') {
            header("Location: ../admin/admin_dashboard.php");
        } else {
            header("Location: ../dashboard.php");
        }
        exit;
    } else {
        die("Invalid email or password.");
    }
}
?>