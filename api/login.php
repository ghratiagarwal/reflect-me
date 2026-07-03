<?php
session_start();
require_once '../api/db.php'; // Ensure this path to your db.php is correct

$error_status = false;

// --- 1. HANDLE LOGOUT ---
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: login.php?status=logged_out");
    exit();
}

// --- 2. IF ALREADY LOGGED IN, REDIRECT TO DASHBOARD ---
if (isset($_SESSION['user_id'])) {
    header("Location: ../api/index.php");
    exit();
}

// --- 3. HANDLE LOGIN POST ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['username'] = $user['name']; 
            $_SESSION['user_id'] = $user['id'];
            
            // Critical for some servers to "lock" the session before redirecting
            session_write_close(); 
            header("Location: ../api/index.php");
            exit();
        }
    }
    $error_status = true;
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ReflectMe | Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.15);
            --accent-yellow: #ffee58;
        }
        body, html {
            height: 100%; margin: 0; font-family: 'Montserrat', sans-serif;
            background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url('bg.png');
            background-size: cover; background-position: center; background-attachment: fixed;
            color: white; display: flex; justify-content: center; align-items: center; overflow: hidden;
        }
        .glass-card {
            background: var(--glass-bg); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px);
            border: 1px solid <?= $error_status ? 'rgba(255, 118, 117, 0.5)' : 'var(--glass-border)' ?>;
            border-radius: 25px; padding: 40px; width: 100%; max-width: 400px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3); text-align: center;
            transition: all 0.3s ease;
        }
        .shake { animation: shake 0.4s; }
        @keyframes shake {
            0%, 100% {transform: translateX(0);}
            25% {transform: translateX(-7px);}
            75% {transform: translateX(7px);}
        }
        .form-control {
            width: 100%; background: rgba(255, 255, 255, 0.08); border: 1px solid var(--glass-border);
            border-radius: 12px; color: white !important; padding: 12px 15px; margin-bottom: 15px; outline: none;
        }
        .btn-login {
            background: white; color: black; border: none; border-radius: 30px;
            padding: 12px; width: 100%; font-weight: 600; text-transform: uppercase;
            letter-spacing: 2px; cursor: pointer; transition: 0.3s;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(255,255,255,0.2); }
        .status-text { font-size: 0.75rem; margin-top: 10px; }
    </style>
</head>
<body>

    <div class="glass-card <?= $error_status ? 'shake' : '' ?>">
        <h2 style="font-weight: 600; letter-spacing: 2px; margin-bottom: 30px;">LOGIN</h2>

        <form action="login.php" method="POST">
            <input type="email" name="email" class="form-control" placeholder="EMAIL ADDRESS" required>
            <input type="password" name="password" class="form-control" placeholder="PASSWORD" required>
            
            <?php if ($error_status): ?>
                <p style="color: #ff7675;" class="status-text">Authentication failed. Try again.</p>
            <?php endif; ?>

            <?php if (isset($_GET['status']) && $_GET['status'] == 'logged_out'): ?>
                <p style="color: var(--accent-yellow);" class="status-text">Logged out successfully.</p>
            <?php endif; ?>

            <button type="submit" class="btn-login">Enter ReflectMe</button>
        </form>

        <p style="margin-top: 25px; font-size: 0.85rem; font-weight: 300;">
            New here? <a href="signup.html" style="color: var(--accent-yellow); text-decoration: none; font-weight: 600;">Create Account</a>
        </p>
    </div>

</body>
</html>