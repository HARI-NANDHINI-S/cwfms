-- ============================================================
-- CWFMS Migration V5: Production Authentication & Password Hashing
-- Ira Sportswear / Studio 3 Interactive
-- Safe, non-destructive migration to add cryptographic password storage.
-- ============================================================
-- Safety Guarantees:
--   - ONLY adds the nullable password_hash column to user_roles.
--   - Does NOT modify, delete, or overwrite any existing user records.
--   - Does NOT seed any shared default passwords.
--   - Does NOT alter roles, permissions, constraints, or other tables.
--   - Fully idempotent: safe to execute multiple times.
-- ============================================================

-- 1. Add nullable password_hash column to user_roles table if not present
ALTER TABLE user_roles ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255);

-- 2. Add column comment documenting password security policy
COMMENT ON COLUMN user_roles.password_hash IS 'Cryptographic password hash generated via PHP password_hash(..., PASSWORD_DEFAULT). Never store plaintext.';
