<?php
session_start();
$conn = new mysqli("localhost", "root", "", "reflectme");

$user_id = $_SESSION['user_id'] ?? 1;
$filter = $_GET['filter'] ?? 'all';
$selected_date = $_GET['date'] ?? null;

// --- DYNAMIC FILTER LOGIC ---
$where_clause = "WHERE user_id = $user_id AND buddy_summary IS NOT NULL";
if ($selected_date) {
    $where_clause .= " AND DATE(created_at) = '$selected_date'";
} elseif ($filter == 'today') {
    $where_clause .= " AND DATE(created_at) = CURDATE()";
} elseif ($filter == 'week') {
    $where_clause .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($filter == 'month') {
    $where_clause .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}

// ... inside journey.php ...
$filter_date = $_GET['date'] ?? null;

if ($filter_date) {
    // Show only entries from the clicked date
    $query = "SELECT * FROM journal_entries WHERE user_id = $user_id AND DATE(created_at) = '$filter_date' ORDER BY created_at DESC";
} else {
    // Show everything if no date is picked
    $query = "SELECT * FROM journal_entries WHERE user_id = $user_id ORDER BY created_at DESC";
}
$result = $conn->query($query);
$query = "SELECT * FROM journal_entries $where_clause ORDER BY created_at DESC";
$result = $conn->query($query);

// --- THE 12 EMOTIONS COLOR WHEEL ---
$moodData = [
    'Happy'    => ['emoji' => '😊', 'color' => '#FFD700'], // Gold
    'Excited'  => ['emoji' => '🤩', 'color' => '#FF4500'], // OrangeRed
    'Proud'    => ['emoji' => '😌', 'color' => '#FFA500'], // Orange
    'Calm'     => ['emoji' => '🧘', 'color' => '#00FA9A'], // SpringGreen
    'Peaceful' => ['emoji' => '🕊️', 'color' => '#B2FAB4'], // Light Green
    'Neutral'  => ['emoji' => '😐', 'color' => '#CCCCCC'], // Gray
    'Sad'      => ['emoji' => '😔', 'color' => '#1E90FF'], // DodgerBlue
    'Lonely'   => ['emoji' => '😶', 'color' => '#A777E3'], // Purple
    'Anxious'  => ['emoji' => '😟', 'color' => '#DA70D6'], // Orchid
    'Stressed' => ['emoji' => '😫', 'color' => '#FF80AB'], // Pink
    'Angry'    => ['emoji' => '😠', 'color' => '#FF0000'], // Red
    'Fearful'  => ['emoji' => '😨', 'color' => '#8B0000'],  // DarkRed
    'Content'  => ['emoji' => '🙂', 'color' => '#F0E68C']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Journey | ReflectMe</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif;
            background: #0d0208 url('https://images.unsplash.com/photo-1534796636912-3b95b3ab5986?q=80&w=2071') no-repeat center fixed;
            background-size: cover; color: white; min-height: 100vh;
        }

        .overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(180deg, rgba(13, 2, 8, 0.4) 0%, rgba(13, 2, 8, 0.9) 100%);
            z-index: 0;
        }

        .container { position: relative; z-index: 1; max-width: 900px; margin: 0 auto; padding: 60px 20px; }

        .header { text-align: center; margin-bottom: 40px; }
        .header h1 { font-size: 2.2rem; font-weight: 400; margin-bottom: 5px; letter-spacing: 1px; }

        /* --- FILTER TABS & DATE PICKER --- */
        .tabs {
            display: flex; justify-content: center; align-items: center; gap: 20px; 
            margin-bottom: 50px; flex-wrap: wrap;
        }
        .tabs a {
            text-decoration: none; color: rgba(255,255,255,0.6); font-size: 0.85rem;
            padding-bottom: 5px; transition: 0.3s;
        }
        .tabs a.active { color: #fff; border-bottom: 1px solid #ffab91; }
        .date-picker {
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
            color: white; padding: 5px 10px; border-radius: 8px; cursor: pointer;
        }

        .day-title { font-size: 1rem; font-weight: 500; margin: 30px 0 15px; opacity: 0.9; color: #a2d9ff; }

        /* --- THE GLASS CARD --- */
        .entry-row {
            background: rgba(255, 255, 255, 0.04); backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 18px;
            padding: 15px 25px; margin-bottom: 12px;
            display: flex; align-items: center; transition: 0.3s; 
            text-decoration: none; color: inherit;
        }
        .entry-row:hover { background: rgba(255, 255, 255, 0.08); border-color: rgba(255,255,255,0.2); }

        /* EMOJI GLOW: Now uses the specific wheel color */
        .mood-icon-circle {
            width: 50px; height: 50px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; background: rgba(255,255,255,0.03);
            margin-right: 20px; flex-shrink: 0;
            border: 1px solid var(--glow);
            box-shadow: 0 0 15px var(--glow);
        }

        .time-box { width: 85px; font-size: 0.8rem; opacity: 0.6; }

        .content-box { flex-grow: 1; padding: 0 10px; overflow: hidden; }
        .mood-label { font-size: 0.95rem; font-weight: 600; display: block; margin-bottom: 3px; }
        .reframe-preview { font-size: 0.85rem; opacity: 0.7; line-height: 1.4; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* INTENSITY BAR: Transitions to the mood color */
        .intensity-section { display: flex; align-items: center; gap: 10px; width: 140px; }
        .mini-bar { flex-grow: 1; height: 4px; background: rgba(255,255,255,0.1); border-radius: 2px; overflow: hidden; }
        .mini-fill {
            height: 100%; background: linear-gradient(90deg, #5bc0de, var(--glow));
        }
        .intensity-num { font-size: 0.75rem; opacity: 0.8; font-weight: 600; white-space: nowrap; }

        /* CENTERED ADD BUTTON */
        .add-btn {
            position: fixed; bottom: 35px; right: 35px; width: 60px; height: 60px;
            background: #fff; color: #000; border-radius: 50%;
            display: grid; place-items: center; /* Best way to center a plus sign */
            text-decoration: none; font-size: 2rem; font-weight: bold;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5); transition: 0.3s; z-index: 100;
        }
        .add-btn:hover { transform: scale(1.1) rotate(90deg); }
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
    </style>
</head>
<body>
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
<div class="overlay"></div>

<div class="container">
    <div class="header">
        <h1>Your Journey</h1>
    </div>
<div class="journal-card" id="entry-<?php echo $row['id']; ?>">
    </div>
    <div class="tabs">
        <form method="GET" id="filterForm" style="display:flex; align-items:center; gap:20px;">
            <a href="?filter=all" class="<?= $filter == 'all' ? 'active' : '' ?>">All Time</a>
            <a href="?filter=today" class="<?= $filter == 'today' ? 'active' : '' ?>">Today</a>
            <a href="?filter=week" class="<?= $filter == 'week' ? 'active' : '' ?>">This Week</a>
            <a href="?filter=month" class="<?= $filter == 'month' ? 'active' : '' ?>">This Month</a>
            <input type="date" name="date" class="date-picker" onchange="this.form.submit()" value="<?= $selected_date ?>">
        </form>
    </div>

    <div class="timeline">
        <?php 
        $last_date = '';
        while($row = $result->fetch_assoc()): 
            $curr_date = date("l, F j", strtotime($row['created_at']));
            $time = date("h:i A", strtotime($row['created_at']));
            
            $emotions = json_decode($row['ai_emotions_json'], true);
            $primary = $emotions[0]['label'] ?? 'Neutral';
            $mood = $moodData[$primary] ?? $moodData['Neutral'];
            $intensity_pct = ($row['ai_intensity'] / 10) * 100;

            if ($curr_date != $last_date):
                echo "<div class='day-title'>$curr_date</div>";
                $last_date = $curr_date;
            endif;
        ?>
            <a href="reflection.php?id=<?= $row['id'] ?>" class="entry-row" style="--glow: <?= $mood['color'] ?>;">
                <div class="mood-icon-circle">
                    <?= $mood['emoji'] ?>
                </div>
                
                <div class="time-box"><?= $time ?></div>

                <div class="content-box">
                    <span class="mood-label"><?= $primary ?></span>
                    <span class="reframe-preview"><?= htmlspecialchars($row['reframe']) ?></span>
                </div>

                <div class="intensity-section">
                    <div class="mini-bar">
                        <div class="mini-fill" style="width: <?= $intensity_pct ?>%"></div>
                    </div>
                    <span class="intensity-num">🔥 <?= $row['ai_intensity'] ?>/10</span>
                </div>
            </a>
        <?php endwhile; ?>
    </div>
</div>

<a href="chat.php" class="add-btn">+</a>


</body>
</html>