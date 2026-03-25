-- Učim Nemački – Migration v2
-- Run after schema.sql to add new features

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────────────────────────────
-- Add user_id to vocabulary (user-owned words, nullable for legacy data)
-- ─────────────────────────────────────────────────────────────────────────────
ALTER TABLE vocabulary
    ADD COLUMN user_id INT UNSIGNED DEFAULT NULL AFTER id;

ALTER TABLE vocabulary
    ADD CONSTRAINT fk_vocab_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE vocabulary
    ADD INDEX idx_vocab_user (user_id);

-- ─────────────────────────────────────────────────────────────────────────────
-- Teacher categories
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS teacher_categories (
    id         SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE live_teachers
    ADD COLUMN teacher_category_id SMALLINT UNSIGNED DEFAULT NULL AFTER id;

ALTER TABLE live_teachers
    ADD CONSTRAINT fk_teacher_cat
    FOREIGN KEY (teacher_category_id) REFERENCES teacher_categories(id) ON DELETE SET NULL;

-- ─────────────────────────────────────────────────────────────────────────────
-- Proficiency test questions
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS proficiency_questions (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    level          ENUM('A1','A2','B1','B2','C1','C2') NOT NULL,
    question_text  TEXT NOT NULL,
    option_a       VARCHAR(500) NOT NULL,
    option_b       VARCHAR(500) NOT NULL,
    option_c       VARCHAR(500) NOT NULL,
    option_d       VARCHAR(500) NOT NULL,
    correct_answer ENUM('a','b','c','d') NOT NULL,
    sort_order     SMALLINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- Proficiency test results
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS proficiency_results (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id        INT UNSIGNED DEFAULT NULL,
    session_id     VARCHAR(64)  DEFAULT NULL,
    level_achieved ENUM('A1','A2','B1','B2','C1','C2') DEFAULT NULL,
    details        TEXT         DEFAULT NULL COMMENT 'JSON: per-level scores',
    completed_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ─────────────────────────────────────────────────────────────────────────────
-- SEED: 10 proficiency questions per level (A1–C2, 60 total)
-- Questions in Serbian, testing German language knowledge
-- ─────────────────────────────────────────────────────────────────────────────

-- A1: Vocabulary, numbers, greetings, articles, basic verbs
INSERT INTO proficiency_questions (level, question_text, option_a, option_b, option_c, option_d, correct_answer, sort_order) VALUES
('A1', 'Koji je tačan prevod reči "Hund"?', 'mačka', 'pas', 'ptica', 'riba', 'b', 1),
('A1', 'Koji član ide uz reč "Tisch" (sto)?', 'die', 'das', 'der', 'den', 'c', 2),
('A1', 'Kako se na nemačkom kaže "Dobar dan"?', 'Guten Morgen', 'Gute Nacht', 'Guten Tag', 'Auf Wiedersehen', 'c', 3),
('A1', 'Koji broj dolazi posle "neun" (9)?', 'acht', 'sieben', 'elf', 'zehn', 'd', 4),
('A1', 'Šta znači "Ich heiße..."?', 'Ja sam star...', 'Ja se zovem...', 'Ja imam...', 'Ja živim...', 'b', 5),
('A1', 'Koji je tačan prevod reči "Buch"?', 'stolica', 'prozor', 'knjiga', 'olovka', 'c', 6),
('A1', 'Kako se na nemačkom kaže "hvala"?', 'Bitte', 'Danke', 'Ja', 'Nein', 'b', 7),
('A1', 'Koji član ide uz reč "Frau" (žena)?', 'der', 'das', 'die', 'dem', 'c', 8),
('A1', 'Šta znači glagol "trinken"?', 'jesti', 'spavati', 'piti', 'hodati', 'c', 9),
('A1', 'Kako se na nemačkom kaže "Izvinite"?', 'Bitte schön', 'Entschuldigung', 'Danke schön', 'Kein Problem', 'b', 10);

-- A2: Past tense, common phrases, adjective agreement
INSERT INTO proficiency_questions (level, question_text, option_a, option_b, option_c, option_d, correct_answer, sort_order) VALUES
('A2', 'Popunite: "Gestern ___ ich ins Kino gegangen."', 'habe', 'bin', 'hatte', 'war', 'b', 1),
('A2', 'Šta znači "Ich habe Hunger"?', 'Žedan/žedna sam', 'Gladan/gladna sam', 'Umoran/umorna sam', 'Hladno mi je', 'b', 2),
('A2', 'Koji je tačan oblik: "Das ist ___ Mann."?', 'ein', 'eine', 'einen', 'einem', 'a', 3),
('A2', 'Šta znači "Wie alt bist du?"?', 'Gde živiš?', 'Koliko imaš godina?', 'Kako se zoveš?', 'Šta radiš?', 'b', 4),
('A2', 'Koji glagol se koristi za Perfekt: "Ich ___ geschlafen."?', 'bin', 'haben', 'habe', 'hatte', 'c', 5),
('A2', 'Šta znači "Es regnet"?', 'Sija sunce.', 'Pada sneg.', 'Pada kiša.', 'Duva vetar.', 'c', 6),
('A2', 'Koji je tačan plural reči "das Kind"?', 'die Kinds', 'die Kinder', 'die Kindes', 'die Kinde', 'b', 7),
('A2', 'Šta znači "Ich wohne in Berlin"?', 'Radim u Berlinu.', 'Živim u Berlinu.', 'Putujem u Berlin.', 'Volim Berlin.', 'b', 8),
('A2', 'Popunite: "Sie ___ sehr schön."', 'sind', 'ist', 'hat', 'sein', 'b', 9),
('A2', 'Kako se kaže "U sredu" na nemačkom?', 'Am Montag', 'Am Dienstag', 'Am Mittwoch', 'Am Donnerstag', 'c', 10);

-- B1: Dative/accusative, modal verbs, separable verbs, subordinate clauses
INSERT INTO proficiency_questions (level, question_text, option_a, option_b, option_c, option_d, correct_answer, sort_order) VALUES
('B1', 'Koji padež zahteva predlog "mit"?', 'Nominativ', 'Genitiv', 'Dativ', 'Akuzativ', 'c', 1),
('B1', 'Popunite: "Ich ___ heute arbeiten." (obaveza)', 'kann', 'will', 'muss', 'darf', 'c', 2),
('B1', 'Koji je tačan oblik: "Er gibt ___ Frau ein Geschenk."?', 'die', 'der', 'das', 'den', 'b', 3),
('B1', 'Šta znači "anrufen"?', 'isključiti', 'telefonirati nekome', 'uključiti', 'doći', 'b', 4),
('B1', 'Popunite: "Er sagt, dass er ___ ist."', 'krank', 'kranke', 'kranken', 'kranker', 'a', 5),
('B1', 'Koji je tačan redosled u zavisnoj rečenici: "Ich weiß, dass er..."?', 'kommt morgen', 'morgen kommt', 'morgen komme', 'kommt er morgen', 'b', 6),
('B1', 'Šta znači "sich vorstellen"?', 'zamisliti nešto', 'predstaviti se', 'isprazniti', 'nasmejati se', 'b', 7),
('B1', 'Koji predlog zahteva akuzativ: "Er läuft ___ den Park."?', 'in', 'durch', 'mit', 'bei', 'b', 8),
('B1', 'Popunite: "Ich ___ gern Fußball spielen." (želja)', 'muss', 'soll', 'möchte', 'darf', 'c', 9),
('B1', 'Šta znači "aufstehen"?', 'sesti', 'ustati', 'leći', 'trčati', 'b', 10);

-- B2: Konjunktiv II, passive voice, two-way prepositions, complex sentences
INSERT INTO proficiency_questions (level, question_text, option_a, option_b, option_c, option_d, correct_answer, sort_order) VALUES
('B2', 'Koji oblik je Konjunktiv II glagola "haben"?', 'hätte', 'hatte', 'hat', 'habe', 'a', 1),
('B2', 'Prevedite u pasiv: "Man baut das Haus."', 'Das Haus wird gebaut.', 'Das Haus baut man.', 'Das Haus wurde gebaut.', 'Man wird das Haus bauen.', 'a', 2),
('B2', 'Popunite: "Wenn ich mehr Zeit ___, würde ich reisen."', 'habe', 'hätte', 'hatte', 'haben', 'b', 3),
('B2', 'Koji padež sledi predlog "wegen"?', 'Dativ', 'Nominativ', 'Akuzativ', 'Genitiv', 'd', 4),
('B2', 'Šta znači "trotzdem"?', 'dakle', 'zato', 'ipak', 'inače', 'c', 5),
('B2', 'Popunite: "Das Buch ___ von Schiller geschrieben." (prošlost)', 'ist', 'wird', 'wurde', 'war', 'c', 6),
('B2', 'Koji veznik izražava uzrok: "Ich bleibe zu Hause, ___ ich krank bin."?', 'obwohl', 'weil', 'wenn', 'damit', 'b', 7),
('B2', 'Šta znači "es sei denn"?', 'osim ako', 'kao da', 'pored toga', 's obzirom na', 'a', 8),
('B2', 'Popunite: "Er tat so, ___ ob er schliefe."', 'als', 'wie', 'dass', 'wenn', 'a', 9),
('B2', 'Koji oblik je Konjunktiv II od "sein"?', 'sein', 'sei', 'wäre', 'war', 'c', 10);

-- C1: Genitive, idioms, indirect speech, nuanced vocabulary
INSERT INTO proficiency_questions (level, question_text, option_a, option_b, option_c, option_d, correct_answer, sort_order) VALUES
('C1', 'Koji padež sledi: "Aufgrund ___ schlechten Wetters..."?', 'dem', 'des', 'der', 'den', 'b', 1),
('C1', 'Šta znači idiom "jemandem auf den Zahn fühlen"?', 'zagristi nekoga', 'detaljno ispitati nekoga', 'razgovarati s nekim', 'zanemariti nekoga', 'b', 2),
('C1', 'Popunite: "Er arbeitet, ohne ___ zu schlafen."', 'je', 'jemals', 'immer', 'noch', 'b', 3),
('C1', 'Koji je tačan oblik Genitiva: "die Lösung ___ Problems"?', 'des', 'dem', 'den', 'die', 'a', 4),
('C1', 'Šta znači "gleichwohl"?', 'isto tako', 'ipak/svejedno', 'dakako', 'naprotiv', 'b', 5),
('C1', 'Popunite: "Sie bat ihn, er ___ ihr helfen." (indirektni govor)', 'möge', 'muss', 'darf', 'kann', 'a', 6),
('C1', 'Šta znači "sich in etwas verbeißen"?', 'krenuti u nešto', 'tvrdoglavo se boriti s nečim', 'zaboraviti nešto', 'pretjerano jesti', 'b', 7),
('C1', 'Koji veznik izražava ustupak: "___ er fleißig lernt, besteht er die Prüfung nicht."?', 'Obwohl', 'Weil', 'Damit', 'Seitdem', 'a', 8),
('C1', 'Šta znači "ungeachtet"?', 'neprimećen', 'bez obzira na', 'neočekivano', 'nestrpljivo', 'b', 9),
('C1', 'Koji oblik je Konjunktiv I od "kommen" (3. lice jd.)?', 'käme', 'komme', 'kommt', 'kam', 'b', 10);

-- C2: Advanced nuances, literary vocabulary, complex structures
INSERT INTO proficiency_questions (level, question_text, option_a, option_b, option_c, option_d, correct_answer, sort_order) VALUES
('C2', 'Šta znači arhaičan oblik "weiland"?', 'danas', 'nekad davno/bivši', 'svugde', 'iznenada', 'b', 1),
('C2', 'Koji stilski oblik je: "Kaum hatte er das Zimmer betreten, fiel ihm der Brief auf."?', 'Pasiv', 'Kauzalni niz', 'Invertovani red reči za naglasak', 'Konjunktiv I', 'c', 2),
('C2', 'Šta znači "nolens volens"?', 'nevoljno ali nužno', 'srećom', 'pažljivo', 'na brzinu', 'a', 3),
('C2', 'Popunite: "Er verhielt sich, ___ wäre nichts geschehen."', 'als ob', 'wie wenn', 'obwohl', 'damit', 'a', 4),
('C2', 'Šta znači "der Zeitgeist"?', 'vremenski rok', 'duh vremena', 'istorijska ličnost', 'politički pokret', 'b', 5),
('C2', 'Koji je tačan Genitiv množine: "die Stimmen ___ Kinder"?', 'des Kindes', 'der Kinder', 'den Kindern', 'die Kinder', 'b', 6),
('C2', 'Šta znači idiom "das Kind mit dem Bade ausschütten"?', 'biti okrutan', 'odbaciti dobro s lošim', 'ne brinuti o deci', 'donositi brze odluke', 'b', 7),
('C2', 'Popunite: "Hätte er das gewusst, ___ er anders gehandelt."', 'würde', 'hätte', 'wäre', 'sollte', 'b', 8),
('C2', 'Šta znači "konzis" u nemačkom književnom stilu?', 'kratko i jasno', 'sveobuhvatno', 'detaljno', 'emotivno', 'a', 9),
('C2', 'Koji glagolski oblik je: "Er soll angeblich sehr klug sein."?', 'Konjunktiv II', 'Indirektni govor (Konjunktiv I)', 'Pasiv', 'Futur II', 'b', 10);
