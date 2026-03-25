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

// ── TEACHER CATEGORY: DELETE ──────────────────────────────────────────────────
if ($action === 'delete_cat') {
    $catId = (int)($_GET['id'] ?? 0);
    if ($catId) {
        $stmt = $db->prepare('DELETE FROM teacher_categories WHERE id=?');
        $stmt->bind_param('i', $catId);
        $stmt->execute();
        $stmt->close();
        logAdminAction($adminId, 'delete', 'teacher_categories', $catId);
    }
    header('Location: ' . SITE_URL . '/admin/teachers.php?msg=Kategorija+obrisana'); exit;
}

// ── TEACHER CATEGORY: CREATE ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    if (!verifyToken($_POST['csrf_token'] ?? '')) {
        $error = 'Nevažeći CSRF token.';
    } else {
        $catName  = trim($_POST['cat_name'] ?? '');
        $catOrder = (int)($_POST['cat_sort_order'] ?? 0);
        if ($catName !== '') {
            $stmt = $db->prepare('INSERT INTO teacher_categories (name, sort_order) VALUES (?, ?)');
            $stmt->bind_param('si', $catName, $catOrder);
            $stmt->execute();
            logAdminAction($adminId, 'create', 'teacher_categories', $db->insert_id);
            $stmt->close();
            $msg = 'Kategorija kreirana.';
        } else {
            $error = 'Naziv kategorije je obavezan.';
        }
    }
}

// ── TEACHER: DELETE ───────────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) {
        $stmt = $db->prepare('DELETE FROM live_teachers WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        logAdminAction($adminId, 'delete', 'live_teachers', $id);
    }
    header('Location: ' . SITE_URL . '/admin/teachers.php?msg=Nastavnik+obrisan'); exit;
}

// ── TEACHER: CREATE / UPDATE ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['save_category'])) {
    if (!verifyToken($_POST['csrf_token'] ?? '')) {
        $error = 'Nevažeći CSRF token.';
    } else {
        $id    = (int)($_POST['id']   ?? 0);
        $name  = trim($_POST['name']  ?? '');
        $bio   = trim($_POST['bio']   ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $subs  = trim($_POST['subjects']       ?? '');
        $langs = trim($_POST['languages']      ?? '');
        $rate  = floatval($_POST['hourly_rate'] ?? 0);
        $days  = trim($_POST['available_days'] ?? '');
        $photo = trim($_POST['photo_url']      ?? '');
        $catId = (int)($_POST['teacher_category_id'] ?? 0) ?: null;

        if (!empty($_FILES['photo_file']['name'])) {
            $uploaded = uploadFile($_FILES['photo_file'], 'image');
            if ($uploaded) $photo = UPLOAD_URL . $uploaded;
        }

        if ($id) {
            $stmt = $db->prepare(
                'UPDATE live_teachers
                 SET teacher_category_id=?,name=?,bio=?,email=?,phone=?,
                     subjects=?,languages=?,hourly_rate=?,available_days=?,photo_url=?
                 WHERE id=?'
            );
            $stmt->bind_param('issssssdssi', $catId,$name,$bio,$email,$phone,$subs,$langs,$rate,$days,$photo,$id);
            $stmt->execute(); $stmt->close();
            logAdminAction($adminId, 'update', 'live_teachers', $id);
            $msg = 'Nastavnik ažuriran.';
        } else {
            $stmt = $db->prepare(
                'INSERT INTO live_teachers
                 (teacher_category_id,name,bio,email,phone,subjects,languages,hourly_rate,available_days,photo_url)
                 VALUES (?,?,?,?,?,?,?,?,?,?)'
            );
            $stmt->bind_param('issssssdss', $catId,$name,$bio,$email,$phone,$subs,$langs,$rate,$days,$photo);
            $stmt->execute(); $stmt->close();
            logAdminAction($adminId, 'create', 'live_teachers', $db->insert_id);
            $msg = 'Nastavnik kreiran.';
        }
        $action = 'list';
    }
}

if (isset($_GET['msg'])) $msg = sanitize($_GET['msg']);

$teacherCats = $db->query('SELECT * FROM teacher_categories ORDER BY sort_order, name')->fetch_all(MYSQLI_ASSOC);
$teachers    = $db->query(
    'SELECT t.*, tc.name AS cat_name FROM live_teachers t
     LEFT JOIN teacher_categories tc ON tc.id = t.teacher_category_id
     ORDER BY tc.sort_order, tc.name, t.name'
)->fetch_all(MYSQLI_ASSOC);

$editTeacher = null;
if ($action === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) {
        $stmt = $db->prepare('SELECT * FROM live_teachers WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $editTeacher = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

$pageTitle    = 'Admin – Nastavnici';
$extraScripts = '<script src="' . SITE_URL . '/assets/js/admin.js"></script>';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <div class="admin-content">
        <h1 class="admin-page-title">👨‍🏫 Nastavnici</h1>
        <?php if ($msg):   ?><div class="alert alert-success"><?= sanitize($msg) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= sanitize($error) ?></div><?php endif; ?>

        <!-- Teacher Categories Management -->
        <div class="card mb-4">
            <div class="card-header">📂 Kategorije nastavnika</div>
            <div class="card-body">
                <form method="POST" class="d-flex gap-2 mb-3" style="flex-wrap:wrap;align-items:flex-end">
                    <input type="hidden" name="csrf_token" value="<?= generateToken() ?>">
                    <input type="hidden" name="save_category" value="1">
                    <div class="form-group" style="flex:1;min-width:180px;margin-bottom:0">
                        <label class="form-label">Naziv kategorije</label>
                        <input type="text" name="cat_name" class="form-control" required placeholder="npr. Gramatika">
                    </div>
                    <div class="form-group" style="width:120px;margin-bottom:0">
                        <label class="form-label">Redosled</label>
                        <input type="number" name="cat_sort_order" class="form-control" value="0" min="0">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Dodaj kategoriju</button>
                </form>
                <?php if (!empty($teacherCats)): ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead><tr><th>Naziv</th><th>Redosled</th><th>Akcije</th></tr></thead>
                        <tbody>
                        <?php foreach ($teacherCats as $tc): ?>
                        <tr>
                            <td><?= sanitize($tc['name']) ?></td>
                            <td><?= (int)$tc['sort_order'] ?></td>
                            <td class="table-actions">
                                <a href="?action=delete_cat&id=<?= (int)$tc['id'] ?>" class="btn btn-danger btn-sm"
                                   onclick="return confirm('Obrisati kategoriju \'<?= sanitize($tc['name']) ?>\'?')">🗑️</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted" style="margin:0">Još nema kategorija.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Teacher Form -->
        <div class="card mb-4">
            <div class="card-header"><?= $editTeacher ? 'Izmeni nastavnika' : '➕ Novi nastavnik' ?></div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generateToken() ?>">
                    <?php if ($editTeacher): ?><input type="hidden" name="id" value="<?= (int)$editTeacher['id'] ?>"><?php endif; ?>
                    <div class="grid grid-2" style="gap:1rem">
                        <div class="form-group">
                            <label class="form-label">Ime i prezime</label>
                            <input type="text" name="name" class="form-control" required
                                   value="<?= sanitize($editTeacher['name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kategorija</label>
                            <select name="teacher_category_id" class="form-select">
                                <option value="0">— Bez kategorije —</option>
                                <?php foreach ($teacherCats as $tc): ?>
                                <option value="<?= (int)$tc['id'] ?>"
                                    <?= isset($editTeacher['teacher_category_id']) && $editTeacher['teacher_category_id'] == $tc['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($tc['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= sanitize($editTeacher['email'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Telefon</label>
                            <input type="text" name="phone" class="form-control"
                                   value="<?= sanitize($editTeacher['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cena po času (RSD)</label>
                            <input type="number" name="hourly_rate" class="form-control"
                                   value="<?= (float)($editTeacher['hourly_rate'] ?? 0) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fotografija</label>
                            <input type="file" name="photo_file" class="form-control" accept="image/*">
                        </div>
                        <div class="form-group" style="grid-column:1/-1">
                            <label class="form-label">Biografija</label>
                            <textarea name="bio" class="form-control" rows="3"><?= sanitize($editTeacher['bio'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Predmeti (zarezom)</label>
                            <input type="text" name="subjects" class="form-control"
                                   value="<?= sanitize($editTeacher['subjects'] ?? '') ?>"
                                   placeholder="Gramatika, Konverzacija">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Jezici (zarezom)</label>
                            <input type="text" name="languages" class="form-control"
                                   value="<?= sanitize($editTeacher['languages'] ?? '') ?>"
                                   placeholder="Srpski, Engleski">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Dostupni dani (zarezom)</label>
                            <input type="text" name="available_days" class="form-control"
                                   value="<?= sanitize($editTeacher['available_days'] ?? '') ?>"
                                   placeholder="Ponedeljak, Sreda, Petak">
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-2">
                        <button type="submit" class="btn btn-primary btn-sm"><?= $editTeacher ? 'Ažuriraj' : 'Kreiraj' ?></button>
                        <?php if ($editTeacher): ?>
                        <a href="<?= SITE_URL ?>/admin/teachers.php" class="btn btn-ghost btn-sm">Otkaži</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Teachers Table -->
        <div class="card">
            <div class="card-header">Svi nastavnici (<?= count($teachers) ?>)</div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Ime</th><th>Kategorija</th><th>Email</th><th>Cena</th><th>Akcije</th></tr></thead>
                    <tbody>
                    <?php foreach ($teachers as $t): ?>
                    <tr>
                        <td><strong><?= sanitize($t['name']) ?></strong></td>
                        <td><?= $t['cat_name'] ? '<span class="badge badge-purple">' . sanitize($t['cat_name']) . '</span>' : '<span style="color:var(--text-muted)">—</span>' ?></td>
                        <td><?= sanitize($t['email'] ?? '-') ?></td>
                        <td><?= $t['hourly_rate'] ? number_format((float)$t['hourly_rate'],0,',','.') . ' RSD' : '-' ?></td>
                        <td class="table-actions">
                            <a href="?action=edit&id=<?= (int)$t['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
                            <a href="?action=delete&id=<?= (int)$t['id'] ?>" class="btn btn-danger btn-sm"
                               onclick="return confirm('Obrisati nastavnika \'<?= sanitize($t['name']) ?>\'?')">🗑️</a>
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
