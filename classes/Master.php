<?php
require_once '../config.php';

class Master extends DBConnection
{

    public function likeNews($newsId, $userId)
    {
        global $conn;
        $stmt = $conn->prepare("INSERT INTO news_likes (news_id, user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE created_at = NOW()");
        $stmt->bind_param("ii", $newsId, $userId);
        if ($stmt->execute()) {
            $stmt->close();
            $countStmt = $conn->prepare("SELECT COUNT(*) AS total_likes FROM news_likes WHERE news_id = ?");
            $countStmt->bind_param("i", $newsId);
            $countStmt->execute();
            $totalLikes = $countStmt->get_result()->fetch_assoc()['total_likes'] ?? 0;
            $countStmt->close();

            return ['status' => 'success', 'message' => 'News liked successfully', 'likes' => $totalLikes];
        } else {
            $stmt->close();
            return ['status' => 'error', 'message' => 'Failed to like news'];
        }
    }

    public function fetch_comments()
    {
        global $conn;
        $news_id = $_GET['news_id'] ?? 0;

        $stmt = $conn->prepare("
        SELECT 
            c.*, 
            u.name AS author, 
            u.image,
            (SELECT COUNT(*) FROM comment_likes cl WHERE cl.comment_id = c.id) AS likes
        FROM news_comments c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.news_id = ?
        ORDER BY c.created_at DESC
    ");
        $stmt->bind_param("i", $news_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $comments = [];
        while ($row = $result->fetch_assoc()) {
            $comments[] = [
                'id' => $row['id'],
                'text' => htmlspecialchars($row['comment'], ENT_QUOTES, 'UTF-8'),
                'author' => htmlspecialchars($row['author'] ?? 'Anonymous', ENT_QUOTES, 'UTF-8'),
                'author_profile' => !empty($row['image']) ? $row['image'] : 'assets/default-profile.png',
                'likes' => (int) $row['likes'],
                'date' => date('Y-m-d H:i', strtotime($row['created_at']))
            ];
        }

        // Fetch total comments count for this news
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM news_comments WHERE news_id = ?");
        $stmt->bind_param("i", $news_id);
        $stmt->execute();
        $totalComments = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

        echo json_encode([
            'status' => 'success',
            'comments' => $comments,
            'total' => $totalComments
        ]);
    }


    public function addComment($newsId, $userId, $comment)
    {
        global $conn;
        $stmt = $conn->prepare("INSERT INTO news_comments (news_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $newsId, $userId, $comment);
        if ($stmt->execute()) {
            $stmt->close();
            return ['status' => 'success', 'message' => 'Comment added successfully'];
        } else {
            $stmt->close();
            return ['status' => 'error', 'message' => 'Failed to add comment'];
        }
    }

    public function fetchNewsByTeam()
    {
        global $conn;

        $stmt = $conn->prepare("
        SELECT n.*, 
               t.name AS team, 
               (SELECT COUNT(*) FROM news_comments WHERE news_id = n.id) AS comments 
        FROM news n 
        LEFT JOIN teams t ON n.team_id = t.id 
        ORDER BY COALESCE(t.name, ''), n.created_at DESC;
        ");

        $stmt->execute();
        $result = $stmt->get_result();

        $newsByTeam = [];
        while ($row = $result->fetch_assoc()) {
            $team = $row['team'] ?? 'General';
            $newsByTeam[$team][] = [
                'id' => $row['id'],
                'title' => htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'),
                'content' => htmlspecialchars($row['content'], ENT_QUOTES, 'UTF-8'),
                'media' => htmlspecialchars($row['media'], ENT_QUOTES, 'UTF-8'),
                'date' => date('Y-m-d', strtotime($row['created_at'])),
                'author' => htmlspecialchars($row['author'], ENT_QUOTES, 'UTF-8'),
                'likes' => $row['likes'] ?? 0,
                'comments' => $row['comments'] ?? 0 // Ensure comments count is set
            ];
        }

        $stmt->close();

        return ['status' => 'success', 'news' => $newsByTeam];
    }

    public function fetchLikes($newsId)
    {
        global $conn;
        $stmt = $conn->prepare("SELECT COUNT(*) AS total_likes FROM news_likes WHERE news_id = ?");
        $stmt->bind_param("i", $newsId);
        $stmt->execute();
        $totalLikes = $stmt->get_result()->fetch_assoc()['total_likes'] ?? 0;
        $stmt->close();

        return ['status' => 'success', 'likes' => $totalLikes];
    }

    public function addNews($title, $content, $author, $team)
    {
        global $conn;
        $media = null;

        if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = "../uploads/news/images/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = basename($_FILES['media']['name']);
            $fileTmp = $_FILES['media']['tmp_name'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($fileExt, $allowed)) {
                $newFileName = uniqid("news_") . '.' . $fileExt;
                $targetPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmp, $targetPath)) {
                    $media = $newFileName; // Save only the file name, not the full path
                    error_log("Uploaded: $media");
                } else {
                    return ['status' => 'error', 'message' => 'Failed to upload media'];
                }
            } else {
                return ['status' => 'error', 'message' => 'Invalid file type'];
            }
        }


        $stmt = $conn->prepare("INSERT INTO news (title, content, author, team, media, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssis", $title, $content, $author, $team, $media);

        if ($stmt->execute()) {
            $stmt->close();
            return ['status' => 'success', 'message' => 'News added successfully'];
        } else {
            $stmt->close();
            return ['status' => 'error', 'message' => 'Failed to add news'];
        }
    }

    public function deleteNews($newsId)
    {
        global $conn;

        // Fetch the media file name before deleting the news
        $stmt = $conn->prepare("SELECT media FROM news WHERE id = ?");
        $stmt->bind_param("i", $newsId);
        $stmt->execute();
        $result = $stmt->get_result();
        $media = $result->fetch_assoc()['media'] ?? null;
        $stmt->close();

        // Delete the news record
        $stmt = $conn->prepare("DELETE FROM news WHERE id = ?");
        $stmt->bind_param("i", $newsId);
        if ($stmt->execute()) {
            $stmt->close();

            // Delete the media file if it exists
            if ($media) {
                $mediaPath = "../uploads/news/images/" . $media;
                if (file_exists($mediaPath)) {
                    unlink($mediaPath);
                }
            }

            return ['status' => 'success', 'message' => 'News and associated media deleted successfully'];
        } else {
            $stmt->close();
            return ['status' => 'error', 'message' => 'Failed to delete news'];
        }
    }

    public function addTeamMessage($teamId, $userId, $message)
    {
        global $conn;
        $stmt = $conn->prepare("INSERT INTO team_messages (team_id, user_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $teamId, $userId, $message);
        if ($stmt->execute()) {
            $stmt->close();
            return ['status' => 'success', 'message' => 'Message sent successfully'];
        } else {
            $stmt->close();
            return ['status' => 'error', 'message' => 'Failed to send message'];
        }
    }

    public function sendFriendRequest($senderId, $receiverId)
    {
        global $conn;
        $stmt = $conn->prepare("
            INSERT INTO friend_requests (sender_id, receiver_id, status, created_at) 
            VALUES (?, ?, 'pending', NOW())
            ON DUPLICATE KEY UPDATE status = 'pending', created_at = NOW()
        ");
        $stmt->bind_param("ii", $senderId, $receiverId);
        if ($stmt->execute()) {
            return ['status' => 'success', 'message' => 'Friend request sent successfully'];
        } else {
            return ['status' => 'error', 'message' => 'Failed to send friend request'];
        }
    }

    public function respondToFriendRequest($requestId, $status)
    {
        global $conn;
        $stmt = $conn->prepare("UPDATE friend_requests SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $requestId);
        if ($stmt->execute()) {
            $stmt->close();
            return ['status' => 'success', 'message' => 'Friend request updated successfully'];
        } else {
            $stmt->close();
            return ['status' => 'error', 'message' => 'Failed to update friend request'];
        }
    }

    public function muteNotifications($userId, $friendId)
    {
        global $conn;
        $stmt = $conn->prepare("UPDATE friend_requests SET notifications_muted = 1 WHERE sender_id = ? AND receiver_id = ?");
        $stmt->bind_param("ii", $friendId, $userId);
        if ($stmt->execute()) {
            $stmt->close();
            return ['status' => 'success', 'message' => 'Notifications muted successfully'];
        } else {
            $stmt->close();
            return ['status' => 'error', 'message' => 'Failed to mute notifications'];
        }
    }

    public function blockUser($userId, $friendId)
    {
        global $conn;
        $stmt = $conn->prepare("UPDATE friend_requests SET status = 'blocked' WHERE sender_id = ? AND receiver_id = ?");
        $stmt->bind_param("ii", $friendId, $userId);
        if ($stmt->execute()) {
            $stmt->close();
            return ['status' => 'success', 'message' => 'User blocked successfully'];
        } else {
            $stmt->close();
            return ['status' => 'error', 'message' => 'Failed to block user'];
        }
    }

    public function fetchFriendRequests($userId)
    {
        global $conn;
        $stmt = $conn->prepare("
            SELECT fr.id, u.name, u.image, fr.created_at 
            FROM friend_requests fr
            JOIN users u ON fr.sender_id = u.id
            WHERE fr.receiver_id = ? AND fr.status = 'pending'
            ORDER BY fr.created_at DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $friendRequests = [];
        while ($row = $result->fetch_assoc()) {
            $friendRequests[] = [
                'id' => $row['id'],
                'name' => htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'),
                'image' => $row['image'] ?: 'default.png',
                'created_at' => date('Y-m-d H:i', strtotime($row['created_at']))
            ];
        }
        $stmt->close();

        return ['status' => 'success', 'friend_requests' => $friendRequests];
    }

    public function addMoment($userId, $media, $caption = null)
    {
        global $conn;

        $uploadDir = "../uploads/status/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = basename($media['name']);
        $fileTmp = $media['tmp_name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'mp4']; // Specify allowed file types

        if (!in_array($fileExt, $allowed)) {
            return ['status' => 'error', 'message' => 'Invalid file type'];
        }

        $newFileName = uniqid("moment_") . '.' . $fileExt;
        $targetPath = $uploadDir . $newFileName;

        if (!move_uploaded_file($fileTmp, $targetPath)) {
            return ['status' => 'error', 'message' => 'Failed to upload media'];
        }

        $stmt = $conn->prepare("INSERT INTO statuses (user_id, media, media_type, caption, created_at) VALUES (?, ?, ?, ?, NOW())");
        $mediaType = in_array($fileExt, ['mp4']) ? 'video' : 'image';
        $stmt->bind_param("isss", $userId, $newFileName, $mediaType, $caption);

        if ($stmt->execute()) {
            $stmt->close();
            return ['status' => 'success', 'message' => 'Moment added successfully'];
        } else {
            $stmt->close();
            return ['status' => 'error', 'message' => 'Failed to add moment'];
        }
    }

    public function fetchStatuses($userId, $teamId)
    {
        global $conn;

        $statuses = [
            'my_status' => null,
            'others_status' => [],
            'seen_status' => []
        ];

        // Fetch all statuses for the team
        $stmt = $conn->prepare("
            SELECT s.id, s.user_id, u.name, u.image AS user_image, s.media, s.media_type, s.caption, s.created_at,
                   (SELECT COUNT(*) FROM status_views WHERE user_id = ? AND status_id = s.id) AS seen
            FROM statuses s
            JOIN users u ON u.id = s.user_id
            JOIN team_requests tr ON u.id = tr.user_id
            WHERE tr.team_id = ?
            ORDER BY s.created_at DESC
        ");
        $stmt->bind_param("ii", $userId, $teamId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            if ($row['user_id'] == $userId) {
                $statuses['my_status'] = $row;
            } elseif ($row['seen']) {
                $statuses['seen_status'][] = $row;
            } else {
                $statuses['others_status'][] = $row;
            }
        }

        $stmt->close();
        return $statuses;
    }

    public function fetchStatusesAjax($userId, $teamId)
    {
        $statuses = $this->fetchStatuses($userId, $teamId);
        echo json_encode(['status' => 'success', 'data' => $statuses]);
    }

    public function getStatusByUserId($userId)
    {
        global $conn;

        $stmt = $conn->prepare("
            SELECT s.id, s.media, s.media_type, s.caption, s.created_at
            FROM statuses s
            WHERE s.user_id = ?
            ORDER BY s.created_at DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return [
                'status' => 'success',
                'data' => [
                    'id' => $row['id'],
                    'media' => htmlspecialchars($row['media'], ENT_QUOTES, 'UTF-8'),
                    'media_type' => $row['media_type'],
                    'caption' => htmlspecialchars($row['caption'], ENT_QUOTES, 'UTF-8'),
                    'created_at' => $row['created_at']
                ]
            ];
        } else {
            $stmt->close();
            return ['status' => 'error', 'message' => 'No status found for the user'];
        }
    }

    public function getStatusViews($statusId)
    {
        global $conn;

        $stmt = $conn->prepare("
            SELECT COUNT(*) AS views
            FROM status_views
            WHERE status_id = ?
        ");
        $stmt->bind_param("i", $statusId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return ['status' => 'success', 'views' => $row['views']];
        } else {
            $stmt->close();
            return ['status' => 'error', 'message' => 'Failed to fetch views'];
        }
    }

    public function deleteStatus($statusId, $userId)
    {
        global $conn;

        // Fetch the media file name before deleting the status
        $stmt = $conn->prepare("SELECT media FROM statuses WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $statusId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $media = $result->fetch_assoc()['media'] ?? null;
        $stmt->close();

        // Delete the status record
        $stmt = $conn->prepare("DELETE FROM statuses WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $statusId, $userId);
        if ($stmt->execute()) {
            $stmt->close();

            // Delete the media file if it exists
            if ($media) {
                $mediaPath = "../uploads/status/" . $media;
                if (file_exists($mediaPath)) {
                    unlink($mediaPath);
                }
            }

            return ['status' => 'success', 'message' => 'Status deleted successfully'];
        } else {
            $stmt->close();
            return ['status' => 'error', 'message' => 'Failed to delete status'];
        }
    }
    function createMatchRequest($team1_id, $team2_id, $date, $time, $venue)
    {
        global $conn;

        if ($team1_id === $team2_id) {
            return ['status' => 'error', 'message' => 'A team cannot play against itself.'];
        }

        $venue = htmlspecialchars(trim($venue));

        $checkStmt = $conn->prepare("
        SELECT id FROM match_requests 
        WHERE (request_by = ? AND request_to = ? AND match_date = ? AND match_time = ?)
           OR (request_by = ? AND request_to = ? AND match_date = ? AND match_time = ?)
    ");
        $checkStmt->bind_param("iisiissi", $team1_id, $team2_id, $date, $time, $team2_id, $team1_id, $date, $time);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $checkStmt->close();
            return ['status' => 'error', 'message' => 'Match request already exists.'];
        }
        $checkStmt->close();

        $stmt = $conn->prepare("
        INSERT INTO match_requests (request_by, request_to, match_date, match_time, venue) 
        VALUES (?, ?, ?, ?, ?)
    ");
        $stmt->bind_param("iisss", $team1_id, $team2_id, $date, $time, $venue);

        if ($stmt->execute()) {
            $stmt->close();
            return ['status' => 'success', 'message' => 'Match request sent successfully.'];
        } else {
            $stmt->close();
            return ['status' => 'error', 'message' => 'Failed to send match request.'];
        }
    }
    public function respondMatchRequest($id, $action)
    {
        global $conn;

        $id = intval($id);
        $action = in_array($action, ['accepted', 'rejected']) ? $action : 'rejected';

        // Begin transaction (optional, for safety)
        $conn->begin_transaction();

        try {
            // Update match request status
            $stmt = $conn->prepare("UPDATE match_requests SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $action, $id);

            if (!$stmt->execute()) {
                throw new Exception("Failed to update match request.");
            }
            $stmt->close();

            // If accepted, insert into matches
            if ($action === 'accepted') {
                $stmt = $conn->prepare("SELECT request_by, request_to, match_date, match_time, venue FROM match_requests WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $request = $result->fetch_assoc();
                $stmt->close();

                if ($request) {
                    $stmt = $conn->prepare("
                    INSERT INTO matches (team1_id, team2_id, date, time, venue) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                    $stmt->bind_param("iisss", $request['request_by'], $request['request_to'], $request['match_date'], $request['match_time'], $request['venue']);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to insert new match.");
                    }
                    $stmt->close();

                    // Delete the original match request
                    $stmt = $conn->prepare("DELETE FROM match_requests WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to delete original match request.");
                    }
                    $stmt->close();
                } else {
                    throw new Exception("Match request not found.");
                }
            }

            $conn->commit();
            return ['status' => 'success', 'message' => "Match request has been $action."];
        } catch (Exception $e) {
            $conn->rollback();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function deleteMatchRequest($id)
    {
        global $conn;

        $stmt = $conn->prepare("DELETE FROM match_requests WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $stmt->close();
            return ['status' => 'success', 'message' => 'Match request deleted successfully.'];
        } else {
            $stmt->close();
            return ['status' => 'error', 'message' => 'Failed to delete match request.'];
        }
    }


    public function fetchScheduledMatches($teamId)
    {
        global $conn;

        $stmt = $conn->prepare("
            SELECT m.*, t1.name AS team1_name, t2.name AS team2_name 
            FROM matches m
            JOIN teams t1 ON m.team1_id = t1.id
            JOIN teams t2 ON m.team2_id = t2.id
            WHERE m.team1_id = ? OR m.team2_id = ?
            ORDER BY m.match_date, m.match_time
        ");
        $stmt->bind_param("ii", $teamId, $teamId);
        $stmt->execute();
        $result = $stmt->get_result();

        $matches = [];
        while ($row = $result->fetch_assoc()) {
            $matches[] = $row;
        }
        $stmt->close();

        return $matches;
    }

    public function fetchMatchRequests($teamId)
    {
        global $conn;

        $stmt = $conn->prepare("
            SELECT mr.*, t1.name AS request_by_name, t2.name AS request_to_name
            FROM match_requests mr
            JOIN teams t1 ON mr.request_by = t1.id
            JOIN teams t2 ON mr.request_to = t2.id
            WHERE mr.request_by = ? OR mr.request_to = ?
            ORDER BY mr.match_date, mr.match_time
        ");
        $stmt->bind_param("ii", $teamId, $teamId);
        $stmt->execute();
        $result = $stmt->get_result();

        $requests = [];
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
        $stmt->close();

        return $requests;
    }
    function loadPlayers()
    {
        global $conn;

        $team_id = $_POST['team_id'] ?? 0;
        $users = [];

        $players = $conn->query("SELECT * FROM users WHERE role != '3' AND fav_team = '$team_id'");

        if ($players && $players->num_rows > 0) {
            while ($row = $players->fetch_assoc()) {
                $users[] = $row; // Add entire row to array
            }
        }

        // Return JSON
        header('Content-Type: application/json');
        echo json_encode(['users' => $users]);
    }
    function loadusers()
    {
        global $conn;

        $team_id = $_POST['team_id'] ?? 0;
        $users = [];

        $players = $conn->query("SELECT * FROM users WHERE role = '3' AND fav_team = '$team_id'");

        if ($players && $players->num_rows > 0) {
            while ($row = $players->fetch_assoc()) {
                $users[] = $row; // Add entire row to array
            }
        }

        // Return JSON
        header('Content-Type: application/json');
        echo json_encode(['users' => $users]);
    }
    function suspendPlayer($user_id, $team_id)
    {
        global $conn;
        $suspend = 1; // 1 for suspended, 0 for active
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ? AND fav_team = ?");
        $stmt->bind_param("iii", $suspend, $user_id, $team_id);

        if ($stmt->execute()) {
            $stmt->close();
            return ['status' => 'success', 'message' => 'Player status updated successfully.'];
        } else {
            $stmt->close();
            return ['status' => 'error', 'message' => 'Failed to update player status.'];
        }
    }

    function promoteuser($user_id, $team_id)
    {
        global $conn;
        $suspend = 2; // 1 for suspended, 0 for active
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ? AND fav_team = ?");
        $stmt->bind_param("iii", $suspend, $user_id, $team_id);

        if ($stmt->execute()) {
            $stmt->close();
            return ['status' => 'success', 'message' => 'Player status updated successfully.'];
        } else {
            $stmt->close();
            return ['status' => 'error', 'message' => 'Failed to update player status.'];
        }
    }

    public function updateTeam($data, $files)
    {
        global $conn;

        $teamId = intval($data['team_id']);
        if (!$teamId) {
            return ['status' => 'error', 'message' => 'Invalid team ID'];
        }

        $name = htmlspecialchars(trim($data['name']));
        $nickname = htmlspecialchars(trim($data['nickname']));
        $address = htmlspecialchars(trim($data['address']));
        $contact = htmlspecialchars(trim($data['contact']));
        $disc = htmlspecialchars(trim($data['disc']));
        $captain = intval($data['captain']);
        $viceCaptain = intval($data['vice_captain']);
        $logo = null;

        if (isset($files['logo']) && $files['logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = "../uploads/team_logos/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = basename($files['logo']['name']);
            $fileTmp = $files['logo']['tmp_name'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png'];

            if (in_array($fileExt, $allowed)) {
                $newFileName = uniqid("team_") . '.' . $fileExt;
                $targetPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmp, $targetPath)) {
                    $logo = $newFileName;
                } else {
                    return ['status' => 'error', 'message' => 'Failed to upload logo'];
                }
            } else {
                return ['status' => 'error', 'message' => 'Invalid file type'];
            }
        }

        $stmt = $conn->prepare("
        UPDATE teams 
        SET name = ?, nickname = ?, address = ?, contact = ?, disc = ?, captain = ?, vice_captain = ?, logo = COALESCE(?, logo)
        WHERE id = ?
    ");
        $stmt->bind_param("sssssiisi", $name, $nickname, $address, $contact, $disc, $captain, $viceCaptain, $logo, $teamId);

        if ($stmt->execute()) {
            $stmt->close();
            return ['status' => 'success', 'message' => 'Team updated successfully'];
        } else {
            error_log("MySQL Error: " . $stmt->error);
            $stmt->close();
            return ['status' => 'error', 'message' => 'Database update failed'];
        }
    }


    public function getTeamDetails($teamId)
    {
        global $conn;

        $stmt = $conn->prepare("
            SELECT id, name, nickname, address, contact, disc, captain, vice_captain, logo
            FROM teams
            WHERE id = ?
        ");
        $stmt->bind_param("i", $teamId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($team = $result->fetch_assoc()) {
            $stmt->close();
            echo json_encode(['status' => 'success', 'team' => $team]);
        } else {
            $stmt->close();
            echo json_encode(['status' => 'error', 'message' => 'Team not found']);
        }
    }
    public function mark_status_seen()
    {
        extract($_GET);
        $user_id = $_SESSION['userdata']['id'] ?? null;

        if (!$user_id || !isset($status_id)) {
            return ['status' => 'failed', 'msg' => 'Invalid request'];
        }

        // Check if already viewed
        $check = $this->conn->query("SELECT * FROM status_views WHERE status_id = '$status_id' AND user_id = '$user_id'");
        if ($check && $check->num_rows > 0) {
            return ['status' => 'success', 'msg' => 'Already viewed'];
        }

        // Insert view record
        $insert = $this->conn->query("INSERT INTO status_views (status_id, user_id, viewed_at) VALUES ('$status_id', '$user_id', NOW())");
        if ($insert) {
            return ['status' => 'success', 'msg' => 'Status marked as seen'];
        } else {
            return ['status' => 'failed', 'msg' => 'Failed to mark as seen'];
        }
    }
    public function deleteAnnouncement($id)
    {
        global $conn;

        // Fetch the image file name before deleting the announcement
        $stmt = $conn->prepare("SELECT image FROM announcements WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $image = $result->fetch_assoc()['image'] ?? null;
        $stmt->close();

        // Delete the announcement record
        $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $stmt->close();

            // Delete the image file if it exists
            if ($image) {
                $imagePath = "../uploads/announcements/" . basename($image);
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            return ['status' => 'success', 'message' => 'Announcement and associated image deleted successfully'];
        } else {
            $stmt->close();
            return ['status' => 'error', 'message' => 'Failed to delete announcement'];
        }
    }
    public function toggleAnnouncementStatus($id)
    {
        global $conn;

        $stmt = $conn->prepare("SELECT status FROM announcements WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $newStatus = ($row['status'] === 'active') ? 'inactive' : 'active';

            $updateStmt = $conn->prepare("UPDATE announcements SET status = ? WHERE id = ?");
            $updateStmt->bind_param("si", $newStatus, $id);

            if ($updateStmt->execute()) {
                $updateStmt->close();
                return ['status' => 'success', 'message' => 'Status updated successfully'];
            } else {
                $updateStmt->close();
                return ['status' => 'error', 'message' => 'Failed to update status'];
            }
        } else {
            return ['status' => 'error', 'message' => 'Announcement not found'];
        }
    }
    public function addAnnouncement($data)
{
    global $conn;

    $heading = $conn->real_escape_string(trim($data['heading'] ?? ''));
    $message = $conn->real_escape_string(trim($data['message'] ?? ''));
    $fav_team = $_POST['fav_team'] ?? null;

    if (empty($heading) || empty($message)) {
        return ['success' => false, 'message' => 'Heading and Message are required.'];
    }

    // Handle image upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = 'announcement_' . time() . '.' . $ext;
        $target_dir = '../uploads/announcements/';
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $full_path = $target_dir . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $full_path)) {
            $image_path = 'uploads/announcements/' . $filename;
        } else {
            return ['success' => false, 'message' => 'Failed to upload image.'];
        }
    }

    $query = "
        INSERT INTO announcements (heading, message, image, posted_on, team_id, status)
        VALUES ('$heading', '$message', '$image_path', NOW(), " . ($fav_team ? "'$fav_team'" : "NULL") . ", 'active')
    ";

    $result = $conn->query($query);
    return ['success' => $result];
}

public function add_sponcre($conn) {
    $name = $_POST['name'];
    $party = $_POST['party'];
    $team_id = $_POST['team_id'];

    $photo_name = $_FILES['photo']['name'];
    $photo_tmp = $_FILES['photo']['tmp_name'];
    $upload_dir = "../uploads/sponsor_logos/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $photo_path = $upload_dir . basename($photo_name);

    if (move_uploaded_file($photo_tmp, $photo_path)) {
        $stmt = $conn->prepare("INSERT INTO sponsors (name, photo, party, team_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $name, $photo_name, $party, $team_id);
        $stmt->execute();
        $stmt->close();
    } else {
        return "Failed to upload sponsor logo.";
    }

    return $this->show_sponsors($conn, $team_id);
}

public function delete_sponcre($conn) {
    $id = intval($_GET['id']);
    $team_id = $_GET['team_id'];

    // Fetch the sponsor's photo before deletion
    $stmt = $conn->prepare("SELECT photo FROM sponsors WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $photo = $result->fetch_assoc()['photo'] ?? null;
    $stmt->close();

    // Delete the sponsor record
    $stmt = $conn->prepare("DELETE FROM sponsors WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $stmt->close();

        // Delete the photo file if it exists
        if ($photo) {
            $photo_path = "./uploads/sponsor_logos/" . basename($photo);
            if (file_exists($photo_path)) {
                unlink($photo_path);
            }
        }
    } else {
        $stmt->close();
        return "Failed to delete sponsor.";
    }

    return $this->show_sponsors($conn, $team_id);
}

public function show_sponsors($conn, $team_id) {
    $sponsors = $conn->query("SELECT * FROM sponsors WHERE team_id = $team_id");
    ob_start();
    while ($sponsor = $sponsors->fetch_assoc()): ?>
        <div class="relative bg-white rounded-xl border shadow-lg overflow-hidden">
            <img src="./uploads/sponsor_logos/<?php echo htmlspecialchars($sponsor['photo']); ?>" class="w-full h-32 object-cover">
            <div class="p-2 text-center bg-<?php echo htmlspecialchars($sponsor['party']); ?> text-white font-semibold">
                <?php echo strtoupper(htmlspecialchars($sponsor['name'])); ?>
            </div>
            <button onclick="deleteSponsor(<?php echo $sponsor['id']; ?>)" class="absolute top-1 right-1 btn btn-xs btn-error">✕</button>
        </div>
    <?php endwhile;
    return ob_get_clean();
}

}

// Initialize the Master class
$master = new Master();

$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);

switch ($action) {
        case 'add_sponcre':
        echo  $master->add_sponcre($conn);
        break;

    case 'delete_sponcre':
        echo $master->delete_sponcre($conn);
        break;

    case 'show_sponcre':
        $team_id = $_GET['team_id'];
        echo $master->show_sponsors($conn, $team_id);
        break;
    case 'add_announcement':
        echo json_encode($master->addAnnouncement($_POST));
        break;


    case 'mark_status_seen':
        echo json_encode($master->mark_status_seen());
        break;

    case 'suspend_player':
        $user_id = $_POST['user_id'] ?? 0;
        $team_id = $_POST['team_id'] ?? 0;
        if ($user_id && $team_id) {
            echo json_encode($master->suspendPlayer($user_id, $team_id));
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
        }
        break;
    case 'promote_user':
        $user_id = $_POST['user_id'] ?? 0;
        $team_id = $_POST['team_id'] ?? 0;
        if ($user_id && $team_id) {
            echo json_encode($master->promoteuser($user_id, $team_id));
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
        }
        break;
    case 'loadplayers':
        echo $master->loadPlayers();
        break;
    case 'loadusers':
        echo $master->loadusers();
        break;
    case 'get_vote_status':
        $request_id = $_POST['request_id'];
        $result = $conn->query("SELECT vote, COUNT(*) as total FROM match_votes WHERE request_id = $request_id GROUP BY vote");
        $votes = ['accept' => 0, 'reject' => 0];
        while ($row = $result->fetch_assoc()) {
            if ($row['vote'] === 'accepted') $votes['accept'] = $row['total'];
            else $votes['reject'] = $row['total'];
        }
        echo json_encode(['status' => 'success', 'votes' => $votes]);
        break;

    case 'cast_match_vote':
        $request_id = intval($_POST['request_id'] ?? 0);
        $user_id = $_settings->userdata('id');
        $vote = $_POST['vote'] ?? '';
        $reason = $_POST['reason'] ?? null;

        if (!in_array($vote, ['accepted', 'rejected'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid vote type']);
            break;
        }

        $check = $conn->prepare("SELECT id FROM match_votes WHERE request_id = ? AND user_id = ?");
        $check->bind_param("ii", $request_id, $user_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO match_votes (request_id, user_id, vote, reason) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $request_id, $user_id, $vote, $reason);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Vote submitted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to submit vote']);
            }
            $stmt->close();
        } else {
            $stmt = $conn->prepare("UPDATE match_votes SET vote = ?, reason = ? WHERE request_id = ? AND user_id = ?");
            $stmt->bind_param("ssii", $vote, $reason, $request_id, $user_id);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Vote updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update vote']);
            }
            $stmt->close();
        }
        $check->close();
        break;

    case 'respond_match_request':
        echo json_encode($master->respondMatchRequest($_POST['id'], $_POST['action']));
        break;

    case 'create_match_request':
        $team1_id = intval($_POST['team1_id'] ?? 0);
        $team2_id = intval($_POST['team2_id'] ?? 0);
        $date = $_POST['date'] ?? '';
        $time = $_POST['time'] ?? '';
        $venue = $_POST['venue'] ?? '';

        echo json_encode($master->createMatchRequest($team1_id, $team2_id, $date, $time, $venue));
        break;

    case 'delete_news':
        $newsId = $_POST['news_id'] ?? null; // Corrected parameter name
        if ($newsId) {
            echo json_encode($master->deleteNews($newsId));
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters. News ID is required.']);
        }
        break;

    case 'add_news':
        $title = $_POST['title'] ?? null;
        $content = $_POST['content'] ?? null;
        $author = $_POST['author'] ?? null;
        $team = $_settings->userdata('fav_team'); // From form or default to user's fav_team

        if ($title && $content && $author) {
            echo json_encode($master->addNews($title, $content, $author, $team));
        } else {
            echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        }
        break;

    case 'like':
        $newsId = $_POST['news_id'] ?? null;
        $userId = $_POST['user_id'] ?? null;
        if ($newsId && $userId) {
            echo json_encode($master->likeNews($newsId, $userId));
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters. User must be logged in to like news.']);
        }
        break;

    case 'fetch_comments':
        $newsId = $_GET['news_id'] ?? null;

        if (!$newsId) {
            echo json_encode(['status' => 'error', 'message' => 'News ID is required']);
            exit;
        }

        // Directly echo the JSON from fetch_comments
        echo $master->fetch_comments();
        break;


    case 'add_comment':
        $newsId = $_POST['news_id'] ?? null;
        $userId = $_POST['user_id'] ?? null;
        $comment = $_POST['comment'] ?? null;
        if ($newsId && $userId && $comment) {
            echo json_encode($master->addComment($newsId, $userId, $comment));
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters. User must be logged in to comment.']);
        }
        break;

    case 'fetch_news_by_team':
        echo json_encode($master->fetchNewsByTeam());
        break;

    case 'fetch_likes':
        $newsId = $_GET['news_id'] ?? null;
        if ($newsId) {
            echo json_encode($master->fetchLikes($newsId));
        } else {
            echo json_encode(['status' => 'error', 'message' => 'News ID is required']);
        }
        break;

    case 'add_team_message':
        $teamId = $_POST['team_id'] ?? null;
        $userId = $_POST['user_id'] ?? null;
        $message = $_POST['message'] ?? null;
        if ($teamId && $userId && $message) {
            echo json_encode($master->addTeamMessage($teamId, $userId, $message));
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
        }
        break;

    case 'send_friend_request':
        $senderId = $_settings->userdata('id') ?? null;
        $receiverId = $_POST['receiver_id'] ?? null;

        if (!$senderId || !$receiverId) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
            exit;
        }

        if ($senderId == $receiverId) {
            echo json_encode(['status' => 'error', 'message' => 'You cannot send a friend request to yourself']);
            exit;
        }

        $stmt = $conn->prepare("SELECT id FROM friend_requests WHERE sender_id = ? AND receiver_id = ?");
        $stmt->bind_param("ii", $senderId, $receiverId);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Friend request already sent']);
        } else {
            $stmt->close();
            echo json_encode($master->sendFriendRequest($senderId, $receiverId));
        }
        $stmt->close();
        break;

    case 'respond_to_friend_request':
        $requestId = $_POST['request_id'] ?? null;
        $status = $_POST['status'] ?? null;
        if ($requestId && $status) {
            echo json_encode($master->respondToFriendRequest($requestId, $status));
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
        }
        break;

    case 'mute_notifications':
        $userId = $_POST['user_id'] ?? null;
        $friendId = $_POST['friend_id'] ?? null;
        if ($userId && $friendId) {
            echo json_encode($master->muteNotifications($userId, $friendId));
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
        }
        break;

    case 'block_user':
        $userId = $_POST['user_id'] ?? null;
        $friendId = $_POST['friend_id'] ?? null;
        if ($userId && $friendId) {
            echo json_encode($master->blockUser($userId, $friendId));
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
        }
        break;

    case 'fetch_friend_requests':
        $userId = $_GET['user_id'] ?? null;
        if ($userId) {
            echo json_encode($master->fetchFriendRequests($userId));
        } else {
            echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
        }
        break;

    case 'add_moment':
        $userId = $_settings->userdata('id') ?? null; // Assuming user ID is stored in session
        $caption = $_POST['caption'] ?? null;
        $media = $_FILES['media'] ?? null;

        if (!$userId) {
            echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
        } elseif (!$media) {
            echo json_encode(['status' => 'error', 'message' => 'Media file is required']);
        } else {
            echo json_encode($master->addMoment($userId, $media, $caption));
        }
        break;

    case 'fetch_statuses':
        $userId = $_GET['user_id'] ?? null;
        $teamId = $_GET['team_id'] ?? null;

        if ($userId && $teamId) {
            $master->fetchStatusesAjax($userId, $teamId);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters.']);
        }
        break;

    case 'get_status_by_user_id':
        $userId = $_GET['user_id'] ?? null;
        if ($userId) {
            echo json_encode($master->getStatusByUserId($userId));
        } else {
            echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
        }
        break;

    case 'get_status_views':
        $statusId = $_GET['status_id'] ?? null;
        if ($statusId) {
            echo json_encode($master->getStatusViews($statusId));
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Status ID is required']);
        }
        break;

    case 'delete_status':
        $statusId = $_POST['status_id'] ?? null;
        $userId = $_settings->userdata('id') ?? null; // Assuming user ID is stored in session
        if ($statusId && $userId) {
            echo json_encode($master->deleteStatus($statusId, $userId));
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
        }
        break;

    case 'get_voters':
        $request_id = intval($_POST['request_id'] ?? 0);
        $stmt = $conn->prepare("
            SELECT mv.vote, u.name, u.image 
            FROM match_votes mv
            JOIN users u ON mv.user_id = u.id
            WHERE mv.request_id = ?
        ");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $voters = [];
        while ($row = $result->fetch_assoc()) {
            $voters[] = [
                'name' => htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'),
                'avatar' => $row['image'] ?: 'assets/default-profile.png',
                'vote' => $row['vote']
            ];
        }
        $stmt->close();

        echo json_encode(['status' => 'success', 'voters' => $voters]);
        break;

    case 'delete_match_request':
        $id = intval($_POST['id'] ?? 0);
        echo json_encode($master->deleteMatchRequest($id));
        break;

    case 'get_team_users': // Fix: Added a proper case for get_team_users
        $team_id = $_POST['team_id'] ?? 0;
        $qry = $conn->query("SELECT id, name FROM users WHERE fav_team = '{$team_id}' ORDER BY name ASC");
        $users = [];
        while ($row = $qry->fetch_assoc()) {
            $users[] = $row;
        }
        echo json_encode(['status' => 'success', 'users' => $users]);
        break;

    case 'update_team':
        $result = $master->updateTeam($_POST, $_FILES);
        echo json_encode($result); // ONLY this echo
        break;

    case 'get_team_details':
        $teamId = intval($_GET['team_id'] ?? 0);
        if ($teamId) {
            $master->getTeamDetails($teamId);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid team ID']);
        }
        break;
    case 'updatescore':
        $match_id = intval($_POST['match_id'] ?? 0);
        $team1_score = intval($_POST['team1_score'] ?? 0);
        $team2_score = intval($_POST['team2_score'] ?? 0);

        if ($match_id > 0) {
            $stmt = $conn->prepare("UPDATE matches SET team1_score = ?, team2_score = ? WHERE id = ?");
            $stmt->bind_param("iii", $team1_score, $team2_score, $match_id);

            if ($stmt->execute()) {
                echo "1"; // Success
            } else {
                echo "0"; // Failure
            }
            $stmt->close();
        } else {
            echo "0"; // Invalid match ID
        }
        break;
    case 'delete_announcement':
        if (isset($_POST['id'])) {
            echo json_encode($master->deleteAnnouncement($_POST['id']));
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Missing announcement ID']);
        }
        break;

    case 'toggle_announcement_status':
        if (isset($_POST['id'])) {
            echo json_encode($master->toggleAnnouncementStatus($_POST['id']));
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Missing announcement ID']);
        }
        break;


    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
