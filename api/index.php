<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Database Connection
$conn = new mysqli("localhost", "root", "", "reflectme");
$user_id = $_SESSION['user_id'] ?? 1;
date_default_timezone_set('Asia/Kolkata');
// --- 1. DYNAMIC GREETING LOGIC ---
$hour = date('G');
if ($hour >= 5 && $hour < 12) { $time_greet = "Good Morning ☀️"; }
elseif ($hour >= 12 && $hour < 18) { $time_greet = "Good Afternoon 🌤️"; }
else { $time_greet = "Good Evening 🌙"; }

// --- 2. STREAK & INSIGHTS FETCHING ---
$stats_query = "SELECT COUNT(DISTINCT DATE(created_at)) as streak, AVG(ai_intensity) as avg_int FROM journal_entries WHERE user_id = $user_id";
$stats_res = $conn->query($stats_query);
$stats = $stats_res->fetch_assoc();

$streak_val = $stats['streak'] ?? 0;
$avg_mood = round($stats['avg_int'] ?? 0, 1);
$progress_text = ($streak_val % 7) . "/7"; 

// --- 3. RANDOM PROMPT ---
$prompts = [
    "What's one thing you're proud of today?",
    "How did you handle a difficult moment recently?",
    "What made you feel most alive today?",
    "Write about a person who made you smile."
];
$display_prompt = $prompts[array_rand($prompts)];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReflectMe UI</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --border-color: #30323e;
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.15);
        }

        body, html {
            margin: 0; padding: 0; width: 100%; min-height: 100%;
            overflow-x: hidden;
            background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url('bg.png');
            background-size: cover; background-position: center; background-attachment: fixed;
            font-family: 'Montserrat', sans-serif; color: white;
            display: flex; flex-direction: column; align-items: center;
        }

        .page-wrapper { width: 100%; max-width: 800px; display: flex; flex-direction: column; align-items: center; padding-bottom: 50px; }

        .top-nav {
            width: 100%; display: flex; justify-content: space-between; align-items: center; 
            padding: 40px 0 20px 0; position: relative; z-index: 100;
        }
        .header { position: absolute; left: 50%; transform: translateX(-50%); text-align: center; pointer-events: none; width: 100%; }
        .header h3 { font-size: 1.2rem; font-weight: 300; opacity: 0.9; margin: 0; }
        .header p { font-size: 0.85rem; opacity: 0.6; margin: 2px 0 0 0; }

        .user-section { display: flex; align-items: center; gap: 12px; z-index: 110; margin-left: auto; padding-right: 10px; }
        .user-name { font-size: 0.9rem; font-weight: 300; text-transform: capitalize; }
        .user-icon { width: 45px; height: 45px; background: rgba(255,255,255,0.1) url('user.png') no-repeat center/cover; border-radius: 50%; border: 1px solid var(--glass-border); }

        /* --- WHEEL & CONTENT --- */
        .content-body { display: flex; flex-direction: column; align-items: center; width: 100%; }
        .wheel-container { position: relative; width: 400px; height: 400px; margin: 0 auto; }
        svg { width: 100%; height: 100%; transform: rotate(-105deg); }
        .segment { stroke: #000; stroke-width: 1.5; filter: url(#grain); opacity: 0.9; }
        .wheel-border { fill: none; stroke: var(--border-color); stroke-width: 2; }
        text { fill: white; font-size: 9px; font-weight: 600; text-transform: uppercase; pointer-events: none; }
        
        .emotion-selection-area { text-align: center; margin-top: 10px; }
        #current-emotion { font-size: 2.2rem; font-weight: 600; margin: 0; letter-spacing: 2px; text-transform: capitalize; }
        .summary-text { font-size: 0.95rem; margin-top: 5px; opacity: 0; transition: opacity 0.5s; height: 24px; }

        .check-in-btn {
            background: rgba(255, 255, 255, 0.05); border: 1px solid var(--border-color);
            padding: 12px 65px; border-radius: 35px; color: rgba(255, 255, 255, 0.4);
            letter-spacing: 3px; text-transform: uppercase; margin: 20px 0; transition: 0.3s; pointer-events: none;
        }
        .check-in-btn.unlocked { cursor: pointer; pointer-events: auto; color: white; border-color: white; }
        .check-in-btn.unlocked:hover { background: white; color: black; box-shadow: 0 0 15px rgba(255, 255, 255, 0.4); }

        .bottom-section { width: 100%; display: flex; justify-content: center; gap: 15px; margin: 20px 0 40px 0; flex-wrap: wrap; }
        .glass-card {
            background: var(--glass-bg); border: 1px solid var(--glass-border);
            border-radius: 18px; padding: 15px 20px; min-width: 200px; flex: 1; max-width: 250px;
            display: flex; align-items: center; justify-content: center; gap: 12px; cursor: pointer;
            backdrop-filter: blur(15px); transition: 0.3s;
        }
        .glass-card:hover { background: rgba(255,255,255,0.1); transform: translateY(-5px); }
        .glass-card img { width: 28px; height: 28px; }
        .card-text h4 { margin: 0; font-size: 0.95rem; }
        .card-text p { margin: 0; font-size: 0.75rem; opacity: 0.6; }

        /* --- DATA MODULES --- */
        .data-modules { width: 95%; display: flex; flex-direction: column; align-items: center; gap: 20px; }
        .module-item { width: 100%; max-width: 600px; }

        .streak-container { background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 20px; padding: 20px; display: flex; align-items: center; gap: 20px; }
        .flame-icon { font-size: 2.5rem; animation: pulseFlame 1.5s infinite ease-in-out; }
        @keyframes pulseFlame { 0%, 100% { transform: scale(1); opacity: 0.8; } 50% { transform: scale(1.1); opacity: 1; } }
        
        .bar-bg { height: 8px; background: rgba(255,255,255,0.1); border-radius: 10px; margin-top: 10px; width: 100%; overflow: hidden; }
        .bar-fill { height: 100%; background: linear-gradient(90deg, #ff8a00, #ff4d6d); transition: width 1s; }

        .small-card { background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; backdrop-filter: blur(10px); text-align: center; cursor: pointer; }
        
        .calendar-card { background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 20px; padding: 20px; }
        .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; margin-top: 15px; }
        .cal-day { aspect-ratio: 1; border-radius: 6px; font-size: 0.7rem; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.05); }

        .prompt-card { background: linear-gradient(135deg, rgba(255,255,255,0.08), rgba(255,255,255,0.02)); border-radius: 20px; padding: 30px; text-align: center; border: 1px solid var(--glass-border); }
        .write-btn { background: white; color: black; border: none; padding: 12px 30px; border-radius: 25px; font-weight: 600; margin-top: 15px; cursor: pointer; }
        /* Make user section a relative container for the absolute dropdown */
.user-section {
    position: relative; 
    cursor: pointer;
}

/* The hidden dropdown menu */
.user-dropdown {
    position: absolute;
    top: 55px;
    right: 0;
    background: rgba(15, 15, 15, 0.95);
    backdrop-filter: blur(10px);
    border: 1px solid var(--glass-border);
    border-radius: 12px;
    width: 150px;
    display: none; /* Hidden by default */
    flex-direction: column;
    overflow: hidden;
    z-index: 1000;
    box-shadow: 0 10px 25px rgba(0,0,0,0.5);
}

.user-dropdown.active {
    display: flex; /* Shown when icon is clicked */
}

.dropdown-item {
    padding: 12px 15px;
    font-size: 0.85rem;
    color: white;
    text-decoration: none;
    transition: 0.2s;
    display: flex;
    align-items: center;
    gap: 10px;
}

.dropdown-item:hover {
    background: rgba(255, 255, 255, 0.1);
}

.dropdown-item.logout {
    color: #ff4d6d;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
}
    </style>
</head>
<body>

<div class="page-wrapper">
    <div class="top-nav">
    <div class="header">
        <h3><?= $time_greet ?></h3>
        <p>Take a moment. How are you feeling?</p>
    </div>
    
    <div class="user-section" id="userProfileTrigger">
        <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
        <div class="user-icon"></div>
        
        <div class="user-dropdown" id="userDropdown">
            <a href="journey.php" class="dropdown-item">📁 My Journey</a>
            <a href="logout.php" class="dropdown-item logout">🚪 Logout</a>
        </div>
    </div>
</div>
    </div>

    <div class="content-body">
        <div class="wheel-container">
            <svg viewBox="0 0 400 400" id="main-svg">
                <defs>
                    <filter id="grain"><feTurbulence type="fractalNoise" baseFrequency="0.8" numOctaves="4" /><feComposite operator="in" in2="SourceGraphic" /><feBlend mode="multiply" in2="SourceGraphic" /></filter>
                    <radialGradient id="nodeGradient" cx="30%" cy="30%" r="70%"><stop offset="0%" stop-color="#ffffff" /><stop offset="100%" stop-color="#34495e" /></radialGradient>
                </defs>
                <circle class="wheel-border" cx="200" cy="200" r="175" /><circle class="wheel-border" cx="200" cy="200" r="85" />
                <g id="wheel-segments"></g>
                <circle id="selector-node" cx="200" cy="200" r="24" fill="url(#nodeGradient)" cursor="grab" />
            </svg>
        </div>
        
        <div class="emotion-selection-area">
            <h2 id="current-emotion">Reflect</h2>
            <div id="summary" class="summary-text">It seems you are <span id="span-emo">happy</span> today.</div>
            <button id="check-in-btn" class="check-in-btn">Check in</button>
        </div>

        <div class="bottom-section">
            <div class="glass-card" onclick="window.location.href='journal.php'"><img src="journal.png"><div class="card-text"><h4>Journal</h4><p>Write your mind.</p></div></div>
            <div class="glass-card" onclick="window.location.href='chat.php'"><img src="chat.png"><div class="card-text"><h4>Talk to Me</h4><p>I'm listening.</p></div></div>
            <div class="glass-card" onclick="window.location.href='shatter.html'"><img src="glass.png"><div class="card-text"><h4>Shatter Room</h4><p>Break negativity.</p></div></div>
        </div>
    </div>

    <div class="data-modules">
        <div class="module-item">
            <div class="streak-container">
                <div class="flame-icon">🔥</div>
                <div style="flex-grow: 1;">
                    <div style="display:flex; justify-content:space-between; font-size:0.9rem;">
                        <strong><?= $streak_val ?> Day Streak</strong>
                        <span style="opacity:0.6"><?= $progress_text ?></span>
                    </div>
                    <div class="bar-bg"><div class="bar-fill" style="width: <?= ($streak_val % 7) * 14.2 ?>%;"></div></div>
                </div>
            </div>
        </div>

        <div class="module-item">
            <div class="small-card" onclick="window.location.href='insights.php'">
                <p style="margin:0; font-size:0.75rem; opacity:0.6; text-transform: uppercase;">Average Mood Insight</p>
                <h3 style="margin:10px 0; font-size: 1.8rem;"><?= $avg_mood ?>/10</h3>
                <div style="font-size:0.75rem; color:#80ed99;">See detailed analysis →</div>
            </div>
        </div>

        <div class="module-item">
            <div class="calendar-card">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span id="cal-title" style="font-weight:600; font-size:0.95rem;"></span>
                    <div>
                        <button onclick="changeMonth(-1)" style="background:none; border:none; color:white; cursor:pointer; font-size:1.2rem;">‹</button>
                        <button onclick="changeMonth(1)" style="background:none; border:none; color:white; cursor:pointer; font-size:1.2rem;">›</button>
                    </div>
                </div>
                <div class="cal-grid" id="cal-grid">
                    </div>
            </div>
        </div>

        <div class="module-item">
            <div class="prompt-card">
                <p style="font-size:0.8rem; opacity:0.5; text-transform:uppercase; letter-spacing:1px;">Daily Reflection</p>
                <h3 style="font-weight:400; line-height:1.5; margin:15px 0;">"<?= $display_prompt ?>"</h3>
                <button class="write-btn" onclick="window.location.href='journal.php?prompt=<?= urlencode($display_prompt) ?>'">Write about this</button>
            </div>
        </div>
    </div>
</div>

<script>
    // --- CALENDAR LOGIC (NO REFRESH) ---
    let currentMonth = new Date().getMonth() + 1;
    let currentYear = new Date().getFullYear();

    async function loadCalendar(m, y) {
    const response = await fetch(`getcalendar.php?m=${m}&y=${y}`);
    const data = await response.json();
    
    document.getElementById('cal-title').innerText = data.month_name;
    const grid = document.getElementById('cal-grid');
    grid.innerHTML = '';

    data.days.forEach(day => {
        const div = document.createElement('div');
        div.className = 'cal-day';
        div.innerText = day.day_num;
        div.style.backgroundColor = day.color;
        div.style.border = `1px solid ${day.color}`;

        if (day.has_entry) {
            div.style.cursor = 'pointer';
            div.style.fontWeight = '600';
            
            // This is the specific change:
            // It redirects and "jumps" to the specific entry ID on the journey page
            div.onclick = () => {
                window.location.href = `journey.php#entry-${day.entry_id}`;
            };

            div.onmouseover = () => div.style.transform = "scale(1.1)";
            div.onmouseout = () => div.style.transform = "scale(1)";
        } else {
            div.style.opacity = "0.3";
        }
        grid.appendChild(div);
    });
}

    function changeMonth(dir) {
        currentMonth += dir;
        if(currentMonth > 12) { currentMonth = 1; currentYear++; }
        if(currentMonth < 1) { currentMonth = 12; currentYear--; }
        loadCalendar(currentMonth, currentYear);
    }
    loadCalendar(currentMonth, currentYear);

    // --- WHEEL SCRIPT ---
    const emotions = [
    { label: "Happy", color: "#FFFB50" },    
    { label: "Excited", color: "#FFD24D" },  
    { label: "Stressed", color: "#FFAC40" }, 
    { label: "Anxious", color: "#FF8240" },  
    { label: "Guilty", color: "#F54E40" },   
    { label: "Angry", color: "#C74130" },    
    { label: "Fearful", color: "#7D39A3" },  
    { label: "Sad", color: "#3D49B8" },      
    { label: "Down", color: "#34789C" },     
    { label: "Calm", color: "#4EB569" },     
    { label: "Peaceful", color: "#B6E645" }, 
    { label: "Content", color: "#EFFF50" }   
];

    const svgGroup = document.getElementById('wheel-segments');
    const node = document.getElementById('selector-node');
    const mainSvg = document.getElementById('main-svg');
    const emoTitle = document.getElementById('current-emotion');
    const summary = document.getElementById('summary');
    const spanEmo = document.getElementById('span-emo');
    const checkBtn = document.getElementById('check-in-btn');
    
    emotions.forEach((emo, i) => {
        const start = (i * 30), end = (i + 1) * 30;
        const x1 = 200 + 165 * Math.cos(Math.PI * start / 180), y1 = 200 + 165 * Math.sin(Math.PI * start / 180);
        const x2 = 200 + 165 * Math.cos(Math.PI * end / 180), y2 = 200 + 165 * Math.sin(Math.PI * end / 180);
        const x3 = 200 + 95 * Math.cos(Math.PI * end / 180), y3 = 200 + 95 * Math.sin(Math.PI * end / 180);
        const x4 = 200 + 95 * Math.cos(Math.PI * start / 180), y4 = 200 + 95 * Math.sin(Math.PI * start / 180);

        const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
        path.setAttribute("d", `M ${x1} ${y1} A 165 165 0 0 1 ${x2} ${y2} L ${x3} ${y3} A 95 95 0 0 0 ${x4} ${y4} Z`);
        path.setAttribute("fill", emo.color); path.setAttribute("class", "segment");
        svgGroup.appendChild(path);

        const textAngle = start + 15;
        const tx = 200 + 130 * Math.cos(Math.PI * textAngle / 180), ty = 200 + 130 * Math.sin(Math.PI * textAngle / 180);
        const text = document.createElementNS("http://www.w3.org/2000/svg", "text");
        text.setAttribute("x", tx); text.setAttribute("y", ty);
        text.setAttribute("text-anchor", "middle"); text.setAttribute("transform", `rotate(${textAngle + 90}, ${tx}, ${ty})`);
        text.textContent = emo.label;
        svgGroup.appendChild(text);
    });

    let dragging = false;
    const moveNode = (e) => {
        if (!dragging) return;
        const pt = mainSvg.createSVGPoint();
        pt.x = e.touches ? e.touches[0].clientX : e.clientX;
        pt.y = e.touches ? e.touches[0].clientY : e.clientY;
        const svgP = pt.matrixTransform(mainSvg.getScreenCTM().inverse());
        const dx = svgP.x - 200, dy = svgP.y - 200, dist = Math.sqrt(dx*dx + dy*dy);
        if (dist < 155) { node.setAttribute('cx', svgP.x); node.setAttribute('cy', svgP.y); }
        if (dist > 60) {
            let angle = Math.atan2(dy, dx) * (180/Math.PI);
            if (angle < 0) angle += 360;
            const emo = emotions[Math.floor(angle / 30)];
            emoTitle.textContent = emo.label; emoTitle.style.color = emo.color;
            spanEmo.textContent = emo.label.toLowerCase();
            summary.style.opacity = "1"; checkBtn.classList.add('unlocked');
        }
    };

    checkBtn.addEventListener('click', () => {
        if (checkBtn.classList.contains('unlocked')) {
            window.location.href = `analyser.php?emotion=${encodeURIComponent(emoTitle.textContent)}`;
        }
    });

    node.addEventListener('mousedown', () => dragging = true);
    window.addEventListener('mouseup', () => dragging = false);
    window.addEventListener('mousemove', moveNode);
    node.addEventListener('touchstart', (e) => { dragging = true; e.preventDefault(); });
    window.addEventListener('touchend', () => dragging = false);
    window.addEventListener('touchmove', (e) => { moveNode(e); e.preventDefault(); }, {passive: false});
    const profileTrigger = document.getElementById('userProfileTrigger');
const dropdown = document.getElementById('userDropdown');

// Toggle dropdown on click
profileTrigger.addEventListener('click', (e) => {
    e.stopPropagation(); // Prevent immediate closing
    dropdown.classList.toggle('active');
});

// Close dropdown if user clicks anywhere else on the screen
window.addEventListener('click', () => {
    if (dropdown.classList.contains('active')) {
        dropdown.classList.remove('active');
    }
});
</script>
</body>
</html>