<div class="max-w-4xl mx-auto px-4 py-6 bg-white rounded-lg shadow-md">
    <h2 class="text-2xl text-center font-bold mb-4 text-green-700">Hello Boss <?= $_settings->userdata('name') ?></h2>
    <p class="text-gray-500 mb-4">Here you can manage your announcements and other settings.</p>
</div>

<!-- ANNOUNCEMENT SECTION -->
<section class="p-4">
    <?php
    $fav_team = $_settings->userdata('fav_team');

    $query = "
    SELECT * FROM announcements 
    WHERE status = 'active' 
    AND (team_id = '$fav_team' OR team_id IS NULL)
    ORDER BY posted_on DESC
    ";
    $result = mysqli_query($conn, $query);

    $announcements = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $announcements[] = $row;
    }
    ?>

    <div class="max-w-3xl mx-auto px-4 py-8 mt-10 bg-base-100 rounded-2xl shadow-lg">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-primary flex items-center gap-2">📢 Announcements</h2>
            <button class="btn btn-success btn-sm" onclick="document.getElementById('addModal').showModal()">
                ➕ Add
            </button>
        </div>

        <!-- Modal -->
        <dialog id="addModal" class="modal modal-bottom sm:modal-middle">
            <div class="modal-box rounded-xl">
                <h3 class="font-bold text-lg mb-4 text-center">Add Announcement</h3>
                <form id="addAnnouncementForm" class="flex flex-col gap-4" enctype="multipart/form-data">
                    <input type="hidden" name="team_id" value="<?= $fav_team ?>">

                    <label class="form-control w-full">
                        <span class="label-text">Heading</span>
                        <input type="text" name="heading" placeholder="Announcement heading" class="input input-bordered w-full" required>
                    </label>

                    <label class="form-control w-full">
                        <span class="label-text">Message</span>
                        <textarea name="message" rows="3" placeholder="Enter announcement message" class="textarea textarea-bordered w-full" required></textarea>
                    </label>

                    <label class="form-control w-full">
                        <span class="label-text mb-2">Image (optional)</span>
                        <div class="flex flex-col items-center gap-2">
                            <label for="image" class="relative cursor-pointer group">
                                <div class="w-24 h-24 rounded-full border-2 border-dashed border-primary flex items-center justify-center overflow-hidden bg-base-200 shadow-md transition hover:scale-105">
                                    <img id="imagePreview" src="./assets/default.webp" alt="Image Preview" class="w-full h-full object-cover" />
                                    <div class="absolute inset-0 bg-black bg-opacity-40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-sm font-semibold rounded-full transition">
                                        Change
                                    </div>
                                </div>
                                <input type="file" id="image" name="image" accept="image/*" class="hidden" onchange="previewImage(event)" />
                            </label>
                            <span class="text-sm text-gray-500">Tap to upload image</span>
                        </div>
                    </label>

                    <script>
                        function previewImage(event) {
                            const input = event.target;
                            const preview = document.getElementById("imagePreview");
                            if (input.files && input.files[0]) {
                                const reader = new FileReader();
                                reader.onload = e => {
                                    preview.src = e.target.result;
                                };
                                reader.readAsDataURL(input.files[0]);
                            }
                        }
                    </script>


                    <div class="modal-action flex flex-col gap-2 sm:flex-row sm:justify-end">
                        <button type="submit" class="btn btn-primary w-full sm:w-auto">📤 Submit</button>
                        <button type="button" class="btn w-full sm:w-auto" onclick="document.getElementById('addModal').close()">❌ Cancel</button>
                    </div>
                </form>
            </div>
        </dialog>

        <?php if (empty($announcements)): ?>
            <div class="text-center text-gray-500 mt-8">
                No announcements available.
            </div>
        <?php else: ?>
            <ul class="space-y-5 mt-6" id="announcement-list">
                <?php foreach ($announcements as $announcement): ?>
                    <li class="bg-white p-4 rounded-xl shadow border border-gray-200" id="announcement-<?= $announcement['id'] ?>">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-semibold text-blue-600 mb-1">
                                    <?= htmlspecialchars($announcement['heading']) ?>
                                </h3>
                                <p class="text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($announcement['message'])) ?></p>
                                <p class="text-xs text-gray-400 mt-2">🗓️ <?= date('M d, Y h:i A', strtotime($announcement['posted_on'])) ?></p>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <button
                                class="btn btn-warning btn-sm"
                                onclick="toggleStatus(<?= $announcement['id'] ?>)">
                                <?= $announcement['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                            </button>
                            <button
                                class="btn btn-error btn-sm"
                                onclick="deleteAnnouncement(<?= $announcement['id'] ?>)">
                                Delete
                            </button>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>


    <script>
        document.getElementById('addAnnouncementForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('./classes/Master.php?f=add_announcement', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); // Refresh to show the new announcement
                    } else {
                        alert(data.message || 'Failed to add announcement');
                    }
                })
                .catch(() => alert('An error occurred while adding the announcement'));
        });

        function toggleStatus(id) {
            fetch('./classes/Master.php?f=toggle_announcement_status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `id=${id}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        location.reload(); // Reload to reflect the updated status
                    } else {
                        alert(data.message || 'Failed to change status');
                    }
                })
                .catch(err => alert('An error occurred while toggling status'));
        }

        function deleteAnnouncement(id) {
            if (!confirm('Are you sure you want to delete this announcement?')) return;

            fetch('./classes/Master.php?f=delete_announcement', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `id=${id}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById(`announcement-${id}`).remove();
                    } else {
                        alert(data.message || 'Delete failed');
                    }
                })
                .catch(() => alert('An error occurred while deleting the announcement'));
        }
    </script>


</section>

<!-- Sponsors - Clean Mobile UI Style -->
<section class="p-4">
    <div class="bg-base-100 rounded-2xl shadow-xl p-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-800">Supporters</h2>
            <button class="btn btn-success btn-sm" onclick="sponsorModal.showModal()">
                ➕ Add
            </button>
        </div>

        <!-- Sponsor Cards -->
        <div id="sponsorGrid" class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
            <?php
            $sponsors = $conn->query("SELECT * FROM sponsors WHERE team_id = '" . $_settings->userdata('fav_team') . "'");
            while ($sponsor = $sponsors->fetch_assoc()): ?>
                <div class="relative bg-white rounded-xl shadow-md overflow-hidden border">
                    <img src="./uploads/sponsor_logos/<?php echo htmlspecialchars($sponsor['photo']); ?>" class="w-full h-28 object-cover" />
                    <div class="p-2 text-center backdrop-blur-sm text-sm font-medium capitalize <?php echo $sponsor['party']; ?>">
                        <?php echo htmlspecialchars($sponsor['name']); ?>
                    </div>
                    <button onclick="deleteSponsor(<?php echo $sponsor['id']; ?>)" class="absolute top-2 right-2 btn btn-xs btn-circle btn-error" title="Delete">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Add Sponsor Modal -->
    <dialog id="sponsorModal" class="modal modal-bottom sm:modal-middle">
        <div class="modal-box rounded-2xl shadow-xl p-6">
            <h3 class="text-xl font-semibold mb-4 text-center text-primary">Add New Sponsor</h3>
            <form id="addSponsorForm" class="flex flex-col gap-4" enctype="multipart/form-data">

                <!-- Hidden team ID -->
                <input type="hidden" name="team_id" value="<?php echo $fav_team; ?>">

                <!-- Sponsor Name -->
                <label class="form-control w-full">
                    <span class="label-text mb-1 text-sm font-medium">Sponsor Name</span>
                    <input type="text" name="name" placeholder="Enter sponsor name" class="input input-bordered w-full" required />
                </label>

                <!-- Sponsor Photo Upload -->
                <div class="flex flex-col items-center gap-2">
                    <label for="photo" class="relative cursor-pointer group">
                        <div class="w-24 h-24 rounded-full border-2 border-dashed border-primary flex items-center justify-center overflow-hidden bg-base-200 shadow-md transition hover:scale-105">
                            <img id="photoPreview" src="./assets/default.webp" alt="Sponsor Logo" class="w-full h-full object-cover" />
                            <div class="absolute inset-0 bg-black bg-opacity-40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-sm font-semibold rounded-full transition">
                                Change
                            </div>
                        </div>
                        <input type="file" id="photo" name="photo" accept="image/*" class="hidden" onchange="previewSponsorLogo(event)" required />
                    </label>
                    <span class="text-sm text-gray-500">Tap to upload logo</span>
                </div>

                <script>
                    function previewSponsorLogo(event) {
                        const input = event.target;
                        const preview = document.getElementById("photoPreview");
                        if (input.files && input.files[0]) {
                            const reader = new FileReader();
                            reader.onload = e => {
                                preview.src = e.target.result;
                            };
                            reader.readAsDataURL(input.files[0]);
                        }
                    }
                </script>


                <!-- Party Color -->
                <label class="form-control w-full">
                    <span class="label-text mb-1 text-sm font-medium">Party Color</span>
                    <select name="party" class="select select-bordered w-full" required>
                        <option value="" disabled selected>Select Party Color</option>
                        <option value="dmk">DMK</option>
                        <option value="admk">ADMK</option>
                        <option value="dmdk">DMDK</option>
                        <option value="pmk">PMK</option>
                        <option value="tvk">TVK</option>
                        <option value="red">Red</option>
                        <option value="blue">Blue</option>
                        <option value="green">Green</option>
                        <option value="yellow">Yellow</option>
                    </select>
                </label>

                <!-- Action Buttons -->
                <div class="flex flex-col gap-2 pt-2">
                    <button type="submit" class="btn btn-primary w-full shadow-md">Add Sponsor</button>
                    <button type="button" class="btn btn-ghost w-full" onclick="sponsorModal.close()">Cancel</button>
                </div>
            </form>
        </div>
    </dialog>

</section>

<!-- Twilio Settings -->
<section class="max-w-xl mx-auto my-10">
    <?php
    $query = "SELECT account_sid, auth_token, twilio_number FROM settings LIMIT 1";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    ?>

    <div class="card shadow-xl bg-base-100">
        <div class="card-body space-y-4">
            <h2 class="card-title text-primary text-2xl font-semibold">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3a.75.75 0 01.75.75v2.086a7.5 7.5 0 013.404 0V3.75a.75.75 0 011.5 0v2.822a7.5 7.5 0 012.57 1.859l1.997-1.997a.75.75 0 111.06 1.061l-2.014 2.014a7.5 7.5 0 010 3.404l2.014 2.014a.75.75 0 11-1.06 1.06l-1.998-1.996a7.5 7.5 0 01-2.57 1.858v2.823a.75.75 0 01-1.5 0v-2.086a7.5 7.5 0 01-3.404 0v2.086a.75.75 0 01-1.5 0v-2.823a7.5 7.5 0 01-2.57-1.858l-1.997 1.996a.75.75 0 01-1.061-1.06l2.015-2.015a7.5 7.5 0 010-3.404L4.72 8.695a.75.75 0 111.06-1.06l1.998 1.996a7.5 7.5 0 012.57-1.859V3.75a.75.75 0 01.75-.75z" />
                </svg>
                Twilio API Settings
            </h2>

            <form id="twilioSettingsForm" method="post" class="space-y-4">
                <div>
                    <label class="label">
                        <span class="label-text font-medium">Account SID</span>
                    </label>
                    <input type="text" name="account_sid" id="account_sid" value="<?= $row['account_sid'] ?>" class="input input-bordered w-full" required>
                </div>

                <div>
                    <label class="label">
                        <span class="label-text font-medium">Auth Token</span>
                    </label>
                    <input type="text" name="auth_token" id="auth_token" value="<?= $row['auth_token'] ?>" class="input input-bordered w-full" required>
                </div>

                <div>
                    <label class="label">
                        <span class="label-text font-medium">Twilio Number</span>
                    </label>
                    <input type="text" name="twilio_number" id="twilio_number" value="<?= $row['twilio_number'] ?>" class="input input-bordered w-full" required>
                </div>

                <div class="pt-4">
                    <button type="submit" class="btn btn-primary w-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Update Twilio Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('twilioSettingsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('./classes/Login.php?f=update_settings', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert_toast("Settings updated successfully", 'success');
                    } else {
                        alert_toast("Failed to update settings", 'error');
                    }
                });
        });
    </script>
</section>




<script>
    document.getElementById('addSponsorForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        fetch('./classes/Master.php?f=add_sponcre', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(data => {
                document.getElementById('sponsorGrid').innerHTML = data;
                sponsorModal.close();
                form.reset();
            })
            .catch(() => alert('An error occurred while adding the sponsor.'));
    });

    function deleteSponsor(id) {
        conf("Are you sure you want to delete this sponsor?", function() {
            fetch('./classes/Master.php?f=delete_sponcre&id=' + id + '&team_id=<?php echo $fav_team; ?>')
                .then(res => res.text())
                .then(data => {
                    document.getElementById('sponsorGrid').innerHTML = data;
                    alert_toast("Sponsor deleted successfully", 'success');
                })
                .catch(() => {
                    alert_toast("An error occurred while deleting the sponsor.", 'error');
                });
        });
    }
</script>