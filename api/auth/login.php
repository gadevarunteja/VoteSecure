<?php

require_once '../config/db.php';
require_once '../helpers.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.', [], 405);
}


$username = sanitize($_POST['username'] ?? ''); 
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    jsonResponse(false, 'Please provide both Email/Voter ID and password.');
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR voter_id = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(false, 'Invalid credentials. User not found.');
    }

    if (!password_verify($password, $user['password'])) {
        jsonResponse(false, 'Invalid credentials. Incorrect password.');
    }

    startSecureSession();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['voter_id'] = $user['voter_id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['status'] = $user['status'];

    jsonResponse(true, 'Login successful!', [
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'voter_id' => $user['voter_id'],
            'role' => $user['role'],
            'status' => $user['status']
        ]
    ]);

} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), [], 500);
}
?>
