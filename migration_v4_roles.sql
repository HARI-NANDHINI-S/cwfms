-- ============================================================
-- Ira Sportswear Lead Management Module
-- Migration V4: Safe Role Support & Business Role Alignment
-- ============================================================
-- Description:
--   Expands the role CHECK constraint on user_roles so it safely
--   supports both legacy role codes ('admin', 'manager', 'staff')
--   and explicit business codes ('sales_manager', 'sales_executive').
--
-- Business Role Mapping:
--   1. Admin:           'admin'
--   2. Sales Manager:   'manager' / 'sales_manager'
--   3. Sales Executive: 'staff'   / 'sales_executive'
--
-- Safety Guarantees:
--   - Does NOT drop tables or columns
--   - Preserves all existing users, passwords, and assignments
--   - Idempotent: can be executed multiple times without failure
-- ============================================================

DO $$ 
BEGIN
    -- 1. Drop old constraint if present
    ALTER TABLE user_roles DROP CONSTRAINT IF EXISTS user_roles_role_check;

    -- 2. Add updated CHECK constraint supporting both code variants
    ALTER TABLE user_roles ADD CONSTRAINT user_roles_role_check 
        CHECK (role IN ('admin', 'manager', 'staff', 'sales_manager', 'sales_executive'));

    -- 3. Verify indexes on user_roles
    CREATE INDEX IF NOT EXISTS idx_user_roles_role ON user_roles(role);
    CREATE INDEX IF NOT EXISTS idx_user_roles_is_active ON user_roles(is_active);
END $$;
