<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'chairman') {
    header("Location: ../index.php");
    exit();
}

/* ---------------- LOAD PYTHON OUTPUT (UNCHANGED PYTHON) ---------------- */
$mlFile = "../ml/ml_results.json";

$mlData = [];
if (file_exists($mlFile)) {
    $json = file_get_contents($mlFile);
    $decoded = json_decode($json, true);

    if (is_array($decoded)) {
        $mlData = $decoded; // THIS IS A SIMPLE ARRAY
    }
}

/* ---------------- NORMALIZE (IMPORTANT FIX) ---------------- */
$totalParticipants = 0;
$topActivity = "N/A";
$topScore = 0;
$averageScore = 0;

$ranked = [];

if (!empty($mlData)) {

    $sumScore = 0;

    foreach ($mlData as $row) {

        $participants = $row['participants'] ?? 0;
        $score = $row['predicted_score'] ?? 0;

        $totalParticipants += $participants;
        $sumScore += $score;

        $ranked[] = [
            "title" => $row['title'] ?? "Unknown",
            "participants" => $participants,
            "score" => $score
        ];
    }

    // sort by ML score
    usort($ranked, function ($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    $topActivity = $ranked[0]['title'] ?? "N/A";
    $topScore = $ranked[0]['score'] ?? 0;
    $averageScore = count($ranked) > 0 ? $sumScore / count($ranked) : 0;
}

/* ---------------- FORECAST (BASED ON ML) ---------------- */
$stmt = $conn->prepare("SELECT amount FROM budgets ORDER BY id DESC LIMIT 1");
$stmt->execute();
$budgetData = $stmt->fetch(PDO::FETCH_ASSOC);

$totalBudget = $budgetData['amount'] ?? 0;

$predictedIncrease = ($topScore / 100) * ($totalBudget * 0.3);
$futureBudget = $totalBudget + $predictedIncrease;

/* ---------------- AI CONCLUSION (YOUR REQUIREMENT) ---------------- */
$conclusion = [];

if ($topScore >= 70) {
    $conclusion[] = "High-performing activities detected. Expand successful programs like '$topActivity' to attract more participants and increase future budget allocation.";
    $conclusion[] = "Recommended next activity: Sports Tournament / Youth Festival / Community Outreach Event.";
} elseif ($topScore >= 40) {
    $conclusion[] = "Moderate engagement. Improve promotion and introduce interactive youth programs.";
    $conclusion[] = "Recommended next activity: Skills Workshop + Sports League.";
} else {
    $conclusion[] = "Low engagement detected. Revise current programs and focus on community-driven activities.";
    $conclusion[] = "Recommended next activity: Barangay Youth Summit or Free Community Events.";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Chairman Recommendation</title>

<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="../assets/sbstyle.css">

<style>
.main {
    margin-left:220px;
    padding:20px;
}

.card {
    padding:20px;
}

.box {
    padding:10px;
    margin:10px 0;
    background:rgba(255,255,255,0.6);
    border-radius:8px;
}
</style>
</head>

<body>

<?php include '../assets/sidebar.php'; ?>

<div class="main">

<div class="header">
    <h2>🤖 Chairman AI Recommendation</h2>
</div>

<!-- INSIGHT -->
<div class="glass card">
    <h3>📊 ML System Insight</h3>
    <p>Total Participants Recorded: <b><?= $totalParticipants ?></b></p>
    <p>Highest Ranked Activity: <b><?= $topActivity ?></b></p>
    <p>Top ML Score: <b><?= round($topScore,2) ?>%</b></p>
    <p>Average ML Score: <b><?= round($averageScore,2) ?>%</b></p>
</div>

<!-- CONCLUSION -->
<div class="glass card" style="margin-top:20px;">
    <h3>📌 Conclusion & Next Activity Suggestion</h3>

    <?php foreach ($conclusion as $c) { ?>
        <div class="box">
            <?= $c ?>
        </div>
    <?php } ?>
</div>

<!-- FORECAST -->
<div class="glass card" style="margin-top:20px;">
    <h3>💰 Budget Forecast</h3>
    <p>Present Budget: ₱ <?= number_format($totalBudget) ?></p>
    <p>Predicted Growth: ₱ <?= number_format($predictedIncrease) ?></p>
    <p>Future Budget: ₱ <?= number_format($futureBudget) ?></p>
</div>

</div>

</body>
</html>