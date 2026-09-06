<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/db.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'log') {
    $habit_id = $_POST['habit_id'] ?? null;

    if (!empty($habit_id)) {
        // Verify habit belongs to logged-in user
        $checkStmt = $pdo->prepare("SELECT id FROM habits WHERE id = ? AND user_id = ?");
        $checkStmt->execute([$habit_id, $user_id]);
        
        if ($checkStmt->fetch()) {
            // Insert log record (defaults to verified for automatic tracking)
            $stmt = $pdo->prepare("INSERT INTO habit_logs (habit_id, user_id, status, points_awarded) VALUES (?, ?, 'verified', 10)");
            $stmt->execute([$habit_id, $user_id]);

            // Add 10 points directly to user profile
            $updateUser = $pdo->prepare("UPDATE users SET points = points + 10 WHERE id = ?");
            $updateUser->execute([$user_id]);
        }
    }
}

// Redirect immediately back to dashboard
header('Location: ../dashboard.php');
exit;
?>