<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

// FIXED: Always cast to int
$student_id   = intval($_SESSION['user_id']);
$student_name = $_SESSION['user_name'] ?? '';

// FIXED: Prepared statement
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Badge logic — also use intval
$badge_stmt = $conn->prepare("SELECT COUNT(*) c FROM applications WHERE candidate_id = ? AND is_selected = 1");
$badge_stmt->bind_param("i", $student_id);
$badge_stmt->execute();
$completed_count = $badge_stmt->get_result()->fetch_assoc()['c'];

$badge = ""; $b_color = "";
if ($completed_count >= 50)      { $badge = "Gold Tier";   $b_color = "#FFD700"; }
elseif ($completed_count >= 20)  { $badge = "Silver Tier"; $b_color = "#C0C0C0"; }
elseif ($completed_count >= 10)  { $badge = "Bronze Tier"; $b_color = "#CD7F32"; }

// History — prepared statement
$hist_stmt = $conn->prepare(
    "SELECT tasks.title, users.name as company, reviews.rating, reviews.comment
     FROM applications
     JOIN tasks ON applications.job_id = tasks.id
     JOIN users ON tasks.employer_id = users.id
     LEFT JOIN reviews ON reviews.task_id = tasks.id AND reviews.reviewee_id = ?
     WHERE applications.candidate_id = ? AND applications.is_selected = 1"
);
$hist_stmt->bind_param("ii", $student_id, $student_id);
$hist_stmt->execute();
$history_res = $hist_stmt->get_result();

// FIXED: Dynamic initials — no more hardcoded "VV"
$parts    = explode(' ', trim($student_name));
$initials = strtoupper(substr($parts[0], 0, 1)) . (isset($parts[1]) ? strtoupper(substr($parts[1], 0, 1)) : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile | StudentGig</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--blue:#6366f1;--bg:#f1f5f9;--border:#e2e8f0}
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',sans-serif;background:var(--bg);display:flex}
        .sidebar{width:260px;background:#0f172a;height:100vh;border-right:1px solid #1e293b;position:fixed;left:0;top:0;padding:24px;z-index:1000;display:flex;flex-direction:column}
        .brand{display:flex;align-items:center;gap:10px;margin-bottom:40px}
        .brand-icon{width:36px;height:36px;background:var(--blue);border-radius:10px;display:flex;align-items:center;justify-content:center;color:white;font-size:16px}
        .brand-name{font-size:1.1rem;font-weight:700;color:white}
        .nav-link{display:flex;align-items:center;gap:12px;padding:11px 12px;border-radius:10px;text-decoration:none;color:#94a3b8;font-size:0.875rem;font-weight:500;margin-bottom:2px;transition:0.18s}
        .nav-link:hover{background:rgba(255,255,255,0.07);color:white}
        .nav-link.active{background:var(--blue);color:white}
        .sidebar-footer{margin-top:auto;border-top:1px solid #1e293b;padding-top:16px}
        .logout-link{display:flex;align-items:center;gap:12px;padding:11px 12px;border-radius:10px;text-decoration:none;color:#ef4444;font-size:0.875rem;font-weight:500}
        .logout-link:hover{background:rgba(239,68,68,0.1)}
        .main-content{margin-left:260px;width:calc(100% - 260px);padding:30px;display:flex;gap:20px;align-items:flex-start}
        .card{background:#fff;border-radius:12px;border:1px solid var(--border);margin-bottom:16px;width:100%;overflow:hidden;padding:24px}
        .hero-card{padding:0}
        .banner{height:140px;background:linear-gradient(135deg,#6366f1,#4f46e5)}
        .p-body{padding:20px 24px 24px;position:relative;padding-top:55px}
        .avatar{width:110px;height:110px;background:var(--blue);border:4px solid #fff;border-radius:50%;position:absolute;top:-55px;left:24px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:2rem;font-weight:700}
        .timeline{position:relative;padding-left:28px;border-left:2px solid var(--border);margin-left:10px;margin-top:16px}
        .timeline-item{position:relative;margin-bottom:20px}
        .timeline-item::before{content:"";position:absolute;left:-39px;top:2px;width:18px;height:18px;background:#fff;border:3px solid var(--blue);border-radius:50%}
        .timeline-date{font-size:0.7rem;font-weight:700;color:var(--blue);text-transform:uppercase;margin-bottom:4px}
        .modal{display:none;position:fixed;z-index:2000;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.6);align-items:center;justify-content:center}
        .modal-content{background:#fff;padding:28px;border-radius:14px;width:90%;max-width:480px;position:relative;max-height:80vh;overflow-y:auto}
        .trust-item{background:#f8fafc;border:1px solid var(--border);padding:14px;border-radius:10px;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center}
        .btn-brand{background:var(--blue);color:#fff;border:none;padding:10px 20px;border-radius:30px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:8px;margin-top:14px;font-family:inherit}
        .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .form-label{display:block;font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:7px}
        .form-control{width:100%;padding:11px 13px;border:1.5px solid var(--border);border-radius:9px;background:#f8fafc;outline:none;font-family:inherit;font-size:0.875rem;transition:border-color 0.2s}
        .form-control:focus{border-color:var(--blue);background:white}
        .badge-pill{display:inline-block;padding:4px 14px;border-radius:20px;font-size:0.75rem;font-weight:700}
        .save-btn{background:#0f172a;color:#fff;border:none;padding:12px 28px;border-radius:30px;cursor:pointer;font-weight:700;float:right;margin-top:20px;font-family:inherit}
        .save-btn:hover{background:#1e293b}
        .star{color:#f59e0b}
    </style>
</head>
<body>

<div id="portfolioModal" class="modal">
    <div class="modal-content">
        <span style="position:absolute;right:16px;top:16px;cursor:pointer;color:#64748b;" onclick="closeModal()"><i class="fas fa-times"></i></span>
        <h3 style="margin-bottom:16px;">Verified Gigs</h3>
        <?php if($history_res->num_rows > 0): ?>
            <?php while($h = $history_res->fetch_assoc()): ?>
            <div class="trust-item">
                <div>
                    <div style="font-weight:600;font-size:0.875rem;"><?php echo htmlspecialchars($h['title']); ?></div>
                    <div style="font-size:0.75rem;color:#64748b;"><?php echo htmlspecialchars($h['company']); ?></div>
                </div>
                <?php if($h['rating']): ?>
                    <span class="star"><?php echo str_repeat('★', $h['rating']); ?></span>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="color:#94a3b8;text-align:center;padding:20px;">No completed gigs yet.</p>
        <?php endif; ?>
    </div>
</div>

<aside class="sidebar">
    <div class="brand"><div class="brand-icon"><i class="fas fa-bolt"></i></div><div class="brand-name">StudentGig</div></div>
    <a href="candidate_dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
    <a href="browse_tasks.php" class="nav-link"><i class="fas fa-search"></i> Browse Gigs</a>
    <a href="my_applications.php" class="nav-link"><i class="fas fa-paper-plane"></i> My Applications</a>
    <a href="profile.php" class="nav-link active"><i class="fas fa-user-circle"></i> My Profile</a>
    <a href="leaderboard.php" class="nav-link"><i class="fas fa-trophy"></i> Leaderboard</a>
    <div class="sidebar-footer"><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
</aside>

<main class="main-content">
    <div style="flex:1;max-width:760px;">

        <?php if(isset($_GET['status']) && $_GET['status']==='updated'): ?>
        <div style="background:#dcfce7;color:#166534;border:1px solid #bbf7d0;padding:14px 18px;border-radius:10px;margin-bottom:16px;font-size:0.875rem;">
            <i class="fas fa-check-circle"></i> Profile updated successfully!
        </div>
        <?php endif; ?>

        <div class="card hero-card">
            <div class="banner"></div>
            <div class="p-body">
                <div class="avatar"><?php echo $initials ?: '?'; ?></div>
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;">
                    <div>
                        <h1 style="font-size:1.5rem;font-weight:700;"><?php echo htmlspecialchars($student_name); ?>
                            <i class="fas fa-check-circle" style="color:var(--blue);font-size:0.9rem;"></i>
                        </h1>
                        <p style="color:#64748b;margin-top:4px;"><?php echo htmlspecialchars($user['skills'] ?? ''); ?></p>
                        <p style="font-size:0.8rem;color:#94a3b8;margin-top:4px;">
                            <?php if($user['location']): ?><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($user['location']); ?><?php endif; ?>
                        </p>
                        <?php if($badge): ?>
                            <span class="badge-pill" style="background:<?php echo $b_color; ?>20;color:<?php echo $b_color; ?>;border:1px solid <?php echo $b_color; ?>;margin-top:8px;">
                                ★ <?php echo $badge; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <button class="btn-brand" onclick="openModal()">
                        <i class="fas fa-briefcase"></i> <?php echo $completed_count; ?> Completed Gig<?php echo $completed_count!=1?'s':''; ?>
                    </button>
                </div>
            </div>
        </div>

        <div class="card">
            <div style="font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">Academic Journey</div>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-date">Current</div>
                    <h4><?php echo htmlspecialchars($user['university'] ?? 'Add university'); ?></h4>
                    <p style="font-size:0.8rem;color:#64748b;"><?php echo htmlspecialchars($user['study_status'] ?? ''); ?></p>
                </div>
                <div class="timeline-item">
                    <div class="timeline-date">Graduation / Diploma</div>
                    <h4><?php echo htmlspecialchars($user['grad_school'] ?: 'Add school name'); ?></h4>
                </div>
                <div class="timeline-item" style="margin-bottom:0;">
                    <div class="timeline-date">High School</div>
                    <h4><?php echo htmlspecialchars($user['high_school'] ?: 'Add school name'); ?></h4>
                </div>
            </div>
        </div>

        <form action="update_profile_logic.php" method="POST" class="card">
            <div style="font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:1px;margin-bottom:20px;">Edit Profile</div>
            <div class="grid-2">
                <div><label class="form-label">Headline / Skills</label><input class="form-control" type="text" name="skills" value="<?php echo htmlspecialchars($user['skills'] ?? ''); ?>"></div>
                <div><label class="form-label">Location</label><input class="form-control" type="text" name="location" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>"></div>
                <div><label class="form-label">University</label><input class="form-control" type="text" name="university" value="<?php echo htmlspecialchars($user['university'] ?? ''); ?>"></div>
                <div><label class="form-label">Current Status</label>
                    <select class="form-control" name="study_status">
                        <option value="Studying" <?php echo ($user['study_status']??'')==='Studying'?'selected':''; ?>>Studying</option>
                        <option value="Completed" <?php echo ($user['study_status']??'')==='Completed'?'selected':''; ?>>Completed</option>
                    </select>
                </div>
                <div><label class="form-label">Graduation School</label><input class="form-control" type="text" name="grad_school" value="<?php echo htmlspecialchars($user['grad_school'] ?? ''); ?>"></div>
                <div><label class="form-label">High School</label><input class="form-control" type="text" name="high_school" value="<?php echo htmlspecialchars($user['high_school'] ?? ''); ?>"></div>
            </div>
            <div style="margin-top:16px;">
                <label class="form-label">Bio / About Me</label>
                <textarea class="form-control" name="bio" rows="3"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
            </div>
            <div style="margin-top:12px;">
                <label class="form-label">Technical Skills</label>
                <input class="form-control" type="text" name="technical_skills" value="<?php echo htmlspecialchars($user['technical_skills'] ?? ''); ?>" placeholder="e.g. Photoshop, Python, Figma">
            </div>
            <div style="margin-top:12px;">
                <label class="form-label">Soft Skills</label>
                <input class="form-control" type="text" name="soft_skills" value="<?php echo htmlspecialchars($user['soft_skills'] ?? ''); ?>" placeholder="e.g. Communication, Teamwork">
            </div>
            <input type="hidden" name="current_institution" value="">
            <input type="hidden" name="portfolio_bio" value="">
            <button type="submit" name="save" class="save-btn">Save Profile</button>
            <div style="clear:both;"></div>
        </form>
    </div>

    <div style="width:280px;position:sticky;top:30px;flex-shrink:0;">
        <div class="card" style="margin-top:0;">
            <p style="font-weight:700;font-size:0.85rem;color:#0f172a;">Your Public URL</p>
            <p style="font-size:0.75rem;color:var(--blue);margin-top:6px;word-break:break-all;">
                studentgig.com/in/<?php echo strtolower(str_replace(' ', '-', $student_name)); ?>
            </p>
        </div>
        <div class="card">
            <p style="font-weight:700;font-size:0.85rem;color:#0f172a;margin-bottom:12px;">Stats</p>
            <div style="display:flex;justify-content:space-between;font-size:0.875rem;margin-bottom:8px;">
                <span style="color:#64748b;">Tasks completed</span>
                <strong><?php echo $completed_count; ?></strong>
            </div>
            <?php if($badge): ?>
            <div style="display:flex;justify-content:space-between;font-size:0.875rem;">
                <span style="color:#64748b;">Badge</span>
                <strong style="color:<?php echo $b_color; ?>"><?php echo $badge; ?></strong>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
function openModal()  { document.getElementById('portfolioModal').style.display = 'flex'; }
function closeModal() { document.getElementById('portfolioModal').style.display = 'none'; }
window.onclick = e => { if(e.target === document.getElementById('portfolioModal')) closeModal(); }
</script>
</body>
</html>
