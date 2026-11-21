<?php
// api/voters/index.php
// Admin Voters Management (list, verify, delete voters)

require_once '../config/db.php';
require_once '../helpers.php';

// Force admin check
requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Fetch all registered voters (exclude admins)
        $stmt = $pdo->query("SELECT id, name, voter_id, email, mobile, dob, status, created_at FROM users WHERE role = 'voter' ORDER BY created_at DESC");
        $voters = $stmt->fetchAll();
        jsonResponse(true, 'Voters fetched successfully.', ['voters' => $voters]);

    } elseif ($method === 'POST') {
        // Handle Action (verify or delete)
        $action = sanitize($_POST['action'] ?? '');
        $voter_db_id = (int)($_POST['id'] ?? 0); // User Primary Key ID

        if (empty($action) || $voter_db_id <= 0) {
            jsonResponse(false, 'Missing required action or ID parameters.');
        }

        // Check if the voter actually exists and is not an admin
        $stmt = $pdo->prepare("SELECT id, role FROM users WHERE id = ?");
        $stmt->execute([$voter_db_id]);
        $voter = $stmt->fetch();

        if (!$voter) {
            jsonResponse(false, 'Voter not found.');
        }
        if ($voter['role'] === 'admin') {
            jsonResponse(false, 'Admin account cannot be modified via voter management.');
        }

        if ($action === 'verify') {
            // Verify voter
            $stmt = $pdo->prepare("UPDATE users SET status = 'verified' WHERE id = ?");
            $stmt->execute([$voter_db_id]);
            jsonResponse(true, 'Voter verified successfully.');

        } elseif ($action === 'delete') {
            // Delete voter (associated votes will be automatically deleted due to cascade)
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$voter_db_id]);
            jsonResponse(true, 'Voter deleted successfully.');

        } else {
            jsonResponse(false, 'Unsupported action.');
        }

    } else {
        jsonResponse(false, 'Method not allowed.', [], 405);
    }

} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), [], 500);
}
?>
