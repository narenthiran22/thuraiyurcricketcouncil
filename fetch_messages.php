<?php
require_once 'config.php';

header('Content-Type: application/json');

$team_id = $_GET['team_id'] ?? null;
$friend_id = $_GET['friend_id'] ?? null;
$user_id = $_settings->userdata('id');

if ($team_id) {
    $stmt = $conn->prepare("
        SELECT tc.id, tc.sender_id, u.name AS sender_name, u.image AS sender_image, tc.message, tc.created_at
        FROM team_chats tc
        JOIN users u ON tc.sender_id = u.id
        WHERE tc.team_id = ?
        ORDER BY tc.created_at ASC
    ");
    $stmt->bind_param("i", $team_id);
} elseif ($friend_id) {
    $stmt = $conn->prepare("
        SELECT fc.id, fc.sender_id, u.name AS sender_name, u.image AS sender_image, fc.message, fc.created_at
        FROM messages fc
        JOIN users u ON fc.sender_id = u.id
        WHERE (fc.sender_id = ? AND fc.receiver_id = ?) OR (fc.sender_id = ? AND fc.receiver_id = ?)
        ORDER BY fc.created_at ASC
    ");
    $stmt->bind_param("iiii", $user_id, $friend_id, $friend_id, $user_id);
} else {
    echo json_encode([]);
    exit;
}

$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode($messages);
?>
