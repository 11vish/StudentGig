<?php
session_start();
include "db.php";
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'employer') { header("Location: index.html"); exit(); }
$employer_id = intval($_SESSION['user_id']);
$name = $_SESSION['user_name'] ?? 'Employer';
$init = strtoupper(substr($name,0,1));

$sql = "SELECT tasks.*, users.name as student_name, users.id as student_id, applications.submission_link, applications.submission_note
        FROM tasks
        LEFT JOIN applications ON tasks.id=applications.job_id AND applications.is_selected=1
        LEFT JOIN users ON applications.candidate_id=users.id
        WHERE tasks.employer_id=$employer_id
        ORDER BY FIELD(tasks.status,'completed','assigned','open')";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Manage Tasks | StudentGig</title>
    <link rel="stylesheet" href="assets/css/style.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<aside class="sidebar">
    <div class="brand"><div class="brand-icon" style="background:#10b981;"><i class="fas fa-bolt"></i></div><div><div class="brand-name">StudentGig</div><div class="brand-sub">Employer Portal</div></div></div>
    <span class="nav-section-label">Menu</span>
    <a href="employer_dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
    <a href="post_task.php" class="nav-link"><i class="fas fa-plus-circle"></i> Post a Task</a>
    <a href="view_applicants.php" class="nav-link"><i class="fas fa-users"></i> Applicants</a>
    <a href="manage_tasks.php" class="nav-link active"><i class="fas fa-tasks"></i> Manage Tasks</a>
    <a href="leaderboard.php" class="nav-link"><i class="fas fa-trophy"></i> Leaderboard</a>
    <div class="sidebar-footer"><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
</aside>
<main class="main-content">
    <div class="topbar">
        <span class="topbar-title">Task Overview</span>
        <div class="topbar-right"><div class="user-chip"><div class="user-avatar" style="background:#10b981;"><?php echo $init;?></div><span class="user-name"><?php echo htmlspecialchars($name);?></span></div></div>
    </div>
    <div class="page">
        <div class="page-header"><h1>Manage Tasks</h1><p>Review submitted work and rate students to complete gigs.</p></div>

        <?php if(mysqli_num_rows($result)==0): ?>
        <div class="card" style="text-align:center;padding:60px;">
            <i class="fas fa-inbox" style="font-size:3rem;color:#e2e8f0;display:block;margin-bottom:16px;"></i>
            <p style="color:#94a3b8;">No tasks yet. <a href="post_task.php" style="color:#6366f1;font-weight:600;">Post your first task</a></p>
        </div>
        <?php endif; ?>

        <?php while($row=mysqli_fetch_assoc($result)): ?>
        <div class="card" style="padding:24px;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;">
                <div style="flex:1;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                        <span class="badge badge-<?php echo $row['status'];?>"><?php echo ucfirst($row['status']);?></span>
                        <?php if(!empty($row['deadline'])): ?>
                        <span style="font-size:0.75rem;color:#94a3b8;"><i class="fas fa-clock"></i> Due <?php echo date('M d',strtotime($row['deadline']));?></span>
                        <?php endif; ?>
                    </div>
                    <h3 style="font-size:1rem;font-weight:600;margin-bottom:6px;"><?php echo htmlspecialchars($row['title']);?></h3>
                    <div style="display:flex;gap:16px;font-size:0.8rem;color:#64748b;">
                        <span><i class="fas fa-rupee-sign"></i> <?php echo number_format($row['price'],0);?> budget</span>
                        <?php if($row['student_name']): ?>
                        <span><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($row['student_name']);?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <?php if($row['status']=='completed'): ?>
                        <a href="review_task.php?task_id=<?php echo $row['id'];?>&student_id=<?php echo $row['student_id'];?>" class="btn btn-success"><i class="fas fa-star"></i> Rate & Close</a>
                    <?php elseif($row['status']=='open'): ?>
                        <span style="font-size:0.8rem;color:#94a3b8;"><i class="fas fa-hourglass-half"></i> Awaiting applicants</span>
                    <?php else: ?>
                        <span style="font-size:0.8rem;color:#6366f1;font-weight:600;"><i class="fas fa-spinner"></i> In progress</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if($row['status']=='completed' && !empty($row['submission_link'])): ?>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-left:4px solid #10b981;border-radius:0 10px 10px 0;padding:14px;margin-top:16px;">
                <div style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:8px;">Submitted Work</div>
                <a href="<?php echo htmlspecialchars($row['submission_link']);?>" target="_blank" style="color:#6366f1;font-weight:600;font-size:0.875rem;text-decoration:none;"><i class="fas fa-external-link-alt"></i> View Submitted Files</a>
                <?php if(!empty($row['submission_note'])): ?>
                <p style="font-size:0.8rem;color:#64748b;margin-top:8px;font-style:italic;">"<?php echo htmlspecialchars($row['submission_note']);?>"</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div>
</main>
</body>
</html>
