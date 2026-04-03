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

// ── TEACHER: APPROVE / REJECT ─────────────────────────────────────────────────
if ($action === 'approve' || $action === 'reject') {
    $id     = (int)($_GET['id'] ?? 0);
    $status = $action === 'approve' ? 'approved' : 'rejected';
    if ($id) {
        $stmt = $db->prepare('UPDATE live_teachers SET status=? WHERE id=?');
        $stmt->bind_param('si', $status, $id);
        $stmt->execute();
        $stmt->close();
        logAdminAction($adminId, $action, 'live_teachers', $id);
    }
    header('Location: ' . SITE_URL . '/admin/teachers.php?msg=' . urlencode($action === 'approve' ? 'Nastavnik odobren.' : 'Nastavnik odbijen.')); exit;
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
        $id              = (int)($_POST['id']   ?? 0);
        $name            = trim($_POST['name']  ?? '');
        $bio             = trim($_POST['bio']   ?? '');
        $email           = trim($_POST['email'] ?? '');
        $phone           = trim($_POST['phone'] ?? '');
        $experience      = trim($_POST['experience'] ?? '');
        $certificate     = trim($_POST['certificate'] ?? '');
        $teaching_method = trim($_POST['teaching_method'] ?? '');
        $contact_viber   = trim($_POST['contact_viber'] ?? '');
        $contact_whatsapp= trim($_POST['contact_whatsapp'] ?? '');
        $lesson_duration = trim($_POST['lesson_duration'] ?? '');
        $rate            = floatval($_POST['hourly_rate'] ?? 0);
        $days            = trim($_POST['available_days'] ?? '');
        $photo           = trim($_POST['photo_url']      ?? '');
        $catId           = (int)($_POST['teacher_category_id'] ?? 0) ?: null;
        $status          = in_array($_POST['status'] ?? '', ['pending','approved','rejected'], true)
                           ? $_POST['status'] : 'approved';

        if (!empty($_FILES['photo_file']['name'])) {
            $uploaded = uploadFile($_FILES['photo_file'], 'image');
            if ($uploaded) $photo = UPLOAD_URL . $uploaded;
        }

        if ($id) {
            $stmt = $db->prepare(
                'UPDATE live_teachers
                 SET teacher_category_id=?,name=?,bio=?,email=?,phone=?,
                     experience=?,certificate=?,teaching_method=?,
                     contact_viber=?,contact_whatsapp=?,
                     hourly_rate=?,available_days=?,lesson_duration=?,photo_url=?,status=?
                 WHERE id=?'
            );
            $stmt->bind_param('isssssssssdssssi',
                $catId,$name,$bio,$email,$phone,
                $experience,$certificate,$teaching_method,
                $contact_viber,$contact_whatsapp,
                $rate,$days,$lesson_duration,$photo,$status,$id);
            $stmt->execute(); $stmt->close();
            logAdminAction($adminId, 'update', 'live_teachers', $id);
            $msg = 'Nastavnik ažuriran.';
        } else {
            $stmt = $db->prepare(
                'INSERT INTO live_teachers
                 (teacher_category_id,name,bio,email,phone,
                  experience,certificate,teaching_method,
                  contact_viber,contact_whatsapp,
                  hourly_rate,available_days,lesson_duration,photo_url,status)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $stmt->bind_param('isssssssssdssss',
                $catId,$name,$bio,$email,$phone,
                $experience,$certificate,$teaching_method,
                $contact_viber,$contact_whatsapp,
                $rate,$days,$lesson_duration,$photo,$status);
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
     ORDER BY t.status DESC, tc.sort_order, tc.name, t.name'
)->fetch_all(MYSQLI_ASSOC);

$pending  = array_filter($teachers, fn($t) => $t['status'] === 'pending');
$approved = array_filter($teachers, fn($t) => $t['status'] === 'approved');
$rejected = array_filter($teachers, fn($t) => $t['status'] === 'rejected');

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
        <h1 class="admin-page-title">Nastavnici</h1>
        <?php if ($msg):   ?><div class="alert alert-success"><?= sanitize($msg) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= sanitize($error) ?></div><?php endif; ?>

        <!-- Pending applications -->
        <?php if (!empty($pending)): ?>
        <div class="card mb-4" style="border:2px solid #CA8A04">
            <div class="card-header" style="background:#FEF9C3;color:#92400E">
                Prijave na čekanju (<?= count($pending) ?>)
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Ime</th><th>Kategorija</th><th>Email</th><th>Telefon</th><th>Iskustvo</th><th>Akcije</th></tr></thead>
                    <tbody>
                    <?php foreach ($pending as $t): ?>
                    <tr>
                        <td><strong><?= sanitize($t['name']) ?></strong></td>
                        <td><?= $t['cat_name'] ? '<span class="badge badge-purple">' . sanitize($t['cat_name']) . '</span>' : '<span style="color:var(--text-muted)">—</span>' ?></td>
                        <td><?= sanitize($t['email'] ?? '-') ?></td>
                        <td><?= sanitize($t['phone'] ?? '-') ?></td>
                        <td style="font-size:.85rem;max-width:200px;overflow:hidden;text-overflow:ellipsis">
                            <?= sanitize(mb_substr($t['experience'] ?? '', 0, 80)) ?>
                        </td>
                        <td class="table-actions">
                            <a href="?action=approve&id=<?= (int)$t['id'] ?>" class="btn btn-primary btn-sm"
                               onclick="return confirm('Odobriti nastavnika?')">Odobri</a>
                            <a href="?action=reject&id=<?= (int)$t['id'] ?>" class="btn btn-danger btn-sm"
                               onclick="return confirm('Odbiti prijavu?')">Odbij</a>
                            <a href="?action=edit&id=<?= (int)$t['id'] ?>" class="btn btn-outline btn-sm">Izmeni</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Teacher Categories Management -->
        <div class="card mb-4">
            <div class="card-header">Kategorije nastavnika</div>
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
                                   onclick="return confirm('Obrisati kategoriju \'<?= sanitize($tc['name']) ?>\'?')">Obriši</a>
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
            <div class="card-header"><?= $editTeacher ? 'Izmeni nastavnika' : 'Novi nastavnik' ?></div>
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
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="pending"  <?= ($editTeacher['status'] ?? '') === 'pending'  ? 'selected' : '' ?>>Na čekanju</option>
                                <option value="approved" <?= ($editTeacher['status'] ?? 'approved') === 'approved' ? 'selected' : '' ?>>Odobren</option>
                                <option value="rejected" <?= ($editTeacher['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Odbijen</option>
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
                            <label class="form-label">Viber</label>
                            <input type="text" name="contact_viber" class="form-control"
                                   value="<?= sanitize($editTeacher['contact_viber'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">WhatsApp</label>
                            <input type="text" name="contact_whatsapp" class="form-control"
                                   value="<?= sanitize($editTeacher['contact_whatsapp'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cena po času (RSD)</label>
                            <input type="number" name="hourly_rate" class="form-control"
                                   value="<?= (float)($editTeacher['hourly_rate'] ?? 0) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Trajanje časa</label>
                            <input type="text" name="lesson_duration" class="form-control"
                                   value="<?= sanitize($editTeacher['lesson_duration'] ?? '') ?>"
                                   placeholder="npr. 45 min, 60 min">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Načini održavanja (zarezom)</label>
                            <input type="text" name="teaching_method" class="form-control"
                                   value="<?= sanitize($editTeacher['teaching_method'] ?? '') ?>"
                                   placeholder="zoom,google_meet,uzivo">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fotografija</label>
                            <input type="file" name="photo_file" class="form-control" accept="image/*">
                        </div>
                        <div class="form-group" style="grid-column:1/-1">
                            <label class="form-label">Biografija</label>
                            <textarea name="bio" class="form-control" rows="3"><?= sanitize($editTeacher['bio'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group" style="grid-column:1/-1">
                            <label class="form-label">Iskustvo</label>
                            <textarea name="experience" class="form-control" rows="2"><?= sanitize($editTeacher['experience'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Sertifikat</label>
                            <input type="text" name="certificate" class="form-control"
                                   value="<?= sanitize($editTeacher['certificate'] ?? '') ?>"
                                   placeholder="npr. Goethe-Zertifikat B2">
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

        <!-- Approved Teachers Table -->
        <div class="card mb-4">
            <div class="card-header">Odobreni nastavnici (<?= count($approved) ?>)</div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Ime</th><th>Kategorija</th><th>Email</th><th>Cena</th><th>Akcije</th></tr></thead>
                    <tbody>
                    <?php foreach ($approved as $t): ?>
                    <tr>
                        <td><strong><?= sanitize($t['name']) ?></strong></td>
                        <td><?= $t['cat_name'] ? '<span class="badge badge-purple">' . sanitize($t['cat_name']) . '</span>' : '<span style="color:var(--text-muted)">—</span>' ?></td>
                        <td><?= sanitize($t['email'] ?? '-') ?></td>
                        <td><?= $t['hourly_rate'] ? number_format((float)$t['hourly_rate'],0,',','.') . ' RSD' : '-' ?></td>
                        <td class="table-actions">
                            <a href="?action=edit&id=<?= (int)$t['id'] ?>" class="btn btn-outline btn-sm">Izmeni</a>
                            <a href="?action=reject&id=<?= (int)$t['id'] ?>" class="btn btn-danger btn-sm"
                               onclick="return confirm('Odbiti nastavnika?')">Odbij</a>
                            <a href="?action=delete&id=<?= (int)$t['id'] ?>" class="btn btn-danger btn-sm"
                               onclick="return confirm('Obrisati nastavnika \'<?= sanitize($t['name']) ?>\'?')">Obriši</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Rejected applications -->
        <?php if (!empty($rejected)): ?>
        <div class="card">
            <div class="card-header">Odbijene prijave (<?= count($rejected) ?>)</div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Ime</th><th>Kategorija</th><th>Email</th><th>Akcije</th></tr></thead>
                    <tbody>
                    <?php foreach ($rejected as $t): ?>
                    <tr>
                        <td><strong><?= sanitize($t['name']) ?></strong></td>
                        <td><?= $t['cat_name'] ? sanitize($t['cat_name']) : '—' ?></td>
                        <td><?= sanitize($t['email'] ?? '-') ?></td>
                        <td class="table-actions">
                            <a href="?action=approve&id=<?= (int)$t['id'] ?>" class="btn btn-primary btn-sm"
                               onclick="return confirm('Odobriti nastavnika?')">Odobri</a>
                            <a href="?action=delete&id=<?= (int)$t['id'] ?>" class="btn btn-danger btn-sm"
                               onclick="return confirm('Obrisati?')">Obriši</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
