<?php
require_once './config.php.php'; // or your connection setup
$user_id = $_settings->userdata('id')?? 0;
$fav_team = $_settings->userdata('fav_team')?? 0;

$response = [
    'friend_requests' => 0,
    'others_status' => [],
    'seen_status' => [],
    'team_players' => []
];

// Friend requests count
$stmt = $conn->prepare("SELECT COUNT(*) FROM friend_requests WHERE receiver_id = ? AND status = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$response['friend_requests'] = $count;
$stmt->close();

// Statuses (Unseen and Seen)
$stmt = $conn->prepare("
    SELECT s.id, s.user_id, u.name, u.image AS user_image, s.media, s.media_type, s.caption, s.created_at,
        (SELECT COUNT(*) FROM status_views WHERE user_id = ? AND status_id = s.id) AS seen
    FROM statuses s
    JOIN users u ON u.id = s.user_id
    JOIN team_requests tr ON u.id = tr.user_id
    WHERE tr.team_id = ? AND u.id != ?
    ORDER BY s.created_at DESC
");
$stmt->bind_param("iii", $user_id, $fav_team, $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if ($row['seen']) {
        $response['seen_status'][] = $row;
    } else {
        $response['others_status'][] = $row;
    }
}
$stmt->close();

// Chat List
$stmt = $conn->prepare("
    SELECT u.id, u.name, u.image,
        (SELECT message FROM messages WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) AS last_message,
        (SELECT created_at FROM messages WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) AS last_message_time
    FROM users u
    JOIN team_requests tr ON u.id = tr.user_id
    WHERE tr.team_id = ? AND u.id != ?
    ORDER BY last_message_time DESC
");
$stmt->bind_param("iiiiii", $user_id, $user_id, $user_id, $user_id, $fav_team, $user_id);
$stmt->execute();
$response['team_players'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

header('Content-Type: application/json');
echo json_encode($response);
