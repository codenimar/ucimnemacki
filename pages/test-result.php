<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$testId   = isset($_GET['test_id'])  ? (int)$_GET['test_id']  : 0;
$score    = isset($_GET['score'])    ? (int)$_GET['score']     : 0;
$maxScore = isset($_GET['max'])      ? max(1, (int)$_GET['max']) : 1;
$timeSpent= isset($_GET['time'])     ? (int)$_GET['time']      : 0;
$timeout  = !empty($_GET['timeout']);

$db   = getDB();
$test = null;
if ($testId) {
    $stmt = $db->prepare('SELECT t.*, s.name AS sub_name, c.name AS cat_name FROM tests t JOIN subcategories s ON t.subcategory_id=s.id JOIN categories c ON s.category_id=c.id WHERE t.id=?');
    $stmt->bind_param('i', $testId);
    $stmt->execute();
    $test = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$pct   = $maxScore > 0 ? min(100, (int)round($score / $maxScore * 100)) : 0;
$grade = getGrade($pct);
$passed= $test ? $pct >= (int)$test['passing_score'] : $pct >= 60;

// New achievements (if logged in)
$newAchievements = [];
if (isLoggedIn()) {
    $newAchievements = checkAchievements((int)$_SESSION['user_id']);
}

$pageTitle  = 'Rezultati testa';
$extraScripts = '<script>
if (' . ($pct >= 80 ? 'true' : 'false') . ') {
    document.addEventListener("DOMContentLoaded", () => setTimeout(launchConfetti, 300));
}
</script>';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="container-sm">
        <!-- Result card -->
        <div class="card animate-zoom-in" style="overflow:visible">
            <div class="card-body text-center" style="padding:2.5rem">
                <!-- Status badge -->
                <div class="mb-3">
                    <?php if ($timeout): ?>
                    <span class="badge badge-intermediate" style="font-size:1rem;padding:.5rem 1.5rem">⏰ Vreme je isteklo!</span>
                    <?php elseif ($passed): ?>
                    <span class="badge badge-beginner" style="font-size:1rem;padding:.5rem 1.5rem">🎉 Test položen!</span>
                    <?php else: ?>
                    <span class="badge badge-advanced" style="font-size:1rem;padding:.5rem 1.5rem">📚 Test nije položen</span>
                    <?php endif; ?>
                </div>

                <?php if ($test): ?>
                <h2 style="margin-bottom:2rem"><?= sanitize($test['title']) ?></h2>
                <?php endif; ?>

                <!-- Score circle -->
                <div class="score-circle reveal" style="--pct:<?= $pct ?>">
                    <div class="score-inner">
                        <div class="score-num"><?= $pct ?>%</div>
                        <div class="score-label">SKOR</div>
                    </div>
                </div>

                <div style="font-size:3rem;margin:1.5rem 0"><?= $grade['emoji'] ?></div>
                <h3 style="color:<?= $grade['color'] ?>;font-size:2rem;margin-bottom:.5rem"><?= $grade['label'] ?></h3>
                <p class="text-muted" style="font-size:1rem"><?= $score ?> / <?= $maxScore ?> poena</p>

                <!-- Stats grid -->
                <div class="grid grid-3 mt-4 mb-4" style="text-align:center">
                    <div style="background:var(--purple-50);border-radius:12px;padding:1rem">
                        <div style="font-size:1.5rem;font-weight:800;color:var(--purple-800)"><?= $score ?></div>
                        <div class="text-muted" style="font-size:.85rem">Poena</div>
                    </div>
                    <div style="background:var(--purple-50);border-radius:12px;padding:1rem">
                        <div style="font-size:1.5rem;font-weight:800;color:var(--purple-800)"><?= formatTime($timeSpent) ?></div>
                        <div class="text-muted" style="font-size:.85rem">Vreme</div>
                    </div>
                    <div style="background:var(--purple-50);border-radius:12px;padding:1rem">
                        <div style="font-size:1.5rem;font-weight:800;color:<?= $grade['color'] ?>"><?= $pct ?>%</div>
                        <div class="text-muted" style="font-size:.85rem">Tačnost</div>
                    </div>
                </div>

                <!-- Motivational message -->
                <div class="alert <?= $passed ? 'alert-success' : 'alert-warning' ?>" style="text-align:left;margin-bottom:1.5rem">
                    <?php
                    if ($pct >= 95) echo '🏆 Savršen rezultat! Fenomenalno! Pravi si majstor nemačkog!';
                    elseif ($pct >= 80) echo '⭐ Odličan rezultat! Bravo, nastavi ovim tempom!';
                    elseif ($pct >= 65) echo '👍 Dobar rezultat! Još malo vežbe i biće savršeno!';
                    elseif ($pct >= 50) echo '📚 Prošao si! Ponovi gradivo i probaj ponovo za još bolji rezultat.';
                    else echo '💪 Nisi prošao ovaj put, ali ne odustaj! Svaka greška je nova lekcija.';
                    ?>
                </div>

                <!-- Buttons -->
                <div class="d-flex gap-2" style="justify-content:center;flex-wrap:wrap">
                    <?php if ($testId): ?>
                    <a href="<?= SITE_URL ?>/pages/test-take.php?id=<?= $testId ?>" class="btn btn-primary">🔄 Ponovi test</a>
                    <?php endif; ?>
                    <a href="<?= SITE_URL ?>/pages/tests.php" class="btn btn-outline">📝 Svi testovi</a>
                    <?php if (isLoggedIn()): ?>
                    <a href="<?= SITE_URL ?>/pages/profile.php" class="btn btn-ghost">👤 Moj profil</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- New achievements -->
        <?php if (!empty($newAchievements)): ?>
        <div class="card mt-4 animate-slide-up">
            <div class="card-header">🏅 Nova dostignuća zarađena!</div>
            <div class="card-body">
                <div class="grid grid-4" style="gap:1rem">
                    <?php foreach ($newAchievements as $ach): ?>
                    <div class="achievement-badge earned earn">
                        <div class="achievement-icon"><?= sanitize($ach['icon']) ?></div>
                        <div class="achievement-name"><?= sanitize($ach['name']) ?></div>
                        <div class="achievement-desc"><?= sanitize($ach['description']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
