<?php
// api/helpers.php
// Common utility functions for VoteSecure

// Start session if not already started
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Set secure cookie flags
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        session_start();
    }
}

// Return JSON response and exit
function jsonResponse($success, $message, $data = [], $code = 200) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($code);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit;
}

// Clean and sanitize string inputs to prevent XSS
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Check if user is logged in
function isLoggedIn() {
    startSecureSession();
    return isset($_SESSION['user_id']);
}

// Check if logged-in user is an admin
function isAdmin() {
    startSecureSession();
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Force login check, exit with JSON if fails
function requireLogin() {
    if (!isLoggedIn()) {
        jsonResponse(false, 'Unauthorized. Please login.', [], 401);
    }
}

// Force admin check, exit with JSON if fails
function requireAdmin() {
    if (!isAdmin()) {
        jsonResponse(false, 'Forbidden. Admin access required.', [], 403);
    }
}

// Calculate the status of an election dynamically
// Timezone: Asia/Kolkata
function getElectionStatus($election) {
    date_default_timezone_set('Asia/Kolkata');
    
    try {
        $current = new DateTime('now');
        $start = new DateTime($election['start_date'] . ' ' . $election['start_time']);
        $end = new DateTime($election['end_date'] . ' ' . $election['end_time']);
        
        if ($current < $start) {
            return 'Upcoming';
        } elseif ($current >= $start && $current <= $end) {
            return 'Active';
        } else {
            return 'Completed';
        }
    } catch (Exception $e) {
        return 'Unknown';
    }
}

/**
 * Pure PHP SimplePDF Generator Class
 * Generates standards-compliant PDF/1.4 binaries with zero dependencies.
 */
class SimplePDF {
    private $buffer = '';
    private $offsets = [];
    private $objects = [];
    private $currentPage = 0;
    private $pageObjects = [];
    private $contentStreams = [];

    public function __construct() {
        $this->buffer = "%PDF-1.4\n";
    }

    private function newObject() {
        $id = count($this->objects) + 1;
        $this->objects[$id] = '';
        return $id;
    }

    private function writeObject($id, $data) {
        $this->offsets[$id] = strlen($this->buffer);
        $this->objects[$id] = $data;
        $this->buffer .= "$id 0 obj\n$data\nendobj\n";
    }

    public function addPage() {
        $this->currentPage = $this->newObject();
        $this->pageObjects[] = $this->currentPage;
        $this->contentStreams[$this->currentPage] = '';
    }

    public function text($x, $y, $text, $size = 12, $bold = false) {
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        $pdfY = 842 - $y;
        $fontRef = $bold ? '/F2' : '/F1';
        $this->contentStreams[$this->currentPage] .= "BT\n$fontRef $size Tf\n$x $pdfY Td\n($text) Tj\nET\n";
    }

    public function rect($x, $y, $w, $h, $fill = false) {
        $pdfY = 842 - $y - $h;
        $op = $fill ? 'f' : 's';
        $this->contentStreams[$this->currentPage] .= "$x $pdfY $w $h re $op\n";
    }

    public function line($x1, $y1, $x2, $y2) {
        $pdfY1 = 842 - $y1;
        $pdfY2 = 842 - $y2;
        $this->contentStreams[$this->currentPage] .= "$x1 $pdfY1 m $x2 $pdfY2 l s\n";
    }

    public function output($filename = 'report.pdf') {
        // Define fonts
        $fontRegularId = $this->newObject();
        $this->writeObject($fontRegularId, "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>");

        $fontBoldId = $this->newObject();
        $this->writeObject($fontBoldId, "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>");

        // Write page contents
        $streamRefs = [];
        foreach ($this->pageObjects as $pageId) {
            $streamId = $this->newObject();
            $streamContent = $this->contentStreams[$pageId];
            $len = strlen($streamContent);
            $this->writeObject($streamId, "<< /Length $len >>\nstream\n" . $streamContent . "\nendstream");
            $streamRefs[$pageId] = $streamId;
        }

        // Write catalog & pages root
        $catalogId = $this->newObject();
        $pagesId = $this->newObject();

        foreach ($this->pageObjects as $pageId) {
            $streamId = $streamRefs[$pageId];
            $this->writeObject($pageId, "<< /Type /Page\n/Parent $pagesId 0 R\n/Resources << /Font << /F1 $fontRegularId 0 R /F2 $fontBoldId 0 R >> >>\n/MediaBox [0 0 595.28 841.89]\n/Contents $streamId 0 R >>");
        }

        // Write pages root object
        $kids = '[' . implode(' 0 R ', $this->pageObjects) . ' 0 R]';
        $count = count($this->pageObjects);
        $this->writeObject($pagesId, "<< /Type /Pages /Kids $kids /Count $count >>");

        // Write catalog
        $this->writeObject($catalogId, "<< /Type /Catalog /Pages $pagesId 0 R >>");

        // Cross-reference table offset
        $xrefOffset = strlen($this->buffer);
        $this->buffer .= "xref\n0 " . (count($this->offsets) + 1) . "\n";
        $this->buffer .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($this->offsets); $i++) {
            $this->buffer .= sprintf("%010d 00000 n \n", $this->offsets[$i]);
        }

        $this->buffer .= "trailer\n<< /Size " . (count($this->offsets) + 1) . " /Root $catalogId 0 R >>\n";
        $this->buffer .= "startxref\n$xrefOffset\n%%EOF";

        // Set headers for download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($this->buffer));
        echo $this->buffer;
        exit;
    }
}
?>
