<?php
session_start();
$conn = new mysqli("localhost", "root", "", "reflectme");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- 1. HELPERS ---
   
    $user_id = $_SESSION['user_id'];
    $content = $conn->real_escape_string($_POST['content']);

    // --- 2. FETCH MOOD DATA ---
    $mood_query = $conn->query("SELECT emotion, intensity, causes FROM mood_logs WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 1");
    $mood_data = $mood_query->fetch_assoc();

    $emotion = $mood_data['emotion'] ?? 'Neutral';
    $intensity = $mood_data['intensity'] ?? 5;
    $causes = $mood_data['causes'] ?? 'general reflection';
     function getCopingStrategy($emotion) {
        $map = [
            'Happy'      => 'Practice savoring: find one small detail from today to hold onto.',
            'Sad'        => 'Try 4-7-8 breathing: inhale for 4, hold for 7, exhale for 8.',
            'Anxious'    => 'Use the 5-4-3-2-1 grounding: 5 see, 4 touch, 3 hear, 2 smell, 1 taste.',
            'Frustrated' => 'Take 5 minutes for a brain dump—write down every irritation.',
            'Angry'      => 'Physical release: try progressive muscle relaxation.',
            'Content'    => 'Write down one thing you are grateful for right now.',
            'Overwhelmed'=> 'Pick just one tiny task. Ignore the rest for now.',
            'Lonely'     => 'Send one casual text to a friend or family member.',
            'Hopeful'    => 'Visualize one step you can take tomorrow.',
            'Confused'   => 'Set a timer for 5 minutes and just free-write.',
            'Bored'      => 'Change your physical environment—stand up and stretch.',
            'Guilty'     => 'Identify one small action to make amends, then forgive yourself.'
        ];
        return $map[$emotion] ?? 'Take three deep, intentional breaths.';
    }


    // --- 3. PREPARE DATA ---
    $ai_emotions_json = json_encode([["label" => $emotion, "score" => 100]]);
    $themes = json_encode(explode(',', $causes));
    $summary = "Based on your check-in, you're feeling $emotion today due to $causes.";
    
    // THE REQUESTED CHANGES
    $reframe = $content; // Exact content as reframe
    $coping_strategies = getCopingStrategy($emotion);

    // --- 4. HANDLE UPLOADS ---
    $img = "";
    if(!empty($_FILES['image']['name'])) {
        $img = "uploads/" . time() . "_ref.jpg";
        move_uploaded_file($_FILES['image']['tmp_name'], $img);
    }

    $voice = "";
    if(!empty($_FILES['voice']['name'])) {
        $voice = "uploads/" . time() . "_voc.webm";
        move_uploaded_file($_FILES['voice']['tmp_name'], $voice);
    }

    

    // --- 5. SAVE ---
    // Note: ensure your table has columns: ai_reframe, coping_strategy
    $stmt = $conn->prepare("INSERT INTO journal_entries 
        (user_id, content, image_path, voice_path, buddy_summary, themes, ai_emotions_json, ai_intensity, intensity_reason, reframe, coping_strategies) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $intensity_reason = "Detected during your emotional check-in.";
    
    $stmt->bind_param("issssssisss", 
        $user_id, $content, $img, $voice, $summary, $themes, $ai_emotions_json, $intensity, $intensity_reason, $reframe, $coping_strategies
    );

    if ($stmt->execute()) {
        header("Location: journey.php");
        exit();
    }
}
?>