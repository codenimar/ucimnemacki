<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Pristup odbijen.']);
    exit;
}

$db      = getDB();
$adminId = (int)$_SESSION['user_id'];
$body    = json_decode(file_get_contents('php://input'), true) ?? [];
$postData= array_merge($_POST, $body);
$action  = $postData['action'] ?? '';

switch ($action) {
    case 'delete':
        $type = $postData['target_type'] ?? '';
        $id   = (int)($postData['target_id'] ?? 0);
        if (!$type || !$id) { echo json_encode(['success'=>false,'message'=>'Nedostaju podaci.']); exit; }

        $allowed = ['categories','subcategories','tests','questions','live_teachers','users','vocabulary','grammar_lessons','achievements'];
        if (!in_array($type, $allowed, true)) {
            echo json_encode(['success'=>false,'message'=>'Nevažeći tip.']); exit;
        }

        // Prevent admin from deleting themselves
        if ($type === 'users' && $id === $adminId) {
            echo json_encode(['success'=>false,'message'=>'Ne možete obrisati sopstveni nalog.']); exit;
        }

        $stmt = $db->prepare("DELETE FROM `{$type}` WHERE id = ?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            logAdminAction($adminId, 'delete', $type, $id);
            echo json_encode(['success'=>true]);
        } else {
            echo json_encode(['success'=>false,'message'=>'Brisanje nije uspelo.']);
        }
        break;

    case 'stats':
        $stats = [];
        foreach (['users','tests','vocabulary','grammar_lessons','live_teachers','user_progress'] as $tbl) {
            $res = $db->query("SELECT COUNT(*) AS cnt FROM `{$tbl}`");
            $stats[$tbl] = (int)($res->fetch_assoc()['cnt'] ?? 0);
        }
        echo json_encode(['success'=>true,'stats'=>$stats]);
        break;

    default:
        echo json_encode(['error'=>'Nepoznata akcija.']);
}
