<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Automatic Points Engine: Process pending logs into verified points
$pendingLogsStmt = $pdo->query("SELECT id, user_id FROM habit_logs WHERE status = 'pending'");
$pendingLogs = $pendingLogsStmt->fetchAll();

foreach ($pendingLogs as $log) {
    // Award 10 points per verified habit log
    $points = 10;
    
    // Update log status to verified
    $updateLog = $pdo->prepare("UPDATE habit_logs SET status = 'verified', points_awarded = ? WHERE id = ?");
    $updateLog->execute([$points, $log['id']]);

    // Add points to user account
    $updateUser = $pdo->prepare("UPDATE users SET points = points + ? WHERE id = ?");
    $updateUser->execute([$points, $log['user_id']]);
}

// Fetch Leaderboard Standings
$leaderboardStmt = $pdo->query("
    SELECT username, points, profile_pic 
    FROM users 
    WHERE role = 'user' 
    ORDER BY points DESC 
    LIMIT 10
");
$leaderboard = $leaderboardStmt->fetchAll();

// Fetch Progress Report Confirmation for current user
$user_id = $_SESSION['user_id'];
$reportStmt = $pdo->prepare("
    SELECT 
        COUNT(hl.id) as total_completed,
        SUM(hl.points_awarded) as total_points
    FROM habit_logs hl
    WHERE hl.user_id = ? AND hl.status = 'verified'
");
$reportStmt->execute([$user_id]);
$userReport = $reportStmt->fetch();
?>

<?php include 'includes/header.php'; ?>

<main class="dashboard-container">
    <div class="dashboard-header">
        <h1>Community Leaderboard & Progress Report</h1>
    </div>

    <!-- Progress Report Confirmation Section -->
    <div class="habit-card" style="margin-bottom: 24px;">
        <h3>Your Progress Report Confirmation</h3>
        <div class="info-row" style="margin-top: 10px;">
            <span>Verified Log Completed: <strong><?= $userReport['total_completed'] ?? 0 ?></strong></span>
            <span>Total Points Earned: <strong><?= $userReport['total_points'] ?? 0 ?> PTS</strong></span>
        </div>
    </div>

    <!-- Leaderboard Table -->
    <div class="habit-card">
        <h3>Top Achievers</h3>
        <table style="width: 100%; border-collapse: collapse; margin-top: 15px; text-align: left;">
            <thead>
                <tr style="border-bottom: 1px solid #334155;">
                    <th style="padding: 10px;">Rank</th>
                    <th style="padding: 10px;">User</th>
                    <th style="padding: 10px;">Points</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leaderboard)): ?>
                    <tr><td colspan="3" style="padding: 10px;">No rankings available yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($leaderboard as $index => $player): ?>
                        <tr style="border-bottom: 1px solid #1e293b;">
                            <td style="padding: 10px;">#<?= $index + 1 ?></td>
                            <td style="padding: 10px; display: flex; align-items: center; gap: 10px;">
                                <img src="<?= htmlspecialchars($player['profile_pic']) ?>" style="width:28px; height:28px; border-radius:50%;">
                                <?= htmlspecialchars($player['username']) ?>
                            </td>
                            <td style="padding: 10px;"><strong><?= $player['points'] ?> PTS</strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include 'includes/footer.php'; ?>