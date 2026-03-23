<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Pristup odbijen.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'POST required']); exit;
}

if (!verifyToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Nevažeći CSRF token.']); exit;
}

$type = $_POST['type'] ?? 'image'; // 'image' or 'audio'
$file = $_FILES['file'] ?? null;

if (!$file) {
    echo json_encode(['success' => false, 'message' => 'Fajl nije priložen.']); exit;
}

$path = uploadFile($file, $type);

if (!$path) {
    echo json_encode(['success' => false, 'message' => 'Otpremanje fajla nije uspelo. Proverite tip i veličinu fajla.']); exit;
}

echo json_encode([
    'success'  => true,
    'path'     => $path,
    'url'      => UPLOAD_URL . $path,
]);
