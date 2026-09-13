<?php
require_once __DIR__.'/config.php';
// Accept JSON or form POST submissions.
$input = null;
if (!empty($_POST)) {
  $input = $_POST;
} else {
  $raw = file_get_contents('php://input');
  if ($raw !== false && trim($raw) !== '') {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
      $input = $decoded;
    } else {
      parse_str($raw, $parsed);
      if (!empty($parsed)) {
        $input = $parsed;
      }
    }
  }
}

if(!$input) send_json(['success'=>false,'error'=>'Invalid input']);
$name = trim($input['name'] ?? '');
$email = strtolower(trim($input['email'] ?? ''));
$password = $input['password'] ?? '';
if(!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) send_json(['success'=>false,'error'=>'Validation failed']);

// check duplicate
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if($stmt->fetch()) send_json(['success'=>false,'error'=>'Email already registered']);

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO users (name,email,password_hash,created_at) VALUES (?, ?, ?, NOW())');
try{
  $stmt->execute([$name,$email,$hash]);
  send_json(['success'=>true]);
}catch(Exception $e){
  send_json(['success'=>false,'error'=>'Failed to register']);
}
  