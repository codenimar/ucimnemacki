<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$db     = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    // GET /api/tests.php?action=list&category=1
    case 'list':
        $catId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
        $diff  = $_GET['difficulty'] ?? '';
        $where = [];
        $params= [];
        $types = '';
        if ($catId) { $where[] = 'c.id = ?'; $params[] = $catId; $types .= 'i'; }
        if ($diff)  { $where[] = 't.difficulty = ?'; $params[] = $diff; $types .= 's'; }
        $sql  = 'SELECT t.id, t.title, t.description, t.difficulty, t.time_limit, t.passing_score,
                        s.name AS sub_name, c.name AS cat_name, c.color AS cat_color
                 FROM tests t JOIN subcategories s ON t.subcategory_id=s.id JOIN categories c ON s.category_id=c.id'
              . ($where ? ' WHERE '.implode(' AND ',$where) : '')
              . ' ORDER BY c.sort_order, t.id';
        $stmt = $db->prepare($sql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        echo json_encode(['success'=>true,'tests'=>$stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
        $stmt->close();
        break;

    // GET /api/tests.php?action=get&id=1
    case 'get':
        $testId = (int)($_GET['id'] ?? 0);
        if (!$testId) { echo json_encode(['error'=>'ID required']); exit; }
        $stmt = $db->prepare('SELECT * FROM tests WHERE id=?');
        $stmt->bind_param('i', $testId);
        $stmt->execute();
        $test = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$test) { echo json_encode(['error'=>'Test nije pronađen.']); exit; }
        echo json_encode(['success'=>true,'test'=>$test]);
        break;

    // GET /api/tests.php?action=questions&test_id=1
    case 'questions':
        $testId = (int)($_GET['test_id'] ?? 0);
        if (!$testId) { echo json_encode(['error'=>'test_id required']); exit; }
        $stmt = $db->prepare('SELECT * FROM questions WHERE test_id=? ORDER BY sort_order, id');
        $stmt->bind_param('i', $testId);
        $stmt->execute();
        $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        foreach ($questions as &$q) {
            $o = $db->prepare('SELECT * FROM question_options WHERE question_id=? ORDER BY sort_order');
            $o->bind_param('i', $q['id']); $o->execute();
            $q['options'] = $o->get_result()->fetch_all(MYSQLI_ASSOC); $o->close();
            $m = $db->prepare('SELECT * FROM question_media WHERE question_id=?');
            $m->bind_param('i', $q['id']); $m->execute();
            $q['media'] = $m->get_result()->fetch_all(MYSQLI_ASSOC); $m->close();
        }
        echo json_encode(['success'=>true,'questions'=>$questions]);
        break;

    // POST /api/tests.php?action=submit
    case 'submit':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }
        $body   = json_decode(file_get_contents('php://input'), true) ?? [];
        $testId = (int)($body['test_id'] ?? 0);
        if (!$testId) { echo json_encode(['error'=>'test_id required']); exit; }
        // This is handled client-side + via progress API; return success
        echo json_encode(['success'=>true]);
        break;

    default:
        echo json_encode(['error'=>'Nepoznata akcija.']);
}
