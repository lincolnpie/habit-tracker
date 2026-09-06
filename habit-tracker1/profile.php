<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$profile_error = '';
$profile_success = '';
$pwd_error = '';
$pwd_success = '';

// Fetch current user details
$stmt = $pdo->prepare("SELECT username, email, points FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 1. UPDATE USERNAME & EMAIL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');

    if (empty($username) || empty($email)) {
        $profile_error = "Username and email fields cannot be empty.";
    } else {
        try {
            $update = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            $update->execute([$username, $email, $user_id]);
            $_SESSION['username'] = $username;
            $user['username'] = $username;
            $user['email'] = $email;
            $profile_success = "Profile details updated successfully!";
        } catch (PDOException $e) {
            $profile_error = "Email or username is already taken.";
        }
    }
}

// 2. UPDATE PASSWORD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $pwd_error = "Please fill in all password fields.";
    } elseif ($new_password !== $confirm_password) {
        $pwd_error = "New passwords do not match.";
    } else {
        $stmtPass = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmtPass->execute([$user_id]);
        $userData = $stmtPass->fetch(PDO::FETCH_ASSOC);

        if ($userData && password_verify($current_password, $userData['password'])) {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $updatePass = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updatePass->execute([$new_hash, $user_id]);
            $pwd_success = "Password updated successfully!";
        } else {
            $pwd_error = "Incorrect current password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RoutineHub - Profile</title>
    <style>
        body {
            background-color: #f8fafc;
            font-family: system-ui, -apple-system, sans-serif;
            margin: 0;
            color: #1e293b;
        }

        .navbar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 14px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-nav {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 8px 14px;
            border-radius: 8px;
            font-weight: 600;
            color: #334155;
            text-decoration: none;
            font-size: 14px;
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
        }

        .container {
            max-width: 500px;
            margin: 40px auto;
            padding: 0 16px;
        }

        .profile-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            overflow: hidden;
        }

        .profile-header {
            background: #1e293b;
            color: #ffffff;
            padding: 32px 24px;
            text-align: center;
        }

        .avatar {
            width: 70px;
            height: 70px;
            background: #0284c7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 700;
            margin: 0 auto 12px;
            border: 3px solid rgba(255,255,255,0.2);
        }

        .profile-body {
            padding: 24px;
        }

        .form-section {
            margin-bottom: 28px;
        }

        .form-section h3 {
            margin: 0 0 16px;
            font-size: 16px;
            color: #0f172a;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 8px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .btn-submit {
            background: #0284c7;
            color: #ffffff;
            border: none;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            width: 100%;
        }

        .alert {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 14px;
            font-weight: 500;
        }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="dashboard.php" class="logo">RoutineHub</a>
        <div class="nav-right">
            <a href="dashboard.php" class="btn-nav">← Dashboard</a>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="profile-card">
            <div class="profile-header">
                <div class="avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
                <h2 style="margin: 0; font-size: 20px;"><?= htmlspecialchars($user['username']) ?></h2>
                <p style="margin: 4px 0 0; color: #94a3b8; font-size: 14px;"><?= htmlspecialchars($user['email']) ?></p>
            </div>

            <div class="profile-body">
                
                <!-- EDIT PROFILE INFO -->
                <form action="profile.php" method="POST" class="form-section">
                    <h3>Edit Profile Details</h3>

                    <?php if (!empty($profile_error)): ?>
                        <div class="alert alert-error"><?= htmlspecialchars($profile_error) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($profile_success)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($profile_success) ?></div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>

                    <button type="submit" name="update_profile" class="btn-submit">Save Changes</button>
                </form>

                <!-- CHANGE PASSWORD -->
                <form action="profile.php" method="POST" class="form-section" style="margin-bottom: 0;">
                    <h3>Change Password</h3>

                    <?php if (!empty($pwd_error)): ?>
                        <div class="alert alert-error"><?= htmlspecialchars($pwd_error) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($pwd_success)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($pwd_success) ?></div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>

                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" required>
                    </div>

                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>

                    <button type="submit" name="change_password" class="btn-submit">Update Password</button>
                </form>

            </div>
        </div>
    </div>

</body>
</html>