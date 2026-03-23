<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Živi Nastavnici';
$db        = getDB();
$teachers  = $db->query('SELECT * FROM live_teachers ORDER BY name')->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>
<section class="hero" style="padding:3rem 1.25rem 2.5rem">
    <div class="container hero-content">
        <h1 style="color:#fff">👨‍🏫 Živi Nastavnici</h1>
        <p class="hero-sub">Rezervišite privatni čas sa iskusnim nastavnicima nemačkog jezika</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if (empty($teachers)): ?>
        <div class="card card-body text-center" style="padding:3rem">
            <div style="font-size:3rem;margin-bottom:1rem">👨‍🏫</div>
            <h3>Nastavnici će uskoro biti dostupni</h3>
        </div>
        <?php else: ?>
        <div class="grid grid-3">
            <?php foreach ($teachers as $t):
                $subjects  = $t['subjects']  ? explode(',', $t['subjects'])  : [];
                $languages = $t['languages'] ? explode(',', $t['languages']) : [];
                $days      = $t['available_days'] ? explode(',', $t['available_days']) : [];
                $initial   = strtoupper(substr($t['name'], 0, 1));
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
                    <?php if (!empty($subjects)): ?>
                    <div class="teacher-subjects">
                        <?php foreach (array_slice($subjects, 0, 4) as $sub): ?>
                        <span class="teacher-tag"><?= sanitize(trim($sub)) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($t['bio']): ?>
                    <p class="text-muted" style="font-size:.88rem;text-align:center;margin:.75rem 0"><?= sanitize(substr($t['bio'], 0, 140)) ?>...</p>
                    <?php endif; ?>
                    <?php if (!empty($languages)): ?>
                    <p style="text-align:center;font-size:.82rem;color:var(--text-muted);margin-bottom:.5rem">
                        🗣️ <?= sanitize(implode(', ', array_map('trim', $languages))) ?>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($days)): ?>
                    <p style="text-align:center;font-size:.82rem;color:var(--text-muted);margin-bottom:.75rem">
                        📅 <?= sanitize(implode(', ', array_map('trim', $days))) ?>
                    </p>
                    <?php endif; ?>
                    <?php if ($t['hourly_rate']): ?>
                    <div class="teacher-rate"><?= number_format((float)$t['hourly_rate'], 0, ',', '.') ?> RSD/čas</div>
                    <?php endif; ?>
                    <div class="d-flex gap-2 mt-2" style="justify-content:center;flex-wrap:wrap">
                        <?php if ($t['email']): ?>
                        <a href="mailto:<?= sanitize($t['email']) ?>" class="btn btn-primary btn-sm">✉️ Pišite nam</a>
                        <?php endif; ?>
                        <?php if ($t['phone']): ?>
                        <a href="tel:<?= sanitize($t['phone']) ?>" class="btn btn-outline btn-sm">📞 Pozovite</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Info section -->
        <div class="card mt-5" style="background:linear-gradient(135deg,var(--purple-50),var(--white))">
            <div class="card-body" style="padding:2.5rem;text-align:center">
                <h2 style="margin-bottom:1rem">Zašto privatne lekcije?</h2>
                <div class="grid grid-3 mt-3">
                    <div class="card card-body text-center">
                        <div style="font-size:2rem;margin-bottom:.5rem">🎯</div>
                        <h4>Personalizovan pristup</h4>
                        <p class="text-muted" style="font-size:.9rem">Čas prilagođen vašim ciljevima i tempom učenja</p>
                    </div>
                    <div class="card card-body text-center">
                        <div style="font-size:2rem;margin-bottom:.5rem">⚡</div>
                        <h4>Brži napredak</h4>
                        <p class="text-muted" style="font-size:.9rem">Individualni rad garantuje brže rezultate</p>
                    </div>
                    <div class="card card-body text-center">
                        <div style="font-size:2rem;margin-bottom:.5rem">🌟</div>
                        <h4>Iskusni nastavnici</h4>
                        <p class="text-muted" style="font-size:.9rem">Svi nastavnici imaju višegodišnje iskustvo</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
