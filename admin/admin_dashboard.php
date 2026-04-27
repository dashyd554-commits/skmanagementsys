<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

/* FUNCTION: safe count query */
function getCount($conn, $sql, $params = []) {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ?? 0;
}

/* TOTAL USERS */
$totalUsers = getCount($conn, "SELECT COUNT(*) AS total FROM users");

/* PENDING USERS */
$pendingUsers = getCount($conn, "SELECT COUNT(*) AS total FROM users WHERE status = ?", ['pending']);

/* TOTAL ACTIVITIES */
$totalActivities = getCount($conn, "SELECT COUNT(*) AS total FROM activities");

/* TOTAL PARTICIPANTS */
$totalParticipants = getCount($conn, "SELECT COALESCE(SUM(participants),0) AS total FROM activities");

/* ==================== ML LOGIC ==================== */

/* ENGAGEMENT SCORE (simple ML model simulation) */
$engagementScore = 0;

if ($totalActivities > 0) {
    $engagementScore = ($totalParticipants / $totalActivities);
}

/* NORMALIZE SCORE */
$mlScore = min(100, round($engagementScore / 10, 2));

/* SYSTEM CLASSIFICATION */
if ($mlScore >= 70) {
    $mlStatus = "HIGH ENGAGEMENT";
    $mlColor = "green";
    $mlInsight = "System shows strong community participation. Programs are effective.";
    $mlRecommendation = "Maintain current strategies and expand successful programs.";
}
elseif ($mlScore >= 40) {
    $mlStatus = "MODERATE ENGAGEMENT";
    $mlColor = "orange";
    $mlInsight = "System shows average participation trends.";
    $mlRecommendation = "Improve promotion and increase activity diversity.";
}
else {
    $mlStatus = "LOW ENGAGEMENT";
    $mlColor = "red";
    $mlInsight = "System shows weak participation levels.";
    $mlRecommendation = "Reevaluate programs and increase community outreach.";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="../assets/sbstyle.css">

<style>
.grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 25px;
}

.card {
    padding: 25px;
    text-align: center;
}

.card h3 {
    margin-bottom: 10px;
    color: #dc3545;
}

.card h2 {
    font-size: 32px;
}

.info-box {
    padding: 25px;
    margin-top: 10px;
    line-height: 1.8;
}

.ml-box {
    padding: 20px;
    margin-top: 20px;
    border-left: 5px solid;
}

@media (max-width: 768px) {
    .grid {
        grid-template-columns: 1fr;
    }
}
</style>

</head>

<body>

<?php include '../assets/sidebar.php'; ?>
<div class="overlay" id="overlay" onclick="toggleMenu()"></div>

<div class="main">

    <div class="header">
        <h2>🛠 Administrator Dashboard</h2>
        <p>Manage users, approvals, and monitor system activity</p>
    </div>

    <!-- KPI -->
    <div class="grid">

        <div class="glass card">
            <h3>👥 Total Users</h3>
            <h2><?php echo $totalUsers; ?></h2>
        </div>

        <div class="glass card">
            <h3>⏳ Pending</h3>
            <h2><?php echo $pendingUsers; ?></h2>
        </div>

        <div class="glass card">
            <h3>📌 Activities</h3>
            <h2><?php echo $totalActivities; ?></h2>
        </div>

    </div>

    <!-- ML SECTION -->
    <div class="glass ml-box" style="border-color: <?= $mlColor ?>;">
        <h3>🤖 AI System Insight</h3>

        <p><b>Status:</b> <?= $mlStatus ?></p>
        <p><b>ML Score:</b> <?= $mlScore ?>%</p>

        <p><b>Insight:</b> <?= $mlInsight ?></p>
        <p><b>Recommendation:</b> <?= $mlRecommendation ?></p>
    </div>

    <!-- SYSTEM INFO -->
    <div class="glass info-box">
        <h3>📢 System Overview</h3>
        <p>
            Welcome Administrator. This panel allows you to supervise users, approvals, and system activity.
        </p>
    </div>

</div>

<script>
function toggleMenu() {
    document.getElementById("sidebar").classList.toggle("active");
    document.getElementById("overlay").classList.toggle("active");
}
</script>

</body>
</html>