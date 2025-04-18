<?php
require_once 'config.php'; // your DB and $_settings initialization

header('Content-Type: application/json');

$userid = $_settings->userdata('id');
$notifications = [];

// Check if user ID is valid
if (!$userid) {
    echo json_encode([]);
    exit;
}

// Get team ID
$userquery = "SELECT fav_team FROM users WHERE id = '$userid'";
$userresult = $conn->query($userquery);
$teamid = null;

if ($userresult && $userresult->num_rows > 0) {
    $userdata = $userresult->fetch_assoc();
    $teamid = $userdata['fav_team'];
}

// Now fetch notifications
$sql = "SELECT * FROM notifications 
        WHERE is_read = 0 
        AND (
            user_id = '$userid' 
            OR (user_id IS NULL AND team_id = '$teamid')
        )
        ORDER BY created_at DESC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'title' => $row['title'],
            'body' => $row['body'],
            'icon' => $row['icon'] ?? './Icon-180.png',
            'badge' => $row['badge'] ?? '',
            'url' => $row['url'] ?? '#',
            'id' => $row['id']
        ];
    }
}

echo json_encode($notifications);
exit;
