<?php
// api/vote/index.php
// Casting Vote endpoint

require_once '../config/db.php';
require_once '../helpers.php';

// Require voter login
requireLogin();

// Accept only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.', [], 405);
}

$voter_db_id = (int)$_SESSION['user_id'];
$election_id = (int)($_POST['election_id'] ?? 0);
$candidate_id = (int)($_POST['candidate_id'] ?? 0);

if ($election_id <= 0 || $candidate_id <= 0) {
    jsonResponse(false, 'Election ID and Candidate ID are required.');
}

try {
    // 1. Verify Voter Status (Must be verified)
    $stmt = $pdo->prepare("SELECT status, role FROM users WHERE id = ?");
    $stmt->execute([$voter_db_id]);
    $voter = $stmt->fetch();

    if (!$voter) {
        jsonResponse(false, 'Voter record not found.');
    }
    
    if ($voter['role'] === 'admin') {
        jsonResponse(false, 'Administrators are not permitted to vote.');
    }

    if ($voter['status'] !== 'verified') {
        jsonResponse(false, 'Your account is not verified yet. Voting requires admin verification.');
    }

    // 2. Verify Election and calculate its dynamic status
    $stmt = $pdo->prepare("SELECT * FROM elections WHERE id = ?");
    $stmt->execute([$election_id]);
    $election = $stmt->fetch();

    if (!$election) {
        jsonResponse(false, 'Target election does not exist.');
    }

    $status = getElectionStatus($election);
    if ($status !== 'Active') {
        jsonResponse(false, "Voting is not allowed. This election is currently $status.");
    }

    // 3. Verify Candidate belongs to the specified Election
    $stmt = $pdo->prepare("SELECT id FROM candidates WHERE id = ? AND election_id = ?");
    $stmt->execute([$candidate_id, $election_id]);
    if (!$stmt->fetch()) {
        jsonResponse(false, 'Invalid candidate for the selected election.');
    }

    // 4. Verify Voter has NOT already voted in this Election (Prevent duplicate voting)
    $stmt = $pdo->prepare("SELECT id FROM votes WHERE voter_id = ? AND election_id = ?");
    $stmt->execute([$voter_db_id, $election_id]);
    if ($stmt->fetch()) {
        jsonResponse(false, 'Duplicate voting is prohibited. You have already voted in this election.');
    }

    // 5. Record the vote in the database
    $stmt = $pdo->prepare("INSERT INTO votes (voter_id, election_id, candidate_id) VALUES (?, ?, ?)");
    $stmt->execute([$voter_db_id, $election_id, $candidate_id]);

    jsonResponse(true, 'Your vote has been cast successfully! Thank you for participating.');

} catch (PDOException $e) {
    // Check if duplicate entry constraint caught it (redundant layer of safety)
    if ($e->getCode() == 23000) {
        jsonResponse(false, 'You have already voted in this election.');
    }
    jsonResponse(false, 'Database error: ' . $e->getMessage(), [], 500);
}
?>
