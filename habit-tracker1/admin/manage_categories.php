<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Add category logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_name'])) {
    $category_name = trim($_POST['category_name']);
    if (!empty($category_name)) {
        $stmt = $pdo->prepare("INSERT INTO categories (category_name) VALUES (?) ON DUPLICATE KEY UPDATE category_name=category_name");
        $stmt->execute([$category_name]);
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Categories - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>

<main class="dashboard-container">
    <a href="admin_dashboard.php" style="color:#6366f1;">&larr; Back to Admin Dashboard</a>
    <h2>Manage Habit Categories</h2>

    <div class="habit-card" style="margin-bottom: 20px;">
        <form method="POST" style="display:flex; gap:10px;">
            <input type="text" name="category_name" placeholder="New Category Name" required style="flex:1; padding:8px; border-radius:4px; border:1px solid #334155; background:#0f172a; color:#fff;">
            <button type="submit" class="btn-primary">Add Category</button>
        </form>
    </div>

    <div class="habit-card">
        <h3>Existing Categories</h3>
        <ul>
            <?php foreach ($categories as $cat): ?>
                <li style="margin-bottom: 8px;"><?= htmlspecialchars($cat['category_name']) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</main>

</body>
</html>