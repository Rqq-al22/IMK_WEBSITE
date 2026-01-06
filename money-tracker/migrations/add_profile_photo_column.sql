-- Migration: Add profile_photo column to users table
-- Sesuai dengan struktur money_tracker.sql
-- Menambahkan kolom profile_photo setelah email
-- 
-- Struktur tabel users di money_tracker.sql:
--   id bigint UNSIGNED
--   username varchar(50)
--   email varchar(120)
--   password_hash varchar(255)
--   created_at timestamp
--
-- Kolom profile_photo akan ditambahkan setelah email

-- Versi 1: Dengan pengecekan (MySQL 5.7+)
SET @dbname = DATABASE();
SET @tablename = 'users';
SET @columnname = 'profile_photo';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` VARCHAR(255) NULL AFTER `email`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Versi 2: Simple (uncomment jika versi 1 tidak bekerja)
-- ALTER TABLE `users` ADD COLUMN `profile_photo` VARCHAR(255) NULL AFTER `email`;
