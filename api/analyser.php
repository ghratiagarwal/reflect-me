<?php
session_start();
if (!isset($_SESSION['username'])) { header("Location: login.html"); exit(); }

// Check if we are starting fresh or coming from the index wheel
$incomingEmotion = isset($_GET['emotion']) ? htmlspecialchars($_GET['emotion']) : 'Reflect';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ReflectMe | Analysis</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
    --sidebar-bg: rgba(255, 255, 255, 0.05);
    --icon-glow: rgba(255, 255, 255, 0.2);
    --glass-border: rgba(255, 255, 255, 0.1);
}

.sidebar {
    position: fixed;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    width: 70px;
    background: var(--sidebar-bg);
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
        :root { 
            --slider-grad: linear-gradient(90deg, #5c6bc0 0%, #7e57c2 25%, #26c6da 50%, #4db6ac 75%, #ff8a65 100%);
            --mood-glow: rgba(255, 255, 255, 0.1); 
        }

        body, html {
            margin: 0; padding: 0; width: 100%; height: 100%;
            background: #000 url('bg.png') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Montserrat', sans-serif; color: white;
            display: flex; justify-content: center; align-items: center; overflow: hidden;
        }

        .analyser-card {
            background: rgba(255, 255, 255, 0.01); 
            backdrop-filter: blur(20px) saturate(150%);
            -webkit-backdrop-filter: blur(20px) saturate(150%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 35px;
            padding: 50px; width: 85%; max-width: 650px;
            text-align: center; 
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.8), inset 0 0 80px var(--mood-glow);
            transition: all 0.5s ease;
        }

        .step-content { display: none; animation: fadeIn 0.5s ease; }
        .step-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        h1.title { font-weight: 300; letter-spacing: 6px; opacity: 0.3; font-size: 0.7rem; margin-bottom: 20px; text-transform: uppercase; }
        h2 { font-weight: 300; margin-bottom: 35px; font-size: 1.6rem; letter-spacing: 1px; }

        .emotion-header {
            display: inline-block; padding: 10px 40px; border-radius: 50px;
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.4);
            font-size: 1.8rem; font-weight: 500; margin-bottom: 30px;
        }

        /* MINI WHEEL GRID FOR STEP 0 */
        .wheel-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 20px; }
        .wheel-btn { 
            padding: 20px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.03); cursor: pointer; transition: 0.3s; color: white;
        }
        .wheel-btn:hover { background: rgba(255,255,255,0.1); transform: translateY(-5px); border-color: white; }

        .grid { display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; margin: 20px 0; }
        .chip {
            padding: 12px 28px; border-radius: 50px; border: 1px solid rgba(255,255,255,0.15);
            background: rgba(255, 255, 255, 0.05); cursor: pointer; transition: 0.3s;
            font-size: 0.95rem; color: rgba(255,255,255,0.7);
        }
        .chip.active { background: rgba(255,255,255,0.2); border-color: white; color: white; box-shadow: 0 0 20px rgba(255,255,255,0.2); }

        .slider-box { margin: 40px 0; }
        .slider-labels { display: flex; justify-content: space-between; margin-bottom: 15px; opacity: 0.4; font-size: 0.7rem; }
        input[type=range] { -webkit-appearance: none; width: 100%; background: transparent; }
        input[type=range]::-webkit-slider-runnable-track { width: 100%; height: 8px; border-radius: 10px; background: var(--slider-grad); }
        input[type=range]::-webkit-slider-thumb { -webkit-appearance: none; height: 26px; width: 26px; border-radius: 50%; background: #fff; margin-top: -9px; box-shadow: 0 0 15px #fff; border: 2px solid white; }

        .intensity-text { font-size: 2rem; font-weight: 500; margin-top: 15px; display: block; }

        .nav-area { display: flex; justify-content: center; align-items: center; margin-top: 40px; position: relative; }
        .btn-next {
            background: transparent; border: 1px solid rgba(255,255,255,0.4); color: white;
            padding: 12px 60px; border-radius: 50px; cursor: pointer; transition: 0.3s;
            font-size: 0.9rem; letter-spacing: 2px; text-transform: uppercase;
        }
        .btn-next:hover:not(:disabled) { background: white; color: black; box-shadow: 0 0 30px white; }
        .btn-next:disabled { opacity: 0.1; cursor: not-allowed; }
        .btn-back { position: absolute; left: 0; background: none; border: none; color: rgba(255,255,255,0.5); cursor: pointer; }
    </style>
</head>
<body>

<div class="analyser-card">
    <h1 class="title">Mood Analysis</h1>
    <form id="analyserForm" action="save_checkin.php" method="POST">
        <input type="hidden" name="emotion" id="hidden-emotion" value="<?php echo $incomingEmotion; ?>">
        <input type="hidden" name="mood_words" id="hidden-words">
        <input type="hidden" name="causes" id="hidden-causes">
        <input type="hidden" name="intensity" id="hidden-intensity" value="7">

        <div class="step-content <?php echo ($incomingEmotion == 'Reflect') ? 'active' : ''; ?>" id="step-0">
            <h2>How are you feeling right now?</h2>
            <div class="wheel-grid">
                <div class="wheel-btn" style="border-color: #ffeaa7;" onclick="pickEmotion('Happy')"><span>😊</span>Happy</div>
                <div class="wheel-btn" style="border-color: #fab1a0;" onclick="pickEmotion('Excited')"><span>🤩</span>Excited</div>
                <div class="wheel-btn" style="border-color: #ff7675;" onclick="pickEmotion('Stressed')"><span>😫</span>Stressed</div>
                <div class="wheel-btn" style="border-color: #fdcb6e;" onclick="pickEmotion('Anxious')"><span>😰</span>Anxious</div>
                <div class="wheel-btn" style="border-color: #74b9ff;" onclick="pickEmotion('Sad')"><span>😢</span>Sad</div>
                <div class="wheel-btn" style="border-color: #81ecec;" onclick="pickEmotion('Down')"><span>😞</span>Down</div>
                <div class="wheel-btn" style="border-color: #55efc4;" onclick="pickEmotion('Calm')"><span>😌</span>Calm</div>
                <div class="wheel-btn" style="border-color: #a29bfe;" onclick="pickEmotion('Peaceful')"><span>🧘</span>Peaceful</div>
                <div class="wheel-btn" style="border-color: #ffe121;" onclick="pickEmotion('Content')"><span>🙂</span>Content</div>
                <div class="wheel-btn" style="border-color: #d63031;" onclick="pickEmotion('Angry')"><span>😠</span>Angry</div>
                <div class="wheel-btn" style="border-color: #6c5ce7;" onclick="pickEmotion('Fearful')"><span>😨</span>Fearful</div>
                <div class="wheel-btn" style="border-color: #b2bec3;" onclick="pickEmotion('Guilty')"><span>😔</span>Guilty</div>
            </div>
        </div>

        <div class="step-content <?php echo ($incomingEmotion != 'Reflect') ? 'active' : ''; ?>" id="step-1">
            <h2>You are feeling</h2>
            <div class="emotion-header" id="display-emotion"><?php echo $incomingEmotion; ?></div>
            <div class="nav-area">
                <button type="button" class="btn-next" onclick="nextStep(2)">Confirm</button>
            </div>
        </div>

        <div class="step-content" id="step-2">
            <h2>Describe this feeling</h2>
            <div class="grid" id="word-grid"></div>
            <div class="nav-area">
                <button type="button" class="btn-back" onclick="nextStep(1)">‹ Back</button>
                <button type="button" class="btn-next" id="next-2" onclick="nextStep(3)" disabled>Next</button>
            </div>
        </div>

        <div class="step-content" id="step-3">
            <h2>How intense does it feel?</h2>
            <div class="slider-box">
                <div class="slider-labels"><span>1</span><span>2</span><span>3</span><span>4</span><span>5</span><span>6</span><span>7</span><span>8</span><span>9</span><span>10</span></div>
                <input type="range" min="1" max="10" value="7" oninput="updateInt(this.value)">
                <span class="intensity-text" id="int-label">Moderate</span>
            </div>
            <div class="nav-area">
                <button type="button" class="btn-back" onclick="nextStep(2)">‹ Back</button>
                <button type="button" class="btn-next" onclick="nextStep(4)">Next</button>
            </div>
        </div>

        <div class="step-content" id="step-4">
            <h2>What caused this feeling?</h2>
            <div class="grid" id="cause-grid"></div>
            <div class="nav-area">
                <button type="button" class="btn-back" onclick="nextStep(3)">‹ Back</button>
                <button type="submit" class="btn-next" id="finish-btn" disabled>Finish</button>
            </div>
        </div>
    </form>
</div>

<script>
const moodTheme = {
    "Calm": "rgba(129, 212, 250, 0.15)", "Peaceful": "rgba(165, 214, 167, 0.15)",
    "Content": "rgba(255, 245, 157, 0.15)", "Happy": "rgba(255, 202, 40, 0.15)",
    "Sad": "rgba(92, 107, 192, 0.2)", "Angry": "rgba(239, 83, 80, 0.2)",
    "Anxious": "rgba(149, 117, 205, 0.2)", "Excited": "rgba(255, 138, 101, 0.2)",
    "Stressed": "rgba(120, 144, 156, 0.2)", "Down": "rgba(69, 90, 100, 0.2)",
    "Fearful": "rgba(48, 63, 159, 0.25)", "Guilty": "rgba(141, 110, 99, 0.2)"
};

const moodWordsMap = {
    "Calm": ["Neutral","Bored","Tired","Sleepy","Mellow","Relaxed","Steady"],
    "Peaceful":["Content","Relaxed","Relieved","Chill","At Ease","Comfortable","Serene"],
    "Content":["Pleased","Satisfied","Relaxed","Grateful","Peaceful","Steady"],
    "Happy": ["Cheerful","Optimistic","Proud","Grateful","Upbeat","Encouraged","Positive"],
    "Sad": ["Depressed","Lonely","Hopeless","Miserable","Heartbroken","Devastated","Exhausted"],
    "Angry": ["Irritated","Frustrated","Annoyed","Resentful","Outraged","Mad"],
    "Anxious": ["Worried","Nervous","Tense","Panicked"],
    "Excited": ["Energized","Thrilled","Inspired","Overjoyed"],
    "Stressed":["Upset","Overwhelmed","Restless","On Edge","Agitated","Distressed"],
    "Down":["Tired","Blue","Low","Drained","Weary","Sluggish"],
    "Fearful":["Scared","Terrified","Uneasy","Shaken","Panicked"],
    "Guilty":["Remorseful","Ashamed","Regretful","Sorry","Blaming Myself"]
};

const causesList =[["💼", "Work"], ["👥", "Friends"], ["🏠", "Family"], ["❤️", "Relationship"],
    ["🧠", "Health"], ["🎨", "Hobbies"], ["💰", "Finances"], ["📘", "Learning"],
    ["✈️", "Travel"], ["⭐", "Personal"], ["🎯", "Goals"]];

function pickEmotion(name, emoji) {
    document.getElementById('hidden-emotion').value = name;
    document.getElementById('display-emotion').innerText = name;
    initGrids(name);
    nextStep(1);
}

function initGrids(emo) {
    const wordGrid = document.getElementById('word-grid');
    wordGrid.innerHTML = "";
    if(moodTheme[emo]) document.documentElement.style.setProperty('--mood-glow', moodTheme[emo]);

    (moodWordsMap[emo] || ["Neutral"]).forEach(word => {
        const div = document.createElement('div');
        div.className = 'chip';
        div.textContent = word;
        div.onclick = () => {
            div.classList.toggle('active');
            const active = Array.from(document.querySelectorAll('#word-grid .active')).map(c => c.textContent);
            document.getElementById('hidden-words').value = active.join(',');
            document.getElementById('next-2').disabled = active.length === 0;
        };
        wordGrid.appendChild(div);
    });
}

// Cause Grid Init
const causeGrid = document.getElementById('cause-grid');
causesList.forEach(c => {
    const div = document.createElement('div');
    div.className = 'chip';
    div.innerHTML = `<span>${c[0]}</span> ${c[1]}`;
    div.onclick = () => {
        div.classList.toggle('active');
        const active = Array.from(document.querySelectorAll('#cause-grid .active')).map(c => c.innerText.split(' ')[1]);
        document.getElementById('hidden-causes').value = active.join(',');
        document.getElementById('finish-btn').disabled = active.length === 0;
    };
    causeGrid.appendChild(div);
});

function updateInt(val) {
    let label = val < 4 ? "Mild" : val < 8 ? "Moderate" : "Intense";
    document.getElementById('int-label').textContent = label;
    document.getElementById('hidden-intensity').value = val;
}

function nextStep(n) {
    document.querySelectorAll('.step-content').forEach(s => s.classList.remove('active'));
    document.getElementById('step-' + n).classList.add('active');
}

// Auto-init if emotion was passed from Index
if("<?php echo $incomingEmotion; ?>" !== "Reflect") {
    initGrids("<?php echo $incomingEmotion; ?>");
}
</script>
<div class="sidebar">
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
</body>
</html>