<?php
session_start();
require_once '../api/db.php';

$user_id = $_SESSION['user_id'] ?? 1;

$query = "SELECT created_at, ai_intensity, themes, ai_emotions_json, user_valence 
          FROM journal_entries WHERE user_id = $user_id 
          ORDER BY created_at ASC LIMIT 14";
$result = $conn->query($query);

$trend_labels = [];
$intensity_data = [];
$valence_data = [];
$emotion_counts = [];
$theme_frequency = [];

while($row = $result->fetch_assoc()) {
    $trend_labels[] = date("j M", strtotime($row['created_at']));
    $intensity_data[] = (float)$row['ai_intensity'];
    // FIX: If valence is 0 or null, we treat it as 5 (neutral) for the graph
    $val = (float)($row['user_valence'] ?? 0);
    $valence_data[] = $val;

    $emotions = json_decode($row['ai_emotions_json'], true);
    if (is_array($emotions)) {
        foreach($emotions as $e) {
            if (is_array($e) && isset($e['label'])) {
                $emotion_counts[$e['label']] = ($emotion_counts[$e['label']] ?? 0) + 1;
            }
        }
    }

    $themes = json_decode($row['themes'], true);
    if (is_array($themes)) {
        foreach($themes as $t) {
            $theme_frequency[$t] = ($theme_frequency[$t] ?? 0) + 1;
        }
    }
}

// --- Dynamic Avg Valence Fix ---
$valid_scores = array_filter($valence_data, fn($v) => $v > 0);
$avg_valence = count($valid_scores) > 0 ? round(array_sum($valid_scores) / count($valid_scores), 1) : "N/A";

// --- Health Trend Logic Fix ---
$trend_text = "Analysis Pending";
$trend_color = "#94a3b8"; 
$trend_icon = "•";

if (count($valence_data) >= 4) {
    $half = floor(count($valence_data) / 2);
    $recent = array_slice($valence_data, -$half);
    $older = array_slice($valence_data, 0, $half);
    
    $recent_avg = array_sum($recent) / count($recent);
    $older_avg = array_sum($older) / count($older);

    if ($recent_avg > $older_avg + 0.2) {
        $trend_text = "Improving"; $trend_color = "#4ade80"; $trend_icon = "↑";
    } elseif ($recent_avg < $older_avg - 0.2) {
        $trend_text = "Declining"; $trend_color = "#f87171"; $trend_icon = "↓";
    } else {
        $trend_text = "Stable"; $trend_color = "#22d3ee"; $trend_icon = "→";
    }
}

arsort($theme_frequency);
$primary_theme = !empty($theme_frequency) ? array_key_first($theme_frequency) : "Reflection";
$top_themes = array_slice($theme_frequency, 0, 5); 

arsort($emotion_counts);
$emo_labels = array_keys(array_slice($emotion_counts, 0, 10));
$emo_values = array_values(array_slice($emotion_counts, 0, 10));
$brand_colors = ['#22d3ee', '#f472b6', '#a78bfa', '#fb923c', '#10b981', '#6366f1', '#f59e0b', '#ec4899', '#14b8a6', '#8b5cf6'];
?>