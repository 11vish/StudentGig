<?php
session_start();
include "db.php";
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'candidate') { header("Location: index.html"); exit(); }
$id = intval($_SESSION['user_id']);
$name = $_SESSION['user_name'] ?? 'Student';
$init = strtoupper(substr($name,0,1));

$result = mysqli_query($conn, "SELECT tasks.id AS task_id, tasks.title, tasks.price, tasks.status AS task_status, tasks.category, applications.id AS app_id, applications.is_selected, applications.applied_at, applications.bid_amount FROM applications JOIN tasks ON applications.job_id=tasks.id WHERE applications.candidate_id=$id ORDER BY applications.is_selected DESC, applications.applied_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>My Applications | StudentGig</title>
    <link rel="stylesheet" href="assets/css/style.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<aside class="sidebar">
    <div class="brand"><div class="brand-icon"><i class="fas fa-bolt"></i></div><div><div class="brand-name">StudentGig</div><div class="brand-sub">Student Portal</div></div></div>
    <span class="nav-section-label">Menu</span>
    <a href="candidate_dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
    <a href="browse_tasks.php" class="nav-link"><i class="fas fa-search"></i> Browse Gigs</a>
    <a href="my_applications.php" class="nav-link active"><i class="fas fa-paper-plane"></i> My Applications</a>
    <a href="profile.php" class="nav-link"><i class="fas fa-user-circle"></i> My Profile</a>
    <a href="leaderboard.php" class="nav-link"><i class="fas fa-trophy"></i> Leaderboard</a>
    <div class="sidebar-footer"><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
</aside>
<main class="main-content">
    <div class="topbar">
        <span class="topbar-title">My Applications</span>
        <div class="topbar-right"><div class="user-chip"><div class="user-avatar"><?php echo $init;?></div><span class="user-name"><?php echo htmlspecialchars($name);?></span></div></div>
    </div>
    <div class="page">
        <div class="page-header"><h1>Your Gig Pipeline</h1><p>Track your applications and submit finished work.</p></div>

        <?php if(mysqli_num_rows($result)==0): ?>
        <div class="card" style="text-align:center;padding:60px;">
            <i class="fas fa-folder-open" style="font-size:3rem;color:#e2e8f0;display:block;margin-bottom:16px;"></i>
            <p style="color:#94a3b8;margin-bottom:16px;">You haven't applied for any tasks yet.</p>
            <a href="browse_tasks.php" class="btn btn-primary"><i class="fas fa-search"></i> Browse Gigs</a>
        </div>
        <?php else: ?>
        <?php while($r=mysqli_fetch_assoc($result)): ?>
        <div class="pipeline-card">
            <div class="pipeline-left">
                <h3><?php echo htmlspecialchars($r['title']); ?></h3>
                <div class="meta">
                    <?php if(!empty($r['category'])): ?><span style="background:#f1f5f9;padding:2px 8px;border-radius:6px;margin-right:8px;"><?php echo htmlspecialchars($r['category']);?></span><?php endif; ?>
                    Applied <?php echo date('M d, Y', strtotime($r['applied_at'])); ?>
                    &bull; Your bid: <strong>&#8377;<?php echo number_format($r['bid_amount']??$r['price'],0);?></strong>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:12px;">
                <?php if($r['is_selected'] && $r['task_status']=='assigned'): ?>
                    <span class="badge badge-completed"><i class="fas fa-check-circle"></i> Selected!</span>
                    <a href="submit_task.php?app_id=<?php echo $r['app_id'];?>&task_id=<?php echo $r['task_id'];?>" class="btn btn-primary" style="font-size:0.8rem;padding:8px 16px;"><i class="fas fa-upload"></i> Submit Work</a>
                <?php elseif($r['task_status']=='completed'): ?>
                    <span class="badge badge-assigned"><i class="fas fa-clock"></i> Pending Review</span>
                <?php else: ?>
                    <span class="badge badge-pending"><i class="fas fa-hourglass-half"></i> Awaiting Selection</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; endif; ?>
    </div>
</main>
</body>
</html>
