<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db    = getDB();
$page  = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset  = ($page - 1) * $perPage;
$search  = trim($_GET['q'] ?? '');

$where  = [];
$params = [];
$types  = '';
if ($search !== '') {
    $like = '%' . $search . '%';
    $where[] = '(u.username LIKE ? OR u.email LIKE ?)';
    $params[] = $like; $params[] = $like; $types .= 'ss';
}

$total = (int)$db->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];

$sql  = 'SELECT u.*, (SELECT COUNT(*) FROM user_progress WHERE user_id=u.id) AS tests_done,
                     (SELECT COUNT(*) FROM user_achievements WHERE user_id=u.id) AS achievements
         FROM users u'
      . ($where ? ' WHERE '.implode(' AND ',$where) : '')
      . ' ORDER BY u.created_at DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;

$stmt = $db->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pages = max(1, (int)ceil($total / $perPage));

$pageTitle = 'Admin – Korisnici';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <div class="admin-content">
        <h1 class="admin-page-title">Korisnici (<?= $total ?>)</h1>

        <form method="GET" class="d-flex gap-2 mb-4" style="align-items:center">
            <div class="input-group" style="max-width:360px">
                <input type="text" name="q" value="<?= sanitize($search) ?>" class="form-control" placeholder="Pretraži korisnike...">
                <button type="submit" class="btn btn-primary btn-sm">Pretraži</button>
            </div>
            <?php if ($search): ?><a href="<?= SITE_URL ?>/admin/users.php" class="btn btn-ghost btn-sm">Resetuj</a><?php endif; ?>
        </form>

        <div class="card">
            <div class="table-wrapper">
                <table class="table sortable-table">
                    <thead>
                        <tr>
                            <th>ID</th><th>Korisnik</th><th>E-mail</th><th>Uloga</th>
                            <th>Poeni</th><th>Testovi</th><th>Dostignuća</th><th>Datum reg.</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= (int)$u['id'] ?></td>
                        <td>
                            <div class="d-flex align-center gap-2">
                                <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#6B21A8,#9333EA);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.85rem;flex-shrink:0"><?= strtoupper(substr($u['username'],0,1)) ?></div>
                                <strong><?= sanitize($u['username']) ?></strong>
                            </div>
                        </td>
                        <td style="font-size:.88rem"><?= sanitize($u['email']) ?></td>
                        <td><span class="badge <?= $u['role']==='admin' ? 'badge-blue' : 'badge-purple' ?>"><?= $u['role'] ?></span></td>
                        <td><strong style="color:var(--purple-800)"><?= number_format((int)$u['total_points']) ?></strong></td>
                        <td><?= (int)$u['tests_done'] ?></td>
                        <td><?= (int)$u['achievements'] ?></td>
                        <td style="font-size:.82rem;white-space:nowrap"><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
        <div class="d-flex gap-2 mt-4" style="flex-wrap:wrap">
            <?php for ($p=1;$p<=$pages;$p++): ?>
            <a href="?page=<?= $p ?>&q=<?= urlencode($search) ?>" class="btn <?= $p==$page?'btn-primary':'btn-outline' ?> btn-sm"><?= $p ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
