<?php
session_start();
$conn = new mysqli("localhost", "root", "", "reflectme");

// 1. Get user and latest mood
$user_id = $_SESSION['user_id'] ?? 1; 

// FIRST: Check original mood_logs (from analyser.php)
$mood_query = "SELECT * FROM mood_logs 
               WHERE user_id = ? 
               AND created_at >= NOW() - INTERVAL 1 HOUR 
               ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($mood_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$latest_mood = $stmt->get_result()->fetch_assoc();

// SECOND: Fallback to journal_entries using your specific column names
if (!$latest_mood) {
    // We pull ai_intensity and the JSON emotions column from your schema
    $chat_mood_query = "SELECT ai_intensity, ai_emotions_json, created_at FROM journal_entries 
                        WHERE user_id = ? 
                        ORDER BY created_at DESC LIMIT 1";
    $stmt2 = $conn->prepare($chat_mood_query);
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $chat_data = $stmt2->get_result()->fetch_assoc();
    
    if ($chat_data) {
        $latest_mood = $chat_data;
        // Map your ai_intensity to the intensity variable used in the UI
        $latest_mood['intensity'] = $chat_data['ai_intensity'];
        
        // Decode the JSON to extract the primary emotion for the UI
        $emotions = json_decode($chat_data['ai_emotions_json'], true);
        if (!empty($emotions) && is_array($emotions)) {
            // Get the first key (primary emotion) from your JSON
            $latest_mood['emotion'] = array_key_first($emotions);
            $latest_mood['mood_words'] = implode(', ', array_keys($emotions));
        } else {
            $latest_mood['emotion'] = 'Reflected';
            $latest_mood['mood_words'] = 'Thoughtful';
        }
    }
}

// 2. AI Insight Logic (Wrapped to prevent "Cannot redeclare" errors)
if (!function_exists('getAiInsight')) {
    function getAiInsight($mood) {
        if (!$mood || !isset($mood['emotion'])) {
            return "Whatever it feels like... I'm here for you.";
        }
        
        $e = strtolower(trim($mood['emotion']));
        $i = (int)($mood['intensity'] ?? 0);
        
        if ($i >= 8) {
            if (in_array($e, ['happy', 'excited', 'peaceful', 'content', 'calm'])) {
                return "Your energy is incredible today! Let's capture this high point in your journey.";
            }
            return "Today feels heavy. I'm here to help you carry the weight of these thoughts.";
        }
        
        if (in_array($e, ['stressed', 'anxious', 'fearful'])) return "Take a deep breath. Let's untangle these thoughts one by one.";
        if ($e === 'guilty') return "Be kind to yourself today. We are all works in progress.";
        if ($e === 'angry') return "It's okay to let it out. This space is yours to vent without judgment.";
        if (in_array($e, ['sad', 'down'])) return "It's okay not to be okay. Take all the time you need to express this.";
        if (in_array($e, ['happy', 'excited'])) return "I love seeing this energy! What's the best part of your day?";
        if (in_array($e, ['calm', 'peaceful', 'content'])) return "Hold onto this stillness. What helped you find this peace today?";
        
        return "Whatever it feels like... I'm here for you.";
    }
}

$ai_line = getAiInsight($latest_mood);

$emojis = [
    'happy' => '😊', 'excited' => '🤩', 'stressed' => '😫', 'anxious' => '😰',
    'guilty' => '😔', 'angry' => '😠', 'fear' => '😨', 'sad' => '😢',
    'down' => '😞', 'calm' => '😌', 'peaceful' => '🧘', 'content' => '🙂',
    'reflected' => '🧠'
];
$current_emoji = $emojis[strtolower($latest_mood['emotion'] ?? '')] ?? '😶';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ReflectMe | Journal</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
    --sidebar-bg: rgba(255, 255, 255, 0.05);
    --icon-glow: rgba(255, 255, 255, 0.2);
    --glass-border: rgba(255, 255, 255, 0.1);
}

.sidebarr {
    position: fixed;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    width: 70px;
    background: var(--sidebarr-bg);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    border: 1px solid var(--glass-border);
    border-radius: 40px;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 30px 0;
    gap: 25px;
    z-index: 1000;
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
}

.nav-item {
    position: relative;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s ease;
    text-decoration: none;
}

.nav-item img {
    width: 24px;
    height: 24px;
    object-fit: contain;
    filter: brightness(0) invert(1); /* Ensures icons appear white */
    transition: transform 0.3s ease;
}

.nav-item:hover {
    background: var(--icon-glow);
    box-shadow: 0 0 15px var(--icon-glow);
    transform: scale(1.1);
}

/* Tooltip text on hover */
.nav-item::after {
    content: attr(data-label);
    position: absolute;
    left: 65px;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 5px 12px;
    border-radius: 8px;
    font-size: 12px;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
}

.nav-item:hover::after {
    opacity: 1;
}
        :root { --glass: rgba(255, 255, 255, 0.03); --border: rgba(255, 255, 255, 0.1); --accent: #a29bfe; }
        body { margin: 0; height: 100vh; background: #000 url('bg.png') no-repeat center center fixed; background-size: cover; font-family: 'Montserrat', sans-serif; color: white; display: flex; justify-content: center; align-items: center; }

        .journal-card { width: 1100px; height: 650px; background: var(--glass); backdrop-filter: blur(35px); border-radius: 40px; border: 1px solid var(--border); display: flex; overflow: hidden; box-shadow: 0 50px 100px rgba(0,0,0,0.5); }

        .sidebar { width: 340px; padding: 45px; border-right: 1px solid var(--border); background: rgba(0,0,0,0.2); display: flex; flex-direction: column; }
        .mood-container { background: rgba(255,255,255,0.05); padding: 20px; border-radius: 20px; border: 1px solid var(--border); margin: 20px 0; }
        .mood-header { display: flex; align-items: center; gap: 15px; margin-bottom: 10px; }
        
        .mood-word-container { display: flex; flex-wrap: wrap; gap: 8px; margin: 15px 0 30px 0; }
        .mood-tag { padding: 6px 14px; border-radius: 15px; font-size: 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid var(--border); cursor: pointer; transition: 0.3s; color: rgba(255,255,255,0.5); }
        .mood-tag.active { background: var(--accent); color: white; border-color: var(--accent); }

        .workspace { flex-grow: 1; padding: 60px; position: relative; display: flex; flex-direction: column; }
        textarea { width: 100%; flex-grow: 1; background: transparent; border: none; color: white; font-size: 1.3rem; outline: none; resize: none; line-height: 1.8; font-weight: 300; margin-top: 10px; }
        
        #imagePreviewContainer { display: none; align-items: center; gap: 12px; background: rgba(85, 239, 196, 0.1); border: 1px solid rgba(85, 239, 196, 0.3); padding: 8px 15px; border-radius: 15px; width: fit-content; margin: 10px 0; }
        #imgPreview { width: 35px; height: 35px; border-radius: 6px; object-fit: cover; }

        .voice-graph { display: flex; align-items: center; gap: 3px; height: 20px; }
        .bar { width: 3px; height: 5px; background: #ff7675; border-radius: 2px; animation: wave 1s ease-in-out infinite; }
        .bar:nth-child(2) { animation-delay: 0.1s; }
        .bar:nth-child(3) { animation-delay: 0.2s; }
        .bar:nth-child(4) { animation-delay: 0.3s; }
        @keyframes wave { 0%, 100% { height: 5px; } 50% { height: 20px; } }

        .sidebar-tools { margin-top: auto; display: flex; gap: 15px; padding-top: 20px; border-top: 1px solid var(--border); }
        .circle-tool { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.3s; background: rgba(255,255,255,0.05); border: 1.5px solid var(--border); }
        
        #recordingBar { display: none; align-items: center; justify-content: space-between; background: rgba(255, 118, 117, 0.1); border: 1px solid rgba(255, 118, 117, 0.3); padding: 15px 25px; border-radius: 50px; margin-bottom: 20px; }
        .save-btn { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 14px 45px; border-radius: 35px; cursor: pointer; align-self: flex-end; }
        
        .chat-btn { display: inline-block; margin-top: 10px; padding: 12px 20px; background: rgba(162, 155, 254, 0.1); border: 1px solid var(--accent); color: var(--accent); text-decoration: none; border-radius: 30px; font-weight: 500; font-size: 0.8rem; text-align: center; transition: 0.3s; }
        .chat-btn:hover { background: var(--accent); color: white; }

        #promptMenu { display: none; position: absolute; bottom: 110px; left: 45px; background: rgba(15, 15, 15, 0.98); border: 1px solid var(--border); border-radius: 20px; padding: 10px; width: 280px; z-index: 100; }
        .prompt-option { padding: 12px 15px; cursor: pointer; border-radius: 10px; font-size: 0.8rem; }
        
    </style>
</head>
<body>
<div class="sidebarr">
    <a href="index.php" class="nav-item" data-label="Home">
        <img src="home.png" alt="Home">
    </a>
    <a href="chat.php" class="nav-item" data-label="Chat Page">
        <img src="chat.png" alt="Chat">
    </a>
    <a href="insights.php" class="nav-item" data-label="Insights Page">
        <img src="analyser.png" alt="Insights">
    </a>
    <a href="journey.php" class="nav-item" data-label="Journey Page">
        <img src="journey.png" alt="Journey">
    </a>
</div>
<div class="journal-card">
    <div class="sidebar">
        <h4 style="opacity: 0.4; letter-spacing: 2px; font-size: 0.7rem; text-transform: uppercase;">Current Reflection</h4>
        
        <?php if ($latest_mood): ?>
            <div class="mood-container">
                <div class="mood-header">
                    <div style="font-size: 1.8rem;"><?php echo $current_emoji; ?></div>
                    <div style="font-weight: 500;"><?php echo $latest_mood['emotion']; ?></div>
                    <div style="margin-left: auto; font-size: 0.8rem; opacity: 0.6;">✨ <?php echo $latest_mood['intensity']; ?>/10</div>
                </div>
                <div style="height: 6px; width: 100%; border-radius: 10px; background: linear-gradient(90deg, #5de0e6, #004e92, #ff4b2b); position: relative;">
                    <div style="position: absolute; top: -4px; left: <?php echo min($latest_mood['intensity'] * 10, 100); ?>%; width: 14px; height: 14px; background: white; border-radius: 50%; box-shadow: 0 0 10px white;"></div>
                </div>
            </div>

            <p style="font-size: 0.85rem; opacity: 0.6; margin-top: 20px;">Analysis keywords:</p>
            <div class="mood-word-container">
                <?php 
                $words = explode(',', $latest_mood['mood_words'] ?? '');
                foreach($words as $word): if(trim($word)): ?>
                    <span class="mood-tag" onclick="this.classList.toggle('active')"><?php echo trim($word); ?></span>
                <?php endif; endforeach; ?>
            </div>
            
            <p style="font-style: italic; line-height: 1.6; opacity: 0.9; color: #f8c291;">"<?php echo $ai_line; ?>"</p>
            
            <a href="analyser.php" style="display: inline-block; margin-top: 15px; padding: 10px 20px; background: white; color: black; text-decoration: none; border-radius: 30px; font-weight: 500; font-size: 0.8rem; text-align:center;">Re-analyse Mood</a>

        <?php else: ?>
            <div style="text-align: center; padding: 40px 20px; background: rgba(255,255,255,0.03); border: 1px dashed var(--border); border-radius: 30px; margin: 20px 0;">
                <div style="font-size: 2rem; margin-bottom: 10px;">✨</div>
                <p style="font-size: 0.85rem; opacity: 0.7;">How are you feeling right now?</p>
                <a href="analyser.php" style="display: inline-block; margin-top: 15px; padding: 10px 20px; background: white; color: black; text-decoration: none; border-radius: 30px; font-weight: 500; font-size: 0.8rem;">Start Analysis</a>
            </div>
        <?php endif; ?>

        <a href="chat.php" class="chat-btn">💬 Chat with Buddy</a>

        <div class="sidebar-tools">
            <div class="circle-tool" onclick="toggleMic()" id="micBtn" style="border-color: #ff7675;">🎙️</div>
            <div class="circle-tool" onclick="document.getElementById('imgIn').click()" style="border-color: #55efc4;">🖼️</div>
            <div class="circle-tool" onclick="togglePromptMenu()" style="border-color: #a29bfe;">✨</div>
        </div>
    </div>

    <div class="workspace">
        <h2 style="font-weight: 400; margin: 0 0 10px 0;">What's on your mind today?</h2>
        <p style="opacity: 0.5; font-size: 0.9rem; margin-bottom: 20px;">You don't need to write perfectly. Just write honestly.</p>
        
        <div id="activePromptContainer" style="color: var(--accent); font-size: 0.95rem; margin-bottom: 10px; min-height: 25px;"><span id="promptText"></span></div>

        <form action="save_reflection.php" method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; flex-grow:1;">
            <input type="hidden" name="mood_id" value="<?php echo $latest_mood['id'] ?? ''; ?>">
            <input type="hidden" name="prompt_question" id="promptInput" value="">

            <textarea name="content" placeholder="Start typing your story..."></textarea>

            <div id="imagePreviewContainer">
                <img id="imgPreview" src="" alt="Preview">
                <span style="font-size: 0.85rem; color: #55efc4;">Photo attached</span>
                <span onclick="removeImage()" style="cursor:pointer; font-size:18px; margin-left:10px; opacity:0.6;">×</span>
            </div>

            <div id="recordingBar">
                <div style="display:flex; align-items:center; gap:15px;">
                    <div class="voice-graph">
                        <div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div>
                    </div>
                    <span style="font-size:0.9rem;">Recording... <span id="timer">00:00</span></span>
                </div>
                <span style="cursor: pointer; color: #ff7675; font-weight: bold;" onclick="stopRec()">STOP</span>
            </div>

            <input type="file" name="image" id="imgIn" style="display:none;" accept="image/*">
            <input type="file" name="voice" id="voiceIn" style="display:none;">
            
            <button type="submit" class="save-btn">Save Reflection</button>
        </form>
    </div>
</div>

<div id="promptMenu">
    <div class="prompt-option" style="color:#ff7675;" onclick="selectPrompt('')">❌ Clear Prompt</div>
    <div class="prompt-option" onclick="selectPrompt('What made you smile today?')">What made you smile today?</div>
    <div class="prompt-option" onclick="selectPrompt('What are you grateful for right now?')">What are you grateful for right now?</div>
    <div class="prompt-option" onclick="selectPrompt('What challenged you today?')">What challenged you today?</div>
    <div class="prompt-option" onclick="selectPrompt('What did you learn about yourself?')">What did you learn about yourself?</div>
    <div class="prompt-option" onclick="selectPrompt('What small win can you celebrate?')">What small win can you celebrate?</div>
    <div class="prompt-option" onclick="selectPrompt('How are you really feeling right now?')">How are you really feeling right now?</div>
    <div class="prompt-option" onclick="selectPrompt('What are you passionate about lately?')">What are you passionate about lately?</div>
    <div class="prompt-option" onclick="selectPrompt('What would make tomorrow better?')">What would make tomorrow better?</div>
    <div class="prompt-option" onclick="selectPrompt('What are you looking forward to?')">What are you looking forward to?</div>
    <div class="prompt-option" onclick="selectPrompt('What brought you peace today?')">What brought you peace today?</div>
</div>

<script>
    function togglePromptMenu() {
        const menu = document.getElementById('promptMenu');
        menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
    }
    function selectPrompt(val) {
        document.getElementById('promptText').innerText = val ? "✨ " + val : "";
        document.getElementById('promptInput').value = val;
        document.getElementById('promptMenu').style.display = 'none';
    }

    document.getElementById('imgIn').onchange = function(evt) {
        const [file] = this.files;
        if (file) {
            document.getElementById('imgPreview').src = URL.createObjectURL(file);
            document.getElementById('imagePreviewContainer').style.display = 'flex';
        }
    };
    function removeImage() {
        document.getElementById('imgIn').value = ""; 
        document.getElementById('imagePreviewContainer').style.display = 'none';
    }

    let recorder, stream, timerInterval, seconds = 0;
    async function toggleMic() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            recorder = new MediaRecorder(stream);
            let chunks = [];
            recorder.ondataavailable = e => chunks.push(e.data);
            recorder.onstop = () => {
                const blob = new Blob(chunks, { type: 'audio/webm' });
                const file = new File([blob], "voice.webm", {type: "audio/webm"});
                const dt = new DataTransfer(); dt.items.add(file);
                document.getElementById('voiceIn').files = dt.files;
            };
            recorder.start();
            document.getElementById('recordingBar').style.display = 'flex';
            startTimer();
        } catch(err) { console.error("Mic error:", err); }
    }
    function stopRec() {
        if (recorder) recorder.stop();
        if (stream) stream.getTracks().forEach(t => t.stop());
        const recBar = document.getElementById('recordingBar');
        recBar.style.background = "rgba(85, 239, 196, 0.1)";
        recBar.style.borderColor = "rgba(85, 239, 196, 0.3)";
        recBar.innerHTML = `<div style="display:flex; align-items:center; gap:10px;"><span style="font-size:1.2rem;">🎤</span><span style="color:#55efc4; font-size:0.85rem;">Voice note attached</span></div><span style="cursor:pointer; opacity:0.6; font-size:0.8rem;" onclick="location.reload()">Remove</span>`;
        clearInterval(timerInterval);
    }
    function startTimer() {
        seconds = 0;
        timerInterval = setInterval(() => {
            seconds++;
            let min = Math.floor(seconds / 60).toString().padStart(2, '0');
            let sec = (seconds % 60).toString().padStart(2, '0');
            document.getElementById('timer').innerText = min + ":" + sec;
        }, 1000);
    }

    
</script>
</body>
</html>