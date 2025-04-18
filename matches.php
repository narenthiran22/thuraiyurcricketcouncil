<?php
$user_id = $_settings->userdata('id') ?? 0;
$team_id = $_settings->userdata('fav_team') ?? 0;


if ($team_id) {
    $stmt = $conn->prepare("SELECT id FROM team_requests WHERE user_id = ? AND team_id = ? AND status = 'approved'");
    $stmt->bind_param("ii", $user_id, $team_id);
    $stmt->execute();
    $stmt->store_result();
    $can_create_match = $stmt->num_rows > 0;
    $stmt->close();
}

// Fetch all teams
$teams = [];
$result = $conn->query("SELECT id, name FROM teams");
while ($row = $result->fetch_assoc()) {
    $teams[] = $row;
}

// Filter opponent teams (not current team and no match scheduled)
$opponent_teams = [];
if ($team_id) {
    foreach ($teams as $team) {
        if ($team['id'] != $team_id) {
            $stmt = $conn->prepare("SELECT id FROM match_requests
                                    WHERE (request_by = ? AND request_to = ?) 
                                       OR (request_by = ? AND request_to = ?)");
            $stmt->bind_param("iiii", $team_id, $team['id'], $team['id'], $team_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows == 0) {
                $opponent_teams[] = $team;
            }
            $stmt->close();
        }
    }
}

// Fetch scheduled matches for the user's favorite team
$scheduled_matches = [];
$upcoming_matches = [];
$completed_matches = [];

if ($team_id) {
    $stmt = $conn->prepare("
        SELECT m.*, t1.name AS team1_name, t2.name AS team2_name 
        FROM matches m
        JOIN teams t1 ON m.team1_id = t1.id
        JOIN teams t2 ON m.team2_id = t2.id
        WHERE m.team1_id = ? OR m.team2_id = ?
        ORDER BY m.date, m.time
    ");
    $stmt->bind_param("ii", $team_id, $team_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if (is_null($row['team1_score']) && is_null($row['team2_score'])) {
            $upcoming_matches[] = $row;
        } else {
            $completed_matches[] = $row;
        }
    }

    $stmt->close();
}

// Fetch match requests created by the user's favorite team
$match_requests = [];
if ($team_id) {
    $stmt = $conn->prepare("
        SELECT mr.*, t1.name AS request_by_name, t2.name AS request_to_name
        FROM match_requests mr
        JOIN teams t1 ON mr.request_by = t1.id
        JOIN teams t2 ON mr.request_to = t2.id
        WHERE mr.request_by = ?
        ORDER BY mr.match_date, mr.match_time
    ");
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $match_requests[] = $row;
    }
    $stmt->close();
}
?>

<div class="container bg-base-300">

    <!-- Sticky Header -->
    <div class="sticky top-0 bg-base-300 shadow-sm" style="z-index: 999;">
        <div class="p-3">
            <h1 class="text-lg font-bold text-center">Matches</h1>
        </div>
    </div>

    <!-- Scheduled Matches Section -->
    <div class="p-4 space-y-6 bg-base-100">
        <div>
            <h2 class="text-xl font-bold text-primary mb-3 flex items-center gap-2">
                <i class="fa-solid fa-calendar-days text-lg"></i> Scheduled Matches
            </h2>

            <?php if (!empty($upcoming_matches)): ?>
                <div class="flex overflow-x-auto snap-x snap-mandatory gap-4 pb-12 px-1">
                    <?php foreach ($upcoming_matches as $match): ?>
                        <div class="card min-w-[90%] sm:min-w-[350px] snap-start bg-base-200 shadow-xl rounded-2xl border-l-4 border-primary p-4 flex-shrink-0 space-y-3">
                            <div class="text-lg font-bold text-primary">
                                <?= htmlspecialchars($match['team1_name']) ?> <span class="text-base-content">vs</span> <?= htmlspecialchars($match['team2_name']) ?>
                            </div>
                            <div class="text-sm text-base-content/80 space-y-1">
                                <p><i class="fa-solid fa-calendar mr-2 text-accent"></i><?= date('d/m/Y', strtotime($match['date'])) ?></p>
                                <p><i class="fa-solid fa-clock mr-2 text-secondary"></i><?= date('h:i A', strtotime($match['time'])) ?></p>
                                <p><i class="fa-solid fa-location-dot mr-2 text-error"></i><?= htmlspecialchars($match['venue']) ?></p>
                            </div>

                            <div class="pt-2">
                                <button class="btn btn-sm btn-primary" onclick="document.getElementById('scoreModal1').showModal()">
                                    <i class="fa-solid fa-pen-to-square mr-1"></i> Update Score
                                </button>

                            </div>
                        </div>

                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-sm text-base-content/70 italic">No upcoming matches found for your team.</p>
            <?php endif; ?>
        </div>
        <script>
            function openModal(id) {
                const modal = document.getElementById(id);
                if (modal) modal.showModal();
            }
        </script>
        <!-- Modal -->
        <dialog id="scoreModal1" class="modal modal-bottom items-end">
            <div class="modal-box">
                <h3 class="font-bold text-lg mb-2">Update Score</h3>

                <!-- Live Message -->
                <div id="winnerMsg" class="text-sm font-semibold text-primary mb-4 animate-marquee"></div>

                <!-- Score Form -->
                <form id="scoreForm" class="space-y-4">
                    <input type="hidden" name="match_id" value="1">
                    <input type="hidden" id="team1_name" value="Team A">
                    <input type="hidden" id="team2_name" value="Team B">

                    <label class="form-control">
                        <span class="label-text">Your Team Score</span>
                        <input type="number" name="team1_score" id="team1_score" class="input input-bordered" required>
                    </label>

                    <label class="form-control">
                        <span class="label-text">Opponent Team Score</span>
                        <input type="number" name="team2_score" id="team2_score" class="input input-bordered" required>
                    </label>

                    <div class="modal-action">
                        <button type="submit" class="btn btn-success">Submit</button>
                        <button type="button" onclick="document.getElementById('scoreModal1').close()" class="btn">Close</button>
                    </div>
                </form>

            </div>
        </dialog>

        <script>
            // Listen to changes and show the winner message live
            const team1ScoreInput = document.getElementById('team1_score');
            const team2ScoreInput = document.getElementById('team2_score');
            const winnerMsg = document.getElementById('winnerMsg');

            function showWinnerMessage() {
                const t1 = parseInt(team1ScoreInput.value);
                const t2 = parseInt(team2ScoreInput.value);
                const team1 = document.getElementById('team1_name').value;
                const team2 = document.getElementById('team2_name').value;

                if (!isNaN(t1) && !isNaN(t2)) {
                    if (t1 > t2) {
                        winnerMsg.innerHTML = `<i class="fa-solid fa-trophy text-success mr-1 animate-bounce"></i> Well played! Congrats and keep smashing it! 🏆💥`;
                    } else if (t1 < t2) {
                        winnerMsg.innerHTML = `<i class="fa-solid fa-face-smile-wink text-warning mr-1"></i> Opponent took the win by ${t2 - t1} runs. Don’t worry — better luck next time! 💪`;
                    } else {
                        winnerMsg.innerHTML = `<i class="fa-solid fa-handshake text-warning mr-1"></i> Match Draw`;
                    }
                } else {
                    winnerMsg.innerHTML = "";
                }
            }

            team1ScoreInput.addEventListener("input", showWinnerMessage);
            team2ScoreInput.addEventListener("input", showWinnerMessage);
        </script>
        <script>
            document.getElementById("scoreForm").addEventListener("submit", function(e) {
                e.preventDefault();

                const match_id = this.match_id.value;
                const t1_score = parseInt(this.team1_score.value);
                const t2_score = parseInt(this.team2_score.value);
                const t1_name = document.getElementById('team1_name').value;
                const t2_name = document.getElementById('team2_name').value;
                const winnerMsg = document.getElementById('winnerMsg');

                // Update live message
                if (t1_score > t2_score) {
                    winnerMsg.innerHTML = `<i class="fa-solid fa-trophy text-success mr-1"></i> Congrats! ${t1_name} won by ${t1_score - t2_score} runs. Keep playing!`;
                } else if (t1_score < t2_score) {
                    winnerMsg.innerHTML = `<i class="fa-solid fa-face-smile-wink text-warning mr-1"></i> Better luck next time! ${t2_name} won by ${t2_score - t1_score} runs. Keep fighting!`;
                } else {
                    winnerMsg.innerHTML = `<i class="fa-solid fa-handshake-angle text-info mr-1"></i> It’s a draw! Well played both teams!`;
                }

                // AJAX request
                fetch('classes/Master.php?f=updatescore', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `match_id=${match_id}&team1_score=${t1_score}&team2_score=${t2_score}`
                    })
                    .then(res => res.text())
                    .then(data => {
                        if (data.trim() === "1") {
                            alert("Score updated successfully!");
                            document.getElementById('scoreModal1').close();
                            location.reload(); // Refresh the page to reflect updated scores
                        } else {
                            alert("Failed to update score. Please try again.");
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert("Server error. Please try again later.");
                    });
            });
        </script>
    </div>


    <!-- Create Match Button -->
    <?php if ($_settings->userdata('id') > 0 && $_settings->userdata('status') === 1 && $_settings->userdata('role') === 1): ?>
        <div class="text-right mx-6 mt-4 absolute bottom-20 right-0 z-[999]">
            <button onclick="showModal('createMatchModal')" class="btn btn-success btn-sm">
                <i class="fa-solid fa-plus text-2xl animate-wave"></i>
            </button>
        </div>
    <?php endif; ?>
    <!-- name of each tab group should be unique -->
    <div class="tabs tabs-lift justify-center pt-4 bg-base-200">
        <label class="tab">
            <input type="radio" name="my_tabs_4" />
            <i class="fa-solid fa-play size-4 me-2 text-success"></i>
            Live
        </label>
        <div class="tab-content bg-base-100 border-base-300 p-6">
            <div class="text-center space-y-6 max-w-md bg-base-100 p-8 rounded-2xl shadow-lg border border-dashed border-warning animate-pulse">
                <div class="text-warning text-6xl">⚠️</div>
                <h1 class="text-2xl font-bold">We’ll Be Updated Soon!</h1>
                <p class="text-base-content/70">
                    Our site is currently undergoing scheduled maintenance.
                    We appreciate your patience and will be back online shortly.
                </p>
                <div class="text-sm text-base-content/50 italic">
                    – ThuraiyurCricketCouncil
                </div>
            </div>
        </div>

        <label class="tab">
            <input type="radio" name="my_tabs_4" checked="checked" />
            <i class="fa-solid fa-circle-info size-4 me-2 text-info"></i>
            Requests
        </label>
        <div class="tab-content bg-base-100 border-base-300 p-6">
            <?php
            // Match Request Section
            if ($team_id):
                $stmt = $conn->prepare("SELECT mr.id, mr.match_date, mr.match_time, mr.venue, t1.name as team1
                            FROM match_requests mr
                            JOIN teams t1 ON mr.request_by = t1.id
                            WHERE mr.request_to = ? AND mr.status = 'pending'
                            ORDER BY mr.match_date, mr.match_time");
                $stmt->bind_param("i", $team_id);
                $stmt->execute();
                $requests = $stmt->get_result();
                $match_request_ids = [];
            ?>

                <?php if ($requests->num_rows > 0): ?>
                    <div class="card bg-base-100  ">
                        <div class="">
                            <h2 class="text-xl font-bold text-yellow-600 mb-4">Match Requests</h2>
                            <div class="overflow-x-auto">
                                <ul class="flex gap-4 snap-x snap-mandatory overflow-x-auto pb-4 px-1">
                                    <?php $match_request_ids = [];
                                    while ($req = $requests->fetch_assoc()): ?>
                                        <?php $match_request_ids[] = $req['id']; ?>

                                        <?php
                                        $matchTitle = $req['team1'] . ' Match Challenge';
                                        $venue = $req['venue'];
                                        $matchDate = $req['match_date'];
                                        $matchTime = $req['match_time'];
                                        $startTime = strtotime("$matchDate $matchTime");
                                        $endTime = strtotime("+1 hour", $startTime);

                                        $icsUrl = "generate_ics.php?title=" . urlencode($matchTitle) .
                                            "&location=" . urlencode($venue) .
                                            "&start=$startTime&end=$endTime";

                                        ?>
                                        <li class="snap-center shrink-0 w-[85vw] md:w-[400px] bg-base-100 rounded-2xl shadow-md border-l-4 border-warning p-2  space-y-4 relative">
                                            <div class="flex flex-col gap-4 relative">

                                                <!-- Header -->
                                                <div class="space-y-1 overflow-hidden">
                                                    <div class="animate-marquee">
                                                        <h3 class="text-lg font-semibold text-warning leading-snug capitalize">
                                                            <?= htmlspecialchars($req['team1']); ?> <span class="text-base-content font-medium">has challenged your team</span>
                                                        </h3>
                                                    </div>
                                                    <div class="text-sm text-base-content/70 space-y-1 pl-4 bg-base-200 py-2 rounded-xl">
                                                        <div>
                                                            <a href="<?= $icsUrl ?>" class="text-blue-500 hover:underline">
                                                                <i class="fa-solid fa-calendar text-primary mr-2"></i><?= date('d/m/Y', strtotime($req['match_date'])); ?>
                                                            </a>
                                                        </div>
                                                        <div>
                                                            <i class="fa-solid fa-clock text-secondary mr-2"></i><?= $req['match_time']; ?>
                                                        </div>
                                                        <div>
                                                            <i class="fa-solid fa-map-marker-alt text-error mr-2"></i>
                                                            <span class="font-semibold"><?= htmlspecialchars($req['venue']); ?></span>
                                                        </div>
                                                    </div>

                                                    <!-- Vote Bars -->
                                                    <div class="flex flex-col gap-2 text-sm font-medium bg-base-200 p-3 rounded-lg">
                                                        <div class="flex items-center gap-3">
                                                            <span class="text-success w-[50px]">Play</span>
                                                            <progress id="bar-accept-<?= $req['id'] ?>" class="progress progress-success bg-base-300 flex-1 h-3" value="0" max="100"></progress>
                                                            <span id="accept-count-<?= $req['id'] ?>" class="text-xs text-success font-bold w-6 text-right">0</span>
                                                        </div>
                                                        <div class="flex items-center gap-3">
                                                            <span class="text-error w-[50px]">NotPlay</span>
                                                            <progress id="bar-reject-<?= $req['id'] ?>" class="progress progress-error bg-base-300 flex-1 h-3" value="0" max="100"></progress>
                                                            <span id="reject-count-<?= $req['id'] ?>" class="text-xs text-error font-bold w-6 text-right">0</span>
                                                        </div>
                                                    </div>

                                                    <!-- Vote Buttons -->
                                                    <?php
                                                    $userId = $_settings->userdata('id') ?? 0; // Ensure $userId is defined
                                                    $hasVoted = $conn->query("SELECT 1 FROM match_votes WHERE request_id = {$req['id']} AND user_id = {$userId}")->num_rows > 0;
                                                    $req['has_voted'] = $hasVoted;
                                                    ?>
                                                    <?php if ($_settings->userdata('role') === 1 && !$req['has_voted']): ?>

                                                        <div id="vote-buttons-<?= $req['id'] ?>" class="flex gap-2 mt-1">
                                                            <button class="btn btn-success btn-sm flex-1" onclick="castVote(<?= $req['id'] ?>, 'accepted')">
                                                                Playing
                                                            </button>
                                                            <button class="btn btn-error btn-sm flex-1" onclick="castVote(<?= $req['id'] ?>, 'rejected')">
                                                                NotPlaying
                                                            </button>
                                                        </div>
                                                    <?php else: ?>
                                                        <p class="text-sm text-gray-500 italic mt-1">You have already voted.</p>
                                                    <?php endif; ?>

                                                    <!-- Admin Controls -->
                                                    <?php if ($_settings->userdata(key: 'role') === 1): ?>
                                                        <div class="flex gap-2">
                                                            <button class="btn btn-success btn-sm flex-1 overflow-hidden" onclick="respondToRequest(<?= $req['id']; ?>, 'accepted')">
                                                                <span class="animate-marquee">Match Accept</span>
                                                            </button>
                                                            <button class="btn btn-error btn-sm flex-1 overflow-hidden" onclick="respondToRequest(<?= $req['id']; ?>, 'rejected')">
                                                                <span class="animate-marquee">Match Reject</span>
                                                            </button>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- View Button (bottom-right corner) -->
                                                    <button onclick="openVotersModal(<?= $req['id']; ?>)" class="btn btn-sm btn-outline btn-info absolute top-10 right-4 rounded-xl shadow hover:scale-105 transition-transform">
                                                        <i class="fa-solid fa-eye"></i>votes
                                                    </button>
                                                </div>
                                        </li>

                                    <?php endwhile; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-base-content/70">No match requests found.</p>
                <?php endif; ?>
        </div>

        <label class="tab">
            <input type="radio" name="my_tabs_4" />
            <i class="fa-solid fa-heart size-4 me-2 text-red-500"></i>
            Team
        </label>
        <div class="tab-content bg-base-100 border-base-300 p-6">
            <div>
                <h2 class="text-xl font-bold text-warning mb-3">📨 My Team Requests</h2>

                <?php if (!empty($match_requests)): ?>
                    <div class="flex overflow-x-auto snap-x snap-mandatory gap-4 pb-2 px-1">
                        <?php foreach ($match_requests as $request): ?>
                            <?php
                            $status = strtolower($request['status']);
                            $textColor = 'text-yellow-500';
                            $borderColor = 'border-yellow-500';
                            $bgRing = 'ring-yellow-100';

                            if ($status === 'accepted') {
                                $textColor = 'text-green-600';
                                $borderColor = 'border-green-500';
                                $bgRing = 'ring-green-100';
                            } elseif ($status === 'rejected') {
                                $textColor = 'text-red-600';
                                $borderColor = 'border-red-500';
                                $bgRing = 'ring-red-100';
                            }
                            ?>
                            <div class="card bg-base-100 min-w-[90%] sm:min-w-[350px] snap-start shadow-md rounded-2xl p-4 flex-shrink-0 space-y-2 border-l-4 <?= $borderColor ?> ring-1 <?= $bgRing ?>" id="match-request-<?= $request['id'] ?>">

                                <!-- Match Heading -->
                                <div class="text-lg font-semibold <?= $textColor ?>">
                                    <?= htmlspecialchars($request['request_by_name']) ?> <span class="text-base-content">vs</span> <?= htmlspecialchars($request['request_to_name']) ?>
                                </div>

                                <!-- Match Details -->
                                <div class="text-sm text-base-content/80">
                                    <p><i class="fa-solid fa-calendar-day text-accent mr-2"></i> <?= htmlspecialchars($request['match_date']) ?></p>
                                    <p><i class="fa-solid fa-clock text-secondary mr-2"></i> <?= htmlspecialchars($request['match_time']) ?></p>
                                    <p><i class="fa-solid fa-location-dot text-error mr-2"></i> <?= htmlspecialchars($request['venue']) ?></p>
                                    <p><i class="fa-solid fa-info-circle text-primary mr-2"></i> Status: <span class="font-semibold capitalize <?= $textColor ?>"><?= $status ?></span></p>
                                </div>

                                <button class="btn btn-error btn-sm mt-2 w-full" onclick="deleteMatchRequest(<?= $request['id'] ?>)">
                                 Withdraw
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-base-content/70">No match requests created by you team.</p>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <div class="p-4 space-y-6 bg-base-200">
        <?php if (!empty($completed_matches)): ?>
            <div>
                <h2 class="text-lg font-semibold text-base-content mb-2 flex items-center gap-2">
                    <i class="fa-solid fa-flag-checkered text-success"></i> Completed Matches
                </h2>
                <div class="flex overflow-x-auto snap-x snap-mandatory gap-4 pb-12 px-1">
                    <?php foreach ($completed_matches as $match): ?>
                        <?php
                        $winner = "Match Draw";
                        if ($match['team1_score'] > $match['team2_score']) {
                            $diff = $match['team1_score'] - $match['team2_score'];
                            $winner = $match['team1_name'] . " won by {$diff} runs";
                        } elseif ($match['team1_score'] < $match['team2_score']) {
                            $diff = $match['team2_score'] - $match['team1_score'];
                            $winner = $match['team2_name'] . " won by {$diff} runs";
                        }
                        ?>
                        <div class="card min-w-[90%] sm:min-w-[350px] snap-start bg-base-200 shadow-xl rounded-2xl border-l-4 border-success p-4 flex-shrink-0 space-y-2">
                            <div class="text-lg font-bold text-success overflow-hidden">
                                <h2 class="animate-marquee"> <?= htmlspecialchars($match['team1_name']) ?> <span class="text-base-content">vs</span> <?= htmlspecialchars($match['team2_name']) ?>
                                </h2>
                            </div>
                            <div class="text-sm text-base-content/80 space-y-1">
                                <p><i class="fa-solid fa-calendar mr-2 text-accent"></i><?= date('d/m/Y', strtotime($match['date'])) ?></p>
                                <p><i class="fa-solid fa-clock mr-2 text-secondary"></i><?= date('h:i A', strtotime($match['time'])) ?></p>
                                <p><i class="fa-solid fa-location-dot mr-2 text-error"></i><?= htmlspecialchars($match['venue']) ?></p>
                                <p><i class="fa-solid fa-chart-line mr-2 text-primary"></i>Score: <?= $match['team1_score'] ?> - <?= $match['team2_score'] ?></p>
                                <p class="text-green-600 font-semibold">
                                    <i class="fa-solid <?= strpos($winner, 'Draw') !== false ? 'fa-handshake' : 'fa-trophy' ?> mr-2"></i><?= $winner ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>


    <script>
        function deleteMatchRequest(requestId) {
            conf("Are you sure you want to withdraw request?", function() {
                const formData = new FormData();
                formData.append('id', requestId);

                fetch('./classes/Master.php?f=delete_match_request', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            const el = document.getElementById(`match-request-${requestId}`);
                            el.remove();
                            alert_toast(data.message, 'success');
                        } else {
                            alert_toast(data.message || 'Something went wrong.', 'error');
                        }
                    })
                    .catch(error => {
                        alert_toast('An error occurred while deleting the match request.', 'warning');
                    });
            });

        }
    </script>
    <!-- Voters Modal -->
    <div id="votersmodal" class="fixed inset-0 flex items-end justify-center backdrop-blur-sm z-[9999] hidden transition-all duration-300">
        <div class="bg-white rounded-xl w-full max-w-md p-6 relative">
            <button onclick="closeModal('votersmodal')" class="absolute top-2 right-2 btn btn-sm btn-circle btn-ghost">✖</button>
            <h3 class="text-xl font-bold mb-4 text-yellow-700">Voters</h3>
            <div id="voters-modal-content" class="space-y-3 max-h-[400px] overflow-y-auto">
                <p class="text-gray-400 italic">Loading...</p>
            </div>
        </div>
    </div>

    <!-- Create Match Modal -->
    <div id="createMatchModal" class="fixed inset-0 flex items-end justify-center backdrop-blur-sm z-[9999] hidden">
        <div class="bg-white rounded-xl w-full max-w-md p-6 relative">
            <h2 class="text-xl font-bold mb-4">Create Match</h2>
            <form id="createMatchForm">
                <input type="hidden" name="team1_id" value="<?= $team_id ?>">

                <div class="mb-3">
                    <label class="block font-semibold mb-1">Opponent Team</label>
                    <select name="team2_id" class="select select-bordered w-full" required>
                        <option value="" disabled selected>Select Your Opponent</option>
                        <?php foreach ($opponent_teams as $team): ?>
                            <option value="<?= $team['id']; ?>"><?= htmlspecialchars($team['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="block font-semibold mb-1">Date</label>
                    <input type="date" name="date" class="input input-bordered w-full" required>
                </div>

                <div class="mb-3">
                    <label class="block font-semibold mb-1">Time</label>
                    <input type="time" name="time" class="input input-bordered w-full" required>
                </div>

                <div class="mb-4">
                    <label class="block font-semibold mb-1">Venue</label>
                    <input type="text" name="venue" class="input input-bordered w-full" required>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeModal('createMatchModal')" class="btn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
            <div id="createMatchMsg" class="mt-2 text-sm text-green-600 hidden"></div>
        </div>
    </div>
    <script>
        function respondToRequest(requestId, action) {
            $.ajax({
                type: 'POST',
                url: './classes/master.php?f=respond_match_request',
                data: {
                    id: requestId,
                    action: action
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        alert(response.message);
                        location.reload();
                    } else {
                        alert("Error: " + response.message);
                    }
                },
                error: function() {
                    alert("Something went wrong. Please try again.");
                }
            });
        }
    </script>
    <script>
        function toggleLike(button) {
            const requestId = button.getAttribute('data-id'); // Ensure requestId is treated as a string
            let likedRequests = JSON.parse(localStorage.getItem('likedRequests') || '[]');

            if (likedRequests.includes(requestId)) {
                // already liked, so remove
                likedRequests = likedRequests.filter(id => id !== requestId);
            } else {
                likedRequests.push(requestId);
            }

            localStorage.setItem('likedRequests', JSON.stringify(likedRequests));
            updateLikeButtons();
        }

        function updateLikeButtons() {
            let likedRequests = JSON.parse(localStorage.getItem('likedRequests') || '[]');
            document.querySelectorAll('.like-btn').forEach(btn => {
                const id = btn.getAttribute('data-id');
                if (likedRequests.includes(id)) {
                    btn.classList.remove('text-gray-400');
                    btn.classList.add('text-red-500', 'font-bold');
                    btn.innerHTML = '❤️';
                } else {
                    btn.classList.remove('text-red-500', 'font-bold');
                    btn.classList.add('text-gray-400');
                    btn.innerHTML = '🤍';
                }
            });
        }

        // on page load
        document.addEventListener('DOMContentLoaded', updateLikeButtons);
    </script>
    <script>
        function castVote(requestId, vote) {
            if (vote === 'rejected') {
                const reason = prompt("Please enter a reason for rejecting this match:");
                if (!reason) {
                    alert("You must provide a reason to reject the match.");
                    return;
                }
                sendVote(requestId, vote, reason);
            } else {
                sendVote(requestId, vote);
            }
        }

        function sendVote(requestId, vote, reason = null) {
            $.ajax({
                type: 'POST',
                url: './classes/master.php?f=cast_match_vote',
                data: {
                    request_id: requestId,
                    vote: vote,
                    reason: reason
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        updateVoteCount(requestId);
                        $(`#vote-buttons-${requestId}`).hide(); // Disable vote buttons after voting
                        openVotersModal(requestId); // Dynamically update voters modal
                    } else {
                        alert(response.message || "Failed to cast vote. Please try again.");
                    }
                },
                error: function() {
                    alert("An error occurred while processing your vote. Please try again.");
                }
            });
        }

        function updateVoteCount(requestId) {
            $.post('./classes/master.php?f=get_vote_status', {
                request_id: requestId
            }, function(res) {
                if (res.status === 'success') {
                    let accept = res.votes.accept;
                    let reject = res.votes.reject;
                    let total = accept + reject;
                    let percentAccept = total > 0 ? (accept / total) * 100 : 0;
                    let percentReject = total > 0 ? (reject / total) * 100 : 0;

                    $(`#accept-count-${requestId}`).text(accept);
                    $(`#reject-count-${requestId}`).text(reject);

                    // For <progress> bars
                    $(`#bar-accept-${requestId}`).val(percentAccept);
                    $(`#bar-reject-${requestId}`).val(percentReject);
                }
            }, 'json');
        }


        const requestIds = [<?= implode(',', $match_request_ids) ?>];
        setInterval(() => {
            requestIds.forEach(id => updateVoteCount(id));
        }, 1000);


        // Voter Modal Controls
        function openVotersModal(requestId) {
            $.post('./classes/master.php?f=get_voters', {
                request_id: requestId
            }, function(res) {
                let html = '';
                if (res.status === 'success' && res.voters.length > 0) {
                    res.voters.forEach(v => {
                        html += `
                                <div class="flex items-center gap-4 p-3 bg-white shadow-md rounded-xl border border-gray-100 hover:shadow-lg transition-all">
                                    <img src="./uploads/users/${v.avatar}" 
                                        class="w-10 h-10 rounded-full object-cover ring-2 ${v.vote === 'accepted' ? 'ring-green-500' : 'ring-red-500'}" 
                                        alt="User Avatar" />

                                    <div class="flex-1 flex items-center justify-between">
                                        <div>
                                            <p class="font-semibold text-gray-800">${v.name}</p>
                                            ${v.vote === 'rejected' && v.reason 
                                                ? `<p class="text-xs text-gray-500 italic mt-0.5">Reason: ${v.reason}</p>` 
                                                : ''
                                            }
                                        </div>
                                        <p class="text-sm font-semibold ${v.vote === 'accepted' ? 'text-green-600' : 'text-red-600'}">
                                            ${v.vote.toUpperCase()}
                                        </p>
                                    </div>

                                    <button class="btn btn-sm btn-ghost text-gray-400 like-btn" data-id="${v.id}" onclick="toggleLike(this)">
                                    🤍
                                    </button>
                                </div>
                            `;
                    });
                } else {
                    html = `<p class="text-gray-400 italic">No votes yet.</p>`;
                }
                $('#voters-modal-content').html(html);
                $('#votersmodal').removeClass('hidden').addClass('flex');
            }, 'json').fail(function() {
                alert("Failed to fetch voters. Please try again.");
            });
        }

        function closeModal(modalId) {
            $(`#${modalId}`).addClass('hidden').removeClass('flex');
        }
    </script>
    <script>
        $(document).ready(function() {
            $('#createMatchForm').submit(function(e) {
                e.preventDefault();
                const formData = $(this).serialize();

                $.ajax({
                    type: 'POST',
                    url: './classes/master.php?f=create_match_request',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#createMatchMsg').removeClass('hidden').text(response.message);
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            alert(response.message || 'Failed to create match request. Please try again.');
                        }
                    },
                    error: function() {
                        alert('An error occurred while creating the match request. Please try again.');
                    }
                });
            });
        });
    </script>
<?php
                $stmt->close();
            endif;
?>