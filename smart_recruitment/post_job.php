<?php
session_start();
include "db.php";

// Check if user is logged in as an employer
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'employer') {
    header("Location: index.html");
    exit();
}

if (isset($_POST['submit_job'])) {
    $employer_id = intval($_SESSION['user_id']);
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $price = intval($_POST['salary']);
    $location = $_POST['location'];

    $stmt = $conn->prepare("INSERT INTO tasks (employer_id, title, description, price, location) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issis", $employer_id, $title, $desc, $price, $location);

    if ($stmt->execute()) {
        $msg = "Job posted successfully!";
    } else {
        $msg = "Error posting job.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    
    <meta charset="UTF-8">
    <title>Post a New Job | StudentGig</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reusing your Dashboard CSS for consistency */
        :root { --primary: #10b981; --bg: #f8fafc; --white: #ffffff; --border: #e2e8f0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; display: flex; }
        
        .sidebar { width: 260px; background: var(--white); height: 100vh; border-right: 1px solid var(--border); position: fixed; padding: 24px; }
        .main-content { margin-left: 260px; width: calc(100% - 260px); padding: 40px; }
        
        .form-card { background: var(--white); padding: 40px; border-radius: 16px; border: 1px solid var(--border); max-width: 800px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #334155; }
        input, textarea, select { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 1rem; }
        textarea { height: 150px; resize: vertical; }
        
        .btn { background: var(--primary); color: white; padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary); margin-bottom: 40px;">StudentGig</div>
        <nav>
            <a href="employer_dashboard.php" style="text-decoration:none; color:#64748b; display:block; padding:12px;"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="post_job.php" style="text-decoration:none; color:var(--primary); font-weight:600; display:block; padding:12px;"><i class="fas fa-plus-circle"></i> Post Job</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="form-card">
            <h1>Post a New Project</h1>
            <p style="color: #64748b; margin-bottom: 30px;">Fill in the details below to find the perfect candidate.</p>

            <?php if(isset($msg)) echo "<div class='alert'>$msg</div>"; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label>Job Title</label>
                    <input type="text" name="title" placeholder="e.g. Fashion Photographer for Summer Collection" required>
                </div>

                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" placeholder="e.g. Remote or Mumbai, India" required>
                </div>

                <div class="form-group">
                    <label>Budget / Salary</label>
                    <input type="text" name="salary" placeholder="e.g. $500 - $1,000" required>
                </div>

                <div class="form-group">
                    <label>Project Description</label>
                    <textarea name="description" placeholder="Describe the skills and responsibilities..." required></textarea>
                </div>

                <button type="submit" name="submit_job" class="btn">Post Project Now</button>
            </form>
        </div>
    </main>

</body>
</html>