<?php
include '../config/db.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Invalid request");
}

/* UPDATE USER STATUS (MYSQL PDO) */
$stmt = $conn->prepare("UPDATE users SET status = 'approved' WHERE id = ?");
$stmt->execute([$id]);

header("Location: users.php");
exit();
?>