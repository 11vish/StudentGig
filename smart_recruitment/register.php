<?php
include "db.php";

if (isset($_POST['register'])) {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $raw_pass = $_POST['password'];

    $allowed_roles = ['candidate', 'employer'];
    $role = in_array($_POST['role'], $allowed_roles) ? $_POST['role'] : 'candidate';

    if (strlen($raw_pass) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif (empty($name) || empty($email)) {
        $error = "Name and email are required.";
    } else {
        $password = password_hash($raw_pass, PASSWORD_DEFAULT);
        $checkEmail = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        $checkEmail->store_result();

        if ($checkEmail->num_rows > 0) {
            $error = "This email is already registered.";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $password, $role);
            if ($stmt->execute()) {
                $success = true;
            } else {
                error_log("Register error: " . $conn->error);
                $error = "Registration failed. Please try again.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registration | StudentGig</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',sans-serif;background:#f1f5f9;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
        .card{background:white;border-radius:16px;padding:40px;width:100%;max-width:380px;border:1px solid #e2e8f0;box-shadow:0 4px 24px rgba(0,0,0,0.06);text-align:center}
        .logo{display:flex;align-items:center;gap:10px;justify-content:center;margin-bottom:24px}
        .logo-icon{width:40px;height:40px;background:#6366f1;border-radius:10px;display:flex;align-items:center;justify-content:center;color:white;font-size:18px}
        .logo-text{font-size:1.2rem;font-weight:700;color:#0f172a}
        h2{font-size:1.3rem;font-weight:700;margin-bottom:6px;color:#0f172a}
        .sub{color:#64748b;font-size:0.875rem;margin-bottom:24px}
        .success{background:#dcfce7;color:#166534;border:1px solid #bbf7d0;padding:16px;border-radius:10px;margin-bottom:16px}
        .error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca;padding:12px;border-radius:8px;margin-bottom:16px;font-size:0.875rem}
        .btn{display:inline-block;background:#6366f1;color:white;padding:12px 28px;border-radius:10px;font-weight:600;text-decoration:none;font-size:0.95rem;margin-top:12px;transition:background 0.2s}
        .btn:hover{background:#4f46e5}
        .link{color:#6366f1;text-decoration:none;font-weight:500;font-size:0.875rem}
    </style>
</head>
<body>
<div class="card">
    <div class="logo">
        <div class="logo-icon">&#9889;</div>
        <span class="logo-text">StudentGig</span>
    </div>
    <?php if (isset($success)): ?>
        <div class="success">
            <strong>&#9989; Registration Successful!</strong><br>
            Your account has been created.
        </div>
        <a href="index.html" class="btn">Sign In Now</a>
    <?php elseif (isset($error)): ?>
        <h2>Something went wrong</h2>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <a href="register.html" class="btn">Try Again</a>
    <?php else: ?>
        <h2>No data submitted</h2>
        <p class="sub">Please use the registration form.</p>
        <a href="register.html" class="btn">Go to Register</a>
    <?php endif; ?>
    <br><br>
    <a href="index.html" class="link">Already have an account? Sign in</a>
</div>
</body>
</html>
