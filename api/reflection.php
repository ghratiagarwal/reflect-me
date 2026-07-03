<?php
session_start();
$conn = new mysqli("localhost", "root", "", "reflectme");

$entry_id = $_GET['id'] ?? $_SESSION['current_chat_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? 1;

if (!$entry_id) {
    header("Location: journey.php");
    exit;
}

$query = "SELECT * FROM journal_entries WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $entry_id, $user_id);
$stmt->execute();
$db_data = $stmt->get_result()->fetch_assoc();

if (!$db_data) { die("Reflection data not found."); }

// Check if AI is still working
$is_analyzing = empty($db_data['buddy_summary']);

$emotions_list = json_decode($db_data['ai_emotions_json'], true) ?? [];
$themes_list = json_decode($db_data['themes'], true) ?? [];

$emotionColors = [
    'Happy' => '#FFD700', 'Excited' => '#FF4500', 'Stressed' => '#FF6347', 
    'Anxious' => '#DA70D6', 'Guilty' => '#708090', 'Angry' => '#FF0000', 
    'Fear' => '#8B0000', 'Sad' => '#1E90FF', 'Down' => '#4682B4', 
    'Calm' => '#00FA9A', 'Peaceful' => '#E0FFFF', 'Content' => '#F0E68C'
];

$primary_emo = "Neutral";
if (!empty($emotions_list)) {
    $primary_emo = isset($emotions_list[0]['label']) ? $emotions_list[0]['label'] : array_key_first($emotions_list);
}
$activeColor = $emotionColors[$primary_emo] ?? '#a777e3';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Reflection | ReflectMe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        :root { --accent: <?php echo $activeColor; ?>; --bg-blur: rgba(10, 10, 20, 0.2); }
        body, html { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background: transparent; color: #fff; }
        .bg-image { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: url('bg.png') no-repeat center; background-size: cover; z-index: -2; }
        .bg-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(180deg, var(--bg-blur) 0%, rgba(10, 10, 20, 0.98) 100%); z-index: -1; }
        .reflection-container { max-width: 900px; margin: 0 auto; padding: 60px 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .card { background: rgba(255, 255, 255, 0.04); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 24px; padding: 25px; }
        .full-width { grid-column: 1 / -1; }
        .label { font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: rgba(255, 255, 255, 0.5); margin-bottom: 12px; display: block; }
        .mood-box { border: 1px solid var(--accent); background: <?php echo $activeColor; ?>15; }
        .mood-title { color: var(--accent); margin: 0; font-size: 2rem; }
        .intensity-track { height: 10px; background: rgba(255,255,255,0.1); border-radius: 10px; margin: 15px 0; position: relative; overflow: hidden; }
        .intensity-fill { height: 100%; width: <?php echo ($db_data['ai_intensity'] ?? 0) * 10; ?>%; background: var(--accent); box-shadow: 0 0 20px var(--accent); transition: 1s; }
        .btn-discuss { background: linear-gradient(90deg, #7b61ff, #9d85ff); color: white; border: none; padding: 18px; border-radius: 15px; font-weight: bold; cursor: pointer; text-decoration: none; display: block; text-align: center; }
        
        /* Analysis Spinner */
        .analyzing-container { text-align: center; padding: 100px 20px; grid-column: 1 / -1; }
        .loader-icon { font-size: 3rem; color: var(--accent); animation: spin 2s linear infinite; margin-bottom: 20px; }
        @keyframes spin { 100% { transform: rotate(360deg); } }
    </style>
    
</head>
<body>

<div class="bg-image"></div>
<div class="bg-overlay"></div>

<div class="reflection-container">

    <?php if ($is_analyzing): ?>
        <div class="analyzing-container">
            <i class="fas fa-circle-notch loader-icon"></i>
            <h2>Buddy is calculating your insights...</h2>
            <p style="opacity: 0.6;">We're analyzing the patterns in your conversation.</p>
            <script>setTimeout(() => { location.reload(); }, 3000);</script>
        </div>
    <?php else: ?>
        <div class="card full-width">
            <span class="label">Today's Reflection Focus</span>
            <h2 style="margin:0; font-weight: 400;"><?php echo htmlspecialchars($db_data['prompt_question']); ?></h2>
        </div>

        <div class="card mood-box">
            <span class="label" style="color: var(--accent);">Primary Detection</span>
            <h3 class="mood-title"><?php echo $primary_emo; ?></h3>
        </div>

        <div class="card">
            <span class="label">Personal Reframe</span>
            <p style="margin:0; font-style: italic; opacity: 0.9;">"<?php echo htmlspecialchars($db_data['reframe']); ?>"</p>
        </div>

        <div class="card full-width">
            <span class="label">✨ Buddy Insights</span>
            <p style="font-size: 1.1rem; line-height: 1.6; margin:0;"><?php echo nl2br(htmlspecialchars($db_data['buddy_summary'])); ?></p>
        </div>

        <div class="card">
            <span class="label">Emotion Breakdown</span>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <?php foreach($emotions_list as $key => $val): 
                    $name = is_array($val) ? $val['label'] : $key;
                    $score = is_array($val) ? $val['score'] : $val;
                ?>
                    <span style="background: rgba(255,255,255,0.1); padding: 5px 12px; border-radius: 20px; font-size: 13px;">
                        <?php echo "$name $score%"; ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <span class="label">Topics & Themes</span>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <?php foreach($themes_list as $theme): ?>
                    <span style="color: #9d85ff;">#<?php echo htmlspecialchars($theme); ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <span class="label">Intensity (<?php echo $db_data['ai_intensity']; ?>/10)</span>
            <div class="intensity-track"><div class="intensity-fill"></div></div>
            <p style="font-size: 12px; opacity: 0.6;"><?php echo htmlspecialchars($db_data['intensity_reason']); ?></p>
        </div>

        <div class="card">
            <span class="label">Alignment & Authenticity</span>
            <p style="margin:0; font-size: 14px;"><?php echo htmlspecialchars($db_data['alignment_text']); ?></p>
        </div>

        <div class="card full-width" style="border-left: 4px solid var(--accent);">
            <span class="label">Coping & Growth Strategies</span>
            <p style="margin:0; line-height:1.6;"><?php echo nl2br(htmlspecialchars($db_data['coping_strategies'])); ?></p>
        </div>

        <div class="full-width" style="display: grid; grid-template-columns: 1fr 2fr; gap: 15px;">
            <a href="journey.php" class="card" style="text-decoration:none; text-align:center; color:white;">← Journey</a>
            <a href="chat.php" class="btn-discuss">Start New Chat</a>
        </div>

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
    <?php endif; ?>

</div>
</body>
</html>