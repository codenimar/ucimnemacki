<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Postani nastavnik';
$db        = getDB();
$msg       = '';
$error     = '';

// Load teacher categories for the select box
$teacherCats = $db->query(
    'SELECT * FROM teacher_categories ORDER BY sort_order, name'
)->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyToken($_POST['csrf_token'] ?? '')) {
        $error = 'Nevažeći CSRF token. Pokušajte ponovo.';
    } else {
        $name            = trim($_POST['name'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $phone           = trim($_POST['phone'] ?? '');
        $contact_viber   = trim($_POST['contact_viber'] ?? '');
        $contact_whatsapp= trim($_POST['contact_whatsapp'] ?? '');
        $catId           = (int)($_POST['teacher_category_id'] ?? 0) ?: null;
        $experience      = trim($_POST['experience'] ?? '');
        $certificate     = trim($_POST['certificate'] ?? '');
        $teaching_method = implode(',', array_map('trim', (array)($_POST['teaching_method'] ?? [])));
        $lesson_duration = trim($_POST['lesson_duration'] ?? '');
        $hourly_rate     = floatval($_POST['hourly_rate'] ?? 0);
        $bio             = trim($_POST['bio'] ?? '');

        if ($name === '' || $email === '' || $phone === '') {
            $error = 'Ime, e-mail i telefon su obavezni.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Unesite ispravnu e-mail adresu.';
        } else {
            $photo = '';
            if (!empty($_FILES['photo_file']['name'])) {
                $uploaded = uploadFile($_FILES['photo_file'], 'image');
                if ($uploaded) {
                    $photo = UPLOAD_URL . $uploaded;
                }
            }

            $stmt = $db->prepare(
                'INSERT INTO live_teachers
                 (teacher_category_id, name, bio, email, phone, contact_viber, contact_whatsapp,
                  experience, certificate, teaching_method, lesson_duration, hourly_rate, photo_url, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending")'
            );
            $stmt->bind_param(
                'issssssssssds',
                $catId, $name, $bio, $email, $phone, $contact_viber, $contact_whatsapp,
                $experience, $certificate, $teaching_method, $lesson_duration, $hourly_rate, $photo
            );
            $stmt->execute();
            $stmt->close();
            $msg = 'Vaša prijava je uspešno poslata! Admin će pregledati vašu prijavu i kontaktirati vas.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<section class="hero" style="padding:3rem 1.25rem 2.5rem">
    <div class="container hero-content">
        <h1 style="color:#fff">Postani nastavnik</h1>
        <p class="hero-sub">Popunite prijavu i poučavajte nemački jezik na našoj platformi</p>
    </div>
</section>

<section class="section">
    <div class="container-sm">
        <?php if ($msg): ?>
        <div class="alert alert-success mb-4"><?= sanitize($msg) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-error mb-4"><?= sanitize($error) ?></div>
        <?php endif; ?>

        <?php if (!$msg): ?>
        <div class="card">
            <div class="card-header">Prijava za nastavnika</div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generateToken() ?>">

                    <!-- Osnovni podaci -->
                    <h3 style="margin-bottom:1rem;margin-top:.5rem">Osnovni podaci</h3>
                    <div class="grid grid-2" style="gap:1rem">
                        <div class="form-group">
                            <label class="form-label">Ime i prezime <span style="color:red">*</span></label>
                            <input type="text" name="name" class="form-control" required
                                   value="<?= sanitize($_POST['name'] ?? '') ?>"
                                   placeholder="npr. Marija Petrović">
                        </div>
                        <div class="form-group">
                            <label class="form-label">E-mail <span style="color:red">*</span></label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?= sanitize($_POST['email'] ?? '') ?>"
                                   placeholder="npr. marija@example.com">
                        </div>
                        <div class="form-group" style="grid-column:1/-1">
                            <label class="form-label">Kratka biografija</label>
                            <textarea name="bio" class="form-control" rows="3"
                                      placeholder="Recite nešto o sebi..."><?= sanitize($_POST['bio'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Kategorija -->
                    <h3 style="margin-top:1.5rem;margin-bottom:1rem">Kategorija nastave</h3>
                    <div class="form-group">
                        <label class="form-label">Kojoj grupi učenika predajete?</label>
                        <select name="teacher_category_id" class="form-select">
                            <option value="0">— Odaberite kategoriju —</option>
                            <?php foreach ($teacherCats as $tc): ?>
                            <option value="<?= (int)$tc['id'] ?>"
                                <?= isset($_POST['teacher_category_id']) && (int)$_POST['teacher_category_id'] === (int)$tc['id'] ? 'selected' : '' ?>>
                                <?= sanitize($tc['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Iskustvo -->
                    <h3 style="margin-top:1.5rem;margin-bottom:1rem">Iskustvo</h3>
                    <div class="grid grid-2" style="gap:1rem">
                        <div class="form-group" style="grid-column:1/-1">
                            <label class="form-label">Opis iskustva u predavanju nemačkog</label>
                            <textarea name="experience" class="form-control" rows="3"
                                      placeholder="npr. 5 godina predavanja u školi, online nastava..."><?= sanitize($_POST['experience'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group" style="grid-column:1/-1">
                            <label class="form-label">Sertifikat (ukoliko postoji)</label>
                            <input type="text" name="certificate" class="form-control"
                                   value="<?= sanitize($_POST['certificate'] ?? '') ?>"
                                   placeholder="npr. Goethe-Zertifikat B2, DaF sertifikat...">
                        </div>
                    </div>

                    <!-- Nacin odrzavanja casova -->
                    <h3 style="margin-top:1.5rem;margin-bottom:1rem">Način održavanja časova</h3>
                    <div class="form-group">
                        <label class="form-label">Odaberite platforme koje koristite</label>
                        <div style="display:flex;flex-wrap:wrap;gap:.75rem;margin-top:.5rem">
                            <?php
                            $methods = ['zoom' => 'Zoom', 'google_meet' => 'Google Meet', 'skype' => 'Skype',
                                        'microsoft_teams' => 'Microsoft Teams', 'uzivo' => 'Uživo', 'ostalo' => 'Ostalo'];
                            $selectedMethods = (array)($_POST['teaching_method'] ?? []);
                            foreach ($methods as $val => $label): ?>
                            <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer">
                                <input type="checkbox" name="teaching_method[]" value="<?= $val ?>"
                                    <?= in_array($val, $selectedMethods, true) ? 'checked' : '' ?>>
                                <?= $label ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Kontakt -->
                    <h3 style="margin-top:1.5rem;margin-bottom:1rem">Kontakt</h3>
                    <div class="grid grid-2" style="gap:1rem">
                        <div class="form-group">
                            <label class="form-label">Telefon <span style="color:red">*</span></label>
                            <input type="text" name="phone" class="form-control" required
                                   value="<?= sanitize($_POST['phone'] ?? '') ?>"
                                   placeholder="+381 60 ...">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Viber (opciono)</label>
                            <input type="text" name="contact_viber" class="form-control"
                                   value="<?= sanitize($_POST['contact_viber'] ?? '') ?>"
                                   placeholder="+381 60 ...">
                        </div>
                        <div class="form-group">
                            <label class="form-label">WhatsApp (opciono)</label>
                            <input type="text" name="contact_whatsapp" class="form-control"
                                   value="<?= sanitize($_POST['contact_whatsapp'] ?? '') ?>"
                                   placeholder="+381 60 ...">
                        </div>
                    </div>

                    <!-- Trajanje i cena -->
                    <h3 style="margin-top:1.5rem;margin-bottom:1rem">Trajanje i cena časa</h3>
                    <div class="grid grid-2" style="gap:1rem">
                        <div class="form-group">
                            <label class="form-label">Trajanje časa</label>
                            <input type="text" name="lesson_duration" class="form-control"
                                   value="<?= sanitize($_POST['lesson_duration'] ?? '') ?>"
                                   placeholder="npr. 45 min, 60 min">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cena po času (RSD)</label>
                            <input type="number" name="hourly_rate" class="form-control" min="0" step="50"
                                   value="<?= (int)($_POST['hourly_rate'] ?? 0) ?: '' ?>"
                                   placeholder="npr. 1500">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fotografija (opciono)</label>
                            <input type="file" name="photo_file" class="form-control" accept="image/*">
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">Pošalji prijavu</button>
                        <a href="<?= SITE_URL ?>/pages/teachers.php" class="btn btn-ghost btn-sm" style="margin-left:.5rem">Nazad na nastavnike</a>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="text-center mt-4">
            <a href="<?= SITE_URL ?>/pages/teachers.php" class="btn btn-primary">Pogledaj nastavnike</a>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
