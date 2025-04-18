<?php
$user_id = $_settings->userdata('id');
$fav_team = $_settings->userdata('fav_team');


// Fetch team info
$stmt = $conn->prepare("SELECT name, logo FROM teams WHERE id = ?");
$stmt->bind_param("i", $fav_team);
$stmt->execute();
$stmt->bind_result($team_name, $team_logo);
$stmt->fetch();
$stmt->close();

$team_name = htmlspecialchars($team_name);
$team_logo = htmlspecialchars($team_logo);

// Fetch recent chat users
$team_players = [];
if ($fav_team) {
    $stmt = $conn->prepare("
            SELECT u.id, u.name, u.image, u.role,
                (SELECT message FROM messages WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) AS last_message,
                (SELECT created_at FROM messages WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) AS last_message_time
            FROM users u
            JOIN team_requests tr ON u.id = tr.user_id
            WHERE tr.team_id = ? AND u.id != ?
            ORDER BY last_message_time DESC
        ");
    $stmt->bind_param("iiiiii", $user_id, $user_id, $user_id, $user_id, $fav_team, $user_id);
    $stmt->execute();
    $team_players = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$status_users = [];
$stmt = $conn->prepare("
        SELECT s.id, s.user_id, u.name, u.image AS user_image, s.media, s.media_type, s.caption, s.created_at
        FROM statuses s
        INNER JOIN (
            SELECT user_id, MAX(created_at) AS latest
            FROM statuses
            GROUP BY user_id
        ) latest_status 
            ON s.user_id = latest_status.user_id AND s.created_at = latest_status.latest
        JOIN users u ON u.id = s.user_id
        JOIN team_requests tr ON u.id = tr.user_id
        WHERE tr.team_id = ? AND u.id != ?
        ORDER BY s.created_at DESC
    ");
$stmt->bind_param("ii", $fav_team, $user_id);
$stmt->execute();
$status_users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch unseen and seen statuses
$others_status = [];
$seen_status = [];
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
        $seen_status[] = $row;
    } else {
        $others_status[] = $row;
    }
}
$stmt->close();

// Fetch my status
$my_status = null;
$stmt = $conn->prepare("
    SELECT s.id, s.media, s.media_type, s.caption, s.created_at
    FROM statuses s
    WHERE s.user_id = ?
    ORDER BY s.created_at DESC
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $my_status = $row;
}
$stmt->close();

$my_status = $my_status ?? null;

// Fetch friend requests count
$stmt = $conn->prepare("
    SELECT COUNT(*) AS count FROM friend_requests WHERE receiver_id = ? AND status = 0
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($friend_requests);
$stmt->fetch();
$stmt->close();
?>

<div class="flex flex-col h-[calc(100vh-5rem)] overflow-hidden">
    <div class="flex-1 overflow-y-auto px-2 sm:px-6 space-y-6 pb-10" id="chatContentArea">

        <!-- Sticky Header -->
        <div class="sticky top-0 bg-base-300 z-[999] py-4 mb-4 rounded-b-xl px-2 sm:px-6">
            <div class="flex items-center justify-between">
                <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight">Chats</h1>
                <div class="flex items-center space-x-3">
                    <button id="friendrequest" onclick="showModal('friendRequestModal')" class="relative flex items-center gap-2 bg-white text-gray-700 border border-gray-300 hover:border-primary hover:text-primary font-medium text-sm px-4 py-1.5 rounded-full shadow-sm ease-in-out hover:shadow-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 stroke-current" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9l-6 6-6-6" />
                        </svg>
                        <i class="fas fa-paper-plane"></i>
                        <?php if ($friend_requests > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center shadow">
                                <?= $friend_requests ?>
                            </span>
                        <?php endif; ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="mb-6">
            <input type="text" id="searchInput" placeholder="Search users..." class="input w-full rounded-xl shadow-sm" oninput="filterUsers()">
        </div>

        <!-- Status (Moments) -->
        <div class="mb-4 status-row">
            <h3 class="text-3xl pl-2 font-extrabold text-gray-900 tracking-tight">Moments</h3>
            <div class="flex overflow-x-auto bg-base-200 space-x-2 py-2 no-scrollbar">
                <!-- My Status -->
                <div class="user-icon flex flex-col items-center min-w-[80px] group relative">
                    <div class="w-16 h-16 rounded-full overflow-hidden shadow-lg relative transition-transform group-hover:scale-105 <?= $my_status ? 'border-3 border-green-500 cursor-pointer' : 'border-3 border-gray-300' ?>"
                        <?php if ($my_status): ?> onclick="viewMomentsModal(<?= $user_id ?>)" <?php endif; ?>>
                        <?php if ($my_status): ?>
                            <?php if ($my_status['media_type'] === 'video'): ?>
                                <video src="./uploads/status/<?= htmlspecialchars($my_status['media']); ?>"
                                    poster="./uploads/users/<?= htmlspecialchars($_settings->userdata('image')); ?>"
                                    class="w-full h-full object-cover">
                                </video>
                            <?php else: ?>
                                <img src="./uploads/status/<?= htmlspecialchars($my_status['media']); ?>" class="w-full h-full object-cover" />
                            <?php endif; ?>
                        <?php else: ?>
                            <img src="./uploads/users/<?= htmlspecialchars($_settings->userdata('image')); ?>" class="w-full h-full object-cover opacity-75" />
                        <?php endif; ?>
                    </div>
                    <span class="text-xs text-center mt-2 font-medium text-gray-700 group-hover:text-secondary">
                        <?= $my_status ? 'You' : 'Add' ?>
                    </span>
                    <?php if ($my_status): ?>
                        <button type="button" id="deletestatus" class="absolute bottom-6 right-2 bg-green-500 backdrop-blur-lg px-1 py-0 rounded-full z-20 text-red-600"
                            onclick="deleteMyStatus(<?= $my_status['id']; ?>)">
                            <i class="fas fa-trash-can text-sm"></i>
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Friends' Unseen Status -->
                <?php foreach ($others_status as $user): ?>
                    <div class="user-icon flex flex-col items-center min-w-[80px] cursor-pointer group"
                        onclick="markStatusSeen(<?= $user['user_id']; ?>); viewMomentsModal(<?= $user['user_id']; ?>)">
                        <div class="w-16 h-16 rounded-full border-3 border-secondary overflow-hidden shadow-lg relative group-hover:scale-105 transition-transform">
                            <?php if ($user['media_type'] === 'video'): ?>
                                <video src="./uploads/status/<?= htmlspecialchars($user['media']); ?>" class="w-full h-full object-cover"></video>
                            <?php else: ?>
                                <img src="./uploads/status/<?= htmlspecialchars($user['media']); ?>" class="w-full h-full object-cover" />
                            <?php endif; ?>
                        </div>
                        <span class="user-name text-xs text-center mt-2 font-medium text-gray-700 group-hover:text-secondary"><?= htmlspecialchars($user['name']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Chatting Section -->
        <h3 class="text-3xl font-extrabold text-gray-900 tracking-tight">Chatting</h3>

        <!-- Chat List Container -->
        <div class="space-y-3" id="playersContainer">

            <?php
            $can_access_team_chat = $_settings->userdata('role') !== 3;
            $team_chat_link = $can_access_team_chat ? "./?p=chatting&team_hash=" . md5($fav_team) : "javascript:void(0)";
            $onclick_attr = $can_access_team_chat ? '' : "onclick=\"alert_toast('User cannot access','warning')\"";
            $opacity_class = $can_access_team_chat ? '' : 'opacity-85 cursor-not-allowed';
            ?>

            <a href="<?= $team_chat_link; ?>" <?= $onclick_attr; ?>
                class="team-chat flex items-center justify-between p-4 bg-base-100 rounded-xl shadow-md hover:shadow-lg transition cursor-pointer <?= $opacity_class ?>">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-full border-2 border-green-500 p-[2px] profile-border ">
                        <img src="./uploads/team_logos/<?= htmlspecialchars($team_logo); ?>"
                            class="w-full h-full rounded-full object-cover shadow"
                            alt="Team Logo">
                    </div>
                    <div>
                        <p class="font-semibold text-base-content"><?= htmlspecialchars($team_name); ?></p>
                        <p class="text-sm text-base-content/50 w-64 truncate">Group chat with your team</p>
                    </div>
                </div>
            </a>



            <?php foreach ($team_players as $player):
                $player_id = $player['id']; // Player ID
            ?>
                <div class="user-card flex items-center justify-between p-4 bg-base-100 rounded-xl shadow hover:shadow-lg transition group cursor-pointer">
                    <a href="./?p=chatting&friend_hash=<?= md5($player_id); ?>" class="flex items-center w-full">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-full border-2 p-[2px] profile-border relative">
                                <img src="./uploads/users/<?= htmlspecialchars($player['image']); ?>"
                                    class="w-full h-full rounded-full object-cover shadow group-hover:shadow-lg" />
                                <span id="unread-<?= $player_id ?>" class="unread-badge hidden absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full px-1.5 py-0.5"
                                    data-player-id="<?= $player_id ?>">
                                </span>
                            </div>
                            <div>
                                <p class="user-name font-semibold text-gray-800 group-hover:text-primary flex items-center gap-2">
                                    <?= htmlspecialchars($player['name']); ?>
                                    <?php
                                    $role = $player['role'];
                                    if ($role == 1) {
                                        echo '<span class="badge badge-error text-white text-xs">Admin</span>';
                                    } elseif ($role == 2) {
                                        echo '<span class="badge badge-info text-white text-xs">Player</span>';
                                    } elseif ($role == 3) {
                                        echo '<span class="badge badge-warning text-white text-xs">User</span>';
                                    }
                                    ?>
                                </p>
                                <p class="text-sm text-gray-500 w-64 truncate"><?= htmlspecialchars($player['last_message'] ?? 'No messages yet'); ?></p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>

            <script>
                function updateUnreadCounts() {
                    document.querySelectorAll('.unread-badge').forEach(badge => {
                        const playerId = badge.dataset.playerId;

                        fetch(`get_unread_count.php?player_id=${playerId}`)
                            .then(res => res.json())
                            .then(data => {
                                const count = parseInt(data.count);
                                const el = document.getElementById(`unread-${playerId}`);

                                if (count > 0) {
                                    el.textContent = count;
                                    el.classList.remove('hidden');
                                } else {
                                    el.textContent = '';
                                    el.classList.add('hidden');
                                }
                            });
                    });
                }

                // Run every 5 seconds
                setInterval(updateUnreadCounts, 1000);
            </script>



            <script>
                // Assign random border colors to elements with the 'profile-border' class
                (function assignRandomBorderColors() {
                    const borderColors = [
                        'border-red-500',
                        'border-green-500',
                        'border-blue-500',
                        'border-yellow-500',
                        'border-pink-500',
                        'border-purple-500',
                        'border-teal-500',
                        'border-orange-500'
                    ];
                    document.querySelectorAll('.profile-border').forEach(el => {
                        const randomColor = borderColors[Math.floor(Math.random() * borderColors.length)];
                        el.classList.add(randomColor);
                    });
                })();
            </script>

        </div>
    </div>


<!-- Friend Request Modal -->
<div id="friendRequestModal" class="fixed inset-0 z-[9999] flex items-end justify-center bg-base-100 bg-opacity-60 backdrop-blur-sm hidden">
    <div class="relative w-full max-w-md bg-base-200 rounded-t-3xl shadow-xl border border-base-300 px-6 pt-6 pb-4 max-h-[90vh] overflow-y-auto">
        
        <!-- Close Button -->
        <button class="absolute top-3 right-3 text-base-content hover:text-error transition" onclick="closeModal('friendRequestModal')">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <!-- Header -->
        <h3 class="text-2xl font-bold text-base-content mb-4 text-center">Friend Requests</h3>

        <!-- Friend Request List -->
        <div id="friendRequestList" class="space-y-4">
            <!-- Dynamic content -->
        </div>
        
    </div>
</div>


    <!-- Friend Menu -->
    <div id="friendMenu" class="hidden absolute bg-white shadow-lg rounded-lg p-4 z-[9999]">
        <ul class="menu bg-base-100 rounded-box">
            <li><a href="#" onclick="viewProfile(friendId)">View Profile</a></li>
            <li><a href="#" onclick="muteNotifications(friendId)">Mute Notifications</a></li>
            <li><a href="#" onclick="blockUser(friendId)">Block</a></li>
        </ul>
    </div>
    <!-- Friend Menu Modal -->
    <div id="friendMenuModal" class="modal hidden fixed top-0 left-0 w-full h-full bg-black bg-opacity-50 flex items-center justify-center z-[9999]">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Friend Options</h3>
            <ul class="menu bg-base-100 rounded-box">
                <li><a href="#" onclick="viewProfile(selectedFriendId)">View Profile</a></li>
                <li><a href="#" onclick="muteNotifications(selectedFriendId)">Mute Notifications</a></li>
                <li><a href="#" onclick="blockUser(selectedFriendId)">Block</a></li>
            </ul>
            <div class="modal-action">
                <button class="btn" onclick="closeModal('friendMenuModal')">Close</button>
            </div>
        </div>
    </div>
    <!-- Add User Moments Modal -->
    <div id="addMomentModal" class="fixed inset-0 z-[9999] hidden bg-black bg-opacity-50 flex items-end justify-center">

        <div class="w-full h-full bg-white dark:bg-base-200 rounded-t-2xl shadow-xl relative animate-slideUp overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-base-300 z-20 relative bg-white dark:bg-base-200">
                <h3 class="text-lg font-bold text-base-content">Moments</h3>
                <button type="button" onclick="closeModal('addMomentModal')" class="btn btn-sm btn-circle bg-white/20 hover:bg-white/30 backdrop-blur-md transition-all duration-300 shadow-md">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="addMomentForm" class="w-full h-full relative" enctype="multipart/form-data">


                <!-- Hidden File Input -->
                <input type="file" name="media" id="mediaInput" accept="image/*,video/*" class="hidden" required>

                <!-- Full Preview Area -->
                <div id="mediaPreview" class="w-full h-full relative cursor-pointer">
                    <!-- "+" icon before media chosen -->
                    <div id="plusIcon" class="absolute inset-0 flex items-center justify-center text-6xl text-primary bg-base-300 bg-opacity-25 z-10">+</div>
                </div>

                <!-- Caption + Post Button Area (Overlay Bottom Row) -->
                <div id="bottomControls" class="absolute bottom-16 left-0 w-full px-4 flex items-center justify-between gap-2 hidden z-20">
                    <textarea name="caption" class="flex-1 text-white placeholder-white  rounded-lg p-3 resize-none" rows="1" placeholder="Write a caption..."></textarea>
                    <button type="submit" class="btn btn-primary ml-2 whitespace-nowrap">Post</button>
                </div>
            </form>
        </div>
    </div>

    <div id="statusViewerModal" class="hidden fixed inset-0 bg-black bg-opacity-80 z-[9999] flex items-center justify-center">
        <div class="absolute top-0 w-full  p-5  rounded-b-xl flex items-center justify-between z-[9999]">
            <h2 class="text-white font-bold text-2xl tracking-wide">Moments</h2>
            <button onclick="closeStatusViewer()" class="btn btn-sm bg-transparent border-2 border-white text-white hover:bg-white hover:text-gray-800 rounded-full px-5 py-3 transition duration-300 ease-in-out transform hover:scale-110">
                Close
            </button>
        </div>
        <!-- Main Content -->
        <div class="relative bg-base-100 rounded-box shadow-2xl w-full max-w-xl overflow-hidden">
            <!-- Media Container -->
            <div id="statusMediaContainer" class="w-full h-full flex items-center justify-center bg-base-200 rounded-xl overflow-hidden shadow-2xl object-cover">
                <!-- Status content goes here -->
            </div>

        </div>
        <div class="absolute bottom-0 right-5 z-[9999]">
            <label class="swap bottom-6">
                <input type="checkbox" />
                <div class="swap-off"><i class="fas fa-heart text-gray-400 text-lg p-2 bg-base-100 rounded-full"></i></div>
                <div class="swap-on"><i class="fas fa-heart text-red-500 text-lg p-2 bg-pink-100 rounded-full"></i></div>
            </label>
        </div>
        <h2 id="statusCaption" class="text-white text-lg font-medium  absolute bottom-0 left-1/2 transform -translate-x-1/2 px-4 py-8 bg-opacity-40  shadow-md w-full text-center">
            Your Status Caption
        </h2>
    </div>




    <script>
        function updateChatData() {
            fetch('./chat_data.php')
                .then(res => res.json())
                .then(data => {
                    // Update friend request count
                    const reqBtn = document.getElementById('friendrequest');
                    reqBtn.querySelector('span')?.remove();
                    if (data.friend_requests > 0) {
                        const badge = document.createElement('span');
                        badge.className = 'absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center shadow';
                        badge.textContent = data.friend_requests;
                        reqBtn.appendChild(badge);
                    }

                    // Update unseen & seen statuses
                    const statusContainer = document.querySelector('.status-row .flex.overflow-x-auto');
                    if (!statusContainer) return;

                    // Remove all except my status (first child)
                    while (statusContainer.children.length > 1) {
                        statusContainer.removeChild(statusContainer.lastChild);
                    }

                    const createStatusElement = (user, seen = false) => {
                        const div = document.createElement('div');
                        div.className = `user-icon flex flex-col items-center min-w-[80px] cursor-pointer group ${seen ? 'opacity-60' : ''}`;
                        div.setAttribute('onclick', `viewMomentsModal(${user.user_id})`);

                        const media = user.media_type === 'video' ?
                            `<video src="./uploads/status/${user.media}" muted autoplay loop class="w-full h-full object-cover"></video>` :
                            `<img src="./uploads/status/${user.media}" class="w-full h-full object-cover" />`;

                        const border = seen ? 'border-gray-300' : 'border-secondary';

                        div.innerHTML = `
                    <div class="w-16 h-16 rounded-full border-4 ${border} overflow-hidden shadow-lg relative group-hover:scale-105 transition-transform">
                        ${media}
                    </div>
                    <span class="user-name text-xs text-center mt-2 font-medium text-gray-700 group-hover:text-secondary">${user.name}</span>
                `;
                        return div;
                    };

                    data.others_status.forEach(user => {
                        statusContainer.appendChild(createStatusElement(user, false));
                    });
                    data.seen_status.forEach(user => {
                        statusContainer.appendChild(createStatusElement(user, true));
                    });

                    // Update chat list
                    const playersContainer = document.getElementById('playersContainer');
                    const chatList = playersContainer.querySelector('.flex.flex-col');
                    chatList.innerHTML = '';
                    data.team_players.forEach(player => {
                        const item = document.createElement('div');
                        item.className = "user-card flex items-center justify-between p-4 bg-base-100 rounded-xl shadow hover:shadow-lg transition group cursor-pointer";
                        item.innerHTML = `
                    <a href="./?p=chatting&friend_hash=${md5(player.id)}" class="flex items-center w-full">
                        <div class="flex items-center space-x-4">
                            <div class="relative w-12 h-12">
                                <div class="absolute inset-0 rounded-full border-2 border-green-500 "></div>
                                <img src="./uploads/users/${player.image}" class="w-12 h-12 rounded-full object-cover shadow group-hover:shadow-lg relative z-10">
                            </div>
                            <div>
                                <p class="user-name font-semibold text-gray-800 group-hover:text-primary">${player.name}</p>
                                <p class="text-sm text-gray-500 w-64 truncate">${player.last_message ?? 'No messages yet'}</p>
                            </div>
                        </div>
                        
                    </a>`;
                        chatList.appendChild(item);
                    });
                });
        }

        // Call it every 10 seconds
        setInterval(updateChatData, 1000);
    </script>


    <script>
        $(document).ready(function() {
            $('#deletestatus').on('click', function() {
                conf("You want to delete your Moments?", function() {
                    $.ajax({
                        url: "classes/Master.php?f=delete_status",
                        method: "POST",
                        data: {
                            status_id: <?= $my_status ? $my_status['id'] : 'null' ?>, // Ensure the correct variable
                            user_id: <?= $_settings->userdata('id') ?> // Ensure $user_id is set
                        },
                        dataType: "json",
                        beforeSend: function() {
                            start_loader();
                        },
                        success: function(response) {
                            if (response.status === 'success') {
                                alert_toast("Moments deleted", 'success');
                                setTimeout(function() {
                                    location.reload();
                                }, 2000);
                            } else {
                                alert_toast(response.message, 'error');
                            }
                        },
                        error: function(xhr) {
                            console.log(xhr.responseText);
                            alert_toast("An error occurred", 'error');
                        }
                    });
                });
            });
        });
    </script>
    <!-- JavaScript -->
    <script>
        const mediaInput = document.getElementById('mediaInput');
        const mediaPreview = document.getElementById('mediaPreview');
        const plusIcon = document.getElementById('plusIcon');
        const bottomControls = document.getElementById('bottomControls');

        // File selection trigger on preview click (if not already loaded)
        mediaPreview.addEventListener('click', () => {
            const hasMedia = mediaPreview.querySelector('img, video');
            if (!hasMedia) {
                mediaInput.click();
            }
        });

        mediaInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (!file) return;

            const url = URL.createObjectURL(file);
            const fileType = file.type;

            plusIcon.classList.add('hidden');
            mediaPreview.innerHTML = '';

            if (fileType.startsWith('image/')) {
                mediaPreview.innerHTML = `<img src="${url}" class="w-full h-full object-cover rounded-lg" />`;
            } else if (fileType.startsWith('video/')) {
                mediaPreview.innerHTML = `
                <div class="relative w-full h-full">
                    <video 
                        id="previewVideo"
                        src="${url}" 
                        class="w-full h-full object-cover rounded-lg" loop
                        playsinline>
                    </video>
                    <div id="tapToPlayOverlay" class="absolute inset-0 flex items-center justify-center bg-black/50 text-white text-lg font-bold cursor-pointer z-30">
                        Tap to Play
                    </div>
                </div>`;

                const overlay = document.getElementById('tapToPlayOverlay');
                overlay.addEventListener('click', () => {
                    const video = document.getElementById('previewVideo');
                    video.muted = false;
                    video.volume = 1.0;
                    video.play().then(() => {
                        overlay.remove();
                    }).catch(err => {
                        console.warn("Play failed:", err);
                    });
                });
            }

            bottomControls.classList.remove('hidden');
        });

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.add('hidden');
                document.getElementById('addMomentForm').reset();
                mediaPreview.innerHTML = `
                <div id="plusIcon" class="absolute inset-0 flex items-center justify-center text-6xl text-primary bg-base-300 bg-opacity-25 z-10">+</div>`;
                bottomControls.classList.add('hidden');
            }
        }
    </script>


    <script>
        function startChat(userId) {
            window.location.href = `chat.php?friend_id=${userId}`;
        }

        function filterUsers() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const userCards = document.querySelectorAll('#playersContainer .user-card');
            const userIcons = document.querySelectorAll('.user-icon');
            const teamChat = document.querySelector('.team-chat');
            const statusRow = document.querySelector('.status-row');

            let matchFound = false;

            userCards.forEach(card => {
                const name = card.querySelector('.user-name')?.innerText.toLowerCase();
                if (name && name.includes(input)) {
                    card.style.display = 'flex';
                    matchFound = true;
                } else {
                    card.style.display = 'none';
                }
            });

            userIcons.forEach(icon => {
                const name = icon.querySelector('.user-name')?.innerText.toLowerCase();
                if (name && name.includes(input)) {
                    icon.style.display = 'flex';
                    matchFound = true;
                } else {
                    icon.style.display = 'none';
                }
            });

            // Hide Team Chat and status if searching
            if (input) {
                if (teamChat) teamChat.style.display = 'none';
                if (statusRow) statusRow.style.display = 'none';
            } else {
                if (teamChat) teamChat.style.display = 'flex';
                if (statusRow) statusRow.style.display = 'block';
            }
        }
    </script>

    <script>
        function loadFriendRequests() {
            const userId = <?= json_encode($user_id); ?>;
            $.ajax({
                url: 'classes/Master.php?f=fetch_friend_requests',
                method: 'GET',
                data: {
                    user_id: userId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        const friendRequestList = document.getElementById('friendRequestList');
                        friendRequestList.innerHTML = '';

                        if (response.friend_requests.length > 0) {
                            response.friend_requests.forEach(request => {
                                const requestItem = `
                                <div class="flex items-center justify-between p-2 border-b">
                                    <div class="flex items-center space-x-3">
                                        <img src="./uploads/users/${request.image}" class="w-10 h-10 rounded-full object-cover">
                                        <span class="font-medium">${request.name}</span>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button class="btn btn-success btn-sm" onclick="respondToRequest(${request.id}, 'accepted')">Accept</button>
                                        <button class="btn btn-error btn-sm" onclick="respondToRequest(${request.id}, 'rejected')">Reject</button>
                                    </div>
                                </div>
                            `;
                                friendRequestList.innerHTML += requestItem;
                            });
                        } else {
                            friendRequestList.innerHTML = '<p class="text-center text-gray-500">No friend requests</p>';
                        }
                    } else {
                        alert(response.message || 'Failed to fetch friend requests');
                    }
                },
                error: function() {
                    alert('An error occurred while fetching friend requests');
                }
            });
        }

        function respondToRequest(requestId, status) {
            $.ajax({
                url: 'classes/Master.php?f=respond_to_friend_request',
                method: 'POST',
                data: {
                    request_id: requestId,
                    status: status
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        alert(response.message);
                        loadFriendRequests(); // Refresh the friend requests list
                    } else {
                        alert(response.message || 'Failed to respond to friend request');
                    }
                },
                error: function() {
                    alert('An error occurred while responding to the friend request');
                }
            });
        }

        // Load friend requests when the modal is opened
        document.getElementById('friendrequest').addEventListener('click', loadFriendRequests);
    </script>

    <script>
        // Handle Add Moment Form Submission
        $("#addMomentForm").submit(function(e) {
            e.preventDefault();
            let formData = new FormData(this);

            $.ajax({
                url: "classes/Master.php?f=add_moment",
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                dataType: "json",
                beforeSend: function() {
                    closeModal('addMomentModal');
                    start_loader();
                },
                success: function(resp) {
                    if (resp.status === "success") {
                        alert_toast("Moment added successfully", "success");
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        alert_toast(resp.message || "Failed to add moment", "error");
                    }
                    end_loader();
                },
                error: function() {
                    alert_toast("Invalid parameters provided. Please check your input.", "error");
                    end_loader();
                },
            });
        });
    </script>




    <script>
        let currentStatusId = null;
        let statusTimeout = null;

        function viewMomentsModal(user_id) {
            fetch(`classes/Master.php?f=get_status_by_user_id&user_id=${user_id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const container = document.getElementById('statusMediaContainer');
                        container.innerHTML = '';

                        const caption = document.getElementById('statusCaption');
                        caption.textContent = data.data.caption || '';

                        let element;
                        if (data.data.media_type === 'video') {
                            element = document.createElement('video');
                            element.src = `./uploads/status/${data.data.media}`;
                            element.autoplay = true;
                            element.muted = false; // Autoplay requires muted to be true
                            element.playsInline = true;
                            element.loop = false; // Make sure it doesn't loop
                            element.className = 'w-full h-full object-contain rounded-lg';
                            element.controls = false; // Enable video controls
                            element.style.position = 'relative';


                            // Fullscreen on click
                            element.addEventListener('click', () => {
                                if (element.requestFullscreen) {
                                    element.requestFullscreen();
                                } else if (element.webkitRequestFullscreen) { // Safari
                                    element.webkitRequestFullscreen();
                                } else if (element.msRequestFullscreen) { // IE
                                    element.msRequestFullscreen();
                                }
                            });

                            // Close the modal after the video ends
                            element.addEventListener('ended', () => {
                                closeStatusViewer();
                            });
                        } else {
                            element = document.createElement('img');
                            element.src = `./uploads/status/${data.data.media}`;
                            element.className = 'w-full h-full object-cover ';
                        }

                        container.appendChild(element);
                        document.getElementById('statusViewerModal').classList.remove('hidden');

                        // Delete option only if it's your status
                        if (data.data.is_mine) {
                            currentStatusId = data.data.id;
                            document.getElementById('deleteStatusBtn').classList.remove('hidden');
                        } else {
                            document.getElementById('deleteStatusBtn').classList.add('hidden');
                        }

                        // Mark status as seen if not mine
                        if (!data.data.is_mine && !data.data.is_seen) {
                            markStatusSeen(data.data.id);
                        }

                        // Auto close after 10 seconds if it's an image
                        if (data.data.media_type !== 'video') {
                            clearTimeout(statusTimeout);
                            statusTimeout = setTimeout(() => {
                                closeStatusViewer();
                            }, 10000); // 10 seconds
                        }

                    } else {
                        alert(data.message || 'Failed to fetch status');
                    }
                })
                .catch(err => {});
        }


        function closeStatusViewer() {
            const container = document.getElementById('statusMediaContainer');

            // Pause and remove video if exists
            const media = container.querySelector('video');
            if (media) {
                media.pause();
                media.src = ""; // Clear source to fully stop
            }

            // Clean up the container
            container.innerHTML = '';

            document.getElementById('statusViewerModal').classList.add('hidden');
            currentStatusId = null;
        }

        function markStatusSeen(status_id) {
            // Retrieve seen statuses from localStorage
            let seenStatuses = JSON.parse(localStorage.getItem('seenStatuses')) || [];

            // Check if the status is already marked as seen
            if (!seenStatuses.includes(status_id)) {
                seenStatuses.push(status_id);
                localStorage.setItem('seenStatuses', JSON.stringify(seenStatuses));

                // Update the UI dynamically
                updateStatusUI(status_id);
            }
        }

        function updateStatusUI(status_id) {
            const statusElement = document.querySelector(`.user-icon[data-status-id="${status_id}"]`);
            if (statusElement) {
                statusElement.classList.add('opacity-60'); // Mark as seen
                const borderElement = statusElement.querySelector('.border-secondary');
                if (borderElement) {
                    borderElement.classList.replace('border-secondary', 'border-gray-300');
                }
            }
        }

        // On page load, update the UI based on localStorage
        document.addEventListener('DOMContentLoaded', () => {
            const seenStatuses = JSON.parse(localStorage.getItem('seenStatuses')) || [];
            seenStatuses.forEach(updateStatusUI);
        });
    </script>