<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

$currentUser  = getCurrentUser();
$isLoggedIn   = isLoggedIn();
$isAdmin      = isAdmin();
$csrfToken    = generateToken();

$currentPage  = basename($_SERVER['PHP_SELF'] ?? '');
$pageDir      = basename(dirname($_SERVER['PHP_SELF'] ?? ''));

function navActive(string $page): string {
    global $currentPage;
    return $currentPage === $page ? 'active' : '';
}

$siteRoot = SITE_URL;
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Učim Nemački – Naučite nemački jezik na zabavan i interaktivan način">
    <meta name="theme-color" content="#6B21A8">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' | ' : '' ?>Učim Nemački</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $siteRoot ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= $siteRoot ?>/assets/css/animations.css">
    <?= isset($extraHead) ? $extraHead : '' ?>
</head>
<body class="<?= isset($bodyClass) ? sanitize($bodyClass) : '' ?>">

<!-- Skip to content -->
<a href="#main-content" class="skip-link">Preskoči na sadržaj</a>

<!-- Navigation -->
<nav class="navbar" id="navbar" role="navigation" aria-label="Glavna navigacija">
    <div class="nav-container">
        <!-- Logo -->
        <a href="<?= $siteRoot ?>/index.php" class="nav-logo" aria-label="Početna stranica">
            <span class="nav-logo-icon" aria-hidden="true">
                <img src="<?= $siteRoot ?>/assets/images/parrot.svg" alt="" width="38" height="38" class="parrot-logo">
            </span>
            <span class="nav-logo-text">Učim Nemački</span>
        </a>

        <!-- Desktop nav links -->
        <ul class="nav-links" role="list">
            <li><a href="<?= $siteRoot ?>/index.php" class="nav-link <?= navActive('index.php') ?>">Početna</a></li>
            <li><a href="<?= $siteRoot ?>/pages/tests.php" class="nav-link <?= navActive('tests.php') ?>">Testovi</a></li>
            <li><a href="<?= $siteRoot ?>/pages/vocabulary.php" class="nav-link <?= navActive('vocabulary.php') ?>">Vokabular</a></li>
            <li><a href="<?= $siteRoot ?>/pages/grammar.php" class="nav-link <?= navActive('grammar.php') ?>">Gramatika</a></li>
            <li><a href="<?= $siteRoot ?>/pages/proficiency.php" class="nav-link <?= navActive('proficiency.php') ?>">Provera znanja</a></li>
            <li><a href="<?= $siteRoot ?>/pages/teachers.php" class="nav-link <?= navActive('teachers.php') ?>">Nastavnici</a></li>
            <li><a href="<?= $siteRoot ?>/pages/leaderboard.php" class="nav-link <?= navActive('leaderboard.php') ?>">Ranglist</a></li>
        </ul>

        <!-- Auth area -->
        <div class="nav-auth">
            <?php if ($isLoggedIn && $currentUser): ?>
                <div class="user-dropdown" id="userDropdown">
                    <button class="user-avatar-btn" id="userAvatarBtn" aria-haspopup="true" aria-expanded="false">
                        <?php if (!empty($currentUser['avatar_url'])): ?>
                            <img src="<?= sanitize($currentUser['avatar_url']) ?>" alt="Avatar" class="user-avatar-img">
                        <?php else: ?>
                            <span class="user-avatar-placeholder"><?= strtoupper(substr($currentUser['username'], 0, 1)) ?></span>
                        <?php endif; ?>
                        <span class="username-display"><?= sanitize($currentUser['username']) ?></span>
                        <span class="dropdown-arrow">▾</span>
                    </button>
                    <div class="dropdown-menu" id="dropdownMenu" role="menu">
                        <a href="<?= $siteRoot ?>/pages/profile.php" class="dropdown-item" role="menuitem">
                            <span>👤</span> Moj profil
                        </a>
                        <a href="<?= $siteRoot ?>/pages/profile.php#progress" class="dropdown-item" role="menuitem">
                            <span>📊</span> Napredak
                        </a>
                        <?php if ($isAdmin): ?>
                        <div class="dropdown-divider"></div>
                        <a href="<?= $siteRoot ?>/admin/index.php" class="dropdown-item dropdown-admin" role="menuitem">
                            <span>⚙️</span> Admin panel
                        </a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="<?= $siteRoot ?>/api/auth.php?action=logout" class="dropdown-item dropdown-logout" role="menuitem">
                            <span>🚪</span> Odjavi se
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= $siteRoot ?>/pages/login.php" class="btn btn-outline btn-sm">Prijavi se</a>
                <a href="<?= $siteRoot ?>/pages/register.php" class="btn btn-primary btn-sm">Registruj se</a>
            <?php endif; ?>
        </div>

        <!-- Hamburger -->
        <button class="hamburger" id="hamburger" aria-label="Otvori meni" aria-expanded="false" aria-controls="mobileMenu">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </button>
    </div>

    <!-- Mobile menu -->
    <div class="mobile-menu" id="mobileMenu" aria-hidden="true">
        <ul class="mobile-nav-links" role="list">
            <li><a href="<?= $siteRoot ?>/index.php" class="mobile-nav-link">🏠 Početna</a></li>
            <li><a href="<?= $siteRoot ?>/pages/tests.php" class="mobile-nav-link">📝 Testovi</a></li>
            <li><a href="<?= $siteRoot ?>/pages/vocabulary.php" class="mobile-nav-link">📖 Vokabular</a></li>
            <li><a href="<?= $siteRoot ?>/pages/grammar.php" class="mobile-nav-link">✏️ Gramatika</a></li>
            <li><a href="<?= $siteRoot ?>/pages/proficiency.php" class="mobile-nav-link">🎓 Provera znanja</a></li>
            <li><a href="<?= $siteRoot ?>/pages/teachers.php" class="mobile-nav-link">👨‍🏫 Nastavnici</a></li>
            <li><a href="<?= $siteRoot ?>/pages/leaderboard.php" class="mobile-nav-link">🏆 Ranglist</a></li>
            <?php if ($isLoggedIn): ?>
            <li><a href="<?= $siteRoot ?>/pages/profile.php" class="mobile-nav-link">👤 Moj profil</a></li>
            <?php if ($isAdmin): ?>
            <li><a href="<?= $siteRoot ?>/admin/index.php" class="mobile-nav-link">⚙️ Admin panel</a></li>
            <?php endif; ?>
            <li><a href="<?= $siteRoot ?>/api/auth.php?action=logout" class="mobile-nav-link mobile-logout">🚪 Odjavi se</a></li>
            <?php else: ?>
            <li><a href="<?= $siteRoot ?>/pages/login.php" class="mobile-nav-link">🔑 Prijavi se</a></li>
            <li><a href="<?= $siteRoot ?>/pages/register.php" class="mobile-nav-link mobile-register">✨ Registruj se</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<!-- CSRF token meta (for JS) -->
<meta name="csrf-token" content="<?= $csrfToken ?>">

<!-- Parrot mascot floating bubble -->
<div class="parrot-bubble" id="parrotBubble" aria-live="polite" role="status">
    <div class="parrot-bubble-inner">
        <div class="parrot-mascot-small" aria-hidden="true">
            <img src="<?= $siteRoot ?>/assets/images/parrot.svg" alt="Papagaj maskota" width="48" height="48">
        </div>
        <div class="parrot-message" id="parrotMessage">Zdravo! Učimo zajedno! 🎉</div>
        <button class="parrot-close" id="parrotClose" aria-label="Zatvori poruku papagaja">×</button>
    </div>
</div>

<!-- Toast container -->
<div class="toast-container" id="toastContainer" aria-live="polite" aria-atomic="false"></div>

<main id="main-content">
