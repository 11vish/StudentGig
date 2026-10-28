<?php
session_start();
include "db.php";
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'candidate') { header("Location: index.html"); exit(); }

$name = $_SESSION['user_name'] ?? 'Student';
$id   = $_SESSION['user_id'];
$init = strtoupper(substr($name, 0, 1));

$available  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM tasks WHERE status='open'"))['c'];
$applied    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM applications WHERE candidate_id=$id"))['c'];
$selected   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM applications WHERE candidate_id=$id AND is_selected=1"))['c'];
$avg_row    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT ROUND(AVG(rating),1) r FROM reviews WHERE reviewee_id=$id"));
$avg_rating = $avg_row['r'] ?? '—';

// Recent activity
$recent = mysqli_query($conn, "SELECT tasks.title, tasks.price, applications.is_selected, applications.applied_at FROM applications JOIN tasks ON applications.job_id=tasks.id WHERE applications.candidate_id=$id ORDER BY applications.applied_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard | StudentGig</title>
    <link rel="stylesheet" href="assets/css/style.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<aside class="sidebar">
    <div class="brand"><div class="brand-icon"><i class="fas fa-bolt"></i></div><div><div class="brand-name">StudentGig</div><div class="brand-sub">Student Portal</div></div></div>
    <span class="nav-section-label">Menu</span>
    <a href="candidate_dashboard.php" class="nav-link active"><i class="fas fa-home"></i> Dashboard</a>
    <a href="browse_tasks.php" class="nav-link"><i class="fas fa-search"></i> Browse Gigs</a>
    <a href="my_applications.php" class="nav-link"><i class="fas fa-paper-plane"></i> My Applications</a>
    <a href="profile.php" class="nav-link"><i class="fas fa-user-circle"></i> My Profile</a>
    <a href="leaderboard.php" class="nav-link"><i class="fas fa-trophy"></i> Leaderboard</a>
    <div class="sidebar-footer"><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
</aside>

<main class="main-content">
    <div class="topbar">
        <span class="topbar-title">Dashboard</span>
        <div class="topbar-right">
            <div class="user-chip">
                <div class="user-avatar"><?php echo $init; ?></div>
                <span class="user-name"><?php echo htmlspecialchars($name); ?></span>
            </div>
        </div>
    </div>

    <div class="page">
        <div class="page-header">
            <h1>Welcome back, <?php echo htmlspecialchars(explode(' ',$name)[0]); ?> 👋</h1>
            <p>Here's what's happening with your gig activity today.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-icon blue"><i class="fas fa-briefcase"></i></div>
                <div class="stat-num"><?php echo $available; ?></div>
                <div class="stat-label">Available Gigs</div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon green"><i class="fas fa-paper-plane"></i></div>
                <div class="stat-num"><?php echo $applied; ?></div>
                <div class="stat-label">Applied</div>
            </div>
            <div class="stat-card amber">
                <div class="stat-icon amber"><i class="fas fa-check-circle"></i></div>
                <div class="stat-num"><?php echo $selected; ?></div>
                <div class="stat-label">Selected</div>
            </div>
            <div class="stat-card purple">
                <div class="stat-icon purple"><i class="fas fa-star"></i></div>
                <div class="stat-num"><?php echo $avg_rating; ?></div>
                <div class="stat-label">Avg Rating</div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fas fa-clock" style="color:#6366f1;margin-right:8px;"></i>Recent Activity</span>
                    <a href="my_applications.php" class="btn btn-light" style="font-size:0.8rem;padding:7px 14px;">View All</a>
                </div>
                <?php if(mysqli_num_rows($recent) > 0): ?>
                <?php while($r = mysqli_fetch_assoc($recent)): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid #f1f5f9;">
                    <div>
                        <div style="font-weight:600;font-size:0.875rem;"><?php echo htmlspecialchars($r['title']); ?></div>
                        <div style="font-size:0.75rem;color:#94a3b8;margin-top:2px;"><?php echo date('M d', strtotime($r['applied_at'])); ?></div>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span style="font-weight:700;color:#6366f1;">&#8377;<?php echo number_format($r['price'],0); ?></span>
                        <span class="badge <?php echo $r['is_selected'] ? 'badge-completed' : 'badge-pending'; ?>">
                            <?php echo $r['is_selected'] ? 'Selected' : 'Pending'; ?>
                        </span>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php else: ?>
                <div style="text-align:center;padding:40px;color:#94a3b8;">
                    <i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:12px;"></i>
                    No activity yet. <a href="browse_tasks.php" style="color:#6366f1;font-weight:600;">Browse gigs</a>
                </div>
                <?php endif; ?>
            </div>

            <div style="display:flex;flex-direction:column;gap:16px;">
                <div class="card" style="background:linear-gradient(135deg,#6366f1,#4f46e5);border:none;">
                    <div style="color:rgba(255,255,255,0.7);font-size:0.8rem;margin-bottom:8px;font-weight:600;">READY TO EARN?</div>
                    <div style="color:white;font-size:1.1rem;font-weight:700;margin-bottom:16px;">Find your next task today</div>
                    <a href="browse_tasks.php" style="display:block;background:white;color:#6366f1;text-align:center;padding:10px;border-radius:8px;font-weight:700;text-decoration:none;font-size:0.875rem;">Browse Gigs &rarr;</a>
                </div>
                <div class="card" style="border:2px dashed #e2e8f0;">
                    <div style="color:#64748b;font-size:0.8rem;margin-bottom:6px;font-weight:600;">YOUR RANKING</div>
                    <a href="leaderboard.php" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:#0f172a;">
                        <span style="font-size:1.8rem;">🏆</span>
                        <span style="font-weight:700;">View Leaderboard</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>
