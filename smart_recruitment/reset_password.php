<?php
session_start();
include "db.php";
$msg = ''; $error = '';
$email = $_SESSION['reset_email'] ?? '';

if (!$email) { header("Location: forgot_password.php"); exit(); }

if (isset($_POST['reset'])) {
    $code    = trim($_POST['code']);
    $newpass = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (strlen($newpass) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($newpass !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM password_resets WHERE email=? AND code=? AND expires_at > NOW()");
        $stmt->bind_param("ss", $email, $code);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $hashed = password_hash($newpass, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE users SET password=? WHERE email=?");
            $upd->bind_param("ss", $hashed, $email);
            $upd->execute();
            $del = $conn->prepare("DELETE FROM password_resets WHERE email=?");
            $del->bind_param("s", $email);
            $del->execute();
            unset($_SESSION['reset_email']);
            $msg = "Password updated! <a href='index.html' style='color:#065f46;font-weight:700;'>Login now</a>";
        } else {
            $error = "Invalid or expired code. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password | StudentGig</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',sans-serif;background:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;padding:20px}
        .brand{text-align:center;margin-bottom:40px}
        .brand h1{font-size:2.5rem;font-weight:900;letter-spacing:-3px;text-transform:uppercase}
        .card{width:100%;max-width:400px}
        label{display:block;font-weight:700;font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;margin-bottom:10px;margin-top:24px}
        input{width:100%;padding:15px 0;background:transparent;border:none;border-bottom:2px solid #e5e7eb;font-size:1.1rem;outline:none;font-family:inherit;transition:border-color 0.3s}
        input:focus{border-color:#000}
        button{width:100%;padding:16px;background:#000;color:white;border:none;border-radius:4px;font-weight:800;cursor:pointer;font-size:0.9rem;text-transform:uppercase;letter-spacing:2px;margin-top:24px;transition:all 0.3s}
        button:hover{background:#333;letter-spacing:4px}
        .msg{background:#d1fae5;color:#065f46;padding:16px;border-radius:8px;margin-bottom:20px}
        .err{background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:20px;font-size:0.9rem}
    </style>
</head>
<body>
<div class="brand"><h1>StudentGig</h1><p style="color:#6b7280;margin-top:8px;">Enter your reset code</p></div>
<div class="card">
    <?php if($msg): ?>
        <div class="msg"><?php echo $msg; ?></div>
    <?php else: ?>
        <?php if($error): ?><div class="err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <p style="color:#64748b;font-size:0.9rem;margin-bottom:8px;">Resetting password for: <strong><?php echo htmlspecialchars($email); ?></strong></p>
        <form method="POST">
            <label>Reset Code (6 digits)</label>
            <input type="text" name="code" placeholder="123456" maxlength="6" required>
            <label>New Password</label>
            <input type="password" name="new_password" placeholder="Min 8 characters" required>
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" placeholder="Repeat password" required>
            <button type="submit" name="reset">Set New Password</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
