-- Učim Nemački - Complete Database Schema
-- MySQL 5.7+ / MariaDB 10.3+

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS ucimnemacki
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ucimnemacki;

-- ─────────────────────────────────────────
-- USERS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(60)  NOT NULL UNIQUE,
    email         VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL DEFAULT '',
    role          ENUM('admin','user') NOT NULL DEFAULT 'user',
    google_id     VARCHAR(100) DEFAULT NULL,
    avatar_url    VARCHAR(500) DEFAULT NULL,
    streak        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    total_points  INT UNSIGNED NOT NULL DEFAULT 0,
    last_login    DATETIME     DEFAULT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- CATEGORIES
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
    id          TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    description TEXT         DEFAULT NULL,
    icon        VARCHAR(10)  NOT NULL DEFAULT '📚',
    color       VARCHAR(20)  NOT NULL DEFAULT '#6B21A8',
    sort_order  TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- SUBCATEGORIES
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS subcategories (
    id          SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id TINYINT UNSIGNED NOT NULL,
    name        VARCHAR(100) NOT NULL,
    description TEXT         DEFAULT NULL,
    icon        VARCHAR(10)  NOT NULL DEFAULT '📝',
    sort_order  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- TESTS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS tests (
    id            SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subcategory_id SMALLINT UNSIGNED NOT NULL,
    title         VARCHAR(200) NOT NULL,
    description   TEXT         DEFAULT NULL,
    difficulty    ENUM('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner',
    time_limit    SMALLINT UNSIGNED NOT NULL DEFAULT 300 COMMENT 'seconds',
    passing_score TINYINT UNSIGNED NOT NULL DEFAULT 60  COMMENT 'percentage',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- QUESTIONS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS questions (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    test_id        SMALLINT UNSIGNED NOT NULL,
    type           TINYINT UNSIGNED NOT NULL DEFAULT 1
                   COMMENT '1=img+4choice,2=text+4choice,3=audio+choice,4=matching,5=fill-blank,6=drag-order,7=true-false,8=text+4img-choice',
    question_text  TEXT NOT NULL,
    correct_answer TEXT DEFAULT NULL COMMENT 'for types 5,6,7 or JSON array for matching',
    points         TINYINT UNSIGNED NOT NULL DEFAULT 10,
    sort_order     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    hint_text      TEXT DEFAULT NULL,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- QUESTION OPTIONS (for types 1-3, 7)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS question_options (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id INT UNSIGNED NOT NULL,
    option_text VARCHAR(500) NOT NULL,
    is_correct  TINYINT(1)   NOT NULL DEFAULT 0,
    sort_order  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- QUESTION MEDIA
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS question_media (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id     INT UNSIGNED NOT NULL,
    media_type      ENUM('image','audio') NOT NULL,
    file_path       VARCHAR(500) NOT NULL,
    display_context VARCHAR(50)  NOT NULL DEFAULT 'question'
                    COMMENT 'question|option|feedback',
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- USER PROGRESS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS user_progress (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    test_id      SMALLINT UNSIGNED NOT NULL,
    score        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    max_score    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    completed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    time_spent   SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'seconds',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- LIVE TEACHERS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS live_teachers (
    id             SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(120) NOT NULL,
    bio            TEXT         DEFAULT NULL,
    photo_url      VARCHAR(500) DEFAULT NULL,
    email          VARCHAR(120) DEFAULT NULL,
    phone          VARCHAR(30)  DEFAULT NULL,
    subjects       VARCHAR(300) DEFAULT NULL COMMENT 'comma-separated',
    languages      VARCHAR(200) DEFAULT NULL COMMENT 'comma-separated',
    hourly_rate    DECIMAL(8,2) DEFAULT NULL,
    available_days VARCHAR(200) DEFAULT NULL COMMENT 'comma-separated day names',
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- ACHIEVEMENTS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS achievements (
    id             SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(100) NOT NULL,
    description    TEXT         NOT NULL,
    icon           VARCHAR(10)  NOT NULL DEFAULT '🏆',
    criteria_type  VARCHAR(50)  NOT NULL COMMENT 'tests_completed|points_earned|streak_days|perfect_score',
    criteria_value INT UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- USER ACHIEVEMENTS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS user_achievements (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id        INT UNSIGNED NOT NULL,
    achievement_id SMALLINT UNSIGNED NOT NULL,
    earned_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_ach (user_id, achievement_id),
    FOREIGN KEY (user_id)        REFERENCES users(id)        ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- ADMIN LOGS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admin_logs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id    INT UNSIGNED NOT NULL,
    action      VARCHAR(100) NOT NULL,
    target_type VARCHAR(50)  DEFAULT NULL,
    target_id   INT UNSIGNED DEFAULT NULL,
    details     TEXT         DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- VOCABULARY
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS vocabulary (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    german_word         VARCHAR(200) NOT NULL,
    serbian_translation VARCHAR(200) NOT NULL,
    category            VARCHAR(100) DEFAULT NULL,
    audio_path          VARCHAR(500) DEFAULT NULL,
    image_path          VARCHAR(500) DEFAULT NULL,
    example_sentence    TEXT         DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- GRAMMAR LESSONS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS grammar_lessons (
    id         SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title      VARCHAR(200) NOT NULL,
    content    LONGTEXT     NOT NULL,
    difficulty ENUM('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner',
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════
-- SEED DATA
-- ═══════════════════════════════════════════

-- Admin user  (password = "nemacki")
INSERT INTO users (username, email, password_hash, role) VALUES
('adminuci', 'admin@ucimnemacki.rs', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Note: in production run  password_hash('nemacki', PASSWORD_BCRYPT)

-- Categories
INSERT INTO categories (id, name, description, icon, color, sort_order) VALUES
(1, 'Za decu',             'Učenje nemačkog za najmlađe kroz igru i slike',           '🧒', '#16A34A', 1),
(2, 'Za napredne učenike', 'Gramatika, vokabular i konverzacija za odrasle',           '🎓', '#2563EB', 2),
(3, 'Provera znanja',      'Testirajte svoje znanje nemačkog jezika na svim nivoima',  '✅', '#DC2626', 3);

-- Subcategories – kids
INSERT INTO subcategories (id, category_id, name, description, icon, sort_order) VALUES
(1,  1, 'Pozdrav',   'Kako se pozdravljamo na nemačkom',          '👋', 1),
(2,  1, 'Brojevi',   'Naučite brojeve od 1 do 20',                '🔢', 2),
(3,  1, 'Hrana',     'Nazivi hrane i pića na nemačkom',           '🍎', 3),
(4,  1, 'Životinje', 'Domaće i divlje životinje na nemačkom',     '🐶', 4),
(5,  1, 'Boje',      'Sve boje na nemačkom jeziku',               '🎨', 5);

-- Subcategories – advanced
INSERT INTO subcategories (id, category_id, name, description, icon, sort_order) VALUES
(6,  2, 'Gramatika',    'Padežni sistem, glagoli i sintaksa',         '📖', 1),
(7,  2, 'Vokabular',    'Proširite rečnik za svakodnevnu upotrebu',   '💬', 2),
(8,  2, 'Konverzacija', 'Razgovor i fraze iz svakodnevnog života',    '🗣️', 3),
(9,  2, 'Pismo',        'Pisanje formalnih i neformalnih pisama',     '✉️', 4),
(10, 2, 'Kultura',      'Nemačka kultura, tradicija i istorija',      '🏰', 5);

-- Subcategories – testing
INSERT INTO subcategories (id, category_id, name, description, icon, sort_order) VALUES
(11, 3, 'Osnove',        'Test osnovnog znanja nemačkog – A1/A2',     '🌱', 1),
(12, 3, 'Srednji nivo',  'Test znanja nemačkog na nivou B1/B2',       '🌿', 2),
(13, 3, 'Napredni nivo', 'Test naprednog znanja nemačkog – C1/C2',    '🌳', 3);

-- Sample tests
INSERT INTO tests (id, subcategory_id, title, description, difficulty, time_limit, passing_score) VALUES
(1, 1, 'Osnove pozdrava',         'Naučite kako se pozdraviti na nemačkom', 'beginner',     120, 60),
(2, 2, 'Brojevi 1-10',            'Test prepoznavanja brojeva od 1 do 10',   'beginner',     180, 60),
(3, 3, 'Voće i povrće',           'Imenujte voće i povrće na nemačkom',      'beginner',     240, 60),
(4, 4, 'Životinje na farmi',      'Domaće životinje na nemačkom jeziku',     'beginner',     240, 60),
(5, 6, 'Der/Die/Das – Član',      'Određeni i neodređeni član u nemačkom',   'intermediate', 300, 70),
(6, 7, 'Svakodnevni vokabular',   'Reči koje koristimo svaki dan',           'intermediate', 300, 70),
(7, 11,'Provera A1 znanja',       'Sveobuhvatni test nivoa A1',              'beginner',     600, 60),
(8, 12,'Provera B1 znanja',       'Sveobuhvatni test nivoa B1',              'intermediate', 600, 70),
(9, 13,'Napredna provera znanja', 'Test za napredne studente nemačkog',      'advanced',     900, 75);

-- Questions for test 1 (Pozdrav – type 2: text + 4 choices)
INSERT INTO questions (id, test_id, type, question_text, correct_answer, points, sort_order, hint_text) VALUES
(1, 1, 2, 'Šta znači "Guten Morgen"?',       NULL, 10, 1, 'Ovo je jutarnji pozdrav.'),
(2, 1, 2, 'Šta znači "Auf Wiedersehen"?',    NULL, 10, 2, 'Ovo koristimo kada se rastajemo.'),
(3, 1, 2, 'Kako se kaže "Hvala" na nemačkom?', NULL, 10, 3, 'Počinje slovom D.'),
(4, 1, 7, 'Tačno ili netačno: "Guten Abend" znači "Dobro jutro"', 'Netačno', 10, 4, 'Abend znači veče.'),
(5, 1, 5, 'Popunite: "Ich _____ Max." (Ja se zovem Maks)', 'heiße', 10, 5, 'Glagol "heißen" u 1. licu jednine.');

INSERT INTO question_options (question_id, option_text, is_correct, sort_order) VALUES
(1, 'Dobro jutro',   1, 1),
(1, 'Dobra večer',   0, 2),
(1, 'Laku noć',      0, 3),
(1, 'Dobar dan',     0, 4),
(2, 'Dobro jutro',   0, 1),
(2, 'Do viđenja',    1, 2),
(2, 'Hvala',         0, 3),
(2, 'Molim',         0, 4),
(3, 'Bitte',         0, 1),
(3, 'Danke',         1, 2),
(3, 'Ja',            0, 3),
(3, 'Nein',          0, 4),
(4, 'Tačno',         0, 1),
(4, 'Netačno',       1, 2);

-- Questions for test 2 (Brojevi – type 1: img+text, type 2)
INSERT INTO questions (id, test_id, type, question_text, correct_answer, points, sort_order, hint_text) VALUES
(6,  2, 2, 'Koliko je "drei"?',      NULL, 10, 1, 'Između dva i četiri.'),
(7,  2, 2, 'Kako se kaže "5" na nemačkom?', NULL, 10, 2, 'Zvuči kao "feunf".'),
(8,  2, 2, 'Koliko je "zehn"?',     NULL, 10, 3, 'Poslednji jednocifreni broj.'),
(9,  2, 7, 'Tačno ili netačno: "sieben" znači 6', 'Netačno', 10, 4, 'Sedam na nemačkom.'),
(10, 2, 5, 'Popunite: "_____ ist die Nummer?" – odgovor na srpskom: deset', 'zehn', 10, 5, NULL);

INSERT INTO question_options (question_id, option_text, is_correct, sort_order) VALUES
(6,  '2', 0, 1), (6,  '3', 1, 2), (6,  '4', 0, 3), (6,  '5', 0, 4),
(7,  'vier',  0, 1), (7,  'fünf',  1, 2), (7,  'sechs', 0, 3), (7,  'sieben',0, 4),
(8,  '7',  0, 1), (8,  '8',  0, 2), (8,  '9',  0, 3), (8,  '10', 1, 4),
(9,  'Tačno',   0, 1), (9,  'Netačno', 1, 2);

-- Questions for test 5 (Gramatika – der/die/das)
INSERT INTO questions (id, test_id, type, question_text, correct_answer, points, sort_order, hint_text) VALUES
(11, 5, 2, 'Koji je određeni član za "Hund" (pas)?',    NULL, 10, 1, 'Muški rod.'),
(12, 5, 2, 'Koji je određeni član za "Katze" (mačka)?', NULL, 10, 2, 'Ženski rod.'),
(13, 5, 2, 'Koji je određeni član za "Kind" (dete)?',   NULL, 10, 3, 'Srednji rod.'),
(14, 5, 5, 'Popunite: "___ Buch ist interessant." (knjiga – srednji rod)', 'Das', 10, 4, NULL),
(15, 5, 7, 'Tačno ili netačno: neodređeni član za muški rod je "eine"', 'Netačno', 10, 5, '"ein" je za muški i srednji rod.');

INSERT INTO question_options (question_id, option_text, is_correct, sort_order) VALUES
(11, 'der', 1, 1), (11, 'die', 0, 2), (11, 'das', 0, 3), (11, 'ein', 0, 4),
(12, 'der', 0, 1), (12, 'die', 1, 2), (12, 'das', 0, 3), (12, 'ein', 0, 4),
(13, 'der', 0, 1), (13, 'die', 0, 2), (13, 'das', 1, 3), (13, 'ein', 0, 4),
(15, 'Tačno',   0, 1), (15, 'Netačno', 1, 2);

-- Live Teachers
INSERT INTO live_teachers (id, name, bio, photo_url, email, phone, subjects, languages, hourly_rate, available_days) VALUES
(1, 'Ana Müller',
   'Iskusna nastavnica nemačkog jezika sa 10 godina iskustva u predavanju strancima. Diplomirala germanistiku na Beogradskom univerzitetu i provela 3 godine u Berlinu.',
   NULL,
   'ana.muller@ucimnemacki.rs',
   '+381 64 123 4567',
   'Gramatika,Konverzacija,Priprema za ispit',
   'Srpski,Engleski,Nemački',
   2500.00,
   'Ponedeljak,Sreda,Petak'),
(2, 'Marko Schmidt',
   'Profesor nemačkog sa fokusom na poslovnu komunikaciju i pripremu za DSH/TestDaF ispite. Više od 500 zadovoljnih učenika.',
   NULL,
   'marko.schmidt@ucimnemacki.rs',
   '+381 63 987 6543',
   'Poslovna komunikacija,DSH priprema,TestDaF,Pismo',
   'Srpski,Nemački',
   3000.00,
   'Utorak,Četvrtak,Subota'),
(3, 'Jelena Weber',
   'Mlada i energična nastavnica koja se specijalizuje za podučavanje dece uzrasta 6-14 godina. Koristi inovativne metode kroz igru i kreativnost.',
   NULL,
   'jelena.weber@ucimnemacki.rs',
   '+381 65 555 1234',
   'Nemački za decu,Osnove,Vokabular',
   'Srpski,Engleski',
   2000.00,
   'Ponedeljak,Utorak,Sreda,Četvrtak,Petak');

-- Achievements
INSERT INTO achievements (id, name, description, icon, criteria_type, criteria_value) VALUES
(1,  'Početnik',          'Završite prvi test',                                    '🌟', 'tests_completed', 1),
(2,  'Vredan učenik',     'Završite 5 testova',                                    '📚', 'tests_completed', 5),
(3,  'Neumorni učenik',   'Završite 20 testova',                                   '🎓', 'tests_completed', 20),
(4,  'Stotičar',          'Sakupite 100 poena',                                    '💯', 'points_earned',   100),
(5,  'Hiljadaš',          'Sakupite 1000 poena',                                   '🏆', 'points_earned',   1000),
(6,  'Savršen rezultat',  'Završite test sa 100% tačnih odgovora',                 '⭐', 'perfect_score',   1),
(7,  'Tronedeljni streak','Učite 7 dana zaredom',                                  '🔥', 'streak_days',     7),
(8,  'Mesečni maraton',   'Učite 30 dana zaredom',                                 '🌟', 'streak_days',     30),
(9,  'Brzinac',           'Završite test za manje od polovine predviđenog vremena','⚡', 'speed_complete',  1),
(10, 'Savladao osnove',   'Položite sve testove iz kategorije Za decu',            '🧒', 'category_complete',1);

-- Vocabulary (20 words)
INSERT INTO vocabulary (german_word, serbian_translation, category, example_sentence) VALUES
('Hund',      'pas',         'Životinje', 'Der Hund spielt im Garten. – Pas se igra u bašti.'),
('Katze',     'mačka',       'Životinje', 'Die Katze schläft. – Mačka spava.'),
('Vogel',     'ptica',       'Životinje', 'Der Vogel singt schön. – Ptica lepo peva.'),
('Apfel',     'jabuka',      'Hrana',     'Ich esse einen Apfel. – Jedem jabuku.'),
('Brot',      'hleb',        'Hrana',     'Das Brot ist frisch. – Hleb je svež.'),
('Wasser',    'voda',        'Hrana',     'Ich trinke Wasser. – Pijem vodu.'),
('Rot',       'crvena',      'Boje',      'Das Auto ist rot. – Auto je crveno.'),
('Blau',      'plava',       'Boje',      'Der Himmel ist blau. – Nebo je plavo.'),
('Grün',      'zelena',      'Boje',      'Das Gras ist grün. – Trava je zelena.'),
('Haus',      'kuća',        'Svakodnevni život', 'Das Haus ist groß. – Kuća je velika.'),
('Schule',    'škola',       'Obrazovanje', 'Ich gehe in die Schule. – Idem u školu.'),
('Buch',      'knjiga',      'Obrazovanje', 'Das Buch ist interessant. – Knjiga je zanimljiva.'),
('Mutter',    'majka',       'Porodica',  'Meine Mutter kocht gut. – Moja majka dobro kuva.'),
('Vater',     'otac',        'Porodica',  'Mein Vater arbeitet. – Moj otac radi.'),
('Kind',      'dete',        'Porodica',  'Das Kind lacht. – Dete se smeje.'),
('Stadt',     'grad',        'Mesto',     'Die Stadt ist schön. – Grad je lep.'),
('Straße',    'ulica',       'Mesto',     'Die Straße ist lang. – Ulica je duga.'),
('Auto',      'auto/kola',   'Prevoz',    'Das Auto fährt schnell. – Auto vozi brzo.'),
('Zug',       'voz',         'Prevoz',    'Der Zug kommt um 8 Uhr. – Voz dolazi u 8 sati.'),
('Geld',      'novac',       'Svakodnevni život', 'Ich habe kein Geld. – Nemam novca.');

-- Grammar lessons (5 lessons)
INSERT INTO grammar_lessons (id, title, content, difficulty, sort_order) VALUES
(1, 'Određeni i neodređeni član (Der/Die/Das)',
'<h3>Određeni član (Bestimmter Artikel)</h3>
<p>U nemačkom jeziku svaka imenica ima rod: muški, ženski ili srednji.</p>
<table class="grammar-table">
<tr><th>Rod</th><th>Određeni član</th><th>Primer</th><th>Srpski</th></tr>
<tr><td>Muški (Maskulinum)</td><td><strong>der</strong></td><td>der Hund</td><td>pas</td></tr>
<tr><td>Ženski (Femininum)</td><td><strong>die</strong></td><td>die Katze</td><td>mačka</td></tr>
<tr><td>Srednji (Neutrum)</td><td><strong>das</strong></td><td>das Kind</td><td>dete</td></tr>
<tr><td>Množina (Plural)</td><td><strong>die</strong></td><td>die Hunde</td><td>psi</td></tr>
</table>
<h3>Neodređeni član (Unbestimmter Artikel)</h3>
<table class="grammar-table">
<tr><th>Rod</th><th>Neodređeni član</th><th>Primer</th></tr>
<tr><td>Muški</td><td><strong>ein</strong></td><td>ein Hund</td></tr>
<tr><td>Ženski</td><td><strong>eine</strong></td><td>eine Katze</td></tr>
<tr><td>Srednji</td><td><strong>ein</strong></td><td>ein Kind</td></tr>
</table>
<p class="tip">💡 <strong>Savet:</strong> Uvek učite novu imenicu zajedno sa njenim članom!</p>',
'beginner', 1),

(2, 'Glagol "sein" i "haben" (biti i imati)',
'<h3>Glagol "sein" – biti</h3>
<table class="grammar-table">
<tr><th>Lice</th><th>Nemački</th><th>Srpski</th></tr>
<tr><td>ich (ja)</td><td><strong>bin</strong></td><td>sam</td></tr>
<tr><td>du (ti)</td><td><strong>bist</strong></td><td>si</td></tr>
<tr><td>er/sie/es (on/ona/ono)</td><td><strong>ist</strong></td><td>je</td></tr>
<tr><td>wir (mi)</td><td><strong>sind</strong></td><td>smo</td></tr>
<tr><td>ihr (vi)</td><td><strong>seid</strong></td><td>ste</td></tr>
<tr><td>sie/Sie (oni/Vi)</td><td><strong>sind</strong></td><td>su/ste</td></tr>
</table>
<h3>Glagol "haben" – imati</h3>
<table class="grammar-table">
<tr><th>Lice</th><th>Nemački</th><th>Srpski</th></tr>
<tr><td>ich</td><td><strong>habe</strong></td><td>imam</td></tr>
<tr><td>du</td><td><strong>hast</strong></td><td>imaš</td></tr>
<tr><td>er/sie/es</td><td><strong>hat</strong></td><td>ima</td></tr>
<tr><td>wir</td><td><strong>haben</strong></td><td>imamo</td></tr>
<tr><td>ihr</td><td><strong>habt</strong></td><td>imate</td></tr>
<tr><td>sie/Sie</td><td><strong>haben</strong></td><td>imaju/imate</td></tr>
</table>',
'beginner', 2),

(3, 'Imenički padežni sistem (Kasus)',
'<h3>Četiri padeža u nemačkom</h3>
<p>Nemački jezik ima 4 padeža: Nominativ, Akkusativ, Dativ i Genitiv.</p>
<table class="grammar-table">
<tr><th>Padež</th><th>Pitanje</th><th>Srpski padež</th><th>Primer</th></tr>
<tr><td><strong>Nominativ</strong></td><td>Wer/Was? (Ko/Šta?)</td><td>Nominativ</td><td><em>Der Mann</em> schläft.</td></tr>
<tr><td><strong>Akkusativ</strong></td><td>Wen/Was? (Koga/Šta?)</td><td>Akuzativ</td><td>Ich sehe <em>den Mann</em>.</td></tr>
<tr><td><strong>Dativ</strong></td><td>Wem? (Kome?)</td><td>Dativ</td><td>Ich helfe <em>dem Mann</em>.</td></tr>
<tr><td><strong>Genitiv</strong></td><td>Wessen? (Čiji?)</td><td>Genitiv</td><td>Das Auto <em>des Mannes</em>.</td></tr>
</table>
<h3>Promena određenog člana</h3>
<table class="grammar-table">
<tr><th>Padež</th><th>der (m)</th><th>die (f)</th><th>das (n)</th><th>die (pl)</th></tr>
<tr><td>Nominativ</td><td>der</td><td>die</td><td>das</td><td>die</td></tr>
<tr><td>Akkusativ</td><td>den</td><td>die</td><td>das</td><td>die</td></tr>
<tr><td>Dativ</td><td>dem</td><td>der</td><td>dem</td><td>den</td></tr>
<tr><td>Genitiv</td><td>des</td><td>der</td><td>des</td><td>der</td></tr>
</table>',
'intermediate', 3),

(4, 'Perfekt – prošlo vreme',
'<h3>Perfekt (Prošlo vreme)</h3>
<p>Perfekt se najčešće koristi u govoru za prošle radnje. Gradi se od pomoćnog glagola (haben/sein) i Partizipa II.</p>
<h4>Formiranje Partizipa II</h4>
<ul>
<li>Pravilni glagoli: <strong>ge- + osnova + -t</strong><br>spielen → ge<strong>spiel</strong>t (igrao)</li>
<li>Nepravilni glagoli: moraju se učiti napamet<br>gehen → ge<strong>gang</strong>en (otišao)</li>
</ul>
<h4>Kada koristiti "haben" a kada "sein"?</h4>
<p><strong>sein</strong> + Partizip II: glagoli kretanja i promene stanja (gehen, kommen, fahren, werden...)</p>
<p><strong>haben</strong> + Partizip II: ostali glagoli</p>
<h4>Primeri</h4>
<table class="grammar-table">
<tr><th>Prezent</th><th>Perfekt</th><th>Prevod</th></tr>
<tr><td>Ich spiele.</td><td>Ich habe gespielt.</td><td>Igrao sam.</td></tr>
<tr><td>Er geht.</td><td>Er ist gegangen.</td><td>Otišao je.</td></tr>
<tr><td>Wir essen.</td><td>Wir haben gegessen.</td><td>Jeli smo.</td></tr>
</table>',
'intermediate', 4),

(5, 'Konjunktiv II – uslovno vreme',
'<h3>Konjunktiv II</h3>
<p>Konjunktiv II koristimo za:</p>
<ul>
<li>Hipotetičke situacije: <em>Wenn ich reich wäre...</em> (Kada bih bio bogat...)</li>
<li>Ljubazne molbe: <em>Könnten Sie mir helfen?</em> (Biste li mi pomogli?)</li>
<li>Savete: <em>Du solltest mehr schlafen.</em> (Trebalo bi da više spavaš.)</li>
</ul>
<h4>Najvažniji oblici</h4>
<table class="grammar-table">
<tr><th>Glagol</th><th>Konjunktiv II (ich)</th><th>Srpski</th></tr>
<tr><td>sein</td><td>wäre</td><td>bio bih</td></tr>
<tr><td>haben</td><td>hätte</td><td>imao bih</td></tr>
<tr><td>können</td><td>könnte</td><td>mogao bih</td></tr>
<tr><td>müssen</td><td>müsste</td><td>morao bih</td></tr>
<tr><td>werden</td><td>würde</td><td>bio bih/uradio bih</td></tr>
</table>
<p class="tip">💡 Najčešće se koristi konstrukcija: <strong>würde + Infinitiv</strong><br>
Ich würde gern reisen. – Voleo bih da putujem.</p>',
'advanced', 5);

SET FOREIGN_KEY_CHECKS = 1;
