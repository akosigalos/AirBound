<?php
require_once __DIR__.'/config.php';
session_start();

// Support both JSON (AJAX) and traditional form POST submissions.
$input = null;
$isForm = false;

if (!empty($_POST['email']) && isset($_POST['password'])) {
	$input = ['email' => $_POST['email'], 'password' => $_POST['password']];
	$isForm = true;
} else {
	$raw = file_get_contents('php://input');
	if ($raw !== false && trim($raw) !== '') {
		$json = json_decode($raw, true);
		if (is_array($json)) {
			$input = $json;
		} else {
			parse_str($raw, $parsed);
			if (!empty($parsed)) {
				$input = $parsed;
			}
		}
	}
}

if(!$input) {
	if($isForm){ header('Location: ../login.php?error=invalid_input'); exit; }
	send_json(['success'=>false,'error'=>'Invalid input']);
}

$email = strtolower(trim($input['email'] ?? ''));
$password = $input['password'] ?? '';
if(!filter_var($email, FILTER_VALIDATE_EMAIL) || !$password) send_json(['success'=>false,'error'=>'Validation failed']);

$stmt = $pdo->prepare('SELECT id, name, password_hash FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();
if(!$user || !password_verify($password, $user['password_hash'])){
	if($isForm){ header('Location: ../login.php?error=invalid_credentials'); exit; }
	send_json(['success'=>false,'error'=>'Invalid credentials']);
}

// set session
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
session_regenerate_id(true);

// If this was a regular form POST, redirect to the admin realtime monitoring page.
if($isForm){ header('Location: ../admin_monitoring.php'); exit; }

send_json(['success'=>true]);
