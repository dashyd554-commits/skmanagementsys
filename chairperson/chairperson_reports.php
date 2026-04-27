<?php
session_start();
include '../config/db.php';

// Auth check
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

/* ACTIVITIES */
$stmt = $conn->prepare("SELECT * FROM activities ORDER BY date DESC");
$stmt->execute();
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* BUDGETS (safe check) */
try {
    $stmt = $conn->prepare("SELECT * FROM budgets ORDER BY year DESC");
    $stmt->execute();
    $budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $budgets = false;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Reports</title>

<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="../assets/sbstyle.css">

<style>
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
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

tr:hover {
    background: #f5f5f5;
}

.section {
    margin-bottom: 25px;
}
</style>

</head>

<body>

<?php include '../assets/sidebar.php'; ?>

<div class="main">

    <div class="header">
        <h2>📊 Reports</h2>
        <p>System analytics and records overview</p>
    </div>

    <!-- ACTIVITIES -->
    <div class="glass section" style="padding:20px;">
        <h3>📅 Activities Report</h3>

        <table>
            <tr>
                <th>Title</th>
                <th>Participants</th>
                <th>Date</th>
            </tr>

            <?php foreach ($activities as $row) { ?>
            <tr>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= $row['participants'] ?></td>
                <td><?= $row['date'] ?></td>
            </tr>
            <?php } ?>

        </table>
    </div>

    <!-- BUDGET -->
    <div class="glass section" style="padding:20px;">
        <h3>💰 Budget Report</h3>

        <?php if ($budgets): ?>
        <table>
            <tr>
                <th>Amount</th>
                <th>Year</th>
            </tr>

            <?php foreach ($budgets as $row) { ?>
            <tr>
                <td>₱ <?= number_format($row['amount']) ?></td>
                <td><?= $row['year'] ?></td>
            </tr>
            <?php } ?>

        </table>
        <?php else: ?>
            <p>No budget data available.</p>
        <?php endif; ?>
    </div>

</div>

</body>
</html>