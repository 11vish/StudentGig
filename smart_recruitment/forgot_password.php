<?php
session_start();
include "db.php";
$msg = ''; $error = '';

if (isset($_POST['send'])) {
    $email = trim($_POST['email']);
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $user = $res->fetch_assoc();
        // Generate a simple 6-digit reset code
        $code = rand(100000, 999999);
        $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        // Save code to DB
        $conn->prepare("DELETE FROM password_resets WHERE email = ?")->execute() || true;
        $ins = $conn->prepare("INSERT INTO password_resets (email, code, expires_at) VALUES (?, ?, ?)");
        $ins->bind_param("sss", $email, $code, $expires);
        $ins->execute();
        // Code saved to DB — redirect to reset page
        $msg = "A reset code has been generated. <a href='reset_password.php' style='color:#065f46;font-weight:700;'>Click here to enter your code</a>.";
        $_SESSION['reset_email'] = $email;
    } else {
        $error = "No account found with that email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password | StudentGig</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',sans-serif;background:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;padding:20px;color:#000}
        .brand{text-align:center;margin-bottom:40px}
        .brand h1{font-size:2.5rem;font-weight:900;letter-spacing:-3px;text-transform:uppercase}
        .brand p{color:#6b7280;margin-top:8px;font-size:0.95rem}
        .card{width:100%;max-width:400px}
        label{display:block;font-weight:700;font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;margin-bottom:10px}
        input{width:100%;padding:15px 0;background:transparent;border:none;border-bottom:2px solid #e5e7eb;font-size:1.1rem;outline:none;transition:border-color 0.3s;font-family:inherit}
        input:focus{border-color:#000}
        button{width:100%;padding:16px;background:#000;color:white;border:none;border-radius:4px;font-weight:800;cursor:pointer;font-size:0.9rem;text-transform:uppercase;letter-spacing:2px;margin-top:20px;transition:all 0.3s}
        button:hover{background:#333;letter-spacing:4px}
        .msg{background:#d1fae5;color:#065f46;padding:16px;border-radius:8px;margin-bottom:20px;line-height:1.6}
        .err{background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:20px;font-size:0.9rem}
        .links{margin-top:30px;text-align:center;font-size:0.85rem}
        .links a{color:#6b7280;text-decoration:none;font-weight:600}
        .links a:hover{color:#000}
        .step2{margin-top:20px;text-align:center}
        .step2 a{display:inline-block;padding:12px 24px;background:#2563eb;color:white;border-radius:8px;text-decoration:none;font-weight:600}
    </style>
</head>
<body>
<div class="brand">
    <h1>StudentGig</h1>
    <p>Reset your password</p>
</div>
<div class="card">
    <?php if($msg): ?>
        <div class="msg"><?php echo $msg; ?></div>
        <div class="step2"><a href="reset_password.php">Enter Reset Code &rarr;</a></div>
    <?php else: ?>
        <?php if($error): ?><div class="err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="POST">
            <div style="margin-bottom:30px;">
                <label>Your Email Address</label>
                <input type="email" name="email" placeholder="you@example.com" required>
            </div>
            <button type="submit" name="send">Send Reset Code</button>
        </form>
    <?php endif; ?>
    <div class="links" style="margin-top:24px;"><a href="index.html">&larr; Back to Login</a></div>
</div>
</body>
</html>
