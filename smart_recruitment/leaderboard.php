<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}
include "db.php";

// FIXED: NULL avg_rating now sorts to bottom, not top
$sql = "SELECT users.id, users.name, users.skills,
               COUNT(DISTINCT applications.id) as total_tasks,
               ROUND(AVG(reviews.rating),1) as avg_rating,
               COUNT(DISTINCT reviews.id) as review_count
        FROM users
        LEFT JOIN applications ON users.id = applications.candidate_id AND applications.is_selected=1
        LEFT JOIN reviews ON users.id = reviews.reviewee_id
        WHERE users.role = 'candidate'
        GROUP BY users.id
        ORDER BY avg_rating IS NULL, avg_rating DESC, total_tasks DESC
        LIMIT 20";

$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Top Freelancers | StudentGig</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',sans-serif;background:#f8fafc;padding:40px 20px}
        .container{max-width:800px;margin:auto}
        .header{text-align:center;margin-bottom:40px}
        .header h1{font-size:2rem;font-weight:700;color:#0f172a}
        .header p{color:#64748b;margin-top:8px}
        .card{background:white;border-radius:12px;border:1px solid #e2e8f0;padding:20px 24px;margin-bottom:14px;display:flex;align-items:center;gap:20px;transition:0.2s}
        .card:hover{transform:translateX(4px);border-color:#6366f1}
        .rank{font-size:1.5rem;font-weight:900;color:#cbd5e1;width:40px;text-align:center;flex-shrink:0}
        .rank.gold{color:#f59e0b}.rank.silver{color:#94a3b8}.rank.bronze{color:#b45309}
        .avatar{width:50px;height:50px;border-radius:50%;background:#6366f1;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:1.1rem;flex-shrink:0}
        .info{flex:1}
        .name{font-weight:700;font-size:1rem;color:#0f172a}
        .skills{font-size:0.82rem;color:#64748b;margin-top:2px}
        .stats{text-align:right;flex-shrink:0}
        .rating{font-size:1.2rem;font-weight:700;color:#0f172a}
        .stars{color:#f59e0b;font-size:0.82rem}
        .tasks-info{font-size:0.78rem;color:#64748b;margin-top:4px}
        .badge-gold{background:#fef3c7;color:#92400e;padding:3px 10px;border-radius:20px;font-size:0.7rem;font-weight:700;margin-left:8px}
        .back{display:inline-block;margin-bottom:24px;color:#64748b;text-decoration:none;font-weight:600;font-size:0.9rem}
        .back:hover{color:#6366f1}
    </style>
</head>
<body>
<div class="container">
    <a href="index.html" class="back"><i class="fas fa-arrow-left"></i> Back</a>
    <div class="header">
        <h1><i class="fas fa-trophy" style="color:#f59e0b"></i> Top Freelancers</h1>
        <p>Ranked by rating and completed tasks</p>
    </div>

    <?php $rank=1; while($row=mysqli_fetch_assoc($result)): ?>
    <?php
        $rankClass = $rank==1?'gold':($rank==2?'silver':($rank==3?'bronze':''));
        $initial   = strtoupper(substr($row['name'],0,1));
        $rating    = $row['avg_rating'];
        $stars = '';
        if(is_numeric($rating)) {
            $full = floor($rating);
            for($i=0;$i<$full;$i++) $stars.='&#9733;';
            for($i=$full;$i<5;$i++) $stars.='&#9734;';
        }
    ?>
    <div class="card">
        <div class="rank <?php echo $rankClass; ?>">#<?php echo $rank; ?></div>
        <div class="avatar"><?php echo $initial; ?></div>
        <div class="info">
            <div class="name">
                <?php echo htmlspecialchars($row['name']); ?>
                <?php if($rank==1): ?><span class="badge-gold">&#9733; Top Rated</span><?php endif; ?>
            </div>
            <div class="skills"><?php echo htmlspecialchars($row['skills']??'Student Freelancer'); ?></div>
        </div>
        <div class="stats">
            <?php if(is_numeric($rating)): ?>
                <div class="rating"><?php echo $rating; ?>/5</div>
                <div class="stars"><?php echo $stars; ?></div>
            <?php else: ?>
                <div class="rating" style="color:#94a3b8;font-size:0.85rem;">No rating yet</div>
            <?php endif; ?>
            <div class="tasks-info"><?php echo $row['total_tasks']; ?> task(s) &bull; <?php echo $row['review_count']; ?> review(s)</div>
        </div>
    </div>
    <?php $rank++; endwhile; ?>

    <?php if($rank==1): ?>
    <div style="text-align:center;padding:60px;background:white;border-radius:12px;border:1px solid #e2e8f0;color:#94a3b8;">
        No freelancers yet. <a href="register.html" style="color:#6366f1;font-weight:600;">Be the first to sign up!</a>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
