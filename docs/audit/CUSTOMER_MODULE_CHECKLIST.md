# Customer Management Module Checklist (CM-01 to CM-11)

**Source Document:** *Customer Management Module.pdf*  
**Repository Branch Evaluated:** `main` (commit `e35117a`) & `feature/lead-management-module` (commit `67b31bb`)  
**Audit Date:** November 20, 2026  
**Auditor:** Antigravity AI Assistant  

---

## Master Status Summary Table

| ID | Requirement Name | Status | Primary File(s) | Database Table/Columns | Missing / Gap Description | Test Evidence |
|---|---|:---:|---|---|---|---|
| **CM-01.1** | Centralized Customer Storage | ✅ COMPLETED | `supabase_setup.sql`, `api/customers.php` | `customers` (all fields) | None. Fully stored in relational table. | Verified in DB setup and CRUD API. |
| **CM-01.2** | Customer Profile Creation | ✅ COMPLETED | `dashboard.php`, `api/customers.php` | `customers` | None. Form and POST API functional. | Verified form submission & validation. |
| **CM-01.3** | Customer Profile Maintenance | ✅ COMPLETED | `pages/customer-detail.php`, `api/customers.php` | `customers` | None. Edit modal saves and recalculates score. | Verified update handler & trigger. |
| **CM-01.4** | Customer Information Retrieval | ✅ COMPLETED | `api/customers.php`, `api/customer.php` | `customers`, `customer_summary` | None. Fetch by ID and list supported. | Verified GET endpoints return JSON. |
| **CM-01.5** | Quick Access to Contact Details | ✅ COMPLETED | `dashboard.php`, `pages/customer-detail.php` | `customers.mobile_number`, `country_code`, `email` | None. Rendered in table & profile cards. | Visual verification in UI. |
| **CM-01.6** | Quick Access to Shipping Address | ✅ COMPLETED | `dashboard.php`, `pages/customer-detail.php` | `address_line_1`, `address_line_2`, `city`, `state`, `pincode`, `country` | None. Formatted address blocks rendered. | Visual verification in UI. |
| **CM-01.7** | Linking Customers with Leads | ✅ COMPLETED | `pages/customer-detail.php`, `pages/leads.php`, `api/leads.php`, `api/customers.php` | `leads.customer_id` $\to$ `customers.id` | None. Full bidirectional linking: Linked Records counter, Active Pipeline table, and Kanban/Table customer deep links. | Verified in `customer-detail.php` and `leads.php`. |
| **CM-01.8** | Linking Customers with Orders | ❌ NOT IMPLEMENTED | `pages/customer-detail.php`, `dashboard.php` | None (`orders` table does not exist) | Orders module not yet built; sidebar says "Coming Soon (Phase 3)". | Table does not exist in schema. |
| **CM-02.1** | Field: Customer Name | ✅ COMPLETED | `dashboard.php`, `api/customers.php` | `customers.name` VARCHAR(255) | None. Validated for $\ge 3$ characters. | DB CHECK constraint and regex check. |
| **CM-02.2** | Field: Team / Academy Name | ✅ COMPLETED | `dashboard.php`, `api/customers.php` | `customers.team_academy_name` VARCHAR(255) | None. Mandatory field enforced. | Backend and frontend validation verified. |
| **CM-02.3** | Field: Email Address (Optional) | ✅ COMPLETED | `dashboard.php`, `api/customers.php` | `customers.email` VARCHAR(255) | None. Optional; validated if provided. | DB regex check and PHP `filter_var`. |
| **CM-02.4** | Field: Mobile Number | ✅ COMPLETED | `dashboard.php`, `api/customers.php` | `customers.mobile_number` VARCHAR(20) | None. Enforces 7-15 digits. | Regex `/^[0-9]{7,15}$/` validated. |
| **CM-02.5** | Field: Country Code | ✅ COMPLETED | `dashboard.php`, `api/customers.php` | `customers.country_code` VARCHAR(10) | None. Dropdown with +91, +971, +1, etc. | Verified separate column storage. |
| **CM-03.1** | Address Line 1 | ✅ COMPLETED | `dashboard.php`, `api/customers.php` | `customers.address_line_1` TEXT | None. Mandatory validation verified. | Backend check `empty($input['address_line_1'])`. |
| **CM-03.2** | Address Line 2 (Optional) | ✅ COMPLETED | `dashboard.php`, `api/customers.php` | `customers.address_line_2` TEXT | None. Stored as nullable column. | Verified in DB and UI. |
| **CM-03.3** | City | ✅ COMPLETED | `dashboard.php`, `api/customers.php` | `customers.city` VARCHAR(100) | None. Mandatory text field. | Enforced in form and API. |
| **CM-03.4** | State | ✅ COMPLETED | `dashboard.php`, `api/customers.php` | `customers.state` VARCHAR(100) | None. Mandatory text field. | Enforced in form and API. |
| **CM-03.5** | Pincode | ✅ COMPLETED | `dashboard.php`, `api/customers.php` | `customers.pincode` VARCHAR(20) | None. Numeric validation without stripping leading zeros. | DB constraint `pincode ~ '^[0-9]+$'`. |
| **CM-03.6** | Country | ✅ COMPLETED | `dashboard.php`, `api/customers.php` | `customers.country` VARCHAR(100) | None. Select dropdown with major countries. | Enforced in form and API. |
| **CM-04.1** | Add New Customer Profile | ✅ COMPLETED | `dashboard.php`, `api/customers.php` | `customers` | None. Full insertion pipeline active. | POST `api/customers.php` action `create`. |
| **CM-04.2** | Duplicate Mobile Number Check | ✅ COMPLETED | `api/customers.php`, `supabase_setup.sql` | `UNIQUE(mobile_number, country_code)` | None. Returns HTTP 409 Conflict if duplicate. | Verified DB constraint & API check. |
| **CM-05.1** | Edit Customer Profile | ✅ COMPLETED | `pages/customer-detail.php`, `api/customers.php` | `customers`, `customer_activity_log` | None. Authorized users can update data. | POST `api/customers.php` action `update`. |
| **CM-05.2** | Customer Updated Timestamp | ✅ COMPLETED | `supabase_setup.sql` | `customers.updated_at` | None. Automated PostgreSQL trigger updates timestamp. | Function `update_updated_at_column`. |
| **CM-06.1** | View Customer Profile Page | ✅ COMPLETED | `pages/customer-detail.php` | `customers`, `customer_activity_log` | None. Dedicated profile page functional. | HTTP GET `pages/customer-detail.php?id=...`. |
| **CM-06.2** | View Customer Linked Leads | ✅ COMPLETED | `pages/customer-detail.php`, `api/customers.php` | `leads.customer_id` | None. Dedicated Active Pipeline card, real count, and deep links implemented. | Verified in `customer-detail.php` lines 27–40 & 360–475. |
| **CM-06.3** | View Customer Linked Orders | ❌ NOT IMPLEMENTED | `pages/customer-detail.php` | None | Orders module not implemented. | Sidebar says "Coming Soon". |
| **CM-06.4** | View Payment History | ⚪ FUTURE SCOPE | N/A | N/A | Explicitly documented as future scope in PDF p. 5. | Formally excluded from Phase 1. |
| **CM-07.1** | Search: Customer Name | ✅ COMPLETED | `dashboard.php`, `api/customer.php`, `assets/js/app.js` | `customers.name` | None. Case-insensitive substring match. | Tested via live search & client filter. |
| **CM-07.2** | Search: Team / Academy Name | ✅ COMPLETED | `dashboard.php`, `api/customer.php`, `assets/js/app.js` | `customers.team_academy_name` | None. Substring search verified. | Verified in `applyFilters()` and API. |
| **CM-07.3** | Search: Mobile Number | ✅ COMPLETED | `dashboard.php`, `api/customer.php`, `assets/js/app.js` | `customers.mobile_number` | None. Matches digits cleanly. | Verified in client search. |
| **CM-07.4** | Search: City | ✅ COMPLETED | `dashboard.php`, `api/customer.php`, `assets/js/app.js` | `customers.city` | None. Substring search verified. | Verified in client search. |
| **CM-07.5** | Filter: State | ✅ COMPLETED | `dashboard.php`, `assets/js/app.js` | `customers.state` | None. Dynamic state dropdown filter. | Verified client filtering. |
| **CM-07.6** | Filter: Country | ✅ COMPLETED | `dashboard.php`, `assets/js/app.js` | `customers.country` | None. Country dropdown filter. | Verified client filtering. |
| **CM-08.1** | Rule: Customer Name $\ge 3$ Chars | ✅ COMPLETED | `api/customers.php`, `supabase_setup.sql` | `CHECK (char_length(name) >= 3)` | None. Enforced at DB and API level. | Tested with 2-char string $\to$ rejected. |
| **CM-08.2** | Rule: Mobile Number Numeric | ✅ COMPLETED | `api/customers.php`, `includes/config.php` | `customers.mobile_number` | None. Validates 7-15 digits. | Verified `validateMobile()`. |
| **CM-08.3** | Rule: Email Format Validation | ✅ COMPLETED | `api/customers.php`, `supabase_setup.sql` | `CHECK (email ~* '...')` | None. Rejects malformed email. | Verified DB check and `filter_var`. |
| **CM-08.4** | Rule: Pincode Numeric Validation | ✅ COMPLETED | `api/customers.php`, `supabase_setup.sql` | `CHECK (pincode ~ '^[0-9]+$')` | None. Enforced at DB and API level. | Tested with alphanumeric $\to$ rejected. |
| **CM-08.5** | Rule: Mandatory Country Code | ✅ COMPLETED | `api/customers.php`, `dashboard.php` | `customers.country_code` NOT NULL | None. Enforced in API and DB schema. | Verified validation. |
| **CM-08.6** | Rule: Mandatory Country | ✅ COMPLETED | `api/customers.php`, `dashboard.php` | `customers.country` NOT NULL | None. Enforced in API and DB schema. | Verified validation. |
| **CM-09.1** | Role Enforcement: Admin | ✅ COMPLETED | `includes/config.php`, `api/customers.php`, `api/customer.php` | `user_roles.role` | None. Full permissions (Create, Edit, Delete, View). | Verified permission matrix. |
| **CM-09.2** | Role Enforcement: Manager | ✅ COMPLETED | `includes/config.php`, `api/customers.php`, `api/customer.php` | `user_roles.role` | None. Create, Edit, View allowed; Delete blocked (403). | Verified in `api/customer.php` delete action. |
| **CM-09.3** | Role Enforcement: Staff | ✅ COMPLETED | `includes/config.php`, `api/customers.php`, `api/customer.php` | `user_roles.role` | None. Create, View allowed; Edit & Delete blocked (403). | Verified in `api/customers.php` update action. |
| **CM-10.1** | Customer Schema Alignment | ✅ COMPLETED | `supabase_setup.sql`, `migration_v2.sql` | `customers` table | None. All 14 conceptual fields present + scoring. | Schema comparison verified. |
| **CM-11.1** | Future: WhatsApp Order Updates | ⚪ FUTURE SCOPE | N/A | N/A | Documented as future enhancement. | Backlog item. |
| **CM-11.2** | Future: Multiple Shipping Addresses | ⚪ FUTURE SCOPE | N/A | N/A | Documented as future enhancement. | Backlog item. |
| **CM-11.3** | Future: Separate Billing Address | ⚪ FUTURE SCOPE | N/A | N/A | Documented as future enhancement. | Backlog item. |
| **CM-11.4** | Future: GST / Tax ID Field | ⚪ FUTURE SCOPE | N/A | N/A | Documented as future enhancement. | Backlog item. |
| **CM-11.5** | Future: Customer Tagging (School/Club/Corp) | ⚪ FUTURE SCOPE | N/A | N/A | Documented as future enhancement. | Backlog item. |
| **CM-11.6** | Future: Customer Activity Timeline | ⚪ FUTURE SCOPE | N/A | N/A | Basic audit log exists; graphical timeline is future. | Backlog item. |
| **CM-11.7** | Future: Document Uploads (ID, GST) | ⚪ FUTURE SCOPE | N/A | N/A | Documented as future enhancement. | Backlog item. |
| **CM-11.8** | Future: Auto Country Code Detection | ⚪ FUTURE SCOPE | N/A | N/A | Documented as future enhancement. | Backlog item. |

---

## Detailed Audit of Incomplete & Defective Customer Requirements

### Requirement CM-01.7 & CM-06.2: View Customer Linked Leads
1. **Requirement ID:** CM-06.2
2. **Requirement Name:** Display Linked Leads in Customer Profile
3. **Source Document:** *Customer Management Module.pdf*, Page 5
4. **Current Status:** 🔴 DEFECT / 🟡 PARTIAL
5. **Existing Implementation:**
   - In `pages/leads.php`, a lead is created with a foreign key `customer_id` referencing `customers.id`.
   - In `pages/customer-detail.php`, only basic contact cards, shipping cards, profile score, and `customer_activity_log` are queried and displayed.
   - The navigation sidebar in `pages/customer-detail.php` still labels "Leads" as `Coming Soon` with `cursor:not-allowed`.
6. **Exact File Paths:**
   - `pages/customer-detail.php` (lines 40–100)
   - `api/customers.php`
7. **Relevant Functions/APIs:**
   - `pages/customer-detail.php`: Missing Supabase query `$db->select('leads', ['customer_id' => 'eq.' . $id])`.
   - `api/customers.php`: Missing `action=leads` endpoint.
8. **Database Tables and Columns:**
   - Table `leads`, column `customer_id` (UUID references `customers.id`).
9. **Evidence:**
   Direct inspection of `pages/customer-detail.php` shows zero queries or HTML markup referencing the `leads` table.
10. **Missing Functionality:**
    - A dedicated tab or card in `pages/customer-detail.php` listing all leads associated with this customer (showing Lead ID, Sport, Products, Status, Enquiry Date, and Assigned Rep).
    - Clickable link navigating to `pages/leads.php?lead_id={lead.id}`.
    - Updated sidebar link pointing directly to `pages/leads.php`.
11. **Root Cause:**
    The developer who implemented `feature/lead-management-module` focused exclusively on `pages/leads.php` and did not backport cross-module views into `pages/customer-detail.php`.
12. **Severity:** High
13. **Recommended Solution:**
    In `pages/customer-detail.php`, query the `leads` table for `customer_id = $id`, render a "Linked Leads" data table with status badges and quick view buttons, and enable the sidebar link to `pages/leads.php`.
14. **Acceptance Criteria:**
    - When viewing a customer profile with 2 existing leads, both leads appear in the customer detail view.
    - Clicking on a lead opens or navigates to that lead's details.

---

### Requirement CM-01.8 & CM-06.3: View Customer Linked Orders
1. **Requirement ID:** CM-06.3
2. **Requirement Name:** Customer-Linked Orders
3. **Source Document:** *Customer Management Module.pdf*, Page 5
4. **Current Status:** ❌ NOT IMPLEMENTED
5. **Existing Implementation:** No `orders` table or API exists in either branch.
6. **Severity:** Low (Downstream workflow; Orders module is scheduled for future expansion).
7. **Recommended Solution:** Maintain placeholder or future scope indicator until the Order Management module is scheduled.
