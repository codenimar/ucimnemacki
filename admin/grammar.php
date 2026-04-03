<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db           = getDB();
$adminId      = (int)$_SESSION['user_id'];
$action       = $_GET['action'] ?? 'list';
$msg          = '';
$error        = '';
$difficulties = ['beginner','intermediate','advanced'];
$diffLabels   = ['beginner' => 'Početni', 'intermediate' => 'Srednji', 'advanced' => 'Napredni'];

// ── DELETE ────────────────────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) {
        $stmt = $db->prepare('DELETE FROM grammar_lessons WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        logAdminAction($adminId, 'delete', 'grammar_lessons', $id);
    }
    header('Location: ' . SITE_URL . '/admin/grammar.php?msg=Lekcija+obrisana'); exit;
}

// ── SAVE (CREATE / UPDATE) ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyToken($_POST['csrf_token'] ?? '')) {
        $error = 'Nevažeći CSRF token.';
    } else {
        $id         = (int)($_POST['id'] ?? 0);
        $title      = trim($_POST['title'] ?? '');
        $content    = trim($_POST['content'] ?? '');
        $difficulty = in_array($_POST['difficulty'] ?? '', $difficulties)
                      ? $_POST['difficulty'] : 'beginner';
        $sortOrder  = (int)($_POST['sort_order'] ?? 0);

        if ($title === '' || $content === '') {
            $error = 'Naslov i sadržaj su obavezni.';
        } else {
            if ($id > 0) {
                $stmt = $db->prepare(
                    'UPDATE grammar_lessons SET title=?, content=?, difficulty=?, sort_order=? WHERE id=?'
                );
                $stmt->bind_param('sssii', $title, $content, $difficulty, $sortOrder, $id);
                $stmt->execute(); $stmt->close();
                logAdminAction($adminId, 'update', 'grammar_lessons', $id);
                $msg = 'Lekcija ažurirana.';
            } else {
                $stmt = $db->prepare(
                    'INSERT INTO grammar_lessons (title, content, difficulty, sort_order) VALUES (?,?,?,?)'
                );
                $stmt->bind_param('sssi', $title, $content, $difficulty, $sortOrder);
                $stmt->execute(); $stmt->close();
                logAdminAction($adminId, 'create', 'grammar_lessons', $db->insert_id);
                $msg = 'Lekcija kreirana.';
            }
            $action = 'list';
        }
    }
}

if (isset($_GET['msg'])) $msg = sanitize($_GET['msg']);

$lessons = $db->query('SELECT * FROM grammar_lessons ORDER BY sort_order, id')->fetch_all(MYSQLI_ASSOC);

$editLesson = null;
if ($action === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) {
        $stmt = $db->prepare('SELECT * FROM grammar_lessons WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $editLesson = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

$pageTitle = 'Admin – Gramatika';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <div class="admin-content">
        <h1 class="admin-page-title"> Gramatika – Lekcije</h1>
        <?php if ($msg):   ?><div class="alert alert-success"><?= sanitize($msg) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= sanitize($error) ?></div><?php endif; ?>

        <!-- Form -->
        <div class="card mb-4">
            <div class="card-header"><?= $editLesson ? ' Izmeni lekciju' : ' Nova lekcija' ?></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateToken() ?>">
                    <input type="hidden" name="id" value="<?= $editLesson ? (int)$editLesson['id'] : 0 ?>">
                    <div class="grid grid-2" style="gap:1rem">
                        <div class="form-group" style="grid-column:1/-1">
                            <label class="form-label">Naslov lekcije</label>
                            <input type="text" name="title" class="form-control" required
                                   value="<?= sanitize($editLesson['title'] ?? '') ?>"
                                   placeholder="npr. Der Dativ – treći padež">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Težina</label>
                            <select name="difficulty" class="form-select">
                                <?php foreach ($difficulties as $d): ?>
                                <option value="<?= $d ?>"
                                    <?= isset($editLesson['difficulty']) && $editLesson['difficulty'] === $d ? 'selected' : '' ?>>
                                    <?= sanitize($diffLabels[$d]) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Redosled</label>
                            <input type="number" name="sort_order" class="form-control"
                                   value="<?= (int)($editLesson['sort_order'] ?? 0) ?>" min="0">
                        </div>
                        <div class="form-group" style="grid-column:1/-1">
                            <label class="form-label">Sadržaj lekcije (HTML je podržan)</label>
                            <textarea name="content" class="form-control" rows="10" required
                                      placeholder="Unesite sadržaj lekcije..."><?= sanitize($editLesson['content'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <?= $editLesson ? 'Ažuriraj' : 'Kreiraj lekciju' ?>
                        </button>
                        <?php if ($editLesson): ?>
                        <a href="<?= SITE_URL ?>/admin/grammar.php" class="btn btn-outline btn-sm">Otkaži</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lessons table -->
        <div class="card">
            <div class="card-header">Sve lekcije (<?= count($lessons) ?>)</div>
            <?php if (empty($lessons)): ?>
            <div class="card-body text-center" style="padding:2rem">
                <p class="text-muted">Još nema lekcija.</p>
            </div>
            <?php else: ?>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>#</th><th>Naslov</th><th>Težina</th><th>Akcije</th></tr></thead>
                    <tbody>
                    <?php foreach ($lessons as $i => $l): ?>
                    <tr>
                        <td style="color:var(--text-muted)"><?= $i + 1 ?></td>
                        <td><strong><?= sanitize($l['title']) ?></strong></td>
                        <td><span class="badge badge-purple"><?= sanitize($diffLabels[$l['difficulty']] ?? $l['difficulty']) ?></span></td>
                        <td class="table-actions">
                            <a href="?action=edit&id=<?= (int)$l['id'] ?>" class="btn btn-outline btn-sm"></a>
                            <a href="?action=delete&id=<?= (int)$l['id'] ?>" class="btn btn-danger btn-sm"
                               onclick="return confirm('Obrisati lekciju \'<?= sanitize($l['title']) ?>\'?')"></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
