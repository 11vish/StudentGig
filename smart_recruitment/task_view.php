<?php
session_start();
include "db.php";

// FIXED: Only candidates can view and bid on tasks
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'candidate') {
    header("Location: index.html");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: browse_tasks.php");
    exit();
}

$task_id    = intval($_GET['id']);
$student_id = intval($_SESSION['user_id']);

// FIXED: Prepared statement — no raw SQL
$stmt = $conn->prepare(
    "SELECT tasks.*, users.name as employer_name
     FROM tasks
     JOIN users ON tasks.employer_id = users.id
     WHERE tasks.id = ?"
);
$stmt->bind_param("i", $task_id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();

if (!$task) {
    header("Location: browse_tasks.php");
    exit();
}

// Check if already applied
$check = $conn->prepare("SELECT id FROM applications WHERE job_id = ? AND candidate_id = ?");
$check->bind_param("ii", $task_id, $student_id);
$check->execute();
$already_applied = $check->get_result()->num_rows > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($task['title']); ?> | StudentGig</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',sans-serif;background:#f1f5f9;padding:40px 20px;display:flex;justify-content:center}
        .container{background:#fff;width:100%;max-width:720px;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.06)}
        .header{padding:32px;border-bottom:1px solid #f1f5f9}
        .back{color:#64748b;text-decoration:none;font-size:0.875rem;display:inline-flex;align-items:center;gap:6px;margin-bottom:20px}
        .back:hover{color:#6366f1}
        .task-title{font-size:1.6rem;font-weight:700;color:#0f172a;margin-bottom:6px}
        .employer{color:#6366f1;font-weight:600;font-size:0.9rem;margin-bottom:16px}
        .meta-row{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
        .description{color:#475569;font-size:0.95rem;line-height:1.6;max-width:80%}
        .price-badge{background:#dcfce7;color:#166534;padding:10px 18px;border-radius:20px;font-weight:700;font-size:1.1rem;white-space:nowrap}
        .cat-badge{display:inline-block;background:#eff6ff;color:#2563eb;font-size:0.75rem;font-weight:600;padding:4px 12px;border-radius:20px;margin-bottom:14px}
        .deadline{font-size:0.8rem;color:#92400e;background:#fef3c7;padding:4px 12px;border-radius:20px;display:inline-block;margin-bottom:14px;margin-left:8px}
        .bid-form{padding:32px;background:#fafbfc}
        .form-title{font-size:1rem;font-weight:700;color:#6366f1;border-bottom:2px solid #6366f1;display:inline-block;padding-bottom:4px;margin-bottom:24px}
        label{display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;margin-bottom:8px}
        input,textarea{width:100%;padding:12px 14px;border:1.5px solid #e2e8f0;border-radius:10px;margin-bottom:20px;font-family:inherit;outline:none;font-size:0.9rem;background:#f8fafc}
        input:focus,textarea:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,0.1);background:white}
        .btn-submit{background:#6366f1;color:#fff;border:none;padding:14px;width:100%;border-radius:10px;font-weight:700;font-size:1rem;cursor:pointer;transition:0.2s}
        .btn-submit:hover{background:#4f46e5;transform:translateY(-1px)}
        .already-applied{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;padding:20px;border-radius:12px;text-align:center;font-weight:600}
        .grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <a href="browse_tasks.php" class="back"><i class="fas fa-arrow-left"></i> Back to Gigs</a>
        <div class="task-title"><?php echo htmlspecialchars($task['title']); ?></div>
        <div class="employer"><i class="fas fa-building"></i> Posted by <?php echo htmlspecialchars($task['employer_name']); ?></div>
        <?php if(!empty($task['category'])): ?>
            <span class="cat-badge"><?php echo htmlspecialchars($task['category']); ?></span>
        <?php endif; ?>
        <?php if(!empty($task['deadline'])): ?>
            <?php $days = (int)((strtotime($task['deadline'])-time())/86400); ?>
            <span class="deadline"><i class="fas fa-clock"></i>
                <?php echo $days < 0 ? 'Expired' : ($days == 0 ? 'Due today' : "Due in $days days"); ?>
            </span>
        <?php endif; ?>
        <div class="meta-row">
            <p class="description"><?php echo htmlspecialchars($task['description']); ?></p>
            <div class="price-badge">&#8377;<?php echo number_format($task['price']); ?></div>
        </div>
    </div>

    <div class="bid-form">
        <?php if ($already_applied): ?>
            <div class="already-applied">
                <i class="fas fa-check-circle" style="font-size:1.5rem;display:block;margin-bottom:8px;"></i>
                You have already applied for this task.<br>
                <a href="my_applications.php" style="color:#059669;text-decoration:none;font-size:0.875rem;margin-top:8px;display:inline-block;">View your applications &rarr;</a>
            </div>
        <?php elseif($task['status'] !== 'open'): ?>
            <div style="background:#f1f5f9;padding:20px;border-radius:12px;text-align:center;color:#64748b;">
                <i class="fas fa-lock" style="display:block;font-size:1.5rem;margin-bottom:8px;"></i>
                This task is no longer accepting applications.
            </div>
        <?php else: ?>
            <div class="form-title">Place Your Bid</div>
            <form action="apply_logic.php" method="POST">
                <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
                <div class="grid2">
                    <div>
                        <label>Your Bid Amount (&#8377;)</label>
                        <input type="number" name="bid_amount" value="<?php echo $task['price']; ?>" min="1" required>
                    </div>
                    <div>
                        <label>Delivery Time (Days)</label>
                        <input type="number" name="delivery_days" placeholder="e.g. 3" min="1" required>
                    </div>
                </div>
                <label>Your Proposal — Why should they hire you?</label>
                <textarea name="proposal_text" rows="5" placeholder="Mention your skills and why you are the best fit for this task..." required></textarea>
                <button type="submit" name="apply_task" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Submit Your Offer
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
