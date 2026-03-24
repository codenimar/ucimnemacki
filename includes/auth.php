<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// ── Login ─────────────────────────────────────────────────────────────────────
function loginUser(string $username, string $password): array {
    $db   = getDB();
    $stmt = $db->prepare('SELECT id, username, email, password_hash, role, avatar_url FROM users WHERE username = ? OR email = ? LIMIT 1');
    $stmt->bind_param('ss', $username, $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) return ['success' => false, 'message' => 'Korisnik nije pronađen.'];
    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Pogrešna lozinka.'];
    }

    // Update last_login
    $upd = $db->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
    $upd->bind_param('i', $user['id']);
    $upd->execute();
    $upd->close();

    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['username']   = $user['username'];
    $_SESSION['avatar_url'] = $user['avatar_url'];

    return ['success' => true, 'user' => $user];
}

// ── Register ──────────────────────────────────────────────────────────────────
function registerUser(string $username, string $email, string $password): array {
    if (strlen($username) < 3 || strlen($username) > 60) {
        return ['success' => false, 'message' => 'Korisničko ime mora imati 3-60 karaktera.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Neispravna e-mail adresa.'];
    }
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Lozinka mora imati najmanje 6 karaktera.'];
    }

    $db = getDB();

    $chk = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
    $chk->bind_param('ss', $username, $email);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        $chk->close();
        return ['success' => false, 'message' => 'Korisnik sa tim korisničkim imenom ili e-mailom već postoji.'];
    }
    $chk->close();

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $ins  = $db->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?,?,?,\'user\')');
    $ins->bind_param('sss', $username, $email, $hash);
    if (!$ins->execute()) {
        $ins->close();
        return ['success' => false, 'message' => 'Greška pri registraciji. Pokušajte ponovo.'];
    }
    $newId = $ins->insert_id;
    $ins->close();

    $_SESSION['user_id']   = $newId;
    $_SESSION['user_role'] = 'user';
    $_SESSION['username']  = $username;

    return ['success' => true, 'user_id' => $newId];
}

// ── Logout ────────────────────────────────────────────────────────────────────
function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ── Guards ────────────────────────────────────────────────────────────────────
function requireLogin(): void {
    if (!isLoggedIn()) redirect(SITE_URL . '/pages/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
}

function requireAdmin(): void {
    if (!isAdmin()) redirect(SITE_URL . '/index.php');
}

// ── Google OAuth (stub – requires google-api-php-client or JWT verify) ────────
function googleLogin(string $idToken): array {
    if (empty(GOOGLE_CLIENT_ID)) return ['success' => false, 'message' => 'Google prijava nije podešena.'];

    $url      = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);
    $response = @file_get_contents($url);
    if (!$response) return ['success' => false, 'message' => 'Google verifikacija nije uspela.'];

    $data = json_decode($response, true);
    if (($data['aud'] ?? '') !== GOOGLE_CLIENT_ID) {
        return ['success' => false, 'message' => 'Nevažeći Google token.'];
    }

    $googleId = $data['sub']         ?? '';
    $email    = $data['email']       ?? '';
    $name     = $data['name']        ?? 'Korisnik';
    $avatar   = $data['picture']     ?? null;

    if (!$googleId || !$email) return ['success' => false, 'message' => 'Nepotpuni podaci sa Google naloga.'];

    $db   = getDB();
    $stmt = $db->prepare('SELECT id, username, role, avatar_url FROM users WHERE google_id = ? OR email = ? LIMIT 1');
    $stmt->bind_param('ss', $googleId, $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user) {
        // Update google_id if missing
        $upd = $db->prepare('UPDATE users SET google_id = ?, last_login = NOW() WHERE id = ?');
        $upd->bind_param('si', $googleId, $user['id']);
        $upd->execute();
        $upd->close();
    } else {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', explode('@', $email)[0]);
        $username = substr($username, 0, 30) ?: 'user';
        // Ensure unique username
        $base = $username;
        $i    = 1;
        while (true) {
            $chk = $db->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $chk->bind_param('s', $username);
            $chk->execute();
            $exists = $chk->get_result()->num_rows > 0;
            $chk->close();
            if (!$exists) break;
            $username = $base . $i++;
        }
        $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
        $ins  = $db->prepare('INSERT INTO users (username, email, password_hash, google_id, avatar_url, role) VALUES (?,?,?,?,?,\'user\')');
        $ins->bind_param('sssss', $username, $email, $hash, $googleId, $avatar);
        $ins->execute();
        $user = ['id' => $ins->insert_id, 'username' => $username, 'role' => 'user', 'avatar_url' => $avatar];
        $ins->close();
    }

    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['username']   = $user['username'];
    $_SESSION['avatar_url'] = $user['avatar_url'] ?? $avatar;

    return ['success' => true, 'user' => $user];
}
