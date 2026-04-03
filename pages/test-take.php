<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$testId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$testId) { redirect(SITE_URL . '/pages/tests.php'); }

$db = getDB();
$stmt = $db->prepare(
    'SELECT t.*, s.name AS sub_name, c.name AS cat_name
     FROM tests t
     JOIN subcategories s ON t.subcategory_id = s.id
     JOIN categories c    ON s.category_id    = c.id
     WHERE t.id = ?'
);
$stmt->bind_param('i', $testId);
$stmt->execute();
$test = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$test) { redirect(SITE_URL . '/pages/tests.php'); }

// Load questions
$qStmt = $db->prepare('SELECT * FROM questions WHERE test_id = ? ORDER BY sort_order, id');
$qStmt->bind_param('i', $testId);
$qStmt->execute();
$questions = $qStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$qStmt->close();

foreach ($questions as &$q) {
    // Options
    $oStmt = $db->prepare('SELECT * FROM question_options WHERE question_id = ? ORDER BY sort_order, id');
    $oStmt->bind_param('i', $q['id']);
    $oStmt->execute();
    $q['options'] = $oStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $oStmt->close();
    // Media
    $mStmt = $db->prepare('SELECT * FROM question_media WHERE question_id = ?');
    $mStmt->bind_param('i', $q['id']);
    $mStmt->execute();
    $q['media'] = $mStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $mStmt->close();
}
unset($q);

$pageTitle = sanitize($test['title']);
$extraHead = '<style>.navbar{position:sticky;top:0;}</style>';
$extraScripts = '<script src="' . SITE_URL . '/assets/js/test.js"></script>';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="quiz-wrap">
    <!-- Quiz header -->
    <div class="quiz-header">
        <div class="quiz-title">
            <a href="<?= SITE_URL ?>/pages/tests.php" class="btn btn-ghost btn-sm">Nazad</a>
            <strong style="margin-left:.5rem"><?= sanitize($test['title']) ?></strong>
        </div>
        <div class="quiz-timer" id="quizTimer">
            <span id="timerDisplay">--:--</span>
        </div>
        <div style="font-size:.88rem;color:var(--text-muted)">
            Pitanje <span id="questionNum">1 / <?= count($questions) ?></span>
        </div>
    </div>

    <!-- Progress -->
    <div class="progress-bar mb-4">
        <div class="progress-fill" id="quizProgress" style="width:0%"></div>
    </div>

    <!-- Question area -->
    <div class="question-card" id="quizWrap">
        <div id="questionContent">
            <div class="text-center" style="padding:3rem">
                <div class="spinner"></div>
                <p class="text-muted mt-2">Učitavanje testa...</p>
            </div>
        </div>
    </div>

    <!-- Info bar -->
    <div style="text-align:center;margin-top:1rem;font-size:.85rem;color:var(--text-muted)">
        <span class="badge badge-<?= sanitize($test['difficulty']) ?>"><?= ['beginner'=>'Početni','intermediate'=>'Srednji','advanced'=>'Napredni'][$test['difficulty']] ?></span>
        &nbsp;·&nbsp; Prolazna ocena: <?= (int)$test['passing_score'] ?>%
        &nbsp;·&nbsp; <?= count($questions) ?> pitanja
    </div>
</div>

<!-- Inject test data for JS -->
<script id="quizData" type="application/json">
<?= json_encode(['test' => $test, 'questions' => $questions], JSON_UNESCAPED_UNICODE) ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
