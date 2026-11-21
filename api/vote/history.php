<?php
// api/vote/history.php
// Retrieves voting history of the logged-in voter

require_once '../config/db.php';
require_once '../helpers.php';

// Require login
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Invalid request method.', [], 405);
}

$voter_db_id = (int)$_SESSION['user_id'];

try {
    // Fetch all votes by user, including election and candidate information
    $stmt = $pdo->prepare("
        SELECT v.casted_at, e.title as election_title, e.start_date, e.end_date,
               c.name as candidate_name, c.party as candidate_party, c.photo as candidate_photo
        FROM votes v
        JOIN elections e ON v.election_id = e.id
        JOIN candidates c ON v.candidate_id = c.id
        WHERE v.voter_id = ?
        ORDER BY v.casted_at DESC
    ");
    $stmt->execute([$voter_db_id]);
    $history = $stmt->fetchAll();

    jsonResponse(true, 'Voting history retrieved.', ['history' => $history]);

} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), [], 500);
}
?>
