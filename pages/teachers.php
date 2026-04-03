<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Nastavnici';
$db        = getDB();

// Teacher categories for filter
$teacherCats = $db->query(
    'SELECT * FROM teacher_categories ORDER BY sort_order, name'
)->fetch_all(MYSQLI_ASSOC);

$filterCatId = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Only approved teachers, optionally filtered by category
if ($filterCatId > 0) {
    $stmt = $db->prepare(
        'SELECT t.*, tc.name AS cat_name FROM live_teachers t
         LEFT JOIN teacher_categories tc ON tc.id = t.teacher_category_id
         WHERE t.status = "approved" AND t.teacher_category_id = ?
         ORDER BY tc.sort_order, tc.name, t.name'
    );
    $stmt->bind_param('i', $filterCatId);
    $stmt->execute();
    $teachers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $teachers = $db->query(
        'SELECT t.*, tc.name AS cat_name FROM live_teachers t
         LEFT JOIN teacher_categories tc ON tc.id = t.teacher_category_id
         WHERE t.status = "approved"
         ORDER BY tc.sort_order, tc.name, t.name'
    )->fetch_all(MYSQLI_ASSOC);
}

// Group by category
$byCategory = [];
foreach ($teachers as $t) {
    $byCategory[$t['cat_name'] ?? 'Ostalo'][] = $t;
}

require_once __DIR__ . '/../includes/header.php';
?>
<section class="hero" style="padding:3rem 1.25rem 2.5rem">
    <div class="container hero-content">
        <h1 style="color:#fff">Nastavnici</h1>
        <p class="hero-sub">Rezervišite privatni čas sa iskusnim nastavnicima nemačkog jezika</p>
        <div class="hero-cta animate-slide-up delay-1">
            <a href="<?= SITE_URL ?>/pages/teacher-apply.php" class="btn btn-xl" style="background:#fff;color:#6B21A8;">Postani nastavnik</a>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <!-- Category filter -->
        <div class="d-flex gap-2 mb-4" style="flex-wrap:wrap;align-items:center">
            <strong style="margin-right:.25rem">Filter:</strong>
            <a href="<?= SITE_URL ?>/pages/teachers.php"
               class="btn btn-sm <?= $filterCatId === 0 ? 'btn-primary' : 'btn-outline' ?>">Sve kategorije</a>
            <?php foreach ($teacherCats as $tc): ?>
            <a href="?category=<?= (int)$tc['id'] ?>"
               class="btn btn-sm <?= $filterCatId === (int)$tc['id'] ? 'btn-primary' : 'btn-outline' ?>">
                <?= sanitize($tc['name']) ?>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($teachers)): ?>
        <div class="card card-body text-center" style="padding:3rem">
            <h3>Nastavnici će uskoro biti dostupni</h3>
            <p class="text-muted mt-2">Budite prvi – <a href="<?= SITE_URL ?>/pages/teacher-apply.php">prijavite se kao nastavnik</a>.</p>
        </div>
        <?php else: ?>
        <?php foreach ($byCategory as $catName => $catTeachers): ?>
        <h2 style="margin-bottom:1.5rem;padding-bottom:.5rem;border-bottom:2px solid var(--border)">
            <?= sanitize($catName) ?>
            <span class="badge badge-purple" style="font-size:.85rem;vertical-align:middle"><?= count($catTeachers) ?></span>
        </h2>
        <div class="grid grid-3 mb-5">
            <?php foreach ($catTeachers as $t):
                $methods  = $t['teaching_method'] ? explode(',', $t['teaching_method']) : [];
                $days     = $t['available_days'] ? explode(',', $t['available_days']) : [];
                $initial  = strtoupper(substr($t['name'], 0, 1));
                $methodLabels = [
                    'zoom' => 'Zoom', 'google_meet' => 'Google Meet', 'skype' => 'Skype',
                    'microsoft_teams' => 'Microsoft Teams', 'uzivo' => 'Uživo', 'ostalo' => 'Ostalo',
                ];
            ?>
            <div class="card teacher-card hover-glow">
                <?php if ($t['photo_url']): ?>
                    <img src="<?= sanitize($t['photo_url']) ?>" class="teacher-photo" alt="<?= sanitize($t['name']) ?>">
                <?php else: ?>
                    <div style="height:120px;background:linear-gradient(135deg,#6B21A8,#9333EA)"></div>
                <?php endif; ?>
                <div class="card-body" style="padding-top:.5rem">
                    <div class="teacher-avatar"><?= $initial ?></div>
                    <div class="teacher-name mt-2"><?= sanitize($t['name']) ?></div>
                    <?php if ($t['certificate']): ?>
                    <div style="text-align:center;font-size:.82rem;color:var(--text-muted);margin-bottom:.5rem">
                        <?= sanitize($t['certificate']) ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($t['bio']): ?>
                    <p class="text-muted" style="font-size:.88rem;text-align:center;margin:.75rem 0">
                        <?= sanitize(mb_substr($t['bio'], 0, 140)) . (mb_strlen($t['bio']) > 140 ? '...' : '') ?>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($methods)): ?>
                    <p style="text-align:center;font-size:.82rem;color:var(--text-muted);margin-bottom:.5rem">
                        <?= sanitize(implode(', ', array_map(
                            fn($m) => $methodLabels[trim($m)] ?? trim($m),
                            $methods
                        ))) ?>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($days)): ?>
                    <p style="text-align:center;font-size:.82rem;color:var(--text-muted);margin-bottom:.75rem">
                        <?= sanitize(implode(', ', array_map('trim', $days))) ?>
                    </p>
                    <?php endif; ?>
                    <?php if ($t['lesson_duration'] || $t['hourly_rate']): ?>
                    <div class="teacher-rate">
                        <?php if ($t['lesson_duration']): ?><?= sanitize($t['lesson_duration']) ?> &mdash; <?php endif; ?>
                        <?php if ($t['hourly_rate']): ?><?= number_format((float)$t['hourly_rate'], 0, ',', '.') ?> RSD/čas<?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex gap-2 mt-2" style="justify-content:center;flex-wrap:wrap">
                        <?php if ($t['email']): ?>
                        <a href="mailto:<?= sanitize($t['email']) ?>" class="btn btn-primary btn-sm">E-mail</a>
                        <?php endif; ?>
                        <?php if ($t['phone']): ?>
                        <a href="tel:<?= sanitize($t['phone']) ?>" class="btn btn-outline btn-sm">Pozovite</a>
                        <?php endif; ?>
                        <?php if ($t['contact_viber']): ?>
                        <a href="viber://chat?number=<?= urlencode($t['contact_viber']) ?>" class="btn btn-outline btn-sm">Viber</a>
                        <?php endif; ?>
                        <?php if ($t['contact_whatsapp']): ?>
                        <a href="https://wa.me/<?= urlencode(preg_replace('/\D/', '', $t['contact_whatsapp'])) ?>" class="btn btn-outline btn-sm" target="_blank" rel="noopener">WhatsApp</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <!-- Info section -->
        <div class="card mt-5" style="background:linear-gradient(135deg,var(--purple-50),var(--white))">
            <div class="card-body" style="padding:2.5rem;text-align:center">
                <h2 style="margin-bottom:1rem">Zašto privatne lekcije?</h2>
                <div class="grid grid-3 mt-3">
                    <div class="card card-body text-center">
                        <h4>Personalizovan pristup</h4>
                        <p class="text-muted" style="font-size:.9rem">Čas prilagođen vašim ciljevima i tempom učenja</p>
                    </div>
                    <div class="card card-body text-center">
                        <h4>Brži napredak</h4>
                        <p class="text-muted" style="font-size:.9rem">Individualni rad garantuje brže rezultate</p>
                    </div>
                    <div class="card card-body text-center">
                        <h4>Iskusni nastavnici</h4>
                        <p class="text-muted" style="font-size:.9rem">Svi nastavnici imaju višegodišnje iskustvo</p>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="<?= SITE_URL ?>/pages/teacher-apply.php" class="btn btn-primary btn-lg">Postani nastavnik</a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
