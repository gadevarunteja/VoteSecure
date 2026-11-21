<?php


require_once '../config/db.php';
require_once '../helpers.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.', [], 405);
}

$name = sanitize($_POST['name'] ?? '');
$voter_id = sanitize($_POST['voter_id'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$mobile = sanitize($_POST['mobile'] ?? '');
$dob = sanitize($_POST['dob'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';


if (empty($name) || empty($voter_id) || empty($email) || empty($mobile) || empty($dob) || empty($password) || empty($confirm_password)) {
    jsonResponse(false, 'All fields are required.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Invalid email format.');
}

if ($password !== $confirm_password) {
    jsonResponse(false, 'Passwords do not match.');
}

if (strlen($password) < 4) {
    jsonResponse(false, 'Password must be at least 4 characters long.');
}

try {
    $dob_date = new DateTime($dob);
    $today = new DateTime();
    $age = $today->diff($dob_date)->y;
    if ($age < 18) {
        jsonResponse(false, 'Voters must be at least 18 years old.');
    }
} catch (Exception $e) {
    jsonResponse(false, 'Invalid date of birth format.');
}


try {
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE voter_id = ?");
    $stmt->execute([$voter_id]);
    if ($stmt->fetch()) {
        jsonResponse(false, 'Voter ID is already registered.');
    }

    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(false, 'Email address is already registered.');
    }

    
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    
    $stmt = $pdo->prepare("INSERT INTO users (name, voter_id, email, mobile, dob, password, role, status) VALUES (?, ?, ?, ?, ?, ?, 'voter', 'pending')");
    $stmt->execute([$name, $voter_id, $email, $mobile, $dob, $hashed_password]);

    jsonResponse(true, 'Registration successful! Please wait for the admin to verify your account.');

} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), [], 500);
}
?>
