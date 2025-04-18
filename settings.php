<?php
$user_id = $_settings->userdata('id');
$fav_team_id = $_settings->userdata('fav_team') ?? null;
$fav_team_name = "Not Selected";
$is_admin = false;
$players = [];
$normal_users = [];

// Check if user selected a team (has a fav_team_id)
if ($fav_team_id) {
    // Get team name directly based on fav_team_id
    $stmt = $conn->prepare("SELECT name FROM teams WHERE id = ?");
    $stmt->bind_param("i", $fav_team_id);
    $stmt->execute();
    $stmt->bind_result($team_name);
    if ($stmt->fetch()) {
        $fav_team_name = $team_name;
    }
    $stmt->close();

    // Get players (role 2) of the same team
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE fav_team = ? AND role = 2");
    $stmt->bind_param("i", $fav_team_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $players[] = $row;
    }
    $stmt->close();

    // Get normal users (role 3) of the same team
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE fav_team = ? AND role = 3");
    $stmt->bind_param("i", $fav_team_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $normal_users[] = $row;
    }
    $stmt->close();
}
?>


<div class="container bg-base-300">
    <!-- Sticky Header -->
    <div class="sticky top-0 bg-base-300 shadow-sm" style="z-index: 99;">
        <div class="p-3">
            <h1 class="text-lg font-bold text-center">Settings</h1>
        </div>
    </div>

    <!-- Profile Section -->
    <div class="mx-4 mt-6 rounded-2xl bg-base-100 border border-base-300  p-4 flex items-center justify-between gap-4">
        <?php if ($_settings->userdata('id') > 0 && $_settings->userdata('status') === 1): ?>
            <?php
            $user_id = $_settings->userdata('id');
            $user_name = htmlspecialchars(ucwords($_settings->userdata('name')), ENT_QUOTES, 'UTF-8');
            $user_image = htmlspecialchars($_settings->userdata('image'), ENT_QUOTES, 'UTF-8');
            $user_role = $_settings->userdata('role');
            $fav_team_name = $fav_team_id ? htmlspecialchars($fav_team_name, ENT_QUOTES, 'UTF-8') : null;
            ?>

            <!-- Left: Avatar + Name + Role Badge -->
            <div class="flex items-center gap-4 flex-1">
                <div class="avatar">
                    <div class="w-14 rounded-full ring ring-primary ring-offset-base-100 ring-offset-2 shadow-md">
                        <img src="./uploads/users/<?= $user_image ?>" alt="Profile" class="object-cover" />
                    </div>
                </div>
                <div class="flex flex-col">
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-semibold text-base-content capitalize"><?= $user_name ?></h2>

                        <!-- User Role Badge -->
                        <?php if ($user_role == 1): ?>
                            <span class="badge badge-warning text-xs px-2 py-1">
                                Admin
                            </span>
                        <?php elseif ($user_role == 2): ?>
                            <span class="badge badge-primary text-xs px-2 py-1">
                                Player
                            </span>
                        <?php elseif ($user_role == 3): ?>
                            <span class="badge badge-secondary text-xs px-2 py-1">
                                User
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Team Name (if available) -->
                    <?php if ($fav_team_name): ?>
                        <h2 class="text-xs text-accent mt-1"><?= $fav_team_name ?></h2>
                    <?php elseif (!$fav_team_id): ?>
                        <a onclick="showModal('chooseTeamModal')" class="btn btn-sm mt-2 px-6 py-2 text-white bg-gradient-to-r from-[#6a11cb] to-[#2575fc] rounded-full hover:from-[#5a0fb6] hover:to-[#1e63e0] focus:ring-4 focus:ring-[#6a11cb]/30 transition-all duration-300 ease-in-out shadow-lg flex items-center justify-center relative">
                            <!-- Premium Badge as Indicator -->
                            <span class="inline-block w-3 h-3 rounded-full bg-red-500 absolute top-0 right-0 ring-2 ring-white animate-bounce"></span>

                            <span class="font-semibold">Select Team</span>
                        </a>


                    <?php endif; ?>
                </div>
            </div>


            <!-- Right: Buttons (Vertically Aligned) -->
            <div class="flex flex-col items-center gap-3">
                <button onclick="showModal('viewProfileModal')" class="tooltip tooltip-bottom text-success hover:scale-110 transition" data-tip="View Profile">
                    <i class="fas fa-eye text-lg"></i>
                </button>
                <button id="logout-btn" class="tooltip tooltip-bottom text-error hover:scale-110 transition" data-tip="Logout">
                    <i class="fa-solid fa-right-from-bracket text-lg"></i>
                </button>
            </div>
        <?php else: ?>
            <!-- Not Logged In -->
            <div class="flex justify-between items-center w-full">
                <h2 class="text-base sm:text-lg font-semibold text-base-content">Join in to make it yours!</h2>
                <button class="btn btn-sm btn-primary px-5" onclick="showModal('loginModal')">Sign In</button>
            </div>
        <?php endif; ?>
    </div>
    <!-- Settings List -->
    <?php if ($_settings->userdata('id') > 0 && $_settings->userdata('status') === 1): ?>

        <div class="card bg-base-100  mx-4 mt-4">
            <div class="p-4">
                <h2 class="text-lg font-bold">Settings</h2>
                <ul class="mt-3">
                    <li>
                        <button id="updateProfileLink" class="btn btn-link no-underline hove:underline" onclick="showModal('updateProfileModal')">
                            <i class="fa-solid fa-user-edit me-2 pe-3 text-success text-xl"></i>Update Profile
                        </button>
                    </li>
                    <li>
                        <button id="changePasswordLink" class="btn btn-link text-primary no-underline hove:underline" onclick="showModal('changePasswordModal')">
                            <i class="fa-solid fa-key me-2 pe-4 text-success text-xl"></i>Change Password
                        </button>
                    </li>
                    <li>
                        <button id="deleteAccountLink" class="btn btn-link text-error no-underline hove:underline">
                            <i class="fa-solid fa-trash me-2 pe-4 text-error text-xl"></i>Delete Account
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <!-- Notifications -->
    <div class="card bg-base-100  mx-4 mt-4">
        <div class="p-4">
            <h2 class="text-lg font-bold">Notifications</h2>
            <ul class="mt-3">
                <li>
                    <button id="notificationSettingsLink" class="btn btn-link text-primary no-underline hove:underline" onclick="showModal('notificationSettingsModal')">
                        <i class="fa-solid fa-bell me-2 pe-4 text-success text-xl"></i>Notification Settings
                    </button>
                </li>
                <li>
                    <button id="privacySettingsLink" class="btn btn-link text-primary no-underline hove:underline" onclick="showModal('privacySettingsModal')">
                        <i class="fa-solid fa-user-shield me-2 pe-2 text-success text-xl"></i>Privacy Settings
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Support -->
    <div class="card bg-base-100 mx-4 mt-4">
        <div class="p-4">
            <h2 class="text-lg font-bold">Support</h2>
            <ul class="mt-3 space-y-2">
                <!-- credits -->
                <li>
                    <button id="creditsLink" class="btn btn-link text-primary no-underline hover:underline flex items-center gap-2"
                        onclick="showModal('creditsModal')">
                        <i class="fa-solid fa-users text-success text-xl"></i>
                        Credits
                    </button>
                </li>

                <!-- Contact Support -->
                <li>
                    <button id="contactSupportLink" class="btn btn-link text-primary no-underline hover:underline flex items-center gap-2"
                        onclick="showModal('contactSupportModal')">
                        <i class="fa-solid fa-headset text-success text-xl"></i>
                        Contact Support
                    </button>
                </li>

                <!-- FAQ -->
                <li>
                    <button id="faqLink" class="btn btn-link text-primary no-underline hover:underline flex items-center gap-2"
                        onclick="showModal('faqModal')">
                        <i class="fa-solid fa-circle-question text-success text-xl"></i>
                        FAQ
                    </button>
                </li>

                <!-- Feedback -->
                <li>
                    <button id="feedbackLink" class="btn btn-link text-primary no-underline hover:underline flex items-center gap-2"
                        onclick="showModal('feedbackModal')">
                        <i class="fa-solid fa-comment-dots text-success text-xl"></i>
                        Feedback
                    </button>
                </li>

                <!-- Report Bug -->
                <li>
                    <button id="reportBugLink" class="btn btn-link text-primary no-underline hover:underline flex items-center gap-2"
                        onclick="showModal('reportBugModal')">
                        <i class="fa-solid fa-bug text-success text-xl"></i>
                        Report a Bug
                    </button>
                </li>

                <!-- About Us -->
                <li>
                    <button id="aboutUsLink" class="btn btn-link text-primary no-underline hover:underline flex items-center gap-2"
                        onclick="showModal('aboutUsModal')">
                        <i class="fa-solid fa-info-circle text-success text-xl"></i>
                        About Us
                    </button>
                </li>
            </ul>
        </div>
    </div>


    <!-- Legal -->
    <div class="card bg-base-100  mx-4 mt-4">
        <div class="p-4">
            <h2 class="text-lg font-bold">Legal</h2>
            <ul class="mt-3 space-y-2">
                <li>
                    <button id="termsOfServiceLink" class="btn btn-link text-primary no-underline hove:underline" onclick="showModal('termsOfServiceModal')">
                        <i class="fas fa-file-alt me-2 pe-3 text-success text-xl"></i>Terms of Service
                    </button>
                </li>
                <li>
                    <button id="privacyPolicyLink" class="btn btn-link text-primary no-underline hove:underline" onclick="showModal('privacyPolicyModal')">
                        <i class="fas fa-shield-alt me-2 pe-2 text-success text-xl"></i>Privacy Policy
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Footer -->
    <div class="mx-1 mt-4 mb-4 text-center text-gray-500 text-sm">
        <p>© 2023 TCC. All rights reserved.</p>
        <p>Version 1.0.0</p>
        <p>ThuraiyurCricketCouncil</p>
    </div>
</div>

<!-- View Profile Modal -->
<div id="viewProfileModal" class="fixed inset-0 flex items-end justify-center z-[99] hidden transition-all duration-300">
    <div class="relative bg-white rounded-t-2xl shadow-2xl border border-gray-200 px-8 py-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">

        <!-- Close Button -->
        <button class="absolute right-4 top-4 text-gray-600 hover:text-red-500 transition-all" onclick="closeModal('viewProfileModal')">
            <i class="fa-solid fa-xmark text-3xl"></i>
        </button>

        <!-- Profile Header -->
        <div class="flex flex-col items-center space-y-4">
            <div class="avatar">
                <div class="w-28 h-28 rounded-full ring ring-indigo-500 ring-offset-base-100 ring-offset-4 shadow-lg transform hover:scale-105 transition-all duration-300">
                    <img src="./uploads/users/<?= htmlspecialchars($_settings->userdata('image'), ENT_QUOTES, 'UTF-8'); ?>" alt="Profile" class="object-cover rounded-full">
                </div>
            </div>
            <h2 class="text-2xl font-extrabold text-gray-900"><?= htmlspecialchars($_settings->userdata('name'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <p class="text-gray-500 text-sm"><?= htmlspecialchars($_settings->userdata('email'), ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="text-gray-500 text-sm"><?= htmlspecialchars($_settings->userdata('mobile'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <!-- Profile Details -->
        <div class="mt-6 space-y-4 bg-white/60 px-6 py-4 rounded-lg shadow-md border border-gray-200">
            <div class="flex justify-between items-center">
                <span class="text-gray-700 font-semibold">Role:</span>
                <span class="badge badge-primary px-3 py-1 rounded-lg text-white bg-gradient-to-r from-purple-600 to-indigo-500 shadow-md">
                    <?= $_settings->userdata('role') == 1 ? 'Admin' : 'User'; ?>
                </span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-700 font-semibold">Status:</span>
                <span class="badge <?= $_settings->userdata('status') == 1 ? 'bg-green-500' : 'bg-red-500'; ?> px-3 py-1 rounded-lg text-white shadow-md">
                    <?= $_settings->userdata('status') == 1 ? 'Active' : 'Inactive'; ?>
                </span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-700 font-semibold">Favorite Team:</span>
                <span class="badge badge-info px-3 rounded-lg text-white bg-gradient-to-r from-blue-500 to-cyan-400 shadow-md w-30 whitespace-nowrap overflow-hidden block relative ">
                    <?php
                    $fav_team_id = $_settings->userdata('fav_team');
                    $team_name = "Not Selected";

                    if ($fav_team_id) {
                        $stmt = $conn->prepare("SELECT name FROM teams WHERE id = ?");
                        $stmt->bind_param("i", $fav_team_id);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($row = $result->fetch_assoc()) {
                            $team_name = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
                        }

                        $stmt->close();
                    }
                    ?>
                    <span class="animate-marquee text-base-800"> <?= $team_name; ?> </span>
                </span>
            </div>
        </div>
    </div>
</div>


<!-- change password model -->
<div id="changePasswordModal" class="fixed inset-0 flex items-end  justify-center z-[99] hidden">
    <div class="relative bg-white rounded-t-2xl shadow-2xl border border-gray-200 px-8 py-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <button class="absolute right-4 top-4 text-gray-500 hover:text-red-500 transition-all" onclick="closeModal('changePasswordModal')">
            <i class="fa-solid fa-xmark text-2xl"></i>
        </button>

        <h3 class="text-3xl font-semibold text-center mt-3 text-gray-900">Change Password</h3>
        <p class="text-gray-500 text-center text-sm mb-6">Update your account password</p>

        <!-- Change Password Form -->
        <form id="change-password-form" class="space-y-5" autocomplete="off" onsubmit="return validateChangePasswordForm()">
            <div>
                <label class="block text-gray-700 font-medium">Current Password</label>
                <input type="password" name="current_password" placeholder="Enter current password" required
                    class="w-full input input-bordered py-3 px-4 rounded-lg shadow-sm border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
            </div>

            <div>
                <label class="block text-gray-700 font-medium">New Password</label>
                <input type="password" name="new_password" placeholder="Enter new password" required
                    class="w-full input input-bordered py-3 px-4 rounded-lg shadow-sm border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
            </div>

            <div>
                <label class="block text-gray-700 font-medium">Confirm New Password</label>
                <input type="password" name="confirm_new_password" placeholder="Confirm new password" required
                    class="w-full input input-bordered py-3 px-4 rounded-lg shadow-sm border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
            </div>

            <button type="submit"
                class="btn btn-primary w-full py-3 text-white bg-gradient-to-r from-blue-600 to-indigo-700 rounded-lg shadow-lg hover:opacity-90 transition-all">
                Change Password
            </button>
        </form>
    </div>
</div>

<!-- login modal -->
<div id="loginModal" class="fixed inset-0 flex items-end justify-center z-[99] bg-black bg-opacity-40 hidden sm:items-center   sm:p-4">
    <div class="relative bg-white rounded-t-3xl sm:rounded-2xl w-full sm:w-[400px] p-6 shadow-xl animate__animated animate__fadeInUp">
        <!-- Close Button -->
        <button class="absolute right-4 top-4 text-gray-500 hover:text-red-500" onclick="closeModal('loginModal')">
            <i class="fa-solid fa-xmark text-2xl"></i>
        </button>

        <!-- Profile Icon -->
        <div class="flex justify-center mb-4">
            <div class="w-20 h-20 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center shadow-lg">
                <i class="fa-solid fa-user text-white text-3xl"></i>
            </div>
        </div>

        <h2 class="text-xl font-semibold text-center text-gray-800">Welcome Back</h2>
        <p class="text-gray-500 text-center text-sm mb-6">Login to continue using the application</p>

        <!-- Login Form -->
        <form id="login-form" class="space-y-4" autocomplete="off">
            <!-- Mobile Input -->
            <div>
                <label class="block text-gray-600 text-sm mb-1">Mobile Number</label>
                <div class="relative">
                    <i class="fa-solid fa-phone absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 z-[99]"></i>
                    <input type="tel" name="username" placeholder="Enter mobile number"
                        class="pl-10 pr-4 py-2 w-full input input-bordered bg-gray-50 rounded-xl border-gray-300 text-sm focus:ring-2 focus:ring-blue-500"
                        pattern="[0-9]{10}" maxlength="10"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                        required>
                </div>
            </div>

            <!-- Password Input -->
            <div>
                <label class="block text-gray-600 text-sm mb-1">Password</label>
                <div class="relative">
                    <!-- Lock Icon -->
                    <i class="fa-solid fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 z-10"></i>

                    <!-- Password Input -->
                    <input type="password" name="password" id="passwordInput" placeholder="Enter password"
                        class="pl-10 pr-10 py-2 w-full input input-bordered bg-gray-50 rounded-xl border-gray-300 text-sm focus:ring-2 focus:ring-blue-500"
                        required minlength="6" maxlength="10"
                        pattern="[A-Za-z0-9]{6,10}"
                        title="Password must be 6 to 10 characters long and contain only letters or numbers">

                    <!-- Eye Icon -->
                    <button type="button" onclick="togglePassword1()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 focus:outline-none z-10">
                        <i id="eyeIcon" class="fa-solid fa-eye"></i>
                    </button>
                    <script>
                        function togglePassword1() {
                            const passwordInput = document.getElementById("passwordInput");
                            const eyeIcon = document.getElementById("eyeIcon");

                            if (passwordInput.type === "password") {
                                passwordInput.type = "text";
                                eyeIcon.classList.remove("fa-eye");
                                eyeIcon.classList.add("fa-eye-slash");
                            } else {
                                passwordInput.type = "password";
                                eyeIcon.classList.remove("fa-eye-slash");
                                eyeIcon.classList.add("fa-eye");
                            }
                        }
                    </script>
                </div>
            </div>


            <!-- Forgot Password -->
            <div class="flex justify-end text-sm">
                <a href="#" class="text-blue-500 hover:underline">Forgot Password?</a>
            </div>

            <!-- Login Button -->
            <button type="submit"
                class="btn w-full py-2 text-white bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl font-semibold shadow-md hover:scale-105 active:scale-95 transition-all">
                Login
            </button>
        </form>

        <!-- OTP Login Button -->
        <div class="text-center mt-4">
            <button onclick="showModal('otpLoginModal')" class="text-blue-500 text-sm font-medium hover:underline">
                Login with OTP
            </button>
        </div>

        <!-- Social Login -->
        <div class="text-center mt-6 text-gray-400 text-sm">Or continue with</div>
        <div class="flex justify-center gap-6 mt-3">
            <button title="Facebook">
                <i class="fa-brands fa-facebook-f text-blue-600 text-xl"></i>
            </button>
            <button title="Google">
                <i class="fa-brands fa-google text-red-500 text-xl"></i>
            </button>
            <button title="Apple">
                <i class="fa-brands fa-apple text-gray-900 text-2xl"></i>
            </button>
        </div>

        <!-- Register Link -->
        <p class="text-center mt-6 text-gray-500 text-sm">
            Don’t have an account?
            <a href="#" onclick="showModal('registerModal')" class="text-blue-500 hover:underline font-medium">Register</a>
        </p>
    </div>
</div>
<!-- Premium OTP Login Modal -->
<div id="otpLoginModal"
    class="fixed inset-0 bg-black bg-opacity-40 flex items-end sm:items-center justify-center z-[9999] hidden">
    <div class="relative bg-white rounded-t-3xl shadow-2xl border border-gray-100 w-full sm:w-96 p-6 sm:p-8 animate-slide-up">
        <!-- Close Button -->
        <button class="absolute right-4 top-4 text-gray-400 hover:text-red-500 transition"
            onclick="closeModal('otpLoginModal')">
            <i class="fa-solid fa-xmark text-xl sm:text-2xl"></i>
        </button>

        <!-- Header -->
        <div class="text-center mt-4">
            <h2 class="text-2xl font-bold text-gray-800">Login via OTP</h2>
            <p class="text-sm text-gray-500 mt-1">Protected by Thuraiyur Cricket Council’s advanced security systems</p>
        </div>

        <!-- Step 1: Mobile Number Form -->
        <form id="mobile-form" class="mt-6 space-y-4" autocomplete="off">
            <div>
                <label class="text-gray-600 font-medium text-sm">Mobile Number</label>
                <input type="tel" name="mobile" placeholder="e.g. 9025826323"
                    pattern="[0-9]{10}" maxlength="10"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                    class="input input-bordered w-full mt-1 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                    required>
            </div>
            <button type="submit"
                class="btn btn-block bg-blue-500 text-white font-semibold rounded-xl shadow-md hover:scale-105 active:scale-95 transition-transform duration-200">
                Send OTP
            </button>
        </form>


        <!-- Step 2: OTP Form -->
        <form id="otp-form" class="mt-6 space-y-4 hidden" autocomplete="off">
            <div>
                <label class="text-gray-600 font-medium text-sm mb-2 block">OTP</label>
                <div id="otp-inputs" class="flex justify-between gap-2">
                    <input type="tel" maxlength="1" class="otp-box" />
                    <input type="tel" maxlength="1" class="otp-box" />
                    <input type="tel" maxlength="1" class="otp-box" />
                    <input type="tel" maxlength="1" class="otp-box" />
                    <input type="tel" maxlength="1" class="otp-box" />
                    <input type="tel" maxlength="1" class="otp-box" />
                </div>
                <input type="hidden" name="otp" id="otp-hidden">
                <input type="hidden" name="mobile_hidden">
            </div>
            <button type="submit"
                class="btn btn-block bg-green-600 text-white font-semibold rounded-xl shadow-md hover:scale-105 active:scale-95 transition-transform duration-200">
                Verify OTP
            </button>
        </form>

        <style>
            .otp-box {
                width: 3rem;
                height: 3rem;
                text-align: center;
                font-size: 1.5rem;
                border: 2px solid #d1d5db;
                border-radius: 0.75rem;
                background-color: #f9fafb;
                outline: none;
                transition: border 0.3s, box-shadow 0.3s;
            }

            .otp-box:focus {
                border-color: #6366f1;
                box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
            }
        </style>

        <script>
            const otpBoxes = document.querySelectorAll('.otp-box');
            const otpHiddenInput = document.getElementById('otp-hidden');

            otpBoxes.forEach((box, index) => {
                box.addEventListener('input', () => {
                    if (box.value.length === 1 && index < otpBoxes.length - 1) {
                        otpBoxes[index + 1].focus();
                    }
                    updateHiddenOTP();
                });

                box.addEventListener('keydown', (e) => {
                    if (e.key === "Backspace" && box.value === '' && index > 0) {
                        otpBoxes[index - 1].focus();
                    }
                });
            });

            function updateHiddenOTP() {
                const otpValue = Array.from(otpBoxes).map(box => box.value).join('');
                otpHiddenInput.value = otpValue;
            }
        </script>

    </div>
    <script>
        $(document).ready(function() {
            // Step 1: Send OTP
            $('#mobile-form').submit(function(e) {
                e.preventDefault();
                const mobile = $('#mobile-form [name="mobile"]').val().trim();

                if (!mobile) {
                    alert_toast("Mobile number is required!", "error");
                    return;
                }

                $.ajax({
                    url: "classes/Login.php?f=send_otp",
                    method: "POST",
                    data: {
                        mobile
                    },
                    dataType: "json",
                    beforeSend: start_loader,
                    success: function(resp) {
                        if (resp.status === "success") {
                            alert_toast("OTP sent successfully", "success");

                            // Hide mobile form, show OTP form
                            $('#mobile-form').addClass('hidden');
                            $('#otp-form').removeClass('hidden');

                            // Pass mobile to OTP form (hidden field)
                            $('#otp-form [name="mobile_hidden"]').val(mobile);
                        } else {
                            alert_toast(resp.msg || "Failed to send OTP", "error");
                        }
                        end_loader();
                    },
                    error: function() {
                        alert_toast("An error occurred", "error");
                        end_loader();
                    }
                });
            });

            // Step 2: Verify OTP
            $('#otp-form').submit(function(e) {
                e.preventDefault();
                const mobile = $('#otp-form [name="mobile_hidden"]').val().trim();
                const otp = $('#otp-form [name="otp"]').val().trim();

                if (!otp) {
                    alert_toast("OTP is required!", "error");
                    return;
                }

                $.ajax({
                    url: "classes/Login.php?f=verify_otp",
                    method: "POST",
                    data: {
                        mobile,
                        otp
                    },
                    dataType: "json",
                    beforeSend: start_loader,
                    success: function(resp) {
                        if (resp.status === "success") {
                            alert_toast("OTP verified successfully. Logging in...", "success");
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            alert_toast(resp.msg || "Invalid OTP", "error");
                        }
                        end_loader();
                    },
                    error: function() {
                        alert_toast("An error occurred", "error");
                        end_loader();
                    }
                });
            });
        });
    </script>
</div>


<!-- Register Modal -->
<div id="registerModal" class="fixed inset-0 flex items-end justify-center z-[99] hidden">
    <div class="relative bg-white rounded-t-2xl shadow-2xl border border-gray-200 px-8   w-full  max-h-[90vh] overflow-y-auto">
        <!-- Sticky Header -->
        <div class="sticky top-0  w-full bg-white z-10  p-2">
            <!-- Close Button -->
            <button class="absolute right-0 top-6 text-gray-500 hover:text-red-500 transition-all" onclick="closeModal('registerModal')">
                <i class="fa-solid fa-xmark text-2xl"></i>
            </button>

            <h3 class="text-3xl font-semibold text-center mt-3 text-gray-900">Create Account</h3>
            <p class="text-gray-500 text-center text-sm mb-6">Join us and start using the application</p>
        </div>

        <form id="register-form" enctype="multipart/form-data" class="space-y-5" autocomplete="off" novalidate>
            <!-- Profile Upload -->
            <div class="flex justify-center">
                <label for="profile_image" class="relative cursor-pointer group">
                    <div class="w-28 h-28 bg-gray-100 rounded-full flex items-center justify-center shadow-lg overflow-hidden border-4 border-white">
                        <img id="profilePreview" src="./assets/default.webp" alt="Profile" class="w-full h-full object-cover">
                    </div>
                    <input type="file" id="profile_image" name="profile_image" accept="image/*" class="hidden" onchange="previewImage(event)">
                    <i class="fa-solid fa-camera absolute bottom-2 right-2 text-white bg-blue-600 p-2 rounded-full text-sm shadow-md transition-all group-hover:bg-indigo-600"></i>
                </label>
            </div>

            <!-- Full Name -->
            <div>
                <label class="block text-gray-700 font-medium">Full Name</label>
                <input type="text" name="name" placeholder="Enter your full name" required
                    class="w-full input input-bordered py-3 px-4 rounded-lg shadow-sm border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
            </div>

            <!-- Email -->
            <div>
                <label class="block text-gray-700 font-medium">Email</label>
                <input type="email" name="email" placeholder="Enter your email" required
                    class="w-full input input-bordered py-3 px-4 rounded-lg shadow-sm border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
            </div>

            <!-- Mobile Number -->
            <div>
                <label class="block text-gray-700 font-medium mb-1">Mobile Number</label>
                <input type="tel" name="mobile" placeholder="Enter your mobile number" required
                    pattern="[0-9]{10}" maxlength="10"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                    title="Mobile number must be 10 digits"
                    class="w-full input input-bordered py-3 px-4 rounded-lg shadow-sm border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm">
            </div>

            <!-- Password -->
            <div>
                <label class="block text-gray-700 font-medium mb-1">Password</label>
                <div class="relative">
                    <input type="password" name="password" id="passwordInput1" placeholder="Enter password" required
                        minlength="6" maxlength="10"
                        pattern="[A-Za-z0-9]{6,10}"
                        title="Password must be 6 to 10 characters with only letters or numbers"
                        class="w-full input input-bordered py-3 px-4 pr-10 rounded-lg shadow-sm border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm">
                    <i class="fa-solid fa-eye absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 cursor-pointer"
                        onclick="togglePassword('passwordInput', this)"></i>
                </div>
            </div>

            <!-- Confirm Password -->
            <div>
                <label class="block text-gray-700 font-medium mb-1">Confirm Password</label>
                <div class="relative">
                    <input type="password" name="confirm_password" id="confirmPasswordInput1" placeholder="Confirm your password" required
                        minlength="6" maxlength="10"
                        pattern="[A-Za-z0-9]{6,10}"
                        title="Password must be 6 to 10 characters with only letters or numbers"
                        oninput="checkPasswordMatch()"
                        class="w-full input input-bordered py-3 px-4 pr-10 rounded-lg shadow-sm border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm">
                    <i class="fa-solid fa-eye absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 cursor-pointer"
                        onclick="togglePassword('confirmPasswordInput', this)"></i>
                </div>
                <p id="passwordMatchMessage" class="text-sm mt-1 font-medium"></p>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                class="btn btn-primary w-full py-3 text-white bg-gradient-to-r from-blue-600 to-indigo-700 rounded-lg shadow-lg hover:opacity-90 transition-all">
                Register
            </button>
        </form>

        <!-- Login Redirect -->
        <div class="text-center mt-5">
            <p class="text-sm text-gray-500">Already have an account?
                <a href="#" class="text-blue-600 font-medium hover:underline transition-all" onclick="showModal('loginModal')">Login</a>
            </p>
        </div>
    </div>
    <script>
        function togglePassword(fieldId, iconElement) {
            const input = document.getElementById(fieldId);
            input.type = input.type === "password" ? "text" : "password";
            iconElement.classList.toggle("fa-eye");
            iconElement.classList.toggle("fa-eye-slash");
        }

        function checkPasswordMatch() {
            const password = document.getElementById("passwordInput1").value;
            const confirmPassword = document.getElementById("confirmPasswordInput1").value;
            const message = document.getElementById("passwordMatchMessage");

            if (confirmPassword.length > 0) {
                if (password === confirmPassword) {
                    message.textContent = "Passwords match";
                    message.className = "text-sm mt-1 font-medium text-green-600";
                } else {
                    message.textContent = "Passwords do not match";
                    message.className = "text-sm mt-1 font-medium text-red-500";
                }
            } else {
                message.textContent = "";
            }
        }

        function previewImage(event) {
            const input = event.target;
            const reader = new FileReader();

            reader.onload = function() {
                document.getElementById("profilePreview").src = reader.result;
            };

            if (input.files[0]) {
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Optional: Prevent form submission if passwords do not match
        document.getElementById("register-form").addEventListener("submit", function(e) {
            const pass = document.getElementById("passwordInput1").value;
            const confirm = document.getElementById("confirmPasswordInput1").value;
            if (pass !== confirm) {
                e.preventDefault();
                document.getElementById("passwordMatchMessage").textContent = "Passwords do not match";
                document.getElementById("passwordMatchMessage").className = "text-sm mt-1 font-medium text-red-500";
            }
        });
    </script>

</div>


<!-- Update Profile Modal -->
<div id="updateProfileModal" class="fixed inset-0 flex items-end justify-center z-[99] hidden">
    <div class="relative bg-white rounded-t-2xl shadow-2xl border border-gray-200 px-8 py-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <!-- Close Button -->
        <button class="absolute right-4 tep-4 text-gray-500 hover:text-red-500 transition-all" onclick="closeModal('updateProfileModal')">
            <i class="fa-solid fa-xmark text-2xl"></i>
        </button>

        <h3 class="text-3xl font-semibold text-center mt-3 text-gray-900">Update Profile</h3>
        <p class="text-gray-500 text-center text-sm mb-6">Update your profile details below</p>

        <!-- Update Profile Form -->
        <form id="update-profile-form" enctype="multipart/form-data" class="space-y-5" autocomplete="off" autofill="off">
            <input type="hidden" name="user_id" value="<?= htmlspecialchars($_settings->userdata('id'), ENT_QUOTES, 'UTF-8'); ?>">
            <!-- Profile Upload -->
            <div class="flex justify-center">
                <label for="update_profile_image" class="relative cursor-pointer group">
                    <div class="w-28 h-28 bg-gray-100 rounded-full flex items-center justify-center shadow-lg overflow-hidden border-4 border-white">
                        <img id="updateProfilePreview" src="./uploads/users/<?= htmlspecialchars($_settings->userdata('image'), ENT_QUOTES, 'UTF-8'); ?>" alt="Profile" class="w-full h-full object-cover">
                    </div>
                    <input type="file" id="update_profile_image" name="profile_image" accept="image/*" class="hidden">
                    <i class="fa-solid fa-camera absolute bottom-2 right-2 text-white bg-blue-600 p-2 rounded-full text-sm shadow-md transition-all group-hover:bg-indigo-600"></i>
                </label>
            </div>

            <div>
                <label class="block text-gray-700 font-medium">Full Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($_settings->userdata('name'), ENT_QUOTES, 'UTF-8'); ?>" required
                    class="w-full input input-bordered py-3 px-4 rounded-lg shadow-sm border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
            </div>

            <div>
                <label class="block text-gray-700 font-medium">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($_settings->userdata('email'), ENT_QUOTES, 'UTF-8'); ?>" required
                    class="w-full input input-bordered py-3 px-4 rounded-lg shadow-sm border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
            </div>

            <div>
                <label class="block text-gray-700 font-medium">Mobile Number</label>
                <input type="text" name="mobile" value="<?= htmlspecialchars($_settings->userdata('mobile'), ENT_QUOTES, 'UTF-8'); ?>" required
                    class="w-full input input-bordered py-3 px-4 rounded-lg shadow-sm border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
            </div>

            <button type="submit"
                class="btn btn-primary w-full py-3 text-white bg-gradient-to-r from-blue-600 to-indigo-700 rounded-lg shadow-lg hover:opacity-90 transition-all">
                Update Profile
            </button>
        </form>
    </div>
</div>

<!-- Notification Settings Modal -->
<div id="notificationSettingsModal" class="fixed inset-0 flex items-end justify-center z-[99] hidden">
    <div class="relative bg-white rounded-t-t-2xl shadow-2xl border border-gray-200 px-8 py-6 w-full max-w-lg">
        <!-- Close Button -->
        <button class="absolute right-4 top-4 text-gray-500 hover:text-red-500 transition-all" onclick="closeModal('notificationSettingsModal')">
            <i class="fa-solid fa-xmark text-2xl"></i>
        </button>

        <h3 class="text-3xl font-semibold text-center mt-3 text-gray-900">Notification Settings</h3>
        <p class="text-gray-500 text-center text-sm mb-6">Manage your notification preferences</p>

        <!-- Notification Settings Form -->
        <form id="notification-settings-form" class="space-y-5" autocomplete="off">
            <div class="flex items-center justify-between">
                <label class="text-gray-700 font-medium">Email Notifications</label>
                <input type="checkbox" name="email_notifications" class="toggle toggle-primary" checked>
            </div>

            <div class="flex items-center justify-between">
                <label class="text-gray-700 font-medium">SMS Notifications</label>
                <input type="checkbox" name="sms_notifications" class="toggle toggle-primary" checked>
            </div>

            <div class="flex items-center justify-between">
                <label class="text-gray-700 font-medium">Push Notifications</label>
                <input type="checkbox" name="push_notifications" class="toggle toggle-primary" checked>
            </div>
        </form>
    </div>
</div>

<!-- privacy settings modal -->
<div id="privacySettingsModal" class="fixed inset-0 flex items-end justify-center z-[99] hidden">
    <div class="relative bg-white rounded-t-2xl shadow-2xl border border-gray-200 px-8 py-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <!-- Close Button -->
        <button class="absolute right-4 top-4 text-gray-500 hover:text-red-500 transition-all" onclick="closeModal('privacySettingsModal')">
            <i class="fa-solid fa-xmark text-2xl"></i>
        </button>

        <h3 class="text-3xl font-semibold text-center mt-3 text-gray-900">Privacy Settings</h3>
        <p class="text-gray-500 text-center text-sm mb-6">Manage your privacy preferences</p>

        <!-- Privacy Settings Form -->
        <form id="privacy-settings-form" class="space-y-5" autocomplete="off">
            <div class="flex items-center justify-between">
                <label class="text-gray-700 font-medium">Show Profile to Public</label>
                <input type="checkbox" name="show_profile" class="toggle toggle-primary" checked>
            </div>

            <div class="flex items-center justify-between">
                <label class="text-gray-700 font-medium">Allow Search by Email</label>
                <input type="checkbox" name="search_by_email" class="toggle toggle-primary" checked>
            </div>

            <div class="flex items-center justify-between">
                <label class="text-gray-700 font-medium">Enable Two-Factor Authentication</label>
                <input type="checkbox" name="two_factor_auth" class="toggle toggle-primary" checked>
            </div>
        </form>
    </div>
</div>

<!-- contact support modal -->
<div id="contactSupportModal" class="fixed inset-0 flex items-end justify-center z-[99] hidden transition-all">
    <div class="relative bg-white rounded-t-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col overflow-y-auto">

        <!-- Header -->
        <div class="flex items-center justify-between bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4">
            <h3 class="text-white text-lg font-semibold">Support Chat</h3>
            <button onclick="closeModal('contactSupportModal')" class="text-white hover:text-gray-200 transition-all">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <!-- Chat Messages -->
        <div id="chatBotContainer" class="flex-1 p-4 space-y-3 overflow-y-auto">
            <div class="bg-gray-100 p-3 rounded-lg shadow-sm self-start max-w-[80%]">
                <p class="text-gray-700 text-sm">Hi! How can I assist you today? 😊</p>
                <span class="block text-xs text-gray-400 mt-1">Tcc-Bot • Now</span>
            </div>
        </div>

        <!-- User Input Box -->
        <div class="border-t border-gray-200 bg-white p-3 flex items-center">
            <input type="text" id="userMessage" class="flex-1 bg-gray-100 rounded-full px-4 py-2 text-sm outline-none" placeholder="Type a message...">
            <button onclick="sendUserMessage()" class="ml-3 bg-gradient-to-r from-blue-500 to-purple-500 text-white px-4 py-2 rounded-full shadow-md hover:from-blue-600 hover:to-purple-600 transition-all">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

<!-- FAQ Modal -->
<div id="faqModal" class="fixed inset-0 flex items-end justify-center z-[99] hidden transition-all">
    <div class="relative bg-white rounded-t-2xl shadow-2xl  w-full max-w-lg max-h-[90vh] overflow-y-auto">

        <!-- Header -->
        <div class="sticky top-0 z-[9999] flex items-center justify-between bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4 rounded-t-2xl">
            <h3 class="text-white text-lg font-semibold">📖 Frequently Asked Questions</h3>
            <button onclick="closeModal('faqModal')" class="text-white hover:text-gray-200 transition-all">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <!-- FAQ Content (Accordion Style) -->
        <div class="p-6 space-y-3">
            <div class="collapse collapse-plus border border-gray-200 rounded-lg">
                <input type="checkbox">
                <div class="collapse-title text-gray-800 font-medium">
                    What is this application about?
                </div>
                <div class="collapse-content text-sm text-gray-600">
                    This application helps users manage their profiles, settings, and preferences efficiently.
                </div>
            </div>

            <div class="collapse collapse-plus border border-gray-200 rounded-lg">
                <input type="checkbox">
                <div class="collapse-title text-gray-800 font-medium">
                    How can I update my profile?
                </div>
                <div class="collapse-content text-sm text-gray-600">
                    You can update your profile by navigating to the "Update Profile" section in the settings.
                </div>
            </div>

            <div class="collapse collapse-plus border border-gray-200 rounded-lg">
                <input type="checkbox">
                <div class="collapse-title text-gray-800 font-medium">
                    How do I reset my password?
                </div>
                <div class="collapse-content text-sm text-gray-600">
                    Click on "Forgot Password" on the login page and follow the instructions to reset your password.
                </div>
            </div>

            <div class="collapse collapse-plus border border-gray-200 rounded-lg">
                <input type="checkbox">
                <div class="collapse-title text-gray-800 font-medium">
                    How can I contact support?
                </div>
                <div class="collapse-content text-sm text-gray-600">
                    You can contact support by clicking on the "Contact Support" button in the support section.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Terms of Service Modal -->
<div id="termsOfServiceModal" class="fixed inset-0 flex items-end justify-center z-[99] hidden">
    <div class="relative bg-white rounded-t-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <!-- Close Button -->
        <button class="absolute right-4 top-4 text-gray-500 hover:text-red-500 transition-all" onclick="closeModal('termsOfServiceModal')">
            <i class="fa-solid fa-xmark text-2xl"></i>
        </button>

        <!-- Header -->
        <div class="sticky top-0 z-[9999] bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-t-2xl px-8 py-6">
            <h3 class="text-3xl font-semibold text-center">Terms of Service</h3>
            <p class="text-center text-sm mt-2">Please read these terms carefully before using our application.</p>
        </div>

        <!-- Content -->
        <div class="p-6 space-y-4 text-gray-700">
            <h4 class="text-xl font-semibold">1. Acceptance of Terms</h4>
            <p class="text-sm leading-relaxed">
                By accessing or using our application, you agree to be bound by these terms. If you do not agree, please do not use the application.
            </p>

            <h4 class="text-xl font-semibold">2. User Responsibilities</h4>
            <p class="text-sm leading-relaxed">
                You are responsible for maintaining the confidentiality of your account and password and for restricting access to your device.
            </p>

            <h4 class="text-xl font-semibold">3. Prohibited Activities</h4>
            <p class="text-sm leading-relaxed">
                You agree not to engage in any activity that disrupts or interferes with the application, including unauthorized access or data scraping.
            </p>

            <h4 class="text-xl font-semibold">4. Limitation of Liability</h4>
            <p class="text-sm leading-relaxed">
                We are not liable for any damages arising from your use of the application, including but not limited to data loss or service interruptions.
            </p>

            <h4 class="text-xl font-semibold">5. Changes to Terms</h4>
            <p class="text-sm leading-relaxed">
                We reserve the right to modify these terms at any time. Continued use of the application constitutes acceptance of the updated terms.
            </p>
            <h4 class="text-xl font-semibold">6. Governing Law</h4>
            <p class="text-sm leading-relaxed">
                These terms are governed by and construed in accordance with the laws of your jurisdiction. Any disputes arising from these terms will be resolved in the courts of your jurisdiction.
            </p>

            <h4 class="text-xl font-semibold">7. Contact Information</h4>
            <p class="text-sm leading-relaxed">
                If you have any questions about these terms, please contact us at thuraiyurcricketcouncil@yahoo.com.
            </p>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 rounded-b-2xl flex justify-end">
            <button class="btn btn-primary px-6 py-2 text-white bg-gradient-to-r from-blue-600 to-indigo-700 rounded-lg shadow-md hover:opacity-90 transition-all" onclick="closeModal('termsOfServiceModal')">
                I Agree
            </button>
        </div>
    </div>
</div>

<!-- Privacy Policy Modal -->
<div id="privacyPolicyModal" class="fixed inset-0 flex items-end justify-center z-[99] hidden">
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">

        <!-- Header -->
        <div class="sticky top-0 z-[9999] bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-t-2xl px-8 py-6">
            <h3 class="text-3xl font-semibold text-center">Privacy Policy</h3>
            <p class="text-center text-sm mt-2">Learn how we handle your data and protect your privacy.</p>
        </div>

        <!-- Content -->
        <div class="p-6 space-y-4 text-gray-700">
            <h4 class="text-xl font-semibold">1. Data Collection</h4>
            <p class="text-sm leading-relaxed">
                We collect personal information such as your name, email, and mobile number to provide better services.
            </p>

            <h4 class="text-xl font-semibold">2. Data Usage</h4>
            <p class="text-sm leading-relaxed">
                Your data is used to improve your experience, provide support, and send notifications.
            </p>

            <h4 class="text-xl font-semibold">3. Data Sharing</h4>
            <p class="text-sm leading-relaxed">
                We do not share your personal data with third parties without your consent, except as required by law.
            </p>

            <h4 class="text-xl font-semibold">4. Data Security</h4>
            <p class="text-sm leading-relaxed">
                We implement robust security measures to protect your data from unauthorized access or breaches.
            </p>

            <h4 class="text-xl font-semibold">5. Your Rights</h4>
            <p class="text-sm leading-relaxed">
                You have the right to access, update, or delete your personal data. Contact us for assistance.
            </p>

            <h4 class="text-xl font-semibold">6. Changes to Policy</h4>
            <p class="text-sm leading-relaxed">
                We may update this policy from time to time. Continued use of the application constitutes acceptance of the updated policy.
            </p>

            <h4 class="text-xl font-semibold">7. Contact Information</h4>
            <p class="text-sm leading-relaxed">
                If you have any questions about this policy, please contact us at thuraiyurcricketcouncil@yahoo.com.
            </p>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 rounded-b-2xl flex justify-end">
            <button class="btn btn-primary px-6 py-2 text-white bg-gradient-to-r from-blue-600 to-indigo-700 rounded-lg shadow-md hover:opacity-90 transition-all" onclick="closeModal('privacyPolicyModal')">
                I agree
            </button>
        </div>
    </div>
</div>

<!-- Choose Team Modal -->
<div id="chooseTeamModal" class="fixed inset-0 flex items-end justify-center z-[99] hidden">
    <div class="relative bg-white rounded-t-2xl shadow-2xl border border-gray-200 w-full max-w-lg max-h-[90vh] flex flex-col">

        <!-- Sticky Header -->
        <div class="sticky top-0 z-10 bg-white pt-6 pb-4 px-4 border-b border-gray-200">
            <!-- Close Button -->
            <button class="absolute right-4 top-4 text-gray-500 hover:text-red-500 transition-all" onclick="closeModal('chooseTeamModal')">
                <i class="fa-solid fa-xmark text-2xl"></i>
            </button>

            <!-- Header -->
            <div class="text-center">
                <h3 class="text-3xl font-bold text-gray-900">Choose Your Team</h3>
                <p class="text-sm text-gray-500 mt-1">Select your favorite team to personalize your experience</p>
            </div>

            <!-- Search Input -->
            <div class="mt-4">
                <input
                    type="text"
                    id="team-search"
                    placeholder="Search team..."
                    class="w-full input input-bordered px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all shadow-sm" />
            </div>
        </div>

        <!-- Scrollable Content -->
        <div class="overflow-y-auto px-4 pb-32 pt-4">
            <!-- No Team Found & Create CTA -->
            <div class="text-center mb-6">
                <p class="text-sm text-gray-600 mb-2">Can’t find your team?</p>
                <button onclick="showModal('createTeamModal')"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-full shadow-md hover:from-blue-700 hover:to-blue-800 transition-all">
                    <i class="fa-solid fa-plus text-sm"></i>
                    <span class="text-sm font-medium">Create Your Team</span>
                </button>
            </div>

            <!-- Choose Team Form -->
            <form id="choose-team-form" class="space-y-5" autocomplete="off">
                <!-- 🟦 Team Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4" id="team-grid">
                    <?php
                    $teams = $appconfig->__get('teams');
                    if (!empty($teams)) {
                        foreach ($teams as $team) {
                            $teamId = htmlspecialchars($team['id']);
                            $teamName = htmlspecialchars($team['name']);
                            $teamLogo = htmlspecialchars($team['logo']);

                            echo '
                            <label class="relative cursor-pointer group" data-name="' . strtolower($teamName) . '">
                                <input type="radio" name="team" value="' . $teamId . '" class="peer hidden" required>

                                <div class="w-full aspect-square rounded-2xl border border-gray-200 p-0 flex items-end justify-center transition-all bg-white overflow-hidden relative shadow-sm peer-checked:shadow-lg peer-checked:ring-2 peer-checked:ring-blue-400 peer-checked:border-blue-500 group-hover:scale-[1.02] group-active:scale-[0.98] duration-200 ease-in-out">
                                    <img src="./uploads/team_logos/' . $teamLogo . '" alt="' . $teamName . ' logo"
                                        class="absolute w-full h-full object-cover pointer-events-none select-none peer-checked:opacity-100 transition-all duration-300">

                                    <h4 class="z-10 w-full text-center text-xs sm:text-sm font-semibold text-gray-900 drop-shadow-sm backdrop-blur-sm bg-white/70 py-1 px-2">
                                        ' . $teamName . '
                                    </h4>
                                </div>

                                <div class="absolute top-2 right-2 hidden peer-checked:block text-blue-600 z-20">
                                    <i class="fa-solid fa-circle-check text-lg sm:text-xl"></i>
                                </div>
                            </label>';
                        }
                    } else {
                        echo '<p class="text-sm text-red-500">No teams available</p>';
                    }
                    ?>
                </div>
            </form>
        </div>

        <!-- Sticky Footer Button -->
        <div class="sticky bottom-0 z-10 bg-white px-4 py-4 border-t border-gray-200">
            <button type="submit" form="choose-team-form"
                class="btn btn-primary w-full py-3 text-white bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl shadow-lg hover:opacity-90 transition-all text-lg sm:text-xl">
                Join Team
            </button>
        </div>
    </div>
</div>

<!-- Create Team Modal -->
<div id="createTeamModal" class="fixed inset-0 flex items-end justify-center z-[99] hidden">
    <div class="relative bg-white rounded-lg shadow-xl border border-gray-200 px-5 py-6 w-full max-w-lg">

        <!-- Close Button -->
        <button class="absolute right-4 top-4 text-gray-500 hover:text-red-500 transition-all" onclick="closeModal('createTeamModal')">
            <i class="fa-solid fa-xmark text-2xl"></i>
        </button>

        <h3 class="text-2xl sm:text-3xl font-semibold text-center mt-3 text-gray-900">Create a New Team</h3>
        <p class="text-gray-500 text-center text-sm mb-4">Fill in the details to create a new team</p>

        <!-- Create Team Form -->
        <form id="create-team-form" class="space-y-5" autocomplete="off">

            <!-- Team Name -->
            <div>
                <label for="team-name" class="text-gray-700">Team Name</label>
                <input
                    type="text"
                    id="team-name"
                    name="team-name"
                    class="w-full input input-bordered px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    placeholder="Enter Team Name"
                    required />
            </div>

            <!-- Team Logo -->
            <div>
                <label for="team-logo" class="text-gray-700">Team Logo</label>
                <input
                    type="file"
                    id="team-logo"
                    name="team-logo"
                    accept="image/*"
                    class="w-full input input-bordered px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    required />
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary w-full py-3 text-white bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl shadow-lg hover:opacity-90 transition-all text-lg sm:text-xl">
                Create Team
            </button>
        </form>
    </div>
</div>
<!-- Feedback Modal -->
<div id="feedbackModal" class="fixed inset-0 flex items-end justify-center z-[99] hidden">
    <div class="relative bg-white rounded-t-2xl shadow-2xl border border-gray-200 px-8 py-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <button class="absolute right-4 top-4 text-gray-500 hover:text-red-500 transition-all" onclick="closeModal('feedbackModal')">
            <i class="fa-solid fa-xmark text-2xl"></i>
        </button>
        <h3 class="text-3xl font-semibold text-center mt-3 text-gray-900">Feedback</h3>
        <p class="text-gray-500 text-center text-sm mb-6">We value your feedback. Please share your thoughts below.</p>
        <form id="feedback-form" class="space-y-5" autocomplete="off">
            <textarea name="feedback" placeholder="Write your feedback here..." required
                class="w-full input input-bordered py-3 px-4 rounded-lg shadow-sm border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"></textarea>
            <button type="submit"
                class="btn btn-primary w-full py-3 text-white bg-gradient-to-r from-blue-600 to-indigo-700 rounded-lg shadow-lg hover:opacity-90 transition-all">
                Submit Feedback
            </button>
        </form>
    </div>
</div>

<!-- Report Bug Modal -->
<div id="reportBugModal" class="fixed inset-0 flex items-end justify-center z-[99] hidden">
    <div class="relative bg-white rounded-t-2xl shadow-2xl border border-gray-200 px-8 py-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <button class="absolute right-4 top-4 text-gray-500 hover:text-red-500 transition-all" onclick="closeModal('reportBugModal')">
            <i class="fa-solid fa-xmark text-2xl"></i>
        </button>
        <h3 class="text-3xl font-semibold text-center mt-3 text-gray-900">Report a Bug</h3>
        <p class="text-gray-500 text-center text-sm mb-6">Found a bug? Let us know so we can fix it.</p>
        <form id="report-bug-form" class="space-y-5" autocomplete="off">
            <textarea name="bug_description" placeholder="Describe the bug here..." required
                class="w-full input input-bordered py-3 px-4 rounded-lg shadow-sm border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"></textarea>
            <button type="submit"
                class="btn btn-primary w-full py-3 text-white bg-gradient-to-r from-blue-600 to-indigo-700 rounded-lg shadow-lg hover:opacity-90 transition-all">
                Submit Bug Report
            </button>
        </form>
    </div>
</div>

<!-- About Us Modal -->
<div id="aboutUsModal" class="fixed inset-0 flex items-end justify-center z-[99] hidden">
    <div class="relative bg-white rounded-t-2xl shadow-2xl border border-gray-200 px-8 py-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <button class="absolute right-4 top-4 text-gray-500 hover:text-red-500 transition-all" onclick="closeModal('aboutUsModal')">
            <i class="fa-solid fa-xmark text-2xl"></i>
        </button>
        <h3 class="text-3xl font-semibold text-center mt-3 text-gray-900">About Us</h3>
        <p class="text-gray-500 text-center text-sm mb-6">Learn more about Thuraiyur Cricket Council and our mission.</p>
        <div class="text-gray-700 text-sm leading-relaxed">
            <p>Thuraiyur Cricket Council (TCC) is dedicated to promoting cricket and fostering a sense of community among cricket enthusiasts. Our platform provides tools to manage teams, players, and events efficiently.</p>
            <p class="mt-4">For more information, contact us at <a href="mailto:thuraiyurcricketcouncil@yahoo.com" class="text-blue-500 hover:underline">thuraiyurcricketcouncil@yahoo.com</a>.</p>
        </div>
    </div>
</div>

<!-- Credits Modal -->
<div id="creditsModal" class="fixed inset-0 z-[99] hidden">
    <!-- Modal Content -->
    <div class="relative z-10 flex items-end justify-center h-full">
        <div class="relative bg-gradient-to-br from-white via-[#fdfdfd]/80 to-[#f5f5f5]/80 backdrop-blur-2xl rounded-t-3xl shadow-2xl border border-gray-200 px-6 py-10 w-full max-w-5xl max-h-[90vh] overflow-y-auto">

            <!-- Close Button -->
            <button class="absolute right-5 top-5 text-gray-500 hover:text-red-600 transition" onclick="closeModal('creditsModal')">
                <i class="fa-solid fa-circle-xmark text-2xl"></i>
            </button>

            <h3 class="text-4xl font-bold text-center text-[#111827] mb-2"><i class="fa-solid fa-heart text-pink-500"></i> Credits</h3>
            <p class="text-gray-500 text-center text-sm mb-8">Meet our passionate and talented team!</p>

            <!-- Department: Idea & Support -->
            <div class="mb-10">
                <h4 class="text-xl font-semibold text-indigo-600 mb-4 border-b-2 border-indigo-100 pb-2">
                    <i class="fa-solid fa-lightbulb text-yellow-400 mr-2"></i>Idea & Support
                </h4>
                <div class="grid grid-cols-3 gap-6">
                    <!-- Card -->
                    <div class="bg-white rounded-xl border shadow-md overflow-hidden hover:shadow-xl transition">
                        <img src="https://via.placeholder.com/300x150" alt="John Doe" class="w-full h-32 object-cover">
                        <div class="p-4 text-center">
                            <h5 class="text-base font-semibold text-gray-800 animate-marquee">John Doe</h5>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border shadow-md overflow-hidden hover:shadow-xl transition">
                        <img src="https://via.placeholder.com/300x150" alt="Jane Smith" class="w-full h-32 object-cover">
                        <div class="p-4 text-center">
                            <h5 class="text-base font-semibold text-gray-800 animate-marquee">Jane Smith</h5>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border shadow-md overflow-hidden hover:shadow-xl transition">
                        <img src="https://via.placeholder.com/300x150" alt="Alex Johnson" class="w-full h-32 object-cover">
                        <div class="p-4 text-center">
                            <h5 class="text-base font-semibold text-gray-800 animate-marquee">Alex Johnson</h5>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Department: Design & Development -->
            <div class="mb-10">
                <h4 class="text-xl font-semibold text-teal-600 mb-4 border-b-2 border-teal-100 pb-2">
                    <i class="fa-solid fa-code text-green-500 mr-2"></i>Design & Development
                </h4>
                <div class="grid grid-cols-3 gap-6">
                    <div class="bg-white rounded-xl border shadow-md overflow-hidden hover:shadow-xl transition">
                        <img src="https://via.placeholder.com/300x150" alt="Priya Dev" class="w-full h-32 object-cover">
                        <div class="p-4 text-center">
                            <h5 class="text-base font-semibold text-gray-800">Priya Dev</h5>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border shadow-md overflow-hidden hover:shadow-xl transition">
                        <img src="https://via.placeholder.com/300x150" alt="Raj Kumar" class="w-full h-32 object-cover">
                        <div class="p-4 text-center">
                            <h5 class="text-base font-semibold text-gray-800">Raj Kumar</h5>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border shadow-md overflow-hidden hover:shadow-xl transition">
                        <img src="https://via.placeholder.com/300x150" alt="Sneha M" class="w-full h-32 object-cover">
                        <div class="p-4 text-center">
                            <h5 class="text-base font-semibold text-gray-800">Sneha M</h5>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Special Thanks -->
            <div class="bg-yellow-100 rounded-xl shadow-inner overflow-hidden mt-8 border border-yellow-300">
                <img src="https://via.placeholder.com/300x150/FFF4D4/000000?text=❤️+Thanks" alt="Thanks" class="w-full h-28 object-cover">
                <div class="p-5 text-center">
                    <h4 class="text-md font-semibold text-yellow-800"><i class="fa-solid fa-hands-clapping mr-1"></i>Special Thanks</h4>
                    <p class="text-xs text-yellow-700">To our friends, families, and community for their endless support 🙏</p>
                </div>
            </div>

            <!-- Contact -->
            <div class="text-center text-sm text-gray-500 mt-6">
                📬 Contact:
                <a href="mailto:thuraiyurcricketcouncil@yahoo.com" class="text-blue-500 hover:underline font-medium">
                    thuraiyurcricketcouncil@yahoo.com
                </a>
            </div>
        </div>
    </div>
</div>





<!-- 🔍 Realtime Search Script -->
<script>
    document.getElementById('team-search').addEventListener('input', function() {
        const searchValue = this.value.toLowerCase().trim();
        const teamCards = document.querySelectorAll('#team-grid label');

        let matchCount = 0;
        teamCards.forEach(card => {
            const name = card.getAttribute('data-name');
            const isVisible = name.includes(searchValue);
            card.style.display = isVisible ? 'block' : 'none';
            if (isVisible) matchCount++;
        });

        // Optional: handle no match UI (can be customized)
        if (matchCount === 0) {
            // you can show a 'no teams found' message here if needed
        }
    });
    $(document).ready(function() {
        // Function to open the create team modal
        function openCreateTeamModal() {
            document.getElementById('createTeamModal').classList.remove('hidden');
        }

        // Function to close any modal
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        // Form submission for creating a team
        $("#create-team-form").submit(function(e) {
            e.preventDefault();

            let teamName = $("#team-name").val();
            let teamLogo = $("#team-logo")[0].files[0];

            if (!teamName || !teamLogo) {
                alert("Please provide team name and logo.");
                return;
            }

            let formData = new FormData();
            formData.append("team-name", teamName);
            formData.append("team-logo", teamLogo);

            $.ajax({
                url: "classes/Login.php?f=createteam",
                type: "POST",
                data: formData,
                contentType: false,
                processData: false,
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        closeModal('createTeamModal');
                        alert_toast("Team Added successfully!", "success");
                    } else {
                        alert("Error: " + response.msg);
                    }
                },
                error: function() {
                    alert_toast("An error occurred. Please try again.", "error");
                },
            });
        });
    });
</script>

<script>
    $(document).ready(function() {
        $("#choose-team-form").submit(function(e) {
            e.preventDefault();
            let teamId = $("input[name='team']:checked").val(); // ✅ radio input checked value எடுத்துக்கறது

            if (!teamId) {
                alert("Please select a team!");
                return;
            }

            $.ajax({
                url: "classes/Login.php?f=request_join",
                type: "POST",
                data: {
                    team_id: teamId
                },
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        alert("Updated successful!");
                        closeModal('chooseTeamModal');
                        location.reload();
                    } else {
                        alert("Error: " + response.msg);
                    }

                },
                error: function() {
                    alert("An error occurred. Please try again.");
                },
            });
        });
    });
</script>

<script>
    function sendUserMessage() {
        let inputField = document.getElementById("userMessage");
        let messageText = inputField.value.trim();
        if (!messageText) return;

        let chatContainer = document.getElementById("chatBotContainer");

        // Add User Message
        let userMessageHTML = `
        <div class="bg-blue-500 text-white p-3 rounded-lg shadow-md self-end max-w-[80%] ml-auto">
            <p class="text-sm">${messageText}</p>
            <span class="block text-xs text-gray-200 mt-1">You • Now</span>
        </div>
    `;
        chatContainer.innerHTML += userMessageHTML;
        chatContainer.scrollTop = chatContainer.scrollHeight; // Auto-scroll

        inputField.value = ""; // Clear input field

        // Simulate Bot Response
        setTimeout(() => {
            let botMessageHTML = `
            <div class="bg-gray-100 p-3 rounded-lg shadow-sm self-start max-w-[80%]">
                <p class="text-gray-700 text-sm">I'm looking into that for you... 🔍</p>
                <span class="block text-xs text-gray-400 mt-1">Tcc-Bot • Just now</span>
            </div>
        `;
            chatContainer.innerHTML += botMessageHTML;
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }, 1500);
    }
</script>

<script>
    // Profile Image Preview for Update Profile
    document.getElementById("update_profile_image").addEventListener("change", function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById("updateProfilePreview").src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    // Handle Update Profile Submission
    $("#update-profile-form").submit(function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        formData.append("old_image", "<?= htmlspecialchars($_settings->userdata('image'), ENT_QUOTES, 'UTF-8'); ?>");

        $.ajax({
            url: "classes/Login.php?f=update_profile",
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            beforeSend: function() {
                closeModal('updateProfileModal');
                start_loader();
            },
            success: function(resp) {
                if (resp.status === "success") {
                    // Update profile details dynamically
                    $(".image").attr("src", "./uploads/users/" + resp.data.image + "?t=" + new Date().getTime()); // Add cache-busting query string
                    $(".name").text(resp.data.name);
                    alert_toast("Profile updated successfully", "success");
                } else {
                    alert_toast(resp.msg || "Profile update failed", "error");
                }
                end_loader();
            },
            error: function() {
                alert_toast("An error occurred", "error");
                end_loader();
            },
        });
    });

    // Profile Image Preview
    document.getElementById("profile_image").addEventListener("change", function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById("profilePreview").src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
    // Handle Login Submission
    $("#login-form").submit(function(e) {
        e.preventDefault();
        let username = $('[name="username"]').val().trim();
        let password = $('[name="password"]').val().trim();

        if (username === "" || password === "") {
            alert_toast("Both fields are required!", "error");
            return;
        }

        $.ajax({
            url: "classes/Login.php?f=login_user",
            method: "POST",
            data: {
                username,
                password
            },
            dataType: "json",
            beforeSend: function() {
                closeModal('loginModal');
                start_loader();
            },
            success: function(resp) {
                if (resp.status === "success") {
                    alert_toast('Login Successfully', "success");
                    setTimeout(() => location.reload(), 2000);
                } else {
                    alert_toast(resp.msg || "Invalid credentials", "error");
                    end_loader(); // Ensure loader stops on error
                }
            },
            error: function() {
                alert_toast("An error occurred", "error");
                end_loader(); // Ensure loader stops on error
            },
        });
    });

    // Handle Register Submission
    $("#register-form").submit(function(e) {
        e.preventDefault();
        let formData = new FormData(this);

        // Include profile image in FormData
        const profileImage = document.getElementById("profile_image").files[0];
        if (profileImage) {
            formData.append("profile_image", profileImage);
        }

        if (!formData.get("name") || !formData.get("email") || !formData.get("mobile") || !formData.get("password") || !formData.get("confirm_password")) {
            alert_toast("All fields are required!", "error");
            return;
        }

        if (formData.get("password") !== formData.get("confirm_password")) {
            alert_toast("Passwords do not match!", "error");
            return;
        }

        $.ajax({
            url: "classes/Login.php?f=register_user",
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            beforeSend: function() {
                closeModal('registerModal');
                start_loader();
            },
            success: function(resp) {
                if (resp.status === "success") {
                    alert_toast("Registration Successful", "success");
                    setTimeout(() => location.reload(), 2000);
                } else {
                    alert_toast(resp.msg || "Registration failed", "error");
                    end_loader();
                }
            },
            error: function() {
                alert_toast("An error occurred", "error");
                end_loader();
            },
        });
    });

    // Handle Change Password Submission
    $("#change-password-form").submit(function(e) {
        e.preventDefault();
        let formData = new FormData(this);

        if (formData.get("new_password") !== formData.get("confirm_new_password")) {
            alert_toast("New passwords do not match!", "error");
            return;
        }

        $.ajax({
            url: "classes/Login.php?f=change_password",
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            beforeSend: function() {
                closeModal('changePasswordModal');
                start_loader();
            },
            success: function(resp) {
                if (resp.status === "success") {
                    alert_toast("Password changed successfully", "success");
                } else {
                    alert_toast(resp.msg || "Password change failed", "error");
                }
                end_loader();
            },
            error: function() {
                alert_toast("An error occurred", "error");
                end_loader();
            },
        });
    });

    // Handle Notification Settings Submission
    $("#notification-settings-form").submit(function(e) {
        e.preventDefault();
        let formData = new FormData(this);

        $.ajax({
            url: "classes/Settings.php?f=update_notification_settings",
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            beforeSend: function() {
                closeModal('notificationSettingsModal');
                start_loader();
            },
            success: function(resp) {
                if (resp.status === "success") {
                    alert_toast("Notification settings updated successfully", "success");
                } else {
                    alert_toast(resp.msg || "Failed to update settings", "error");
                }
                end_loader();
            },
            error: function() {
                alert_toast("An error occurred", "error");
                end_loader();
            },
        });
    });

    // Handle OTP Login Submission
    $("#otp-login-form").submit(function(e) {
        e.preventDefault();
        let mobile = $('[name="mobile"]').val().trim();

        if (!mobile) {
            alert_toast("Mobile number is required!", "error");
            return;
        }

        $.ajax({
            url: "classes/Login.php?f=send_otp",
            method: "POST",
            data: {
                mobile
            },
            dataType: "json",
            beforeSend: function() {
                start_loader();
            },
            success: function(resp) {
                if (resp.status === "success") {
                    alert_toast("OTP sent successfully", "success");
                    // Show OTP verification fields
                    $("#otp-verification-fields").removeClass("hidden");
                    $('[name="mobile"]').prop("readonly", true);
                } else {
                    alert_toast(resp.msg || "Failed to send OTP", "error");
                }
                end_loader();
            },
            error: function() {
                alert_toast("An error occurred", "error");
                end_loader();
            },
        });
    });

    // Handle OTP Verification Submission
    $("#otp-login-form").on("submit", function(e) {
        e.preventDefault();
        let mobile = $('[name="mobile"]').val().trim();
        let otp = $('[name="otp"]').val().trim();

        if (!otp) {
            alert_toast("OTP is required!", "error");
            return;
        }

        $.ajax({
            url: "classes/Login.php?f=verify_otp",
            method: "POST",
            data: {
                mobile,
                otp
            },
            dataType: "json",
            beforeSend: function() {
                start_loader();
            },
            success: function(resp) {
                if (resp.status === "success") {
                    alert_toast("OTP verified successfully. Logging in...", "success");
                    setTimeout(() => location.reload(), 2000);
                } else {
                    alert_toast(resp.msg || "Invalid OTP", "error");
                }
                end_loader();
            },
            error: function() {
                alert_toast("An error occurred", "error");
                end_loader();
            },
        });
    });
</script>

<script>
    // logout function
    $(document).ready(function() {
        $('#logout-btn').on('click', function() {
            conf("Are you sure you want to logout?", function() {
                $.ajax({
                    url: "classes/Login.php?f=logout",
                    method: "POST",
                    beforeSend: function() {
                        start_loader();
                    },
                    success: function() {
                        location.reload();
                        alert_toast("Logged out successfully", 'success');
                        end_loader();
                    },
                    error: function(xhr) {
                        console.log(xhr.responseText);
                        alert_toast("An error occurred", 'error');
                        end_loader();
                    }
                });
            });
        });
    });
    //delete account function
    $(document).ready(function() {
        $('#deleteAccountLink').on('click', function() {
            conf("you want to delete your account?", function() {
                $.ajax({
                    url: "classes/Login.php?f=delete_account",
                    method: "POST",
                    beforeSend: function() {
                        start_loader();
                    },
                    success: function() {
                        alert_toast("Account deleted successfully", 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
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