-- ============================================================
-- Migration V3: Priority Support & Customer Association for Leads
-- Ira Sportswear / CWFMS Lead Management Module
-- Safe migration: Can be executed multiple times without errors
-- ============================================================

-- 1. Add priority column to leads table if it doesn't already exist
ALTER TABLE leads ADD COLUMN IF NOT EXISTS priority VARCHAR(20) DEFAULT 'medium';

-- 2. Add CHECK constraint for allowed priority values: 'low', 'medium', 'high'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'chk_leads_priority'
    ) THEN
        ALTER TABLE leads ADD CONSTRAINT chk_leads_priority 
            CHECK (priority IN ('low', 'medium', 'high'));
    END IF;
END $$;

-- 3. Set existing records to 'medium' if priority is null
UPDATE leads SET priority = 'medium' WHERE priority IS NULL;

-- 4. Create an index on priority for filtering & performance
CREATE INDEX IF NOT EXISTS idx_leads_priority ON leads(priority);

-- 5. Ensure index exists on customer_id for fast relational joins
CREATE INDEX IF NOT EXISTS idx_leads_customer_id ON leads(customer_id);

-- 6. Add comment documenting the priority levels per PDF specification
COMMENT ON COLUMN leads.priority IS 'Priority level: high (red), medium (orange), low (blue). Default medium.';
