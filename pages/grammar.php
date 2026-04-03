<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Gramatika';
$db        = getDB();
$lessonId  = isset($_GET['lesson']) ? (int)$_GET['lesson'] : 0;
$difficulty= isset($_GET['level'])  ? $_GET['level'] : '';

// All lessons (sidebar)
$stmt = $db->prepare('SELECT id, title, difficulty, sort_order FROM grammar_lessons ORDER BY sort_order');
$stmt->execute();
$allLessons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Active lesson
$activeLesson = null;
if ($lessonId) {
    $stmt = $db->prepare('SELECT * FROM grammar_lessons WHERE id = ?');
    $stmt->bind_param('i', $lessonId);
    $stmt->execute();
    $activeLesson = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
if (!$activeLesson && !empty($allLessons)) {
    $first = $allLessons[0];
    $stmt  = $db->prepare('SELECT * FROM grammar_lessons WHERE id = ?');
    $stmt->bind_param('i', $first['id']);
    $stmt->execute();
    $activeLesson = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

require_once __DIR__ . '/../includes/header.php';
?>
<section class="section">
    <div class="container">
        <h1 style="margin-bottom:2rem">Gramatika</h1>
        <div style="display:flex;gap:2rem;align-items:flex-start;flex-wrap:wrap">
            <!-- Sidebar -->
            <div style="width:260px;flex-shrink:0;background:var(--white);border:1px solid var(--border);border-radius:var(--radius);padding:1rem;position:sticky;top:calc(var(--nav-h) + 1rem)">
                <h4 style="margin-bottom:1rem;font-size:1rem">Lekcije</h4>
                <?php foreach ($allLessons as $lesson): ?>
                <a href="?lesson=<?= (int)$lesson['id'] ?>"
                   class="d-flex align-center gap-2"
                   style="padding:.65rem .75rem;border-radius:8px;margin-bottom:.35rem;text-decoration:none;font-weight:600;font-size:.9rem;
                   <?= ($activeLesson && $activeLesson['id'] == $lesson['id']) ? 'background:var(--purple-100);color:var(--purple-800);' : 'color:var(--gray-600);' ?>
                   transition:all .2s">
                    <span class="badge badge-<?= sanitize($lesson['difficulty']) ?>" style="font-size:.7rem;padding:.15rem .5rem">
                        <?= ['beginner'=>'A','intermediate'=>'B','advanced'=>'C'][$lesson['difficulty']] ?>
                    </span>
                    <?= sanitize($lesson['title']) ?>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Lesson content -->
            <div style="flex:1;min-width:0">
                <?php if ($activeLesson): ?>
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-center gap-2">
                            <span class="badge badge-<?= sanitize($activeLesson['difficulty']) ?>">
                                <?= ['beginner'=>'Početni','intermediate'=>'Srednji','advanced'=>'Napredni'][$activeLesson['difficulty']] ?>
                            </span>
                            <h2 style="margin:0;font-size:1.4rem"><?= sanitize($activeLesson['title']) ?></h2>
                        </div>
                    </div>
                    <div class="card-body" style="line-height:1.8">
                        <?= $activeLesson['content'] /* already sanitized in DB, contains trusted HTML */ ?>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="d-flex gap-2 mt-3" style="justify-content:space-between">
                    <?php
                    $ids = array_column($allLessons, 'id');
                    $pos = array_search($activeLesson['id'], $ids);
                    $prevId = ($pos > 0) ? $ids[$pos - 1] : null;
                    $nextId = ($pos < count($ids)-1) ? $ids[$pos + 1] : null;
                    ?>
                    <?php if ($prevId): ?><a href="?lesson=<?= $prevId ?>" class="btn btn-outline btn-sm">← Prethodna lekcija</a><?php else: ?><span></span><?php endif; ?>
                    <?php if ($nextId): ?><a href="?lesson=<?= $nextId ?>" class="btn btn-primary btn-sm">Sledeća lekcija →</a><?php endif; ?>
                </div>
                <?php else: ?>
                <div class="card card-body text-center" style="padding:3rem">
                    
                    <h3>Odaberite lekciju</h3>
                    <p class="text-muted">Kliknite na neku lekciju sa liste sa leve strane.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
