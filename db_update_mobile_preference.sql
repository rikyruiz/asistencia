-- Add mobile landing preference column to usuarios table
-- Run this SQL to update the database schema

ALTER TABLE usuarios
ADD COLUMN mobile_landing_preference ENUM('dashboard', 'clock', 'ask') DEFAULT 'ask'
AFTER ultimo_acceso;

-- Optional: Update existing users to 'ask' (already default, but for clarity)
UPDATE usuarios SET mobile_landing_preference = 'ask' WHERE mobile_landing_preference IS NULL;
