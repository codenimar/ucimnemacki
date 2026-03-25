<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pageTitle = 'Moj Vokabular';
$db        = getDB();
$userId    = (int)$_SESSION['user_id'];
$msg       = '';
$error     = '';

// ── DELETE ────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!verifyToken($_POST['csrf_token'] ?? '')) {
        $error = 'Nevažeći CSRF token.';
    } else {
        $delId = (int)$_POST['delete_id'];
        if ($delId) {
            $stmt = $db->prepare('DELETE FROM vocabulary WHERE id = ? AND user_id = ?');
            $stmt->bind_param('ii', $delId, $userId);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: ' . SITE_URL . '/pages/vocabulary.php?msg=Re%C4%8D+obrisana');
        exit;
    }
}

// ── LOAD WORD FOR EDIT ────────────────────────────────────────────────────────
$editWord = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editId = (int)$_GET['id'];
    if ($editId) {
        $stmt = $db->prepare('SELECT * FROM vocabulary WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $editId, $userId);
        $stmt->execute();
        $editWord = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

// ── CREATE / UPDATE ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
    if (!verifyToken($_POST['csrf_token'] ?? '')) {
        $error = 'Nevažeći CSRF token.';
    } else {
        $id      = (int)($_POST['id'] ?? 0);
        $german  = trim($_POST['german_word'] ?? '');
        $serbian = trim($_POST['serbian_translation'] ?? '');
        $cat     = trim($_POST['category'] ?? '');
        $example = trim($_POST['example_sentence'] ?? '');

        if ($german === '' || $serbian === '') {
            $error = 'Nemački i srpski prevod su obavezni.';
        } else {
            if ($id > 0) {
                $stmt = $db->prepare(
                    'UPDATE vocabulary SET german_word=?, serbian_translation=?, category=?, example_sentence=?
                     WHERE id=? AND user_id=?'
                );
                $stmt->bind_param('ssssii', $german, $serbian, $cat, $example, $id, $userId);
                $stmt->execute();
                $stmt->close();
                $msg = 'Reč ažurirana.';
            } else {
                $stmt = $db->prepare(
                    'INSERT INTO vocabulary (user_id, german_word, serbian_translation, category, example_sentence)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->bind_param('issss', $userId, $german, $serbian, $cat, $example);
                $stmt->execute();
                $stmt->close();
                $msg = 'Reč dodata.';
            }
            $editWord = null;
        }
    }
}

if (isset($_GET['msg'])) $msg = sanitize($_GET['msg']);

// ── LOAD USER WORDS ───────────────────────────────────────────────────────────
$mode = (isset($_GET['mode']) && $_GET['mode'] === 'list') ? 'list' : 'flashcard';

$stmt = $db->prepare('SELECT * FROM vocabulary WHERE user_id = ? ORDER BY category, german_word');
$stmt->bind_param('i', $userId);
$stmt->execute();
$words = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$userCats = array_values(array_unique(array_filter(array_column($words, 'category'))));

require_once __DIR__ . '/../includes/header.php';
?>
<section class="section">
    <div class="container">
        <div class="d-flex align-center gap-2 mb-4" style="flex-wrap:wrap">
            <h1 style="margin:0;flex:1">📖 Moj Vokabular</h1>
            <?php if (!empty($words)): ?>
            <a href="?mode=<?= $mode === 'list' ? 'flashcard' : 'list' ?>" class="btn btn-outline btn-sm">
                <?= $mode === 'list' ? '🃏 Kartica mod' : '📋 Lista mod' ?>
            </a>
            <?php endif; ?>
        </div>

        <?php if ($msg):   ?><div class="alert alert-success"><?= sanitize($msg) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= sanitize($error) ?></div><?php endif; ?>

        <!-- Add / Edit Form -->
        <div class="card mb-5">
            <div class="card-header"><?= $editWord ? '✏️ Izmeni reč' : '➕ Dodaj novu reč' ?></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateToken() ?>">
                    <input type="hidden" name="id" value="<?= $editWord ? (int)$editWord['id'] : 0 ?>">
                    <div class="grid grid-2" style="gap:1rem">
                        <div class="form-group">
                            <label class="form-label">Nemački</label>
                            <input type="text" name="german_word" class="form-control" required
                                   value="<?= sanitize($editWord['german_word'] ?? '') ?>"
                                   placeholder="npr. der Hund">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Srpski prevod</label>
                            <input type="text" name="serbian_translation" class="form-control" required
                                   value="<?= sanitize($editWord['serbian_translation'] ?? '') ?>"
                                   placeholder="npr. pas">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kategorija</label>
                            <input type="text" name="category" class="form-control"
                                   value="<?= sanitize($editWord['category'] ?? '') ?>"
                                   list="catList" placeholder="npr. Životinje">
                            <datalist id="catList">
                                <?php foreach ($userCats as $uc): ?>
                                <option value="<?= sanitize($uc) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Primer rečenice (opciono)</label>
                            <input type="text" name="example_sentence" class="form-control"
                                   value="<?= sanitize($editWord['example_sentence'] ?? '') ?>"
                                   placeholder="npr. Der Hund ist groß.">
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <?= $editWord ? 'Ažuriraj' : 'Dodaj reč' ?>
                        </button>
                        <?php if ($editWord): ?>
                        <a href="<?= SITE_URL ?>/pages/vocabulary.php" class="btn btn-outline btn-sm">Otkaži</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($words)): ?>
        <div class="card card-body text-center" style="padding:3rem">
            <div style="font-size:3rem;margin-bottom:1rem">📭</div>
            <h3>Vaš vokabular je prazan</h3>
            <p class="text-muted">Dodajte prvu reč koristeći formu iznad.</p>
        </div>

        <?php elseif ($mode === 'flashcard'): ?>
        <div class="text-center mb-4">
            <p class="text-muted">Kliknite na karticu da vidite prevod</p>
        </div>
        <div class="flashcard-wrap" id="flashcardWrap">
            <div class="flashcard" id="mainFlashcard" tabindex="0" role="button"
                 aria-label="Kartica – kliknite za prevod">
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
            document.getElementById('fcCategory').textContent = w.category || '';
            document.getElementById('fcSerbian').textContent  = w.serbian_translation;
            document.getElementById('fcExample').textContent  = w.example_sentence || '';
            document.getElementById('fcNum').textContent      = (fcIdx + 1) + ' / ' + fcWords.length;
            document.getElementById('mainFlashcard').classList.remove('flipped');
        }
        document.getElementById('mainFlashcard').addEventListener('click', () => {
            document.getElementById('mainFlashcard').classList.toggle('flipped');
        });
        document.getElementById('mainFlashcard').addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                document.getElementById('mainFlashcard').classList.toggle('flipped');
            }
        });
        document.getElementById('fcNext').addEventListener('click', () => {
            fcIdx = (fcIdx + 1) % fcWords.length; renderFC();
        });
        document.getElementById('fcPrev').addEventListener('click', () => {
            fcIdx = (fcIdx - 1 + fcWords.length) % fcWords.length; renderFC();
        });
        renderFC();
        </script>

        <?php else: ?>
        <?php
        $byCategory = [];
        foreach ($words as $w) { $byCategory[$w['category'] ?: 'Ostalo'][] = $w; }
        ?>
        <?php foreach ($byCategory as $cat => $wList): ?>
        <div class="mb-5">
            <h3 style="margin-bottom:1rem;padding-bottom:.5rem;border-bottom:2px solid var(--border)">
                📂 <?= sanitize($cat) ?>
                <span class="badge badge-purple" style="font-size:.8rem;vertical-align:middle"><?= count($wList) ?></span>
            </h3>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Nemački</th><th>Srpski</th><th>Primer</th><th>Akcije</th></tr></thead>
                    <tbody>
                    <?php foreach ($wList as $w): ?>
                    <tr>
                        <td><strong><?= sanitize($w['german_word']) ?></strong></td>
                        <td><?= sanitize($w['serbian_translation']) ?></td>
                        <td style="font-size:.85rem;color:var(--text-muted)"><?= sanitize($w['example_sentence'] ?? '') ?></td>
                        <td class="table-actions">
                            <a href="?action=edit&id=<?= (int)$w['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
                            <form method="POST" style="display:inline"
                                  onsubmit="return confirm('Obrisati reč \'<?= sanitize($w['german_word']) ?>\'?')">
                                <input type="hidden" name="csrf_token" value="<?= generateToken() ?>">
                                <input type="hidden" name="delete_id"  value="<?= (int)$w['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <p class="text-muted mt-3">Ukupno reči: <strong><?= count($words) ?></strong></p>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
