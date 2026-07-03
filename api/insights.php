<?php 
// 1. Include the data logic
include 'get_insights_data.php'; 

// 2. Extra safety check for the average valence if not defined in the include
$valid_scores = array_filter($valence_data, fn($v) => $v > 0);
$display_avg = count($valid_scores) > 0 ? round(array_sum($valid_scores) / count($valid_scores), 1) : "0.0";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ReflectMe | Emotional Insights</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg: #08080a;
            --card-bg: rgba(255, 255, 255, 0.04);
            --border: rgba(255, 255, 255, 0.1);
            --accent-cyan: #22d3ee;
            --accent-pink: #f472b6;
            --accent-purple: #a78bfa;
        }

        body {
            margin: 0;
            padding: 40px;
            min-height: 100vh;
            color: white;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            
            /* BACKGROUND FIX: Layering the image UNDER a transparent gradient */
            background-image: 
                radial-gradient(circle at 50% -20%, rgba(30, 30, 46, 0.7) 0%, rgba(8, 8, 10, 0.9) 100%),
                url('bg.png'); 
            
            background-attachment: fixed;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px; 
            max-width: 1200px; 
            margin: 0 auto;
        }

        .banner {
            grid-column: span 4;
            background: linear-gradient(135deg, rgba(34, 211, 238, 0.1) 0%, rgba(244, 112, 182, 0.1) 100%);
            border: 1px solid var(--border); 
            border-radius: 24px;
            padding: 40px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            margin-bottom: 10px;
        }

        .card {
            background: var(--card-bg); 
            border: 1px solid var(--border);
            border-radius: 20px; 
            padding: 25px; 
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            transition: transform 0.3s ease;
        }

        .stat-card { text-align: left; }
        .stat-label { font-size: 11px; opacity: 0.5; text-transform: uppercase; letter-spacing: 1.5px; }
        .stat-value { font-size: 24px; font-weight: 600; margin: 10px 0; display: flex; align-items: center; gap: 8px; }

        .span-2 { grid-column: span 2; }
        .chart-header { font-size: 14px; margin-bottom: 25px; opacity: 0.8; font-weight: 600; letter-spacing: 0.5px; }

        .progress-item { margin-bottom: 15px; transition: all 0.3s ease; padding: 8px; border-radius: 12px; }
        .progress-label { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 8px; }
        .progress-track { height: 6px; background: rgba(255,255,255,0.05); border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 10px; transition: width 1s ease-in-out; }

        .alignment-score {
            background: rgba(34, 197, 94, 0.1); color: #4ade80;
            padding: 8px 16px; border-radius: 10px; font-weight: 600; font-size: 13px;
        }
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

<div class="dashboard-grid">
    <div class="banner">
        <div>
            <h1 style="margin:0; font-size: 32px; letter-spacing: -0.5px;">Insights</h1>
            <p style="margin:5px 0 0; opacity:0.6;">Your emotional well-being dashboard</p>
        </div>
        <div style="background: rgba(255,255,255,0.07); border: 1px solid var(--border); padding: 8px 18px; border-radius: 10px; font-size: 12px; font-weight: 600;">30 DAY OVERVIEW</div>
    </div>

    <div class="card stat-card">
        <div class="stat-label">Health Trend</div>
        <div class="stat-value" style="color: <?= $trend_color ?>;">
            <?= $trend_icon ?> <span style="font-size: 20px; margin-left: 4px;"><?= $trend_text ?></span>
        </div>
    </div>

    <div class="card stat-card">
        <div class="stat-label">Avg Valence</div>
        <div class="stat-value">
            <?= $display_avg ?> <span style="font-size:12px; opacity:0.5">/ 10</span>
        </div>
    </div>

    <div class="card stat-card">
        <div class="stat-label">Primary Theme</div>
        <div class="stat-value" style="font-size: 18px; color: var(--accent-cyan);"><?= $primary_theme ?></div>
    </div>

    <div class="card stat-card">
        <div class="stat-label">Self-Awareness</div>
        <div class="stat-value" style="color: var(--accent-pink);">88%</div>
    </div>

    <div class="card span-2">
        <div class="chart-header">Mood Trends</div>
        <canvas id="moodChart" height="160"></canvas>
    </div>

    <div class="card span-2">
        <div class="chart-header">Emotion Breakdown</div>
        <div style="display:flex; align-items:center; gap:30px">
            <div style="width:150px"><canvas id="breakdownChart"></canvas></div>
            <div style="flex-grow:1">
                <?php if(!empty($emo_labels)): ?>
                    <?php foreach($emo_labels as $i => $l): 
                        $color = $brand_colors[$i % count($brand_colors)];
                    ?>
                    <div class="progress-item" id="emo-row-<?= $i ?>">
                        <div class="progress-label">
                            <span style="display:flex; align-items:center; gap:8px; font-weight: 600;">
                                <span style="width:8px; height:8px; background:<?= $color ?>; border-radius:50%; box-shadow: 0 0 8px <?= $color ?>;"></span>
                                <?= $l ?>
                            </span>
                            <span style="opacity: 0.7;"><?= $emo_values[$i] ?>%</span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill" style="width:<?= ($emo_values[$i]/max($emo_values))*100 ?>%; background: <?= $color ?>; box-shadow: 0 0 12px <?= $color ?>33;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="font-size: 12px; opacity: 0.5;">No emotion data found yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card span-2">
        <div class="chart-header">Mood Alignment</div>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
            <div class="alignment-score">Great Alignment 80%</div>
        </div>
        <div class="progress-item">
            <div class="progress-label" style="opacity:0.6">Your Mood (5.0)</div>
            <div class="progress-track"><div class="progress-fill" style="width:50%; background: var(--accent-cyan)"></div></div>
        </div>
        <div class="progress-item">
            <div class="progress-label" style="opacity:0.6">AI Detected (4.0)</div>
            <div class="progress-track"><div class="progress-fill" style="width:40%; background: var(--accent-pink)"></div></div>
        </div>
    </div>

    <div class="card span-2">
        <div class="chart-header">Theme Frequency</div>
        <?php if(!empty($top_themes)): ?>
            <?php foreach($top_themes as $name => $count): ?>
            <div class="progress-item">
                <div class="progress-label"><span style="font-weight: 600;"><?= $name ?></span><span style="opacity: 0.6;"><?= $count ?> Entries</span></div>
                <div class="progress-track"><div class="progress-fill" style="width:<?= ($count/max($top_themes))*100 ?>%; background: var(--accent-purple)"></div></div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
             <p style="font-size: 12px; opacity: 0.5;">Add more entries to see themes.</p>
        <?php endif; ?>
    </div>
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
<script>
    // Neon Line Chart
    new Chart(document.getElementById('moodChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($trend_labels) ?>,
            datasets: [
                {
                    label: 'Valence',
                    data: <?= json_encode($valence_data) ?>,
                    borderColor: '#22d3ee',
                    backgroundColor: 'rgba(34, 211, 238, 0.1)',
                    fill: true, tension: 0.4, borderWidth: 3, pointRadius: 4, pointBackgroundColor: '#22d3ee'
                },
                {
                    label: 'Intensity',
                    data: <?= json_encode($intensity_data) ?>,
                    borderColor: '#f472b6',
                    fill: false, tension: 0.4, borderDash: [5, 5], borderWidth: 2, pointRadius: 0
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { min: 0, max: 10, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: 'rgba(255,255,255,0.3)', font: { size: 10 } } },
                x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.3)', font: { size: 10 } } }
            }
        }
    });

    // Interactive Doughnut Chart
    const breakdownChart = new Chart(document.getElementById('breakdownChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($emo_labels) ?>,
            datasets: [{
                data: <?= json_encode($emo_values) ?>,
                backgroundColor: <?= json_encode($brand_colors) ?>,
                borderWidth: 0,
                hoverOffset: 15
            }]
        },
        options: { 
            cutout: '82%', 
            plugins: { legend: { display: false } },
            onHover: (event, chartElement) => {
                document.querySelectorAll('.progress-item[id^="emo-row-"]').forEach(el => {
                    el.style.background = 'transparent';
                    el.style.transform = 'scale(1)';
                });

                if (chartElement.length > 0) {
                    const index = chartElement[0].index;
                    const row = document.getElementById(`emo-row-${index}`);
                    if (row) {
                        row.style.background = 'rgba(255, 255, 255, 0.08)';
                        row.style.transform = 'scale(1.03)';
                    }
                }
            }
        }
    });
</script>
</body>
</html>