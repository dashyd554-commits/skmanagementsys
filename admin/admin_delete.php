<?php
include '../config/db.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Invalid request");
}

/* DELETE USER (SAFE PDO) */
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$id]);

header("Location: users.php");
exit();
?>