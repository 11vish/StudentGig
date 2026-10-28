<?php
session_start();
include "db.php";

// Security: Only Employers should be 'vetting' students here
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'employer') {
    header("Location: index.html");
    exit();
}

if (isset($_GET['id'])) {
    $student_id = intval($_GET['id']);

    // 1. Fetch Student Basic Info & Bio
    $stmt = $conn->prepare("SELECT name, email, portfolio_bio FROM users WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // 2. Fetch Average Rating
    $r_stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(id) as total_reviews FROM reviews WHERE reviewee_id = ?");
    $r_stmt->bind_param("i", $student_id);
    $r_stmt->execute();
    $rating_data = $r_stmt->get_result()->fetch_assoc();
    $avg_rating = number_format($rating_data['avg_rating'], 1);

    // 3. Fetch Portfolio Images
    $p_stmt = $conn->prepare("SELECT * FROM portfolio_items WHERE student_id = ?");
    $p_stmt->bind_param("i", $student_id);
    $p_stmt->execute();
    $portfolio = $p_stmt->get_result();
} else {
    header("Location: view_applicants.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="assets/css/style.css?v=3">
    <meta charset="UTF-8">
    <title><?php echo $user['name']; ?>'s Profile | StudentGig</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #10b981; --bg: #f8fafc; --border: #e2e8f0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); padding: 40px; margin: 0; }
        .profile-container { max-width: 900px; margin: auto; background: white; padding: 40px; border-radius: 20px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header { display: flex; align-items: center; gap: 20px; border-bottom: 1px solid var(--border); padding-bottom: 30px; margin-bottom: 30px; }
        .avatar { width: 100px; height: 100px; border-radius: 50%; background: #ecfdf5; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: var(--primary); font-weight: 700; }
        .rating-badge { background: #fffbeb; color: #92400e; padding: 5px 15px; border-radius: 20px; font-weight: 700; font-size: 0.9rem; margin-top: 10px; display: inline-block; }
        .bio-section { line-height: 1.6; color: #475569; margin-bottom: 40px; }
        .gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
        .gallery img { width: 100%; height: 200px; object-fit: cover; border-radius: 12px; border: 1px solid var(--border); }
        .back-link { text-decoration: none; color: #64748b; font-weight: 600; margin-bottom: 20px; display: inline-block; }
    </style>
</head>
<body>

    <div class="profile-container">
        <a href="view_applicants.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Applicants</a>
        
        <div class="header">
            <div class="avatar"><?php echo substr($user['name'], 0, 1); ?></div>
            <div>
                <h1 style="margin: 0;"><?php echo htmlspecialchars($user['name']); ?></h1>
                <p style="margin: 5px 0; color: #64748b;"><?php echo htmlspecialchars($user['email']); ?></p>
                <div class="rating-badge">
                    <i class="fas fa-star"></i> <?php echo ($rating_data['total_reviews'] > 0) ? $avg_rating : "New Member"; ?>
                    <span style="font-weight: 400;">(<?php echo $rating_data['total_reviews']; ?> Reviews)</span>
                </div>
            </div>
        </div>

        <div class="bio-section">
            <h3>About the Student</h3>
            <p><?php echo nl2br(htmlspecialchars($user['portfolio_bio'] ?? 'This student hasn\'t added a bio yet.')); ?></p>
        </div>

        <h3>Work Portfolio</h3>
        <div class="gallery">
            <?php if($portfolio->num_rows > 0): ?>
                <?php while($img = $portfolio->fetch_assoc()): ?>
                    <img src="<?php echo $img['image_path']; ?>" alt="Work Sample">
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: #94a3b8;">No work samples uploaded yet.</p>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>