<?php
require_once './config.php'; // include db + session

$user_id = $_settings->userdata('id') ?? null;

if ($user_id) {
    $stmt = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}
?>
