<?php
require_once 'config.php'; // DB connection + session

header('Content-Type: application/json');

$response = ['success' => false];
$user_id = $_settings->userdata('id');
$message = $_POST['message'] ?? '';
$team_id = $_POST['team_id'] ?? null;
$friend_id = $_POST['friend_id'] ?? null;

if (empty($message)) {
    echo json_encode($response);
    exit;
}

if ($team_id) {
    $stmt = $conn->prepare("INSERT INTO team_chats (team_id, sender_id, message, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $team_id, $user_id, $message);
} elseif ($friend_id) {
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $user_id, $friend_id, $message);
} else {
    echo json_encode($response);
    exit;
}

if ($stmt->execute()) {
    $response['success'] = true;
}
$stmt->close();

echo json_encode($response);
?>

