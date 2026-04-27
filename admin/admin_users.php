<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit();
}

/* GET ALL USERS */
$stmt = $conn->prepare("SELECT * FROM users ORDER BY id DESC");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==================== ML ANALYTICS ==================== */

$totalUsers = count($users);

$roles = [
    'chairman' => 0,
    'secretary' => 0,
    'treasurer' => 0,
    'admin' => 0,
    'other' => 0
];

$pendingCount = 0;

foreach ($users as $u) {

    $role = $u['role'];

    if (isset($roles[$role])) {
        $roles[$role]++;
    } else {
        $roles['other']++;
    }

    if ($u['status'] === 'pending') {
        $pendingCount++;
    }
}

/* ML SCORE (system balance + approval health) */
$roleBalanceScore = 0;

if ($totalUsers > 0) {
    $roleBalanceScore = 100 - (max($roles) / $totalUsers * 100);
}

/* SYSTEM HEALTH */
if ($pendingCount > 10) {
    $mlStatus = "HIGH RISK";
    $mlColor = "red";
    $mlInsight = "Too many pending users. Approval system may be delayed.";
    $mlRecommendation = "Review and process pending registrations immediately.";
}
elseif ($roleBalanceScore < 40) {
    $mlStatus = "UNBALANCED SYSTEM";
    $mlColor = "orange";
    $mlInsight = "User roles are not evenly distributed.";
    $mlRecommendation = "Encourage registration of underrepresented roles.";
}
else {
    $mlStatus = "HEALTHY SYSTEM";
    $mlColor = "green";
    $mlInsight = "User distribution and system activity are balanced.";
    $mlRecommendation = "Maintain current user management strategy.";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Users</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="../assets/sbstyle.css">

<style>
.main {
    margin-left: 220px;
    width: calc(100% - 220px);
    padding: 20px;
    min-height: 100vh;
    overflow-x: hidden;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    border-radius: 10px;
    background: white;
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
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    color: white;
}

.pending { background: orange; }
.approved { background: green; }
.rejected { background: red; }

.ml-box {
    padding: 20px;
    margin-bottom: 20px;
    border-left: 5px solid;
}
</style>

</head>

<body>

<?php include '../assets/sidebar.php'; ?>

<div class="main">

    <div class="header">
        <h2>👤 Manage Users</h2>
        <p>View, monitor, and manage system users</p>
    </div>

    <!-- ================= ML INSIGHT ================= -->
    <div class="glass ml-box" style="border-color: <?= $mlColor ?>;">

        <h3>🤖 AI User System Insight</h3>

        <p><b>Status:</b> <?= $mlStatus ?></p>
        <p><b>Total Users:</b> <?= $totalUsers ?></p>
        <p><b>Pending Users:</b> <?= $pendingCount ?></p>

        <p><b>Insight:</b> <?= $mlInsight ?></p>
        <p><b>Recommendation:</b> <?= $mlRecommendation ?></p>

    </div>

    <!-- ================= TABLE ================= -->
    <div class="glass" style="padding:20px;">

        <h3>All Users</h3>

        <table>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Role</th>
                <th>Status</th>
                <th>Action</th>
            </tr>

            <?php foreach ($users as $row) { ?>
            <tr>
                <td><?= $row['id']; ?></td>
                <td><?= htmlspecialchars($row['username']); ?></td>
                <td><?= $row['role']; ?></td>
                <td>
                    <span class="badge <?= $row['status']; ?>">
                        <?= $row['status']; ?>
                    </span>
                </td>
                <td>
                    <a href="delete.php?id=<?= $row['id']; ?>"
                       onclick="return confirm('Delete this user?')">
                        Delete
                    </a>
                </td>
            </tr>
            <?php } ?>

        </table>

    </div>

</div>

</body>
</html>