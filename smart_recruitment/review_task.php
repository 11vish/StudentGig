<?php
session_start();
include "db.php";
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'employer') {
    header("Location: index.html"); exit();
}

$employer_id = intval($_SESSION['user_id']);
$task_id     = intval($_GET['task_id']   ?? 0);
$student_id  = intval($_GET['student_id'] ?? 0);
$msg = '';

// FIXED: Verify this employer actually owns this task
$own = $conn->prepare("SELECT id FROM tasks WHERE id = ? AND employer_id = ?");
$own->bind_param("ii", $task_id, $employer_id);
$own->execute();
if ($own->get_result()->num_rows === 0) {
    header("Location: manage_tasks.php"); exit();
}

if (isset($_POST['submit_rating'])) {
    $rating  = max(1, min(5, intval($_POST['rating'])));
    $comment = $_POST['comment'] ?? '';

    // Check if review already exists for this task
    $dup = $conn->prepare("SELECT id FROM reviews WHERE task_id = ? AND reviewer_id = ?");
    $dup->bind_param("ii", $task_id, $employer_id);
    $dup->execute();
    $dup->store_result();

    if ($dup->num_rows > 0) {
        $msg = "You have already submitted a review for this task.";
    } else {
        $stmt = $conn->prepare("INSERT INTO reviews (task_id, reviewer_id, reviewee_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiis", $task_id, $employer_id, $student_id, $rating, $comment);

        if ($stmt->execute()) {
            $u = $conn->prepare("UPDATE tasks SET status='completed' WHERE id=?");
            $u->bind_param("i", $task_id); $u->execute();
            $msg = "Review submitted successfully!";
        }
    }
}

$name = $_SESSION['user_name'] ?? 'Employer';
$init = strtoupper(substr($name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Rate Student | StudentGig</title>
    <link rel="stylesheet" href="assets/css/style.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stars-row{display:flex;gap:8px;margin:12px 0}
        .star-btn{font-size:2rem;background:none;border:none;cursor:pointer;color:#e2e8f0;transition:color 0.15s;padding:0}
        .star-btn.active{color:#f59e0b}
    </style>
</head>
<body>
<aside class="sidebar">
    <div class="brand"><div class="brand-icon" style="background:#10b981;"><i class="fas fa-bolt"></i></div><div><div class="brand-name">StudentGig</div><div class="brand-sub">Employer Portal</div></div></div>
    <span class="nav-section-label">Menu</span>
    <a href="employer_dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
    <a href="post_task.php" class="nav-link"><i class="fas fa-plus-circle"></i> Post a Task</a>
    <a href="view_applicants.php" class="nav-link"><i class="fas fa-users"></i> Applicants</a>
    <a href="manage_tasks.php" class="nav-link active"><i class="fas fa-tasks"></i> Manage Tasks</a>
    <div class="sidebar-footer"><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
</aside>
<main class="main-content">
    <div class="topbar">
        <span class="topbar-title">Rate Student</span>
        <div class="topbar-right"><div class="user-chip"><div class="user-avatar" style="background:#10b981;"><?php echo $init;?></div><span class="user-name"><?php echo htmlspecialchars($name);?></span></div></div>
    </div>
    <div class="page">
        <div class="page-header"><h1>Rate Performance</h1><p>Your rating builds the student's reputation on the platform.</p></div>
        <div style="max-width:520px;">
            <?php if($msg): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $msg;?></div>
            <a href="manage_tasks.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Tasks</a>
            <?php else: ?>
            <div class="card">
                <form method="POST">
                    <input type="hidden" name="task_id"    value="<?php echo $task_id;?>">
                    <input type="hidden" name="student_id" value="<?php echo $student_id;?>">
                    <input type="hidden" name="rating" id="ratingVal" value="5">
                    <div class="form-group">
                        <label class="form-label">Your Rating</label>
                        <div class="stars-row">
                            <?php for($i=1;$i<=5;$i++): ?>
                            <button type="button" class="star-btn active" data-val="<?php echo $i;?>" onclick="setRating(<?php echo $i;?>)">&#9733;</button>
                            <?php endfor; ?>
                        </div>
                        <div id="ratingLabel" style="font-size:0.875rem;color:#64748b;">5 stars — Excellent</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Comments (optional)</label>
                        <textarea class="form-control" name="comment" rows="4" placeholder="Describe the student's work quality, communication and timeliness..."></textarea>
                    </div>
                    <button type="submit" name="submit_rating" class="btn btn-success btn-block"><i class="fas fa-star"></i> Submit Review</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>
<script>
const labels={1:'1 star — Very poor',2:'2 stars — Poor',3:'3 stars — Average',4:'4 stars — Good',5:'5 stars — Excellent'};
function setRating(val){
    document.getElementById('ratingVal').value=val;
    document.getElementById('ratingLabel').textContent=labels[val];
    document.querySelectorAll('.star-btn').forEach(b=>b.classList.toggle('active',parseInt(b.dataset.val)<=val));
}
</script>
</body>
</html>
