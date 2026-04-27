<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'chairman') {
    header("Location: ../index.php");
    exit();
}

/* ---------------- LOAD PYTHON ML JSON ---------------- */
$mlData = json_decode(file_get_contents("../ml/ml_results.json"), true);

/* convert ML into searchable array */
$mlScores = [];
if ($mlData && isset($mlData['results'])) {
    foreach ($mlData['results'] as $ml) {
        $mlScores[$ml['title']] = $ml['score'];
    }
}

/* ---------------- ADD PROJECT ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = $_POST['name'] ?? '';
    $purpose = $_POST['purpose'] ?? '';
    $target = $_POST['target_participants'] ?? 0;
    $activity_id = $_POST['activity_id'] ?? '';

    if (!empty($name) && !empty($activity_id)) {

        $stmt = $conn->prepare("
            INSERT INTO projects (name, purpose, target_participants, activity_id, status)
            VALUES (:name, :purpose, :target, :activity_id, 'ongoing')
        ");

        $stmt->execute([
            ':name' => $name,
            ':purpose' => $purpose,
            ':target' => $target,
            ':activity_id' => $activity_id
        ]);
    }
}

/* ---------------- ACTIVITIES ---------------- */
$stmt = $conn->prepare("SELECT id, title FROM activities");
$stmt->execute();
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------------- PROJECTS WITH LINKED ACTIVITIES ---------------- */
$stmt = $conn->prepare("
    SELECT p.*, a.title 
    FROM projects p
    LEFT JOIN activities a ON p.activity_id = a.id
    ORDER BY p.id DESC
");
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------------- PROJECT ML ANALYSIS ---------------- */
$projectInsights = [];

foreach ($projects as $p) {

    $activityTitle = $p['title'];
    $mlScore = $mlScores[$activityTitle] ?? 0;

    if ($mlScore >= 70) {
        $status = "Very High Success";
        $suggestion = "Strongly recommended for budget expansion.";
    } elseif ($mlScore >= 40) {
        $status = "Moderate Success";
        $suggestion = "Can succeed with stronger promotion.";
    } else {
        $status = "Low Success";
        $suggestion = "Needs redesign or community interest improvement.";
    }

    $projectInsights[] = [
        'project' => $p['name'],
        'activity' => $activityTitle,
        'score' => $mlScore,
        'status' => $status,
        'suggestion' => $suggestion
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Projects</title>

<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="../assets/sbstyle.css">

<style>
.form-group { margin-bottom: 12px; }

input, select, textarea {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
}

button {
    padding: 10px 15px;
    background: #2d89ef;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

button:hover { background: #1b5fbf; }

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    background: white;
}

th {
    background: #2d89ef;
    color: white;
    padding: 10px;
}

td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
}

tr:hover { background: #f5f5f5; }

.small { font-size: 12px; color: gray; }
</style>

</head>

<body>

<?php include '../assets/sidebar.php'; ?>

<div class="main">

    <div class="header">
        <h2>📁 Projects Management with ML Analysis</h2>
    </div>

    <!-- FORM -->
    <div class="glass" style="padding:20px; margin-bottom:20px;">
        <h3>Add New Project</h3>

        <form method="POST">

            <input type="text" name="name" placeholder="Project Name" required><br><br>

            <textarea name="purpose" placeholder="Project Purpose"></textarea><br><br>

            <input type="number" name="target_participants" placeholder="Target Participants"><br><br>

            <select name="activity_id" required>
                <option value="">Select Activity</option>

                <?php foreach ($activities as $a) { ?>
                    <option value="<?= $a['id']; ?>">
                        <?= htmlspecialchars($a['title']); ?>
                    </option>
                <?php } ?>

            </select><br><br>

            <button type="submit">➕ Add Project</button>

        </form>
    </div>

    <!-- PROJECT TABLE -->
    <div class="glass" style="padding:20px;">
        <h3>📊 Project ML Evaluation</h3>

        <table>
            <tr>
                <th>Project</th>
                <th>Linked Activity</th>
                <th>ML Score</th>
                <th>Prediction</th>
                <th>Recommendation</th>
            </tr>

            <?php foreach ($projectInsights as $p) { ?>
            <tr>
                <td><?= htmlspecialchars($p['project']) ?></td>
                <td><?= htmlspecialchars($p['activity']) ?></td>
                <td><?= $p['score'] ?>%</td>
                <td><?= $p['status'] ?></td>
                <td><?= $p['suggestion'] ?></td>
            </tr>
            <?php } ?>

        </table>
    </div>

</div>

</body>
</html>