<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Provera znanja';
$db        = getDB();

$levels     = ['A1','A2','B1','B2','C1','C2'];
$levelNames = [
    'A1' => 'Početnik',
    'A2' => 'Osnovno',
    'B1' => 'Srednji',
    'B2' => 'Viši srednji',
    'C1' => 'Napredni',
    'C2' => 'Savršen',
];
$levelDesc = [
    'A1' => 'Razumete i koristite poznate svakodnevne izraze i vrlo jednostavne fraze.',
    'A2' => 'Možete da komunicirate u jednostavnim i rutinskim zadacima.',
    'B1' => 'Možete da razumete glavne tačke jasnog standardnog govora.',
    'B2' => 'Možete da razumete složenije tekstove i komunicirate tečno.',
    'C1' => 'Možete da razumete zahtevne i duže tekstove i izražavate se spontano.',
    'C2' => 'Možete da razumete praktično sve što čujete ili pročitate bez napora.',
];

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

// ── RESET ─────────────────────────────────────────────────────────────────────
if ($action === 'reset') {
    unset($_SESSION['prof_test']);
    header('Location: ' . SITE_URL . '/pages/proficiency.php'); exit;
}

// ── START TEST ────────────────────────────────────────────────────────────────
if ($action === 'start') {
    unset($_SESSION['prof_test']);
    $_SESSION['prof_test'] = [
        'round'         => 1,
        'current_level' => 'B1',
        'passed_levels' => [],
        'done'          => false,
        'result_level'  => null,
        'questions'     => [],
        'results'       => [],
    ];
    header('Location: ' . SITE_URL . '/pages/proficiency.php'); exit;
}

// ── SUBMIT ANSWERS ────────────────────────────────────────────────────────────
if ($action === 'submit' && isset($_SESSION['prof_test']) && !$_SESSION['prof_test']['done']) {
    $test  = &$_SESSION['prof_test'];
    $qIds  = $test['questions'];
    $score = 0;

    if (!empty($qIds)) {
        $placeholders = implode(',', array_fill(0, count($qIds), '?'));
        $types        = str_repeat('i', count($qIds));
        $stmt         = $db->prepare("SELECT id, correct_answer FROM proficiency_questions WHERE id IN ($placeholders)");
        $stmt->bind_param($types, ...$qIds);
        $stmt->execute();
        $correctMap = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $correctMap[$row['id']] = $row['correct_answer'];
        }
        $stmt->close();

        foreach ($qIds as $qid) {
            $given   = $_POST['answer_' . $qid] ?? '';
            $correct = $correctMap[$qid] ?? '';
            if ($given === $correct) $score++;
        }
    }

    $currentLevel = $test['current_level'];
    $passed       = ($score >= 5);

    $test['results'][] = [
        'level'  => $currentLevel,
        'score'  => $score,
        'passed' => $passed,
    ];
    if ($passed) {
        $test['passed_levels'][] = $currentLevel;
    }

    // Determine next level or end
    $levelIdx  = array_search($currentLevel, $levels);
    $nextLevel = null;
    $done      = false;

    if ($passed) {
        if ($currentLevel === 'C2' || $test['round'] >= 3) {
            $done = true;
        } else {
            $nextLevel = $levels[$levelIdx + 1] ?? null;
            if ($nextLevel === null) $done = true;
        }
    } else {
        if ($currentLevel === 'A1' || $test['round'] >= 3) {
            $done = true;
        } else {
            $nextLevel = $levels[$levelIdx - 1] ?? null;
            if ($nextLevel === null) $done = true;
        }
    }

    if ($done || $nextLevel === null) {
        // Result = highest passed level; if nothing passed, report lowest attempted
        if (!empty($test['passed_levels'])) {
            $levelIndex = array_flip($levels);
            usort($test['passed_levels'], function($a, $b) use ($levelIndex) {
                return $levelIndex[$a] <=> $levelIndex[$b];
            });
            $resultLevel = end($test['passed_levels']);
        } else {
            $resultLevel = $currentLevel;
        }
        $test['done']         = true;
        $test['result_level'] = $resultLevel;

        // Save result to DB
        $userId  = isLoggedIn() ? (int)$_SESSION['user_id'] : null;
        $sessId  = session_id();
        $details = json_encode($test['results']);
        $stmt    = $db->prepare(
            'INSERT INTO proficiency_results (user_id, session_id, level_achieved, details) VALUES (?,?,?,?)'
        );
        $stmt->bind_param('isss', $userId, $sessId, $resultLevel, $details);
        $stmt->execute();
        $stmt->close();
    } else {
        $test['round']++;
        $test['current_level'] = $nextLevel;
        $test['questions']     = [];
    }

    header('Location: ' . SITE_URL . '/pages/proficiency.php'); exit;
}

// ── LOAD QUESTIONS FOR CURRENT ROUND ─────────────────────────────────────────
if (isset($_SESSION['prof_test']) && !$_SESSION['prof_test']['done'] && empty($_SESSION['prof_test']['questions'])) {
    $test  = &$_SESSION['prof_test'];
    $level = $test['current_level'];
    $stmt  = $db->prepare('SELECT id FROM proficiency_questions WHERE level = ? ORDER BY RAND() LIMIT 10');
    $stmt->bind_param('s', $level);
    $stmt->execute();
    $test['questions'] = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'id');
    $stmt->close();
}

// ── FETCH QUESTION DATA FOR DISPLAY ──────────────────────────────────────────
$currentQuestions = [];
if (isset($_SESSION['prof_test']) && !$_SESSION['prof_test']['done'] && !empty($_SESSION['prof_test']['questions'])) {
    $qIds         = $_SESSION['prof_test']['questions'];
    $placeholders = implode(',', array_fill(0, count($qIds), '?'));
    $types        = str_repeat('i', count($qIds));
    $stmt         = $db->prepare("SELECT * FROM proficiency_questions WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$qIds);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    // Preserve original order
    $rowMap = [];
    foreach ($rows as $r) $rowMap[$r['id']] = $r;
    foreach ($qIds as $qid) {
        if (isset($rowMap[$qid])) $currentQuestions[] = $rowMap[$qid];
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<section class="section">
    <div class="container" style="max-width:760px">

<?php if (!isset($_SESSION['prof_test'])): ?>
    <!-- ── Intro ─────────────────────────────────────────────────── -->
    <div class="card card-body text-center" style="padding:3rem">
        <div style="font-size:3.5rem;margin-bottom:1rem"></div>
        <h1 style="margin-bottom:.5rem">Provera znanja nemačkog</h1>
        <p class="text-muted" style="margin-bottom:2rem;font-size:1.05rem">
            Saznajte vaš nivo nemačkog prema CEFR skali (A1–C2).<br>
            Test se sastoji od najviše 3 runde od 10 pitanja svaka.
        </p>
        <div class="grid grid-3 mb-4" style="text-align:left">
            <?php foreach ($levels as $l): ?>
            <div class="card card-body" style="padding:1rem">
                <strong><?= $l ?></strong> – <?= sanitize($levelNames[$l]) ?><br>
                <small class="text-muted"><?= sanitize(mb_substr($levelDesc[$l], 0, 60)) ?>...</small>
            </div>
            <?php endforeach; ?>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="start">
            <button type="submit" class="btn btn-primary">Počni test</button>
        </form>
    </div>

<?php elseif ($_SESSION['prof_test']['done']): ?>
    <!-- ── Results ───────────────────────────────────────────────── -->
    <?php
    $result    = $_SESSION['prof_test']['result_level'];
    $roundData = $_SESSION['prof_test']['results'];
    ?>
    <div class="card card-body text-center" style="padding:3rem">
        <div style="font-size:3.5rem;margin-bottom:1rem"></div>
        <h1 style="margin-bottom:.5rem">Vaš nivo nemačkog je:</h1>
        <div style="font-size:3rem;font-weight:800;color:var(--purple);margin:1rem 0"><?= sanitize($result) ?></div>
        <h2 style="margin-bottom:.5rem"><?= sanitize($levelNames[$result] ?? $result) ?></h2>
        <p class="text-muted" style="margin-bottom:2rem;font-size:1rem"><?= sanitize($levelDesc[$result] ?? '') ?></p>

        <?php if (!empty($roundData)): ?>
        <div class="card mb-4" style="text-align:left">
            <div class="card-header">Detalji po rundama</div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Runda</th><th>Nivo</th><th>Rezultat</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($roundData as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= sanitize($r['level']) ?></strong></td>
                        <td><?= (int)$r['score'] ?>/10</td>
                        <td><?= $r['passed'] ? ' Položeno' : ' Nije položeno' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="reset">
            <button type="submit" class="btn btn-outline btn-sm">Ponovi test</button>
        </form>
    </div>

<?php else: ?>
    <!-- ── Test in progress ──────────────────────────────────────── -->
    <?php
    $test  = $_SESSION['prof_test'];
    $level = $test['current_level'];
    $round = $test['round'];
    ?>
    <div class="card mb-3">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <span> Provera znanja – Runda <?= (int)$round ?>/3</span>
            <span class="badge badge-purple"><?= sanitize($level) ?> – <?= sanitize($levelNames[$level] ?? $level) ?></span>
        </div>
    </div>

    <?php if (empty($currentQuestions)): ?>
    <div class="alert alert-error">Nema pitanja za ovaj nivo. Molimo kontaktirajte administratora.</div>
    <form method="POST">
        <input type="hidden" name="action" value="reset">
        <button type="submit" class="btn btn-outline btn-sm">← Nazad</button>
    </form>
    <?php else: ?>
    <form method="POST">
        <input type="hidden" name="action" value="submit">
        <?php foreach ($currentQuestions as $i => $q): ?>
        <div class="card mb-3">
            <div class="card-body">
                <p style="font-weight:600;margin-bottom:1rem"><?= ($i + 1) ?>. <?= sanitize($q['question_text']) ?></p>
                <?php foreach (['a','b','c','d'] as $opt): ?>
                <label style="display:block;padding:.5rem .75rem;border-radius:6px;cursor:pointer;
                              border:1px solid var(--border);margin-bottom:.4rem;transition:background .15s"
                       onmouseover="this.style.background='var(--purple-50)'"
                       onmouseout="this.style.background=''">
                    <input type="radio" name="answer_<?= (int)$q['id'] ?>" value="<?= $opt ?>" required
                           style="margin-right:.5rem">
                    <?= sanitize($q['option_' . $opt]) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <div class="d-flex gap-2 mt-2">
            <button type="submit" class="btn btn-primary">Potvrdi odgovore →</button>
        </div>
    </form>
    <form method="POST" style="display:inline;margin-top:.5rem">
        <input type="hidden" name="action" value="reset">
        <button type="submit" class="btn btn-outline btn-sm"
                onclick="return confirm('Odustati od testa?')">Odustani</button>
    </form>
    <?php endif; ?>

<?php endif; ?>

    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
