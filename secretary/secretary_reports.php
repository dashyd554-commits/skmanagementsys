<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'secretary') {
    header("Location: ../index.php");
    exit();
}

/* ===================== GET ACTIVITIES ===================== */
$stmt = $conn->prepare("SELECT * FROM activities ORDER BY date DESC");
$stmt->execute();
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===================== TOTAL PARTICIPANTS ===================== */
$stmt = $conn->prepare("SELECT COALESCE(SUM(participants),0) AS total FROM activities");
$stmt->execute();
$totalData = $stmt->fetch(PDO::FETCH_ASSOC);
$totalParticipants = (int)($totalData['total'] ?? 0);

/* ===================== ML PROCESSING ===================== */
$mlResults = [];

foreach ($activities as $a) {

    $participants = (int)$a['participants'];

    // ML SCORE (percentage contribution)
    $score = ($totalParticipants > 0)
        ? ($participants / $totalParticipants) * 100
        : 0;

    $mlResults[] = [
        'title' => $a['title'],
        'participants' => $participants,
        'date' => $a['date'],
        'score' => round($score, 2)
    ];
}

/* ===================== SORT ML ===================== */
usort($mlResults, function($a, $b) {
    return $b['score'] <=> $a['score'];
});

/* ===================== TOP ACTIVITY ===================== */
$topActivity = $mlResults[0]['title'] ?? 'No Data';
$topScore = $mlResults[0]['score'] ?? 0;

/* ===================== ML INSIGHT ===================== */
if ($totalParticipants >= 200) {
    $mlInsight = "High engagement detected. Activities are strongly supported by the community.";
    $recommendation = "Maintain successful programs and expand similar activities.";
} elseif ($totalParticipants >= 100) {
    $mlInsight = "Moderate engagement detected. Some activities are performing well.";
    $recommendation = "Improve promotion and replicate successful events.";
} else {
    $mlInsight = "Low engagement detected. Activities need improvement.";
    $recommendation = "Increase outreach and redesign programs for better participation.";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Secretary Reports (ML)</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="../assets/sbstyle.css">

<style>
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

th {
    background: #28a745;
    color: white;
    padding: 10px;
}

td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
}

tr:hover {
    background: #f5f5f5;
}

.highlight {
    font-weight: bold;
    color: #2d89ef;
}

.section {
    margin-top: 20px;
}
</style>

</head>

<body>

<?php include '../assets/sidebar.php'; ?>

<div class="main">

    <div class="header">
        <h2>🤖 Secretary Reports (ML Enhanced)</h2>
        <p>AI-powered activity analytics and insights</p>
    </div>

    <!-- SUMMARY -->
    <div class="glass section" style="padding:20px;">
        <h3>📌 Summary</h3>
        <p>Total Participants Recorded:</p>
        <h2><?= $totalParticipants ?></h2>
    </div>

    <!-- ML INSIGHT -->
    <div class="glass section" style="padding:20px;">
        <h3>🤖 ML Insight</h3>
        <p class="highlight"><?= $mlInsight ?></p>

        <p><b>Top Activity:</b> <?= $topActivity ?></p>
        <p><b>Top Score:</b> <?= $topScore ?>%</p>
    </div>

    <!-- TABLE WITH ML -->
    <div class="glass section" style="padding:20px;">
        <h3>📊 Activity List (ML Ranked)</h3>

        <table>
            <tr>
                <th>Title</th>
                <th>Participants</th>
                <th>Date</th>
                <th>ML Score</th>
            </tr>

            <?php foreach ($mlResults as $row) { ?>
            <tr>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= $row['participants'] ?></td>
                <td><?= $row['date'] ?></td>
                <td><?= $row['score'] ?>%</td>
            </tr>
            <?php } ?>

        </table>
    </div>

    <!-- RECOMMENDATION -->
    <div class="glass section" style="padding:20px;">
        <h3>💡 Recommendation</h3>
        <p><?= $recommendation ?></p>
    </div>

</div>

</body>
</html>