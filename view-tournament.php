<?php
require_once('config.php');

$tournamentId = isset($_GET['id']) ? (int)$_GET['id'] : -1;
$tournament = null;
$gallery = [];
$teams = [];

if ($tournamentId > -1) {
    $stmt = $conn->prepare("SELECT * FROM tournaments WHERE id = ?");
    $stmt->bind_param("i", $tournamentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $tournament = $result->fetch_assoc();
    $stmt->close();

    if ($tournament) {
        $stmt = $conn->prepare("SELECT * FROM tournament_gallery WHERE tournament_id = ?");
        $stmt->bind_param("i", $tournamentId);
        $stmt->execute();
        $gallery = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}
?>

<?php if ($tournament): ?>
    <div class="bg-base-300 p-4 overflow-hidden">
        <h1 class="text-3xlfont-bold text-center text-primary mb-6 animate-marquee">
            <?php echo htmlspecialchars($tournament['name']); ?>
        </h1>

        <div class="grid gap-6 md:grid-cols-2">
            <div class="space-y-2 bg-base-200 p-4 rounded-xl">
                <p><strong>📍 Location:</strong> <?= htmlspecialchars($tournament['location']); ?></p>
                <p><strong>🗓 Start:</strong> <?= htmlspecialchars($tournament['start_date']); ?></p>
                <p><strong>🏁 End:</strong> <?= htmlspecialchars($tournament['end_date']); ?></p>
            </div>
            <div class="space-y-2 bg-base-200 p-4 rounded-xl">
                <p><strong>📝 Description:</strong><br><?= nl2br(htmlspecialchars($tournament['description'])); ?></p>
                <p><strong>📋 Protocols:</strong><br><?= nl2br(htmlspecialchars($tournament['protocols'])); ?></p>
            </div>
            <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-4 bg-gradient-to-br from-gray-50 to-white p-6 rounded-2xl shadow-lg">
                <div class="bg-white rounded-xl shadow hover:shadow-xl transition duration-300 p-4 text-center border border-green-100">
                    <p class="text-xl font-bold text-green-600 mb-1 flex items-center justify-center gap-1">🥇 First Prize</p>
                    <p class="text-gray-800 text-lg"><?= htmlspecialchars($tournament['first_prize']); ?></p>
                </div>
                <div class="bg-white rounded-xl shadow hover:shadow-xl transition duration-300 p-4 text-center border border-yellow-100">
                    <p class="text-xl font-bold text-yellow-500 mb-1 flex items-center justify-center gap-1">🥈 Second Prize</p>
                    <p class="text-gray-800 text-lg"><?= htmlspecialchars($tournament['second_prize']); ?></p>
                </div>
                <div class="bg-white rounded-xl shadow hover:shadow-xl transition duration-300 p-4 text-center border border-gray-200">
                    <p class="text-xl font-bold text-gray-500 mb-1 flex items-center justify-center gap-1">🥉 Third Prize</p>
                    <p class="text-gray-800 text-lg"><?= htmlspecialchars($tournament['third_prize']); ?></p>
                </div>
            </div>

        </div>

        <!-- Gallery -->
        <h2 class="text-2xl font-bold mt-8 bg-base-200 mb-4 border-b pb-2 text-gray-800">🎞 Gallery</h2>
        <div id="gallery" class="grid grid-cols-2 sm:grid-cols-3 bg-base-200 gap-3 max-h-80 overflow-y-auto pr-2">
            <?php if (!empty($gallery)): ?>
                <?php foreach ($gallery as $media):
                    $file = htmlspecialchars($media['media']);
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                ?>
                    <div class="relative group">
                        <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                            <img src="<?= $file ?>" class="w-full h-40 object-cover rounded-lg shadow-md">
                        <?php elseif (in_array($ext, ['mp4', 'webm', 'ogg'])): ?>
                            <video controls class="w-full h-40 object-cover rounded-lg shadow-md">
                                <source src="<?= $file ?>" type="video/<?= $ext ?>">
                            </video>
                        <?php endif; ?>

                        <!-- Like Button -->
                        <button class="absolute bottom-2 right-2">
                            <label class="swap">
                            <input type="checkbox" />
                            <div class="swap-off"><i class="fas fa-heart text-gray-400 text-lg"></i></div>
                            <div class="swap-on"><i class="fas fa-heart text-red-500 text-lg"></i></div>
                        </label>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-gray-500 col-span-full">No media found.</p>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <p class="text-center text-red-600 font-medium mt-10">Tournament not found.</p>
<?php endif; ?>
 
 
