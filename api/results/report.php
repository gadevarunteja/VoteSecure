<?php
// api/results/report.php
// Generates and downloads CSV and PDF reports of election results

require_once '../config/db.php';
require_once '../helpers.php';

// Force admin check
requireAdmin();

$election_id = (int)($_GET['election_id'] ?? 0);
$format = strtolower(sanitize($_GET['format'] ?? ''));

if ($election_id <= 0 || !in_array($format, ['csv', 'pdf'])) {
    // Return standard error page or message
    echo "<h1>Error</h1><p>Missing or invalid election ID or export format (csv/pdf).</p>";
    exit;
}

try {
    // Fetch election detail
    $stmt = $pdo->prepare("SELECT * FROM elections WHERE id = ?");
    $stmt->execute([$election_id]);
    $election = $stmt->fetch();

    if (!$election) {
        echo "<h1>Error</h1><p>Election not found.</p>";
        exit;
    }
    $election['status'] = getElectionStatus($election);

    // Fetch candidates with their vote counts
    $stmt = $pdo->prepare("
        SELECT c.*, 
               (SELECT COUNT(*) FROM votes v WHERE v.candidate_id = c.id) as vote_count
        FROM candidates c
        WHERE c.election_id = ?
        ORDER BY vote_count DESC, c.name ASC
    ");
    $stmt->execute([$election_id]);
    $candidates = $stmt->fetchAll();

    // Total votes cast in this election
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE election_id = ?");
    $stmt->execute([$election_id]);
    $total_votes = (int)$stmt->fetchColumn();

    // Total verified voters (eligible voters in the system)
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'voter' AND status = 'verified'");
    $total_verified_voters = (int)$stmt->fetchColumn();

    // Participation percentage
    $participation_percentage = 0;
    if ($total_verified_voters > 0) {
        $participation_percentage = round(($total_votes / $total_verified_voters) * 100, 2);
    }

    // Identify winner and calculate percentages
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

    $winner_name = 'No votes cast yet';
    if ($max_votes > 0) {
        $winner_name = $is_tie ? 'Tie between multiple candidates' : $winner['name'] . ' (' . $winner['party'] . ')';
    }

    // --- CSV FORMAT ---
    if ($format === 'csv') {
        $filename = 'VoteSecure_Report_Election_' . $election_id . '_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // Write title info
        fputcsv($output, ['VoteSecure Online Voting System - Election Results Report']);
        fputcsv($output, ['Generated At:', date('Y-m-d H:i:s')]);
        fputcsv($output, []);

        // Write Election Metadata
        fputcsv($output, ['ELECTION METADATA']);
        fputcsv($output, ['Election Title', $election['title']]);
        fputcsv($output, ['Description', $election['description']]);
        fputcsv($output, ['Start Time', $election['start_date'] . ' ' . $election['start_time']]);
        fputcsv($output, ['End Time', $election['end_date'] . ' ' . $election['end_time']]);
        fputcsv($output, ['Current Status', $election['status']]);
        fputcsv($output, []);

        // Write Stats
        fputcsv($output, ['PARTICIPATION STATISTICS']);
        fputcsv($output, ['Total Eligible Voters', $total_verified_voters]);
        fputcsv($output, ['Total Votes Cast', $total_votes]);
        fputcsv($output, ['Participation Rate (%)', $participation_percentage . '%']);
        fputcsv($output, ['Election Winner', $winner_name]);
        fputcsv($output, []);

        // Write Candidate Standings Table
        fputcsv($output, ['CANDIDATE STANDINGS']);
        fputcsv($output, ['Candidate Name', 'Political Party', 'Votes Received', 'Percentage (%)']);

        foreach ($candidates as $cand) {
            fputcsv($output, [
                $cand['name'],
                $cand['party'],
                $cand['vote_count'],
                $cand['percentage'] . '%'
            ]);
        }

        fclose($output);
        exit;
    }

    // --- PDF FORMAT ---
    if ($format === 'pdf') {
        $filename = 'VoteSecure_Report_Election_' . $election_id . '_' . date('Ymd_His') . '.pdf';

        $pdf = new SimplePDF();
        $pdf->addPage();

        // Title and Header
        $pdf->rect(20, 20, 555, 60); // Header box
        $pdf->text(40, 40, "VoteSecure - Online Voting Management System", 16, true);
        $pdf->text(40, 62, "Official Election Results Report - Confidential", 11);
        $pdf->text(430, 62, "Date: " . date('Y-m-d H:i:s'), 9);

        // Election Details Section
        $pdf->text(20, 110, "ELECTION PROFILE", 13, true);
        $pdf->line(20, 115, 200, 115);

        $pdf->text(20, 135, "Title:", 10, true);
        $pdf->text(100, 135, $election['title'], 10);

        $pdf->text(20, 155, "Description:", 10, true);
        $desc = strlen($election['description']) > 80 ? substr($election['description'], 0, 77) . '...' : $election['description'];
        $pdf->text(100, 155, $desc, 10);

        $pdf->text(20, 175, "Start Time:", 10, true);
        $pdf->text(100, 175, $election['start_date'] . ' ' . $election['start_time'] . ' (Asia/Kolkata)', 10);

        $pdf->text(20, 195, "End Time:", 10, true);
        $pdf->text(100, 195, $election['end_date'] . ' ' . $election['end_time'] . ' (Asia/Kolkata)', 10);

        $pdf->text(20, 215, "Status:", 10, true);
        $pdf->text(100, 215, $election['status'], 10, true);

        // Stats Section
        $pdf->text(340, 110, "VOTER PARTICIPATION", 13, true);
        $pdf->line(340, 115, 520, 115);

        // Stats card box
        $pdf->rect(340, 125, 235, 100);
        $pdf->text(355, 145, "Total Eligible Voters:", 10, true);
        $pdf->text(500, 145, $total_verified_voters, 10);

        $pdf->text(355, 165, "Total Votes Cast:", 10, true);
        $pdf->text(500, 165, $total_votes, 10);

        $pdf->text(355, 185, "Participation Rate:", 10, true);
        $pdf->text(500, 185, $participation_percentage . "%", 10, true);

        $pdf->text(355, 205, "Winner:", 10, true);
        $winner_short = strlen($winner_name) > 22 ? substr($winner_name, 0, 19) . '...' : $winner_name;
        $pdf->text(500, 205, $winner_short, 10, true);

        // Candidate Standings Section
        $pdf->text(20, 260, "CANDIDATE STANDINGS & RESULTS", 13, true);
        $pdf->line(20, 265, 260, 265);

        // Table Header
        $pdf->rect(20, 280, 555, 25, true); // Filled box for header
        // Since rect is filled (op is 'f'), text should be drawn over it.
        // Wait, standard filled box color is black in basic PDF stream unless we set fill color.
        // To be safe, let's draw an unfilled box for the table header so black text is visible.
        $pdf->rect(20, 280, 555, 25, false);
        $pdf->text(30, 296, "Candidate Name", 10, true);
        $pdf->text(200, 296, "Political Party", 10, true);
        $pdf->text(380, 296, "Votes Received", 10, true);
        $pdf->text(480, 296, "Percentage", 10, true);

        // Table Rows
        $y = 305;
        foreach ($candidates as $cand) {
            // Draw row separator line
            $pdf->line(20, $y, 575, $y);
            $pdf->rect(20, $y, 555, 25, false);

            $pdf->text(30, $y + 16, $cand['name'], 9);
            $pdf->text(200, $y + 16, $cand['party'], 9);
            $pdf->text(380, $y + 16, $cand['vote_count'], 9);
            $pdf->text(480, $y + 16, $cand['percentage'] . "%", 9, true);

            $y += 25;
            // Handle page overflow if candidates list is extremely long
            if ($y > 780) {
                $pdf->addPage();
                $y = 40;
                $pdf->rect(20, $y, 555, 25, false);
                $pdf->text(30, $y + 16, "Candidate Name", 10, true);
                $pdf->text(200, $y + 16, "Political Party", 10, true);
                $pdf->text(380, $y + 16, "Votes Received", 10, true);
                $pdf->text(480, $y + 16, "Percentage", 10, true);
                $y += 25;
            }
        }

        // Footer note
        $pdf->line(20, 800, 575, 800);
        $pdf->text(20, 815, "VoteSecure System - Automated Report Generation. No signature required.", 8);
        $pdf->text(500, 815, "Page 1 of 1", 8);

        $pdf->output($filename);
        exit;
    }

} catch (PDOException $e) {
    echo "<h1>Database Error</h1><p>" . $e->getMessage() . "</p>";
    exit;
}
?>
