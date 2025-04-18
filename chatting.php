<?php
// Fetch team hash from the URL
$team_hash = $_GET['team_hash'] ?? null;
$friend_hash = $_GET['friend_hash'] ?? null;

if ($team_hash) {
    // Team chat logic
} elseif ($friend_hash) {
    // Friend chat logic
} else {
    die("Invalid access.");
}


if ($team_hash) {
    $stmt = $conn->prepare("SELECT id, name, logo FROM teams WHERE MD5(id) = ?");
    $stmt->bind_param("s", $team_hash);
    $stmt->execute();
    $team = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$team) {
        die("Team not found.");
    }

    $stmt = $conn->prepare("
        SELECT tc.id, tc.sender_id, u.name AS sender_name, u.image AS sender_image, tc.message, tc.created_at
        FROM team_chats tc
        JOIN users u ON tc.sender_id = u.id
        WHERE tc.team_id = ?
        ORDER BY tc.created_at ASC
    ");
    $stmt->bind_param("i", $team['id']);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

if ($friend_hash) {
    $stmt = $conn->prepare("SELECT id, name, image FROM users WHERE MD5(id) = ?");
    $stmt->bind_param("s", $friend_hash);
    $stmt->execute();
    $friend = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$friend) {
        die("Friend not found.");
    }

    $user_id = $_settings->userdata('id');
    $stmt = $conn->prepare("
        SELECT fc.id, fc.sender_id, u.name AS sender_name, u.image AS sender_image, fc.message, fc.created_at
        FROM messages fc
        JOIN users u ON fc.sender_id = u.id
        WHERE (fc.sender_id = ? AND fc.receiver_id = ?) OR (fc.sender_id = ? AND fc.receiver_id = ?)
        ORDER BY fc.created_at ASC
    ");
    $stmt->bind_param("iiii", $user_id, $friend['id'], $friend['id'], $user_id);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

?>

<body class="bg-base-200 min-h-screen flex flex-col font-sans">

    <!-- Chat Header -->
    <div class="navbar bg-base-100 backdrop-blur-sm shadow-md sticky top-0 z-20 px-4">
        <div class="flex items-center gap-4">
            <a href="javascript:history.back()" class="btn btn-ghost btn-circle">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>

            <div class="relative">
                <?php if (isset($team)): ?>
                    <img src="./uploads/team_logos/<?= htmlspecialchars($team['logo']); ?>" class="w-12 h-12 rounded-full object-cover ring ring-success" alt="Team Logo">
                <?php elseif (isset($friend)): ?>
                    <img src="./uploads/users/<?= htmlspecialchars($friend['image']); ?>" class="w-12 h-12 rounded-full object-cover ring ring-primary" alt="Friend Image">
                <?php endif; ?>
            </div>

            <div>
                <h2 class="font-semibold text-base-content text-lg leading-tight">
                    <?= isset($team) ? htmlspecialchars($team['name']) : htmlspecialchars($friend['name']) ?>
                </h2>
                <p class="text-sm text-base-content/50">
                    <?= isset($team) ? 'Group Chat' : 'Private Chat' ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Messages Area -->
    <div id="messageContainer" class="flex-1 overflow-y-auto px-4 py-4 space-y-6 scroll-smooth h-[80%]">
        <!-- Messages will be dynamically loaded here -->
    </div>

    <!-- New Message Notification -->
    <div id="newMessageNotification" class="hidden fixed bottom-24 right-4 bg-red-500 text-white px-4 py-2 rounded-full shadow-lg cursor-pointer animate-bounce z-30">
        <i class="fas fa-arrow-down mr-1"></i> New Message
    </div>

    <!-- Chat Form -->
    <form id="chatForm" class="fixed bottom-0 left-0 right-0 px-4 py-3 bg-base-100 backdrop-blur-md z-30 shadow-inner">
        <input type="hidden" name="team_id" value="<?= isset($team) ? $team['id'] : ''; ?>">
        <input type="hidden" name="friend_id" value="<?= isset($friend) ? $friend['id'] : ''; ?>">

        <div class="flex items-center gap-3">
            <!-- Textarea Input -->
             <div class="flex-1 bg-white rounded-2xl px-4 py-2 shadow-sm">
                <textarea name="message" id="messageInput"
                    rows="1"
                    placeholder="Type a message..."
                    class="w-full bg-transparent border-none focus:outline-none resize-none text-sm placeholder-gray-500"
                    required></textarea>
            </div>

            <!-- Send Button -->
            <button type="submit" class="btn btn-success rounded-full btn-sm shadow-md px-4 py-2">
                <i class="fas fa-paper-plane text-white"></i>
            </button>
        </div>
    </form>

    <!-- Sound Notification -->
    <audio id="messageSound" src="./assets/sounds/notification.mp3" preload="auto"></audio>

</body>



<script>
    const chatForm = document.getElementById('chatForm');
    const messageInput = document.getElementById('messageInput');
    const messagesContainer = document.getElementById('messageContainer');
    const newMessageNotification = document.getElementById('newMessageNotification');
    const messageSound = document.getElementById('messageSound');
    let lastMessagesHtml = '';
    let isSoundEnabled = false;
    let userIsInteracting = false;

    // Enable sound playback on first user interaction
    function enableSound() {
        if (!isSoundEnabled) {
            messageSound.play().then(() => {
                messageSound.pause();
                messageSound.currentTime = 0;
                isSoundEnabled = true;
            }).catch(error => {
                console.warn("Sound playback could not be enabled:", error);
            });
        }
    }

    // Detect user interaction with the messages container
    messagesContainer.addEventListener('scroll', () => {
        const isAtBottom = Math.abs(messagesContainer.scrollHeight - messagesContainer.scrollTop - messagesContainer.clientHeight) < 10;
        userIsInteracting = !isAtBottom;
    });

    function renderMessages(messages) {
        const isAtBottom = Math.abs(messagesContainer.scrollHeight - messagesContainer.scrollTop - messagesContainer.clientHeight) < 10;

        if (messages.length === 0) {
            messagesContainer.innerHTML = `
            <div class="flex flex-col items-center justify-center h-full text-center text-gray-500 mt-20 z-[9999]">
                <img src="./assets/icons/emptychat.png" alt="No chats" class="w-40 h-40 mb-4 opacity-80" />
                <p class="text-sm">No messages yet. Start the conversation!</p>
            </div>
            `;
            return;
        }

        messagesContainer.innerHTML = messages.map(message => {
            const isSender = message.sender_id === <?= json_encode($_settings->userdata('id')); ?>;
            return `
            <div class="flex items-end gap-2 mb-3 ${isSender ? 'justify-end' : 'justify-start'}">
                ${!isSender ? `
                    <div class="w-9 h-9 rounded-full overflow-hidden border-2 border-green-500">
                        <img src="./uploads/users/${message.sender_image}" class="object-cover w-full h-full" />
                    </div>
                ` : ''}
                <div class="max-w-[75%]">
                    ${!isSender ? `
                        <div class="text-xs font-semibold text-green-500 mb-1 pl-0">
                            ${message.sender_name}
                        </div>
                    ` : ''}
                    <div class="relative px-4 py-2 rounded-2xl text-sm shadow 
                        ${isSender 
                            ? 'bg-green-500 text-white rounded-br-none' 
                            : 'bg-gray-100 text-base-900 rounded-bl-none'} word-wrap">
                        ${message.message.replace(/\n/g, '<br>')}
                        <div class="text-[6px] text-right mt-1 opacity-70">
                            ${new Date(message.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                        </div>
                    </div>
                </div>
                ${isSender ? `
                    <div class="w-9 h-9 rounded-full overflow-hidden border-2 border-green-500">
                        <img src="./uploads/users/${message.sender_image}" class="object-cover w-full h-full" />
                    </div>
                ` : ''}
            </div>
        `;
        }).join('');

        if (isAtBottom || !userIsInteracting) {
            scrollToBottom();
        } else {
            newMessageNotification.classList.remove('hidden');
            playNotificationSound();
        }
    }

    function fetchMessages() {
        const team_id = <?= json_encode($team['id'] ?? null); ?>;
        const friend_id = <?= json_encode($friend['id'] ?? null); ?>;
        const url = team_id ? `fetch_messages.php?team_id=${team_id}` : `fetch_messages.php?friend_id=${friend_id}`;

        fetch(url)
            .then(res => res.json())
            .then(messages => {
                const newHtml = messages.map(message => JSON.stringify(message)).join('');
                if (newHtml !== lastMessagesHtml) {
                    lastMessagesHtml = newHtml;
                    renderMessages(messages);
                }
            });
    }

    function scrollToBottom() {
        messagesContainer.scrollTo({
            top: messagesContainer.scrollHeight,
            behavior: 'smooth'
        });
        newMessageNotification.classList.add('hidden');
        userIsInteracting = false; // Reset interaction state
    }

    function playNotificationSound() {
        if (isSoundEnabled && messageSound) {
            messageSound.currentTime = 0;
            messageSound.play().catch(error => {
                console.error("Sound playback failed:", error);
            });
        }
    }

    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(chatForm);

        fetch('send_message.php', {
            method: 'POST',
            body: formData
        }).then(res => res.json()).then(response => {
            if (response.success) {
                messageInput.value = '';
                fetchMessages();
            } else {
                alert('Failed to send message. Please try again.');
            }
        }).catch(error => {
            console.error('Error sending message:', error);
        });
    });

    // Auto fetch messages on page load
    window.onload = () => {
        fetchMessages();
        setTimeout(scrollToBottom, 100); // Ensure scroll happens after rendering
    };

    // Periodic fetch
    setInterval(fetchMessages, 1000);

    // Optional: Press Enter to send
    messageInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            chatForm.dispatchEvent(new Event('submit'));
        }
    });

    // Scroll to bottom when clicking the notification
    newMessageNotification.addEventListener('click', scrollToBottom);

    // Enable sound on first user interaction
    document.addEventListener('click', enableSound, {
        once: true
    });
    document.addEventListener('touchstart', enableSound, {
        once: true
    });
</script>
<script>
    setInterval(() => {
        fetch('update_activity.php');
    }, 1000);
</script>
<style>
    .word-wrap {
        word-wrap: break-word;
        overflow-wrap: break-word;
    }
</style>