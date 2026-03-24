<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle  = 'Vokabular';
$db         = getDB();
$category   = isset($_GET['cat'])  ? trim($_GET['cat']) : '';
$search     = isset($_GET['q'])    ? trim($_GET['q'])   : '';
$mode       = isset($_GET['mode']) ? $_GET['mode']      : 'list'; // list | flashcard

$cats = $db->query('SELECT DISTINCT category FROM vocabulary WHERE category IS NOT NULL ORDER BY category')->fetch_all(MYSQLI_ASSOC);
$cats = array_column($cats, 'category');

// Build query
$where  = [];
$params = [];
$types  = '';
if ($category !== '') { $where[] = 'category = ?'; $params[] = $category; $types .= 's'; }
if ($search   !== '') { $like = '%'.$search.'%'; $where[] = '(german_word LIKE ? OR serbian_translation LIKE ?)'; $params[] = $like; $params[] = $like; $types .= 'ss'; }
$sql  = 'SELECT * FROM vocabulary' . ($where ? ' WHERE '.implode(' AND ',$where) : '') . ' ORDER BY category, german_word';
$stmt = $db->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$words = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once __DIR__ . '/../includes/header.php';
?>
<section class="section">
    <div class="container">
        <div class="d-flex align-center gap-2 mb-4" style="flex-wrap:wrap">
            <h1 style="margin:0;flex:1">📖 Vokabular</h1>
            <a href="?mode=<?= $mode === 'list' ? 'flashcard' : 'list' ?>&cat=<?= urlencode($category) ?>&q=<?= urlencode($search) ?>" class="btn btn-outline btn-sm">
                <?= $mode === 'list' ? '🃏 Kartica mod' : '📋 Lista mod' ?>
            </a>
        </div>

        <!-- Filters -->
        <form method="get" class="d-flex gap-2 mb-4" style="flex-wrap:wrap;align-items:center">
            <input type="hidden" name="mode" value="<?= sanitize($mode) ?>">
            <div class="input-group" style="flex:1;min-width:220px">
                <input type="text" name="q" value="<?= sanitize($search) ?>" class="form-control" placeholder="Pretraži reči...">
                <button type="submit" class="btn btn-primary">🔍</button>
            </div>
            <select name="cat" class="form-select" style="width:auto" onchange="this.form.submit()">
                <option value="">Sve kategorije</option>
                <?php foreach ($cats as $c): ?>
                <option value="<?= sanitize($c) ?>" <?= $category === $c ? 'selected' : '' ?>><?= sanitize($c) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($category || $search): ?>
            <a href="?mode=<?= sanitize($mode) ?>" class="btn btn-ghost btn-sm">✕ Resetuj</a>
            <?php endif; ?>
        </form>

        <p class="text-muted mb-3">Pronađeno: <strong><?= count($words) ?></strong> reči</p>

        <?php if ($mode === 'flashcard' && !empty($words)): ?>
        <!-- Flashcard mode -->
        <div class="text-center mb-4">
            <p class="text-muted">Kliknite na karticu da vidite prevod</p>
        </div>
        <div class="flashcard-wrap" id="flashcardWrap">
            <div class="flashcard" id="mainFlashcard" tabindex="0" role="button" aria-label="Kartica – kliknite za prevod">
                <div class="flashcard-front">
                    <div>
                        <div class="flashcard-word" id="fcGerman"></div>
                        <div class="flashcard-hint" id="fcCategory"></div>
                    </div>
                </div>
                <div class="flashcard-back">
                    <div>
                        <div class="flashcard-word" id="fcSerbian"></div>
                        <div class="flashcard-hint" id="fcExample"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 mt-4" style="justify-content:center;align-items:center">
            <button id="fcPrev" class="btn btn-outline btn-sm">← Prethodna</button>
            <span id="fcNum" style="font-weight:700;color:var(--text-muted)">1 / <?= count($words) ?></span>
            <button id="fcNext" class="btn btn-primary btn-sm">Sledeća →</button>
        </div>
        <script>
        const fcWords = <?= json_encode($words, JSON_UNESCAPED_UNICODE) ?>;
        let fcIdx = 0;
        function renderFC() {
            const w = fcWords[fcIdx];
            document.getElementById('fcGerman').textContent   = w.german_word;
            document.getElementById('fcCategory').textContent = w.category ?? '';
            document.getElementById('fcSerbian').textContent  = w.serbian_translation;
            document.getElementById('fcExample').textContent  = w.example_sentence ?? '';
            document.getElementById('fcNum').textContent      = (fcIdx+1) + ' / ' + fcWords.length;
            document.getElementById('mainFlashcard').classList.remove('flipped');
        }
        document.getElementById('fcNext').addEventListener('click', () => { fcIdx=(fcIdx+1)%fcWords.length; renderFC(); });
        document.getElementById('fcPrev').addEventListener('click', () => { fcIdx=(fcIdx-1+fcWords.length)%fcWords.length; renderFC(); });
        renderFC();
        </script>

        <?php else: ?>
        <!-- List mode -->
        <?php
        $byCategory = [];
        foreach ($words as $w) { $byCategory[$w['category'] ?? 'Ostalo'][] = $w; }
        ?>
        <?php foreach ($byCategory as $cat => $wList): ?>
        <div class="mb-5">
            <h3 style="margin-bottom:1rem;padding-bottom:.5rem;border-bottom:2px solid var(--border)">
                📂 <?= sanitize($cat) ?> <span class="badge badge-purple" style="font-size:.8rem;vertical-align:middle"><?= count($wList) ?></span>
            </h3>
            <div class="grid grid-auto">
                <?php foreach ($wList as $w): ?>
                <div class="vocab-card">
                    <div class="d-flex align-center" style="justify-content:space-between">
                        <div class="vocab-german"><?= sanitize($w['german_word']) ?></div>
                        <?php if ($w['audio_path']): ?>
                        <button class="audio-btn" onclick="(new Audio('/uploads/<?= sanitize($w['audio_path']) ?>')).play()" title="Poslušaj izgovor" aria-label="Poslušaj izgovor reči <?= sanitize($w['german_word']) ?>">🔊</button>
                        <?php endif; ?>
                    </div>
                    <div class="vocab-serbian"><?= sanitize($w['serbian_translation']) ?></div>
                    <?php if ($w['example_sentence']): ?>
                    <div class="vocab-example"><?= sanitize($w['example_sentence']) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($words)): ?>
        <div class="card card-body text-center" style="padding:3rem">
            <div style="font-size:3rem;margin-bottom:1rem">📭</div>
            <h3>Nema reči za prikaz</h3>
            <p class="text-muted">Pokušajte drugačiji upit ili resetujte filter.</p>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
