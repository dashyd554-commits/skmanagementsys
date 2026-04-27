<?php
session_start();

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

/* BASIC VALIDATION */
if (!$username || !$password) {
    die("Please fill in all fields");
}

/* HARD-CODED ADMIN LOGIN (OK FOR SMALL SYSTEMS) */
if ($username === 'admin' && $password === 'admin123') {

    $_SESSION['admin'] = true;

    header("Location: dashboard.php");
    exit();

} else {
    echo "Invalid admin login";
}
?>