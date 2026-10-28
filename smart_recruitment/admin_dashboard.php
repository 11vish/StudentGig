<?php
session_start();
include "db.php";

// FIXED: redirect if not admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.html");
    exit();
}

// Handle actions
if (isset($_GET['ban'])) {
    $uid = intval($_GET['ban']);
    $s = $conn->prepare("UPDATE users SET is_banned=1 WHERE id=? AND role != 'admin'");
    $s->bind_param("i", $uid); $s->execute();
    header("Location: admin_dashboard.php?msg=User+banned"); exit();
}
if (isset($_GET['unban'])) {
    $uid = intval($_GET['unban']);
    $s = $conn->prepare("UPDATE users SET is_banned=0 WHERE id=?");
    $s->bind_param("i", $uid); $s->execute();
    header("Location: admin_dashboard.php?msg=User+unbanned"); exit();
}
if (isset($_GET['deltask'])) {
    $tid = intval($_GET['deltask']);
    $s = $conn->prepare("DELETE FROM tasks WHERE id=?");
    $s->bind_param("i", $tid); $s->execute();
    header("Location: admin_dashboard.php?msg=Task+deleted"); exit();
}
if (isset($_GET['deluser'])) {
    $uid = intval($_GET['deluser']);
    $s = $conn->prepare("DELETE FROM users WHERE id=? AND role != 'admin'");
    $s->bind_param("i", $uid); $s->execute();
    header("Location: admin_dashboard.php?msg=User+deleted"); exit();
}

// Stats — use safe queries that won't break if columns are missing
$total_users   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users WHERE role != 'admin'"))['c'] ?? 0;
$total_tasks   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM tasks"))['c'] ?? 0;
$total_reviews = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM reviews"))['c'] ?? 0;
$completed     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM tasks WHERE status='completed'"))['c'] ?? 0;
$candidates    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users WHERE role='candidate'"))['c'] ?? 0;
$employers     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users WHERE role='employer'"))['c'] ?? 0;

// Users — no created_at so we order by id
$users = mysqli_query($conn, "SELECT * FROM users WHERE role != 'admin' ORDER BY id DESC");

// Tasks
$tasks = mysqli_query($conn, "SELECT tasks.*, users.name as emp_name FROM tasks LEFT JOIN users ON tasks.employer_id=users.id ORDER BY tasks.id DESC LIMIT 30");

$admin_name = $_SESSION['user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | StudentGig</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',sans-serif;background:#0f172a;color:#e2e8f0;min-height:100vh}

        /* TOP BAR */
        .topbar{background:#1e293b;padding:0 40px;height:64px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #334155;position:sticky;top:0;z-index:100}
        .logo{display:flex;align-items:center;gap:12px}
        .logo-icon{width:36px;height:36px;background:#f59e0b;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#0f172a;font-size:16px}
        .logo-text{font-size:1.1rem;font-weight:700;color:white}
        .logo-badge{background:#ef4444;color:white;font-size:0.65rem;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:4px}
        .topbar-right{display:flex;align-items:center;gap:16px}
        .admin-chip{display:flex;align-items:center;gap:8px;background:#334155;border-radius:30px;padding:6px 14px 6px 6px}
        .admin-avatar{width:28px;height:28px;border-radius:50%;background:#f59e0b;color:#0f172a;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700}
        .admin-name{font-size:0.8rem;font-weight:600;color:#e2e8f0}
        .logout-btn{color:#94a3b8;text-decoration:none;font-size:0.875rem;display:flex;align-items:center;gap:6px;padding:8px 12px;border-radius:8px;border:1px solid #334155;transition:all 0.2s}
        .logout-btn:hover{background:#334155;color:white}

        /* LAYOUT */
        .container{padding:36px 40px;max-width:1280px;margin:auto}

        /* ALERT */
        .alert{background:#1e3a5f;color:#93c5fd;border:1px solid #1e40af;padding:14px 40px;font-size:0.875rem;display:flex;align-items:center;gap:10px}

        /* STATS GRID */
        .stats-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:14px;margin-bottom:32px}
        .stat{background:#1e293b;border:1px solid #334155;border-radius:14px;padding:20px;text-align:center}
        .stat-n{font-size:1.8rem;font-weight:700;margin-bottom:4px}
        .stat-l{font-size:0.75rem;color:#64748b;font-weight:500;text-transform:uppercase;letter-spacing:0.5px}
        .stat.amber .stat-n{color:#f59e0b}
        .stat.blue .stat-n{color:#60a5fa}
        .stat.green .stat-n{color:#34d399}
        .stat.purple .stat-n{color:#a78bfa}
        .stat.red .stat-n{color:#f87171}
        .stat.teal .stat-n{color:#2dd4bf}

        /* SECTION */
        .section{background:#1e293b;border:1px solid #334155;border-radius:14px;margin-bottom:28px;overflow:hidden}
        .section-head{padding:18px 24px;border-bottom:1px solid #334155;display:flex;align-items:center;justify-content:space-between}
        .section-title{font-size:0.95rem;font-weight:600;color:#f1f5f9;display:flex;align-items:center;gap:10px}
        .section-title i{color:#f59e0b}
        .count-badge{background:#334155;color:#94a3b8;font-size:0.75rem;padding:3px 10px;border-radius:20px}

        /* TABLE */
        table{width:100%;border-collapse:collapse}
        thead th{padding:12px 20px;text-align:left;font-size:0.72rem;color:#475569;text-transform:uppercase;letter-spacing:0.06em;border-bottom:1px solid #334155;background:#0f172a}
        tbody td{padding:14px 20px;border-bottom:1px solid #1e293b;font-size:0.875rem;color:#cbd5e1;vertical-align:middle}
        tbody tr:last-child td{border-bottom:none}
        tbody tr:hover td{background:#162032}

        /* BADGES */
        .role-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:0.72rem;font-weight:600}
        .role-candidate{background:#1e3a5f;color:#93c5fd}
        .role-employer{background:#14532d;color:#86efac}
        .role-banned{background:#7f1d1d;color:#fca5a5}
        .status-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:0.72rem;font-weight:600}
        .s-open{background:#1e3a5f;color:#93c5fd}
        .s-assigned{background:#422006;color:#fcd34d}
        .s-completed{background:#14532d;color:#86efac}

        /* ACTION BUTTONS */
        .action-btns{display:flex;gap:6px;align-items:center}
        .btn-action{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:6px;font-size:0.75rem;font-weight:600;text-decoration:none;cursor:pointer;border:none;font-family:inherit;transition:all 0.15s}
        .btn-ban{background:#7f1d1d;color:#fca5a5}.btn-ban:hover{background:#991b1b}
        .btn-unban{background:#14532d;color:#86efac}.btn-unban:hover{background:#166534}
        .btn-del{background:#3f0e0e;color:#f87171;border:1px solid #7f1d1d}.btn-del:hover{background:#7f1d1d}

        /* EMPTY STATE */
        .empty{text-align:center;padding:50px;color:#475569}
        .empty i{font-size:2.5rem;display:block;margin-bottom:12px;color:#334155}

        /* USER AVATAR */
        .user-cell{display:flex;align-items:center;gap:10px}
        .u-avatar{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0}
        .u-candidate{background:#1e3a5f;color:#93c5fd}
        .u-employer{background:#14532d;color:#86efac}
        .u-banned{background:#7f1d1d;color:#fca5a5}

        @media(max-width:900px){.stats-grid{grid-template-columns:repeat(3,1fr)}}
        @media(max-width:600px){.stats-grid{grid-template-columns:repeat(2,1fr)}.container{padding:20px}}
    </style>
</head>
<body>

<div class="topbar">
    <div class="logo">
        <div class="logo-icon"><i class="fas fa-shield-alt"></i></div>
        <span class="logo-text">Admin Panel</span>
        <span class="logo-badge">SUPER ADMIN</span>
    </div>
    <div class="topbar-right">
        <div class="admin-chip">
            <div class="admin-avatar"><?php echo strtoupper(substr($admin_name,0,1)); ?></div>
            <span class="admin-name"><?php echo htmlspecialchars($admin_name); ?></span>
        </div>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<?php if(isset($_GET['msg'])): ?>
<div class="alert"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>

<div class="container">

    <!-- Page Title -->
    <div style="margin-bottom:28px;">
        <h1 style="font-size:1.5rem;font-weight:700;color:white;letter-spacing:-0.3px;">Dashboard Overview</h1>
        <p style="color:#64748b;margin-top:4px;font-size:0.875rem;">Manage all users, tasks, and platform activity.</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat amber"><div class="stat-n"><?php echo $total_users; ?></div><div class="stat-l">Total Users</div></div>
        <div class="stat blue"><div class="stat-n"><?php echo $candidates; ?></div><div class="stat-l">Students</div></div>
        <div class="stat green"><div class="stat-n"><?php echo $employers; ?></div><div class="stat-l">Employers</div></div>
        <div class="stat purple"><div class="stat-n"><?php echo $total_tasks; ?></div><div class="stat-l">Total Tasks</div></div>
        <div class="stat teal"><div class="stat-n"><?php echo $completed; ?></div><div class="stat-l">Completed</div></div>
        <div class="stat red"><div class="stat-n"><?php echo $total_reviews; ?></div><div class="stat-l">Reviews</div></div>
    </div>

    <!-- Users Table -->
    <div class="section">
        <div class="section-head">
            <div class="section-title"><i class="fas fa-users"></i> All Users</div>
            <span class="count-badge"><?php echo $total_users; ?> users</span>
        </div>
        <?php if(mysqli_num_rows($users) == 0): ?>
            <div class="empty"><i class="fas fa-users"></i>No users registered yet.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while($u = mysqli_fetch_assoc($users)): ?>
            <?php
                $avatarClass = $u['is_banned'] ? 'u-banned' : 'u-'.$u['role'];
                $initial = strtoupper(substr($u['name'],0,1));
            ?>
            <tr>
                <td>
                    <div class="user-cell">
                        <div class="u-avatar <?php echo $avatarClass; ?>"><?php echo $initial; ?></div>
                        <div>
                            <div style="font-weight:600;color:#f1f5f9;"><?php echo htmlspecialchars($u['name']); ?></div>
                            <div style="font-size:0.75rem;color:#64748b;">ID: <?php echo $u['id']; ?></div>
                        </div>
                    </div>
                </td>
                <td style="color:#94a3b8;"><?php echo htmlspecialchars($u['email']); ?></td>
                <td>
                    <span class="role-badge <?php echo $u['is_banned']?'role-banned':'role-'.$u['role']; ?>">
                        <i class="fas fa-<?php echo $u['role']==='candidate'?'graduation-cap':'briefcase'; ?>"></i>
                        <?php echo ucfirst($u['role']); ?>
                    </span>
                </td>
                <td>
                    <?php if($u['is_banned']): ?>
                        <span style="color:#f87171;font-size:0.8rem;"><i class="fas fa-ban"></i> Banned</span>
                    <?php else: ?>
                        <span style="color:#34d399;font-size:0.8rem;"><i class="fas fa-check-circle"></i> Active</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="action-btns">
                        <?php if($u['is_banned']): ?>
                            <a href="admin_dashboard.php?unban=<?php echo $u['id']; ?>" class="btn-action btn-unban"><i class="fas fa-unlock"></i> Unban</a>
                        <?php else: ?>
                            <a href="admin_dashboard.php?ban=<?php echo $u['id']; ?>" class="btn-action btn-ban" onclick="return confirm('Ban <?php echo htmlspecialchars($u['name']); ?>?')"><i class="fas fa-ban"></i> Ban</a>
                        <?php endif; ?>
                        <a href="admin_dashboard.php?deluser=<?php echo $u['id']; ?>" class="btn-action btn-del" onclick="return confirm('Permanently delete this user?')"><i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Tasks Table -->
    <div class="section">
        <div class="section-head">
            <div class="section-title"><i class="fas fa-tasks"></i> All Tasks</div>
            <span class="count-badge"><?php echo $total_tasks; ?> tasks</span>
        </div>
        <?php if(mysqli_num_rows($tasks) == 0): ?>
            <div class="empty"><i class="fas fa-inbox"></i>No tasks posted yet.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Task Title</th>
                    <th>Employer</th>
                    <th>Category</th>
                    <th>Budget</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php while($t = mysqli_fetch_assoc($tasks)): ?>
            <tr>
                <td style="font-weight:600;color:#f1f5f9;max-width:240px;">
                    <?php echo htmlspecialchars($t['title']); ?>
                </td>
                <td style="color:#94a3b8;"><?php echo htmlspecialchars($t['emp_name'] ?? '—'); ?></td>
                <td>
                    <?php if(!empty($t['category'])): ?>
                    <span style="background:#1e293b;border:1px solid #334155;color:#94a3b8;padding:3px 9px;border-radius:6px;font-size:0.75rem;">
                        <?php echo htmlspecialchars($t['category']); ?>
                    </span>
                    <?php else: echo '<span style="color:#475569;">—</span>'; endif; ?>
                </td>
                <td style="color:#f59e0b;font-weight:600;">&#8377;<?php echo number_format($t['price'], 0); ?></td>
                <td><span class="status-badge s-<?php echo $t['status']; ?>"><?php echo ucfirst($t['status']); ?></span></td>
                <td>
                    <a href="admin_dashboard.php?deltask=<?php echo $t['id']; ?>" class="btn-action btn-del" onclick="return confirm('Delete this task permanently?')"><i class="fas fa-trash"></i> Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
