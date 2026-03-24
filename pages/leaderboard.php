<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Ranglist';
$db        = getDB();
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 20;
$offset    = ($page - 1) * $perPage;

$total = (int)$db->query('SELECT COUNT(*) AS c FROM users WHERE role="user"')->fetch_assoc()['c'];
$pages = max(1, (int)ceil($total / $perPage));

$stmt = $db->prepare(
    'SELECT u.id, u.username, u.avatar_url, u.total_points, u.streak,
            (SELECT COUNT(*) FROM user_progress WHERE user_id=u.id) AS tests_done
     FROM users u WHERE u.role="user"
     ORDER BY u.total_points DESC, tests_done DESC
     LIMIT ? OFFSET ?'
);
$stmt->bind_param('ii', $perPage, $offset);
$stmt->execute();
$leaders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$currentUserId = isLoggedIn() ? (int)$_SESSION['user_id'] : 0;

// Current user rank
$userRank = null;
if ($currentUserId) {
    $rStmt = $db->prepare(
        'SELECT COUNT(*)+1 AS rank FROM users WHERE total_points > (SELECT total_points FROM users WHERE id=?) AND role="user"'
    );
    $rStmt->bind_param('i', $currentUserId);
    $rStmt->execute();
    $userRank = (int)$rStmt->get_result()->fetch_assoc()['rank'];
    $rStmt->close();
}

require_once __DIR__ . '/../includes/header.php';
?>
<section class="section">
    <div class="container-sm">
        <h1 style="text-align:center;margin-bottom:.5rem">🏆 Ranglist</h1>
        <p class="text-muted" style="text-align:center;margin-bottom:2rem">Takmičite se sa ostalim učenicima nemačkog</p>

        <?php if ($userRank && $currentUserId): ?>
        <div class="alert alert-info mb-4" style="text-align:center">
            Vaše trenutno mesto: <strong>#<?= $userRank ?></strong> od <?= $total ?> korisnika
        </div>
        <?php endif; ?>

        <?php if (empty($leaders)): ?>
        <div class="card card-body text-center" style="padding:3rem">
            <p class="text-muted">Nema podataka za prikaz.</p>
        </div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:.6rem">
            <?php foreach ($leaders as $i => $leader):
                $rank      = $offset + $i + 1;
                $rankEmoji = match($rank) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => '' };
                $isMe      = $leader['id'] == $currentUserId;
            ?>
            <div class="leaderboard-item <?= $isMe ? 'hover-glow' : '' ?>" style="<?= $isMe ? 'border-color:var(--purple-400);background:var(--purple-50);' : '' ?>">
                <div class="leaderboard-rank <?= $rank <= 3 ? 'rank-'.$rank : '' ?>">
                    <?= $rankEmoji ?: "#$rank" ?>
                </div>
                <div class="leaderboard-avatar">
                    <?php if ($leader['avatar_url']): ?>
                    <img src="<?= sanitize($leader['avatar_url']) ?>" alt="" style="width:46px;height:46px;border-radius:50%;object-fit:cover">
                    <?php else: ?>
                    <?= strtoupper(substr($leader['username'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div class="leaderboard-name">
                    <?= sanitize($leader['username']) ?>
                    <?php if ($isMe): ?> <span class="badge badge-purple" style="font-size:.7rem">Vi</span><?php endif; ?>
                    <div style="font-size:.8rem;color:var(--text-muted);font-weight:400">
                        <?= (int)$leader['tests_done'] ?> testova · 🔥 <?= (int)$leader['streak'] ?> dana
                    </div>
                </div>
                <div class="leaderboard-points"><?= number_format((int)$leader['total_points']) ?> <span style="font-size:.8rem;font-weight:400;color:var(--text-muted)">poena</span></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
        <div class="d-flex gap-2 mt-4" style="justify-content:center;flex-wrap:wrap">
            <?php for ($p = 1; $p <= $pages; $p++): ?>
            <a href="?page=<?= $p ?>" class="btn <?= $p == $page ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= $p ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
