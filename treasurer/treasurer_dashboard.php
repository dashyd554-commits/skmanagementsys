<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'treasurer') {
    header("Location: ../index.php");
    exit();
}

/* ================= PRESENT ANNUAL BUDGET ONLY ================= */
$stmt = $conn->prepare("
    SELECT year, amount 
    FROM budgets 
    ORDER BY year DESC 
    LIMIT 1
");
$stmt->execute();
$current = $stmt->fetch(PDO::FETCH_ASSOC);

$currentYear = $current['year'] ?? 'N/A';
$currentBudget = $current['amount'] ?? 0;

/* ================= YEARLY DATA ================= */
$stmt = $conn->prepare("
    SELECT year, amount 
    FROM budgets 
    ORDER BY year ASC
");
$stmt->execute();

$years = [];
$amounts = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $years[] = $row['year'];
    $amounts[] = $row['amount'];
}

/* ================= ML TREND ANALYSIS ================= */
$trend = "stable";
$mlInsight = "No enough data for prediction.";

if (count($amounts) >= 2) {

    $last = $amounts[count($amounts) - 1];
    $prev = $amounts[count($amounts) - 2];

    if ($last > $prev) {
        $trend = "increasing";
        $mlInsight = "Budget trend is increasing. Strong financial performance detected.";
    } elseif ($last < $prev) {
        $trend = "decreasing";
        $mlInsight = "Budget trend is decreasing. Review funding sources and activity effectiveness.";
    } else {
        $trend = "stable";
        $mlInsight = "Budget is stable. Maintain current financial strategy.";
    }
}

/* ================= SIMPLE ML FORECAST ================= */
$forecast = $currentBudget;

if ($trend == "increasing") {
    $forecast = $currentBudget * 1.10; // +10%
} elseif ($trend == "decreasing") {
    $forecast = $currentBudget * 0.90; // -10%
}

?>

<!DOCTYPE html>
<html>
<head>
<title>Treasurer Dashboard</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="../assets/sbstyle.css">

<style>
.grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.card {
    padding: 20px;
    text-align: center;
}

canvas {
    max-width: 100%;
}

.badge {
    display:inline-block;
    padding:5px 10px;
    border-radius:8px;
    color:white;
    font-size:12px;
}

.up { background:green; }
.down { background:red; }
.stable { background:gray; }

@media (max-width: 768px) {
    .grid {
        grid-template-columns: 1fr;
    }
}
</style>

</head>

<body>

<?php include '../assets/sidebar.php'; ?>

<div class="main">

    <div class="header">
        <h2>💰 Treasurer Dashboard (ML Enhanced)</h2>
        <p>Financial monitoring with predictive insights</p>
    </div>

    <!-- PRESENT ANNUAL BUDGET -->
    <div class="grid">

        <div class="glass card">
            <h3>📅 Present Year</h3>
            <h2><?= $currentYear ?></h2>
        </div>

        <div class="glass card">
            <h3>💰 Current Annual Budget</h3>
            <h2>₱ <?= number_format($currentBudget) ?></h2>
        </div>

        <div class="glass card">
            <h3>📊 Trend</h3>
            <span class="badge <?= $trend ?>">
                <?= strtoupper($trend) ?>
            </span>
        </div>

    </div>

    <!-- CHART -->
    <div class="glass" style="margin-top:20px; padding:20px;">
        <h3>📊 Budget History</h3>
        <canvas id="chart"></canvas>
    </div>

    <!-- ML INSIGHT -->
    <div class="glass" style="margin-top:20px; padding:20px;">
        <h3>🤖 ML Insight</h3>
        <p><?= $mlInsight ?></p>
    </div>

    <!-- FORECAST -->
    <div class="glass" style="margin-top:20px; padding:20px;">
        <h3>📈 ML Forecast</h3>
        <p>Projected Next Budget: <b>₱ <?= number_format($forecast) ?></b></p>
    </div>

</div>

<script>
new Chart(document.getElementById('chart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($years) ?>,
        datasets: [{
            label: 'Budget',
            data: <?= json_encode($amounts) ?>
        }]
    }
});
</script>

</body>
</html>