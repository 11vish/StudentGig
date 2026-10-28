<?php
session_start();
include "db.php";

// FIXED: was $_SESSION['role'] — wrong key, broke all bidding
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'candidate') {
    header("Location: index.html");
    exit();
}

if (isset($_POST['apply_task'])) {

    $task_id      = intval($_POST['task_id']);       // FIXED: cast to int, safe
    $student_id   = intval($_SESSION['user_id']);    // FIXED: always cast session IDs
    $bid_amount   = intval($_POST['bid_amount']);
    $delivery_days = intval($_POST['delivery_days']);
    $proposal_text = $_POST['proposal_text'];        // handled by prepared statement below

    // Validate numbers make sense
    if ($bid_amount <= 0 || $delivery_days <= 0) {
        header("Location: candidate_dashboard.php?msg=invalid_bid");
        exit();
    }

    // FIXED: Use prepared statement — no more raw query string
    $check = $conn->prepare("SELECT id FROM applications WHERE job_id = ? AND candidate_id = ?");
    $check->bind_param("ii", $task_id, $student_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        header("Location: candidate_dashboard.php?msg=already_applied");
        exit();
    }

    // FIXED: prepared statement for INSERT too
    $stmt = $conn->prepare(
        "INSERT INTO applications (job_id, candidate_id, bid_amount, delivery_days, proposal_text, status)
         VALUES (?, ?, ?, ?, ?, 'pending')"
    );
    $stmt->bind_param("iiiss", $task_id, $student_id, $bid_amount, $delivery_days, $proposal_text);

    if ($stmt->execute()) {
        header("Location: candidate_dashboard.php?msg=bid_success");
    } else {
        header("Location: candidate_dashboard.php?msg=error");
    }

} else {
    header("Location: browse_tasks.php");
}
exit();
?>
