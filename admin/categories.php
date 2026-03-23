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

// Handle POST (create/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyToken($_POST['csrf_token'] ?? '')) { $error = 'Nevažeći CSRF token.'; }
    else {
        $type = $_POST['type'] ?? 'category'; // category | subcategory
        if ($type === 'category') {
            $name  = trim($_POST['name'] ?? '');
            $desc  = trim($_POST['description'] ?? '');
            $icon  = trim($_POST['icon']  ?? '📚');
            $color = trim($_POST['color'] ?? '#6B21A8');
            $order = (int)($_POST['sort_order'] ?? 0);
            $id    = (int)($_POST['id'] ?? 0);
            if ($id) {
                $stmt = $db->prepare('UPDATE categories SET name=?,description=?,icon=?,color=?,sort_order=? WHERE id=?');
                $stmt->bind_param('ssssii', $name,$desc,$icon,$color,$order,$id);
                $stmt->execute(); $stmt->close();
                logAdminAction($adminId,'update','categories',$id);
                $msg = 'Kategorija ažurirana.';
            } else {
                $stmt = $db->prepare('INSERT INTO categories (name,description,icon,color,sort_order) VALUES (?,?,?,?,?)');
                $stmt->bind_param('ssssi', $name,$desc,$icon,$color,$order);
                $stmt->execute(); $stmt->close();
                logAdminAction($adminId,'create','categories',$db->insert_id);
                $msg = 'Kategorija kreirana.';
            }
        } else {
            // subcategory
            $catId = (int)($_POST['category_id'] ?? 0);
            $name  = trim($_POST['name'] ?? '');
            $desc  = trim($_POST['description'] ?? '');
            $icon  = trim($_POST['icon']  ?? '📝');
            $order = (int)($_POST['sort_order'] ?? 0);
            $id    = (int)($_POST['id'] ?? 0);
            if ($id) {
                $stmt = $db->prepare('UPDATE subcategories SET category_id=?,name=?,description=?,icon=?,sort_order=? WHERE id=?');
                $stmt->bind_param('isssii', $catId,$name,$desc,$icon,$order,$id);
                $stmt->execute(); $stmt->close();
                logAdminAction($adminId,'update','subcategories',$id);
                $msg = 'Potkategorija ažurirana.';
            } else {
                $stmt = $db->prepare('INSERT INTO subcategories (category_id,name,description,icon,sort_order) VALUES (?,?,?,?,?)');
                $stmt->bind_param('isssi', $catId,$name,$desc,$icon,$order);
                $stmt->execute(); $stmt->close();
                logAdminAction($adminId,'create','subcategories',$db->insert_id);
                $msg = 'Potkategorija kreirana.';
            }
        }
        $action = 'list';
    }
}

// Delete
if ($action === 'delete_cat') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) { $db->prepare('DELETE FROM categories WHERE id=?')->execute(); logAdminAction($adminId,'delete','categories',$id); }
    header('Location: ' . SITE_URL . '/admin/categories.php?msg=Obrisano'); exit;
}
if ($action === 'delete_sub') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) { $stmt=$db->prepare('DELETE FROM subcategories WHERE id=?'); $stmt->bind_param('i',$id); $stmt->execute(); $stmt->close(); logAdminAction($adminId,'delete','subcategories',$id); }
    header('Location: ' . SITE_URL . '/admin/categories.php?msg=Obrisano'); exit;
}

if (isset($_GET['msg'])) $msg = sanitize($_GET['msg']);

$categories   = $db->query('SELECT * FROM categories ORDER BY sort_order')->fetch_all(MYSQLI_ASSOC);
$subcategories= $db->query('SELECT s.*, c.name AS cat_name FROM subcategories s JOIN categories c ON s.category_id=c.id ORDER BY c.sort_order, s.sort_order')->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Admin – Kategorije';
$extraScripts = '<script src="' . SITE_URL . '/assets/js/admin.js"></script>';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <div class="admin-content">
        <h1 class="admin-page-title">📂 Kategorije & Potkategorije</h1>
        <?php if ($msg):   ?><div class="alert alert-success"><?= sanitize($msg) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= sanitize($error) ?></div><?php endif; ?>

        <div class="grid grid-2 mb-4" style="align-items:start;gap:1.5rem">
            <!-- Create category form -->
            <div class="card">
                <div class="card-header">➕ Nova kategorija</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateToken() ?>">
                        <input type="hidden" name="type"       value="category">
                        <div class="form-group"><label class="form-label">Naziv</label><input type="text" name="name" class="form-control" required></div>
                        <div class="form-group"><label class="form-label">Opis</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                        <div class="grid grid-2" style="gap:.75rem">
                            <div class="form-group"><label class="form-label">Ikona (emoji)</label><input type="text" name="icon" class="form-control" value="📚" maxlength="5"></div>
                            <div class="form-group"><label class="form-label">Boja</label><input type="color" name="color" class="form-control" value="#6B21A8" style="height:46px"></div>
                        </div>
                        <div class="form-group"><label class="form-label">Redosled</label><input type="number" name="sort_order" class="form-control" value="0"></div>
                        <button type="submit" class="btn btn-primary btn-sm">Sačuvaj kategoriju</button>
                    </form>
                </div>
            </div>
            <!-- Create subcategory form -->
            <div class="card">
                <div class="card-header">➕ Nova potkategorija</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateToken() ?>">
                        <input type="hidden" name="type"       value="subcategory">
                        <div class="form-group">
                            <label class="form-label">Kategorija</label>
                            <select name="category_id" class="form-select" required>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int)$cat['id'] ?>"><?= sanitize($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label class="form-label">Naziv</label><input type="text" name="name" class="form-control" required></div>
                        <div class="form-group"><label class="form-label">Opis</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                        <div class="grid grid-2" style="gap:.75rem">
                            <div class="form-group"><label class="form-label">Ikona</label><input type="text" name="icon" class="form-control" value="📝" maxlength="5"></div>
                            <div class="form-group"><label class="form-label">Redosled</label><input type="number" name="sort_order" class="form-control" value="0"></div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Sačuvaj potkategoriju</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Categories table -->
        <div class="card mb-4">
            <div class="card-header">Sve kategorije</div>
            <div class="table-wrapper">
                <table class="table sortable-table">
                    <thead><tr><th>ID</th><th>Ikona</th><th>Naziv</th><th>Boja</th><th>Redosled</th><th>Akcije</th></tr></thead>
                    <tbody>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?= (int)$cat['id'] ?></td>
                        <td style="font-size:1.3rem"><?= sanitize($cat['icon']) ?></td>
                        <td><strong><?= sanitize($cat['name']) ?></strong></td>
                        <td><span style="display:inline-block;width:20px;height:20px;background:<?= sanitize($cat['color']) ?>;border-radius:4px;vertical-align:middle;margin-right:.4rem"></span><?= sanitize($cat['color']) ?></td>
                        <td><?= (int)$cat['sort_order'] ?></td>
                        <td class="table-actions">
                            <a href="?action=delete_cat&id=<?= (int)$cat['id'] ?>" class="btn btn-danger btn-sm" data-confirm="Obrisati kategoriju '<?= sanitize($cat['name']) ?>'? Ovo će obrisati sve potkategorije i testove!">Obriši</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Subcategories table -->
        <div class="card">
            <div class="card-header">Sve potkategorije</div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>ID</th><th>Ikona</th><th>Naziv</th><th>Kategorija</th><th>Redosled</th><th>Akcije</th></tr></thead>
                    <tbody>
                    <?php foreach ($subcategories as $sub): ?>
                    <tr>
                        <td><?= (int)$sub['id'] ?></td>
                        <td style="font-size:1.2rem"><?= sanitize($sub['icon']) ?></td>
                        <td><?= sanitize($sub['name']) ?></td>
                        <td><span class="badge badge-purple"><?= sanitize($sub['cat_name']) ?></span></td>
                        <td><?= (int)$sub['sort_order'] ?></td>
                        <td class="table-actions">
                            <a href="?action=delete_sub&id=<?= (int)$sub['id'] ?>" class="btn btn-danger btn-sm" data-confirm="Obrisati '<?= sanitize($sub['name']) ?>'?">Obriši</a>
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
