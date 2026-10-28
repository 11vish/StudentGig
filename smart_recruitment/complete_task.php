<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'candidate') {
    header("Location: index.html");
    exit();
}

if (isset($_GET['task_id'])) {

    $task_id    = intval($_GET['task_id']);
    $student_id = intval($_SESSION['user_id']);

    // FIXED: Verify this candidate is actually assigned to this task
    // before allowing them to mark it complete
    $check = $conn->prepare(
        "SELECT id FROM applications
         WHERE job_id = ? AND candidate_id = ? AND is_selected = 1"
    );
    $check->bind_param("ii", $task_id, $student_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        // Not their task — reject silently
        header("Location: my_gigs.php?msg=unauthorized");
        exit();
    }

    // Safe to update now
    $stmt = $conn->prepare("UPDATE tasks SET status = 'completed' WHERE id = ?");
    $stmt->bind_param("i", $task_id);

    if ($stmt->execute()) {
        header("Location: my_gigs.php?msg=task_submitted");
    } else {
        header("Location: my_gigs.php?msg=error");
    }
}
exit();
?>
