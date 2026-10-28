<?php
session_start();
include "db.php";
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'employer') { header("Location: index.html"); exit(); }
$employer_id = intval($_SESSION['user_id']);
$name = $_SESSION['user_name'] ?? 'Employer';
$init = strtoupper(substr($name,0,1));

$stmt = $conn->prepare("SELECT tasks.title AS task_title, tasks.price AS task_price, users.name AS student_name, users.email AS student_email, users.id AS candidate_id, applications.applied_at, applications.id AS app_id, applications.is_selected, applications.bid_amount, applications.delivery_days, applications.proposal_text FROM applications JOIN tasks ON applications.job_id=tasks.id JOIN users ON applications.candidate_id=users.id WHERE tasks.employer_id=? ORDER BY applications.applied_at DESC");
$stmt->bind_param("i",$employer_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Applicants | StudentGig</title>
    <link rel="stylesheet" href="assets/css/style.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<aside class="sidebar">
    <div class="brand"><div class="brand-icon" style="background:#10b981;"><i class="fas fa-bolt"></i></div><div><div class="brand-name">StudentGig</div><div class="brand-sub">Employer Portal</div></div></div>
    <span class="nav-section-label">Menu</span>
    <a href="employer_dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
    <a href="post_task.php" class="nav-link"><i class="fas fa-plus-circle"></i> Post a Task</a>
    <a href="view_applicants.php" class="nav-link active"><i class="fas fa-users"></i> Applicants</a>
    <a href="manage_tasks.php" class="nav-link"><i class="fas fa-tasks"></i> Manage Tasks</a>
    <a href="leaderboard.php" class="nav-link"><i class="fas fa-trophy"></i> Leaderboard</a>
    <div class="sidebar-footer"><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
</aside>
<main class="main-content">
    <div class="topbar">
        <span class="topbar-title">Task Applicants</span>
        <div class="topbar-right"><div class="user-chip"><div class="user-avatar" style="background:#10b981;"><?php echo $init;?></div><span class="user-name"><?php echo htmlspecialchars($name);?></span></div></div>
    </div>
    <div class="page">
        <div class="page-header"><h1>Review Applicants</h1><p>Browse student bids and assign tasks to the best fit.</p></div>

        <?php if($result->num_rows==0): ?>
        <div class="card" style="text-align:center;padding:60px;">
            <i class="fas fa-inbox" style="font-size:3rem;color:#e2e8f0;display:block;margin-bottom:16px;"></i>
            <p style="color:#94a3b8;">No applications yet. Students will appear here once they apply.</p>
        </div>
        <?php else: ?>
        <?php while($r=$result->fetch_assoc()): ?>
        <div class="card" style="padding:20px 24px;">
            <div style="display:flex;align-items:flex-start;gap:16px;">
                <div style="width:44px;height:44px;border-radius:50%;background:#eef2ff;color:#6366f1;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1rem;flex-shrink:0;">
                    <?php echo strtoupper(substr($r['student_name'],0,1));?>
                </div>
                <div style="flex:1;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;">
                        <div>
                            <a href="view_student_profile.php?id=<?php echo $r['candidate_id'];?>" style="font-weight:700;font-size:0.95rem;color:#0f172a;text-decoration:none;"><?php echo htmlspecialchars($r['student_name']);?> <i class="fas fa-external-link-alt" style="font-size:10px;color:#94a3b8;"></i></a>
                            <div style="font-size:0.8rem;color:#64748b;"><?php echo htmlspecialchars($r['student_email']);?></div>
                        </div>
                        <?php if($r['is_selected']): ?>
                            <span class="badge badge-completed"><i class="fas fa-check-circle"></i> Assigned</span>
                        <?php else: ?>
                            <a href="assign_task.php?id=<?php echo $r['app_id'];?>" class="btn btn-primary" style="font-size:0.8rem;padding:8px 16px;" onclick="return confirm('Assign this task to <?php echo htmlspecialchars($r['student_name']);?>?')"><i class="fas fa-user-check"></i> Assign Task</a>
                        <?php endif; ?>
                    </div>
                    <div style="background:#f8fafc;border-radius:8px;padding:12px;margin-top:12px;font-size:0.85rem;color:#475569;line-height:1.6;">
                        <div style="display:flex;gap:20px;margin-bottom:8px;flex-wrap:wrap;">
                            <span><i class="fas fa-tag" style="color:#6366f1;"></i> Task: <strong><?php echo htmlspecialchars($r['task_title']);?></strong></span>
                            <span><i class="fas fa-rupee-sign" style="color:#10b981;"></i> Bid: <strong>&#8377;<?php echo number_format($r['bid_amount']??$r['task_price'],0);?></strong></span>
                            <?php if($r['delivery_days']): ?><span><i class="fas fa-clock" style="color:#f59e0b;"></i> <strong><?php echo $r['delivery_days'];?> days</strong></span><?php endif; ?>
                        </div>
                        <?php if(!empty($r['proposal_text'])): ?>
                        <div style="font-style:italic;color:#64748b;border-left:3px solid #e2e8f0;padding-left:10px;">"<?php echo htmlspecialchars(substr($r['proposal_text'],0,200));?><?php echo strlen($r['proposal_text'])>200?'...':'';?>"</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; endif; ?>
    </div>
</main>
</body>
</html>
