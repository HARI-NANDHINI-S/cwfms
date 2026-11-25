# Phase 6: Database and Data Integrity Review Audit

**Application:** Ira Sportswear / CWFMS  
**Database Engine:** PostgreSQL 15+ (Hosted via Supabase)  
**Access Layer:** Supabase PostgREST API + PostgreSQL RLS Policies  
**Audit Date:** 2026-11-25  

---

## 1. Schema Architecture & Entity Relationship Review

The database schema is organized across six core tables and one view:
1. `customers`: Primary customer records, contact information, shipping address, and profile completeness score.
2. `leads`: Lead pipeline entries, sport, products list (`jsonb`), design source, roster attachments (`jsonb`), priority, and workflow status.
3. `user_roles`: System users, RBAC roles (`admin`, `manager`, `staff`, `sales_manager`, `sales_executive`), and password hashes.
4. `customer_activity_log`: Audit trail for customer profile lifecycle events (`created`, `updated`, `viewed`, `activated`, `deactivated`).
5. `lead_activity_log`: Detailed audit trail for lead state and status transitions.
6. `lead_whatsapp_messages`: Meta WhatsApp outbound and webhook delivery tracking records.
7. `customer_summary`: Convenience view joining customer records with formatted telephone strings.

---

## 2. Integrity Constraints & Referential Safety

### 2.1 Foreign Key Cascades & Constraints
- `leads.customer_id REFERENCES customers(id) ON DELETE SET NULL`: Prevents orphan leads while safeguarding lead history if a customer is modified.
- `leads.assigned_to REFERENCES user_roles(id) ON DELETE SET NULL`: Preserves lead record if an assigned sales rep is removed.
- `lead_activity_log.lead_id REFERENCES leads(id) ON DELETE CASCADE`: Cleanly cascades log cleanup when a lead is permanently purged.
- `customer_activity_log.customer_id REFERENCES customers(id) ON DELETE CASCADE`: Cleanly cascades log cleanup when a customer is permanently purged.

### 2.2 Validation Constraints & Check Rules
- `customers.name`: `CHECK (char_length(name) >= 3)`
- `customers.email`: Regex check for valid RFC email format or `NULL`.
- `customers.pincode`: `CHECK (pincode ~ '^[0-9]+$')` (Numeric validation).
- `customers.UNIQUE(mobile_number, country_code)`: Prevents duplicate customer entries across the same country dial code.
- `leads.priority`: `CHECK (priority IN ('low', 'medium', 'high'))`.
- `leads.lead_status`: Validates the 10 workflow stages:
  `CHECK (lead_status IN ('Received','Analysis','Awaiting Clarification','Order Sheet Prepared','Verified','Awaiting Approval','Approved','Awaiting Payment','Moved to Production','Lost'))`.
- `user_roles.role`: `CHECK (role IN ('admin', 'manager', 'staff', 'sales_manager', 'sales_executive'))`.

---

## 3. Indexing & Query Optimization Review

The following PostgreSQL indexes are defined:
- `customers`:
  - `idx_customers_name`: Fast customer name lookup.
  - `idx_customers_mobile`: Fast phone lookup and duplicate checking.
  - `idx_customers_city`: Location filtering.
  - `idx_customers_active`: Status filtering.
  - `idx_customers_profile_score`: Ordering and sorting by profile strength.
- `leads`:
  - `idx_leads_customer`: Foreign key join performance.
  - `idx_leads_priority`: Priority-based Kanban filtering.
  - `idx_leads_assigned`: Sales representative dashboard filtering.
  - `idx_leads_status`: Column grouping in Kanban board.
  - `idx_leads_date`: Date range filtering (`date_from`, `date_to`).
- `user_roles`:
  - `idx_user_roles_role`: Role-based filtering.
  - `idx_user_roles_is_active`: Active user validation.

---

## 4. Row Level Security (RLS) Review

In `supabase_setup.sql`:
- All tables have `ALTER TABLE ... ENABLE ROW LEVEL SECURITY;`.
- Baseline policy:
  `CREATE POLICY "Allow all operations..." FOR ALL USING (true) WITH CHECK (true);`
- In `migration_v7_rls_hardening.sql`:
  - Hardened policies restrict direct anonymous client queries while permitting authenticated operations and service-role backend access.

---

## 5. Offline Limitations & Real Integration Testing Checklist

> [!IMPORTANT]
> Because `.env` is unpopulated in this local test clone, direct network queries to the Supabase database cannot be verified against live servers. All findings above are derived from static schema analysis and offline test doubles.

### Required Real Integration Tasks (Post-Credential Deployment):
1. Execute `supabase_setup.sql` in Supabase SQL Editor for new projects.
2. If migrating an existing project, sequentially apply:
   - `migration_v3_leads.sql`
   - `migration_v4_roles.sql`
   - `migration_v5_auth.sql`
   - `migration_v6_whatsapp.sql`
   - `migration_v7_rls_hardening.sql`
3. Verify live connection health using `api/health.php`.
4. Validate live RLS policies against PostgREST anonymous requests to confirm zero data leakage.
