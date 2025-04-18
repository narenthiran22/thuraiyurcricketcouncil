<?php
include 'config.php';

header('Content-Type: application/json');

$current_user_id = $_settings->userdata('id');

if (!isset($_GET['player_id']) || !is_numeric($_GET['player_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$player_id = intval($_GET['player_id']);

// Get current user's last activity
$current_user_query = mysqli_query($conn, "SELECT last_activity FROM users WHERE id = '$current_user_id'");
if (!$current_user_query || mysqli_num_rows($current_user_query) === 0) {
    echo json_encode(['count' => 0]);
    exit;
}

$current_user_data = mysqli_fetch_assoc($current_user_query);
$current_user_last_activity = $current_user_data['last_activity'] ?? date('Y-m-d H:i:s');

// Get unread message count
$unread_q = mysqli_query($conn, "SELECT COUNT(*) as count FROM messages 
    WHERE sender_id = '$player_id' 
    AND receiver_id = '$current_user_id' 
    AND created_at >= '$current_user_last_activity'");

if (!$unread_q) {
    echo json_encode(['count' => 0]);
    exit;
}

$unread_result = mysqli_fetch_assoc($unread_q);
$unread_count = intval($unread_result['count']);

echo json_encode(['count' => $unread_count]);
?>
