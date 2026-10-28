<?php
session_start();
include "db.php";
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'employer') { header("Location: index.html"); exit(); }

$msg = ''; $error = '';
if (isset($_POST['submit_task'])) {
    $employer_id = intval($_SESSION['user_id']);
    $title    = $_POST['title'];
    $category = $_POST['category'];
    $price    = intval($_POST['price']);
    $desc     = $_POST['description'];
    $deadline = $_POST['deadline'] ?? null;

    if ($price <= 0) { $error = "Price must be greater than 0."; }
    else {
        $stmt = $conn->prepare("INSERT INTO tasks (employer_id, title, description, category, price, location, deadline) VALUES (?, ?, ?, ?, ?, 'Remote', ?)");
        $stmt->bind_param("isssis", $employer_id, $title, $desc, $category, $price, $deadline);
        if ($stmt->execute()) { $msg = "Task posted successfully!"; }
        else { $error = "Something went wrong. Please try again."; }
    }
}
$name = $_SESSION['user_name'] ?? 'Employer';
$init = strtoupper(substr($name,0,1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Post a Task | StudentGig</title>
    <link rel="stylesheet" href="assets/css/style.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<aside class="sidebar">
    <div class="brand"><div class="brand-icon" style="background:#10b981;"><i class="fas fa-bolt"></i></div><div><div class="brand-name">StudentGig</div><div class="brand-sub">Employer Portal</div></div></div>
    <span class="nav-section-label">Menu</span>
    <a href="employer_dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
    <a href="post_task.php" class="nav-link active"><i class="fas fa-plus-circle"></i> Post a Task</a>
    <a href="view_applicants.php" class="nav-link"><i class="fas fa-users"></i> Applicants</a>
    <a href="manage_tasks.php" class="nav-link"><i class="fas fa-tasks"></i> Manage Tasks</a>
    <a href="leaderboard.php" class="nav-link"><i class="fas fa-trophy"></i> Leaderboard</a>
    <div class="sidebar-footer"><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
</aside>
<main class="main-content">
    <div class="topbar">
        <span class="topbar-title">Post a Task</span>
        <div class="topbar-right"><div class="user-chip"><div class="user-avatar" style="background:#10b981;"><?php echo $init;?></div><span class="user-name"><?php echo htmlspecialchars($name);?></span></div></div>
    </div>
    <div class="page">
        <div class="page-header"><h1>Post a New Task</h1><p>Describe what you need done and a student will apply for it.</p></div>
        <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;">
            <div class="card">
                <?php if($msg): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
                <?php if($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Task Title *</label>
                        <input class="form-control" type="text" name="title" placeholder="e.g. Design 5 Instagram posts for a cafe" required>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div class="form-group">
                            <label class="form-label">Category *</label>
                            <select class="form-control" name="category" required>
                                <option value="">Select category</option>
                                <?php foreach(['Data Entry','Cover Making','Photo Shoot','Ad Campaign','Website Building','Video Editing','Content Writing','Social Media'] as $c): ?>
                                <option value="<?php echo $c;?>"><?php echo $c;?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Budget (&#8377;) *</label>
                            <input class="form-control" type="number" name="price" placeholder="e.g. 500" min="1" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Deadline (optional)</label>
                        <input class="form-control" type="date" name="deadline" min="<?php echo date('Y-m-d');?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Task Instructions *</label>
                        <textarea class="form-control" name="description" rows="5" placeholder="Describe exactly what the student needs to do, what format to deliver, and any important details..." required></textarea>
                    </div>
                    <button type="submit" name="submit_task" class="btn btn-success btn-block"><i class="fas fa-paper-plane"></i> Post Task</button>
                </form>
            </div>
            <div>
                <div class="card" style="background:#fffbeb;border-color:#fde68a;">
                    <div style="font-weight:700;color:#92400e;margin-bottom:12px;"><i class="fas fa-lightbulb" style="margin-right:6px;"></i>Tips for a great post</div>
                    <ul style="color:#78350f;font-size:0.85rem;line-height:1.8;padding-left:16px;">
                        <li>Be specific about deliverables</li>
                        <li>Set a fair price for the effort</li>
                        <li>Mention the tools or format needed</li>
                        <li>Add a deadline to attract serious students</li>
                    </ul>
                </div>
                <div class="card" style="margin-top:0;">
                    <div style="font-weight:600;color:#64748b;font-size:0.8rem;margin-bottom:12px;">TASK REACHES</div>
                    <div style="font-size:2rem;font-weight:700;color:#6366f1;">500+</div>
                    <div style="color:#64748b;font-size:0.85rem;">active student freelancers</div>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>
