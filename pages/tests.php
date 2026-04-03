<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Testovi';
$db = getDB();

$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search     = isset($_GET['q']) ? trim($_GET['q']) : '';

// Load all categories
$categories = $db->query('SELECT * FROM categories ORDER BY sort_order')->fetch_all(MYSQLI_ASSOC);

// Build filter clause
$where  = [];
$params = [];
$types  = '';
if ($categoryId > 0) { $where[] = 'c.id = ?'; $params[] = $categoryId; $types .= 'i'; }
if ($search !== '') { $like = '%' . $search . '%'; $where[] = '(t.title LIKE ? OR t.description LIKE ?)'; $params[] = $like; $params[] = $like; $types .= 'ss'; }

$sql = 'SELECT t.*, s.name AS sub_name, c.id AS cat_id, c.name AS cat_name, c.color AS cat_color, c.icon AS cat_icon
        FROM tests t
        JOIN subcategories s ON t.subcategory_id = s.id
        JOIN categories c    ON s.category_id    = c.id'
      . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
      . ' ORDER BY c.sort_order, s.sort_order, t.id';

$stmt = $db->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Group by category → subcategory
$grouped = [];
foreach ($tests as $t) {
    $grouped[$t['cat_id']]['info'] = ['id'=>$t['cat_id'],'name'=>$t['cat_name'],'color'=>$t['cat_color'],'icon'=>$t['cat_icon']];
    $grouped[$t['cat_id']]['subs'][$t['sub_name']][] = $t;
}

require_once __DIR__ . '/../includes/header.php';
?>
<section class="section">
    <div class="container">
        <div class="d-flex align-center gap-2 mb-4" style="flex-wrap:wrap">
            <h1 style="margin:0;flex:1">Testovi</h1>
        </div>

        <!-- Search + filter -->
        <form method="get" class="d-flex gap-2 mb-4" style="flex-wrap:wrap;align-items:center">
            <div class="input-group" style="flex:1;min-width:220px">
                <input type="text" name="q" value="<?= sanitize($search) ?>" class="form-control" placeholder="Pretraži testove...">
                <button type="submit" class="btn btn-primary">Pretraži</button>
            </div>
            <select name="category" class="form-select" style="width:auto" onchange="this.form.submit()">
                <option value="0">Sve kategorije</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
                    <?= sanitize($cat['icon']) ?> <?= sanitize($cat['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php if ($categoryId || $search): ?>
            <a href="<?= SITE_URL ?>/pages/tests.php" class="btn btn-ghost btn-sm">Resetuj filter</a>
            <?php endif; ?>
        </form>

        <?php if (empty($grouped)): ?>
        <div class="card card-body text-center" style="padding:3rem">
            <h3>Nema pronađenih testova</h3>
            <p class="text-muted">Pokušajte drugačiji upit ili resetujte filter.</p>
        </div>
        <?php else: foreach ($grouped as $catId => $catData): ?>
        <div class="mb-5">
            <div class="d-flex align-center gap-2 mb-3">
                <h2 style="margin:0;font-size:1.5rem"><?= sanitize($catData['info']['name']) ?></h2>
            </div>

            <?php foreach ($catData['subs'] as $subName => $subTests): ?>
            <div class="mb-4">
                <h3 style="font-size:1.1rem;color:var(--text-muted);margin-bottom:.75rem;font-weight:700">
                    <?= sanitize($subName) ?> <span style="font-size:.85rem;background:var(--gray-100);padding:.15rem .6rem;border-radius:20px;margin-left:.5rem"><?= count($subTests) ?></span>
                </h3>
                <div class="grid grid-3">
                    <?php foreach ($subTests as $test): ?>
                    <div class="card test-card hover-glow">
                        <div class="card-body">
                            <div class="d-flex align-center gap-2 mb-2">
                                <span class="badge badge-<?= sanitize($test['difficulty']) ?>"><?=
                                    ['beginner'=>'Početni','intermediate'=>'Srednji','advanced'=>'Napredni'][$test['difficulty']] ?? $test['difficulty']
                                ?></span>
                            </div>
                            <div class="test-card-title"><?= sanitize($test['title']) ?></div>
                            <div class="test-card-meta">
                                <span><?= formatTime((int)$test['time_limit']) ?></span>
                                <span>Prolaz: <?= (int)$test['passing_score'] ?>%</span>
                            </div>
                            <?php if ($test['description']): ?>
                            <p class="text-muted" style="font-size:.87rem;margin-bottom:.75rem"><?= sanitize($test['description']) ?></p>
                            <?php endif; ?>
                            <a href="<?= SITE_URL ?>/pages/test-take.php?id=<?= (int)$test['id'] ?>" class="btn btn-primary btn-sm btn-block">Počni test</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
