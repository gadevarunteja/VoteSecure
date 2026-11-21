<?php
// api/vote/status.php
// Check voting status of current user in a specific election

require_once '../config/db.php';
require_once '../helpers.php';

// Require login
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Invalid request method.', [], 405);
}

$voter_db_id = (int)$_SESSION['user_id'];
$election_id = (int)($_GET['election_id'] ?? 0);

if ($election_id <= 0) {
    jsonResponse(false, 'Missing or invalid election ID.');
}

try {
    $stmt = $pdo->prepare("SELECT v.*, c.name as candidate_name, c.party as candidate_party FROM votes v LEFT JOIN candidates c ON v.candidate_id = c.id WHERE v.voter_id = ? AND v.election_id = ?");
    $stmt->execute([$voter_db_id, $election_id]);
    $vote = $stmt->fetch();

    if ($vote) {
        jsonResponse(true, 'Voting status retrieved.', [
            'voted' => true,
            'vote' => $vote
        ]);
    } else {
        jsonResponse(true, 'Voting status retrieved.', [
            'voted' => false
        ]);
    }

} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), [], 500);
}
?>
