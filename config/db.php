<?php
$host = "fdb1034.awardspace.net";
$dbname = "4754025_sksys";
$user = "4754025_sksys";
$pass = "Hy?yuOZD6?/JiT{4";

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>