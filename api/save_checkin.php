<?php
session_start();
$conn = new mysqli("localhost", "root", "", "reflectme");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'] ?? 1; // Default for testing
    $emotion = $_POST['emotion'];
    $mood_words = $_POST['mood_words']; // Comma separated string
    $intensity = $_POST['intensity'];
    $causes = $_POST['causes'];       // Comma separated string

    $stmt = $conn->prepare("INSERT INTO mood_logs (user_id, emotion, mood_words, intensity, causes) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issis", $user_id, $emotion, $mood_words, $intensity, $causes);

    if ($stmt->execute()) {
        header("Location: journal.php?saved=true");
    } else {
        echo "Error saving entry.";
    }
}
?>