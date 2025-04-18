<?php 
    include 'config.php'; // your DB connection file

    $userId = $_GET['id'] ?? null;

    if (!$userId) {
        echo "<script>alert_toast('User not found','error')</script>";
        exit;
    }

    $stmt = $conn->prepare("SELECT u.*, t.name AS team_name FROM users u LEFT JOIN teams t ON u.fav_team = t.id WHERE u.id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        echo "User not found.";
        exit;
    }
    ?>



<div class="bg-gradient-to-br from-base-300 to-base-200 shadow-xl p-6 max-w-md w-full mx-auto text-center relative overflow-hidden">

<?php if ($userId != $_settings->userdata('id')): ?>
    <div id="friend-request-area" class="mt-4">
        <button id="send-friend-request" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-user-plus mr-1"></i>
        </button>
    </div>
<?php endif; ?>

<script>
document.getElementById('send-friend-request')?.addEventListener('click', function () {
    const button = this;

    const senderId = <?= $_settings->userdata('id') ?>;
    const toUserId = <?= $userId ?>;

    button.disabled = true;
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Sending...';

    fetch('./classes/Master.php?f=send_friend_request', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            sender_id: senderId,
            receiver_id: toUserId
        }).toString()
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            document.getElementById('friend-request-area').innerHTML =
                '<div class="text-sm text-success mt-2"><i class="fa-solid fa-check mr-1"></i>' + data.message + '</div>';
        } else {
            button.disabled = false;
            button.innerHTML = '<i class="fa-solid fa-user-plus mr-1"></i> Friend Request';
            alert(data.message || 'Failed to send friend request.');
        }
    })
    .catch(error => {
        console.error('AJAX Error:', error);
        button.disabled = false;
        button.innerHTML = '<i class="fa-solid fa-user-plus mr-1"></i> Friend Request';
        alert('Something went wrong! Please try again later.');
    });
});
</script>



    <div class="relative w-28 h-28 mx-auto mb-4">
        <img class="w-full h-full object-cover rounded-full border-4 border-primary shadow-lg" src="uploads/users/<?= htmlspecialchars($user['image']) ?>" alt="Profile Picture">
        <span class="absolute bottom-1 right-1 w-4 h-4 rounded-full border-2 border-base-100 shadow-md <?= $user['status'] ? 'bg-success' : 'bg-error' ?>"></span>
    </div>

    <h2 class="text-2xl font-bold text-base-content"><?= htmlspecialchars($user['name']) ?></h2>
    <p class="text-sm text-base-content opacity-70 mt-1"><i class="fa-solid fa-envelope mr-1"></i> <?= htmlspecialchars($user['email']) ?></p>
    <p class="text-sm text-base-content opacity-70"><i class="fa-solid fa-phone mr-1"></i> <?= htmlspecialchars($user['mobile']) ?></p>

    <div class="mt-4 flex justify-center gap-2 flex-wrap">
        <span class="px-3 py-1 text-xs font-medium rounded-full bg-primary/10 text-primary shadow-sm">
            <?= $user['role'] == 1 ? "<i class='fa-solid fa-crown mr-1'></i>Admin" : ($user['role'] == 2 ? "<i class='fa-solid fa-gamepad mr-1'></i>Player" : "<i class='fa-solid fa-user mr-1'></i>User") ?>
        </span>
        <span class="px-3 py-1 text-xs font-medium rounded-full <?= $user['status'] ? 'bg-success/10 text-success' : 'bg-error/10 text-error' ?> shadow-sm">
            <?= $user['status'] ? '<i class="fa-solid fa-check mr-1"></i>Active' : '<i class="fa-solid fa-ban mr-1"></i>Inactive' ?>
        </span>
    </div>

    <div class="mt-5">
        <div class="text-sm text-base-content font-medium">
            <i class="fa-solid fa-trophy mr-1 text-warning"></i> Favorite Team: <span class="font-semibold"><?= $user['team_name'] ?? 'None' ?></span>
        </div>
        <div class="text-xs text-base-content opacity-60 mt-1">
            <i class="fa-regular fa-clock mr-1"></i> Last Seen: <?= $user['last_activity'] ? date('d M Y, h:i A', strtotime($user['last_activity'])) : 'N/A' ?>
        </div>
    </div>
</div>
