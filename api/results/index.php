<?php


require_once '../config/db.php';
require_once '../helpers.php';

// Check login
requireLogin();

$election_id = (int)($_GET['election_id'] ?? 0);

try {
    if ($election_id > 0) {
        
        $stmt = $pdo->prepare("SELECT * FROM elections WHERE id = ?");
        $stmt->execute([$election_id]);
        $election = $stmt->fetch();

        if (!$election) {
            jsonResponse(false, 'Election not found.');
        }
        $election['status'] = getElectionStatus($election);

        
        $stmt = $pdo->prepare("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM votes v WHERE v.candidate_id = c.id) as vote_count
            FROM candidates c
            WHERE c.election_id = ?
            ORDER BY vote_count DESC, c.name ASC
        ");
        $stmt->execute([$election_id]);
        $candidates = $stmt->fetchAll();

        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE election_id = ?");
        $stmt->execute([$election_id]);
        $total_votes = (int)$stmt->fetchColumn();

        
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'voter' AND status = 'verified'");
        $total_verified_voters = (int)$stmt->fetchColumn();

        
        $participation_percentage = 0;
        if ($total_verified_voters > 0) {
            $participation_percentage = round(($total_votes / $total_verified_voters) * 100, 2);
        }

        
        $winner = null;
        $max_votes = -1;
        $is_tie = false;

        foreach ($candidates as &$candidate) {
            $candidate['vote_count'] = (int)$candidate['vote_count'];
            $candidate['percentage'] = 0;
            if ($total_votes > 0) {
                $candidate['percentage'] = round(($candidate['vote_count'] / $total_votes) * 100, 2);
            }

            
            if ($candidate['vote_count'] > $max_votes) {
                $max_votes = $candidate['vote_count'];
                $winner = $candidate;
                $is_tie = false;
            } elseif ($candidate['vote_count'] === $max_votes && $max_votes > 0) {
                $is_tie = true;
            }
        }
        unset($candidate);

        
        $winner_text = 'No votes cast yet';
        $winner_party = '';
        $winner_photo = '';
        $winner_votes = 0;
        $winner_pct = 0;

        if ($max_votes > 0) {
            if ($is_tie) {
                $winner_text = 'Tie between multiple candidates';
            } else {
                $winner_text = $winner['name'];
                $winner_party = $winner['party'];
                $winner_photo = $winner['photo'];
                $winner_votes = $winner['vote_count'];
                $winner_pct = $winner['percentage'];
            }
        }

        jsonResponse(true, 'Election results retrieved.', [
            'election' => $election,
            'candidates' => $candidates,
            'statistics' => [
                'total_votes' => $total_votes,
                'total_eligible' => $total_verified_voters,
                'participation_percentage' => $participation_percentage,
                'winner' => [
                    'name' => $winner_text,
                    'party' => $winner_party,
                    'photo' => $winner_photo,
                    'votes' => $winner_votes,
                    'percentage' => $winner_pct
                ]
            ]
        ]);

    } else {
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'voter'");
        $total_registered_voters = (int)$stmt->fetchColumn();

        
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'voter' AND status = 'verified'");
        $total_verified_voters = (int)$stmt->fetchColumn();

        
        $stmt = $pdo->query("SELECT COUNT(*) FROM votes");
        $total_votes_cast = (int)$stmt->fetchColumn();

        
        $stmt = $pdo->query("SELECT * FROM elections");
        $elections = $stmt->fetchAll();

        $active_elections = 0;
        $completed_elections = 0;
        $upcoming_elections = 0;

        foreach ($elections as $elec) {
            $status = getElectionStatus($elec);
            if ($status === 'Active') {
                $active_elections++;
            } elseif ($status === 'Completed') {
                $completed_elections++;
            } elseif ($status === 'Upcoming') {
                $upcoming_elections++;
            }
        }

        
        // Total votes cast / (Total verified voters * Total elections) * 100
        $total_elections_count = count($elections);
        $participation_percentage = 0;
        if ($total_verified_voters > 0 && $total_elections_count > 0) {
            $participation_percentage = round(($total_votes_cast / ($total_verified_voters * $total_elections_count)) * 100, 2);
            // Cap at 100 
            if ($participation_percentage > 100) {
                $participation_percentage = 100;
            }
        }

        jsonResponse(true, 'Global stats retrieved.', [
            'statistics' => [
                'total_voters' => $total_registered_voters,
                'verified_voters' => $total_verified_voters,
                'total_votes' => $total_votes_cast,
                'active_elections' => $active_elections,
                'completed_elections' => $completed_elections,
                'upcoming_elections' => $upcoming_elections,
                'participation_percentage' => $participation_percentage
            ]
        ]);
    }

} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), [], 500);
}
?>
