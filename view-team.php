<?php
include 'config.php'; // your DB connection file

$teamId = $_GET['id'] ?? null;

if (!$teamId) {
    echo "<script>alert_toast('Team not found.','error')</script>";
    exit;
}

$stmt = $conn->prepare("
 SELECT 
    t.name AS team_name, 
    t.logo, 
    t.address, 
    t.contact, 
    t.nickname, 
    t.disc, 
    
    u0.name AS created_by, 
    u0.image AS created_by_image,
    
    u1.name AS captain, 
    u1.image AS captain_image,
    
    u2.name AS vice_captain, 
    u2.image AS vice_captain_image

FROM 
    teams t
LEFT JOIN 
    users u1 ON t.captain = u1.id
LEFT JOIN 
    users u2 ON t.vice_captain = u2.id
LEFT JOIN
    users u0 ON t.created_by = u0.id
WHERE 
    t.id = ?

");

$stmt->bind_param("i", $teamId);
$stmt->execute();
$result = $stmt->get_result();
$team = $result->fetch_assoc();

$playersStmt = $conn->prepare("SELECT id, name, image, role FROM users WHERE fav_team = ? AND role != 3");
$playersStmt->bind_param("i", $teamId);
$playersStmt->execute();
$playersResult = $playersStmt->get_result();
$players = $playersResult->fetch_all(MYSQLI_ASSOC);

$matchesStmt = $conn->prepare("
    SELECT 
        m.*, 
        t1.name AS team1_name, t1.logo AS team1_logo,
        t2.name AS team2_name, t2.logo AS team2_logo
    FROM 
        matches m
    JOIN teams t1 ON m.team1_id = t1.id
    JOIN teams t2 ON m.team2_id = t2.id
    WHERE 
        m.team1_id = ? OR m.team2_id = ?
    ORDER BY m.date DESC, m.time DESC
");
$matchesStmt->bind_param("ii", $teamId, $teamId);
$matchesStmt->execute();
$matchesResult = $matchesStmt->get_result();
$matches = $matchesResult->fetch_all(MYSQLI_ASSOC);


if (!$team) {
    echo "<script>alert_toast('Team not found.','error')</script>";
    exit;
}
?>

<div class="max-w-2xl mx-auto px-4 py-6 text-base-content space-y-8">

    <!-- Team Header -->
    <div class="bg-gradient-to-br from-base-100 to-base-200 shadow-xl rounded-3xl p-6">
        <div class="flex items-center gap-4 mb-6">
            <img src="./uploads/team_logos/<?= htmlspecialchars($team['logo']); ?>" alt="Team Logo"
                class="w-16 h-16 sm:w-20 sm:h-20 object-cover rounded-xl bg-base-200 p-2 shadow-md">
            <div>
                <h1 class="text-xl font-bold text-primary"><?= htmlspecialchars($team['team_name']); ?></h1>
                <p class="text-sm text-base-content opacity-70 mt-1">
                    <i class="fa-solid fa-tag text-warning mr-1"></i><?= htmlspecialchars($team['nickname']); ?>
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-base-100 p-4 rounded-xl shadow-sm">
                <h2 class="text-sm font-semibold mb-1">
                    <i class="fa-solid fa-location-dot text-error mr-1"></i>Address
                </h2>
                <p class="text-sm opacity-80"><?= htmlspecialchars($team['address']); ?></p>
            </div>

            <div class="bg-base-100 p-4 rounded-xl shadow-sm">
                <h2 class="text-sm font-semibold mb-1">
                    <i class="fa-solid fa-phone text-success mr-1"></i>Contact
                </h2>
                <p class="text-sm opacity-80"><?= htmlspecialchars($team['contact']); ?></p>
            </div>

            <div class="bg-base-100 p-4 rounded-xl shadow-sm flex items-center gap-4 col-span-full sm:col-span-1">
                <img src="uploads/users/<?= $team['captain_image'] ?? 'default.png' ?>"
                    class="w-12 h-12 object-cover rounded-full border-2 border-primary">
                <div>
                    <h2 class="text-sm font-semibold">
                        <i class="fa-solid fa-crown text-warning mr-1"></i>Captain
                    </h2>
                    <p class="text-sm opacity-80"><?= htmlspecialchars($team['captain'] ?? 'N/A'); ?></p>
                </div>
            </div>

            <div class="bg-base-100 p-4 rounded-xl shadow-sm flex items-center gap-4 col-span-full sm:col-span-1">
                <img src="uploads/users/<?= $team['vice_captain_image'] ?? 'default.png' ?>"
                    class="w-12 h-12 object-cover rounded-full border-2 border-secondary">
                <div>
                    <h2 class="text-sm font-semibold">
                        <i class="fa-solid fa-chess-knight text-info mr-1"></i>Vice Captain
                    </h2>
                    <p class="text-sm opacity-80"><?= htmlspecialchars($team['vice_captain'] ?? 'N/A'); ?></p>
                </div>
            </div>

            <div class="bg-base-100 p-4 rounded-xl shadow-sm col-span-full">
                <h2 class="text-sm font-semibold mb-1">
                    <i class="fa-solid fa-align-left text-primary mr-1"></i>Description
                </h2>
                <p class="text-sm opacity-80"><?= htmlspecialchars($team['disc']); ?></p>
            </div>

            <div class="bg-base-100 p-4 rounded-xl shadow-sm flex items-center gap-4 col-span-full">
                <img src="uploads/users/<?= $team['created_by_image'] ?? 'default.png' ?>"
                    class="w-12 h-12 object-cover rounded-full border-2 border-accent">
                <div>
                    <h2 class="text-sm font-semibold">Admin</h2>
                    <p class="text-sm opacity-80"><?= htmlspecialchars($team['created_by']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Players -->
    <div>
        <h2 class="text-lg font-bold mb-4 text-primary">
            <i class="fa-solid fa-users mr-2 text-secondary"></i>Players
        </h2>
        <div class="flex gap-4 overflow-x-auto pb-2 scroll-snap-x snap-x snap-mandatory">
            <?php foreach ($players as $p): ?>
                <div class="min-w-[180px] flex-shrink-0 bg-base-100 p-4 rounded-xl shadow-sm flex items-center gap-3 snap-start">
                    <a href="javascript:void(0)" data-type="view-profile" data-id="<?= $p['id'] ?>" class="open-modal">
                        <img src="uploads/users/<?= htmlspecialchars($p['image'] ?? 'default.png') ?>"
                            class="w-10 h-10 rounded-full object-cover border-2 border-primary">
                        <div>
                            <p class="font-medium"><?= htmlspecialchars($p['name']) ?></p>
                            <span class="text-xs text-base-content opacity-60">
                                <?= $p['role'] == 1 ? '👑 Admin' : ($p['role'] == 2 ? '🎮 Player' : '👤 User') ?>
                            </span>
                        </div>
                    </a>
                    <?php if ($_settings->userdata('id')): ?>
                        <div id="friend-request-area" class="mt-4">
                            <button class="btn btn-primary btn-sm add-friend" data-id="<?= $p['id'] ?>">
                                <i class="fa-solid fa-user-plus mr-1"></i>
                            </button>
                        </div>
                        <script>
                            document.querySelector('.add-friend').addEventListener('click', function () {
                                var friendid = this.getAttribute('data-id');

                                var xhr = new XMLHttpRequest();
                                xhr.open('POST', './classes/Master.php?f=send_friend_request', true);
                                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                                xhr.onreadystatechange = function () {
                                    if (xhr.readyState === 4 && xhr.status === 200) {
                                        var response = JSON.parse(xhr.responseText);
                                        if (response.status === 'success') {
                                            document.getElementById('friend-request-area').innerHTML =
                                                '<div class="text-sm text-success mt-2"><i class="fa-solid fa-check mr-1"></i>' + response.message + '</div>';
                                        } else {
                                            alert(response.message || 'Failed to send friend request.');
                                        }
                                    }
                                };
                                xhr.send('sender_id=<?= $_settings->userdata('id') ?>&receiver_id=' + friendid);
                            });
                        </script>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Matches -->
    <div class="w-full">
        <h2 class="text-lg font-bold mb-4 text-primary">
            <i class="fa-solid fa-trophy text-warning mr-2"></i>Recent Matches
        </h2>
        <div class="flex gap-4 overflow-x-auto pb-2 scroll-snap-x snap-x snap-mandatory w-full">
            <?php foreach ($matches as $match): ?>
                <?php
                $team1_score = $match['team1_score'];
                $team2_score = $match['team2_score'];
                $is_draw = $team1_score !== null && $team1_score === $team2_score;
                $team1_win = $team1_score !== null && $team1_score > $team2_score;
                $team2_win = $team1_score !== null && $team1_score < $team2_score;
                ?>
                <div class="min-w-[300px] flex-shrink-0 bg-base-100/80 backdrop-blur-md p-5 rounded-2xl shadow-lg border border-base-300 snap-start hover:scale-105 transition-transform duration-300">

                    <!-- Team Logos and Names -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <span class="text-sm <?= $team1_win ? 'text-success' : ($team2_win ? 'text-error' : ($is_draw ? 'text-warning' : '')) ?>">
                                <?= $team1_win ? '🏆' : '' ?>

                                <?= htmlspecialchars($match['team1_name']) ?>
                            </span>
                        </div>
                        <span class="text-sm text-gray-500 font-semibold">VS</span>
                        <div class="flex items-center gap-2">
                            <span class="text-sm <?= $team2_win ? 'text-success' : ($team1_win ? 'text-error' : ($is_draw ? 'text-warning' : '')) ?>">
                                <?= htmlspecialchars($match['team2_name']) ?>
                                <?= $team2_win ? '🏆' : '' ?>
                            </span>
                        </div>
                    </div>

                    <!-- Match Info -->
                    <div class="text-sm space-y-1 text-base-content/80">
                        <div><i class="fa-solid fa-calendar-days text-info mr-1"></i><?= date("d M Y", strtotime($match['date'])) ?></div>
                        <div><i class="fa-solid fa-clock text-secondary mr-1"></i><?= date("h:i A", strtotime($match['time'])) ?></div>
                        <div><i class="fa-solid fa-location-dot text-error mr-1"></i><?= htmlspecialchars($match['venue']) ?></div>
                    </div>

                    <!-- Score -->
                    <?php if ($team1_score !== null && $team2_score !== null): ?>
                        <div class="mt-3 flex items-center justify-center text-lg font-extrabold text-base-content/90 bg-base-200 px-4 py-2 rounded-xl shadow-inner">
                            <?= $team1_score ?> <span class="mx-2 text-gray-400">:</span> <?= $team2_score ?>
                        </div>
                        <?php if ($is_draw): ?>
                            <p class="text-xs text-warning text-center mt-1 font-medium">Match Draw</p>
                        <?php elseif ($team1_win): ?>
                            <p class="text-xs text-success text-center mt-1 font-medium"><?= htmlspecialchars($match['team1_name']) ?> Won</p>
                        <?php else: ?>
                            <p class="text-xs text-success text-center mt-1 font-medium"><?= htmlspecialchars($match['team2_name']) ?> Won</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>


    </div>
</div>