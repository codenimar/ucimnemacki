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

if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) { $stmt=$db->prepare('DELETE FROM live_teachers WHERE id=?'); $stmt->bind_param('i',$id); $stmt->execute(); $stmt->close(); logAdminAction($adminId,'delete','live_teachers',$id); }
    header('Location: ' . SITE_URL . '/admin/teachers.php?msg=Nastavnik+obrisan'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyToken($_POST['csrf_token'] ?? '')) { $error = 'Nevažeći CSRF token.'; }
    else {
        $id    = (int)($_POST['id']   ?? 0);
        $name  = trim($_POST['name']  ?? '');
        $bio   = trim($_POST['bio']   ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $subs  = trim($_POST['subjects']       ?? '');
        $langs = trim($_POST['languages']      ?? '');
        $rate  = floatval($_POST['hourly_rate']?? 0);
        $days  = trim($_POST['available_days'] ?? '');
        $photo = trim($_POST['photo_url']      ?? '');

        // Handle photo upload
        if (!empty($_FILES['photo_file']['name'])) {
            $uploaded = uploadFile($_FILES['photo_file'], 'image');
            if ($uploaded) $photo = UPLOAD_URL . $uploaded;
        }

        if ($id) {
            $stmt = $db->prepare('UPDATE live_teachers SET name=?,bio=?,email=?,phone=?,subjects=?,languages=?,hourly_rate=?,available_days=?,photo_url=? WHERE id=?');
            $stmt->bind_param('ssssssdssi', $name,$bio,$email,$phone,$subs,$langs,$rate,$days,$photo,$id);
            $stmt->execute(); $stmt->close();
            logAdminAction($adminId,'update','live_teachers',$id);
            $msg = 'Nastavnik ažuriran.';
        } else {
            $stmt = $db->prepare('INSERT INTO live_teachers (name,bio,email,phone,subjects,languages,hourly_rate,available_days,photo_url) VALUES (?,?,?,?,?,?,?,?,?)');
            $stmt->bind_param('ssssssdss', $name,$bio,$email,$phone,$subs,$langs,$rate,$days,$photo);
            $stmt->execute(); $stmt->close();
            logAdminAction($adminId,'create','live_teachers',$db->insert_id);
            $msg = 'Nastavnik kreiran.';
        }
        $action = 'list';
    }
}

if (isset($_GET['msg'])) $msg = sanitize($_GET['msg']);
$teachers = $db->query('SELECT * FROM live_teachers ORDER BY name')->fetch_all(MYSQLI_ASSOC);

$editTeacher = null;
if ($action === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) { $stmt=$db->prepare('SELECT * FROM live_teachers WHERE id=?'); $stmt->bind_param('i',$id); $stmt->execute(); $editTeacher=$stmt->get_result()->fetch_assoc(); $stmt->close(); }
}

$pageTitle = 'Admin – Nastavnici';
$extraScripts = '<script src="' . SITE_URL . '/assets/js/admin.js"></script>';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <div class="admin-content">
        <h1 class="admin-page-title">👨‍🏫 Nastavnici</h1>
        <?php if ($msg):   ?><div class="alert alert-success"><?= sanitize($msg) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= sanitize($error) ?></div><?php endif; ?>

        <!-- Form -->
        <div class="card mb-4">
            <div class="card-header"><?= $editTeacher ? 'Izmeni nastavnika' : '➕ Novi nastavnik' ?></div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generateToken() ?>">
                    <?php if ($editTeacher): ?><input type="hidden" name="id" value="<?= (int)$editTeacher['id'] ?>"><?php endif; ?>
                    <div class="grid grid-2" style="gap:1rem">
                        <div class="form-group"><label class="form-label">Ime i prezime</label><input type="text" name="name" class="form-control" required value="<?= sanitize($editTeacher['name'] ?? '') ?>"></div>
                        <div class="form-group"><label class="form-label">E-mail</label><input type="email" name="email" class="form-control" value="<?= sanitize($editTeacher['email'] ?? '') ?>"></div>
                        <div class="form-group"><label class="form-label">Telefon</label><input type="text" name="phone" class="form-control" value="<?= sanitize($editTeacher['phone'] ?? '') ?>"></div>
                        <div class="form-group"><label class="form-label">Cena po času (RSD)</label><input type="number" name="hourly_rate" class="form-control" value="<?= (float)($editTeacher['hourly_rate'] ?? 0) ?>"></div>
                        <div class="form-group" style="grid-column:1/-1"><label class="form-label">Biografija</label><textarea name="bio" class="form-control" rows="3"><?= sanitize($editTeacher['bio'] ?? '') ?></textarea></div>
                        <div class="form-group"><label class="form-label">Predmeti (razdvojeni zarezom)</label><input type="text" name="subjects" class="form-control" value="<?= sanitize($editTeacher['subjects'] ?? '') ?>" placeholder="Gramatika, Konverzacija"></div>
                        <div class="form-group"><label class="form-label">Jezici (razdvojeni zarezom)</label><input type="text" name="languages" class="form-control" value="<?= sanitize($editTeacher['languages'] ?? '') ?>" placeholder="Srpski, Engleski"></div>
                        <div class="form-group"><label class="form-label">Dostupni dani (razdvojeni zarezom)</label><input type="text" name="available_days" class="form-control" value="<?= sanitize($editTeacher['available_days'] ?? '') ?>" placeholder="Ponedeljak, Sreda, Petak"></div>
                        <div class="form-group"><label class="form-label">Fotografija</label><input type="file" name="photo_file" class="form-control" accept="image/*" data-preview="photoPreview"><div id="photoPreview"></div></div>
                    </div>
                    <div class="d-flex gap-2 mt-2">
                        <button type="submit" class="btn btn-primary btn-sm"><?= $editTeacher ? 'Ažuriraj' : 'Kreiraj' ?></button>
                        <?php if ($editTeacher): ?><a href="<?= SITE_URL ?>/admin/teachers.php" class="btn btn-ghost btn-sm">Otkaži</a><?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-header">Svi nastavnici (<?= count($teachers) ?>)</div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Ime</th><th>Email</th><th>Cena</th><th>Predmeti</th><th>Akcije</th></tr></thead>
                    <tbody>
                    <?php foreach ($teachers as $t): ?>
                    <tr>
                        <td><strong><?= sanitize($t['name']) ?></strong></td>
                        <td><?= sanitize($t['email'] ?? '-') ?></td>
                        <td><?= $t['hourly_rate'] ? number_format((float)$t['hourly_rate'],0,',','.') . ' RSD' : '-' ?></td>
                        <td style="font-size:.82rem"><?= sanitize($t['subjects'] ?? '-') ?></td>
                        <td class="table-actions">
                            <a href="?action=edit&id=<?= (int)$t['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
                            <a href="?action=delete&id=<?= (int)$t['id'] ?>" class="btn btn-danger btn-sm" data-confirm="Obrisati nastavnika '<?= sanitize($t['name']) ?>'?">🗑️</a>
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
