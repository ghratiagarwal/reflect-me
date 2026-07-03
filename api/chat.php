<?php
session_start();
$conn = new mysqli("localhost", "root", "", "reflectme");

// 1. Route/Session Management
if (!isset($_SESSION['current_chat_id'])) {
    $user_id = $_SESSION['user_id'] ?? 1;
    // content column is usually 'New Chat Session' by default
    $stmt = $conn->prepare("INSERT INTO journal_entries (user_id, content) VALUES (?, 'New Chat Session')");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $_SESSION['current_chat_id'] = $conn->insert_id;
}
$chat_id = $_SESSION['current_chat_id'];
$user_id = $_SESSION['user_id'] ?? 1;

// 2. Fetch past conversations for the Sidebar Menu
$history_query = $conn->query("SELECT id, created_at FROM journal_entries WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 10");

// 3. AI Insight Logic
function getAiInsight($emotion, $intensity) {
    if (!$emotion) return "Analyse your mood by starting a chat...";
    $e = strtolower(trim($emotion));
    $i = (int)$intensity;
    if ($i >= 8) {
        if (in_array($e, ['happy', 'excited', 'peaceful', 'content', 'calm'])) return "Your energy is incredible today! Let's capture this high point.";
        return "Today feels heavy. I'm here to help you carry the weight.";
    }
    if (in_array($e, ['stressed', 'anxious', 'fearful'])) return "Take a deep breath. Let's untangle these thoughts.";
    return "Whatever it feels like... I'm here for you.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ReflectMe | Journey Companion</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="https://img.icons8.com/fluency/48/aurora.png" type="image/x-icon">
    <style>
        :root { --glass: rgba(255, 255, 255, 0.08); --accent: #a777e3; --border: rgba(255, 255, 255, 0.1); }
        body {
            background: url('bg.png') no-repeat center center fixed;
            background-size: cover; font-family: 'Segoe UI', sans-serif; color: white; margin: 0;
            display: flex; justify-content: center; align-items: center; height: 100vh; overflow: hidden;
        }

        .history-menu {
            position: fixed; left: -320px; top: 0; width: 280px; height: 100%;
            background: rgba(10, 10, 20, 0.95); backdrop-filter: blur(15px);
            z-index: 1000; transition: 0.4s ease; padding: 20px; border-right: 1px solid var(--border);
        }
        .history-menu.open { left: 0; }
        .menu-item { 
            padding: 12px; margin-bottom: 10px; background: var(--glass); 
            border-radius: 10px; cursor: pointer; font-size: 13px; transition: 0.3s;
            display: block; text-decoration: none; color: white; border: 1px solid transparent;
        }
        .menu-item:hover { border-color: var(--accent); background: rgba(167, 119, 227, 0.2); }

        .main-container { display: flex; width: 95%; max-width: 1200px; height: 85vh; gap: 20px; position: relative; }
        .sidebar, .chat-box { background: var(--glass); backdrop-filter: blur(20px); border: 1px solid var(--border); border-radius: 25px; padding: 25px; }
        .sidebar { flex: 1; max-width: 300px; display: flex; flex-direction: column; gap: 10px; }
        .chat-box { flex: 2.5; display: flex; flex-direction: column; position: relative; }

        .mood-container { background: rgba(255,255,255,0.05); padding: 20px; border-radius: 20px; border: 1px solid var(--border); margin: 10px 0; }
        .mood-header { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
        .intensity-track { height: 6px; width: 100%; border-radius: 10px; background: linear-gradient(90deg, #5de0e6, #a777e3, #ff4b2b); position: relative; }
        .intensity-dot { position: absolute; top: -4px; left: 0%; width: 14px; height: 14px; background: white; border-radius: 50%; box-shadow: 0 0 10px white; transition: 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .mood-word-container { display: flex; flex-wrap: wrap; gap: 8px; margin: 15px 0; }
        .mood-tag { padding: 6px 14px; border-radius: 15px; font-size: 0.75rem; background: rgba(255,255,255,0.05); border: 1px solid var(--border); color: rgba(255,255,255,0.7); }
        .ai-insight-box { font-style: italic; line-height: 1.6; opacity: 0.9; color: #f8c291; font-size: 0.85rem; margin-top: 10px; }

        .chat-display { flex: 1; overflow-y: auto; padding-right: 10px; }
        .msg { display: flex; margin-bottom: 20px; gap: 10px; align-items: flex-end; }
        .msg.user { justify-content: flex-end; }
        .bubble { max-width: 75%; padding: 12px 18px; border-radius: 18px; font-size: 14px; background: rgba(255, 255, 255, 0.1); }
        .user .bubble { background: rgba(110, 142, 251, 0.3); border-bottom-right-radius: 2px; }
        .ai .bubble { border-bottom-left-radius: 2px; background: rgba(255, 255, 255, 0.05); }

        #chat-loading { display: none; align-items: center; gap: 10px; margin-bottom: 15px; padding-left: 10px; font-style: italic; }
        .dot-container { display: flex; gap: 4px; }
        .dot-container div { width: 4px; height: 4px; background: white; border-radius: 50%; animation: blink 1.4s infinite both; }
        .dot-container div:nth-child(2) { animation-delay: 0.2s; }
        .dot-container div:nth-child(3) { animation-delay: 0.4s; }
        @keyframes blink { 0%, 80%, 100% { opacity: 0; } 40% { opacity: 1; } }

        .journal-btn { 
            display: none; background: rgba(167, 119, 227, 0.1); 
            border: 1px solid var(--accent); color: var(--accent); padding: 10px 24px; border-radius: 12px; 
            cursor: pointer; margin: 10px auto; font-weight: bold; transition: 0.3s;
        }
        .journal-btn:hover { background: var(--accent); color: white; }
        
        .input-area { 
            display: flex; background: rgba(255,255,255,0.1); padding: 10px 20px; 
            border-radius: 30px; align-items: center; gap: 15px; border: 1px solid var(--border);
        }
        input { background: transparent; border: none; color: white; flex: 1; outline: none; }
        .icon-btn { color: rgba(255,255,255,0.5); font-size: 18px; cursor: pointer; border: none; background: none; transition: 0.2s; }
        .icon-btn.active { color: #ff7675; transform: scale(1.2); }
        .menu-trigger { position: absolute; top: -45px; left: 0; cursor: pointer; font-size: 20px; }

        #freq-bars { display: none; gap: 3px; align-items: center; height: 20px; }
        .bar { width: 3px; background: #ff7675; border-radius: 2px; animation: scaleY 0.5s infinite ease-in-out; }
        @keyframes scaleY { 0%, 100% { height: 5px; } 50% { height: 15px; } }
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
<div class="history-menu" id="historyMenu">
    <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom: 20px;">
        <h3 style="margin:0">History</h3>
        <i class="fas fa-arrow-left" onclick="toggleMenu()" style="cursor:pointer; opacity: 0.5;"></i>
    </div>
    <hr style="opacity:0.1; margin-bottom: 15px;">
    <a href="chat.php" class="menu-item"><i class="fas fa-plus"></i> Start New Conversation</a>
    <?php while($row = $history_query->fetch_assoc()): ?>
        <a href="chat.php?chat_id=<?php echo $row['id']; ?>" class="menu-item">
            <i class="fas fa-calendar-alt"></i> Session #<?php echo $row['id']; ?><br>
            <small style="opacity:0.6"><?php echo date('M d, H:i', strtotime($row['created_at'])); ?></small>
        </a>
    <?php endwhile; ?>
</div>

<div class="main-container">
    <div class="menu-trigger" onclick="toggleMenu()"><i class="fas fa-bars"></i></div>
    
    <aside class="sidebar">
        <h4 style="opacity: 0.4; letter-spacing: 2px; font-size: 0.7rem; text-transform: uppercase;">Current Reflection</h4>
        
        <div id="mood-display-area">
            <div class="mood-container">
                <div class="mood-header">
                    <div id="side-emoji" style="font-size: 1.8rem;">😶</div>
                    <div id="side-emotion" style="font-weight: 500;">Analyzing...</div>
                    <div id="side-intensity-text" style="margin-left: auto; font-size: 0.8rem; opacity: 0.6;">✨ 0/10</div>
                </div>
                <div class="intensity-track">
                    <div id="side-intensity-dot" class="intensity-dot"></div>
                </div>
            </div>

            <p style="font-size: 0.85rem; opacity: 0.6; margin-top: 15px;">Keywords detected:</p>
            <div class="mood-word-container" id="side-tags"></div>
            
            <div class="ai-insight-box" id="side-insight">"Whatever it feels like... I'm here for you."</div>
        </div>
        
        <div style="margin-top: auto;">
             <a href="journal.php" style="text-decoration:none">
                <button class="journal-btn" style="display:block; width:100%; margin-bottom:0;">
                    <i class="fas fa-book-open"></i> View Journal
                </button>
             </a>
        </div>
    </aside>

    <main class="chat-box">
        <div class="chat-display" id="chat-window">
            <div class="msg ai"><div class="bubble">I'm here for you. What happened today?</div></div>
        </div>

        <div id="chat-loading">
            <span id="loading-text" style="font-size: 14px; color: rgba(255,255,255,0.7);">Reviewing your entries</span>
            <div class="dot-container"><div></div><div></div><div></div></div>
        </div>

        <button class="journal-btn" id="journal-btn" onclick="finalizeCheckin()">
            <i class="fas fa-sparkles"></i> Finalize Check-in &rarr;
        </button>

        <div class="input-area">
            <button class="icon-btn" id="mic-btn" onclick="toggleMic()"><i class="fas fa-microphone"></i></button>
            <div id="freq-bars"><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div></div>
            <input type="text" id="user-msg" placeholder="Message Buddy..." onkeypress="if(event.key === 'Enter') sendChat()">
            <button class="icon-btn" style="color:var(--accent)" onclick="sendChat()"><i class="fas fa-arrow-up"></i></button>
        </div>
    </main>
</div>

<script>
const emotionMap = {
    'happy': '😊', 'excited': '🤩', 'stressed': '😫', 'anxious': '😰',
    'guilty': '😔', 'angry': '😠', 'fear': '😨', 'sad': '😢',
    'down': '😞', 'calm': '😌', 'peaceful' : '🧘', 'content': '🙂'
};

function getAiInsightJS(emotion, intensity) {
    const e = emotion.toLowerCase();
    const i = parseInt(intensity);
    if (i >= 8) {
        if (['happy', 'excited', 'peaceful', 'content', 'calm'].includes(e)) return "Your energy is incredible today! Let's capture this high point.";
        return "Today feels heavy. I'm here to help you carry the weight.";
    }
    if (['stressed', 'anxious', 'fearful'].includes(e)) return "Take a deep breath. Let's untangle these thoughts.";
    return "Whatever it feels like... I'm here for you.";
}

function toggleMenu() { document.getElementById('historyMenu').classList.toggle('open'); }

async function sendChat() {
    const input = document.getElementById('user-msg');
    const windowObj = document.getElementById('chat-window');
    const text = input.value.trim();
    if (!text) return;

    windowObj.innerHTML += `<div class="msg user"><div class="bubble">${text}</div></div>`;
    input.value = '';
    windowObj.scrollTop = windowObj.scrollHeight;
    document.getElementById('chat-loading').style.display = 'flex';
    
    try {
        const response = await fetch('chat_handler.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `message=${encodeURIComponent(text)}`
        });
        const data = await response.json();
        document.getElementById('chat-loading').style.display = 'none';
        
        // Use AI reply
        windowObj.innerHTML += `<div class="msg ai"><div class="bubble">${data.reply}</div></div>`;
        windowObj.scrollTop = windowObj.scrollHeight;

        if (data.emotion) {
            const emo = data.emotion.toLowerCase();
            document.getElementById('side-emotion').innerText = data.emotion;
            document.getElementById('side-emoji').innerText = emotionMap[emo] || '✨';
            document.getElementById('side-intensity-text').innerText = `✨ ${data.intensity}/10`;
            document.getElementById('side-intensity-dot').style.left = (data.intensity * 10) + '%';
            
            const tagContainer = document.getElementById('side-tags');
            tagContainer.innerHTML = '';
            // FIXED: Using data.themes or data.emotion correctly
            const keywords = data.themes && data.themes.length > 0 ? data.themes : [data.emotion];
            keywords.forEach(word => {
                tagContainer.innerHTML += `<span class="mood-tag">#${word}</span>`;
            });

            document.getElementById('side-insight').innerText = `"${getAiInsightJS(data.emotion, data.intensity)}"`;
            document.getElementById('journal-btn').style.display = 'block';
        }
    } catch (e) { 
        console.error(e);
        document.getElementById('chat-loading').style.display = 'none'; 
    }
}

// Fixed Mic Logic
let recognition;
let isListening = false;
if (window.webkitSpeechRecognition || window.SpeechRecognition) {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    recognition = new SpeechRecognition();
    recognition.onstart = () => { 
        isListening = true;
        document.getElementById('mic-btn').classList.add('active'); 
        document.getElementById('freq-bars').style.display = 'flex'; 
    };
    recognition.onend = () => { 
        isListening = false;
        document.getElementById('mic-btn').classList.remove('active'); 
        document.getElementById('freq-bars').style.display = 'none'; 
    };
    recognition.onresult = (e) => { 
        document.getElementById('user-msg').value = e.results[0][0].transcript; 
        sendChat(); 
    };
}

function toggleMic() {
    if (!recognition) return alert("Speech recognition not supported.");
    isListening ? recognition.stop() : recognition.start();
}

// FIXED: This now points to reflection_handler.php and redirects correctly
async function finalizeCheckin() {
    const btn = document.getElementById('journal-btn');
    btn.innerText = "Analyzing Session...";
    btn.disabled = true;

    try {
        const response = await fetch('reflection_handler.php');
        const data = await response.json();
        if(data.success) {
            window.location.href = 'reflection.php';
        } else {
            alert("Analysis error: " + (data.error || "Unknown error"));
            btn.innerText = "Finalize Check-in →";
            btn.disabled = false;
        }
    } catch (e) {
        alert("Failed to connect to server.");
        btn.disabled = false;
    }
}
</script>
</body>
</html>