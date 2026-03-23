<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$db     = getDB();
$action = $_GET['action'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$postData = array_merge($_POST, $body);

switch ($action ?: ($postData['action'] ?? '')) {
    // POST – save progress after completing a test
    case 'save': case '':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }
        if (!isLoggedIn()) { echo json_encode(['success'=>false,'message'=>'Morate biti prijavljeni.']); exit; }
        $userId    = (int)$_SESSION['user_id'];
        $testId    = (int)($postData['test_id']   ?? 0);
        $score     = (int)($postData['score']     ?? 0);
        $maxScore  = (int)($postData['max_score'] ?? 0);
        $timeSpent = (int)($postData['time_spent']?? 0);
        if (!$testId) { echo json_encode(['error'=>'test_id required']); exit; }

        $stmt = $db->prepare('INSERT INTO user_progress (user_id, test_id, score, max_score, time_spent) VALUES (?,?,?,?,?)');
        $stmt->bind_param('iiiii', $userId, $testId, $score, $maxScore, $timeSpent);
        $stmt->execute();
        $stmt->close();

        // Award points
        awardPoints($userId, $score);

        // Update streak
        $db->prepare('UPDATE users SET streak = streak + 1 WHERE id = ?')->execute() ?: null;
        $upd = $db->prepare('UPDATE users SET streak = streak + 1 WHERE id = ?');
        $upd->bind_param('i', $userId);
        $upd->execute(); $upd->close();

        $newAch = checkAchievements($userId);
        echo json_encode(['success'=>true,'new_achievements'=>$newAch]);
        break;

    // GET progress for current user
    case 'get':
        if (!isLoggedIn()) { echo json_encode(['success'=>false,'message'=>'Nije prijavljen.']); exit; }
        $userId = (int)$_SESSION['user_id'];
        $stmt = $db->prepare(
            'SELECT up.*, t.title FROM user_progress up JOIN tests t ON up.test_id=t.id
             WHERE up.user_id=? ORDER BY up.completed_at DESC LIMIT 20'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $progress = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode(['success'=>true,'progress'=>$progress]);
        break;

    // GET leaderboard
    case 'leaderboard':
        $limit = min(50, (int)($_GET['limit'] ?? 20));
        $stmt  = $db->prepare(
            'SELECT id, username, total_points, streak, avatar_url FROM users WHERE role="user"
             ORDER BY total_points DESC LIMIT ?'
        );
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        echo json_encode(['success'=>true,'leaderboard'=>$stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
        $stmt->close();
        break;

    default:
        echo json_encode(['error'=>'Nepoznata akcija.']);
}
