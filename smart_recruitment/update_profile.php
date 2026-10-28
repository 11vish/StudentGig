<?php
session_start();
include "db.php";

// 1. Security: Only Students can access this
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'candidate') {
    header("Location: index.html");
    exit();
}

$student_id = $_SESSION['user_id'];
$success_msg = "";

// 2. Handle the "Save" button click
if (isset($_POST['save_profile'])) {
    $bio = mysqli_real_escape_string($conn, $_POST['bio']);
    $inst = mysqli_real_escape_string($conn, $_POST['institution']);
    $edu = mysqli_real_escape_string($conn, $_POST['education']);
    $tech = mysqli_real_escape_string($conn, $_POST['tech_skills']);
    $soft = mysqli_real_escape_string($conn, $_POST['soft_skills']);

    $update_sql = "UPDATE users SET 
                    portfolio_bio = '$bio', 
                    current_institution = '$inst',
                    education_history = '$edu', 
                    technical_skills = '$tech', 
                    soft_skills = '$soft' 
                   WHERE id = $student_id";

    if (mysqli_query($conn, $update_sql)) {
        $success_msg = "Your professional profile has been updated!";
    }
}

// 3. Fetch current data so the boxes aren't empty when you open the page
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $student_id");
$user = mysqli_fetch_assoc($user_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    
    <meta charset="UTF-8">
    <title>My Professional Profile</title>
    <style>
        body { font-family: sans-serif; background: #f8fafc; padding: 40px; color: #1e293b; }
        .container { max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        label { display: block; margin-top: 15px; font-weight: bold; font-size: 0.9rem; }
        input, textarea { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #e2e8f0; border-radius: 6px; box-sizing: border-box; }
        .btn { background: #2563eb; color: white; padding: 12px; border: none; border-radius: 6px; width: 100%; cursor: pointer; margin-top: 20px; font-weight: bold; }
        .success { background: #dcfce7; color: #166534; padding: 10px; border-radius: 6px; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Edit Professional Details</h2>
    
    <?php if($success_msg) echo "<div class='success'>$success_msg</div>"; ?>

    <form action="" method="POST">
        <label>About Me (Bio)</label>
        <textarea name="bio" rows="3"><?php echo htmlspecialchars($user['portfolio_bio'] ?? ''); ?></textarea>

        <label>Current College / School</label>
        <input type="text" name="institution" value="<?php echo htmlspecialchars($user['current_institution'] ?? ''); ?>">

        <label>Education History (Past schools/degrees)</label>
        <textarea name="education" rows="2"><?php echo htmlspecialchars($user['education_history'] ?? ''); ?></textarea>

        <label>Technical Skills (e.g. Photoshop, Python)</label>
        <input type="text" name="tech_skills" value="<?php echo htmlspecialchars($user['technical_skills'] ?? ''); ?>">

        <label>Soft Skills (e.g. Communication)</label>
        <input type="text" name="soft_skills" value="<?php echo htmlspecialchars($user['soft_skills'] ?? ''); ?>">

        <button type="submit" name="save_profile" class="btn">Save My Details</button>
    </form>
    
    <p style="text-align:center;"><a href="candidate_dashboard.php">Go Back</a></p>
</div>

</body>
</html>