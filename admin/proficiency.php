<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db      = getDB();
$adminId = (int)$_SESSION['user_id'];
$action  = $_GET['action'] ?? 'list';
$msg     = '';
$error   = '';
$levels  = ['A1','A2','B1','B2','C1','C2'];

// ── DELETE ────────────────────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) {
        $stmt = $db->prepare('DELETE FROM proficiency_questions WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        logAdminAction($adminId, 'delete', 'proficiency_questions', $id);
    }
    header('Location: ' . SITE_URL . '/admin/proficiency.php?msg=Pitanje+obrisano'); exit;
}

// ── SAVE (CREATE / UPDATE) ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyToken($_POST['csrf_token'] ?? '')) {
        $error = 'Nevažeći CSRF token.';
    } else {
        $id      = (int)($_POST['id'] ?? 0);
        $level   = in_array($_POST['level'] ?? '', $levels) ? $_POST['level'] : 'B1';
        $text    = trim($_POST['question_text'] ?? '');
        $optA    = trim($_POST['option_a'] ?? '');
        $optB    = trim($_POST['option_b'] ?? '');
        $optC    = trim($_POST['option_c'] ?? '');
        $optD    = trim($_POST['option_d'] ?? '');
        $correct = in_array($_POST['correct_answer'] ?? '', ['a','b','c','d'])
                   ? $_POST['correct_answer'] : 'a';
        $sortOrd = (int)($_POST['sort_order'] ?? 0);

        if ($text === '' || $optA === '' || $optB === '' || $optC === '' || $optD === '') {
            $error = 'Sva polja su obavezna.';
        } else {
            if ($id > 0) {
                $stmt = $db->prepare(
                    'UPDATE proficiency_questions
                     SET level=?, question_text=?, option_a=?, option_b=?, option_c=?, option_d=?,
                         correct_answer=?, sort_order=?
                     WHERE id=?'
                );
                $stmt->bind_param('sssssssii', $level,$text,$optA,$optB,$optC,$optD,$correct,$sortOrd,$id);
                $stmt->execute(); $stmt->close();
                logAdminAction($adminId, 'update', 'proficiency_questions', $id);
                $msg = 'Pitanje ažurirano.';
            } else {
                $stmt = $db->prepare(
                    'INSERT INTO proficiency_questions
                     (level, question_text, option_a, option_b, option_c, option_d, correct_answer, sort_order)
                     VALUES (?,?,?,?,?,?,?,?)'
                );
                $stmt->bind_param('sssssssi', $level,$text,$optA,$optB,$optC,$optD,$correct,$sortOrd);
                $stmt->execute(); $stmt->close();
                logAdminAction($adminId, 'create', 'proficiency_questions', $db->insert_id);
                $msg = 'Pitanje kreirano.';
            }
            $action = 'list';
        }
    }
}

if (isset($_GET['msg'])) $msg = sanitize($_GET['msg']);

$allQuestions = $db->query('SELECT * FROM proficiency_questions ORDER BY level, sort_order, id')->fetch_all(MYSQLI_ASSOC);
$byLevel      = [];
foreach ($allQuestions as $q) { $byLevel[$q['level']][] = $q; }

$editQuestion = null;
if ($action === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) {
        $stmt = $db->prepare('SELECT * FROM proficiency_questions WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $editQuestion = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

$pageTitle = 'Admin – Provera znanja';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <div class="admin-content">
        <h1 class="admin-page-title">🎓 Provera znanja – Pitanja</h1>
        <?php if ($msg):   ?><div class="alert alert-success"><?= sanitize($msg) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= sanitize($error) ?></div><?php endif; ?>

        <!-- Form -->
        <div class="card mb-4">
            <div class="card-header"><?= $editQuestion ? '✏️ Izmeni pitanje' : '➕ Novo pitanje' ?></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateToken() ?>">
                    <input type="hidden" name="id" value="<?= $editQuestion ? (int)$editQuestion['id'] : 0 ?>">
                    <div class="grid grid-2" style="gap:1rem">
                        <div class="form-group">
                            <label class="form-label">Nivo</label>
                            <select name="level" class="form-select">
                                <?php foreach ($levels as $l): ?>
                                <option value="<?= $l ?>"
                                    <?= isset($editQuestion['level']) && $editQuestion['level'] === $l ? 'selected' : '' ?>>
                                    <?= $l ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tačan odgovor</label>
                            <select name="correct_answer" class="form-select">
                                <?php foreach (['a','b','c','d'] as $o): ?>
                                <option value="<?= $o ?>"
                                    <?= isset($editQuestion['correct_answer']) && $editQuestion['correct_answer'] === $o ? 'selected' : '' ?>>
                                    <?= strtoupper($o) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="grid-column:1/-1">
                            <label class="form-label">Tekst pitanja</label>
                            <textarea name="question_text" class="form-control" rows="2" required><?= sanitize($editQuestion['question_text'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Opcija A</label>
                            <input type="text" name="option_a" class="form-control" required
                                   value="<?= sanitize($editQuestion['option_a'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Opcija B</label>
                            <input type="text" name="option_b" class="form-control" required
                                   value="<?= sanitize($editQuestion['option_b'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Opcija C</label>
                            <input type="text" name="option_c" class="form-control" required
                                   value="<?= sanitize($editQuestion['option_c'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Opcija D</label>
                            <input type="text" name="option_d" class="form-control" required
                                   value="<?= sanitize($editQuestion['option_d'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Redosled</label>
                            <input type="number" name="sort_order" class="form-control"
                                   value="<?= (int)($editQuestion['sort_order'] ?? 0) ?>">
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <?= $editQuestion ? 'Ažuriraj' : 'Kreiraj pitanje' ?>
                        </button>
                        <?php if ($editQuestion): ?>
                        <a href="<?= SITE_URL ?>/admin/proficiency.php" class="btn btn-outline btn-sm">Otkaži</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Questions grouped by level -->
        <?php foreach ($levels as $lvl): ?>
        <?php $qs = $byLevel[$lvl] ?? []; ?>
        <div class="card mb-4">
            <div class="card-header">
                Nivo <?= $lvl ?> <span class="badge badge-purple"><?= count($qs) ?></span>
            </div>
            <?php if (empty($qs)): ?>
            <div class="card-body"><p class="text-muted">Nema pitanja za ovaj nivo.</p></div>
            <?php else: ?>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>#</th><th>Pitanje</th><th>Tačan odg.</th><th>Akcije</th></tr></thead>
                    <tbody>
                    <?php foreach ($qs as $i => $q): ?>
                    <tr>
                        <td style="color:var(--text-muted)"><?= $i + 1 ?></td>
                        <td><?= sanitize(mb_substr($q['question_text'], 0, 80)) ?><?= mb_strlen($q['question_text']) > 80 ? '…' : '' ?></td>
                        <td><strong><?= strtoupper(sanitize($q['correct_answer'])) ?></strong>
                            – <?= sanitize($q['option_' . $q['correct_answer']]) ?></td>
                        <td class="table-actions">
                            <a href="?action=edit&id=<?= (int)$q['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
                            <a href="?action=delete&id=<?= (int)$q['id'] ?>" class="btn btn-danger btn-sm"
                               onclick="return confirm('Obrisati ovo pitanje?')">🗑️</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
