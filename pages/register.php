<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) redirect(SITE_URL . '/pages/profile.php');

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyToken($_POST['csrf_token'] ?? '')) {
        $error = 'Nevažeći CSRF token.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm']  ?? '';

        if ($password !== $confirm) {
            $error = 'Lozinke se ne poklapaju.';
        } else {
            $result = registerUser($username, $email, $password);
            if ($result['success']) {
                redirect(SITE_URL . '/pages/profile.php');
            } else {
                $error = $result['message'];
            }
        }
    }
}

$pageTitle = 'Registracija';
$bodyClass = 'page-auth';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrapper">
    <div class="auth-card card animate-slide-up">
        <div class="card-body" style="padding:2.5rem">
            <div class="auth-header">
                <img src="<?= SITE_URL ?>/assets/images/parrot.svg" alt="" width="64" height="64" style="margin:0 auto 1rem">
                <h1>Registrujte se</h1>
                <p>Besplatno i brzo – pod minut!</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error"><?= sanitize($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="" novalidate>
                <input type="hidden" name="csrf_token" value="<?= generateToken() ?>">
                <div class="form-group">
                    <label class="form-label" for="username">Korisničko ime</label>
                    <input type="text" id="username" name="username" class="form-control"
                           value="<?= sanitize($_POST['username'] ?? '') ?>"
                           placeholder="npr. petar123" required minlength="3" maxlength="60" autocomplete="username">
                    <div class="form-text">Minimum 3 karaktera, samo slova, brojevi i _</div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="email">E-mail adresa</label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="<?= sanitize($_POST['email'] ?? '') ?>"
                           placeholder="vas@email.com" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Lozinka</label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Minimum 6 karaktera" required minlength="6" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm">Potvrdite lozinku</label>
                    <input type="password" id="confirm" name="confirm" class="form-control"
                           placeholder="Ponovite lozinku" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg"> Registruj se</button>
            </form>

            <?php if (!empty(GOOGLE_CLIENT_ID)): ?>
            <div class="divider"><span>ili</span></div>
            <button class="btn btn-outline btn-block" id="googleLoginBtn">
                <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                Registruj se putem Google-a
            </button>
            <?php endif; ?>

            <p class="text-center mt-3" style="font-size:.9rem">
                Već imate nalog? <a href="<?= SITE_URL ?>/pages/login.php">Prijavite se</a>
            </p>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
