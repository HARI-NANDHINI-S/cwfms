# CWFMS Branch Comparison Report

**Repository:** `https://github.com/HARI-NANDHINI-S/cwfms`  
**Audit Date:** November 20, 2026  
**Auditor:** Antigravity AI Assistant  
**Git Base Commit (`main`):** `e35117a` (Initial commit)  
**Feature Branch Commit (`feature/lead-management-module`):** `67b31bb`  

---

## 1. Executive Summary

A comprehensive Git inspection was conducted across all local and remote branches in the repository:
- `main` (tracking `origin/main`): Contains the foundational **Customer Management Module (v1/v2)**, user management, and Supabase integration. It **completely lacks** the Lead Management Module.
- `feature/lead-management-module` (tracking `origin/feature/lead-management-module`): Implemented by contributor `KIRUTHIKA-S-25` on top of `main`. It contains 45 added/modified files (+9,056 lines / -256 lines) that deliver the **Lead Management Module (Phases 1-6)**, WhatsApp service scaffolding, enhanced role handling, and unit test suites.

Both branches are currently divergent: `feature/lead-management-module` is 1 commit ahead of `main`. No files have been merged into `main` yet.

---

## 2. Branch Metadata & Summary Matrix

| Branch Name | Type | Latest Commit | Customer Module | Lead Module | Unique Changes | Missing Features | Recommendation |
|---|---|---|---|---|---|---|---|
| `main` | Local & Remote (`origin/main`) | `e35117a` | ✅ Implemented (v1 & v2 Profile Scoring) | ❌ Not Implemented (Only "Coming Soon" placeholders) | Baseline CRM, CSV import, basic role management, dashboard | Entire Lead Management module, WhatsApp integration, Lead migrations | **Preserve as production baseline.** Do not make destructive direct changes. |
| `feature/lead-management-module` | Local & Remote (`origin/feature/...`) | `67b31bb` | ✅ Preserved (Branding updated to Ira Sportswear) | 🟡 Substantially Implemented (Phases 1–6 present; WhatsApp live dispatch blocked on Meta keys) | `api/leads.php`, `pages/leads.php`, `includes/whatsapp.php`, `api/whatsapp.php`, `migration_v3..v6`, test suites | Customer profile view doesn't display linked leads; demo login password remains; live WhatsApp credentials missing | **Recommend staged merge after security remediation.** |

---

## 3. Detailed File Structure & Structural Changes

```
File Path                                | Status in main | Status in feature branch | Nature of Change
-----------------------------------------|----------------|--------------------------|-----------------------------------------
.env.example                             | Missing        | Added                    | Example env vars for Supabase & WhatsApp
.gitignore                               | Missing        | Added                    | Excludes .env, uploads/leads, vendor
.htaccess                                | Present        | Modified                 | Regex enhanced to deny .sql and .env
FINAL_PROJECT_DOCUMENTATION.md           | Missing        | Added                    | Architecture & installation guide
FINAL_VERIFICATION_REPORT.md             | Missing        | Added                    | Phase 1-6 test execution report
WHATSAPP_SETUP.md                        | Missing        | Added                    | Meta Cloud API setup instructions
about.php                                | Present        | Modified                 | Branding update to Ira Sportswear
api/auth.php                             | Present        | Modified                 | Added role normalization & business roles
api/leads.php                            | Missing        | Added (929 lines)        | Lead CRUD, Kanban, table, calendar, bulk
api/whatsapp.php                         | Missing        | Added (164 lines)        | Manual WhatsApp dispatch & log retrieval
api/whatsapp_webhook.php                 | Missing        | Added (110 lines)        | Meta webhook verification & status callback
assets/css/public.css                    | Present        | Modified                 | Modern login & portal styling
assets/css/style.css                     | Present        | Modified                 | Kanban, priority chips, card indicators
assets/ira_logo.png                      | Missing        | Added                    | Ira Sportswear brand identity
assets/ira_logo_web.png                  | Missing        | Added                    | Web-optimized brand logo
contact.php                              | Present        | Modified                 | Branding update
dashboard.php                            | Present        | Modified                 | Linked to pages/leads.php in sidebar
includes/config.php                      | Present        | Modified                 | .env loader, role normalization, WA config
includes/public_header.php               | Present        | Modified                 | Ira Sportswear logo & navigation
includes/whatsapp.php                    | Missing        | Added (379 lines)        | WhatsAppService class & template registry
index.php                                | Present        | Modified                 | Branding update
login.php                                | Present        | Modified                 | Added demo pill autofill & role labels
migration_v3_leads.sql                   | Missing        | Added                    | Adds priority column & relational index
migration_v4_roles.sql                   | Missing        | Added                    | Adds sales_manager & sales_executive roles
migration_v6_whatsapp.sql                | Missing        | Added                    | Creates lead_whatsapp_messages audit table
pages/customer-detail.php                | Present        | Modified                 | Title & logo updated (Leads still missing)
pages/import.php                         | Present        | Modified                 | Ira branding updated
pages/leads.php                          | Missing        | Added (1,605 lines)      | Full Kanban, Table, Calendar UI
pages/users.php                          | Present        | Modified                 | Supports sales_manager & sales_executive
supabase_setup.sql                       | Present        | Modified                 | Appended leads & lead_activity_log tables
tests/* (12 test & preview files)        | Missing        | Added (~3,500 lines)     | 300 test assertions across Phases 1-6
uploads/.htaccess                        | Missing        | Added                    | Hardened upload directory against exec
```

---

## 4. Branch Feature Comparison

### 4.1 Features Implemented in `main`
1. Customer database schema (`customers`, `customer_activity_log`, `user_roles`).
2. Customer CRUD (`api/customers.php`, `api/customer.php`).
3. Customer Profile Completeness Scoring (0–100%) via PostgreSQL trigger (`migration_v2.sql`).
4. Activate / Deactivate customer status with audit logging.
5. Customer live search and filtering (Name, Team, Mobile, City, State, Country).
6. CSV bulk customer import (`pages/import.php`).
7. Customer detail profile card (`pages/customer-detail.php`).
8. User administration (`pages/users.php`).

### 4.2 Features Implemented Only in `feature/lead-management-module`
1. **Lead Management UI (`pages/leads.php`)**:
   - 10-column Kanban board with dynamic counts and drag-and-drop workflow.
   - Lead creation & edit modal with customer selection dropdown.
   - Multi-select product checkboxes (13 products).
   - Sport radio selection (6 sports).
   - Dynamic design source input (Kit Builder Submission ID / Design Link / Custom Design multi-upload).
   - Conditional player roster upload (XLSX, XLS, CSV, PDF).
   - Top filters (Search, Assigned To, Sport, Lead Source, Date Range).
   - View switcher (Kanban, Table with bulk editing & CSV export, Calendar month grid).
   - Lead cards with priority left border (Red/Orange/Blue) and 6 visual badges (📎, 🎨, 📋, 💬, 🔥, ⭐).
2. **Lead Backend API (`api/leads.php`)**:
   - Comprehensive CRUD with validation of conditional fields and size limits (10 MB).
   - Relational joins resolving customer and assigned user objects.
   - Idempotent state transitions and `lead_activity_log` recording.
   - Automated WhatsApp webhook triggers upon specific status transitions (`Received`, `Approved`, etc.).
   - Bulk status, priority, and assignment updates.
   - Lead CSV export endpoint.
3. **WhatsApp Communication Framework (`includes/whatsapp.php`, `api/whatsapp.php`, `api/whatsapp_webhook.php`)**:
   - E.164 phone normalization (`+91 9876543210` $\to$ `919876543210`).
   - 5 pre-approved templates with dynamic placeholder injection.
   - Audit trail in `lead_whatsapp_messages`.
   - Webhook callback endpoint handling `sent`, `delivered`, `read`, and `failed` status progressions.
4. **Role Normalization Framework**:
   - Maps legacy roles (`admin`, `manager`, `staff`) to domain roles (`admin`, `sales_manager`, `sales_executive`).
   - Granular capability checks (`canAssignLead`, `canChangeLeadStatus`, `canEditLeadRecord`, `canDeleteLead`).
5. **Database Migrations (`migration_v3_leads.sql`, `migration_v4_roles.sql`, `migration_v6_whatsapp.sql`)**.
6. **Upload Security Hardening (`uploads/.htaccess`)**:
   - Script execution disabled (`php_flag engine off`).

### 4.3 Features Missing in Both Branches
1. **Customer-to-Lead Integration in Customer Detail View**:
   - `pages/customer-detail.php` has no tab, table, or list showing leads associated with that customer.
   - `customer-detail.php` sidebar still renders "Coming Soon: Leads".
   - No direct navigation link from a lead card to the customer profile.
2. **Production-Ready Authentication**:
   - Both branches rely on demo password authentication (`password === 'password123'`).
   - No password hashing or Supabase Auth implementation.
3. **MIME Magic-Byte File Validation**:
   - Both branches validate uploads strictly by file extension; binary MIME type verification (`finfo_file`) is absent.
4. **Orders & Production Module**:
   - Not implemented (marked "Coming Soon" / future scope in both branches).

---

## 5. Potential Conflicts & Integration Risks

1. **Role Constraint Mismatch**:
   - In `main`, `user_roles.role` has `CHECK (role IN ('admin', 'manager', 'staff'))`.
   - In `feature/lead-management-module`, `migration_v4_roles.sql` expands this to include `sales_manager` and `sales_executive`.
   - *Risk:* If user records with `sales_manager` are created on `main` before `migration_v4_roles.sql` is executed, database inserts will fail with a CHECK constraint violation.
2. **Branding Inconsistency**:
   - `main` uses "Studio 3 Interactive" branding.
   - `feature/lead-management-module` updates branding to "Ira Sportswear".
   - *Resolution:* Ira Sportswear is the official brand specified in both PDF reference documents.
3. **Uploads Directory Permissions**:
   - `uploads/leads` directory requires write permissions (`0775`) by the web server process.

---

## 6. Recommended Integration Strategy

**Do not execute an automatic or blind `git merge`.**

Follow this safe, staged integration approach:
1. **Step 1 - Database Schema Baseline**: Run `supabase_setup.sql`, `migration_v2.sql`, `migration_v3_leads.sql`, `migration_v4_roles.sql`, and `migration_v6_whatsapp.sql` on the database instance in order.
2. **Step 2 - Merge Feature Branch to Main**: Fast-forward or merge `feature/lead-management-module` into `main` after verifying tests.
3. **Step 3 - Implement Cross-Module Integration**:
   - Update `pages/customer-detail.php` to fetch and render all leads belonging to that customer.
   - Remove "Coming Soon" from the sidebar navigation.
   - Link customer names on lead cards directly to `customer-detail.php?id={customer_id}`.
4. **Step 4 - Modernize Authentication**:
   - Replace the `password123` logic in `login.php` with secure `password_hash`/`password_verify` or Supabase Auth.
