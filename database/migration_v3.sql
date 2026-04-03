-- Učim Nemački – Migration v3
-- Run after migration_v2.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────────────────────────────
-- Add status and additional fields to live_teachers
-- ─────────────────────────────────────────────────────────────────────────────
ALTER TABLE live_teachers
    ADD COLUMN IF NOT EXISTS status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER created_at,
    ADD COLUMN IF NOT EXISTS experience TEXT DEFAULT NULL AFTER bio,
    ADD COLUMN IF NOT EXISTS certificate VARCHAR(500) DEFAULT NULL AFTER experience,
    ADD COLUMN IF NOT EXISTS teaching_method VARCHAR(300) DEFAULT NULL AFTER certificate COMMENT 'comma-separated: zoom,google_meet,skype etc',
    ADD COLUMN IF NOT EXISTS contact_viber VARCHAR(50) DEFAULT NULL AFTER phone,
    ADD COLUMN IF NOT EXISTS contact_whatsapp VARCHAR(50) DEFAULT NULL AFTER contact_viber,
    ADD COLUMN IF NOT EXISTS lesson_duration VARCHAR(100) DEFAULT NULL AFTER available_days;

-- Approve existing teachers so they remain visible
UPDATE live_teachers SET status = 'approved' WHERE status = 'pending';

-- ─────────────────────────────────────────────────────────────────────────────
-- Seed teacher categories (skip duplicates)
-- ─────────────────────────────────────────────────────────────────────────────
INSERT INTO teacher_categories (name, sort_order)
SELECT 'Deca', 1 WHERE NOT EXISTS (SELECT 1 FROM teacher_categories WHERE name = 'Deca');
INSERT INTO teacher_categories (name, sort_order)
SELECT 'Osnovna škola', 2 WHERE NOT EXISTS (SELECT 1 FROM teacher_categories WHERE name = 'Osnovna škola');
INSERT INTO teacher_categories (name, sort_order)
SELECT 'Srednja škola', 3 WHERE NOT EXISTS (SELECT 1 FROM teacher_categories WHERE name = 'Srednja škola');
INSERT INTO teacher_categories (name, sort_order)
SELECT 'Priprema za ispit', 4 WHERE NOT EXISTS (SELECT 1 FROM teacher_categories WHERE name = 'Priprema za ispit');
INSERT INTO teacher_categories (name, sort_order)
SELECT 'Konverzacija', 5 WHERE NOT EXISTS (SELECT 1 FROM teacher_categories WHERE name = 'Konverzacija');
INSERT INTO teacher_categories (name, sort_order)
SELECT 'Gramatika', 6 WHERE NOT EXISTS (SELECT 1 FROM teacher_categories WHERE name = 'Gramatika');

SET FOREIGN_KEY_CHECKS = 1;
