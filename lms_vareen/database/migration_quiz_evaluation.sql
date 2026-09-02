-- Batch 2: post-assessment evaluation storage.
-- quiz_attempts.evaluation holds the JSON evaluation (score breakdown,
-- strong/weak areas, topics to revise, recommended lessons, study advice)
-- generated when an attempt is submitted. Idempotent via information_schema.

SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'quiz_attempts'
      AND COLUMN_NAME = 'evaluation'
);

SET @ddl = IF(@col_exists = 0,
    'ALTER TABLE quiz_attempts ADD COLUMN evaluation MEDIUMTEXT NULL',
    'SELECT ''evaluation column already exists'' AS info');

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
