<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
header('Content-Type: application/json');

try {
    $conn = new mysqli("localhost", "root", "", "reflectme");
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    $user_msg = $_POST['message'] ?? '';
    $chat_id = $_SESSION['current_chat_id'] ?? null;

    if (!$user_msg || !$chat_id) {
        throw new Exception("Missing message or session.");
    }

    // Save User Message
    $stmt_save = $conn->prepare("INSERT INTO chat_messages (entry_id, sender, message) VALUES (?, 'user', ?)");
    $stmt_save->bind_param("is", $chat_id, $user_msg);
    $stmt_save->execute();

    // API Setup - Using 1.5-Flash (Stable)
    $apiKey = getenv('API_KEY'); 
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent?key=" . $apiKey;


    $prompt = "Role: Emotional companion. Rule: One word from [Happy, Excited, Stressed, Anxious, Guilty, Angry, Fear, Sad, Down, Calm, Peaceful, Content]. No math/coding. Output ONLY JSON: {\"reply\":\"...\",\"emotion\":\"...\",\"intensity\":5,\"themes\":[]}. User says: '$user_msg'";

    $data = [
        "contents" => [["parts" => [["text" => $prompt]]]],
        "generationConfig" => [
            "response_mime_type" => "application/json",
            "temperature" => 0.7
        ],
        "safetySettings" => [
            ["category" => "HARM_CATEGORY_HARASSMENT", "threshold" => "BLOCK_NONE"],
            ["category" => "HARM_CATEGORY_HATE_SPEECH", "threshold" => "BLOCK_NONE"],
            ["category" => "HARM_CATEGORY_SEXUALLY_EXPLICIT", "threshold" => "BLOCK_NONE"],
            ["category" => "HARM_CATEGORY_DANGEROUS_CONTENT", "threshold" => "BLOCK_NONE"]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    
    $response = curl_exec($ch);
    $result = json_decode($response, true);

    // FIX 1: Robust Response Checking
    if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        // Log the full response for debugging
        file_put_contents('api_error_log.txt', $response);
        throw new Exception("API Error or Blocked Content. Check api_error_log.txt");
    }

    $ai_json_text = $result['candidates'][0]['content']['parts'][0]['text'];
    
    // FIX 2: Strip Markdown if present
    $ai_json_text = trim(str_replace(['```json', '```'], '', $ai_json_text));

    $ai_data = json_decode($ai_json_text, true);
    $reply = $ai_data['reply'] ?? 'I hear you. Tell me more.';
    $emo = $ai_data['emotion'] ?? 'Neutral';
    $int = $ai_data['intensity'] ?? 5;

    // Save AI Reply
    $stmt_ai = $conn->prepare("INSERT INTO chat_messages (entry_id, sender, message) VALUES (?, 'ai', ?)");
    $stmt_ai->bind_param("is", $chat_id, $reply);
    $stmt_ai->execute();

    // Update Journal
    $stmt_upd = $conn->prepare("UPDATE journal_entries SET ai_emotions_json = ?, ai_intensity = ? WHERE id = ?");
    $emo_json = json_encode([$emo => 1]);
    $stmt_upd->bind_param("sii", $emo_json, $int, $chat_id);
    $stmt_upd->execute();

    echo json_encode($ai_data);

} catch (Exception $e) {
    echo json_encode(["reply" => "Error: " . $e->getMessage()]);
}
?>