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

// Delete
if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) {
        $stmt = $db->prepare('DELETE FROM tests WHERE id=?');
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
        logAdminAction($adminId, 'delete', 'tests', $id);
    }
    header('Location: ' . SITE_URL . '/admin/tests.php?msg=Test+obrisan'); exit;
}

// Save (create / update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyToken($_POST['csrf_token'] ?? '')) { $error = 'Nevažeći CSRF token.'; }
    else {
        $id      = (int)($_POST['id']             ?? 0);
        $subId   = (int)($_POST['subcategory_id'] ?? 0);
        $title   = trim($_POST['title']           ?? '');
        $desc    = trim($_POST['description']     ?? '');
        $diff    = $_POST['difficulty']           ?? 'beginner';
        $time    = (int)($_POST['time_limit']     ?? 300);
        $pass    = (int)($_POST['passing_score']  ?? 60);

        if ($id) {
            $stmt = $db->prepare('UPDATE tests SET subcategory_id=?,title=?,description=?,difficulty=?,time_limit=?,passing_score=? WHERE id=?');
            $stmt->bind_param('isssiii', $subId,$title,$desc,$diff,$time,$pass,$id);
            $stmt->execute(); $stmt->close();
            logAdminAction($adminId,'update','tests',$id);
            $msg = 'Test ažuriran.';
        } else {
            $stmt = $db->prepare('INSERT INTO tests (subcategory_id,title,description,difficulty,time_limit,passing_score) VALUES (?,?,?,?,?,?)');
            $stmt->bind_param('isssii', $subId,$title,$desc,$diff,$time,$pass);
            $stmt->execute(); $stmt->close();
            logAdminAction($adminId,'create','tests',$db->insert_id);
            $msg = 'Test kreiran.';
        }
        $action = 'list';
    }
}

if (isset($_GET['msg'])) $msg = sanitize($_GET['msg']);

$tests = $db->query(
    'SELECT t.*, s.name AS sub_name, c.name AS cat_name FROM tests t
     JOIN subcategories s ON t.subcategory_id=s.id
     JOIN categories c ON s.category_id=c.id
     ORDER BY c.sort_order, s.sort_order, t.id'
)->fetch_all(MYSQLI_ASSOC);

$subcategories = $db->query(
    'SELECT s.*, c.name AS cat_name FROM subcategories s JOIN categories c ON s.category_id=c.id ORDER BY c.sort_order, s.sort_order'
)->fetch_all(MYSQLI_ASSOC);

$editTest = null;
if ($action === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) {
        $stmt = $db->prepare('SELECT * FROM tests WHERE id=?');
        $stmt->bind_param('i',$id); $stmt->execute();
        $editTest = $stmt->get_result()->fetch_assoc(); $stmt->close();
    }
}

$pageTitle = 'Admin – Testovi';
$extraScripts = '<script src="' . SITE_URL . '/assets/js/admin.js"></script>';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <div class="admin-content">
        <h1 class="admin-page-title">Testovi</h1>
        <?php if ($msg):   ?><div class="alert alert-success"><?= sanitize($msg) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= sanitize($error) ?></div><?php endif; ?>

        <!-- Form -->
        <div class="card mb-4">
            <div class="card-header"><?= $editTest ? 'Izmeni test' : 'Novi test' ?></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateToken() ?>">
                    <?php if ($editTest): ?><input type="hidden" name="id" value="<?= (int)$editTest['id'] ?>"><?php endif; ?>
                    <div class="grid grid-2" style="gap:1rem">
                        <div class="form-group">
                            <label class="form-label">Naziv testa</label>
                            <input type="text" name="title" class="form-control" required value="<?= sanitize($editTest['title'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Potkategorija</label>
                            <select name="subcategory_id" class="form-select" required>
                                <?php foreach ($subcategories as $s): ?>
                                <option value="<?= (int)$s['id'] ?>" <?= ($editTest && $editTest['subcategory_id']==$s['id']) ? 'selected' : '' ?>><?= sanitize($s['cat_name']) ?> › <?= sanitize($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="grid-column:1/-1">
                            <label class="form-label">Opis</label>
                            <textarea name="description" class="form-control" rows="2"><?= sanitize($editTest['description'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Težina</label>
                            <select name="difficulty" class="form-select">
                                <?php foreach (['beginner'=>'Početni','intermediate'=>'Srednji','advanced'=>'Napredni'] as $v=>$l): ?>
                                <option value="<?= $v ?>" <?= ($editTest && $editTest['difficulty']===$v)?'selected':'' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Vremensko ograničenje (sekunde)</label>
                            <input type="number" name="time_limit" class="form-control" value="<?= (int)($editTest['time_limit'] ?? 300) ?>" min="30" max="3600">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Prolazna ocena (%)</label>
                            <input type="number" name="passing_score" class="form-control" value="<?= (int)($editTest['passing_score'] ?? 60) ?>" min="1" max="100">
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm"><?= $editTest ? 'Ažuriraj' : 'Kreiraj' ?> test</button>
                        <?php if ($editTest): ?><a href="<?= SITE_URL ?>/admin/tests.php" class="btn btn-ghost btn-sm">Otkaži</a><?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-header">Svi testovi (<?= count($tests) ?>)</div>
            <div class="table-wrapper">
                <table class="table sortable-table">
                    <thead><tr><th>ID</th><th>Naziv</th><th>Kategorija</th><th>Težina</th><th>Vreme</th><th>Prolaz</th><th>Akcije</th></tr></thead>
                    <tbody>
                    <?php foreach ($tests as $t): ?>
                    <tr>
                        <td><?= (int)$t['id'] ?></td>
                        <td><strong><?= sanitize($t['title']) ?></strong></td>
                        <td><span class="badge badge-purple"><?= sanitize($t['cat_name']) ?></span> › <?= sanitize($t['sub_name']) ?></td>
                        <td><span class="badge badge-<?= $t['difficulty'] ?>"><?= ['beginner'=>'Početni','intermediate'=>'Srednji','advanced'=>'Napredni'][$t['difficulty']] ?></span></td>
                        <td><?= formatTime((int)$t['time_limit']) ?></td>
                        <td><?= (int)$t['passing_score'] ?>%</td>
                        <td class="table-actions">
                            <a href="?action=edit&id=<?= (int)$t['id'] ?>" class="btn btn-outline btn-sm">Izmeni</a>
                            <a href="<?= SITE_URL ?>/admin/questions.php?test_id=<?= (int)$t['id'] ?>" class="btn btn-primary btn-sm">Pitanja</a>
                            <a href="?action=delete&id=<?= (int)$t['id'] ?>" class="btn btn-danger btn-sm" data-confirm="Obrisati test '<?= sanitize($t['title']) ?>'?">Obriši</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
