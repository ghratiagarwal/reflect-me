<?php
session_start();
require_once 'db.php';

$error_status = false;

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
            header("Location: ../api/index.php");
            exit();
        }
    }
    // If we reach here, something was wrong. We set a flag instead of echoing text.
    $error_status = true;
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <style>
        /* Base styles matching your dashboard */
        body {
            margin: 0; height: 100vh; display: flex; align-items: center; justify-content: center;
            background: #000 url('bg.png') no-repeat center center fixed; background-size: cover;
            font-family: 'Montserrat', sans-serif; color: white;
        }

        .login-card {
            width: 350px; padding: 50px; border-radius: 40px;
            background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(25px);
            border: 1px solid <?= $error_status ? 'rgba(255, 118, 117, 0.5)' : 'rgba(255, 255, 255, 0.1)' ?>;
            transition: border 0.4s ease; text-align: center;
        }

        .input-field {
            width: 100%; padding: 15px; margin: 10px 0; border-radius: 15px;
            background: rgba(255, 255, 255, 0.05); color: white; border: 1px solid rgba(255,255,255,0.1);
            outline: none; box-sizing: border-box;
        }

        /* If error occurs, shake the card slightly */
        .shake { animation: shake 0.4s; }
        @keyframes shake {
            0%, 100% {transform: translateX(0);}
            25% {transform: translateX(-5px);}
            75% {transform: translateX(5px);}
        }

        .btn-enter {
            width: 100%; padding: 15px; border-radius: 15px; border: none;
            background: white; color: black; font-weight: 600; cursor: pointer; margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="login-card <?= $error_status ? 'shake' : '' ?>">
    <h2 style="font-weight:300; letter-spacing:4px;">REFLECT</h2>
    
    <form action="login.php" method="POST">
        <input type="email" name="email" class="input-field" placeholder="Email" required>
        <input type="password" name="password" class="input-field" placeholder="Password" required>
        
        <?php if ($error_status): ?>
            <p style="color: #ff7675; font-size: 0.7rem; margin: 5px 0;">Authentication failed. Try again.</p>
        <?php endif; ?>

        <button type="submit" class="btn-enter">Enter Journey</button>
    </form>
</div>

</body>
</html>