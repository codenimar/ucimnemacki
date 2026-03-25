<nav class="admin-sidebar" aria-label="Admin navigacija">
    <div class="admin-logo">
        <img src="<?= SITE_URL ?>/assets/images/parrot.svg" alt="" width="30" height="30">
        Admin Panel
    </div>
    <div class="admin-nav-section">
        <div class="admin-nav-label">Pregled</div>
        <a href="<?= SITE_URL ?>/admin/index.php"      class="admin-nav-link <?= basename($_SERVER['PHP_SELF'])==='index.php'?'active':'' ?>"><span class="nav-icon">📊</span> Dashboard</a>
    </div>
    <div class="admin-nav-section">
        <div class="admin-nav-label">Sadržaj</div>
        <a href="<?= SITE_URL ?>/admin/categories.php" class="admin-nav-link <?= basename($_SERVER['PHP_SELF'])==='categories.php'?'active':'' ?>"><span class="nav-icon">📂</span> Kategorije</a>
        <a href="<?= SITE_URL ?>/admin/tests.php"      class="admin-nav-link <?= basename($_SERVER['PHP_SELF'])==='tests.php'?'active':'' ?>"><span class="nav-icon">📝</span> Testovi</a>
        <a href="<?= SITE_URL ?>/admin/questions.php"  class="admin-nav-link <?= basename($_SERVER['PHP_SELF'])==='questions.php'?'active':'' ?>"><span class="nav-icon">❓</span> Pitanja</a>
        <a href="<?= SITE_URL ?>/admin/grammar.php"    class="admin-nav-link <?= basename($_SERVER['PHP_SELF'])==='grammar.php'?'active':'' ?>"><span class="nav-icon">✏️</span> Gramatika</a>
    </div>
    <div class="admin-nav-section">
        <div class="admin-nav-label">Resursi</div>
        <a href="<?= SITE_URL ?>/admin/teachers.php"   class="admin-nav-link <?= basename($_SERVER['PHP_SELF'])==='teachers.php'?'active':'' ?>"><span class="nav-icon">👨‍🏫</span> Nastavnici</a>
        <a href="<?= SITE_URL ?>/admin/proficiency.php" class="admin-nav-link <?= basename($_SERVER['PHP_SELF'])==='proficiency.php'?'active':'' ?>"><span class="nav-icon">🎓</span> Provera znanja</a>
    </div>
    <div class="admin-nav-section">
        <div class="admin-nav-label">Korisnici</div>
        <a href="<?= SITE_URL ?>/admin/users.php"      class="admin-nav-link <?= basename($_SERVER['PHP_SELF'])==='users.php'?'active':'' ?>"><span class="nav-icon">👥</span> Korisnici</a>
    </div>
    <div class="admin-nav-section">
        <div class="admin-nav-label">Sajt</div>
        <a href="<?= SITE_URL ?>/index.php" class="admin-nav-link" target="_blank"><span class="nav-icon">🌐</span> Pogledaj sajt</a>
        <a href="<?= SITE_URL ?>/api/auth.php?action=logout" class="admin-nav-link" style="color:rgba(255,100,100,.8)"><span class="nav-icon">🚪</span> Odjavi se</a>
    </div>
</nav>
