<?php


require_once '../config/db.php';
require_once '../helpers.php';


requireLogin();

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        
        $stmt = $pdo->query("
            SELECT e.*, 
                   (SELECT COUNT(*) FROM candidates c WHERE c.election_id = e.id) as candidate_count,
                   (SELECT COUNT(*) FROM votes v WHERE v.election_id = e.id) as vote_count
            FROM elections e 
            ORDER BY e.start_date DESC, e.start_time DESC
        ");
        $elections = $stmt->fetchAll();

        
        foreach ($elections as &$election) {
            $election['status'] = getElectionStatus($election);
        }
        unset($election); 

        jsonResponse(true, 'Elections retrieved successfully.', ['elections' => $elections]);

    } elseif ($method === 'POST') {
        
        requireAdmin();

        $action = sanitize($_POST['action'] ?? '');

        if ($action === 'create') {
            $title = sanitize($_POST['title'] ?? '');
            $description = sanitize($_POST['description'] ?? '');
            $start_date = sanitize($_POST['start_date'] ?? '');
            $start_time = sanitize($_POST['start_time'] ?? '');
            $end_date = sanitize($_POST['end_date'] ?? '');
            $end_time = sanitize($_POST['end_time'] ?? '');

            if (empty($title) || empty($description) || empty($start_date) || empty($start_time) || empty($end_date) || empty($end_time)) {
                jsonResponse(false, 'All election fields are required.');
            }

            
            try {
                date_default_timezone_set('Asia/Kolkata');
                $start_dt = new DateTime($start_date . ' ' . $start_time);
                $end_dt = new DateTime($end_date . ' ' . $end_time);
                $current_dt = new DateTime('now');

                if ($end_dt <= $start_dt) {
                    jsonResponse(false, 'End date & time must be after start date & time.');
                }
                
                
                if ($end_dt <= $current_dt) {
                    jsonResponse(false, 'End date & time cannot be in the past.');
                }
            } catch (Exception $e) {
                jsonResponse(false, 'Invalid date or time formats provided.');
            }

            
            $stmt = $pdo->prepare("INSERT INTO elections (title, description, start_date, start_time, end_date, end_time) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $start_date, $start_time, $end_date, $end_time]);

            jsonResponse(true, 'Election created successfully!');

        } elseif ($action === 'delete') {
            $election_id = (int)($_POST['id'] ?? 0);

            if ($election_id <= 0) {
                jsonResponse(false, 'Invalid election ID.');
            }

            // Delete the election 
            $stmt = $pdo->prepare("DELETE FROM elections WHERE id = ?");
            $stmt->execute([$election_id]);

            jsonResponse(true, 'Election deleted successfully.');

        } else {
            jsonResponse(false, 'Unsupported write action.');
        }
    } else {
        jsonResponse(false, 'Method not allowed.', [], 405);
    }

} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), [], 500);
}
?>
