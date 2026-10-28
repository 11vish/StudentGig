<?php
session_start();
include "db.php";

// Security check
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

if (isset($_POST['save'])) {

    // FIXED: Always cast the user ID to integer from session
    $user_id = intval($_SESSION['user_id']);

    // Get all inputs (prepared statement handles safety below — no need for escape string)
    $skills           = $_POST['skills']           ?? '';
    $bio              = $_POST['bio']              ?? '';
    $location         = $_POST['location']         ?? '';
    $university       = $_POST['university']       ?? '';
    $current_institution = $_POST['current_institution'] ?? '';
    $high_school      = $_POST['high_school']      ?? '';
    $grad_school      = $_POST['grad_school']      ?? '';
    $study_status     = $_POST['study_status']     ?? '';
    $technical_skills = $_POST['technical_skills'] ?? '';
    $soft_skills      = $_POST['soft_skills']      ?? '';
    $portfolio_bio    = $_POST['portfolio_bio']    ?? '';

    // Whitelist study_status so it can't be set to arbitrary values
    $allowed_statuses = ['Studying', 'Completed'];
    if (!in_array($study_status, $allowed_statuses)) {
        $study_status = 'Studying';
    }

    // FIXED: Prepared statement — safe, no raw string building
    $stmt = $conn->prepare(
        "UPDATE users SET
            skills = ?,
            bio = ?,
            location = ?,
            university = ?,
            current_institution = ?,
            high_school = ?,
            grad_school = ?,
            study_status = ?,
            technical_skills = ?,
            soft_skills = ?,
            portfolio_bio = ?
         WHERE id = ?"
    );
    $stmt->bind_param(
        "sssssssssssi",
        $skills, $bio, $location, $university,
        $current_institution, $high_school, $grad_school,
        $study_status, $technical_skills, $soft_skills,
        $portfolio_bio, $user_id
    );

    if ($stmt->execute()) {
        header("Location: profile.php?status=updated");
    } else {
        // FIXED: Log error privately, don't show raw DB error to user
        error_log("Profile update error: " . $conn->error);
        header("Location: profile.php?status=error");
    }

} else {
    header("Location: profile.php");
}
exit();
?>
