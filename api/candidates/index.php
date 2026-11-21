<?php

require_once '../config/db.php';
require_once '../helpers.php';


requireLogin();

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        
        $election_id = (int)($_GET['election_id'] ?? 0);

        if ($election_id <= 0) {
            jsonResponse(false, 'Missing or invalid election ID parameter.');
        }

        
        $stmt = $pdo->prepare("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM votes v WHERE v.candidate_id = c.id) as vote_count 
            FROM candidates c 
            WHERE c.election_id = ?
            ORDER BY c.name ASC
        ");
        $stmt->execute([$election_id]);
        $candidates = $stmt->fetchAll();

        
        $stmt = $pdo->prepare("SELECT * FROM elections WHERE id = ?");
        $stmt->execute([$election_id]);
        $election = $stmt->fetch();

        if ($election) {
            $election['status'] = getElectionStatus($election);
        }

        jsonResponse(true, 'Candidates retrieved.', [
            'candidates' => $candidates,
            'election' => $election
        ]);

    } elseif ($method === 'POST') {
        
        requireAdmin();

        $action = sanitize($_POST['action'] ?? '');

        if ($action === 'create') {
            $election_id = (int)($_POST['election_id'] ?? 0);
            $name = sanitize($_POST['name'] ?? '');
            $party = sanitize($_POST['party'] ?? '');
            $manifesto = sanitize($_POST['manifesto'] ?? '');

            if ($election_id <= 0 || empty($name) || empty($party) || empty($manifesto)) {
                jsonResponse(false, 'All candidate fields are required.');
            }

            
            $stmt = $pdo->prepare("SELECT id FROM elections WHERE id = ?");
            $stmt->execute([$election_id]);
            if (!$stmt->fetch()) {
                jsonResponse(false, 'Target election does not exist.');
            }

            
            $photo_path = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['photo']['tmp_name'];
                $file_name = $_FILES['photo']['name'];
                $file_size = $_FILES['photo']['size'];
                $file_type = $_FILES['photo']['type'];
                
                
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                if (!in_array($file_ext, $allowed_extensions)) {
                    jsonResponse(false, 'Invalid image format. Allowed formats: JPG, JPEG, PNG, GIF, WebP.');
                }

                
                if ($file_size > 5 * 1024 * 1024) {
                    jsonResponse(false, 'Photo size must be less than 5MB.');
                }

                
                $upload_dir = '../uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                
                $unique_name = uniqid('candidate_', true) . '.' . $file_ext;
                $dest_path = $upload_dir . $unique_name;

                if (move_uploaded_file($file_tmp, $dest_path)) {
                    
                    $photo_path = 'api/uploads/' . $unique_name;
                } else {
                    jsonResponse(false, 'Failed to save candidate photo file.');
                }
            } else {
                jsonResponse(false, 'Candidate photo is required.');
            }

            
            $stmt = $pdo->prepare("INSERT INTO candidates (election_id, name, party, photo, manifesto) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$election_id, $name, $party, $photo_path, $manifesto]);

            jsonResponse(true, 'Candidate added successfully!');

        } elseif ($action === 'delete') {
            $candidate_id = (int)($_POST['id'] ?? 0);

            if ($candidate_id <= 0) {
                jsonResponse(false, 'Invalid candidate ID.');
            }

            
            $stmt = $pdo->prepare("SELECT photo FROM candidates WHERE id = ?");
            $stmt->execute([$candidate_id]);
            $candidate = $stmt->fetch();

            if ($candidate) {
                if ($candidate['photo']) {
                    
                    $relative_path = '../' . str_replace('api/uploads/', 'uploads/', $candidate['photo']);
                    if (file_exists($relative_path)) {
                        unlink($relative_path);
                    }
                }

                
                $stmt = $pdo->prepare("DELETE FROM candidates WHERE id = ?");
                $stmt->execute([$candidate_id]);
                jsonResponse(true, 'Candidate deleted successfully.');
            } else {
                jsonResponse(false, 'Candidate not found.');
            }

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
