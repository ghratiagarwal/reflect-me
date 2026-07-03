<?php
include '../config/db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>How Are You Feeling?</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../public/css/style.css">
</head>

<body class="bg-light">

    <div class="container mt-5">
        <h2 class="mb-3 text-center fw-bold">How are you feeling today?</h2>
        <p class="text-center text-muted mb-4">Express what’s on your mind.</p>

        <form action="save_mood.php" method="POST">
            <textarea name="mood" class="form-control p-3" rows="6" placeholder="Write your thoughts..."></textarea>

            <button type="submit" class="btn btn-primary mt-4 w-100">
                Submit Mood
            </button>
        </form>
    </div>

</body>
</html>
