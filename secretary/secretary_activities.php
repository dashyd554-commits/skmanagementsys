<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'secretary') {
    header("Location: ../index.php");
    exit();
}

$message = "";

/* ===================== INSERT ACTIVITY ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = $_POST['title'] ?? '';
    $participants = $_POST['participants'] ?? 0;
    $date = $_POST['date'] ?? '';

    if (!empty($title) && !empty($participants) && !empty($date)) {

        try {
            $stmt = $conn->prepare("
                INSERT INTO activities (title, participants, date)
                VALUES (:title, :participants, :date)
            ");

            $stmt->execute([
                ':title' => $title,
                ':participants' => $participants,
                ':date' => $date
            ]);

            $message = "✅ Activity recorded successfully!";

        } catch (PDOException $e) {
            $message = "❌ Error: " . $e->getMessage();
        }

    } else {
        $message = "⚠️ All fields are required!";
    }
}

/* ===================== GET DATA ===================== */
$stmt = $conn->prepare("SELECT * FROM activities ORDER BY date DESC");
$stmt->execute();
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===================== ML PROCESSING ===================== */
$totalParticipants = 0;

foreach ($activities as $a) {
    $totalParticipants += (int)$a['participants'];
}

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
    $mlInsight = "High engagement detected. Activities are performing very well.";
    $recommendation = "Maintain and expand successful activities.";
} elseif ($totalParticipants >= 100) {
    $mlInsight = "Moderate engagement detected. Some activities are performing well.";
    $recommendation = "Improve promotion and replicate successful events.";
} else {
    $mlInsight = "Low engagement detected. Activities need improvement.";
    $recommendation = "Increase outreach and redesign activities.";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Activities Management (ML)</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="../assets/sbstyle.css">

<style>
input {
    width: 100%;
    padding: 10px;
    margin-bottom: 12px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 14px;
}

button {
    padding: 10px 15px;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

button:hover {
    background: #1e7e34;
}

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

.message {
    margin-top: 10px;
    font-size: 14px;
}

.highlight {
    font-weight: bold;
    color: #2d89ef;
}
</style>

</head>

<body>

<?php include '../assets/sidebar.php'; ?>

<div class="main">

    <div class="header">
        <h2>🤖 Activities Management (ML Enhanced)</h2>
        <p>AI-powered activity analysis and insights</p>
    </div>

    <!-- FORM -->
    <div class="glass" style="padding:20px; margin-bottom:20px;">

        <h3>Add New Activity</h3>

        <form method="POST">

            <input type="text" name="title" placeholder="Activity Title" required>

            <input type="number" name="participants" placeholder="Participants" required>

            <input type="date" name="date" required>

            <button type="submit">➕ Save Activity</button>

        </form>

        <div class="message">
            <?php echo $message; ?>
        </div>

    </div>

    <!-- ML INSIGHT -->
    <div class="glass" style="padding:20px; margin-bottom:20px;">

        <h3>🤖 ML Insight</h3>
        <p class="highlight"><?= $mlInsight ?></p>

        <p><b>Top Activity:</b> <?= $topActivity ?></p>
        <p><b>Top Score:</b> <?= $topScore ?>%</p>

    </div>

    <!-- TABLE (WITH ML SCORE) -->
    <div class="glass" style="padding:20px;">

        <h3>📊 Activity List (ML Ranked)</h3>

        <table>
            <tr>
                <th>Title</th>
                <th>Participants</th>
                <th>ML Score</th>
            </tr>

            <?php foreach ($mlResults as $r) { ?>
            <tr>
                <td><?= htmlspecialchars($r['title']) ?></td>
                <td><?= $r['participants'] ?></td>
                <td><?= $r['score'] ?>%</td>
            </tr>
            <?php } ?>

        </table>

    </div>

    <!-- RECOMMENDATION -->
    <div class="glass" style="padding:20px; margin-top:20px;">

        <h3>💡 Recommendation</h3>
        <p><?= $recommendation ?></p>

    </div>

</div>

</body>
</html>