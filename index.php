<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Naučite Nemački na zabavan način';
$bodyClass = 'page-home';

// Stats
$db = getDB();
$stats = [];
$res = $db->query('SELECT COUNT(*) AS cnt FROM tests');    $stats['tests']    = (int)($res->fetch_assoc()['cnt'] ?? 0);
$res = $db->query('SELECT COUNT(*) AS cnt FROM users WHERE role="user"'); $stats['users'] = (int)($res->fetch_assoc()['cnt'] ?? 0);
$res = $db->query('SELECT COUNT(*) AS cnt FROM vocabulary'); $stats['vocab']   = (int)($res->fetch_assoc()['cnt'] ?? 0);

// Categories
$cats = $db->query('SELECT c.*, (SELECT COUNT(*) FROM subcategories s WHERE s.category_id=c.id) AS sub_cnt FROM categories c ORDER BY c.sort_order')->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero -->
<section class="hero">
    <div class="hero-content container">
        <div class="hero-parrot animate-float" aria-hidden="true">
            <img src="<?= SITE_URL ?>/assets/images/parrot.svg" alt="Učim Nemački papagaj maskota" width="160" height="160">
        </div>
        <h1 class="animate-slide-up">Naučite Nemački<br>na zabavan način! 🇩🇪</h1>
        <p class="hero-sub animate-slide-up delay-1">Interaktivni testovi, vokabular, gramatika i živi nastavnici – sve na jednom mestu za srpske govornike.</p>
        <div class="hero-cta animate-slide-up delay-2">
            <?php if (isLoggedIn()): ?>
                <a href="<?= SITE_URL ?>/pages/tests.php" class="btn btn-xl" style="background:#fff;color:#6B21A8;">📝 Počni sa testovima</a>
                <a href="<?= SITE_URL ?>/pages/vocabulary.php" class="btn btn-xl btn-outline" style="border-color:#fff;color:#fff;">📖 Vokabular</a>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/pages/register.php" class="btn btn-xl" style="background:#fff;color:#6B21A8;">✨ Registruj se besplatno</a>
                <a href="<?= SITE_URL ?>/pages/tests.php" class="btn btn-xl btn-outline" style="border-color:rgba(255,255,255,.7);color:#fff;">👀 Pogledaj testove</a>
            <?php endif; ?>
        </div>
        <div class="hero-stats animate-fade-in delay-3">
            <div class="hero-stat">
                <div class="hero-stat-num" data-count="<?= $stats['tests'] ?>"><?= $stats['tests'] ?></div>
                <div class="hero-stat-label">Testova</div>
            </div>
            <div class="hero-stat">
                <div class="hero-stat-num" data-count="<?= $stats['users'] ?>"><?= $stats['users'] ?></div>
                <div class="hero-stat-label">Korisnika</div>
            </div>
            <div class="hero-stat">
                <div class="hero-stat-num" data-count="<?= $stats['vocab'] ?>"><?= $stats['vocab'] ?></div>
                <div class="hero-stat-label">Reči u vokabularu</div>
            </div>
            <div class="hero-stat">
                <div class="hero-stat-num">100%</div>
                <div class="hero-stat-label">Besplatno</div>
            </div>
        </div>
    </div>
</section>

<!-- Categories -->
<section class="section section-gray">
    <div class="container">
        <h2 class="section-title">Odaberi kategoriju učenja</h2>
        <p class="section-sub">Nemački za svakoga – od najmlađih do naprednih govornika</p>
        <div class="grid grid-3">
            <?php foreach ($cats as $cat): ?>
            <a href="<?= SITE_URL ?>/pages/tests.php?category=<?= (int)$cat['id'] ?>"
               class="category-card reveal"
               style="background: linear-gradient(135deg, <?= sanitize($cat['color']) ?> 0%, <?= sanitize($cat['color']) ?>cc 100%);">
                <div class="category-card-icon"><?= sanitize($cat['icon']) ?></div>
                <div class="category-card-name"><?= sanitize($cat['name']) ?></div>
                <div class="category-card-desc"><?= sanitize($cat['description']) ?></div>
                <div class="category-card-count">📁 <?= (int)$cat['sub_cnt'] ?> potkategorija</div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Features -->
<section class="section section-light">
    <div class="container">
        <h2 class="section-title">Zašto Učim Nemački?</h2>
        <p class="section-sub">Platforma dizajnirana da učenje nemačkog bude zabavno i efikasno</p>
        <div class="grid grid-4">
            <?php
            $features = [
                ['🎯', 'Interaktivni testovi', 'Sedam vrsta pitanja uključujući slike, audio i povlačenje'],
                ['🔊', 'Audio izgovor', 'Pravilni izgovor nemačkih reči uz audio snimke'],
                ['🏆', 'Dostignuća', 'Zarađujte značke i poene dok napredujete'],
                ['👨‍🏫', 'Živi nastavnici', 'Rezervišite čas sa iskusnim nastavnicima nemačkog'],
                ['📊', 'Praćenje napretka', 'Pratite svoja postignuća i statistike učenja'],
                ['🌍', 'Gramatika', 'Detaljni vodiči za nemačku gramatiku na srpskom'],
                ['📱', 'Mobilni prikaz', 'Učite na telefonu, tabletu ili računaru'],
                ['⚡', 'Brzo učenje', 'Efikasne metode za brže pamćenje vokabulara'],
            ];
            foreach ($features as $f): ?>
            <div class="card card-body text-center reveal">
                <div style="font-size:2.5rem;margin-bottom:.75rem"><?= $f[0] ?></div>
                <h4 style="margin-bottom:.4rem"><?= $f[1] ?></h4>
                <p class="text-muted" style="font-size:.9rem;margin:0"><?= $f[2] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Latest tests preview -->
<section class="section section-purple">
    <div class="container">
        <h2 class="section-title">Popularni testovi</h2>
        <p class="section-sub">Počnite sa ovim testovima i proverite svoje znanje</p>
        <?php
        $latestTests = $db->query(
            'SELECT t.*, s.name AS sub_name, c.name AS cat_name, c.color AS cat_color
             FROM tests t
             JOIN subcategories s ON t.subcategory_id=s.id
             JOIN categories c ON s.category_id=c.id
             ORDER BY t.id ASC LIMIT 6'
        )->fetch_all(MYSQLI_ASSOC);
        ?>
        <div class="grid grid-3">
            <?php foreach ($latestTests as $test): ?>
            <div class="card test-card reveal hover-glow">
                <div class="card-body">
                    <div class="d-flex align-center gap-2 mb-2">
                        <span class="badge badge-<?= sanitize($test['difficulty']) ?>"><?=
                            ['beginner'=>'Početni','intermediate'=>'Srednji','advanced'=>'Napredni'][$test['difficulty']] ?? $test['difficulty']
                        ?></span>
                        <span class="badge" style="background:<?= sanitize($test['cat_color']) ?>22;color:<?= sanitize($test['cat_color']) ?>"><?= sanitize($test['cat_name']) ?></span>
                    </div>
                    <div class="test-card-title"><?= sanitize($test['title']) ?></div>
                    <div class="test-card-meta">
                        <span>⏱️ <?= formatTime((int)$test['time_limit']) ?></span>
                        <span>✅ Prolaz: <?= (int)$test['passing_score'] ?>%</span>
                    </div>
                    <p class="text-muted" style="font-size:.88rem"><?= sanitize($test['description']) ?></p>
                    <a href="<?= SITE_URL ?>/pages/test-take.php?id=<?= (int)$test['id'] ?>" class="btn btn-primary btn-sm btn-block mt-2">▶ Počni test</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="<?= SITE_URL ?>/pages/tests.php" class="btn btn-primary btn-lg">Pogledaj sve testove →</a>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="section section-light">
    <div class="container">
        <h2 class="section-title">Šta kažu naši korisnici</h2>
        <div class="grid grid-3">
            <?php
            $testimonials = [
                ['Ana M.', 'Studenti', '⭐⭐⭐⭐⭐', 'Platforma mi je pomogla da položim B2 ispit! Testovi su zanimljivi i odlično pokrivaju gramatiku.'],
                ['Petar K.', 'Roditelj', '⭐⭐⭐⭐⭐', 'Moje dete (9 godina) obožava učenje nemačkog kroz igru. Slike i zvukovi čine sve lakšim.'],
                ['Jovana T.', 'Profesionalac', '⭐⭐⭐⭐⭐', 'Poslovni nemački mi je bio potreban – platforma ima odličan vokabular i konverzacijske testove.'],
            ];
            foreach ($testimonials as $t): ?>
            <div class="card card-body reveal">
                <div style="font-size:1.5rem;margin-bottom:.5rem"><?= $t[2] ?></div>
                <p style="font-style:italic;color:var(--gray-600);margin-bottom:1rem">"<?= $t[3] ?>"</p>
                <div class="d-flex align-center gap-2">
                    <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#6B21A8,#9333EA);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800">
                        <?= strtoupper(substr($t[0], 0, 1)) ?>
                    </div>
                    <div>
                        <div style="font-weight:700"><?= $t[0] ?></div>
                        <div style="font-size:.82rem;color:var(--text-muted)"><?= $t[1] ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA -->
<?php if (!isLoggedIn()): ?>
<section class="section" style="background:linear-gradient(135deg,#6B21A8,#9333EA);color:#fff;text-align:center;">
    <div class="container">
        <h2 style="color:#fff;margin-bottom:1rem">Pridružite se hiljadama učenika!</h2>
        <p style="opacity:.9;font-size:1.1rem;margin-bottom:2rem">Registracija je besplatna. Počnite da učite nemački danas.</p>
        <a href="<?= SITE_URL ?>/pages/register.php" class="btn btn-xl" style="background:#fff;color:#6B21A8">✨ Registruj se besplatno</a>
    </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
