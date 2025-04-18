<section>
    <?php
    $fav_team = $_settings->userdata('fav_team');

    // First, check for active announcements specific to the favorite team
    $query = "SELECT * FROM announcements WHERE status = 'active' AND team_id = '$fav_team' ORDER BY posted_on DESC LIMIT 1";
    $result = mysqli_query($conn, $query);

    $announcements = [];

    if (mysqli_num_rows($result) > 0) {
        // If announcements for the favorite team exist, fetch the latest one
        while ($row = mysqli_fetch_assoc($result)) {
            $announcements[] = $row;
        }
    } else {
        // If no announcements for the favorite team, fetch the latest active announcement overall
        $query = "SELECT * FROM announcements WHERE status = 'active' ORDER BY posted_on DESC LIMIT 1";
        $result = mysqli_query($conn, $query);

        while ($row = mysqli_fetch_assoc($result)) {
            $announcements[] = $row;
        }
    }
    ?>

    <?php if (count($announcements) > 0): ?>
        <?php foreach ($announcements as $index => $a): ?>
            <input type="checkbox" id="modal-<?= $index ?>" class="modal-toggle" <?= $index === 0 ? 'checked' : '' ?>>
            <div class="modal fixed inset-0 z-[9999] flex items-end md:items-center justify-center bg-black bg-opacity-80 backdrop-blur-md" id="modal-wrap-<?= $index ?>" onclick="closeModal(<?= $index ?>)">
                <div class="relative w-full max-w-xl bg-white/10 backdrop-blur-lg text-white shadow-2xl rounded-3xl p-8 border border-white/20" onclick="event.stopPropagation()">

                    <!-- AI Bot Icon -->
                    <div class="flex justify-center -mt-14 mb-4 relative w-full">
                        <div class="relative">
                            <img src="./assets/imgs/bot 1.jpeg" alt="AI Bot" class="w-20 h-20 object-cover rounded-full border-4 border-white shadow-lg bg-gradient-to-br from-blue-500 to-purple-600 p-1">

                            <!-- Badge Positioned Bottom-Right -->
                            <span class="absolute bottom-0 right-0 w-4 h-4 bg-green-500 border-2 border-white rounded-full shadow-md"></span>
                        </div>
                    </div>

                    <!-- Animated Heading -->
                    <h3 class="text-2xl font-bold text-center typewriter"><?= htmlspecialchars($a['heading']) ?></h3>

                    <!-- Message -->
                    <p class="py-4 whitespace-pre-line text-lg text-center text-white/90"><?= nl2br(htmlspecialchars($a['message'])) ?></p>

                    <?php if (!empty($a['image'])): ?>
                        <img src="./<?= $a['image'] ?>" class="rounded-xl mt-4 max-h-72 w-full object-cover shadow-lg" />
                    <?php endif; ?>

                    <!-- Date -->
                    <div class="text-sm text-right text-white/60 mt-4">
                        <?= date('d M Y, h:i A', strtotime($a['posted_on'])) ?>
                    </div>

                    <!-- Close Button -->
                    <div class="mt-6 text-center">
                        <button class="px-6 py-3 rounded-full bg-gradient-to-r from-pink-500 to-purple-500 text-white font-semibold shadow-xl hover:from-pink-600 hover:to-purple-600 animate-glow" onclick="closeModal(<?= $index ?>)">
                            Thanks
                        </button>
                    </div>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

            <script>
                window.onload = function() {
                    const firstModalId = 'modal-0';
                    const hasSeenAnnouncement = sessionStorage.getItem('seen_announcement');

                    if (!hasSeenAnnouncement) {
                        const checkbox = document.getElementById(firstModalId);
                        if (checkbox) {
                            checkbox.checked = true; // Show modal
                            launchConfetti();
                            launchBalloons(); // Optional
                            sessionStorage.setItem('seen_announcement', 'true');
                        }
                    } else {
                        // Hide all modals if already seen
                        document.querySelectorAll('[id^="modal-"]').forEach(el => el.checked = false);
                        document.querySelectorAll('[id^="modal-wrap-"]').forEach(el => el.classList.add('hidden'));
                    }
                }

                function closeModal(index) {
                    const modal = document.getElementById('modal-wrap-' + index);
                    const checkbox = document.getElementById('modal-' + index);
                    if (checkbox) checkbox.checked = false;
                    if (modal) {
                        modal.classList.add('opacity-0');
                        setTimeout(() => modal.classList.add('hidden'), 300);
                    }
                }

                function launchConfetti() {
                    const duration = 3000;
                    const end = Date.now() + duration;

                    (function frame() {
                        confetti({
                            particleCount: 5,
                            angle: 60,
                            spread: 55,
                            zIndex: 9999,
                            origin: {
                                x: 0
                            }
                        });
                        confetti({
                            particleCount: 5,
                            angle: 120,
                            spread: 55,
                            zIndex: 9999,
                            origin: {
                                x: 1
                            }
                        });

                        if (Date.now() < end) {
                            requestAnimationFrame(frame);
                        }
                    })();
                }
            </script>

        <?php endforeach; ?>
    <?php endif; ?>

</section>


<!------------------------------------------TEAM SECTION-------------------------------->
<section>
    <div class="continue p-4">
        <h2 class="text-3xl font-bold text-gray-900 mb-6">Teams</h2>
        <?php if (!empty($appconfig->__get('teams'))): ?> <!-- Use magic method -->
            <div id="team-container" class="flex flex-wrap gap-x-6 overflow-x-auto scrollbar-hide"></div>
        <?php else: ?>
            <div class="flex flex-col items-center">
                <p class="text-gray-500 text-lg">No teams available</p>
            </div>
        <?php endif; ?>
    </div>

    <style>
        .team-container {
            position: relative;
            background-color: white;
            /* White margin */
            padding: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .team-name {
            position: absolute;
            bottom: 0;
            width: 100%;
            background: rgba(0, 0, 0, 0.6);
            color: #fff;
            text-align: center;
            font-weight: bold;
            padding: 0px 0;
            text-transform: uppercase;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const teams = <?php echo json_encode($appconfig->__get('teams')); ?>;
            const container = document.getElementById('team-container');

            if (teams.length > 0) {
                let rowHtml = '<div class="flex space-x-6 relative bg-white p-4 rounded-xl">';

                const firstTeam = teams[0];
                rowHtml += `
                <div class="w-60 h-[340px] bg-white shadow-md rounded-xl border border-gray-300 hover:shadow-xl overflow-hidden flex-shrink-0 team-photo relative" data-index="0">
                    <div class="w-full h-full overflow-hidden shadow-md">
                        <img src="./uploads/team_logos/${firstTeam.logo}" alt="${firstTeam.name} Logo" class="w-full h-full object-cover">
                    </div>
                    <div class="absolute glass team-name bottom-0 left-0 w-60 bg-white bg-opacity-50 text-center py-2">
                        <p class="font-bold uppercase animate-marquee">${firstTeam.name}</p>
                    </div>
                </div>`;

                for (let i = 1; i < teams.length; i += 2) {
                    const teamGroup = teams.slice(i, i + 2);
                    let groupHtml = '<div class="flex flex-col space-y-4 flex-shrink-0">';

                    teamGroup.forEach((team, index) => {
                        groupHtml += `
                        <div class="w-40 h-40 bg-white shadow-md rounded-xl border border-gray-300 hover:shadow-xl overflow-hidden flex-shrink-0 team-photo relative" data-index="${i + index}">
                            <div class="w-full h-full overflow-hidden shadow-md">
                                <img src="./uploads/team_logos/${team.logo}" alt="${team.name} Logo" class="w-full h-full object-cover">
                            </div>
                            <div class="absolute glass team-name  w-full bg-white bg-opacity-75">
                                <p class="font-bold uppercase animate-marquee">${team.name}</p>
                            </div>
                        </div>`;
                    });

                    groupHtml += '</div>';
                    rowHtml += groupHtml;
                }

                rowHtml += '</div>';
                container.innerHTML += rowHtml;
            }
        });
    </script>

</section>

<!------------------------------------------TOURNAMENTS SECTION-------------------------------->
<section>
    <div class="p-4">
        <h2 class="text-3xl font-bold text-gray-900 mb-6">Tournaments</h2>
        <?php if (!empty($appconfig->__get('tournaments'))): ?>
            <div class="flex overflow-x-auto scrollbar-hide gap-6 px bg-gray-100 ">
                <?php foreach ($appconfig->__get('tournaments') as $tournament): ?>
                    <div class="flex-shrink-0 w-64 bg-white shadow-lg rounded-xl border border-gray-300 hover:shadow-2xl transition-transform transform hover:-translate-y-2 overflow-hidden">
                        <div class="w-full h-40 overflow-hidden relative">
                            <?php if (!empty($tournament['image'])): ?>
                                <img src="./uploads/tournament_images/<?php echo htmlspecialchars($tournament['image']); ?>" alt="<?php echo htmlspecialchars($tournament['name']); ?> Image" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center bg-gray-200 text-gray-500 text-lg font-semibold">
                                    <?php echo htmlspecialchars($tournament['name']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="absolute top-2 right-2 text-gray-900 text-xs px-2 py-1">
                                <?php echo date('d-m-Y', strtotime($tournament['start_date'])); ?>
                            </div>
                        </div>
                        <div class="p-4">
                            <h3 class="text-xl font-semibold text-gray-900 truncate"> <?php echo htmlspecialchars($tournament['name']); ?> </h3>
                            <p class="text-gray-600 mt-1"><i class="fas fa-map-marker-alt mr-2"></i> <?php echo htmlspecialchars($tournament['location']); ?></p>
                            <p class="text-gray-600 mt-1"><i class="fas fa-trophy mr-2"></i> First Prize: <?php echo htmlspecialchars($tournament['first_prize']); ?></p>

                            <a href="javascript:void(0)" data-type="view-tournament" data-id="<?= $tournament['id'] ?>" class="btn btn-link open-modal no-underline"> View Details</a>

                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-lg">No tournaments available</p>
        <?php endif; ?>
    </div>
</section>

<!------------------------------------------Matches SECTION-------------------------------->

<?php
$fav_team_id = $_settings->userdata('fav_team');

if ($fav_team_id) {
    // Show matches involving favorite team
    $sql = "SELECT m.*, 
                   t1.name AS team1_name, 
                   t2.name AS team2_name 
            FROM matches m
            JOIN teams t1 ON m.team1_id = t1.id
            JOIN teams t2 ON m.team2_id = t2.id
            WHERE m.team1_id = '$fav_team_id' OR m.team2_id = '$fav_team_id'
            ORDER BY m.date DESC, m.time DESC
            LIMIT 5";
} else {
    // Show any recent matches
    $sql = "SELECT m.*, 
                   t1.name AS team1_name, 
                   t2.name AS team2_name 
            FROM matches m
            JOIN teams t1 ON m.team1_id = t1.id
            JOIN teams t2 ON m.team2_id = t2.id
            ORDER BY m.date DESC, m.time DESC
            LIMIT 5";
}

$result = mysqli_query($conn, $sql);
?>

<section class="p-4">
    <h2 class="text-2xl font-bold text-gray-900 mb-4">Matches</h2>

    <div class="flex space-x-4 overflow-x-auto snap-x snap-mandatory scrollbar-hide pb-2">
        <?php while ($row = mysqli_fetch_assoc($result)) {
            $winner = null;
            if ($row['team1_score'] !== null && $row['team2_score'] !== null) {
                $diff = abs($row['team1_score'] - $row['team2_score']);
                if ($row['team1_score'] > $row['team2_score']) {
                    $winner = "{$row['team1_name']} Won by {$diff} runs";
                } elseif ($row['team1_score'] < $row['team2_score']) {
                    $winner = "{$row['team2_name']} Won by {$diff} runs";
                } else {
                    $winner = "Match Draw";
                }
            }
        ?>
            <div class="bg-white rounded-2xl shadow-md p-4 border border-gray-100 min-w-[100%] sm:min-w-[300px] snap-start flex-shrink-0">
                <!-- Date & Time -->
                <div class="flex items-center justify-between text-xs text-gray-500 mb-2">
                    <div class="flex items-center space-x-1">
                        <i class="fas fa-calendar-alt text-blue-500"></i>
                        <span><?= date("d M Y", strtotime($row['date'])) ?></span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <i class="fas fa-clock text-yellow-500"></i>
                        <span><?= date("h:i A", strtotime($row['time'])) ?></span>
                    </div>
                </div>

                <!-- Teams -->
                <div class="text-center text-lg font-bold text-gray-800 mb-3 overflow-hidden">
                    <h2 class="animate-marquee">
                        <span><?= $row['team1_name'] ?></span>
                        <span class="text-sm text-red-400 font-medium px-2">V/S</span>
                        <span><?= $row['team2_name'] ?></span>
                    </h2>

                </div>

                <!-- Venue -->
                <div class="flex items-center justify-center text-sm text-gray-600 mb-2">
                    <i class="fas fa-map-marker-alt text-red-500 mr-1"></i>
                    <span class="font-medium">Venue:</span>&nbsp;<?= htmlspecialchars($row['venue']) ?>
                </div>

                <?php if ($row['team1_score'] !== null && $row['team2_score'] !== null): ?>
                    <div class="flex justify-center items-center mt-3 mb-1 text-blue-600 font-semibold text-sm">
                        <i class="fas fa-chart-line mr-1"></i> <?= $row['team1_score'] ?> - <?= $row['team2_score'] ?>
                    </div>

                    <div class="text-center mt-1 font-bold text-sm 
                    <?= strpos($winner, 'Draw') !== false ? 'text-yellow-600' : 'text-green-600' ?>">
                        <?php if (strpos($winner, 'Draw') !== false): ?>
                            <i class="fas fa-handshake mr-1"></i> <?= $winner ?>
                        <?php else: ?>
                            <i class="fas fa-trophy mr-1 animate-bounce"></i> <?= $winner ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php } ?>
    </div>
</section>


<!------------------------------------------Supporters SECTION-------------------------------->
<?php $fav_team = $_settings->userdata('fav_team'); ?>
<section><?php if ($fav_team): ?>
        <div class="continue p-4">
            <h2 class="text-3xl font-bold text-gray-900 mb-6">Supporters</h2>
            <?php if (!empty($appconfig->__get('sponsors'))): ?>
                <div class="relative w-full overflow-hidden">
                    <div class="relative w-full h-44 overflow-hidden">
                        <div id="sponsorCarousel" class="flex transition-transform duration-700 ease-in-out">
                            <?php foreach ($appconfig->__get('sponsors') as $index => $sponsor): ?>
                                <?php if (isset($sponsor['team_id']) && $sponsor['team_id'] == $fav_team): ?>
                                    <div class="w-full flex-shrink-0 flex justify-center items-center">
                                        <div class="w-40 h-40 bg-white shadow-lg rounded-xl border border-gray-300 overflow-hidden flex-shrink-0 sponsor-item transform hover:scale-105 transition duration-300">
                                            <div class="w-full h-full relative">
                                                <img src="./uploads/sponsor_logos/<?php echo htmlspecialchars($sponsor['photo']); ?>" alt="<?php echo htmlspecialchars($sponsor['name']); ?> Logo" class="w-full h-full object-cover">
                                                <div class="absolute glass bottom-0 w-full text-center bg-base-300 bg-opacity-50">
                                                    <p class="text-white font-bold uppercase <?php echo $sponsor['party']; ?> <?php echo strlen($sponsor['name']) > 10 ? 'animate-marquee' : ''; ?>">
                                                        <?php echo strtoupper(htmlspecialchars($sponsor['name'])); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Indicators (Fixed) -->
                <div class="flex w-full justify-center gap-2 py-2" id="sponsorIndicators">
                    <?php foreach ($appconfig->__get('sponsors') as $index => $sponsor): ?>
                        <button class="w-3 h-3 rounded-full transition-all duration-300 ease-in-out sponsor-indicator <?php echo $index === 0 ? 'bg-success' : 'bg-gray-300'; ?>" data-index="<?php echo $index; ?>" aria-label="Sponsor <?php echo $index + 1; ?>"></button>
                    <?php endforeach; ?>
                </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                let currentSlide = 0;
                const carousel = document.getElementById("sponsorCarousel");
                const slides = document.querySelectorAll("#sponsorCarousel > div");
                const indicators = document.querySelectorAll(".sponsor-indicator");
                const totalSlides = slides.length;

                function showSlide(index) {
                    let translateValue = `translateX(-${index * 100}%)`;
                    carousel.style.transform = translateValue;

                    indicators.forEach((indicator, i) => {
                        indicator.classList.toggle("bg-success", i === index);
                        indicator.classList.toggle("bg-gray-300", i !== index);
                    });
                }

                function nextSlide() {
                    currentSlide = (currentSlide + 1) % totalSlides;
                    showSlide(currentSlide);
                }

                // Auto-slide every 3 seconds
                let autoSlideInterval = setInterval(nextSlide, 3000);

                // Click indicator to switch slide
                indicators.forEach((indicator, i) => {
                    indicator.addEventListener("click", function() {
                        currentSlide = i;
                        showSlide(currentSlide);
                        clearInterval(autoSlideInterval);
                        autoSlideInterval = setInterval(nextSlide, 3000);
                    });
                });

                showSlide(currentSlide);
            });
        </script>
    <?php else: ?>
        <p class="text-gray-500 text-lg text-center">No sponsors available</p>
    <?php endif; ?>
    </div>
<?php endif; ?>

</section>