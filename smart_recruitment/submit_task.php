<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'candidate') {
    header("Location: index.html");
    exit();
}

// Get IDs from URL safely
if (!isset($_GET['app_id'], $_GET['task_id'])) {
    header("Location: my_applications.php");
    exit();
}

$app_id     = intval($_GET['app_id']);
$task_id    = intval($_GET['task_id']);
$student_id = intval($_SESSION['user_id']);

// FIXED: Verify this app_id actually belongs to this student
$check = $conn->prepare(
    "SELECT id FROM applications WHERE id = ? AND candidate_id = ? AND is_selected = 1"
);
$check->bind_param("ii", $app_id, $student_id);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
    // Not their application — stop here
    header("Location: my_applications.php?msg=unauthorized");
    exit();
}

if (isset($_POST['submit_work'])) {
    $link = $_POST['link'];
    $note = $_POST['note'] ?? '';

    // Basic URL validation
    if (!filter_var($link, FILTER_VALIDATE_URL)) {
        $error = "Please enter a valid URL (starting with https://).";
    } else {
        // FIXED: Use prepared statements for both updates
        $update_app = $conn->prepare(
            "UPDATE applications SET submission_link = ?, submission_note = ? WHERE id = ?"
        );
        $update_app->bind_param("ssi", $link, $note, $app_id);

        $update_task = $conn->prepare("UPDATE tasks SET status = 'completed' WHERE id = ?");
        $update_task->bind_param("i", $task_id);

        if ($update_app->execute() && $update_task->execute()) {
            header("Location: my_applications.php?msg=submitted");
            exit();
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="assets/css/style.css?v=3">
    <meta charset="UTF-8">
    <title>Submit Work | StudentGig</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; padding: 40px; display: flex; justify-content: center; }
        .submit-card { background: white; padding: 30px; border-radius: 15px; border: 1px solid #e2e8f0; width: 100%; max-width: 500px; }
        input, textarea { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; }
        .btn { background: #2563eb; color: white; padding: 12px; border: none; border-radius: 8px; width: 100%; font-weight: 600; cursor: pointer; }
        .error { background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="submit-card">
        <a href="my_applications.php" style="display:inline-block;margin-bottom:16px;color:#64748b;text-decoration:none;font-size:0.875rem;font-weight:600;">&#8592; Back to My Applications</a>
        <h2>Submit Your Work</h2>
        <p style="color: #64748b; margin-bottom: 20px;">Provide the link to your finished work (e.g., Google Drive, Dropbox).</p>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <input type="hidden" name="app_id"  value="<?php echo $app_id; ?>">
            <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">

            <label>Work Link (Required)</label>
            <input type="url" name="link" placeholder="https://drive.google.com/..." required>

            <label>Notes for Employer</label>
            <textarea name="note" rows="4" placeholder="Mention any specific details..."></textarea>

            <button type="submit" name="submit_work" class="btn">Finish Task</button>
        </form>
    </div>
</body>
</html>
