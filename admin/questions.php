<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db      = getDB();
$adminId = (int)$_SESSION['user_id'];
$testId  = (int)($_GET['test_id'] ?? 0);
$msg     = '';
$error   = '';

// Load test info
$test = null;
if ($testId) {
    $stmt = $db->prepare('SELECT t.*, s.name AS sub_name, c.name AS cat_name FROM tests t JOIN subcategories s ON t.subcategory_id=s.id JOIN categories c ON s.category_id=c.id WHERE t.id=?');
    $stmt->bind_param('i', $testId); $stmt->execute();
    $test = $stmt->get_result()->fetch_assoc(); $stmt->close();
}

// Delete question
if (($_GET['action'] ?? '') === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) {
        $stmt = $db->prepare('DELETE FROM questions WHERE id=?');
        $stmt->bind_param('i',$id); $stmt->execute(); $stmt->close();
        logAdminAction($adminId,'delete','questions',$id);
    }
    header('Location: ' . SITE_URL . '/admin/questions.php?test_id=' . $testId . '&msg=Pitanje+obrisano'); exit;
}

// Save question
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyToken($_POST['csrf_token'] ?? '')) { $error = 'Nevažeći CSRF token.'; }
    else {
        $qId    = (int)($_POST['id']            ?? 0);
        $tId    = (int)($_POST['test_id']        ?? $testId);
        $type   = (int)($_POST['type']           ?? 2);
        $qText  = trim($_POST['question_text']   ?? '');
        $cAns   = trim($_POST['correct_answer']  ?? '');
        $pts    = (int)($_POST['points']         ?? 10);
        $order  = (int)($_POST['sort_order']     ?? 0);
        $hint   = trim($_POST['hint_text']       ?? '');

        if ($qId) {
            $stmt = $db->prepare('UPDATE questions SET test_id=?,type=?,question_text=?,correct_answer=?,points=?,sort_order=?,hint_text=? WHERE id=?');
            $stmt->bind_param('iissiiis', $tId,$type,$qText,$cAns,$pts,$order,$hint,$qId);
            $stmt->execute(); $stmt->close();
            // Clear old options
            $del = $db->prepare('DELETE FROM question_options WHERE question_id=?');
            $del->bind_param('i',$qId); $del->execute(); $del->close();
            logAdminAction($adminId,'update','questions',$qId);
            $msg = 'Pitanje ažurirano.';
        } else {
            $stmt = $db->prepare('INSERT INTO questions (test_id,type,question_text,correct_answer,points,sort_order,hint_text) VALUES (?,?,?,?,?,?,?)');
            $stmt->bind_param('iissiii', $tId,$type,$qText,$cAns,$pts,$order,$hint);
            $stmt->execute(); $qId = $db->insert_id; $stmt->close();
            logAdminAction($adminId,'create','questions',$qId);
            $msg = 'Pitanje kreiran.';
        }

        // Save options
        $options  = $_POST['options']         ?? [];
        $correctI = (int)($_POST['correct_option'] ?? 0);
        foreach ($options as $i => $opt) {
            $optText = trim($opt);
            if ($optText === '') continue;
            $isCor   = ($i === $correctI) ? 1 : 0;
            $ins = $db->prepare('INSERT INTO question_options (question_id,option_text,is_correct,sort_order) VALUES (?,?,?,?)');
            $ins->bind_param('isii', $qId,$optText,$isCor,$i); $ins->execute(); $ins->close();
        }

        // Handle media upload
        if (!empty($_FILES['image_file']['name'])) {
            $path = uploadFile($_FILES['image_file'], 'image');
            if ($path) {
                $ins = $db->prepare('INSERT INTO question_media (question_id,media_type,file_path,display_context) VALUES (?,\'image\',?,\'question\')');
                $ins->bind_param('is',$qId,$path); $ins->execute(); $ins->close();
            }
        }
        if (!empty($_FILES['audio_file']['name'])) {
            $path = uploadFile($_FILES['audio_file'], 'audio');
            if ($path) {
                $ins = $db->prepare('INSERT INTO question_media (question_id,media_type,file_path,display_context) VALUES (?,\'audio\',?,\'question\')');
                $ins->bind_param('is',$qId,$path); $ins->execute(); $ins->close();
            }
        }

        $testId = $tId;
    }
}

if (isset($_GET['msg'])) $msg = sanitize($_GET['msg']);

// Load questions for this test
$questions = [];
if ($testId) {
    $stmt = $db->prepare('SELECT q.* FROM questions q WHERE q.test_id=? ORDER BY q.sort_order, q.id');
    $stmt->bind_param('i',$testId); $stmt->execute();
    $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
    foreach ($questions as &$q) {
        $o = $db->prepare('SELECT * FROM question_options WHERE question_id=? ORDER BY sort_order');
        $o->bind_param('i',$q['id']); $o->execute();
        $q['options'] = $o->get_result()->fetch_all(MYSQLI_ASSOC); $o->close();
        $m = $db->prepare('SELECT * FROM question_media WHERE question_id=?');
        $m->bind_param('i',$q['id']); $m->execute();
        $q['media'] = $m->get_result()->fetch_all(MYSQLI_ASSOC); $m->close();
    } unset($q);
}

$tests = $db->query('SELECT t.id, t.title FROM tests t ORDER BY t.id')->fetch_all(MYSQLI_ASSOC);

$qTypes = [
    1=>'Slika + 4 izbora',
    2=>'Tekst + 4 izbora',
    3=>'Audio + izbor',
    4=>'Sparivanje parova',
    5=>'Popuni prazninu',
    6=>'Složi redosled',
    7=>'Tačno/Netačno',
];

$pageTitle = 'Admin – Pitanja';
$extraScripts = '<script src="' . SITE_URL . '/assets/js/admin.js"></script>';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <div class="admin-content">
        <h1 class="admin-page-title">
            ❓ Pitanja
            <?php if ($test): ?>– <span style="color:var(--purple-800)"><?= sanitize($test['title']) ?></span><?php endif; ?>
        </h1>

        <!-- Test selector -->
        <form method="GET" class="d-flex gap-2 mb-4" style="align-items:center">
            <select name="test_id" class="form-select" style="max-width:360px">
                <option value="">-- Odaberite test --</option>
                <?php foreach ($tests as $t): ?>
                <option value="<?= (int)$t['id'] ?>" <?= $testId===$t['id']?'selected':'' ?>><?= sanitize($t['title']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Prikaži pitanja</button>
        </form>

        <?php if ($msg):   ?><div class="alert alert-success"><?= sanitize($msg) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= sanitize($error) ?></div><?php endif; ?>

        <!-- Add question form -->
        <?php if ($testId): ?>
        <div class="card mb-4">
            <div class="card-header">➕ Novo pitanje</div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generateToken() ?>">
                    <input type="hidden" name="test_id"   value="<?= $testId ?>">
                    <div class="grid grid-2" style="gap:1rem">
                        <div class="form-group">
                            <label class="form-label">Tip pitanja</label>
                            <select name="type" id="questionType" class="form-select">
                                <?php foreach ($qTypes as $v=>$l): ?>
                                <option value="<?= $v ?>"><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Poeni</label>
                            <input type="number" name="points" class="form-control" value="10" min="1">
                        </div>
                        <div class="form-group" style="grid-column:1/-1">
                            <label class="form-label">Tekst pitanja</label>
                            <textarea name="question_text" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="form-group" style="grid-column:1/-1">
                            <label class="form-label">Nagoveštaj (opciono)</label>
                            <input type="text" name="hint_text" class="form-control" placeholder="Nagoveštaj za učenike">
                        </div>
                    </div>

                    <!-- Options (types 1,2,3) -->
                    <div id="choicesSection">
                        <h4 style="margin:1rem 0 .5rem">Opcije odgovora</h4>
                        <div id="optionsContainer">
                            <?php for ($i=0;$i<4;$i++): ?>
                            <div class="option-row d-flex align-center gap-2 mb-2">
                                <input type="text" name="options[]" class="form-control" placeholder="Opcija <?= $i+1 ?>">
                                <label class="d-flex align-center gap-1" style="white-space:nowrap">
                                    <input type="radio" name="correct_option" value="<?= $i ?>" <?= $i===0?'checked':'' ?>> Tačan
                                </label>
                            </div>
                            <?php endfor; ?>
                        </div>
                        <button type="button" id="addOption" class="btn btn-ghost btn-sm">+ Dodaj opciju</button>
                    </div>

                    <!-- Correct answer (types 5,6,7) -->
                    <div id="fillSection" class="hidden">
                        <div class="form-group mt-3">
                            <label class="form-label">Tačan odgovor</label>
                            <input type="text" name="correct_answer" class="form-control" placeholder="npr. heiße">
                        </div>
                    </div>
                    <div id="dragSection" class="hidden">
                        <div class="form-group mt-3">
                            <label class="form-label">Tačan redosled (JSON niz)</label>
                            <input type="text" name="correct_answer" class="form-control" placeholder='["Ich","heiße","Max"]'>
                            <div class="form-text">JSON niz reči u tačnom redosledu</div>
                        </div>
                    </div>
                    <div id="matchingSection" class="hidden">
                        <div class="form-group mt-3">
                            <label class="form-label">Parovi (JSON)</label>
                            <textarea name="correct_answer" class="form-control" rows="3" placeholder='[["der Hund","pas"],["die Katze","mačka"]]'></textarea>
                            <div class="form-text">JSON niz parova: [[levo, desno], ...]</div>
                        </div>
                    </div>
                    <div id="tfSection" class="hidden">
                        <div class="form-group mt-3">
                            <label class="form-label">Tačan odgovor</label>
                            <select name="correct_answer" class="form-select">
                                <option value="Tačno">Tačno</option>
                                <option value="Netačno">Netačno</option>
                            </select>
                        </div>
                    </div>

                    <!-- Media uploads -->
                    <div class="grid grid-2 mt-3" style="gap:1rem">
                        <div class="form-group">
                            <label class="form-label">Slika pitanja (opciono)</label>
                            <input type="file" name="image_file" class="form-control" accept="image/*" data-preview="imgPreview">
                            <div id="imgPreview"></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Audio fajl (opciono)</label>
                            <input type="file" name="audio_file" class="form-control" accept="audio/*" data-preview="audioPreview">
                            <div id="audioPreview"></div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mt-2">Sačuvaj pitanje</button>
                </form>
            </div>
        </div>

        <!-- Questions list -->
        <div class="card">
            <div class="card-header">Pitanja (<?= count($questions) ?>)</div>
            <?php if (empty($questions)): ?>
            <div class="card-body text-muted">Ovaj test nema pitanja.</div>
            <?php else: ?>
            <div id="sortableQuestions">
                <?php foreach ($questions as $q): ?>
                <div draggable="true" data-row style="padding:1rem 1.25rem;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;gap:1rem">
                    <input type="hidden" name="sort_order" value="<?= (int)$q['sort_order'] ?>">
                    <div style="cursor:grab;color:var(--gray-400);font-size:1.3rem;padding-top:.2rem">⠿</div>
                    <div style="flex:1;min-width:0">
                        <div class="d-flex align-center gap-2 mb-1">
                            <span class="badge badge-purple" style="font-size:.75rem"><?= $qTypes[(int)$q['type']] ?? 'Tip '.((int)$q['type']) ?></span>
                            <span class="badge" style="background:var(--yellow-light);color:#92400E;font-size:.75rem"><?= (int)$q['points'] ?> poena</span>
                        </div>
                        <div style="font-weight:600"><?= sanitize(mb_substr($q['question_text'], 0, 120)) ?><?= mb_strlen($q['question_text']) > 120 ? '…' : '' ?></div>
                        <?php if (!empty($q['options'])): ?>
                        <div style="font-size:.82rem;color:var(--text-muted);margin-top:.3rem">
                            <?php foreach ($q['options'] as $opt): ?>
                            <span style="<?= $opt['is_correct'] ? 'color:var(--green);font-weight:700' : '' ?>"><?= sanitize($opt['option_text']) ?></span>
                            <?php if (!$opt['is_correct']): ?> | <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <?php foreach ($q['media'] as $m): ?>
                        <div style="font-size:.8rem;color:var(--blue)">📎 <?= $m['media_type'] === 'image' ? '🖼️' : '🔊' ?> <?= sanitize($m['file_path']) ?></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="table-actions">
                        <a href="?test_id=<?= $testId ?>&action=delete&id=<?= (int)$q['id'] ?>" class="btn btn-danger btn-sm" data-confirm="Obrisati ovo pitanje?">🗑️</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
