-- ============================================================
-- CWFMS Migration V7: Database Row Level Security (RLS) Hardening
-- Ira Sportswear / Studio 3 Interactive
-- Replaces permissive public write policies with strict service-role
-- and authenticated access controls to prevent anonymous tampering.
-- ============================================================

-- 1. Enable RLS explicitly on all tables
ALTER TABLE customers ENABLE ROW LEVEL SECURITY;
ALTER TABLE customer_activity_log ENABLE ROW LEVEL SECURITY;
ALTER TABLE user_roles ENABLE ROW LEVEL SECURITY;
ALTER TABLE leads ENABLE ROW LEVEL SECURITY;
ALTER TABLE lead_activity_log ENABLE ROW LEVEL SECURITY;
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'lead_whatsapp_messages') THEN
        ALTER TABLE lead_whatsapp_messages ENABLE ROW LEVEL SECURITY;
    END IF;
END $$;

-- 2. Drop overly permissive legacy policies
DROP POLICY IF EXISTS "Allow all operations on customers" ON customers;
DROP POLICY IF EXISTS "Allow all operations on activity log" ON customer_activity_log;
DROP POLICY IF EXISTS "Allow all operations on user_roles" ON user_roles;
DROP POLICY IF EXISTS "Allow all operations on leads" ON leads;
DROP POLICY IF EXISTS "Allow all operations on lead_activity" ON lead_activity_log;

-- 3. Dedicated Service Role Full Access Policies
-- The PHP backend operates via service_role key; grant full privileges to service_role
CREATE POLICY "service_role_customers_all" ON customers
    FOR ALL TO service_role USING (true) WITH CHECK (true);

CREATE POLICY "service_role_customer_activity_all" ON customer_activity_log
    FOR ALL TO service_role USING (true) WITH CHECK (true);

CREATE POLICY "service_role_user_roles_all" ON user_roles
    FOR ALL TO service_role USING (true) WITH CHECK (true);

CREATE POLICY "service_role_leads_all" ON leads
    FOR ALL TO service_role USING (true) WITH CHECK (true);

CREATE POLICY "service_role_lead_activity_all" ON lead_activity_log
    FOR ALL TO service_role USING (true) WITH CHECK (true);

DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'lead_whatsapp_messages') THEN
        DROP POLICY IF EXISTS "service_role_whatsapp_all" ON lead_whatsapp_messages;
        CREATE POLICY "service_role_whatsapp_all" ON lead_whatsapp_messages
            FOR ALL TO service_role USING (true) WITH CHECK (true);
    END IF;
END $$;

-- 4. Authenticated Read Access for Verified Clients
CREATE POLICY "authenticated_select_customers" ON customers
    FOR SELECT TO authenticated USING (is_deleted = false);

CREATE POLICY "authenticated_select_leads" ON leads
    FOR SELECT TO authenticated USING (is_deleted = false);
