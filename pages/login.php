<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Already logged in
if (isLoggedIn()) redirect(SITE_URL . '/pages/profile.php');

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyToken($_POST['csrf_token'] ?? '')) {
        $error = 'Nevažeći CSRF token. Osvežite stranicu.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $result   = loginUser($username, $password);
        if ($result['success']) {
            $redirect = $_GET['redirect'] ?? (SITE_URL . '/pages/profile.php');
            redirect(filter_var($redirect, FILTER_VALIDATE_URL) ? $redirect : SITE_URL . '/pages/profile.php');
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'Prijava';
$bodyClass = 'page-auth';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrapper">
    <div class="auth-card card animate-slide-up">
        <div class="card-body" style="padding:2.5rem">
            <div class="auth-header">
                <img src="<?= SITE_URL ?>/assets/images/parrot.svg" alt="" width="64" height="64" style="margin:0 auto 1rem">
                <h1>Dobrodošli nazad!</h1>
                <p>Prijavite se na vaš nalog</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error"><?= sanitize($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="" novalidate>
                <input type="hidden" name="csrf_token" value="<?= generateToken() ?>">
                <div class="form-group">
                    <label class="form-label" for="username">Korisničko ime ili e-mail</label>
                    <input type="text" id="username" name="username" class="form-control"
                           value="<?= sanitize($_POST['username'] ?? '') ?>"
                           placeholder="korisnicko_ime" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Lozinka</label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="••••••••" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg"> Prijavi se</button>
            </form>

            <?php if (!empty(GOOGLE_CLIENT_ID)): ?>
            <div class="divider"><span>ili</span></div>
            <button class="btn btn-outline btn-block" id="googleLoginBtn">
                <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                Prijavi se putem Google-a
            </button>
            <?php endif; ?>

            <p class="text-center mt-3" style="font-size:.9rem">
                Nemate nalog? <a href="<?= SITE_URL ?>/pages/register.php">Registrujte se</a>
            </p>
        </div>
    </div>
</div>
<?php
if (!empty(GOOGLE_CLIENT_ID)):
    $gClientId = json_encode(GOOGLE_CLIENT_ID);
    $gSiteUrl  = json_encode(SITE_URL);
    $extraScripts = <<<HTML
<script src="https://accounts.google.com/gsi/client" async></script>
<script>
(function() {
    var btn = document.getElementById('googleLoginBtn');
    if (!btn) return;
    btn.addEventListener('click', function() {
        if (typeof google === 'undefined' || !google.accounts) {
            showToast('Google biblioteka se još uvek učitava. Pokušajte ponovo za trenutak.', 'warning');
            return;
        }
        google.accounts.id.initialize({
            client_id: {$gClientId},
            callback: function(response) {
                var form = new FormData();
                form.append('id_token', response.credential);
                fetch({$gSiteUrl} + '/api/auth.php?action=google', { method: 'POST', body: form })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success) {
                            var params   = new URLSearchParams(window.location.search);
                            var redirect = params.get('redirect') || '';
                            var siteOrigin = new URL({$gSiteUrl}).origin;
                            var dest;
                            try {
                                dest = redirect && new URL(redirect).origin === siteOrigin
                                    ? redirect
                                    : {$gSiteUrl} + '/pages/profile.php';
                            } catch (e) {
                                dest = {$gSiteUrl} + '/pages/profile.php';
                            }
                            window.location.href = dest;
                        } else {
                            showToast(data.message || 'Google prijava nije uspela.', 'error');
                        }
                    })
                    .catch(function() { showToast('Mrežna greška. Proverite internet vezu.', 'error'); });
            }
        });
        google.accounts.id.prompt();
    });
})();
</script>
HTML;
endif;
require_once __DIR__ . '/../includes/footer.php'; ?>
