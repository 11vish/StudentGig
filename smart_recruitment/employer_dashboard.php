<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'employer') {
    header("Location: index.html");
    exit();
}

$employer_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "Employer";
$employer_id = intval($_SESSION['user_id']);

$s1 = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE employer_id = ? AND status = 'open'");
$s1->bind_param("i", $employer_id); $s1->execute();
$open_tasks = $s1->get_result()->fetch_assoc()['total'];

$s2 = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE employer_id = ? AND status = 'assigned'");
$s2->bind_param("i", $employer_id); $s2->execute();
$ongoing_tasks = $s2->get_result()->fetch_assoc()['total'];

$s3 = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE employer_id = ? AND status = 'completed'");
$s3->bind_param("i", $employer_id); $s3->execute();
$review_needed = $s3->get_result()->fetch_assoc()['total'];

$s4 = $conn->prepare("SELECT COUNT(*) as total FROM applications JOIN tasks ON applications.job_id = tasks.id WHERE tasks.employer_id = ? AND applications.is_selected = 0");
$s4->bind_param("i", $employer_id); $s4->execute();
$total_apps = $s4->get_result()->fetch_assoc()['total'];

// Chart data
$chart_data = json_encode(['open'=>(int)$open_tasks,'assigned'=>(int)$ongoing_tasks,'completed'=>(int)$review_needed]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="assets/css/style.css?v=3">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Dashboard | StudentGig</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--primary:#10b981;--secondary:#64748b;--bg-light:#f8fafc;--white:#ffffff;--border:#e2e8f0;--text-dark:#0f172a}
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',sans-serif;background:var(--bg-light);color:var(--text-dark);display:flex}
        .sidebar{width:260px;background:var(--white);height:100vh;border-right:1px solid var(--border);position:fixed;display:flex;flex-direction:column;padding:24px}
        .brand{font-size:1.5rem;font-weight:700;color:var(--primary);margin-bottom:40px;display:flex;align-items:center;gap:10px}
        .nav-link{text-decoration:none;color:var(--secondary);padding:12px 16px;border-radius:8px;display:flex;align-items:center;gap:12px;font-weight:500;margin-bottom:8px;transition:0.2s}
        .nav-link:hover,.nav-link.active{background:#ecfdf5;color:var(--primary)}
        .main-content{margin-left:260px;width:calc(100% - 260px);min-height:100vh}
        .top-header{background:var(--white);padding:16px 40px;border-bottom:1px solid var(--border);display:flex;justify-content:flex-end;align-items:center}
        .container{padding:40px;max-width:1200px}
        .dashboard-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px;margin-bottom:32px}
        .card{background:var(--white);padding:32px;border-radius:16px;border:1px solid var(--border);display:flex;flex-direction:column}
        .card:hover{transform:translateY(-4px);box-shadow:0 10px 20px rgba(0,0,0,0.08);transition:0.2s}
        .icon-circle{width:48px;height:48px;background:#ecfdf5;color:var(--primary);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;margin-bottom:20px}
        .badge{background:#ef4444;color:white;padding:2px 8px;border-radius:10px;font-size:0.75rem;margin-left:auto}
        .btn{display:block;padding:12px;background:var(--primary);color:white;border-radius:10px;text-decoration:none;font-weight:600;text-align:center;border:none;cursor:pointer;margin-top:auto}
        .logout-btn{color:#ef4444;padding:12px 16px;text-decoration:none;border-top:1px solid var(--border);margin-top:auto;display:flex;align-items:center;gap:12px}
        .chart-card{background:var(--white);border-radius:16px;border:1px solid var(--border);padding:28px}
        .chart-card h3{margin-bottom:20px;font-size:1rem;color:#475569}
    </style>
</head>
<body>
<aside class="sidebar">
    <div class="brand"><i class="fas fa-bolt"></i> StudentGig</div>
    <nav style="flex-grow:1;">
        <a href="employer_dashboard.php" class="nav-link active"><i class="fas fa-home"></i> Dashboard</a>
        <a href="post_task.php" class="nav-link"><i class="fas fa-plus-circle"></i> Post a Task</a>
        <a href="view_applicants.php" class="nav-link"><i class="fas fa-users"></i> Review Students
            <?php if($total_apps>0): ?><span class="badge"><?php echo $total_apps; ?></span><?php endif; ?>
        </a>
        <a href="manage_tasks.php" class="nav-link"><i class="fas fa-tasks"></i> Task Status</a>
        <a href="leaderboard.php" class="nav-link"><i class="fas fa-trophy"></i> Leaderboard</a>
    </nav>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</aside>
<main class="main-content">
    <header class="top-header">
        <div style="display:flex;align-items:center;gap:12px;">
            <span>Hi, <strong><?php echo htmlspecialchars($employer_name); ?></strong></span>
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($employer_name); ?>&background=10b981&color=fff" style="width:35px;border-radius:50%;">
        </div>
    </header>
    <div class="container">
        <div style="margin-bottom:30px;">
            <h1>Employer Workspace</h1>
            <p style="color:var(--secondary);">Monitor your micro-tasks and track student progress.</p>
        </div>
        <div class="dashboard-grid">
            <div class="card">
                <div class="icon-circle"><i class="fas fa-bullhorn"></i></div>
                <h3>Your Listings</h3>
                <p>You have <strong><?php echo $open_tasks; ?></strong> open and <strong><?php echo $ongoing_tasks; ?></strong> in progress.</p>
                <a href="post_task.php" class="btn" style="margin-top:20px;">Add New Task</a>
            </div>
            <div class="card">
                <div class="icon-circle"><i class="fas fa-user-graduate"></i></div>
                <h3>New Applications</h3>
                <p><strong><?php echo $total_apps; ?></strong> students waiting for your approval.</p>
                <a href="view_applicants.php" class="btn" style="margin-top:20px;">Review Applicants</a>
            </div>
            <div class="card">
                <div class="icon-circle"><i class="fas fa-star"></i></div>
                <h3>Reviews Needed</h3>
                <p><strong><?php echo $review_needed; ?></strong> completed tasks need your rating.</p>
                <a href="manage_tasks.php" class="btn" style="margin-top:20px;background:#f1f5f9;color:#0f172a;">Manage Tasks</a>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="chart-card">
            <h3><i class="fas fa-chart-bar" style="color:#10b981;margin-right:8px;"></i> Task Overview Chart</h3>
            <canvas id="taskChart" style="max-height:260px;"></canvas>
        </div>
    </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
const data = <?php echo $chart_data; ?>;
const ctx = document.getElementById('taskChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Open', 'Assigned / In Progress', 'Completed'],
        datasets: [{
            label: 'Tasks',
            data: [data.open, data.assigned, data.completed],
            backgroundColor: ['#bfdbfe','#fde68a','#bbf7d0'],
            borderColor: ['#2563eb','#d97706','#10b981'],
            borderWidth: 2,
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
</script>
</body>
</html>
