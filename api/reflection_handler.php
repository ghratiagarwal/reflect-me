<?php
// 1. SILENCE EXTERNAL NOISE
error_reporting(0);
ini_set('display_errors', 0);
ob_start(); // Start buffering to catch any accidental output

session_start();
header('Content-Type: application/json');

try {
    $conn = new mysqli("localhost", "root", "", "reflectme");
    if ($conn->connect_error) throw new Exception("DB Connection failed");

    $chat_id = $_SESSION['current_chat_id'] ?? null;

    // CLEAR SESSION ID so the next chat starts fresh
    unset($_SESSION['current_chat_id']);

    if (!$chat_id) throw new Exception("No active session");

    // 1. Fetch conversation history
    $res = $conn->query("SELECT sender, message FROM chat_messages WHERE entry_id = $chat_id ORDER BY id ASC");
    $history = "";
    while($row = $res->fetch_assoc()) {
        $history .= ($row['sender'] == 'user' ? "User: " : "Buddy: ") . $row['message'] . "\n";
    }

    if (empty($history)) throw new Exception("No conversation to analyze");


   $apiKey = getenv('API_KEY'); // Ensure this is your real key!
   $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent?key=" . $apiKey;



    $prompt = "Analyze this chat history and provide a JSON summary.
    CHAT HISTORY:
    $history

    JSON FORMAT REQUIRED:
    {
      \"reframe\": \"A short 1st person 'I feel' statement,make it sound human\",
      \"buddy_summary\": \"A warm 2 sentence summary\",
      \"ai_emotions_json\": [{\"label\":\"Happy\",\"score\":80}],
      \"themes\": [\"Work\", \"Stress\"],
      \"ai_intensity\": 7, 
      \"intensity_reason\": \"Explanation of the 1-10 score\",
      \"coping_strategies\": \"Two actionable steps\",
      \"alignment_text\": \"Comparison of mood vs words\",
      \"prompt_question\": \"The focus of the session\"
    }";

    $data = [
        "contents" => [["parts" => [["text" => $prompt]]]],
        "generationConfig" => ["response_mime_type" => "application/json"]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    $response = curl_exec($ch);
    if (curl_errno($ch)) throw new Exception("CURL Error: " . curl_error($ch));
    curl_close($ch);

    $result = json_decode($response, true);
    $raw_ai_text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $ai_json = json_decode($raw_ai_text, true);

    if (empty($ai_json) || !isset($ai_json['buddy_summary'])) {
        throw new Exception("AI Analysis failed to return valid JSON");
    }

    // 3. Save to Database
    $stmt = $conn->prepare("UPDATE journal_entries SET 
        reframe = ?, 
        buddy_summary = ?, 
        ai_emotions_json = ?, 
        themes = ?, 
        ai_intensity = ?,
        intensity_reason = ?, 
        coping_strategies = ?, 
        alignment_text = ?, 
        prompt_question = ? 
        WHERE id = ?");
        
    $emotions_json = json_encode($ai_json['ai_emotions_json']);
    $themes_json = json_encode($ai_json['themes']);
    $intensity = (int)$ai_json['ai_intensity'];
    
    $stmt->bind_param("ssssissssi", 
        $ai_json['reframe'], 
        $ai_json['buddy_summary'], 
        $emotions_json, 
        $themes_json, 
        $intensity,
        $ai_json['intensity_reason'], 
        $ai_json['coping_strategies'], 
        $ai_json['alignment_text'], 
        $ai_json['prompt_question'], 
        $chat_id
    );
    
    if ($stmt->execute()) {
        ob_clean(); // Delete any accidental output (warnings/notices)
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Database update failed: " . $conn->error);
    }

} catch (Exception $e) {
    ob_clean(); // Wipe buffer to ensure pure JSON error response
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>