<?php
include '../config/db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $passwordRaw = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    $role = trim($_POST['role'] ?? '');

    /* VALIDATION */
    if (!$username || !$passwordRaw || !$confirmPassword || !$role) {
        $message = "⚠️ All fields are required!";
    }

    elseif (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
        $message = "❌ Username must be a valid email!";
    }

    elseif (strlen($passwordRaw) < 8) {
        $message = "❌ Password must be at least 8 characters!";
    }

    elseif ($passwordRaw !== $confirmPassword) {
        $message = "❌ Passwords do not match!";
    }

    else {

        /* CHECK EMAIL EXISTS */
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);

        if ($stmt->fetch()) {
            $message = "❌ Email already registered!";
        }

        else {

            /* HASH PASSWORD */
            $hashedPassword = password_hash($passwordRaw, PASSWORD_DEFAULT);

            /* INSERT USER */
            $stmt = $conn->prepare("
                INSERT INTO users (username, password, role, status)
                VALUES (?, ?, ?, 'pending')
            ");

            if ($stmt->execute([$username, $hashedPassword, $role])) {

                echo "<script>
                        alert('✅ Registered Successfully! Wait for admin approval.');
                        window.location='../index.php';
                      </script>";
                exit();

            } else {
                $message = "❌ Registration failed!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>SK Registration</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="../assets/style.css">

<style>
.container{
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
}

.register-box{
    width:380px;
    padding:25px;
}

input,select{
    width:100%;
    padding:12px;
    margin-bottom:12px;
    border:none;
    border-radius:8px;
    outline:none;
}

button{
    width:100%;
    padding:12px;
    border:none;
    border-radius:8px;
    background:#6c8cff;
    color:white;
    cursor:pointer;
}

button:hover{
    background:#4f6ef7;
}

.message{
    text-align:center;
    margin-top:10px;
    font-size:14px;
    font-weight:bold;
}

a{
    display:block;
    text-align:center;
    margin-top:12px;
    text-decoration:none;
}
</style>
</head>

<body>

<div class="container">

    <div class="glass register-box">

        <h2 style="text-align:center;">📝 SK Registration</h2>

        <form method="POST">

            <input type="email" name="username" placeholder="Email Address" required>

            <input type="password" name="password" placeholder="Password" required>

            <input type="password" name="confirm_password" placeholder="Confirm Password" required>

            <select name="role" required>
                <option value="">Select Role</option>
                <option value="chairman">Chairman</option>
                <option value="secretary">Secretary</option>
                <option value="treasurer">Treasurer</option>
            </select>

            <button type="submit">Register</button>

        </form>

        <div class="message"><?php echo $message; ?></div>

        <a href="../index.php">← Back to Login</a>

    </div>

</div>

</body>
</html>