<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// -------------------------------------------------------------------
// FETCH USER INFO (POINTS & USERNAME)
// -------------------------------------------------------------------
$user_points = 0;
$username = $_SESSION['username'] ?? 'User';

try {
    $userStmt = $pdo->prepare("SELECT username, points FROM users WHERE id = ?");
    $userStmt->execute([$user_id]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $user_points = (int)($user['points'] ?? 0);
        $username = $user['username'] ?? $username;
    }
} catch (PDOException $e) {
    // Fallback if query fails
}

// -------------------------------------------------------------------
// AUTO-MISS YESTERDAY'S UNFINISHED ROUTINES (-5 PTS EACH)
// -------------------------------------------------------------------
$yesterday = date('Y-m-d', strtotime('-1 day'));
try {
    $missedStmt = $pdo->prepare("
        SELECT r.id 
        FROM routines r
        LEFT JOIN routine_logs l 
            ON r.id = l.routine_id 
            AND l.logged_date = :yesterday
        WHERE r.user_id = :user_id 
          AND r.created_at IS NOT NULL
          AND DATE(r.created_at) <= :yesterday
          AND l.id IS NULL
    ");
    $missedStmt->execute(['yesterday' => $yesterday, 'user_id' => $user_id]);
    $missedRoutines = $missedStmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($missedRoutines)) {
        $insertLog = $pdo->prepare("INSERT INTO routine_logs (routine_id, status, logged_date) VALUES (?, 'skipped', ?)");
        foreach ($missedRoutines as $rId) {
            $insertLog->execute([$rId, $yesterday]);
        }

        $penalty = count($missedRoutines) * 5;
        $deductPts = $pdo->prepare("UPDATE users SET points = GREATEST(0, points - ?) WHERE id = ?");
        $deductPts->execute([$penalty, $user_id]);

        $user_points = max(0, $user_points - $penalty);
    }
} catch (PDOException $e) {
    // Silently handle if column doesn't exist yet
}

// -------------------------------------------------------------------
// FETCH TODAY'S ROUTINES & LOGS
// -------------------------------------------------------------------
$routines = [];
try {
    $stmt = $pdo->prepare("
        SELECT r.*, l.status AS today_status 
        FROM routines r
        LEFT JOIN routine_logs l 
            ON r.id = l.routine_id 
            AND l.logged_date = :today
        WHERE r.user_id = :user_id
        ORDER BY r.id DESC
    ");
    $stmt->execute([
        'user_id' => $user_id,
        'today'   => $today
    ]);
    $routines = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $routines = [];
}

// Categorize routines into Today's Lists
$todo_routines    = [];
$done_routines    = [];
$skipped_routines = [];

foreach ($routines as $routine) {
    $status = $routine['today_status'] ?? '';
    if ($status === 'done') {
        $done_routines[] = $routine;
    } elseif ($status === 'skipped') {
        $skipped_routines[] = $routine;
    } else {
        $todo_routines[] = $routine;
    }
}

// -------------------------------------------------------------------
// 7-DAY CHART DATA STATS
// -------------------------------------------------------------------
$chartLabels = [];
$chartDoneData = [];
$chartMissedData = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('D', strtotime($date));

    // Fetch done count
    try {
        $sDone = $pdo->prepare("
            SELECT COUNT(*) FROM routine_logs l
            INNER JOIN routines r ON l.routine_id = r.id
            WHERE r.user_id = :user_id AND l.logged_date = :date AND l.status = 'done'
        ");
        $sDone->execute(['user_id' => $user_id, 'date' => $date]);
        $chartDoneData[] = (int)$sDone->fetchColumn();
    } catch (PDOException $e) {
        $chartDoneData[] = 0;
    }

    // Fetch missed/skipped count
    try {
        $sMissed = $pdo->prepare("
            SELECT COUNT(*) FROM routine_logs l
            INNER JOIN routines r ON l.routine_id = r.id
            WHERE r.user_id = :user_id AND l.logged_date = :date AND l.status = 'skipped'
        ");
        $sMissed->execute(['user_id' => $user_id, 'date' => $date]);
        $chartMissedData[] = (int)$sMissed->fetchColumn();
    } catch (PDOException $e) {
        $chartMissedData[] = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RoutineHub - Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { position: relative; z-index: 1; transition: background 0.2s, color 0.2s; }
        .container, .main-content, .sidebar, .navbar { position: relative; z-index: 2; pointer-events: auto !important; }
        .action-btn, .btn-add, .user-btn, .dropdown-menu a { cursor: pointer !important; pointer-events: auto !important; }
        
        .dropdown { position: relative; display: inline-block; }
        .dropdown-menu { display: none; position: absolute; right: 0; top: 100%; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); min-width: 140px; z-index: 1000; }
        .dropdown-menu.show { display: block; }
        .dropdown-menu a { display: block; padding: 10px 16px; color: #334155; text-decoration: none; font-size: 14px; }
        .dropdown-menu a:hover { background: #f1f5f9; }

        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: #ffffff; padding: 24px; border-radius: 8px; width: 100%; max-width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .modal-box h3 { margin-top: 0; color: #0f172a; }
        .modal-box input { width: 100%; padding: 10px; margin: 12px 0 20px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; }
        .modal-actions button { padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; }
        .btn-cancel { background: #94a3b8; color: white; }
        .btn-submit { background: #0284c7; color: white; }
        
        .nav-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .pts-badge {
            font-weight: 700;
            color: #eab308;
            font-size: 14px;
        }

        .theme-btn {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer !important;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .theme-btn:hover {
            background: #e2e8f0;
        }

        .user-btn {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 8px 14px;
            border-radius: 8px;
            font-weight: 600;
            color: #334155;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            transition: background 0.2s;
        }

        .user-btn:hover {
            background: #e2e8f0;
        }

        .btn-logout {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #fca5a5;
            padding: 8px 14px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s ease;
        }

        .btn-logout:hover {
            background-color: #dc2626;
            color: #ffffff;
            border-color: #dc2626;
        }

        /* DARK THEME STYLES */
        [data-theme="dark"] body {
            background-color: #0f172a;
            color: #f8fafc;
        }

        [data-theme="dark"] .navbar,
        [data-theme="dark"] .routine-card,
        [data-theme="dark"] .sidebar,
        [data-theme="dark"] .modal-box,
        [data-theme="dark"] .calendar-box,
        [data-theme="dark"] .stats-box {
            background-color: #1e293b;
            color: #f8fafc;
            border-color: #334155;
        }

        [data-theme="dark"] .section-title,
        [data-theme="dark"] .stat-label,
        [data-theme="dark"] .cal-day-name {
            color: #94a3b8;
        }

        [data-theme="dark"] .user-btn,
        [data-theme="dark"] .theme-btn {
            background: #334155;
            border-color: #475569;
            color: #f8fafc;
        }

        [data-theme="dark"] .user-btn:hover,
        [data-theme="dark"] .theme-btn:hover {
            background: #475569;
        }

        [data-theme="dark"] .modal-box input {
            background: #0f172a;
            border-color: #475569;
            color: #f8fafc;
        }
    </style>
</head>
<body>

<!-- NAVBAR HEADER -->
<nav class="navbar">
    <div class="logo">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        RoutineHub
    </div>
    
    <!-- INTEGRATED NAV-RIGHT WITH DARK MODE TOGGLE -->
    <div class="nav-right">
        <!-- DYNAMIC POINTS BADGE -->
        <div class="pts-badge">★ <?= $user_points ?> pts</div>

        <!-- DARK THEME TOGGLE BUTTON -->
        <button id="themeToggle" class="theme-btn" title="Toggle Theme">🌙</button>

        <!-- PROFILE BUTTON -->
        <a href="profile.php" class="user-btn">👤 <?= htmlspecialchars($username) ?></a>

        <!-- DIRECT LOGOUT BUTTON -->
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

    <!-- MAIN DASHBOARD CONTENT -->
    <div class="container">
        
        <!-- LEFT CONTENT: ROUTINES -->
        <main class="main-content">
            <h2>Today</h2>

            <!-- TODO SECTION -->
            <div class="section-title">🕒 TODO</div>
            <div class="card-list">
                <?php if (empty($todo_routines)): ?>
                    <div class="empty-text">Good job! Let's do it everyday!</div>
                <?php else: ?>
                    <?php foreach ($todo_routines as $r): ?>
                        <div class="routine-card">
                            <span><?= htmlspecialchars($r['title'] ?? 'Routine') ?></span>
                            <div class="actions">
                                <a href="api/actions.php?action=complete&id=<?= $r['id'] ?>" class="action-btn btn-check" title="Complete">✓</a>
                                <a href="api/actions.php?action=skip&id=<?= $r['id'] ?>" class="action-btn btn-skip" title="Skip">⏩</a>
                                <a href="api/actions.php?action=delete&id=<?= $r['id'] ?>" class="action-btn btn-delete" title="Delete" onclick="return confirm('Delete this routine?')">🗑</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- DONE SECTION -->
            <div class="section-title">✓ DONE</div>
            <div class="card-list">
                <?php if (empty($done_routines)): ?>
                    <div class="empty-text">Let's get going!</div>
                <?php else: ?>
                    <?php foreach ($done_routines as $r): ?>
                        <div class="routine-card done">
                            <span><?= htmlspecialchars($r['title'] ?? 'Routine') ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- SKIPPED SECTION -->
            <div class="section-title">⏩ SKIPPED/MISSED</div>
            <div class="card-list">
                <?php if (empty($skipped_routines)): ?>
                    <div class="empty-text">Well done! No routine skipped!</div>
                <?php else: ?>
                    <?php foreach ($skipped_routines as $r): ?>
                        <div class="routine-card skipped">
                            <span><?= htmlspecialchars($r['title'] ?? 'Routine') ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>

        <!-- RIGHT SIDEBAR -->
        <aside class="sidebar">
            <button class="btn-add" id="openAddModalBtn">+ Add Routine</button>

            <!-- CALENDAR -->
            <div class="calendar-box">
                <div class="cal-header">
                    <span>Prev</span>
                    <span><?= strtoupper(date('F Y')) ?></span>
                    <span>Next</span>
                </div>
                <div class="cal-grid">
                    <div class="cal-day-name">S</div><div class="cal-day-name">M</div><div class="cal-day-name">T</div>
                    <div class="cal-day-name">W</div><div class="cal-day-name">T</div><div class="cal-day-name">F</div><div class="cal-day-name">S</div>
                    <?php 
                        $daysInMonth = date('t');
                        $currentDay = date('j');
                        for($d = 1; $d <= $daysInMonth; $d++): 
                    ?>
                        <div class="cal-date <?= $d == $currentDay ? 'active' : '' ?>"><?= $d ?></div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- STATS BOX -->
            <div class="stats-box">
                <div class="stats-header">📈 STATISTICS & ACTIVITY</div>
                <div class="stats-grid-3">
                    <div class="stat-card">
                        <div class="stat-value" style="color: #22c55e;">0</div>
                        <div class="stat-label">STREAK</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" style="color: #0284c7;"><?= count($done_routines) ?></div>
                        <div class="stat-label">DONE</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" style="color: #ef4444;"><?= count($skipped_routines) ?></div>
                        <div class="stat-label">MISSED</div>
                    </div>
                </div>

                <div class="chart-wrapper" style="height: 180px; margin-top: 15px;">
                    <canvas id="activityChart"></canvas>
                </div>
            </div>
        </aside>

    </div>

    <!-- MODAL FOR ADDING ROUTINE -->
    <div class="modal-overlay" id="addModal">
        <div class="modal-box">
            <h3>Add New Routine</h3>
            <form action="api/actions.php?action=create" method="POST">
                <input type="text" name="title" placeholder="Routine Name (e.g., Drink Water)" required autofocus>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" id="closeAddModalBtn">Cancel</button>
                    <button type="submit" class="btn-submit">Save Routine</button>
                </div>
            </form>
        </div>
    </div>

    <!-- INTERACTIVE SCRIPTS -->
    <script>
        // Dark Theme Toggle Logic
        const themeToggleBtn = document.getElementById('themeToggle');
        const savedTheme = localStorage.getItem('theme');

        if (savedTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            if (themeToggleBtn) themeToggleBtn.textContent = '☀️';
        }

        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', () => {
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                if (isDark) {
                    document.documentElement.removeAttribute('data-theme');
                    localStorage.setItem('theme', 'light');
                    themeToggleBtn.textContent = '🌙';
                } else {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    localStorage.setItem('theme', 'dark');
                    themeToggleBtn.textContent = '☀️';
                }
            });
        }

        // Profile Dropdown
        const userBtn = document.getElementById('userDropdownBtn');
        const dropdownMenu = document.getElementById('userDropdownMenu');

        if (userBtn && dropdownMenu) {
            userBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdownMenu.classList.toggle('show');
            });

            document.addEventListener('click', () => {
                dropdownMenu.classList.remove('show');
            });
        }

        // Add Routine Modal
        const openModalBtn = document.getElementById('openAddModalBtn');
        const closeModalBtn = document.getElementById('closeAddModalBtn');
        const addModal = document.getElementById('addModal');

        if (openModalBtn && addModal) {
            openModalBtn.addEventListener('click', () => {
                addModal.classList.add('active');
            });
        }

        if (closeModalBtn && addModal) {
            closeModalBtn.addEventListener('click', () => {
                addModal.classList.remove('active');
            });
        }

        // Chart.js Setup
        const ctx = document.getElementById('activityChart')?.getContext('2d');
        if (ctx) {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            const textColor = isDark ? '#94a3b8' : '#334155';
            const gridColor = isDark ? '#334155' : '#cbd5e1';

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($chartLabels) ?>,
                    datasets: [
                        {
                            label: 'Done',
                            data: <?= json_encode($chartDoneData) ?>,
                            backgroundColor: '#22c55e',
                            borderRadius: 4
                        },
                        {
                            label: 'Missed',
                            data: <?= json_encode($chartMissedData) ?>,
                            backgroundColor: '#ef4444',
                            borderRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: textColor,
                                font: { size: 11, weight: '700' },
                                boxWidth: 10,
                                padding: 8
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: textColor, font: { size: 10, weight: '600' } }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, color: textColor, font: { size: 10, weight: '600' } },
                            grid: { color: gridColor }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>