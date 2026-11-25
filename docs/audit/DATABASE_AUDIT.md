# CWFMS Complete Database & Schema Audit

**Database Technology:** PostgreSQL 15+ / Supabase REST PostgREST v1  
**Audit Date:** November 20, 2026  
**Auditor:** Antigravity AI Assistant  
**Repository Branch Evaluated:** `main` (commit `e35117a`) & `feature/lead-management-module` (commit `67b31bb`)  

---

## 1. Database Architecture & Table Inventory

The CWFMS database consists of 5 core relational tables, 1 PostgreSQL view, and automated triggers.

```
┌─────────────────────────────────┐           1 : N           ┌─────────────────────────────────┐
│           customers             │───────────────────────────│             leads               │
│─────────────────────────────────│                           │─────────────────────────────────│
│ id (UUID, PK)                   │                           │ id (UUID, PK)                   │
│ name (VARCHAR)                  │                           │ customer_id (UUID, FK)          │
│ team_academy_name (VARCHAR)     │                           │ assigned_to (UUID, FK)          │
│ email (VARCHAR)                 │                           │ lead_source (VARCHAR)           │
│ country_code (VARCHAR)          │                           │ enquiry_date (DATE)             │
│ mobile_number (VARCHAR)         │                           │ products_interested (JSONB)     │
│ address_line_1 (TEXT)           │                           │ sport (VARCHAR)                 │
│ address_line_2 (TEXT)           │                           │ design_source (VARCHAR)         │
│ city (VARCHAR)                  │                           │ submission_id (VARCHAR)         │
│ state (VARCHAR)                 │                           │ design_link (TEXT)              │
│ pincode (VARCHAR)               │                           │ custom_design_file (JSONB)      │
│ country (VARCHAR)               │                           │ player_roster_received (VARCHAR)│
│ profile_score (INT)             │                           │ player_list_file (JSONB)        │
│ is_active (BOOLEAN)             │                           │ notes (TEXT)                    │
│ is_deleted (BOOLEAN)            │                           │ send_whatsapp_notification (BOOL│
│ created_at / updated_at (TZ)    │                           │ whatsapp_status (VARCHAR)       │
└─────────────────────────────────┘                           │ priority (VARCHAR)              │
                 │                                            │ lead_status (VARCHAR)           │
                 │ 1 : N                                      │ created_at / updated_at (TZ)    │
                 ▼                                            └─────────────────────────────────┘
┌─────────────────────────────────┐                                            │
│      customer_activity_log      │                                            │ 1 : N
│─────────────────────────────────│                                            ▼
│ id (UUID, PK)                   │                           ┌─────────────────────────────────┐
│ customer_id (UUID, FK)          │                           │       lead_activity_log         │
│ action (VARCHAR)                │                           │─────────────────────────────────│
│ changed_fields (JSONB)          │                           │ id (UUID, PK)                   │
│ performed_by (VARCHAR)          │                           │ lead_id (UUID, FK)              │
│ performed_at (TIMESTAMPTZ)      │                           │ action (VARCHAR)                │
│ notes (TEXT)                    │                           │ details (TEXT)                  │
└─────────────────────────────────┘                           │ old_status / new_status (VARCHAR│
                                                              │ changed_by (UUID, FK)           │
┌─────────────────────────────────┐                           │ created_at (TIMESTAMPTZ)        │
│           user_roles            │                           └─────────────────────────────────┘
│─────────────────────────────────│                                            │
│ id (UUID, PK)                   │                                            │ 1 : N
│ user_email (VARCHAR, UNIQUE)    │                                            ▼
│ user_name (VARCHAR)             │                           ┌─────────────────────────────────┐
│ role (VARCHAR)                  │                           │     lead_whatsapp_messages      │
│ is_active (BOOLEAN)             │                           │─────────────────────────────────│
│ created_at (TIMESTAMPTZ)        │                           │ id (UUID, PK)                   │
└─────────────────────────────────┘                           │ lead_id (UUID, FK)              │
                                                              │ customer_id (UUID, FK)          │
                                                              │ recipient_phone (VARCHAR)       │
                                                              │ template_name (VARCHAR)         │
                                                              │ parameters (JSONB)              │
                                                              │ provider_message_id (VARCHAR)   │
                                                              │ status (VARCHAR)                │
                                                              │ trigger_type (VARCHAR)          │
                                                              │ sent_by (UUID, FK)              │
                                                              │ created_at (TIMESTAMPTZ)        │
                                                              └─────────────────────────────────┘
```

---

## 2. Table-by-Table Schema Specification & Audit

### 2.1 Table: `customers`
- **Purpose:** Primary customer and athletic club profile storage.
- **Primary Key:** `id UUID DEFAULT uuid_generate_v4() PRIMARY KEY`
- **Audit Findings:**

| Column Name | SQL Data Type | Nullable | Constraints & Checks | PDF Conceptual Requirement | Match Status | Notes |
|---|---|:---:|---|---|:---:|---|
| `id` | UUID | No | PK, `uuid_generate_v4()` | `id` PK | ✅ MATCH | Standard UUIDv4 |
| `name` | VARCHAR(255) | No | `CHECK (char_length(name) >= 3)` | `name` Text, Mandatory | ✅ MATCH | Min 3 chars constraint |
| `team_academy_name` | VARCHAR(255) | No | None | `team_academy_name` Text, Mandatory | ✅ MATCH | Mandatory |
| `email` | VARCHAR(255) | Yes | `CHECK (email ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$' OR email IS NULL)` | `email` Email, Optional | ✅ MATCH | Regex validation on DB level |
| `country_code` | VARCHAR(10) | No | `DEFAULT '+91'` | `country_code` Dropdown, Mandatory | ✅ MATCH | Stored separately |
| `mobile_number` | VARCHAR(20) | No | None | `mobile_number` Number, Mandatory | ✅ MATCH | Numeric validated in app |
| `address_line_1` | TEXT | No | None | `address_line_1` Text, Mandatory | ✅ MATCH | Mandatory |
| `address_line_2` | TEXT | Yes | None | `address_line_2` Text, Optional | ✅ MATCH | Optional |
| `city` | VARCHAR(100) | No | None | `city` Text, Mandatory | ✅ MATCH | Mandatory |
| `state` | VARCHAR(100) | No | None | `state` Text, Mandatory | ✅ MATCH | Mandatory |
| `pincode` | VARCHAR(20) | No | `CHECK (pincode ~ '^[0-9]+$')` | `pincode` Number, Mandatory | ✅ MATCH | Preserves leading zeros |
| `country` | VARCHAR(100) | No | None | `country` Dropdown, Mandatory | ✅ MATCH | Mandatory |
| `created_at` | TIMESTAMPTZ | Yes | `DEFAULT NOW()` | `created_at` Timestamp | ✅ MATCH | Auto timestamp |
| `updated_at` | TIMESTAMPTZ | Yes | `DEFAULT NOW()` | `updated_at` Timestamp | ✅ MATCH | Managed by trigger |
| `is_active` | BOOLEAN | Yes | `DEFAULT TRUE` | Extra (v2 Migration) | ✅ ENHANCED | Soft activate/deactivate |
| `profile_score` | INTEGER | Yes | `DEFAULT 0` | Extra (v2 Migration) | ✅ ENHANCED | Completeness score 0–100% |
| `is_deleted` | BOOLEAN | Yes | `DEFAULT FALSE` | Extra (Soft Delete) | ✅ ENHANCED | Soft delete flag |
| `created_by` | UUID | Yes | None | Extra | ✅ ENHANCED | Audit reference |

- **Indexes:**
  - `idx_customers_name` ON `name`
  - `idx_customers_mobile` ON `mobile_number`
  - `idx_customers_city` ON `city`
  - `idx_customers_state` ON `state`
  - `idx_customers_country` ON `country`
  - `idx_customers_team` ON `team_academy_name`
  - `idx_customers_active` ON `is_active`
  - `idx_customers_profile_score` ON `profile_score`
  - `idx_customers_deleted` ON `is_deleted`
- **Unique Constraint:** `UNIQUE (mobile_number, country_code)` ensures duplicate customers are rejected cleanly.

---

### 2.2 Table: `leads`
- **Purpose:** Athletic apparel enquiries and sales pipeline tracking.
- **Primary Key:** `id UUID DEFAULT uuid_generate_v4() PRIMARY KEY`
- **Audit Findings:**

| Column Name | SQL Data Type | Nullable | Constraints & Checks | PDF Conceptual Requirement | Match Status | Notes |
|---|---|:---:|---|---|:---:|---|
| `id` | UUID | No | PK, `uuid_generate_v4()` | `id` PK | ✅ MATCH | Standard UUIDv4 |
| `customer_id` | UUID | Yes | `REFERENCES customers(id) ON DELETE SET NULL` | `customer_id` FK | ✅ MATCH | Preserves leads on customer delete |
| `lead_source` | VARCHAR(50) | No | `CHECK (lead_source IN ('Kit Builder', 'Website', 'WhatsApp', 'Email'))` | `lead_source` Mandatory Radio | ✅ MATCH | Exact 4 values |
| `enquiry_date` | DATE | No | None | `enquiry_date` Date, Mandatory | ✅ MATCH | Standard SQL DATE |
| `products_interested` | JSONB | No | `DEFAULT '[]'::jsonb` | `products_interested` Multi-select | ✅ MATCH | Stores array of up to 13 items |
| `sport` | VARCHAR(50) | No | `CHECK (sport IN ('Cricket', 'Soccer', 'Pickleball', 'Tennis', 'Badminton', 'Others'))` | `sport` Mandatory Radio | ✅ MATCH | Exact 6 sports |
| `design_source` | VARCHAR(50) | No | `CHECK (design_source IN ('Kit Builder', 'Design Link', 'Custom Design'))` | `design_source` Mandatory Radio | ✅ MATCH | Exact 3 sources |
| `submission_id` | VARCHAR(255) | Yes | None | `submission_id` Text | ✅ MATCH | Conditional for Kit Builder |
| `design_link` | TEXT | Yes | None | `design_link` Text | ✅ MATCH | Conditional for Design Link |
| `custom_design_file` | JSONB | Yes | `DEFAULT '[]'::jsonb` | `custom_design_file` Multi-file | ✅ MATCH | File metadata objects array |
| `player_roster_received`| VARCHAR(20) | No | `CHECK (player_roster_received IN ('Received', 'Not Received'))` | `player_roster_received` Radio | ✅ MATCH | Exact 2 options |
| `player_list_file` | JSONB | Yes | `DEFAULT '[]'::jsonb` | `player_list_file` File Upload | ✅ MATCH | File metadata objects array |
| `notes` | TEXT | Yes | None | `notes` Text Area | ✅ MATCH | Multiline instructions |
| `send_whatsapp_notification` | BOOLEAN | Yes | `DEFAULT FALSE` | Send WhatsApp Checkbox | ✅ MATCH | Boolean flag |
| `whatsapp_status` | VARCHAR(30) | Yes | `DEFAULT 'not_requested'` | WhatsApp Delivery Status | ✅ ENHANCED | Lifecycle state tracker |
| `assigned_to` | UUID | Yes | `REFERENCES user_roles(id) ON DELETE SET NULL` | `assigned_to` User Dropdown | ✅ MATCH | Relational sales rep |
| `priority` | VARCHAR(20) | No | `DEFAULT 'medium' CHECK (priority IN ('low', 'medium', 'high'))` | Card Priority Colors | ✅ MATCH | Added in Migration v3 |
| `lead_status` | VARCHAR(50) | No | `DEFAULT 'Received' CHECK (lead_status IN ('Received','Analysis','Awaiting Clarification','Order Sheet Prepared','Verified','Awaiting Approval','Approved','Awaiting Payment','Moved to Production','Lost'))` | `lead_status` 10 Stages | ✅ MATCH | Exact 10 statuses |
| `is_deleted` | BOOLEAN | Yes | `DEFAULT FALSE` | Soft Delete Flag | ✅ ENHANCED | Soft delete flag |
| `whatsapp_last_sent_at` | TIMESTAMPTZ | Yes | None | WhatsApp Timestamp | ✅ ENHANCED | Added in Migration v6 |
| `whatsapp_last_message_id` | VARCHAR(150)| Yes | None | Provider Message ID | ✅ ENHANCED | Added in Migration v6 |
| `created_at` | TIMESTAMPTZ | Yes | `DEFAULT NOW()` | `created_at` Timestamp | ✅ MATCH | Auto timestamp |
| `updated_at` | TIMESTAMPTZ | Yes | `DEFAULT NOW()` | `updated_at` Timestamp | ✅ MATCH | Managed in application/trigger |

- **Indexes:**
  - `idx_leads_customer` ON `customer_id`
  - `idx_leads_priority` ON `priority`
  - `idx_leads_assigned` ON `assigned_to`
  - `idx_leads_status` ON `lead_status`
  - `idx_leads_date` ON `enquiry_date`

---

### 2.3 Table: `user_roles`
- **Purpose:** System users, sales representatives, and access privileges.
- **Primary Key:** `id UUID DEFAULT uuid_generate_v4() PRIMARY KEY`
- **Constraint Progression:**
  - In `supabase_setup.sql`: `CHECK (role IN ('admin', 'manager', 'staff'))`
  - In `migration_v4_roles.sql`: Expanded to `CHECK (role IN ('admin', 'manager', 'staff', 'sales_manager', 'sales_executive'))`
- **Columns:**
  - `id UUID PRIMARY KEY`
  - `user_email VARCHAR(255) NOT NULL UNIQUE`
  - `user_name VARCHAR(255) NOT NULL`
  - `role VARCHAR(20) NOT NULL`
  - `is_active BOOLEAN DEFAULT TRUE`
  - `created_at TIMESTAMPTZ DEFAULT NOW()`

---

### 2.4 Table: `customer_activity_log`
- **Purpose:** Audit trail for customer profile operations.
- **Columns:**
  - `id UUID PRIMARY KEY`
  - `customer_id UUID REFERENCES customers(id) ON DELETE CASCADE`
  - `action VARCHAR(50) NOT NULL` (`created`, `updated`, `deleted`, `viewed`, `activated`, `deactivated`)
  - `changed_fields JSONB`
  - `performed_by VARCHAR(100)`
  - `performed_at TIMESTAMPTZ DEFAULT NOW()`
  - `notes TEXT`

---

### 2.5 Table: `lead_activity_log`
- **Purpose:** Audit trail for lead lifecycle and status transitions.
- **Columns:**
  - `id UUID PRIMARY KEY`
  - `lead_id UUID REFERENCES leads(id) ON DELETE CASCADE`
  - `action VARCHAR(100) NOT NULL`
  - `details TEXT`
  - `old_status VARCHAR(50)`
  - `new_status VARCHAR(50)`
  - `changed_by UUID REFERENCES user_roles(id) ON DELETE SET NULL`
  - `created_at TIMESTAMPTZ DEFAULT NOW()`

---

### 2.6 Table: `lead_whatsapp_messages`
- **Purpose:** Comprehensive Meta WhatsApp Cloud API transmission audit trail.
- **Created In:** `migration_v6_whatsapp.sql`
- **Columns:**
  - `id UUID PRIMARY KEY`
  - `lead_id UUID REFERENCES leads(id) ON DELETE CASCADE`
  - `customer_id UUID REFERENCES customers(id) ON DELETE SET NULL`
  - `recipient_phone VARCHAR(30) NOT NULL`
  - `template_name VARCHAR(100) NOT NULL`
  - `template_language VARCHAR(10) DEFAULT 'en'`
  - `parameters JSONB DEFAULT '[]'::jsonb`
  - `provider_message_id VARCHAR(150)`
  - `status VARCHAR(30) NOT NULL DEFAULT 'requested' CHECK (status IN ('not_requested', 'requested', 'sent', 'delivered', 'read', 'failed'))`
  - `error_message TEXT`
  - `trigger_type VARCHAR(30) NOT NULL DEFAULT 'manual' CHECK (trigger_type IN ('manual', 'automated', 'system'))`
  - `sent_by UUID REFERENCES user_roles(id) ON DELETE SET NULL`
  - `created_at / updated_at TIMESTAMPTZ DEFAULT NOW()`

---

## 3. Migration Dependency & Execution Plan

To deploy the CWFMS database cleanly without dependency errors, run the 5 SQL migration files in this exact sequence:

```
[1] supabase_setup.sql
    └── Creates extensions, customers, customer_activity_log, user_roles, 
        leads, lead_activity_log, views, and initial RLS policies.
        │
        ▼
[2] migration_v2.sql
    └── Adds customers.is_active, customers.profile_score, 
        and calculate_profile_score() automated trigger.
        │
        ▼
[3] migration_v3_leads.sql
    └── Adds leads.priority column with chk_leads_priority constraint ('low', 'medium', 'high') 
        and idx_leads_priority index.
        │
        ▼
[4] migration_v4_roles.sql
    └── Safely updates user_roles_role_check to support ('admin', 'manager', 'staff', 
        'sales_manager', 'sales_executive').
        │
        ▼
[5] migration_v6_whatsapp.sql
    └── Creates lead_whatsapp_messages audit log table and appends 
        whatsapp_last_sent_at and whatsapp_last_message_id to leads table.
```

### Idempotency Assessment
- Every `CREATE TABLE` specifies `IF NOT EXISTS`.
- Every `ADD COLUMN` specifies `IF NOT EXISTS`.
- Check constraints are wrapped in `DO $$ BEGIN ... IF NOT EXISTS ... END $$` blocks.
- Triggers use `CREATE OR REPLACE FUNCTION` and `DROP TRIGGER IF EXISTS`.
- **Conclusion:** All 5 migration files are 100% idempotent and safe to re-run multiple times on an active database without data loss.

---

## 4. Row Level Security (RLS) Policy Audit

In `supabase_setup.sql`, Row Level Security is enabled on tables:
```sql
ALTER TABLE customers ENABLE ROW LEVEL SECURITY;
ALTER TABLE customer_activity_log ENABLE ROW LEVEL SECURITY;
ALTER TABLE user_roles ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Allow all operations on customers" ON customers
    FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all operations on activity log" ON customer_activity_log
    FOR ALL USING (true) WITH CHECK (true);
CREATE POLICY "Allow all operations on user_roles" ON user_roles
    FOR ALL USING (true) WITH CHECK (true);
```

### Critical Security Finding (SEC-DB-01)
- **Finding:** The RLS policies use `USING (true) WITH CHECK (true)`.
- **Vulnerability:** If an attacker discovers the public Supabase Anon key (`SUPABASE_ANON_KEY`), they can make direct HTTPS requests to `https://<ref>.supabase.co/rest/v1/customers` and read, modify, or delete any record without authentication!
- **Remediation:** In production, restrict anonymous PostgREST access so that only requests authenticated via Supabase JWT or the backend service key can perform mutations.
