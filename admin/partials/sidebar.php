<nav class="admin-sidebar" aria-label="Admin navigacija">
    <div class="admin-logo">
        <img src="<?= SITE_URL ?>/assets/images/parrot.svg" alt="" width="30" height="30">
        Admin Panel
    </div>
    <div class="admin-nav-section">
        <div class="admin-nav-label">Pregled</div>
        <a href="<?= SITE_URL ?>/admin/index.php"      class="admin-nav-link <?= basename($_SERVER['PHP_SELF'])==='index.php'?'active':'' ?>">Dashboard</a>
    </div>
    <div class="admin-nav-section">
        <div class="admin-nav-label">Sadržaj</div>
        <a href="<?= SITE_URL ?>/admin/categories.php" class="admin-nav-link <?= basename($_SERVER['PHP_SELF'])==='categories.php'?'active':'' ?>">Kategorije</a>
        <a href="<?= SITE_URL ?>/admin/tests.php"      class="admin-nav-link <?= basename($_SERVER['PHP_SELF'])==='tests.php'?'active':'' ?>">Testovi</a>
        <a href="<?= SITE_URL ?>/admin/questions.php"  class="admin-nav-link <?= basename($_SERVER['PHP_SELF'])==='questions.php'?'active':'' ?>">Pitanja</a>
        <a href="<?= SITE_URL ?>/admin/grammar.php"    class="admin-nav-link <?= basename($_SERVER['PHP_SELF'])==='grammar.php'?'active':'' ?>">Gramatika</a>
    </div>
    <div class="admin-nav-section">
        <div class="admin-nav-label">Resursi</div>
        <a href="<?= SITE_URL ?>/admin/teachers.php"   class="admin-nav-link <?= basename($_SERVER['PHP_SELF'])==='teachers.php'?'active':'' ?>">Nastavnici</a>
        <a href="<?= SITE_URL ?>/admin/proficiency.php" class="admin-nav-link <?= basename($_SERVER['PHP_SELF'])==='proficiency.php'?'active':'' ?>">Provera znanja</a>
    </div>
    <div class="admin-nav-section">
        <div class="admin-nav-label">Korisnici</div>
        <a href="<?= SITE_URL ?>/admin/users.php"      class="admin-nav-link <?= basename($_SERVER['PHP_SELF'])==='users.php'?'active':'' ?>">Korisnici</a>
    </div>
    <div class="admin-nav-section">
        <div class="admin-nav-label">Sajt</div>
        <a href="<?= SITE_URL ?>/index.php" class="admin-nav-link" target="_blank">Pogledaj sajt</a>
        <a href="<?= SITE_URL ?>/api/auth.php?action=logout" class="admin-nav-link" style="color:rgba(255,100,100,.8)">Odjavi se</a>
    </div>
</nav>
