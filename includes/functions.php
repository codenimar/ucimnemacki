<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// ── Input sanitization ────────────────────────────────────────────────────────
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ── Session helpers ───────────────────────────────────────────────────────────
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    $db   = getDB();
    $id   = (int)$_SESSION['user_id'];
    $stmt = $db->prepare('SELECT id, username, email, role, avatar_url, streak, total_points FROM users WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

// ── Redirect ──────────────────────────────────────────────────────────────────
function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

// ── CSRF ──────────────────────────────────────────────────────────────────────
function generateToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyToken(string $token): bool {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ── File upload ───────────────────────────────────────────────────────────────
function uploadFile(array $file, string $type = 'image'): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;

    $maxSize = ($type === 'audio') ? 10 * 1024 * 1024 : 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) return null;

    $allowedImage = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowedAudio = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/webm'];
    $allowed      = ($type === 'audio') ? $allowedAudio : $allowedImage;

    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowed, true)) return null;

    $ext      = ($type === 'audio') ? 'mp3' : pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('', true) . '.' . strtolower($ext);
    $subDir   = ($type === 'audio') ? 'audio' : 'images';
    $destDir  = UPLOAD_DIR . $subDir . '/';
    $destPath = $destDir . $filename;

    if (!is_dir($destDir)) mkdir($destDir, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], $destPath)) return null;

    return $subDir . '/' . $filename;
}

// ── Time formatting ───────────────────────────────────────────────────────────
function formatTime(int $seconds): string {
    if ($seconds < 60) return $seconds . 's';
    $m = (int)floor($seconds / 60);
    $s = $seconds % 60;
    return $s > 0 ? "{$m}m {$s}s" : "{$m}m";
}

// ── Points & achievements ─────────────────────────────────────────────────────
function awardPoints(int $userId, int $points): void {
    if ($points <= 0) return;
    $db   = getDB();
    $stmt = $db->prepare('UPDATE users SET total_points = total_points + ? WHERE id = ?');
    $stmt->bind_param('ii', $points, $userId);
    $stmt->execute();
    $stmt->close();
}

function getAchievements(int $userId): array {
    $db   = getDB();
    $stmt = $db->prepare(
        'SELECT a.*, ua.earned_at FROM achievements a
         INNER JOIN user_achievements ua ON ua.achievement_id = a.id
         WHERE ua.user_id = ?
         ORDER BY ua.earned_at DESC'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function checkAchievements(int $userId): array {
    $db      = getDB();
    $earned  = [];

    // Fetch user stats
    $stmt = $db->prepare('SELECT total_points, streak FROM users WHERE id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $db->prepare('SELECT COUNT(*) AS cnt FROM user_progress WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $testsCompleted = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    $stmt = $db->prepare(
        'SELECT COUNT(*) AS cnt FROM user_progress WHERE user_id = ? AND score = max_score AND max_score > 0'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $perfectScores = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    // Fetch all achievements not yet earned
    $stmt = $db->prepare(
        'SELECT a.* FROM achievements a
         WHERE a.id NOT IN (SELECT achievement_id FROM user_achievements WHERE user_id = ?)'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $pending = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($pending as $ach) {
        $met = false;
        switch ($ach['criteria_type']) {
            case 'tests_completed': $met = $testsCompleted  >= $ach['criteria_value']; break;
            case 'points_earned':  $met = ($user['total_points'] ?? 0) >= $ach['criteria_value']; break;
            case 'streak_days':    $met = ($user['streak'] ?? 0)       >= $ach['criteria_value']; break;
            case 'perfect_score':  $met = $perfectScores               >= $ach['criteria_value']; break;
        }
        if ($met) {
            $ins = $db->prepare('INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?,?)');
            $ins->bind_param('ii', $userId, $ach['id']);
            $ins->execute();
            $ins->close();
            $earned[] = $ach;
        }
    }
    return $earned;
}

// ── Admin logging ─────────────────────────────────────────────────────────────
function logAdminAction(int $adminId, string $action, string $targetType = '', int $targetId = 0, string $details = ''): void {
    $db   = getDB();
    $stmt = $db->prepare(
        'INSERT INTO admin_logs (admin_id, action, target_type, target_id, details) VALUES (?,?,?,?,?)'
    );
    $stmt->bind_param('issis', $adminId, $action, $targetType, $targetId, $details);
    $stmt->execute();
    $stmt->close();
}

// ── Grade helper ──────────────────────────────────────────────────────────────
function getGrade(int $pct): array {
    return match (true) {
        $pct >= 95 => ['label' => 'Odličan',   'color' => '#16A34A', 'emoji' => ''],
        $pct >= 80 => ['label' => 'Vrlo dobar', 'color' => '#2563EB', 'emoji' => ''],
        $pct >= 65 => ['label' => 'Dobar',      'color' => '#CA8A04', 'emoji' => ''],
        $pct >= 50 => ['label' => 'Dovoljan',   'color' => '#EA580C', 'emoji' => ''],
        default    => ['label' => 'Nedovoljan', 'color' => '#DC2626', 'emoji' => ''],
    };
}
