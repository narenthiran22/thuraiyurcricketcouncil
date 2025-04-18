<?php
require_once '../config.php';
class Login extends DBConnection
{
	private $settings;
	public function __construct()
	{
		global $_settings;
		$this->settings = $_settings;

		parent::__construct();
		ini_set('display_error', 1);
	}
	public function __destruct()
	{
		parent::__destruct();
	}
	public function index()
	{
		echo "<h1>Access Denied</h1> <a href='" . base_url . "'>Go Back.</a>";
	}
	public function login()
	{
		extract($_POST);

		$stmt = $this->conn->prepare("SELECT * from users where username = ? and password = ? ");
		$password = md5($password);
		$stmt->bind_param('ss', $username, $password);
		$stmt->execute();
		$result = $stmt->get_result();
		if ($result->num_rows > 0) {
			foreach ($result->fetch_array() as $k => $v) {
				if (!is_numeric($k) && $k != 'password') {
					$this->settings->set_userdata($k, $v);
				}
			}
			$this->settings->set_userdata('login_type', 1);

			// Update last_activity
			$stmt = $this->conn->prepare("UPDATE users SET is_logged_in = 1, last_activity = NOW() WHERE id = ?");
			$user_id = $this->settings->userdata('id');
			$stmt->bind_param("i", $user_id);
			$stmt->execute();
			$stmt->close();

			return json_encode(array('status' => 'success'));
		} else {
			return json_encode(array('status' => 'incorrect', 'last_qry' => "SELECT * from users where username = '$username' and password = md5('$password') "));
		}
	}
	public function logout()
	{
		$user_id = $this->settings->userdata('id');
		$stmt = $this->conn->prepare("UPDATE users SET is_logged_in = 0, last_activity = NOW() WHERE id = ?");
		$stmt->bind_param("i", $user_id);
		$stmt->execute();
		$stmt->close();

		session_destroy();
		echo json_encode(['status' => 'success', 'msg' => 'Logged out']);
	}


	public function login_user()
	{
		$username = trim($_POST['username'] ?? '');
		$password = trim($_POST['password'] ?? '');

		// Validate inputs
		if (empty($username) || empty($password)) {
			echo json_encode(['status' => 'error', 'msg' => 'Username and password are required']);
			return;
		}

		// Add country code for mobile numbers if not present
		if (preg_match('/^[0-9]{10,15}$/', $username) && strpos($username, '+') !== 0) {
			$username = '+91' . $username; // Replace +91 with your country code if needed
		}

		// Determine login column (email or mobile)
		if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
			$column = 'email';
		} elseif (preg_match('/^\+?[0-9]{10,15}$/', $username)) {
			$column = 'mobile';
		} else {
			echo json_encode(['status' => 'error', 'msg' => 'Invalid email or mobile number']);
			return;
		}

		// Query user by email or mobile
		$query = "SELECT * FROM users WHERE $column = ?";
		$stmt = $this->conn->prepare($query);
		$stmt->bind_param("s", $username);
		$stmt->execute();
		$result = $stmt->get_result();
		$user = $result->fetch_assoc();
		$stmt->close();

		if (!$user) {
			echo json_encode(['status' => 'error', 'msg' => 'User not found']);
			return;
		}

		if ((int)$user['status'] !== 1) {
			echo json_encode(['status' => 'error', 'msg' => 'Account is inactive']);
			return;
		}

		if (!password_verify($password, $user['password'])) {
			echo json_encode(['status' => 'error', 'msg' => 'Incorrect password']);
			return;
		}

		$user_id = (int)$user['id'];

		// Force logout previous session if already logged in
		if ((int)$user['is_logged_in'] === 1) {
			$logout_stmt = $this->conn->prepare("UPDATE users SET is_logged_in = 0 WHERE id = ?");
			$logout_stmt->bind_param("i", $user_id);
			$logout_stmt->execute();
			$logout_stmt->close();
		}

		// Set session (excluding password)
		foreach ($user as $key => $value) {
			if ($key !== 'password') {
				$this->settings->set_userdata($key, $value);
			}
		}

		// Update login status
		$update_stmt = $this->conn->prepare("UPDATE users SET is_logged_in = 1, last_activity = NOW() WHERE id = ?");
		$update_stmt->bind_param("i", $user_id);
		$update_stmt->execute();
		$update_stmt->close();

		// Respond with success
		echo json_encode([
			'status' => 'success',
			'role' => $user['role'],
			'data' => [
				'name' => htmlspecialchars(ucwords($user['name']), ENT_QUOTES, 'UTF-8'),
				'image' => htmlspecialchars($user['image'], ENT_QUOTES, 'UTF-8')
			]
		]);
	}



	public function register_user()
	{
		header('Content-Type: application/json');

		// Trim input data
		$name = trim($_POST['name'] ?? '');
		$email = trim($_POST['email'] ?? '');
		$mobile = trim($_POST['mobile'] ?? '');
		$password = trim($_POST['password'] ?? '');
		$confirm_password = trim($_POST['confirm_password'] ?? '');

		// Validate empty fields
		if (!$name || !$email || !$mobile || !$password || !$confirm_password) {
			echo json_encode(['status' => 'error', 'msg' => 'All fields are required']);
			return;
		}

		// Validate email format
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			echo json_encode(['status' => 'error', 'msg' => 'Invalid email format']);
			return;
		}

		// Add country code for mobile numbers if not present
		if (preg_match('/^[0-9]{10,15}$/', $mobile) && strpos($mobile, '+') !== 0) {
			$mobile = '+91' . $mobile; // Replace +91 with your country code if needed
		}

		// Validate mobile number (Only digits, 10-15 length)
		if (!preg_match('/^\+?[0-9]{10,15}$/', $mobile)) {
			echo json_encode(['status' => 'error', 'msg' => 'Invalid mobile number']);
			return;
		}

		// Check if passwords match
		if ($password !== $confirm_password) {
			echo json_encode(['status' => 'error', 'msg' => 'Passwords do not match']);
			return;
		}

		// Check if email or mobile already exists
		$stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ? OR mobile = ?");
		if (!$stmt) {
			echo json_encode(['status' => 'error', 'msg' => 'Database error: ' . $this->conn->error]);
			return;
		}

		$stmt->bind_param("ss", $email, $mobile);
		$stmt->execute();
		$result = $stmt->get_result();

		if ($result && $result->num_rows > 0) {
			echo json_encode(['status' => 'error', 'msg' => 'Email or Mobile already registered']);
			return;
		}

		// Hash password securely
		$hashed_password = password_hash($password, PASSWORD_DEFAULT);
		$uploaded_image = null; // Default no image

		// Handle image upload
		if (isset($_FILES['profile_image']) && $_FILES['profile_image']['tmp_name']) {
			$upload_dir = '../uploads/users/';
			$allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
			$max_size = 2 * 1024 * 1024; // 2MB

			$file_info = pathinfo($_FILES['profile_image']['name']);
			$file_ext = strtolower($file_info['extension']);
			$file_size = $_FILES['profile_image']['size'];

			if (!in_array($file_ext, $allowed_types)) {
				echo json_encode(['status' => 'error', 'msg' => 'Invalid image format. Only JPG, PNG, GIF allowed']);
				return;
			}

			if ($file_size > $max_size) {
				echo json_encode(['status' => 'error', 'msg' => 'Image size must be under 2MB']);
				return;
			}

			$image_name = strtolower(str_replace(' ', '_', $name)) . '_' . time() . '.' . $file_ext;
			$image_path = $upload_dir . $image_name;

			if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $image_path)) {
				$uploaded_image = $image_name;
			} else {
				echo json_encode(['status' => 'error', 'msg' => 'Failed to upload image']);
				return;
			}
		}

		// Insert new user
		$stmt = $this->conn->prepare("INSERT INTO users (name, email, mobile, image, password, status) VALUES (?, ?, ?, ?, ?, 1)");
		if (!$stmt) {
			echo json_encode(['status' => 'error', 'msg' => 'Database error: ' . $this->conn->error]);
			return;
		}

		$stmt->bind_param("sssss", $name, $email, $mobile, $uploaded_image, $hashed_password);

		if ($stmt->execute()) {
			echo json_encode(['status' => 'success', 'msg' => 'Registration successful']);
		} else {
			echo json_encode(['status' => 'error', 'msg' => 'Registration failed']);
		}
	}


	public function update_profile()
	{
		// Trim and sanitize input data
		$name = htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8');
		$email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
		$mobile = filter_var(trim($_POST['mobile']), FILTER_SANITIZE_STRING);
		$user_id = intval($_POST['user_id']); // Ensure user ID is passed and sanitized

		// Validate empty fields
		if (empty($name) || empty($email) || empty($mobile) || empty($user_id)) {
			echo json_encode(['status' => 'error', 'msg' => 'All fields are required']);
			return;
		}

		// Handle image upload
		$upload_dir = '../uploads/users/';
		$uploaded_image = $_POST['old_image']; // Keep old image by default

		// Update the image upload logic to ensure the correct handling of the uploaded image
		if (isset($_FILES['profile_image']) && $_FILES['profile_image']['tmp_name']) {
			$allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
			$file_ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));

			if (in_array($file_ext, $allowed_types)) {
				$image_name = strtolower(str_replace(' ', '_', $name)) . '_' . time() . '.' . $file_ext;
				$image_path = $upload_dir . $image_name;

				if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $image_path)) {
					// Delete the old image only if it's not empty and exists
					if (!empty($_POST['old_image']) && file_exists($upload_dir . $_POST['old_image'])) {
						unlink($upload_dir . $_POST['old_image']);
					}
					$uploaded_image = $image_name;
				} else {
					echo json_encode(['status' => 'error', 'msg' => 'Failed to upload the image']);
					return;
				}
			} else {
				echo json_encode(['status' => 'error', 'msg' => 'Invalid image format']);
				return;
			}
		}

		// Update user information
		$stmt = $this->conn->prepare("UPDATE users SET name = ?, email = ?, mobile = ?, image = ? WHERE id = ?");
		$stmt->bind_param("ssssi", $name, $email, $mobile, $uploaded_image, $user_id);

		if ($stmt->execute()) {
			// Fetch updated user data
			$stmt = $this->conn->prepare("SELECT name, email, mobile, image FROM users WHERE id = ?");
			$stmt->bind_param("i", $user_id);
			$stmt->execute();
			$result = $stmt->get_result()->fetch_assoc();

			// Set updated data into session
			foreach ($result as $k => $v) {
				$this->settings->set_userdata($k, $v);
			}

			echo json_encode(['status' => 'success', 'msg' => 'Profile updated successfully', 'data' => $result]);
			return;
		} else {
			echo json_encode(['status' => 'error', 'msg' => 'Failed to update profile']);
			return;
		}
	}

	public function change_password()
	{
		// Trim and sanitize input data
		$user_id = intval($this->settings->userdata('id'));
		$current_password = trim($_POST['current_password']);
		$new_password = trim($_POST['new_password']);
		$confirm_new_password = trim($_POST['confirm_new_password']);

		// Validate empty fields
		if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
			echo json_encode(['status' => 'error', 'msg' => 'All fields are required']);
			return;
		}

		// Check if new passwords match
		if ($new_password !== $confirm_new_password) {
			echo json_encode(['status' => 'error', 'msg' => 'New passwords do not match']);
			return;
		}

		// Fetch current password from database
		$stmt = $this->conn->prepare("SELECT password FROM users WHERE id = ?");
		$stmt->bind_param("i", $user_id);
		$stmt->execute();
		$result = $stmt->get_result()->fetch_assoc();
		$stmt->close();

		if (!$result || !password_verify($current_password, $result['password'])) {
			echo json_encode(['status' => 'error', 'msg' => 'Current password is incorrect']);
			return;
		}

		// Hash the new password
		$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

		// Update the password in the database
		$stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
		$stmt->bind_param("si", $hashed_password, $user_id);

		if ($stmt->execute()) {
			echo json_encode(['status' => 'success', 'msg' => 'Password changed successfully']);
		} else {
			echo json_encode(['status' => 'error', 'msg' => 'Failed to change password']);
		}
	}

	public function delete_account()
	{
		// Get user ID from session
		$user_id = intval($this->settings->userdata('id'));

		// Delete user from database
		$stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
		$stmt->bind_param("i", $user_id);

		if ($stmt->execute()) {
			echo json_encode(['status' => 'success', 'msg' => 'Account deleted successfully']);
			session_start();
			session_unset();
			session_destroy();
			exit;
		} else {
			echo json_encode(['status' => 'error', 'msg' => 'Failed to delete account']);
		}
	}
	public function request_join()
	{
		$user_id = intval($this->settings->userdata('id'));
		$team_id = intval($_POST['team_id'] ?? 0);

		if (!$team_id) {
			echo json_encode(["status" => "error", "message" => "Team ID is required."]);
			return;
		}

		// Update fav_team in users table
		$stmt = $this->conn->prepare("UPDATE users SET fav_team = ? WHERE id = ?");
		$stmt->bind_param("ii", $team_id, $user_id);

		if ($stmt->execute()) {
			// ✅ Update session data also
			$this->settings->set_userdata('fav_team', $team_id);

			echo json_encode(["status" => "success", "message" => "Team selection updated successfully."]);
		} else {
			echo json_encode(["status" => "error", "message" => "Failed to update team. Please try again."]);
		}
	}

	public function createteam()
	{
		if (!isset($_POST['team-name']) || !isset($_FILES['team-logo'])) {
			echo json_encode(['status' => 'error', 'msg' => 'Missing required fields']);
			return;
		}

		$teamName = htmlspecialchars($_POST['team-name'], ENT_QUOTES, 'UTF-8');
		$teamLogo = $_FILES['team-logo'];
		$userId = $this->settings->userdata('id');
		$uploadDir = '../uploads/team_logos/';
		$allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
		$fileExt = strtolower(pathinfo($teamLogo['name'], PATHINFO_EXTENSION));

		if (!in_array($fileExt, $allowedTypes)) {
			echo json_encode(['status' => 'error', 'msg' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.']);
			return;
		}

		$fileName = uniqid('team_', true) . '.' . $fileExt;
		$uploadPath = $uploadDir . $fileName;

		if (!move_uploaded_file($teamLogo['tmp_name'], $uploadPath)) {
			echo json_encode(['status' => 'error', 'msg' => 'Failed to upload team logo.']);
			return;
		}

		$stmt = $this->conn->prepare("INSERT INTO teams (name, logo, created_by) VALUES (?, ?, ?)");
		if ($stmt) {
			$stmt->bind_param("ssi", $teamName, $fileName, $userId);
			if ($stmt->execute()) {
				$teamId = $stmt->insert_id;

				// Update user's role to 3 (team admin)
				$updateRoleStmt = $this->conn->prepare("UPDATE users SET role = 1 WHERE id = ?");
				if ($updateRoleStmt) {
					$updateRoleStmt->bind_param("i", $userId);
					$updateRoleStmt->execute();
					$updateRoleStmt->close();
				}

				echo json_encode([
					'status' => 'success',
					'msg' => 'Team created successfully!',
					'teamId' => $teamId,
					'teamName' => $teamName,
					'teamLogo' => $fileName
				]);
			} else {
				echo json_encode(['status' => 'error', 'msg' => 'Failed to insert team into database.']);
			}
			$stmt->close();
		} else {
			echo json_encode(['status' => 'error', 'msg' => 'Database query preparation failed.']);
		}
	}

	public function send_otp()
	{
		$mobile = trim($_POST['mobile'] ?? '');

		// Validate mobile number
		if (empty($mobile) || !preg_match('/^[0-9]{10,15}$/', $mobile)) {
			echo json_encode(['status' => 'error', 'msg' => 'Invalid mobile number']);
			return;
		}

		// Format mobile number with country code (e.g., +91 for India)
		if (strpos($mobile, '+') !== 0) {
			$mobile = '+91' . $mobile; // Replace +91 with your country code if needed
		}

		// Check if a valid OTP already exists
		$stmt = $this->conn->prepare("SELECT * FROM otp_codes WHERE mobile = ? AND TIMESTAMPDIFF(MINUTE, created_at, NOW()) <= 5");
		$stmt->bind_param("s", $mobile);
		$stmt->execute();
		$result = $stmt->get_result();
		$existing_otp = $result->fetch_assoc();
		$stmt->close();

		if ($existing_otp) {
			echo json_encode(['status' => 'success', 'msg' => 'OTP already sent. Please check your messages.']);
			return;
		}

		// Generate a 6-digit OTP
		$otp = rand(100000, 999999);

		// Save OTP in the database
		$stmt = $this->conn->prepare("INSERT INTO otp_codes (mobile, otp, created_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE otp = ?, created_at = NOW()");
		$stmt->bind_param("sss", $mobile, $otp, $otp);
		if (!$stmt->execute()) {
			echo json_encode(['status' => 'error', 'msg' => 'Failed to save OTP.']);
			return;
		}
		$stmt->close();

		// Get Twilio settings from DB
		$query = "SELECT account_sid, auth_token, twilio_number FROM settings LIMIT 1";
		$result = mysqli_query($this->conn, $query);
		$row = mysqli_fetch_assoc($result);

		// Assign values
		$account_sid = $row['account_sid'];
		$auth_token = $row['auth_token'];
		$twilio_number = $row['twilio_number'];

		// Send OTP via Twilio
		$url = "https://api.twilio.com/2010-04-01/Accounts/$account_sid/Messages.json";
		$data = [
			'From' => $twilio_number,
			'To' => $mobile,
			'Body' => "Your OTP is $otp. It is valid for 5 minutes. -ThuraiyurCricketCouncil"
		];

		$options = [
			'http' => [
				'header' => "Authorization: Basic " . base64_encode("$account_sid:$auth_token") . "\r\n" .
					"Content-Type: application/x-www-form-urlencoded\r\n",
				'method' => 'POST',
				'content' => http_build_query($data),
			],
		];

		$context = stream_context_create($options);
		$response = file_get_contents($url, false, $context);

		if ($response === false) {
			echo json_encode(['status' => 'error', 'msg' => 'Failed to send OTP. Please check your Twilio configuration.']);
			return;
		}

		echo json_encode(['status' => 'success', 'msg' => 'OTP sent successfully']);
	}

	public function verify_otp()
	{
		$mobile = trim($_POST['mobile'] ?? '');
		$otp = trim($_POST['otp'] ?? '');

		// Validate inputs
		if (empty($mobile) || empty($otp)) {
			echo json_encode(['status' => 'error', 'msg' => 'Mobile number and OTP are required']);
			return;
		}

		// Format mobile number with country code (e.g., +91 for India)
		if (strpos($mobile, '+') !== 0) {
			$mobile = '+91' . $mobile; // Replace +91 with your country code if needed
		}

		// Check OTP in the database
		$stmt = $this->conn->prepare("SELECT * FROM otp_codes WHERE mobile = ? AND otp = ? AND TIMESTAMPDIFF(MINUTE, created_at, NOW()) <= 5");
		$stmt->bind_param("ss", $mobile, $otp);
		$stmt->execute();
		$result = $stmt->get_result();
		$otp_data = $result->fetch_assoc();
		$stmt->close();

		if (!$otp_data) {
			echo json_encode(['status' => 'error', 'msg' => 'Invalid or expired OTP']);
			return;
		}

		// Fetch user by mobile
		$stmt = $this->conn->prepare("SELECT * FROM users WHERE mobile = ?");
		$stmt->bind_param("s", $mobile);
		$stmt->execute();
		$result = $stmt->get_result();
		$user = $result->fetch_assoc();
		$stmt->close();

		if (!$user) {
			echo json_encode(['status' => 'error', 'msg' => 'User not found']);
			return;
		}

		// Set session (excluding password)
		foreach ($user as $key => $value) {
			if ($key !== 'password') {
				$this->settings->set_userdata($key, $value);
			}
		}

		// Update login status
		$user_id = (int)$user['id'];
		$update_stmt = $this->conn->prepare("UPDATE users SET is_logged_in = 1, last_activity = NOW() WHERE id = ?");
		$update_stmt->bind_param("i", $user_id);
		$update_stmt->execute();
		$update_stmt->close();

		// Delete OTP from the database
		$delete_stmt = $this->conn->prepare("DELETE FROM otp_codes WHERE mobile = ?");
		$delete_stmt->bind_param("s", $mobile);
		$delete_stmt->execute();
		$delete_stmt->close();

		echo json_encode(['status' => 'success', 'msg' => 'OTP verified successfully']);
	}
	public function update_settings()
	{

		$account_sid = $_POST['account_sid'];
		$auth_token = $_POST['auth_token'];
		$twilio_number = $_POST['twilio_number'];

		$query = "UPDATE settings SET account_sid = ?, auth_token = ?, twilio_number = ?, updated_at = NOW()";
		$stmt = $this->conn->prepare($query);
		$stmt->bind_param("sss", $account_sid, $auth_token, $twilio_number);

		if ($stmt->execute()) {
			echo json_encode(['status' => 'success', 'msg' => 'Settings Updated']);
		} else {
			echo json_encode(['status' => 'success', 'msg' => 'Updated failed']);
		}
	}
}

$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$auth = new Login();
switch ($action) {
	case 'update_settings':
		echo $auth->update_settings();
		break;

	case 'createteam':
		echo $auth->createteam();
		break;

	case 'request_join':
		echo $auth->request_join();
		break;
	case 'login':
		echo $auth->login();
		break;
	case 'login_user':
		echo $auth->login_user();
		break;
	case 'register_user':
		echo $auth->register_user();
		break;
	case 'update_profile':
		echo $auth->update_profile();
		break;
	case 'change_password':
		echo $auth->change_password();
		break;
	case 'delete_account':
		echo $auth->delete_account();
		break;
	case 'logout':
		echo $auth->logout();
		break;
	case 'send_otp':
		echo $auth->send_otp();
		break;
	case 'verify_otp':
		echo $auth->verify_otp();
		break;
	default:
		echo $auth->index();
		break;
}
