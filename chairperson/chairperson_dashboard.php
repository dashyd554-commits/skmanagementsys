<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'chairman') {
    header("Location: ../index.php");
    exit();
}

/* ==================== PRESENT BUDGET ==================== */
$stmt = $conn->prepare("SELECT amount FROM budgets ORDER BY id DESC LIMIT 1");
$stmt->execute();
$budgetData = $stmt->fetch(PDO::FETCH_ASSOC);
$totalBudget = $budgetData['amount'] ?? 0;

/* ==================== ACTIVITIES ==================== */
$stmt = $conn->prepare("SELECT COUNT(*) AS total_projects FROM activities");
$stmt->execute();
$totalProjects = $stmt->fetch(PDO::FETCH_ASSOC)['total_projects'] ?? 0;

$stmt = $conn->prepare("SELECT COALESCE(SUM(participants),0) AS total_participants FROM activities");
$stmt->execute();
$totalParticipants = $stmt->fetch(PDO::FETCH_ASSOC)['total_participants'] ?? 0;

$stmt = $conn->prepare("SELECT title, participants FROM activities");
$stmt->execute();
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

$labels = [];
$data = [];

foreach ($activities as $a) {
    $labels[] = $a['title'];
    $data[] = $a['participants'];
}

/* ==================== SAFE ML LOAD ==================== */
$mlFile = "../ml/ml_results.json";
$mlData = [];

if (file_exists($mlFile)) {
    $json = file_get_contents($mlFile);
    $decoded = json_decode($json, true);

    if (is_array($decoded)) {
        $mlData = $decoded;
    }
}

/* ==================== CLEAN ML DATA ==================== */
$cleanML = [];

foreach ($mlData as $item) {

    if (is_array($item)) {
        $cleanML[] = [
            'title' => $item['title'] ?? $item['activity'] ?? 'Unknown',
            'participants' => $item['participants'] ?? 0,
            'score' => $item['predicted_score'] ?? $item['score'] ?? 0
        ];
    }
}

/* ==================== SORT ML ==================== */
usort($cleanML, function($a, $b) {
    return $b['score'] <=> $a['score'];
});

/* ==================== TOP ACTIVITY ==================== */
$topActivity = "No ML Data";
$topScore = 0;

if (!empty($cleanML)) {
    $topActivity = $cleanML[0]['title'];
    $topScore = $cleanML[0]['score'];
}

/* ==================== FORECAST ==================== */
$predictedIncrease = ($topScore / 100) * ($totalBudget * 0.30);
$futureBudget = $totalBudget + $predictedIncrease;

/* ==================== BASIC INSIGHT ==================== */
if ($topScore >= 70) {
    $mlTip = "High engagement detected. Strong community participation supports budget growth.";
} elseif ($topScore >= 40) {
    $mlTip = "Moderate engagement detected. Some activities perform well.";
} else {
    $mlTip = "Low engagement detected. Improve participation strategies.";
}

/* ==================== NEXT ACTIVITY AI ==================== */
$suggestedActivities = [
    "Sports Festival / Inter-Barangay Sports League",
    "Youth Leadership Training Workshop",
    "Clean-Up Drive + Environmental Campaign",
    "Community Talent Show / Cultural Night",
    "Educational Seminar (Scholarship / Career Guidance)",
    "Digital Skills Training for Youth"
];

if ($totalParticipants >= 200 && $topScore >= 70) {

    $conclusion = "Strong engagement detected. Community participation is high and supports future budget increase.";

    $nextActivity = $suggestedActivities[0];

} elseif ($totalParticipants >= 100) {

    $conclusion = "Moderate engagement detected. Some improvement is needed before maximizing budget growth.";

    $nextActivity = $suggestedActivities[2];

} else {

    $conclusion = "Low engagement detected. New engagement-driven programs are needed.";

    $nextActivity = $suggestedActivities[3];
}

$budgetImpact = ($totalParticipants > 0)
    ? round(($totalParticipants / 10) * 50)
    : 0;

?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chairman Dashboard</title>

<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="../assets/sbstyle.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
.main{
    margin-left:220px;
    padding:20px;
}

.grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:15px;
}

.card{
    padding:20px;
    text-align:center;
}

table{
    width:100%;
    border-collapse:collapse;
    background:white;
}

th{
    background:#0d6efd;
    color:white;
    padding:10px;
}

td{
    padding:10px;
    border-bottom:1px solid #ddd;
}

.glass{
    padding:20px;
    margin-top:20px;
}

@media(max-width:768px){
    .main{ margin-left:70px; }
    .grid{ grid-template-columns:1fr; }
}
</style>

</head>

<body>

<?php include '../assets/sidebar.php'; ?>

<div class="main">

<div class="header">
    <h2>🤖 AI Chairman Dashboard</h2>
</div>

<!-- KPI -->
<div class="grid">

    <div class="glass card">
        <h3>💰 Budget</h3>
        <h2>₱ <?= number_format($totalBudget) ?></h2>
    </div>

    <div class="glass card">
        <h3>📁 Activities</h3>
        <h2><?= $totalProjects ?></h2>
    </div>

    <div class="glass card">
        <h3>👥 Participants</h3>
        <h2><?= $totalParticipants ?></h2>
    </div>

    <div class="glass card">
        <h3>🏆 Top Activity</h3>
        <h2><?= htmlspecialchars($topActivity) ?></h2>
        <small><?= $topScore ?>%</small>
    </div>

</div>

<!-- CHART -->
<div class="glass">
    <h3>📊 Activity Participation</h3>
    <canvas id="chart"></canvas>
</div>

<!-- ML TABLE -->
<div class="glass">
    <h3>🤖 ML Results</h3>

    <table>
        <tr>
            <th>Activity</th>
            <th>Participants</th>
            <th>Score</th>
        </tr>

        <?php if (!empty($cleanML)) { ?>
            <?php foreach (array_slice($cleanML, 0, 5) as $r) { ?>
            <tr>
                <td><?= htmlspecialchars($r['title']) ?></td>
                <td><?= $r['participants'] ?></td>
                <td><?= $r['score'] ?>%</td>
            </tr>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="3">No ML data available</td>
            </tr>
        <?php } ?>
    </table>
</div>

<!-- FORECAST -->
<div class="glass">
    <h3>💰 Budget Forecast</h3>
    <p>Present Budget: ₱ <?= number_format($totalBudget) ?></p>
    <p>Predicted Growth: ₱ <?= number_format($predictedIncrease) ?></p>
    <p>Future Budget Estimate: ₱ <?= number_format($futureBudget) ?></p>
</div>

<!-- INSIGHT -->
<div class="glass">
    <h3>🤖 ML Insight</h3>
    <p><?= $mlTip ?></p>
</div>

<!-- CONCLUSION -->
<div class="glass">
    <h3>📌 AI Conclusion & Next Activity Suggestion</h3>

    <p><b>Conclusion:</b></p>
    <p><?= $conclusion ?></p>

    <hr>

    <p><b>🚀 Recommended Next Activity:</b></p>
    <h3 style="color:#2d89ef;"><?= $nextActivity ?></h3>

    <hr>

    <p><b>💰 Estimated Budget Impact:</b> ₱ <?= number_format($budgetImpact) ?></p>

</div>

</div>

<script>
new Chart(document.getElementById('chart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'Participants',
            data: <?= json_encode($data) ?>
        }]
    }
});
</script>

</body>
</html>