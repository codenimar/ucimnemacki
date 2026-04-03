<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db    = getDB();
$stats = [];
foreach (['users','tests','vocabulary','grammar_lessons','live_teachers','user_progress','categories'] as $t) {
    $stats[$t] = (int)$db->query("SELECT COUNT(*) AS c FROM `{$t}`")->fetch_assoc()['c'];
}

// Recent logs
$logs = $db->query(
    'SELECT l.*, u.username FROM admin_logs l JOIN users u ON l.admin_id=u.id ORDER BY l.created_at DESC LIMIT 10'
)->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Admin – Dashboard';
$extraHead = '<link rel="stylesheet" href="' . SITE_URL . '/assets/css/main.css">';
$extraScripts = '<script src="' . SITE_URL . '/assets/js/admin.js"></script>';

require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    <div class="admin-content">
        <h1 class="admin-page-title">Dashboard</h1>

        <!-- Stats -->
        <div class="grid grid-4 mb-4">
            <?php
            $statItems = [
                ['users',         'Korisnici',    '/admin/users.php',      '#2563EB'],
                ['tests',         'Testovi',      '/admin/tests.php',      '#6B21A8'],
                ['vocabulary',    'Vokabular',    '#',                     '#16A34A'],
                ['user_progress', 'Popunjenih',   '#',                     '#CA8A04'],
                ['live_teachers', 'Nastavnici','/admin/teachers.php',   '#EC4899'],
                ['categories',    'Kategorije',   '/admin/categories.php', '#DC2626'],
                ['grammar_lessons','Lekcije',     '#',                     '#0891B2'],
            ];
            foreach ($statItems as [$key, $label, $link, $color]): ?>
            <a href="<?= SITE_URL . $link ?>" class="stat-card" style="text-decoration:none;display:block">
                <div class="stat-card-num" style="color:<?= $color ?>"><?= $stats[$key] ?? 0 ?></div>
                <div class="stat-card-label"><?= $label ?></div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Quick actions -->
        <div class="card mb-4">
            <div class="card-header">Brze radnje</div>
            <div class="card-body d-flex gap-2" style="flex-wrap:wrap">
                <a href="<?= SITE_URL ?>/admin/tests.php?action=new"      class="btn btn-primary btn-sm">Novi test</a>
                <a href="<?= SITE_URL ?>/admin/categories.php?action=new" class="btn btn-outline btn-sm">Nova kategorija</a>
                <a href="<?= SITE_URL ?>/admin/teachers.php?action=new"   class="btn btn-outline btn-sm">Novi nastavnik</a>
                <a href="<?= SITE_URL ?>/admin/users.php"                 class="btn btn-ghost btn-sm">Korisnici</a>
            </div>
        </div>

        <!-- Recent logs -->
        <div class="card">
            <div class="card-header">Poslednje aktivnosti admina</div>
            <?php if (empty($logs)): ?>
            <div class="card-body text-muted">Nema aktivnosti.</div>
            <?php else: ?>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Vreme</th><th>Admin</th><th>Akcija</th><th>Tip</th><th>ID</th></tr></thead>
                    <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td style="font-size:.82rem;white-space:nowrap"><?= date('d.m.Y H:i', strtotime($log['created_at'])) ?></td>
                        <td><?= sanitize($log['username']) ?></td>
                        <td><span class="badge badge-purple"><?= sanitize($log['action']) ?></span></td>
                        <td><?= sanitize($log['target_type'] ?? '-') ?></td>
                        <td><?= (int)($log['target_id'] ?? 0) ?: '-' ?></td>
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
