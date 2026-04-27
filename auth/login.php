<?php
session_start();
include '../config/db.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

/* GET USER (MYSQL PDO) */
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* VALIDATION */
if (!$user) {
    die("User not found");
}

if ($user['status'] !== 'approved') {
    die("Account not approved");
}

if (!password_verify($password, $user['password'])) {
    die("Invalid password");
}

/* SESSION */
$_SESSION['user'] = $user;

/* REDIRECT BY ROLE */
if ($user['role'] === 'chairman') {
    header("Location: ../chairperson/chairperson_dashboard.php");
    exit();
}

elseif ($user['role'] === 'secretary') {
    header("Location: ../secretary/secretary_dashboard.php");
    exit();
}

elseif ($user['role'] === 'treasurer') {
    header("Location: ../treasurer/treasurer_dashboard.php");
    exit();
}

else {
    die("Unknown role");
}
?>