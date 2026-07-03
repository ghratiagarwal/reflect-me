<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ReflectMe | Check Your Email</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body, html {
            height: 100%; margin: 0; font-family: 'Montserrat', sans-serif;
            background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url('../public/bg.png');
            background-size: cover; background-position: center; color: white; display: flex; justify-content: center; align-items: center;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 25px;
            padding: 40px; width: 100%; max-width: 400px; text-align: center;
        }
        .icon { font-size: 50px; color: #ffee58; margin-bottom: 20px; }
        .btn-back {
            background: white; color: black; border-radius: 30px; padding: 10px 25px;
            text-decoration: none; font-weight: 600; display: inline-block; margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="glass-card">
        <div class="icon">✉️</div>
        <h2>Check Your Email</h2>
        <p style="font-weight: 300; opacity: 0.8;">If an account exists for that email, we've sent instructions to reset your password.</p>
        <a href="login.html" class="btn-back">Return to Login</a>
    </div>
</body>
</html>