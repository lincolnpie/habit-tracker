<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$habits = $pdo->query("
    SELECT h.id, h.title, h.description, u.username 
    FROM habits h 
    JOIN users u ON h.user_id = u.id 
    ORDER BY h.created_at DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Moderate Content - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>

<main class="dashboard-container">
    <a href="admin_dashboard.php" style="color:#6366f1;">&larr; Back to Admin Control Panel</a>
    <h2>Content Moderation Panel</h2>

    <div class="habit-card">
        <h3>User Created Habits</h3>
        <table style="width: 100%; border-collapse: collapse; text-align: left; margin-top: 15px;">
            <thead>
                <tr style="border-bottom: 1px solid #334155;">
                    <th style="padding: 10px;">User</th>
                    <th style="padding: 10px;">Habit Title</th>
                    <th style="padding: 10px;">Description</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($habits as $h): ?>
                    <tr style="border-bottom: 1px solid #1e293b;">
                        <td style="padding: 10px;"><?= htmlspecialchars($h['username']) ?></td>
                        <td style="padding: 10px;"><?= htmlspecialchars($h['title']) ?></td>
                        <td style="padding: 10px;"><?= htmlspecialchars($h['description'] ?? 'N/A') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>