<?php
session_start();
include "db.php";

if (isset($_POST['login'])) {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            // Check if banned
            if (!empty($user['is_banned'])) {
                $error = "Your account has been suspended. Contact admin.";
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                // FIXED: admin now gets routed to admin dashboard
                if ($user['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                } elseif ($user['role'] === 'candidate') {
                    header("Location: candidate_dashboard.php");
                } else {
                    header("Location: employer_dashboard.php");
                }
                exit();
            }
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No account found with that email.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | StudentGig</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',sans-serif;background:#f1f5f9;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
        .card{background:white;border-radius:16px;padding:40px;width:100%;max-width:380px;border:1px solid #e2e8f0;box-shadow:0 4px 24px rgba(0,0,0,0.06)}
        .logo{display:flex;align-items:center;gap:10px;margin-bottom:28px;justify-content:center}
        .logo-icon{width:40px;height:40px;background:#6366f1;border-radius:10px;display:flex;align-items:center;justify-content:center;color:white;font-size:18px}
        .logo-text{font-size:1.2rem;font-weight:700;color:#0f172a}
        h2{font-size:1.4rem;font-weight:700;color:#0f172a;margin-bottom:6px}
        p{color:#64748b;font-size:0.875rem;margin-bottom:24px}
        label{display:block;font-size:0.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:7px}
        input{width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:0.9rem;font-family:inherit;outline:none;transition:border-color 0.2s;background:#f8fafc;color:#0f172a;margin-bottom:18px}
        input:focus{border-color:#6366f1;background:white;box-shadow:0 0 0 3px rgba(99,102,241,0.1)}
        .btn{width:100%;padding:13px;background:#6366f1;color:white;border:none;border-radius:10px;font-size:0.95rem;font-weight:600;cursor:pointer;font-family:inherit;transition:all 0.2s}
        .btn:hover{background:#4f46e5}
        .links{display:flex;justify-content:space-between;margin-top:18px;font-size:0.8rem}
        .links a{color:#6366f1;text-decoration:none;font-weight:500}
        .error{background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;font-size:0.875rem;margin-bottom:18px;border:1px solid #fecaca}
    </style>
</head>
<body>
<div class="card">
    <div class="logo">
        <div class="logo-icon">&#9889;</div>
        <span class="logo-text">StudentGig</span>
    </div>
    <h2>Welcome back</h2>
    <p>Sign in to your account</p>

    <?php if (isset($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="you@example.com" required>
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" required>
        <button type="submit" name="login" class="btn">Sign In</button>
    </form>

    <div class="links">
        <a href="register.html">Create account</a>
        <a href="forgot_password.php">Forgot password?</a>
        </div>
        <div style="text-align:center;margin-top:12px;"><a href="admin_login.php" style="color:#94a3b8;font-size:0.75rem;text-decoration:none;">Admin login</a>
    </div>
</div>
</body>
</html>
