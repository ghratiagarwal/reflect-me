<?php
session_start(); // Start the session at the very top
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $email = $_POST['email'];
    $pass = $_POST['password'];

    $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);

    try {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $user, $email, $hashed_pass);
        
        if ($stmt->execute()) {
            // --- NEW: LOG THE USER IN AUTOMATICALLY ---
            $_SESSION['username'] = $user; 
            
            // --- REDIRECT TO .PHP VERSION ---
            header("Location: ../api/index.php");
            exit();
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            echo "
            <body style='background: #121212; font-family: Montserrat, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; color: white;'>
                <div style='background: rgba(255,255,255,0.05); padding: 30px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); text-align: center; backdrop-filter: blur(10px);'>
                    <h2 style='color: #ef5350;'>Account already exists!</h2>
                    <p style='opacity: 0.8;'>This email is already registered with ReflectMe.</p>
                    <a href='../api/login.php' style='display: inline-block; margin-top: 20px; padding: 10px 25px; background: white; color: black; text-decoration: none; border-radius: 25px; font-weight: 600; text-transform: uppercase; font-size: 0.8rem;'>Click to Login</a>
                </div>
            </body>";
        } else {
            echo "Error: " . $e->getMessage();
        }
    }

    if(isset($stmt)) $stmt->close();
    $conn->close();
}
?>