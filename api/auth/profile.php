<?php

require_once '../config/db.php';
require_once '../helpers.php';


requireLogin();

$user_id = $_SESSION['user_id'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        
        $stmt = $pdo->prepare("SELECT id, name, voter_id, email, mobile, dob, role, status, created_at FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (!$user) {
            jsonResponse(false, 'User not found.');
        }

        
        jsonResponse(true, 'Profile retrieved.', ['user' => $user]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        $name = sanitize($_POST['name'] ?? '');
        $mobile = sanitize($_POST['mobile'] ?? '');
        $dob = sanitize($_POST['dob'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($name) || empty($mobile) || empty($dob)) {
            jsonResponse(false, 'Name, mobile, and date of birth are required.');
        }


        try {
            $dob_date = new DateTime($dob);
            $today = new DateTime();
            $age = $today->diff($dob_date)->y;
            if ($age < 18) {
                jsonResponse(false, 'You must be at least 18 years old.');
            }
        } catch (Exception $e) {
            jsonResponse(false, 'Invalid Date of Birth.');
        }

        
        if (!empty($password)) {
            if (strlen($password) < 4) {
                jsonResponse(false, 'Password must be at least 4 characters long.');
            }
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, mobile = ?, dob = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $mobile, $dob, $hashed_password, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, mobile = ?, dob = ? WHERE id = ?");
            $stmt->execute([$name, $mobile, $dob, $user_id]);
        }

        
        $stmt = $pdo->prepare("SELECT id, name, voter_id, email, mobile, dob, role, status FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $updated_user = $stmt->fetch();

        
        $_SESSION['name'] = $updated_user['name'];

        jsonResponse(true, 'Profile updated successfully!', ['user' => $updated_user]);
    } else {
        jsonResponse(false, 'Invalid request method.', [], 405);
    }

} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), [], 500);
}
?>
