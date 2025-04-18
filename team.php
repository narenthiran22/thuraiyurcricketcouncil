<?php
// Determine user role and fetch appropriate team data
$role = $_settings->userdata('role');
$fav_team = $_settings->userdata('fav_team');
if ($role != 1 && $role != 2) {
    $teams = $conn->query("SELECT teams.*, creator.name AS created_by_user, captain.name AS captain_name, vice.name AS vice_captain_name
        FROM teams
        LEFT JOIN users AS creator ON creator.id = teams.created_by
        LEFT JOIN users AS captain ON captain.id = teams.captain
        LEFT JOIN users AS vice ON vice.id = teams.vice_captain
        ORDER BY teams.id ASC LIMIT 1");
} else {
    if (!$fav_team) {
        $first_team = $conn->query("SELECT id FROM teams ORDER BY id ASC LIMIT 1")->fetch_assoc();
        $fav_team = $first_team['id'] ?? 1;
    }
    $teams = $conn->query("SELECT teams.*, creator.name AS created_by_user, captain.name AS captain_name, vice.name AS vice_captain_name
        FROM teams
        LEFT JOIN users AS creator ON creator.id = teams.created_by
        LEFT JOIN users AS captain ON captain.id = teams.captain
        LEFT JOIN users AS vice ON vice.id = teams.vice_captain
        WHERE teams.id = '{$fav_team}'
        ORDER BY teams.name");
}
?>

<!-- 💎 My Team Header -->
<div class="text-center text-xl font-bold text-base-content mt-4 mb-2 py-3 shadow-md sticky top-0 bg-base-300/80 backdrop-blur-md z-[999] rounded-b-2xl">
    <h2 class="tracking-wide">🏏 My Team</h2>
</div>

<div class="max-w-md mx-auto px-3">
    <?php if ($_settings->userdata('id') > 0 && $role === 1): ?>
        <!-- ➕ Create Match Floating Button -->
        <div class="fixed bottom-20 right-4 z-50">
            <a href="./?p=matches" class="tooltip tooltip-left" data-tip="Create Match">
                <button class="btn btn-primary btn-circle shadow-xl hover:scale-110 active:scale-95 transition-all duration-200">
                    <i class="fas fa-calendar-plus text-lg"></i>
                </button>
            </a>
        </div>
    <?php endif; ?>

    <?php while ($team = $teams->fetch_assoc()): ?>
        <!-- 🧾 Team Card -->
        <div class="card bg-base-100 shadow-xl rounded-2xl mb-6 overflow-hidden transition-transform hover:scale-[1.01] duration-200">
            <div class="flex items-center gap-4 p-4">
                <div class="avatar">
                    <div class="w-16 h-16 rounded-xl overflow-hidden bg-base-200 flex items-center justify-center">
                        <?php if (!empty($team['logo'])): ?>
                            <img src="uploads/team_logos/<?= htmlspecialchars($team['logo']) ?>" class="object-cover w-full h-full" />
                        <?php else: ?>
                            <i class="fas fa-users text-gray-400 text-2xl"></i>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex-1">
                    <h2 class="text-lg font-bold truncate"><?= htmlspecialchars($team['name']) ?></h2>
                    <p class="text-sm italic text-gray-500">"<?= htmlspecialchars($team['nickname']) ?>"</p>
                </div>
                <?php if ($role === 1): ?>
                    <button class="btn btn-sm btn-ghost text-accent" title="Edit Team" data-team-id="<?= $team['id'] ?>">
                        <i class="fas fa-edit"></i>
                    </button>
                <?php endif; ?>
            </div>

            <div class="divider m-0"></div>

            <div class="p-4 space-y-2 text-sm text-base-content">
                <div class="flex items-center gap-2"><i class="fas fa-user text-blue-600 w-5 text-center"></i><span>Captain: <?= $team['captain_name'] ?? 'N/A' ?></span></div>
                <div class="flex items-center gap-2"><i class="fas fa-user-shield text-indigo-600 w-5 text-center"></i><span>Vice Captain: <?= $team['vice_captain_name'] ?? 'N/A' ?></span></div>
                <div class="flex items-center gap-2"><i class="fas fa-map-marker-alt text-red-500 w-5 text-center"></i><span><?= $team['address'] ?></span></div>
                <div class="flex items-center gap-2"><i class="fas fa-phone text-green-500 w-5 text-center"></i><span><?= $team['contact'] ?></span></div>
                <div class="flex items-center gap-2"><i class="fas fa-user-cog text-purple-500 w-5 text-center"></i><span>Admin: <?= $team['created_by_user'] ?></span></div>
                <?php if (!empty($team['disc'])): ?>
                    <div class="flex items-start gap-2"><i class="fas fa-align-left text-yellow-600 w-5 text-center pt-1"></i><span><?= $team['disc'] ?></span></div>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<!-- 🧭 Tabs: Players | Gallery | Followers -->
<div class="w-full max-w-md mx-auto mt-6 px-2 sm:px-0">
    <div class="tabs tabs-lifted overflow-x-auto scrollbar-hide whitespace-nowrap rounded-xl bg-base-100 shadow-lg">

        <!-- Players Tab -->
        <input type="radio" name="team_tab" role="tab" class="tab text-sm sm:text-base" aria-label="Players" checked />
        <div role="tabpanel" class="tab-content bg-base-100 border-base-300 rounded-box p-4">
            <ul id="players" class="space-y-3 animate-fade-in">
                <!-- 🦴 Skeleton Loader -->
                <template id="players-skeleton">
                    <li class="flex items-center gap-3 bg-base-200 p-3 rounded-xl shadow animate-pulse">
                        <div class="avatar">
                            <div class="w-10 h-10 bg-base-300 rounded-full"></div>
                        </div>
                        <div class="flex-1 space-y-2">
                            <div class="h-4 bg-base-300 rounded w-3/4"></div>
                            <div class="h-3 bg-base-300 rounded w-1/2"></div>
                        </div>
                    </li>
                </template>

                <!-- 🎯 Actual Player Example -->
                <li class="flex items-center gap-3 bg-base-200 p-3 rounded-xl shadow hover:bg-base-300 transition-all duration-200">
                    <div class="avatar">
                        <div class="w-10 rounded-full">
                            <img src="https://placehold.co/80x80" />
                        </div>
                    </div>
                    <div class="text-sm font-medium">
                        <div>John Doe</div>
                        <div class="text-xs text-gray-500">Batsman</div>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Gallery Tab -->
        <input type="radio" name="team_tab" role="tab" class="tab text-sm sm:text-base" aria-label="Gallery" />
        <div role="tabpanel" class="tab-content bg-base-100 border-base-300 rounded-box p-6 text-center space-y-4">
            <div class="text-warning text-5xl animate-bounce">⚠️</div>
            <h2 class="text-xl font-bold">Gallery Coming Soon</h2>
            <p class="text-sm text-base-content/70">We’re working on something amazing. Stay tuned!</p>
        </div>

        <!-- Followers Tab -->
        <input type="radio" name="team_tab" role="tab" class="tab text-sm sm:text-base" aria-label="Followers" />
        <div role="tabpanel" class="tab-content bg-base-100 border-base-300 rounded-box p-4">
            <ul id="followers" class="space-y-3 animate-fade-in">
                <!-- 🦴 Skeleton Loader -->
                <template id="followers-skeleton">
                    <li class="flex items-center gap-3 bg-base-200 p-3 rounded-xl shadow animate-pulse">
                        <div class="avatar">
                            <div class="w-10 h-10 bg-base-300 rounded-full"></div>
                        </div>
                        <div class="flex-1 space-y-2">
                            <div class="h-4 bg-base-300 rounded w-3/4"></div>
                            <div class="h-3 bg-base-300 rounded w-1/2"></div>
                        </div>
                    </li>
                </template>

                <!-- 🎯 Actual Follower Example -->
                <li class="flex items-center gap-3 bg-base-200 p-3 rounded-xl shadow hover:bg-base-300 transition-all duration-200">
                    <div class="avatar">
                        <div class="w-10 rounded-full">
                            <img src="https://placehold.co/80x80" />
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <script>
            function showSkeletons(containerId, templateId, count = 3) {
                const container = document.getElementById(containerId);
                const template = document.getElementById(templateId);
                container.innerHTML = "";
                for (let i = 0; i < count; i++) {
                    container.appendChild(template.content.cloneNode(true));
                }
            }

            // Example usage:
            showSkeletons("players", "players-skeleton");
            showSkeletons("followers", "followers-skeleton");

            // Replace with actual data via AJAX or after delay
            setTimeout(() => {
                // fetch and replace player/follower list here
            }, 1500);
        </script>
    </div>


    <div class="py-6 px-2">
        <h2 class="text-center text-xl font-bold text-base-content mb-4">All OverTeams</h2>
        <div class="flex gap-4 overflow-x-auto snap-x snap-mandatory scroll-smooth pb-4 px-1 scrollbar-hide">
            <?php
            $fav_team = $fav_team ?? 1;
            $overall_teams = $conn->query("SELECT id, name, nickname, contact, logo FROM teams WHERE id != '{$fav_team}' ORDER BY name ASC");
            while ($team = $overall_teams->fetch_assoc()):
                $image = !empty($team['logo'])
                    ? 'uploads/team_logos/' . htmlspecialchars($team['logo'])
                    : 'https://via.placeholder.com/300x200?text=' . strtoupper(substr($team['name'], 0, 2));
            ?>
                <div class="snap-start min-w-[250px] flex-shrink-0">
                    <div class="relative rounded-2xl overflow-hidden shadow-xl">
                        <div class="h-[240px] w-full bg-cover bg-center flex flex-col justify-end" style="background-image: url('<?= $image ?>');">
                            <div class="bg-gradient-to-t from-black/80 via-black/40 to-transparent w-full h-full p-4 flex flex-col justify-end text-white">
                                <h3 class="text-lg font-semibold truncate"><?= htmlspecialchars($team['name']) ?></h3>
                                <p class="text-sm italic text-gray-300">"<?= htmlspecialchars($team['nickname']) ?>"</p>
                                <p class="text-xs text-gray-400 mt-1">📞 <?= htmlspecialchars($team['contact']) ?></p>
                            </div>
                        </div>

                        <!-- Eye Icon for View -->
                        <button
                            class="absolute top-2 left-2 text-white p-2  transition open-modal"
                            data-type="view-team"
                            data-id="<?= $team['id'] ?>"
                            title="View Team">
                            <i class="fas fa-eye"></i>
                        </button>

                        <!-- Heart Icon -->
                        <label class="swap absolute top-2 right-2 z-20">
                            <input type="checkbox" />
                            <div class="swap-off">
                                <i class="fas fa-heart text-white text-lg p-2 bg-black/40 rounded-full"></i>
                            </div>
                            <div class="swap-on">
                                <i class="fas fa-heart text-red-500 text-lg p-2 bg-white/80 rounded-full"></i>
                            </div>
                        </label>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>


    <!-- 📜 Hide Scrollbar Style -->
    <style>
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }

        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>



    <!-- Update Team Modal -->
    <div id="updateProfileModal" class="fixed inset-0 z-[9999] flex items-end justify-center backdrop-blur-sm hidden">
        <div @click.outside="document.getElementById('updateProfileModal').classList.add('hidden')"
            class="relative w-full max-w-md bg-base-100 rounded-2xl shadow-xl border border-base-300 max-h-[90vh] overflow-y-auto transition-all duration-300">

            <!-- Header -->
            <div class="sticky top-0 z-10 bg-base-100 px-4 py-3 border-b border-base-300">
                <h2 class="text-xl font-semibold text-center text-primary">Update Team</h2>
            </div>

            <!-- Form -->
            <form id="update_team_form" action="update_team.php" method="POST" enctype="multipart/form-data"
                class="px-4 py-5 space-y-4">
                <input type="hidden" name="team_id" id="team_id" value="">

                <!-- Team Name -->
                <div>
                    <label class="label">
                        <span class="label-text font-medium">Team Name</span>
                    </label>
                    <input type="text" name="name" value="<?= isset($teamData['name']) ? htmlspecialchars($teamData['name']) : '' ?>" required
                        class="input input-bordered w-full" />
                </div>

                <!-- Nickname -->
                <div>
                    <label class="label">
                        <span class="label-text font-medium">Nickname</span>
                    </label>
                    <input type="text" name="nickname" value="<?= isset($teamData['nickname']) ? htmlspecialchars($teamData['nickname']) : '' ?>"
                        class="input input-bordered w-full" />
                </div>

                <!-- Captain -->
                <div>
                    <label class="label">
                        <span class="label-text font-medium">Captain</span>
                    </label>
                    <select name="captain" class="select select-bordered w-full">
                        <?php
                        $users = $conn->query("SELECT id, name FROM users ORDER BY name ASC");
                        while ($user = $users->fetch_assoc()):
                        ?>
                            <option value="<?= $user['id'] ?>" <?= isset($teamData['captain']) && $user['id'] == $teamData['captain'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Vice Captain -->
                <div>
                    <label class="label">
                        <span class="label-text font-medium">Vice Captain</span>
                    </label>
                    <select name="vice_captain" class="select select-bordered w-full">
                        <?php
                        $users = $conn->query("SELECT id, name FROM users ORDER BY name ASC");
                        while ($user = $users->fetch_assoc()):
                        ?>
                            <option value="<?= $user['id'] ?>" <?= isset($teamData['vice_captain']) && $user['id'] == $teamData['vice_captain'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Address -->
                <div>
                    <label class="label">
                        <span class="label-text font-medium">Address</span>
                    </label>
                    <textarea name="address" class="textarea textarea-bordered w-full"><?= isset($teamData['address']) ? htmlspecialchars($teamData['address']) : '' ?></textarea>
                </div>

                <!-- Contact -->
                <div>
                    <label class="label">
                        <span class="label-text font-medium">Contact</span>
                    </label>
                    <input type="text" name="contact" value="<?= isset($teamData['contact']) ? htmlspecialchars($teamData['contact']) : '' ?>"
                        class="input input-bordered w-full" />
                </div>

                <!-- Description -->
                <div>
                    <label class="label">
                        <span class="label-text font-medium">Description</span>
                    </label>
                    <textarea name="disc" class="textarea textarea-bordered w-full"><?= isset($teamData['disc']) ? htmlspecialchars($teamData['disc']) : '' ?></textarea>
                </div>

                <!-- Team Logo -->
                <div>
                    <label class="label">
                        <span class="label-text font-medium">Team Logo</span>
                    </label>
                    <input type="file" name="logo" id="logoInput" accept="image/*" class="file-input file-input-bordered w-full" />
                    <?php if (isset($teamData['logo']) && !empty($teamData['logo'])): ?>
                        <div id="logoPreview" class="mt-3 flex justify-center">
                            <img src="uploads/team_logos/<?= htmlspecialchars($teamData['logo']) ?>" class="w-20 h-20 rounded-full border-2 border-primary shadow-md object-cover" alt="Current Logo" />
                        </div>
                    <?php else: ?>
                        <div id="logoPreview" class="mt-3 flex justify-center hidden">
                            <img class="w-20 h-20 rounded-full border-2 border-primary shadow-md" alt="Preview Logo" />
                        </div>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Footer Buttons -->
            <div class="sticky bottom-0 z-10 bg-base-100 px-4 py-3 border-t border-base-300 flex gap-3">
                <button type="button" onclick="document.getElementById('updateProfileModal').classList.add('hidden')"
                    class="btn btn-outline">Cancel</button>
                <button type="submit" form="update_team_form" class="btn btn-primary">Update</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById("logoInput").addEventListener("change", function(e) {
        const file = e.target.files[0];
        const previewDiv = document.getElementById("logoPreview");
        const previewImg = previewDiv.querySelector("img");

        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                previewImg.src = event.target.result;
                previewDiv.classList.remove("hidden");
            };
            reader.readAsDataURL(file);
        }
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const captainSelect = document.querySelector("select[name='captain']");
        const viceCaptainSelect = document.querySelector("select[name='vice_captain']");

        function filterOptions() {
            const selectedCaptain = captainSelect.value;
            const selectedViceCaptain = viceCaptainSelect.value;

            // Reset both dropdowns first
            Array.from(captainSelect.options).forEach(option => {
                option.disabled = (option.value === selectedViceCaptain && option.value !== "");
            });

            Array.from(viceCaptainSelect.options).forEach(option => {
                option.disabled = (option.value === selectedCaptain && option.value !== "");
            });
        }

        // Run once on load to filter already selected options
        filterOptions();

        // Add change listeners
        captainSelect.addEventListener("change", filterOptions);
        viceCaptainSelect.addEventListener("change", filterOptions);
    });
</script>

<script>
    function openUpdateModal(teamId) {
        // Fetch team data via AJAX
        fetch(`classes/Master.php?f=get_team_details&team_id=${teamId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const team = data.team;

                    // Populate modal fields with team data
                    document.getElementById('team_id').value = team.id;
                    document.querySelector('input[name="name"]').value = team.name;
                    document.querySelector('input[name="nickname"]').value = team.nickname;
                    document.querySelector('textarea[name="address"]').value = team.address;
                    document.querySelector('input[name="contact"]').value = team.contact;
                    document.querySelector('textarea[name="disc"]').value = team.disc;
                    document.querySelector('select[name="captain"]').value = team.captain;
                    document.querySelector('select[name="vice_captain"]').value = team.vice_captain;

                    // Update logo preview if available
                    const logoPreview = document.querySelector('#updateProfileModal img');
                    if (team.logo) {
                        logoPreview.src = `uploads/team_logos/${team.logo}`;
                        logoPreview.style.display = 'block';
                    } else {
                        logoPreview.style.display = 'none';
                    }

                    // Show the modal
                    document.getElementById('updateProfileModal').classList.remove('hidden');
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to fetch team details.');
            });
    }

    document.querySelectorAll('button[title="Edit Team"]').forEach(button => {
        button.addEventListener('click', function() {
            const teamId = this.getAttribute('data-team-id');
            openUpdateModal(teamId);
        });
    });

    document.querySelector('form[action="update_team.php"]').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('classes/Master.php?f=update_team', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update UI if needed (optional)
                    alert('Team updated successfully ✅');
                    document.getElementById('updateProfileModal').classList.add('hidden');
                    location.reload(); // full refresh to reflect updates
                } else {
                    alert(data.message || 'Update failed ❌');
                }
            })
            .catch(err => {
                console.error('Update Error:', err);
                alert('Something went wrong while updating. ❌');
            });
    });
</script>
<script>
    const colors = ['text-primary', 'text-secondary', 'text-accent', 'text-info', 'text-success', 'text-warning', 'text-error', 'text-neutral'];

    const shuffle = (arr) => {
        for (let i = arr.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [arr[i], arr[j]] = [arr[j], arr[i]];
        }
        return arr;
    };

    function renderUserList(users, targetSelector) {
        const shuffledColors = shuffle([...colors]);
        let html = '';

        if (users.length > 0) {
            $.each(users, function(i, user) {
                html += `
                    <li class="list-row mt-2 bg-base-200 flex items-center gap-4 p-2 rounded-lg">
                        <img class="size-10 rounded-box object-cover" src="./uploads/users/${user.image}" />
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                            <a href="javascript:void(0)" data-type="view-profile" data-id="${user.id}" class="open-modal underline"><h1 class="font-semibold">${user.name}</h1></a>
                                <i class="fas ${user.role == 1? 'fa-user-shield': user.role == 2? 'fa-futbol': 'fa-user'} ${shuffledColors[i % shuffledColors.length]} mb-2"></i>
                            </div>
                            <p class="text-xs ${shuffledColors[i % shuffledColors.length]}">${user.role == 1? 'Admin': user.role == 2? 'Player': 'User'}</p>
                        </div>
                        <?php if ($_settings->userdata('role') == 1) { ?>
                        <button onclick="suspendUser(${user.id})" class="btn btn-md ${(user.role == 1 || user.role == 3) ? 'hidden' : ''}">
                            <i class="fas fa-certificate text-success text-lg"></i>
                        </button>
                        <?php } ?>
                        <button class="btn btn-md ${user.role == 3 ? '' : 'hidden'}" onclick="promoteuser(${user.id})" ><i class="fas fa-chess-queen text-error"></i></button>
                        <label class="swap">
                            <input type="checkbox" />
                            <div class="swap-off"><i class="fas fa-heart text-gray-400 text-lg"></i></div>
                            <div class="swap-on"><i class="fas fa-heart text-red-500 text-lg"></i></div>
                        </label>
                    </li>
                `;
            });
        } else {
            html = `
    <div class="flex flex-col items-center justify-center text-center text-gray-500 bg-base-200 p-6 rounded-xl shadow-inner">
        <i class="fas fa-earth-europe fa-3x mb-3 text-gray-400 animate-spin"></i>
        <p class="text-sm font-medium">No users found</p>
        <p class="text-xs text-gray-400">Patience is the key — your content is on the way.</p>
    </div>
`;

        }

        $(targetSelector).html(html);
    }

    function loadPlayers(team_id) {
        $.ajax({
            url: 'classes/Master.php?f=loadplayers',
            type: 'POST',
            dataType: 'json',
            data: {
                team_id
            },
            success: function(res) {
                const players = res.users || [];
                localStorage.setItem('team_players', JSON.stringify(players)); // store in local
                renderUserList(players, '#players');
            }
        });
    }

    function loadusers(team_id) {
        $.ajax({
            url: 'classes/Master.php?f=loadusers',
            type: 'POST',
            dataType: 'json',
            data: {
                team_id
            },
            success: function(res) {
                const players = res.users || [];
                localStorage.setItem('team_users', JSON.stringify(players)); // store in local
                renderUserList(players, '#followers');
            }
        });
    }

    $(document).ready(function() {
        const team_id = <?php echo json_encode($_settings->userdata('fav_team') ?: 5); ?>;
        loadPlayers(team_id); // First load
        loadusers(team_id); // Load users

        $('input[name="my_tabs_4"]').on('change', function() {
            if (!$(this).is(':checked')) return;

            const labelText = $(this).closest('label').text().trim().toLowerCase();
            const allPlayers = JSON.parse(localStorage.getItem('team_players') || '[]');

            if (labelText.includes('players')) {
                renderUserList(allPlayers, '#players');
            } else if (labelText.includes('followers')) {
                const followers = JSON.parse(localStorage.getItem('team_users') || '[]');
                renderUserList(followers, '#followers');
            }
        });
    });


    function suspendUser(userId) {
        const team_id = <?php echo json_encode($_settings->userdata('fav_team')); ?>;

        conf("You want to promote this Player to Admin", function() {
            $.ajax({
                url: 'classes/Master.php?f=suspend_player',
                type: 'POST',
                data: {
                    user_id: userId,
                    team_id: team_id
                },
                beforeSend: function() {
                    start_loader();
                },
                success: function(res) {
                    alert_toast("Player promoted", "success");
                    loadPlayers(team_id); // Optional reload
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                    alert_toast("An error occurred while promoting", "error");
                },
                complete: function() {
                    end_loader();
                }
            });
        });
    }

    function promoteuser(userId) {
        const team_id = <?php echo json_encode($_settings->userdata('fav_team')); ?>;

        conf("You want to promote this user to player?", function() {
            $.ajax({
                url: 'classes/Master.php?f=promote_user',
                type: 'POST',
                data: {
                    user_id: userId,
                    team_id: team_id
                },
                beforeSend: function() {
                    start_loader();
                },
                success: function(res) {
                    alert_toast("promoted successfully", "success");
                    loadPlayers(team_id); // Optional reload
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                    alert_toast("An error occurred while promoting", "error");
                },
                complete: function() {
                    end_loader();
                }
            });
        });
    }
</script>
