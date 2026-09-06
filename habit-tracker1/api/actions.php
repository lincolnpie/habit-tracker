<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// CREATE ROUTINE
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');

    if (!empty($title)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO routines (user_id, title, created_at) VALUES (?, ?, CURRENT_DATE)");
            $stmt->execute([$user_id, $title]);
        } catch (PDOException $e) {
            // Handle error silently or log
        }
    }
}

// COMPLETE ROUTINE
if ($action === 'complete' && isset($_GET['id'])) {
    $routine_id = (int)$_GET['id'];
    $today = date('Y-m-d');

    try {
        $del = $pdo->prepare("DELETE FROM routine_logs WHERE routine_id = ? AND logged_date = ?");
        $del->execute([$routine_id, $today]);

        $stmt = $pdo->prepare("INSERT INTO routine_logs (routine_id, status, logged_date) VALUES (?, 'done', ?)");
        $stmt->execute([$routine_id, $today]);

        $addPts = $pdo->prepare("UPDATE users SET points = points + 10 WHERE id = ?");
        $addPts->execute([$user_id]);
    } catch (PDOException $e) {}
}

// SKIP ROUTINE
if ($action === 'skip' && isset($_GET['id'])) {
    $routine_id = (int)$_GET['id'];
    $today = date('Y-m-d');

    try {
        $del = $pdo->prepare("DELETE FROM routine_logs WHERE routine_id = ? AND logged_date = ?");
        $del->execute([$routine_id, $today]);

        $stmt = $pdo->prepare("INSERT INTO routine_logs (routine_id, status, logged_date) VALUES (?, 'skipped', ?)");
        $stmt->execute([$routine_id, $today]);
    } catch (PDOException $e) {}
}

// DELETE ROUTINE
if ($action === 'delete' && isset($_GET['id'])) {
    $routine_id = (int)$_GET['id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM routines WHERE id = ? AND user_id = ?");
        $stmt->execute([$routine_id, $user_id]);
    } catch (PDOException $e) {}
}

header("Location: ../dashboard.php");
exit();