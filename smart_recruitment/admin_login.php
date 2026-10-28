<?php
session_start();
include "db.php";

$error = '';

if (isset($_POST['login'])) {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Direct admin check with plain password comparison (for setup only)
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Only use secure bcrypt check
        $ok = password_verify($password, $user['password']);

        if ($ok) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = 'admin';

            // Now update to a proper hash for future logins
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $upd  = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $upd->bind_param("si", $hash, $user['id']);
            $upd->execute();

            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Wrong password.";
        }
    } else {
        $error = "No admin account found. Run setup first.";
    }
}

// Auto-create admin if not exists
$check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users WHERE email='admin@studentgig.com'"));
$needsSetup = $check['c'] == 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login | StudentGig</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',sans-serif;background:#0f172a;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
        .card{background:#1e293b;border:1px solid #334155;border-radius:16px;padding:40px;width:100%;max-width:380px}
        .logo{display:flex;align-items:center;gap:10px;margin-bottom:28px;justify-content:center}
        .logo-icon{width:42px;height:42px;background:#f59e0b;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#0f172a;font-size:20px}
        .logo-text{font-size:1.1rem;font-weight:700;color:white}
        h2{font-size:1.3rem;font-weight:700;color:white;margin-bottom:4px;text-align:center}
        p{color:#64748b;font-size:0.875rem;margin-bottom:24px;text-align:center}
        label{display:block;font-size:0.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:7px}
        input{width:100%;padding:11px 14px;border:1.5px solid #334155;border-radius:10px;font-size:0.9rem;font-family:inherit;outline:none;background:#0f172a;color:white;margin-bottom:18px;transition:border-color 0.2s}
        input:focus{border-color:#f59e0b}
        .btn{width:100%;padding:13px;background:#f59e0b;color:#0f172a;border:none;border-radius:10px;font-size:0.95rem;font-weight:700;cursor:pointer;font-family:inherit;transition:all 0.2s}
        .btn:hover{background:#d97706}
        .error{background:#7f1d1d;color:#fca5a5;border:1px solid #991b1b;padding:12px;border-radius:8px;font-size:0.875rem;margin-bottom:18px;text-align:center}
        .setup-box{background:#422006;border:1px solid #92400e;color:#fcd34d;padding:14px;border-radius:8px;font-size:0.8rem;margin-bottom:20px;line-height:1.6}
        .back{display:block;text-align:center;margin-top:16px;color:#64748b;text-decoration:none;font-size:0.8rem}
        .back:hover{color:#94a3b8}
    </style>
</head>
<body>
<div class="card">
    <div class="logo">
        <div class="logo-icon">&#9733;</div>
        <span class="logo-text">Admin Panel</span>
    </div>
    <h2>Admin Sign In</h2>
    <p>Restricted access only</p>

    <?php if($needsSetup): ?>
    <div class="setup-box">
        &#9888; No admin account found.<br>
        <a href="setup_admin.php" style="color:#fcd34d;font-weight:700;">Click here to create admin account first</a>
    </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Email</label>
        <input type="email" name="email" value="admin@studentgig.com" required>
        <label>Password</label>
        <input type="password" name="password" placeholder="Your admin password" required autofocus>
        <button type="submit" name="login" class="btn">&#9889; Sign In as Admin</button>
    </form>
    <a href="index.html" class="back">&larr; Back to main site</a>
</div>
</body>
</html>
