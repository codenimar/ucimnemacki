<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db   = getDB();
$user = getCurrentUser();
$userId = (int)$user['id'];

// Progress stats
$stmt = $db->prepare('SELECT COUNT(*) AS tests_done, COALESCE(SUM(score),0) AS total_score FROM user_progress WHERE user_id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$progStats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $db->prepare('SELECT COUNT(*) AS perfect FROM user_progress WHERE user_id = ? AND score = max_score AND max_score > 0');
$stmt->bind_param('i', $userId);
$stmt->execute();
$perfectCount = (int)$stmt->get_result()->fetch_assoc()['perfect'];
$stmt->close();

// Recent activity
$stmt = $db->prepare(
    'SELECT up.*, t.title, t.time_limit FROM user_progress up
     JOIN tests t ON up.test_id = t.id
     WHERE up.user_id = ? ORDER BY up.completed_at DESC LIMIT 8'
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$recentActivity = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Achievements
$achievements = getAchievements($userId);
$allAch = $db->query('SELECT * FROM achievements ORDER BY id')->fetch_all(MYSQLI_ASSOC);
$earnedIds = array_column($achievements, 'achievement_id');

$pageTitle = 'Moj profil';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="section">
    <div class="container">
        <!-- Profile header -->
        <div class="card mb-4">
            <div class="card-body" style="display:flex;gap:1.5rem;align-items:center;flex-wrap:wrap;padding:2rem">
                <div style="width:90px;height:90px;border-radius:50%;background:linear-gradient(135deg,#6B21A8,#9333EA);display:flex;align-items:center;justify-content:center;font-size:2.5rem;font-weight:800;color:#fff;flex-shrink:0">
                    <?php if (!empty($user['avatar_url'])): ?>
                    <img src="<?= sanitize($user['avatar_url']) ?>" alt="Avatar" style="width:90px;height:90px;border-radius:50%;object-fit:cover">
                    <?php else: ?>
                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div style="flex:1">
                    <h2 style="margin-bottom:.25rem"><?= sanitize($user['username']) ?></h2>
                    <p class="text-muted" style="margin:0"><?= sanitize($user['email']) ?></p>
                    <div class="d-flex gap-2 mt-2" style="flex-wrap:wrap">
                        <span class="badge badge-purple">⭐ <?= (int)$user['total_points'] ?> poena</span>
                        <span class="badge" style="background:var(--yellow-light);color:#92400E">🔥 <?= (int)$user['streak'] ?> dana streak</span>
                        <?php if ($user['role'] === 'admin'): ?>
                        <span class="badge badge-blue">⚙️ Administrator</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats grid -->
        <div class="grid grid-4 mb-4" id="progress">
            <div class="stat-card">
                <div class="stat-card-num" data-count="<?= (int)$progStats['tests_done'] ?>"><?= (int)$progStats['tests_done'] ?></div>
                <div class="stat-card-label">Testova urađeno</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-num" data-count="<?= (int)$progStats['total_score'] ?>"><?= (int)$progStats['total_score'] ?></div>
                <div class="stat-card-label">Ukupno poena</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-num" data-count="<?= $perfectCount ?>"><?= $perfectCount ?></div>
                <div class="stat-card-label">Savršenih rezultata</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-num" data-count="<?= count($achievements) ?>"><?= count($achievements) ?></div>
                <div class="stat-card-label">Dostignuća</div>
            </div>
        </div>

        <div class="grid grid-2" style="gap:1.5rem;align-items:start">
            <!-- Recent activity -->
            <div class="card">
                <div class="card-header">📊 Nedavna aktivnost</div>
                <div class="card-body" style="padding:.5rem 0">
                    <?php if (empty($recentActivity)): ?>
                    <div class="text-center" style="padding:2rem;color:var(--text-muted)">
                        Još nema završenih testova.<br>
                        <a href="<?= SITE_URL ?>/pages/tests.php" class="btn btn-primary btn-sm mt-2">Počnite učiti!</a>
                    </div>
                    <?php else: ?>
                    <?php foreach ($recentActivity as $act):
                        $pct   = $act['max_score'] > 0 ? (int)round($act['score']/$act['max_score']*100) : 0;
                        $grade = getGrade($pct);
                    ?>
                    <div style="display:flex;align-items:center;gap:1rem;padding:.75rem 1.25rem;border-bottom:1px solid var(--border)">
                        <div style="font-size:1.3rem"><?= $grade['emoji'] ?></div>
                        <div style="flex:1;min-width:0">
                            <div style="font-weight:700;font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= sanitize($act['title']) ?></div>
                            <div style="font-size:.8rem;color:var(--text-muted)"><?= date('d.m.Y', strtotime($act['completed_at'])) ?> · <?= formatTime((int)$act['time_spent']) ?></div>
                        </div>
                        <div style="text-align:right">
                            <div style="font-weight:800;color:<?= $grade['color'] ?>"><?= $pct ?>%</div>
                            <div style="font-size:.78rem;color:var(--text-muted)"><?= $act['score'] ?>/<?= $act['max_score'] ?>p</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Achievements -->
            <div class="card">
                <div class="card-header">🏅 Dostignuća</div>
                <div class="card-body">
                    <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:.75rem">
                        <?php foreach ($allAch as $ach):
                            $earned = in_array($ach['id'], $earnedIds);
                        ?>
                        <div class="achievement-badge <?= $earned ? 'earned' : 'locked' ?>" title="<?= sanitize($ach['description']) ?>">
                            <div class="achievement-icon"><?= sanitize($ach['icon']) ?></div>
                            <div class="achievement-name"><?= sanitize($ach['name']) ?></div>
                            <?php if ($earned): ?>
                            <div style="font-size:.7rem;color:var(--green);font-weight:700">✓ Zarađeno</div>
                            <?php else: ?>
                            <div class="achievement-desc"><?= sanitize($ach['description']) ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
