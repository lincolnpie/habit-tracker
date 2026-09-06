<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/db.php';

// Auth Check
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// CREATE HABIT
if ($action === 'create') {
    $title = trim($_POST['title'] ?? '');
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    $target_frequency = $_POST['target_frequency'] ?? 'daily';
    $description = trim($_POST['description'] ?? '');

    if (!empty($title)) {
        $stmt = $pdo->prepare("INSERT INTO habits (user_id, category_id, title, description, target_frequency) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $category_id, $title, $description, $target_frequency]);
    }
}

// UPDATE HABIT
if ($action === 'update') {
    $habit_id = $_POST['habit_id'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    $target_frequency = $_POST['target_frequency'] ?? 'daily';
    $description = trim($_POST['description'] ?? '');

    if (!empty($habit_id) && !empty($title)) {
        $stmt = $pdo->prepare("UPDATE habits SET title = ?, category_id = ?, target_frequency = ?, description = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $category_id, $target_frequency, $description, $habit_id, $user_id]);
    }
}

// DELETE HABIT
if ($action === 'delete') {
    $habit_id = $_POST['habit_id'] ?? '';
    if (!empty($habit_id)) {
        $stmt = $pdo->prepare("DELETE FROM habits WHERE id = ? AND user_id = ?");
        $stmt->execute([$habit_id, $user_id]);
    }
}

// Redirect back to dashboard after processing
header('Location: ../dashboard.php');
exit;
?>