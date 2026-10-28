<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'employer') {
    header("Location: index.html");
    exit();
}

$employer_id = intval($_SESSION['user_id']);

if (isset($_GET['id'])) {
    $application_id = intval($_GET['id']);

    // FIXED: Prepared statement + verify employer owns this task
    $stmt = $conn->prepare(
        "SELECT applications.job_id, applications.candidate_id
         FROM applications
         JOIN tasks ON applications.job_id = tasks.id
         WHERE applications.id = ? AND tasks.employer_id = ?"
    );
    $stmt->bind_param("ii", $application_id, $employer_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row) {
        $task_id   = $row['job_id'];
        $student_id = $row['candidate_id'];

        // Mark this student as selected
        $s1 = $conn->prepare("UPDATE applications SET is_selected = 1 WHERE id = ?");
        $s1->bind_param("i", $application_id);
        $s1->execute();

        // Mark task as assigned
        $s2 = $conn->prepare("UPDATE tasks SET status = 'assigned' WHERE id = ?");
        $s2->bind_param("i", $task_id);
        $s2->execute();

        header("Location: view_applicants.php?status=assigned");
    } else {
        header("Location: view_applicants.php?status=error");
    }
} else {
    header("Location: view_applicants.php");
}
exit();
