<?php
session_start();
$conn = new mysqli("localhost", "root", "", "reflectme");
$user_id = $_SESSION['user_id'] ?? 1;

$month = (int)$_GET['m'];
$year = (int)$_GET['y'];

$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$month_name = date('F Y', mktime(0, 0, 0, $month, 1, $year));

$cal_entries = [];
// ADDED: Fetching 'id' from the table
$query = "SELECT id, DAY(created_at) as d, ai_intensity 
          FROM journal_entries 
          WHERE user_id = $user_id AND MONTH(created_at) = $month AND YEAR(created_at) = $year";
$res = $conn->query($query);

while($row = $res->fetch_assoc()) {
    $intensity = $row['ai_intensity'];
    if($intensity <= 3) $color = "rgba(144, 238, 144, 0.7)"; 
    elseif($intensity <= 7) $color = "rgba(255, 183, 77, 0.7)"; 
    else $color = "rgba(239, 83, 80, 0.8)"; 

    // Store the ID so we can use it for the link
    $cal_entries[$row['d']] = [
        'color' => $color, 
        'entry_id' => $row['id'] 
    ];
}

$days = [];
for($i = 1; $i <= $daysInMonth; $i++) {
    $has_entry = isset($cal_entries[$i]);
    $days[] = [
        'day_num' => $i,
        'has_entry' => $has_entry,
        'color' => $has_entry ? $cal_entries[$i]['color'] : 'rgba(255,255,255,0.05)',
        'entry_id' => $has_entry ? $cal_entries[$i]['entry_id'] : null
    ];
}

echo json_encode(['month_name' => $month_name, 'days' => $days]);