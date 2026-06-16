-- ============================================================
-- Migration V2: Profile Scoring & Activate/Deactivate
-- Run this in your Supabase SQL Editor
-- ============================================================

-- Add is_active column (default true for existing customers)
ALTER TABLE customers ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE;

-- Add profile_score column (0-100)
ALTER TABLE customers ADD COLUMN IF NOT EXISTS profile_score INTEGER DEFAULT 0;

-- Set existing customers to active
UPDATE customers SET is_active = TRUE WHERE is_active IS NULL;

-- Create index on is_active for fast filtering
CREATE INDEX IF NOT EXISTS idx_customers_active ON customers(is_active);

-- Create index on profile_score
CREATE INDEX IF NOT EXISTS idx_customers_profile_score ON customers(profile_score);

-- ============================================================
-- Update customer_summary view
-- ============================================================
CREATE OR REPLACE VIEW customer_summary AS
SELECT
    id,
    name,
    team_academy_name,
    email,
    country_code || ' ' || mobile_number AS full_mobile,
    city,
    state,
    country,
    is_active,
    profile_score,
    created_at,
    updated_at
FROM customers
WHERE is_deleted = FALSE;

-- ============================================================
-- Function: Calculate profile score
-- ============================================================
CREATE OR REPLACE FUNCTION calculate_profile_score()
RETURNS TRIGGER AS $$
BEGIN
    NEW.profile_score := 0;
    -- Name (15%)
    IF NEW.name IS NOT NULL AND char_length(trim(NEW.name)) >= 3 THEN
        NEW.profile_score := NEW.profile_score + 15;
    END IF;
    -- Team (10%)
    IF NEW.team_academy_name IS NOT NULL AND char_length(trim(NEW.team_academy_name)) > 0 THEN
        NEW.profile_score := NEW.profile_score + 10;
    END IF;
    -- Email (15%)
    IF NEW.email IS NOT NULL AND char_length(trim(NEW.email)) > 0 THEN
        NEW.profile_score := NEW.profile_score + 15;
    END IF;
    -- Mobile (15%)
    IF NEW.mobile_number IS NOT NULL AND char_length(trim(NEW.mobile_number)) >= 7 THEN
        NEW.profile_score := NEW.profile_score + 15;
    END IF;
    -- Address Line 1 (10%)
    IF NEW.address_line_1 IS NOT NULL AND char_length(trim(NEW.address_line_1)) > 0 THEN
        NEW.profile_score := NEW.profile_score + 10;
    END IF;
    -- Address Line 2 (5%)
    IF NEW.address_line_2 IS NOT NULL AND char_length(trim(NEW.address_line_2)) > 0 THEN
        NEW.profile_score := NEW.profile_score + 5;
    END IF;
    -- City (10%)
    IF NEW.city IS NOT NULL AND char_length(trim(NEW.city)) > 0 THEN
        NEW.profile_score := NEW.profile_score + 10;
    END IF;
    -- State (5%)
    IF NEW.state IS NOT NULL AND char_length(trim(NEW.state)) > 0 THEN
        NEW.profile_score := NEW.profile_score + 5;
    END IF;
    -- Pincode (5%)
    IF NEW.pincode IS NOT NULL AND char_length(trim(NEW.pincode)) > 0 THEN
        NEW.profile_score := NEW.profile_score + 5;
    END IF;
    -- Country (5%)
    IF NEW.country IS NOT NULL AND char_length(trim(NEW.country)) > 0 THEN
        NEW.profile_score := NEW.profile_score + 5;
    END IF;
    -- Active Status (5%)
    IF NEW.is_active = TRUE THEN
        NEW.profile_score := NEW.profile_score + 5;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger to auto-calculate score on insert/update
DROP TRIGGER IF EXISTS trg_calculate_profile_score ON customers;
CREATE TRIGGER trg_calculate_profile_score
    BEFORE INSERT OR UPDATE ON customers
    FOR EACH ROW
    EXECUTE FUNCTION calculate_profile_score();

-- ============================================================
-- Recalculate scores for existing customers
-- ============================================================
UPDATE customers SET updated_at = NOW();
