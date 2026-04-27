<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'chairman') {
    header("Location: ../index.php");
    exit();
}

/* ==================== READ PYTHON ML JSON ==================== */
$mlFile = "../ml/ml_results.json";

if (file_exists($mlFile)) {
    $mlData = json_decode(file_get_contents($mlFile), true);
} else {
    $mlData = [];
}

/* ==================== ASSIGN RESULTS ==================== */
$results = $mlData;

/* ==================== ML ANALYSIS ==================== */
$totalActivities = count($results);
$totalParticipants = array_sum(array_column($results, 'participants'));

$topActivity = $results[0]['title'] ?? 'N/A';
$topScore = $results[0]['predicted_score'] ?? 0;
$currentBudget = $results[0]['budget'] ?? 0;

/* ==================== CONCLUSION ==================== */
if ($topScore >= 70) {
    $conclusion = "Machine Learning analysis shows HIGH probability of success among current SK activities. Community engagement is strong and these activities can help justify larger future budget requests.";
} elseif ($topScore >= 40) {
    $conclusion = "Machine Learning analysis shows MODERATE activity performance. Some programs are attracting residents, but several activities still require optimization.";
} else {
    $conclusion = "Machine Learning analysis shows LOW success trend. Current activities are not yet generating enough engagement to strongly support budget growth proposals.";
}

/* ==================== RECOMMENDATIONS ==================== */
$suggestions = [];

if ($topActivity != 'N/A') {
    $suggestions[] = "Prioritize and repeat the highest predicted activity: " . $topActivity;
}

if ($topScore < 40) {
    $suggestions[] = "Increase promotion using barangay announcements, youth social media pages, and school partnerships.";
}

$suggestions[] = "Allocate more resources to activities with higher participant turnout.";
$suggestions[] = "Schedule events during weekends, holidays, or after-school hours.";
$suggestions[] = "Introduce competitions, incentives, and recognition to improve attendance.";
$suggestions[] = "Use ML ranking as basis for next SK annual planning.";

/* ==================== PROJECTED BUDGET GROWTH ==================== */
$projectedIncrease = ($topScore / 100) * 15000;
$futureBudget = $currentBudget + $projectedIncrease;
?>

<!DOCTYPE html>
<html>
<head>
<title>Prediction</title>
<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="../assets/sbstyle.css">

<style>
.main{
    margin-left:220px;
    padding:20px;
}

table{
    width:100%;
    border-collapse:collapse;
    background:white;
}

th{
    background:#dc3545;
    color:white;
    padding:10px;
}

td{
    padding:10px;
    border-bottom:1px solid #ddd;
}

tr:hover{
    background:#f5f5f5;
}

@media(max-width:768px){
    .main{
        margin-left:70px;
    }
}
</style>
</head>

<body>

<?php include '../assets/sidebar.php'; ?>

<div class="main">

<div class="header">
    <h2>🤖 Python Machine Learning Prediction Results</h2>
    <p>AI-based success forecasting for SK activities</p>
</div>

<!-- TABLE -->


<!-- TOP ACTIVITY -->
<div class="glass" style="margin-top:20px; padding:20px;">
    <h3>🏆 Highest Predicted Success Activity</h3>
    <p><b><?php echo $topActivity; ?></b> with ML score of <b><?php echo $topScore; ?>%</b></p>
</div>

<!-- BUDGET FORECAST -->
<div class="glass" style="margin-top:20px; padding:20px;">
    <h3>💰 Budget Growth Forecast</h3>
    <p>Present Budget Basis: <b>₱ <?php echo number_format($currentBudget); ?></b></p>
    <p>Predicted Possible Additional Support: <b>₱ <?php echo number_format($projectedIncrease); ?></b></p>
    <p>Projected Future Budget Capacity: <b>₱ <?php echo number_format($futureBudget); ?></b></p>
</div>

<!-- CONCLUSION -->
<div class="glass" style="margin-top:20px; padding:20px;">
    <h3>📌 ML Conclusion</h3>
    <p><?php echo $conclusion; ?></p>
</div>

<!-- RECOMMENDATIONS -->
<div class="glass" style="margin-top:20px; padding:20px;">
    <h3>💡 AI Recommendations</h3>
    <ul>
        <?php foreach ($suggestions as $s) { ?>
            <li><?php echo $s; ?></li>
        <?php } ?>
    </ul>
</div>

<div class="glass card" style="padding:20px;">

<table>
<tr>
    <th>Activity</th>
    <th>Participants</th>
    <th>Budget Basis</th>
    <th>Predicted ML Score</th>
</tr>

<?php if (!empty($results)) { ?>
    <?php foreach ($results as $r) { ?>
    <tr>
        <td><?= htmlspecialchars($r['title']) ?></td>
        <td><?= $r['participants'] ?></td>
        <td>₱ <?= number_format($r['budget']) ?></td>
        <td><?= $r['predicted_score'] ?>%</td>
    </tr>
    <?php } ?>
<?php } else { ?>
    <tr>
        <td colspan="4" style="text-align:center;">No Python ML results found. Please run train_model.py first.</td>
    </tr>
<?php } ?>

</table>

</div>

</div>

</body>
</html>