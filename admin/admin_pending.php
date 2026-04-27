<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

/* GET PENDING USERS */
$stmt = $conn->prepare("SELECT * FROM users WHERE status = ? ORDER BY id DESC");
$stmt->execute(['pending']);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pendingCount = count($users);

/* ==================== ML LOGIC ==================== */

/* Risk Level */
if ($pendingCount >= 20) {
    $mlStatus = "HIGH RISK";
    $mlColor = "red";
    $mlInsight = "Too many pending users may slow system approval flow.";
    $mlRecommendation = "Prioritize bulk approval and review process.";
}
elseif ($pendingCount >= 10) {
    $mlStatus = "MODERATE LOAD";
    $mlColor = "orange";
    $mlInsight = "Pending queue is growing steadily.";
    $mlRecommendation = "Process approvals regularly to avoid backlog.";
}
else {
    $mlStatus = "NORMAL LOAD";
    $mlColor = "green";
    $mlInsight = "Approval system is running smoothly.";
    $mlRecommendation = "Maintain current processing speed.";
}

/* Role analysis (optional ML enhancement) */
$roleCount = [];

foreach ($users as $u) {
    $role = $u['role'];
    $roleCount[$role] = ($roleCount[$role] ?? 0) + 1;
}

$mostRequestedRole = "N/A";
if (!empty($roleCount)) {
    arsort($roleCount);
    $mostRequestedRole = array_key_first($roleCount);
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Pending Users</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="../assets/sbstyle.css">

<style>
.main {
    margin-left: 220px;
    width: calc(100% - 220px);
    padding: 20px;
    min-height: 100vh;
}

.glass {
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
    padding: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    background: white;
    border-radius: 10px;
    overflow: hidden;
}

th {
    background: #dc3545;
    color: white;
    padding: 12px;
}

td {
    padding: 12px;
    border-bottom: 1px solid #eee;
}

tr:hover {
    background: #f8f9fa;
}

.badge {
    background: orange;
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
}

.ml-box {
    margin-bottom: 20px;
    padding: 20px;
    border-left: 5px solid;
}

@media (max-width: 768px) {
    .main {
        margin-left: 0;
        width: 100%;
    }
}
</style>

</head>
<body>

<?php include '../assets/sidebar.php'; ?>

<div class="main">

    <div class="header">
        <h2>⏳ Pending Users</h2>
        <p>Approve or manage newly registered accounts</p>
    </div>

    <!-- ================= ML SECTION ================= -->
    <div class="glass ml-box" style="border-color: <?= $mlColor ?>;">

        <h3>🤖 AI Approval System Insight</h3>

        <p><b>Status:</b> <?= $mlStatus ?></p>
        <p><b>Pending Users:</b> <?= $pendingCount ?></p>
        <p><b>Most Requested Role:</b> <?= $mostRequestedRole ?></p>

        <p><b>Insight:</b> <?= $mlInsight ?></p>
        <p><b>Recommendation:</b> <?= $mlRecommendation ?></p>

    </div>

    <!-- ================= TABLE ================= -->
    <div class="glass">

        <h3>Users Waiting Approval</h3>

        <table>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Role</th>
                <th>Status</th>
                <th>Action</th>
            </tr>

            <?php if ($pendingCount > 0) { ?>
                <?php foreach ($users as $row) { ?>
                <tr>
                    <td><?= $row['id']; ?></td>
                    <td><?= htmlspecialchars($row['username']); ?></td>
                    <td><?= $row['role']; ?></td>
                    <td><span class="badge">Pending</span></td>
                    <td>
                        <a href="approve_user.php?id=<?= $row['id']; ?>">
                            Approve
                        </a>
                    </td>
                </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="5" style="text-align:center; padding:20px;">
                        No pending users
                    </td>
                </tr>
            <?php } ?>

        </table>

    </div>

</div>

</body>
</html>