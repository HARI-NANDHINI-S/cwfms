# CWFMS Implementation Gap Report & Technical Specifications

**Project:** Ira Sportswear / Studio 3 Interactive — CWFMS  
**Audit Date:** November 20, 2026  
**Auditor:** Antigravity AI Assistant  

---

## 1. Consolidated Gap Summary & Priority Hierarchy

| Gap ID | Requirement ID(s) | Gap Title | Category | Severity | Current Status | Primary Affected Files |
|---|---|---|---|:---:|:---:|---|
| **GAP-01** | SEC-01 | Production Authentication & Password Hashing | Security / Foundation | **CRITICAL** | 🔴 DEFECT | `login.php`, `user_roles` table, `api/auth.php` |
| **GAP-02** | SEC-02, SEC-03 | Secret Key Isolation & RLS Policy Hardening | Security / Foundation | **CRITICAL** | 🔴 DEFECT | `includes/config.php`, `.env`, `supabase_setup.sql` |
| **GAP-03** | CM-06.2, INT-05, INT-06 | Customer Profile Linked Leads Section & Navigation | Customer / Integration | **HIGH** | 🔴 DEFECT | `pages/customer-detail.php`, `api/customers.php` |
| **GAP-04** | INT-07 | Lead Card to Customer Profile Direct Navigation | Lead / Integration | **HIGH** | 🟡 PARTIAL | `pages/leads.php` |
| **GAP-05** | LM-13.3, SEC-04 | Server-Side Binary Magic-Byte File Validation | Lead / Security | **MEDIUM** | 🟡 PARTIAL | `api/leads.php` |
| **GAP-06** | BRANCH-01 | Staged Feature Branch Harmonization into Main | Git / Architecture | **MEDIUM** | 🟡 PARTIAL | Git repository branches |
| **GAP-07** | INT-11 | Bidirectional Customer Activity Logging for Leads | Integration | **MEDIUM** | 🟡 PARTIAL | `api/leads.php`, `customer_activity_log` |
| **GAP-08** | LM-09.4 | Meta WhatsApp Cloud API Live Delivery Onboarding | Lead / Integration | **MEDIUM** | ⛔ BLOCKED | `.env`, `includes/whatsapp.php`, `api/whatsapp.php` |
| **GAP-09** | CM-06.3, INT-12 | Orders Module Future Integration Placeholder | Customer / Expansion | **LOW** | ❌ NOT IMPLEMENTED | `pages/customer-detail.php` |

---

## 2. Exhaustive Technical Specifications & Implementation Prompts for Every Gap

---

### GAP-01: Production Authentication & Secure Password Hashing

#### A. Requirement Identification
- **Requirement ID:** GAP-01 (addresses VULN-01, TC-SEC-01)
- **Requirement Title:** Replace Demo Password with Native Hashed Password Authentication
- **Reference Document:** *System Security Specification & README Critical Security Guideline*
- **Current Status:** 🔴 DEFECT / CRITICAL

#### B. Current Implementation Analysis
- **Existing Files:** `login.php`, `api/auth.php`, `supabase_setup.sql`
- **What currently works:** Role assignment and session persistence work; active status check works.
- **What is incomplete/broken:** `login.php` hardcodes `$validPassword = ($password === 'password123');`. In addition, hardcoded mock accounts (`$demoUsers`) exist with static mock UUIDs. Any email with `password123` gains access.
- **Root Cause:** A temporary testing stub was never replaced with production authentication.

#### C. Implementation Objective
Implement cryptographically secure password storage using PHP native `password_hash()` (Argon2ID or BCRYPT) and `password_verify()`. Remove all hardcoded demo accounts. Add password update capability for administrators.

#### D. Frontend Implementation
- **Files to modify:** `login.php`
- Remove demo autofill pills (`Admin`, `Sales Manager`, `Sales Executive`).
- Clean login form with Email and Password inputs, loading spinner, and responsive error banners.

#### E. Backend Implementation
- **Files to modify:** `login.php`, `api/auth.php`
- In `login.php`:
  1. Fetch user from `user_roles` where `user_email = strtolower($email)` and `is_active = true`.
  2. Verify with `password_verify($password, $userRecord['password_hash'])`.
  3. If password matches, regenerate session ID (`session_regenerate_id(true)`), set `$_SESSION['user']`, and redirect to `dashboard.php`.
- In `api/auth.php`:
  - When Admin creates or updates a user (`action=create_user` or `action=reset_password`), hash the initial password with `password_hash($rawPassword, PASSWORD_DEFAULT)`.

#### F. Database Implementation
- **SQL Migration (`migration_v5_auth.sql`):**
  ```sql
  ALTER TABLE user_roles ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255);
  -- Seed baseline hashed passwords (password123 hashed securely for initial migration)
  UPDATE user_roles 
  SET password_hash = '$2y$10$wT3x9e.1jZJjD6fU4X7bO.sWk6hC6lGZ9O1q.Hk9rVf8dO0M6aFm.'
  WHERE password_hash IS NULL;
  ```

#### G. Integration
Seamless integration with `getCurrentUser()`, `requireApiAuth()`, and RBAC permissions in `includes/config.php`.

#### H. Security
- Session fixation protection via `session_regenerate_id(true)`.
- Replaces plaintext password comparison with constant-time cryptographic hash verification.

#### I. Test Cases
1. *Valid:* Login with valid email and correct hashed password $\to$ success (200 $\to$ dashboard).
2. *Invalid:* Login with valid email and wrong password $\to$ error: "Invalid email or password".
3. *Invalid:* Login with deactivated user account (`is_active = false`) $\to$ error: "Invalid email or password".
4. *Unauthorized:* Direct API access without active session $\to$ HTTP 401 Unauthorized.

#### J. Acceptance Criteria
- No hardcoded password strings exist in `login.php`.
- Database stores only one-way cryptographic password hashes.
- All users log in using their individually hashed credentials.

#### K. Final Verification
Execute `login.php` POST with invalid credentials; verify failure. Execute with correct credentials; verify session creation and redirection.

#### L. Ready-to-Use Antigravity Implementation Prompt
```markdown
Please implement production authentication and secure password hashing for CWFMS:
1. Create `migration_v5_auth.sql` adding `password_hash VARCHAR(255)` to `user_roles` table, and set initial secure password hashes for existing users.
2. Update `login.php` to remove the hardcoded `$demoUsers` array and the `$password === 'password123'` check. Replace with `password_verify($password, $userRecord['password_hash'])` and call `session_regenerate_id(true)`.
3. In `login.php`, remove the demo pills from the UI.
4. In `api/auth.php`, update `action=create_user` to accept a `password` parameter, hash it using `password_hash($password, PASSWORD_DEFAULT)`, and store it in `password_hash`.
5. Add an `action=reset_password` endpoint in `api/auth.php` for Admin users.
Ensure all existing session management and role normalization functions in `includes/config.php` remain completely compatible.
```

---

### GAP-02: Supabase Secret Key Isolation & RLS Policy Hardening

#### A. Requirement Identification
- **Requirement ID:** GAP-02 (addresses VULN-02, VULN-03)
- **Requirement Title:** Isolate Supabase Service Role Key and Harden Row Level Security
- **Reference Document:** *Developer Reference & Part 3.4 Security Audit*
- **Current Status:** 🔴 DEFECT / CRITICAL

#### B. Current Implementation Analysis
- **Existing Files:** `includes/config.php`, `check_tables.py`, `test_roles.py`, `test_trigger.py`, `supabase_setup.sql`
- **What is broken:** `SUPABASE_SERVICE_KEY` JWT is committed in plaintext into source files. Database policies grant full access to anonymous public requests (`USING (true)`).

#### C. Implementation Objective
Ensure secret keys are loaded exclusively from `.env` or system environment variables with zero fallback plaintext secrets in source files. Restrict PostgREST RLS policies.

#### D. Frontend Implementation
N/A (Backend / Infrastructure).

#### E. Backend Implementation
- In `includes/config.php`:
  - Enforce `$serviceKey = $getEnvVal('SUPABASE_SERVICE_KEY', '');`. If empty, fail gracefully or throw an initialization error instead of falling back to a committed hardcoded token.
- In all Python utility scripts (`check_tables.py`, `test_roles.py`):
  - Read `os.environ.get('SUPABASE_SERVICE_KEY')` instead of hardcoded strings.

#### F. Database Implementation
- **SQL Migration (`migration_v7_rls_hardening.sql`):**
  ```sql
  -- Restrict customers table RLS
  DROP POLICY IF EXISTS "Allow all operations on customers" ON customers;
  CREATE POLICY "Service role full access on customers" ON customers
      FOR ALL TO service_role USING (true) WITH CHECK (true);
  CREATE POLICY "Authenticated users select customers" ON customers
      FOR SELECT TO authenticated USING (true);

  -- Restrict leads table RLS
  DROP POLICY IF EXISTS "Allow all operations on leads" ON leads;
  CREATE POLICY "Service role full access on leads" ON leads
      FOR ALL TO service_role USING (true) WITH CHECK (true);
  ```

#### G. Integration
Backend PHP uses `new Supabase(true)` with the securely injected `SUPABASE_SERVICE_KEY`.

#### H. Security
Prevents unauthorized public clients from querying or tampering with the database via PostgREST.

#### I. Test Cases
1. Access Supabase REST API directly with `anon` key $\to$ Mutations (POST/PATCH/DELETE) rejected.
2. Web application performs customer and lead operations $\to$ Succeeded via backend service key.

#### J. Acceptance Criteria
- Zero plaintext `eyJ...` JWT strings exist in git-tracked source files.
- `.env` is listed in `.gitignore` and ignored.

#### K. Final Verification
Search codebase for Supabase JWT strings; verify 0 matches.

#### L. Ready-to-Use Antigravity Implementation Prompt
```markdown
Please isolate secret credentials and harden Row Level Security for CWFMS:
1. In `includes/config.php`, update the constants `SUPABASE_URL`, `SUPABASE_ANON_KEY`, and `SUPABASE_SERVICE_KEY` to load exclusively from environment variables or `.env`. Remove all plaintext JWT fallback tokens from `includes/config.php`.
2. Ensure `check_tables.py`, `test_roles.py`, and `test_trigger.py` read secrets from environment variables.
3. Create `migration_v7_rls_hardening.sql` that replaces the permissive `FOR ALL USING (true)` policies with strict policies granting write operations exclusively to `service_role`.
4. Ensure `.env` is confirmed in `.gitignore`.
```

---

### GAP-03: Customer Profile Linked Leads Section & Navigation

#### A. Requirement Identification
- **Requirement ID:** GAP-03 (addresses CM-06.2, INT-05, INT-06, DEF-01, DEF-04)
- **Requirement Title:** Render Linked Leads and Enable Navigation in Customer Profile
- **Reference Document:** *Customer Management Module.pdf*, Page 5
- **Current Status:** 🔴 DEFECT / HIGH

#### B. Current Implementation Analysis
- **Existing Files:** `pages/customer-detail.php`, `api/customers.php`
- **What currently works:** Customer contact card, address card, profile completeness score, and activity logs render properly.
- **What is incomplete/broken:** Customer profile does not query or display leads linked to the customer. Sidebar still says "Coming Soon: Leads".
- **Root Cause:** Feature branch developed `pages/leads.php` without backporting customer-specific lead queries to `pages/customer-detail.php`.

#### C. Implementation Objective
Display all active and historical leads associated with the customer in `pages/customer-detail.php`. Update navigation sidebar to enable the link to `leads.php`.

#### D. Frontend Implementation
- **Files to modify:** `pages/customer-detail.php`
- In sidebar: Replace `<span class="nav-item" style="opacity:0.4;cursor:not-allowed">Leads</span>` with `<a href="leads.php" class="nav-item">Leads</a>`.
- In main body: Add a new section **"Enquiries & Leads Pipeline"**:
  - Render a data table with columns: `Ref #`, `Sport`, `Products Interested`, `Priority`, `Status`, `Enquiry Date`, `Assigned Rep`, and `Action`.
  - Add an "Add New Lead for Customer" button that opens `leads.php?customer_id={id}&action=new`.
  - If no leads exist, render a clean empty state with a call to action.

#### E. Backend Implementation
- In `pages/customer-detail.php` (server-side):
  Query `leads` table for this customer:
  ```php
  $leadsResult = $db->select('leads', [
      'customer_id' => 'eq.' . $id,
      'is_deleted'  => 'eq.false',
      'order'       => 'enquiry_date.desc',
  ]);
  $customerLeads = $leadsResult['data'] ?? [];
  ```
- Also resolve sales user names from `user_roles`.

#### F. Database Implementation
No new schema needed; utilizes existing relational index `idx_leads_customer` on `leads(customer_id)`.

#### G. Integration
Directly links the Customer Management Module with the Lead Management Module.

#### H. Security
Staff, Sales Executives, Sales Managers, and Admins can view customer leads according to their role permissions.

#### I. Test Cases
1. Open customer profile with 2 leads $\to$ Both leads rendered with correct sports, statuses, and badges.
2. Open customer profile with 0 leads $\to$ Clean empty state rendered.
3. Click "View Lead" $\to$ Navigates to `leads.php?lead_id=...`.
4. Click sidebar "Leads" $\to$ Navigates to `leads.php`.

#### J. Acceptance Criteria
- Customer profile displays all linked leads.
- Sidebar link to Leads is fully active and functional.

#### K. Final Verification
Navigate to `pages/customer-detail.php?id={test_id}` and confirm that linked leads render cleanly.

#### L. Ready-to-Use Antigravity Implementation Prompt
```markdown
Please update `pages/customer-detail.php` to display linked leads and fix cross-module navigation:
1. In `pages/customer-detail.php`, query the `leads` table using `$db->select('leads', ['customer_id' => 'eq.' . $id, 'is_deleted' => 'eq.false', 'order' => 'created_at.desc'])`.
2. Fetch sales user records to resolve the assigned sales representative's name.
3. In the customer profile UI, add a new section titled "Enquiries & Athletic Orders (Leads)" right below the customer profile score card.
4. Render a responsive table showing: Lead Reference ID, Sport with icon, Products interested, Priority badge, Status badge with official color styling, Enquiry date, Assigned representative, and a "View in Pipeline" button linking to `leads.php?id={lead.id}`.
5. In `pages/customer-detail.php`, update the sidebar navigation link from the disabled "Coming Soon: Leads" span to an active `<a href="leads.php" class="nav-item">` link with the existing SVG icon.
```

---

### GAP-04: Direct Customer Profile Navigation from Lead Views

#### A. Requirement Identification
- **Requirement ID:** GAP-04 (addresses INT-07, DEF-05)
- **Requirement Title:** Add Direct Links to Customer Profile from Lead Cards and Modals
- **Reference Document:** *Lead Management Module.pdf*, Page 9 & 10
- **Current Status:** 🟡 PARTIAL / HIGH

#### B. Current Implementation Analysis
- **Existing Files:** `pages/leads.php`
- **What is incomplete:** Lead cards and lead details modal render customer name and academy name as plain text. Sales representatives cannot click on a customer name to inspect their full shipping address or profile score.

#### C. Implementation Objective
Wrap customer names in lead cards and the lead detail modal with hyperlinks pointing to `customer-detail.php?id={lead.customer_id}`.

#### D. Frontend Implementation
- **Files to modify:** `pages/leads.php`
- In `renderKanban()`:
  Update card header markup:
  ```javascript
  const customerLink = `<a href="customer-detail.php?id=${encodeURIComponent(lead.customer_id)}" class="card-customer-link" title="View Customer Profile">${escapeHtml(lead.customer?.name || 'Customer')}</a>`;
  ```
- In `viewLead(leadId)` modal:
  Update customer box to include an "Open Customer Profile" button.

#### E. Backend Implementation
N/A (Frontend data already contains `lead.customer_id`).

#### F. Database Implementation
N/A.

#### G. Integration
Provides seamless two-way navigation between Lead Management and Customer Management.

#### H. Security
Preserves existing role-based access.

#### I. Test Cases
1. Click customer link on Kanban card $\to$ Opens customer profile page.
2. Click "View Profile" in lead modal $\to$ Opens customer profile page.

#### J. Acceptance Criteria
Customer name is clickable and navigates to the corresponding customer profile.

#### K. Ready-to-Use Antigravity Implementation Prompt
```markdown
Please enhance `pages/leads.php` with direct customer profile navigation:
1. In `pages/leads.php` within `renderKanban()` and `renderLeadTable()`, wrap the customer name in a clean hyperlink pointing to `customer-detail.php?id=${encodeURIComponent(lead.customer_id)}` with hover styling and a tooltip "View Full Customer Profile".
2. In the `viewLead()` modal dialog, update the "Customer" detail box so that clicking the customer name or a dedicated profile icon navigates directly to `customer-detail.php?id=${encodeURIComponent(lead.customer_id)}`.
```

---

### GAP-05: Server-Side Binary Magic-Byte File Validation

#### A. Requirement Identification
- **Requirement ID:** GAP-05 (addresses LM-13.3, VULN-04, DEF-03)
- **Requirement Title:** Implement Binary MIME Type Magic-Byte File Validation for Uploads
- **Reference Document:** *Lead Management Module.pdf*, Page 7
- **Current Status:** 🟡 PARTIAL / MEDIUM

#### B. Current Implementation Analysis
- **Existing Files:** `api/leads.php`
- **What is incomplete:** File uploads are checked strictly by file extension string. Malicious files with spoofed extensions could bypass the extension check.

#### C. Implementation Objective
Enforce binary magic-byte inspection using PHP's Fileinfo extension (`finfo_open(FILEINFO_MIME_TYPE)`) inside `savedFileMeta()`.

#### D. Backend Implementation
- **Files to modify:** `api/leads.php`
- Update `savedFileMeta($file)`:
  1. Check extension against whitelist: `pdf`, `ai`, `eps`, `png`, `jpg`, `jpeg`, `zip`, `xlsx`, `xls`, `csv`.
  2. Inspect actual MIME type using `finfo_file()`:
     ```php
     $finfo = finfo_open(FILEINFO_MIME_TYPE);
     $detectedMime = finfo_file($finfo, $file['tmp_name']);
     finfo_close($finfo);
     ```
  3. Validate detected MIME type against allowed map.
  4. Reject mismatch with HTTP 422: "File content does not match allowed types."

#### E. Test Cases
1. Upload real `.pdf` $\to$ Accepted.
2. Upload executable renamed to `.pdf` $\to$ Rejected with MIME mismatch error.

#### F. Ready-to-Use Antigravity Implementation Prompt
```markdown
Please enhance file upload security in `api/leads.php`:
1. In `api/leads.php` inside `savedFileMeta()`, implement binary MIME type verification using `finfo_open(FILEINFO_MIME_TYPE)` and `finfo_file()`.
2. Define a strict MIME whitelist for allowed extensions:
   - PDF: `application/pdf`
   - PNG: `image/png`
   - JPG/JPEG: `image/jpeg`
   - ZIP: `application/zip`, `application/x-zip-compressed`
   - XLSX: `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`
   - XLS: `application/vnd.ms-excel`
   - CSV: `text/csv`, `text/plain`
   - AI/EPS: `application/postscript`, `application/pdf`, `image/x-eps`, `application/octet-stream`
3. If the detected binary MIME type does not match the file extension or is not allowed, reject the upload and return HTTP 422 with a clear error message.
```

---

### GAP-06: Staged Feature Branch Harmonization into Main

#### A. Requirement Identification
- **Requirement ID:** GAP-06 (addresses Part 2 Branch Audit)
- **Requirement Title:** Merge `feature/lead-management-module` into `main` Safely
- **Current Status:** 🟡 PARTIAL / MEDIUM

#### B. Current Implementation Analysis
- Branch `main` has commit `e35117a`.
- Branch `feature/lead-management-module` has commit `67b31bb` (ahead by 1 commit).
- `main` completely lacks the Lead Management module.

#### C. Implementation Objective
Safely fast-forward or merge `feature/lead-management-module` into `main` without losing any commit history or working tree state.

#### D. Ready-to-Use Antigravity Implementation Prompt
```markdown
Please perform a clean git fast-forward integration of `feature/lead-management-module` into `main`:
1. Verify working tree is clean.
2. Checkout `main` and execute `git merge --ff-only feature/lead-management-module`.
3. Verify all 45 files from the feature branch are present on `main`.
4. Run the automated test suites in `tests/` to confirm zero regression.
```

---

### GAP-07: Bidirectional Customer Activity Logging for Leads

#### A. Requirement Identification
- **Requirement ID:** GAP-07 (addresses INT-11)
- **Requirement Title:** Log Customer Activity when Leads are Created or Reassigned
- **Current Status:** 🟡 PARTIAL / MEDIUM

#### B. Current Implementation Analysis
- Lead actions currently log strictly to `lead_activity_log`.
- `customer_activity_log` receives no notice of lead creation or status changes.

#### C. Ready-to-Use Antigravity Implementation Prompt
```markdown
Please implement bidirectional activity logging in `api/leads.php`:
1. In `api/leads.php` when a new lead is created, call `$db->logActivity($customerId, 'lead_created', null, $currentUser['name'], "Enquiry {$leadId} created for {$sport}")`.
2. When a lead moves to `Approved` or `Moved to Production`, log an entry to `customer_activity_log` so the customer profile timeline reflects apparel order progression.
```

---

### GAP-08: Meta WhatsApp Cloud API Live Delivery Onboarding

#### A. Requirement Identification
- **Requirement ID:** GAP-08 (addresses LM-09.4, BLK-01)
- **Requirement Title:** Configure Live Meta WhatsApp Cloud API Credentials & Webhook
- **Current Status:** ⛔ BLOCKED

#### B. Current Implementation Analysis
- Service architecture, template generators, E.164 normalization, and webhook receivers are fully implemented in `includes/whatsapp.php` and `api/whatsapp_webhook.php`.
- Dispatch is blocked solely because `.env` holds placeholder tokens (`<your-meta-whatsapp-token>`).

#### C. Ready-to-Use Antigravity Implementation Prompt
```markdown
Please prepare CWFMS for live WhatsApp Cloud API onboarding:
1. Verify that `WHATSAPP_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`, `WHATSAPP_BUSINESS_ACCOUNT_ID`, and `WHATSAPP_APP_SECRET` are read from `.env` in `includes/config.php`.
2. Provide a step-by-step verification checklist for the administrator to register the 5 approved templates in Meta WhatsApp Manager (`ira_lead_acknowledgement`, `ira_clarification_request`, `ira_order_sheet_update`, `ira_order_approved`, `ira_moved_to_production`).
3. Verify that `api/whatsapp_webhook.php` returns the `hub_challenge` correctly during Meta webhook verification.
```
