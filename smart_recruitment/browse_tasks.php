<?php
session_start();
include "db.php";
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'candidate') {
    header("Location: index.html"); exit();
}

$search   = isset($_GET['search'])   ? trim($_GET['search'])   : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

$sql = "SELECT tasks.*, users.name AS employer_name FROM tasks JOIN users ON tasks.employer_id = users.id WHERE tasks.status = 'open'";
$params = []; $types = '';
if ($search !== '')   { $sql .= " AND tasks.title LIKE ?";    $params[] = '%'.$search.'%'; $types .= 's'; }
if ($category !== '') { $sql .= " AND tasks.category = ?";    $params[] = $category;       $types .= 's'; }
$sql .= " ORDER BY tasks.created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

// FIXED: category list now matches post_task.php exactly
$all_categories = ['Data Entry','Cover Making','Photo Shoot','Ad Campaign','Website Building','Video Editing','Content Writing','Social Media'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse Gigs | StudentGig</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',sans-serif;background:#f8fafc;display:flex;min-height:100vh}
        .sidebar{width:260px;background:#0f172a;height:100vh;border-right:1px solid #1e293b;position:fixed;left:0;top:0;padding:24px;z-index:1000;display:flex;flex-direction:column}
        .brand{display:flex;align-items:center;gap:10px;margin-bottom:40px}
        .brand-icon{width:36px;height:36px;background:#6366f1;border-radius:10px;display:flex;align-items:center;justify-content:center;color:white;font-size:16px}
        .brand-name{font-size:1.1rem;font-weight:700;color:white}
        .nav-link{display:flex;align-items:center;gap:12px;padding:11px 12px;border-radius:10px;text-decoration:none;color:#94a3b8;font-size:0.875rem;font-weight:500;margin-bottom:2px;transition:0.18s}
        .nav-link:hover{background:rgba(255,255,255,0.07);color:white}
        .nav-link.active{background:#6366f1;color:white}
        .sidebar-footer{margin-top:auto;border-top:1px solid #1e293b;padding-top:16px}
        .logout-link{display:flex;align-items:center;gap:12px;padding:11px 12px;border-radius:10px;text-decoration:none;color:#ef4444;font-size:0.875rem;font-weight:500}
        .main-content{margin-left:260px;width:calc(100% - 260px);padding:40px}
        .search-bar{display:flex;gap:10px;margin-bottom:24px;flex-wrap:wrap}
        .search-bar input,.search-bar select{padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:0.875rem;font-family:inherit;outline:none;background:#f8fafc}
        .search-bar input{flex:1;min-width:180px}
        .search-bar input:focus,.search-bar select:focus{border-color:#6366f1;background:white}
        .search-bar button{background:#6366f1;color:white;border:none;padding:10px 20px;border-radius:9px;font-weight:600;cursor:pointer;font-family:inherit}
        .search-bar a{padding:10px 14px;color:#64748b;text-decoration:none;border:1.5px solid #e2e8f0;border-radius:9px;font-size:0.875rem}
        .gig-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:18px}
        .gig-card{background:white;padding:22px;border-radius:14px;border:1px solid #e2e8f0;display:flex;flex-direction:column;transition:0.2s;position:relative;overflow:hidden}
        .gig-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:#6366f1;opacity:0;transition:opacity 0.2s}
        .gig-card:hover{transform:translateY(-4px);box-shadow:0 10px 24px rgba(0,0,0,0.07);border-color:#c7d2fe}
        .gig-card:hover::before{opacity:1}
        .price-tag{color:#059669;font-weight:700;font-size:1.2rem;margin-bottom:6px}
        .cat-badge{display:inline-block;background:#eff6ff;color:#2563eb;font-size:0.72rem;font-weight:600;padding:3px 10px;border-radius:20px;margin-bottom:8px}
        .employer-name{font-size:0.82rem;color:#64748b;margin-bottom:10px}
        .deadline-badge{font-size:0.75rem;color:#92400e;background:#fef3c7;padding:3px 9px;border-radius:20px;display:inline-block;margin-bottom:10px}
        .deadline-urgent{font-size:0.75rem;background:#fee2e2;color:#991b1b;padding:3px 9px;border-radius:20px;display:inline-block;margin-bottom:10px}
        .btn-apply{background:#6366f1;color:white;text-align:center;padding:11px;border-radius:9px;text-decoration:none;font-weight:600;margin-top:auto;display:block;transition:background 0.2s}
        .btn-apply:hover{background:#4f46e5}
    </style>
</head>
<body>
<aside class="sidebar">
    <div class="brand"><div class="brand-icon"><i class="fas fa-bolt"></i></div><div class="brand-name">StudentGig</div></div>
    <a href="candidate_dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
    <a href="browse_tasks.php" class="nav-link active"><i class="fas fa-search"></i> Browse Gigs</a>
    <a href="my_applications.php" class="nav-link"><i class="fas fa-paper-plane"></i> My Applications</a>
    <a href="profile.php" class="nav-link"><i class="fas fa-user-circle"></i> My Profile</a>
    <a href="leaderboard.php" class="nav-link"><i class="fas fa-trophy"></i> Leaderboard</a>
    <div class="sidebar-footer"><a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
</aside>
<main class="main-content">
    <h1 style="margin-bottom:6px;font-size:1.5rem;font-weight:700;">Available Gigs</h1>
    <p style="color:#64748b;margin-bottom:24px;">Find a task that matches your skills and start earning.</p>

    <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="Search by title..." value="<?php echo htmlspecialchars($search); ?>">
        <select name="category">
            <option value="">All Categories</option>
            <?php foreach($all_categories as $c): ?>
                <option value="<?php echo $c;?>" <?php echo $category===$c?'selected':'';?>><?php echo $c;?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit"><i class="fas fa-search"></i> Search</button>
        <?php if($search||$category): ?><a href="browse_tasks.php">Clear</a><?php endif; ?>
    </form>

    <p style="font-size:0.875rem;color:#64748b;margin-bottom:16px;"><?php echo $result->num_rows;?> gig(s) found</p>

    <div class="gig-grid">
        <?php while($row=$result->fetch_assoc()): ?>
        <?php
            $dl='';
            if(!empty($row['deadline'])){
                $days=(int)((strtotime($row['deadline'])-time())/86400);
                $cls=$days<=2?'deadline-urgent':'deadline-badge';
                $txt=$days<0?'Expired':($days==0?'Due today':"Due in $days days");
                $dl="<span class='$cls'><i class='fas fa-clock'></i> $txt</span>";
            }
        ?>
        <div class="gig-card">
            <span class="price-tag">&#8377;<?php echo number_format($row['price'],2);?></span>
            <?php if(!empty($row['category'])): ?><span class="cat-badge"><?php echo htmlspecialchars($row['category']);?></span><?php endif;?>
            <h3 style="margin:0 0 6px;font-size:1rem;"><?php echo htmlspecialchars($row['title']);?></h3>
            <div class="employer-name"><i class="fas fa-building"></i> <?php echo htmlspecialchars($row['employer_name']);?></div>
            <?php echo $dl;?>
            <p style="font-size:0.85rem;color:#475569;line-height:1.5;margin-bottom:14px;flex:1;"><?php echo substr(htmlspecialchars($row['description']),0,100);?>...</p>
            <a href="task_view.php?id=<?php echo $row['id'];?>" class="btn-apply">View &amp; Apply</a>
        </div>
        <?php endwhile;?>
        <?php if($result->num_rows==0):?>
        <div style="grid-column:1/-1;text-align:center;padding:60px;background:white;border-radius:12px;border:1px solid #e2e8f0;">
            <i class="fas fa-search" style="font-size:2rem;color:#cbd5e1;display:block;margin-bottom:12px;"></i>
            <p style="color:#64748b;">No gigs found<?php echo($search||$category)?' for your search':'';?>.</p>
            <?php if($search||$category):?><a href="browse_tasks.php" style="color:#6366f1;font-weight:600;text-decoration:none;display:inline-block;margin-top:8px;">View all gigs</a><?php endif;?>
        </div>
        <?php endif;?>
    </div>
</main>
</body>
</html>
