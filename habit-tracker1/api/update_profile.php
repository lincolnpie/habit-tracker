<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!empty($password)) {
    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
    $stmt->execute([$username, $email, $hashed, $user_id]);
} else {
    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
    $stmt->execute([$username, $email, $user_id]);
}

$_SESSION['username'] = $username;
$_SESSION['email'] = $email;

header('Location: ../profile.php?status=updated');
exit;
?>