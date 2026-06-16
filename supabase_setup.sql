-- ============================================================
-- Customer Management Module - Supabase Database Setup
-- Run this in your Supabase SQL Editor
-- ============================================================

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ============================================================
-- CUSTOMERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS customers (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    name VARCHAR(255) NOT NULL CHECK (char_length(name) >= 3),
    team_academy_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) CHECK (email ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$' OR email IS NULL),
    country_code VARCHAR(10) NOT NULL DEFAULT '+91',
    mobile_number VARCHAR(20) NOT NULL,
    address_line_1 TEXT NOT NULL,
    address_line_2 TEXT,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    pincode VARCHAR(20) NOT NULL CHECK (pincode ~ '^[0-9]+$'),
    country VARCHAR(100) NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    created_by UUID,
    is_deleted BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    profile_score INTEGER DEFAULT 0,
    UNIQUE(mobile_number, country_code)
);

CREATE INDEX IF NOT EXISTS idx_customers_active ON customers(is_active);
CREATE INDEX IF NOT EXISTS idx_customers_profile_score ON customers(profile_score);

-- ============================================================
-- CUSTOMER ACTIVITY LOG TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS customer_activity_log (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    customer_id UUID REFERENCES customers(id) ON DELETE CASCADE,
    action VARCHAR(50) NOT NULL, -- 'created', 'updated', 'deleted', 'viewed'
    changed_fields JSONB,
    performed_by VARCHAR(100),
    performed_at TIMESTAMPTZ DEFAULT NOW(),
    notes TEXT
);

-- ============================================================
-- USER ROLES TABLE (for permissions)
-- ============================================================
CREATE TABLE IF NOT EXISTS user_roles (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    user_email VARCHAR(255) NOT NULL UNIQUE,
    user_name VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL CHECK (role IN ('admin', 'manager', 'staff')),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================================
-- TRIGGERS: Auto-update updated_at
-- ============================================================
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_customers_updated_at
    BEFORE UPDATE ON customers
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- ============================================================
-- ROW LEVEL SECURITY (RLS)
-- ============================================================
ALTER TABLE customers ENABLE ROW LEVEL SECURITY;
ALTER TABLE customer_activity_log ENABLE ROW LEVEL SECURITY;
ALTER TABLE user_roles ENABLE ROW LEVEL SECURITY;

-- Policy: Allow all operations (adjust based on your auth setup)
CREATE POLICY "Allow all operations on customers" ON customers
    FOR ALL USING (true) WITH CHECK (true);

CREATE POLICY "Allow all operations on activity log" ON customer_activity_log
    FOR ALL USING (true) WITH CHECK (true);

CREATE POLICY "Allow all operations on user_roles" ON user_roles
    FOR ALL USING (true) WITH CHECK (true);

-- ============================================================
-- INDEXES for performance
-- ============================================================
CREATE INDEX IF NOT EXISTS idx_customers_name ON customers(name);
CREATE INDEX IF NOT EXISTS idx_customers_mobile ON customers(mobile_number);
CREATE INDEX IF NOT EXISTS idx_customers_city ON customers(city);
CREATE INDEX IF NOT EXISTS idx_customers_state ON customers(state);
CREATE INDEX IF NOT EXISTS idx_customers_country ON customers(country);
CREATE INDEX IF NOT EXISTS idx_customers_team ON customers(team_academy_name);
CREATE INDEX IF NOT EXISTS idx_customers_deleted ON customers(is_deleted);
CREATE INDEX IF NOT EXISTS idx_activity_customer ON customer_activity_log(customer_id);

-- ============================================================
-- SEED DATA: Sample customers for testing
-- ============================================================
INSERT INTO customers (name, team_academy_name, email, country_code, mobile_number, address_line_1, city, state, pincode, country)
VALUES
    ('Rajesh Kumar', 'Chennai FC Academy', 'rajesh@example.com', '+91', '9876543210', '12, Anna Nagar', 'Chennai', 'Tamil Nadu', '600040', 'India'),
    ('Priya Sharma', 'Mumbai Strikers Club', 'priya@example.com', '+91', '9812345678', '45, Bandra West', 'Mumbai', 'Maharashtra', '400050', 'India'),
    ('Ahmed Al-Rashid', 'Dubai Sports Club', 'ahmed@example.com', '+971', '501234567', 'Al Wasl Road, Jumeirah', 'Dubai', 'Dubai', '00000', 'UAE'),
    ('Suresh Babu', 'Coimbatore United', NULL, '+91', '9944556677', '78, RS Puram', 'Coimbatore', 'Tamil Nadu', '641002', 'India'),
    ('Lakshmi Devi', 'Bangalore Warriors Academy', 'lakshmi@example.com', '+91', '8899001122', '23, Koramangala', 'Bengaluru', 'Karnataka', '560034', 'India');

-- ============================================================
-- SEED DATA: Default admin user
-- ============================================================
INSERT INTO user_roles (user_email, user_name, role)
VALUES
    ('admin@studio3.com', 'Admin User', 'admin'),
    ('manager@studio3.com', 'Manager User', 'manager'),
    ('staff@studio3.com', 'Staff User', 'staff');

-- ============================================================
-- VIEWS: Customer summary view
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
    created_at,
    updated_at
FROM customers
WHERE is_deleted = FALSE;
