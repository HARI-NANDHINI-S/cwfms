# CWFMS Executable Runtime Test Plan & Verification Matrix

**Project:** CWFMS – Customer and Workflow Management System  
**Organization:** Studio 3 Interactive / Ira Sportswear  
**Target Environment:** PHP 8.1+ (Recommended: PHP 8.2.x or 8.3.x) with Supabase PostgreSQL  
**Document Version:** 1.0.0 (Production Release Candidate)

---

## 1. Test Environment Prerequisites & Classification Matrix

Each test scenario is assigned a prerequisite tier so that tests can be executed incrementally as infrastructure is provisioned:

| Prerequisite Tier | Dependencies Required | What Can Be Tested |
| :--- | :--- | :--- |
| **Tier 1: PHP CLI Only** | `php.exe` on PATH | Syntax linting (`php -l`), unit algorithms, file integrity. |
| **Tier 2: PHP Local Web Server** | `php.exe` running `php -S localhost:8000` | Session lifecycle, CSRF verification, rate limiting, upload validation, HTTP headers. |
| **Tier 3: PHP + Supabase Database** | `php.exe` + active Supabase credentials in `.env` | Complete Customer CRUD, Lead CRUD, Kanban drag-and-drop, activity dual write, RBAC scoping. |
| **Tier 4: Full Live Integration** | PHP + Supabase + Meta Cloud API System User credentials | Real WhatsApp template dispatch, webhook delivery event receipt and status mutation. |

---

## 2. Test Execution Plan by Module

### 2.1 Authentication & Session Management

| ID | Test Scenario | Prerequisite | Execution Method / Endpoint | Expected Outcome |
| :--- | :--- | :---: | :--- | :--- |
| **AUTH-01** | Valid Admin Login | Tier 3 | POST `/login.php` with valid email, password, and CSRF token | HTTP 302 redirect to `dashboard.php`; `$_SESSION['user']` populated; session ID regenerated. |
| **AUTH-02** | Invalid Password Attempt | Tier 3 | POST `/login.php` with incorrect password | Generic error message `"Invalid email or password."`; failed attempt recorded in rate limiter. |
| **AUTH-03** | Brute-Force Rate Limiting | Tier 2 | POST `/login.php` with wrong password 6 consecutive times | HTTP 200 with lockout message `"Too many failed login attempts. Please wait 15 minutes before trying again."` |
| **AUTH-04** | Login CSRF Rejection | Tier 2 | POST `/login.php` with missing or altered `csrf_token` | Form rejected with error `"Security validation failed: invalid or expired CSRF token."` |
| **AUTH-05** | Session Invalidation on Logout | Tier 2 | GET `/logout.php` | `$_SESSION` cleared to `[]`; session cookie expired with past epoch timestamp; redirect to `login.php?logged_out=1`. |
| **AUTH-06** | Inactive User Block | Tier 3 | POST `/login.php` for account with `is_active = false` | Login denied with generic error; no session created. |
| **AUTH-07** | Session Inactivity Timeout | Tier 2 | Send request after 7201 seconds of inactivity | Redirect to `login.php?session=expired` (or HTTP 401 for API calls). |

### 2.2 Customer Management (CRUD & Pipeline)

| ID | Test Scenario | Prerequisite | Execution Method / Endpoint | Expected Outcome |
| :--- | :--- | :---: | :--- | :--- |
| **CUST-01** | Create Customer (Valid) | Tier 3 | POST `/api/customers.php` (`action=create`) | HTTP 200/201; record persisted; activity log entry created; initial `profile_score` calculated. |
| **CUST-02** | Duplicate Prevention | Tier 3 | POST `/api/customers.php` with existing `(mobile_number, country_code)` | HTTP 409 Conflict with message `"A customer with this mobile number already exists"`. |
| **CUST-03** | Input Validation Error | Tier 2 | POST `/api/customers.php` with 2-character name or invalid mobile | HTTP 422 Unprocessable Entity with specific field validation error. |
| **CUST-04** | Customer Search & Filter | Tier 3 | GET `/api/customers.php?action=list&status=active` | HTTP 200; returns JSON array containing only active, non-deleted customer records. |
| **CUST-05** | Customer Profile Completeness | Tier 3 | View `/pages/customer-detail.php?id={id}` | Completeness score badge displays dynamic percentage matching populated fields. |
| **CUST-06** | Customer Soft Delete (Admin) | Tier 3 | POST `/api/customers.php` (`action=delete`, Admin session) | HTTP 200; `is_deleted` set to `true`; customer omitted from list view. |
| **CUST-07** | Customer Delete Denial (Staff) | Tier 3 | POST `/api/customers.php` (`action=delete`, Sales Executive session) | HTTP 403 Forbidden with `"Only administrators can delete customers"`. |

### 2.3 Lead Management & Kanban Lifecycle

| ID | Test Scenario | Prerequisite | Execution Method / Endpoint | Expected Outcome |
| :--- | :--- | :---: | :--- | :--- |
| **LEAD-01** | Create Lead (Associated) | Tier 3 | POST `/api/leads.php` (`action=create`) with valid `customer_id` | HTTP 201 Created; initial stage set to `Received`; activity entry logged. |
| **LEAD-02** | Sales Exec Self-Assignment | Tier 3 | POST `/api/leads.php` (`action=create`, Sales Executive session) | `assigned_to` automatically locked to requesting user ID even if different rep was posted. |
| **LEAD-03** | Kanban Drag-and-Drop Mutation | Tier 3 | PATCH `/api/leads.php` with `lead_status = 'Analysis'` | HTTP 200; status updated in database; dual-write activity created on both lead and customer. |
| **LEAD-04** | Invalid Stage Transition | Tier 2 | PATCH `/api/leads.php` with `lead_status = 'InvalidStage'` | HTTP 422 with `"Invalid lead status value"`. |
| **LEAD-05** | Lead Assignment (Manager/Admin) | Tier 3 | PATCH `/api/leads.php` updating `assigned_to` | HTTP 200; reassignment activity logged. |
| **LEAD-06** | Lead Assignment Denial (Sales Exec) | Tier 3 | PATCH `/api/leads.php` updating `assigned_to` (Sales Exec session) | HTTP 403 Forbidden with `"Sales Executives cannot assign or reassign leads"`. |
| **LEAD-07** | Lead CSV Export (Admin/Manager) | Tier 3 | GET `/api/leads.php?action=export` | HTTP 200 with `Content-Type: text/csv` and lead dataset stream. |
| **LEAD-08** | Lead CSV Export Denial (Sales Exec) | Tier 3 | GET `/api/leads.php?action=export` (Sales Exec session) | HTTP 403 Forbidden with `"Sales Executives cannot export lead datasets"`. |
| **LEAD-09** | Lead Deletion (Admin Only) | Tier 3 | DELETE `/api/leads.php?id={id}` (Admin session) | HTTP 200; `is_deleted = true`. Non-admin sessions receive HTTP 403. |

### 2.4 File Upload, Storage & Authenticated Download

| ID | Test Scenario | Prerequisite | Execution Method / Endpoint | Expected Outcome |
| :--- | :--- | :---: | :--- | :--- |
| **FILE-01** | Valid Design Upload | Tier 3 | POST `/api/leads.php` with `custom_design_file` (1MB PDF) | Upload saved with hashed name in `uploads/leads/`; metadata stored in DB; HTTP 201. |
| **FILE-02** | Dangerous Double Extension Block | Tier 2 | POST `/api/leads.php` with file named `payload.php.png` | HTTP 400 with `"Security violation: dangerous double file extension detected"`. |
| **FILE-03** | Disallowed Extension Block | Tier 2 | POST `/api/leads.php` with file named `script.exe` or `test.svg` | HTTP 400 with `"Invalid file type"`. |
| **FILE-04** | Magic Byte Spoof Block | Tier 2 | POST `/api/leads.php` with PHP code saved as `image.png` | `finfo` detects executable content; returns HTTP 400 security violation. |
| **FILE-05** | Direct Web Access Block | Tier 2 | Direct HTTP request `GET /uploads/leads/{filename}` | Apache returns HTTP 403 Forbidden via `uploads/.htaccess`. |
| **FILE-06** | Authenticated Download (Owner) | Tier 3 | GET `/api/leads.php?action=attachment&lead_id={id}&type=design` | HTTP 200; file streamed with `X-Content-Type-Options: nosniff` and safe `Content-Disposition`. |
| **FILE-07** | Authenticated Download Denial | Tier 3 | GET `/api/leads.php?action=attachment` for unassigned lead (Sales Exec) | HTTP 403 Forbidden with `"Sales Executives can only access attachments for leads assigned to them"`. |
| **FILE-08** | Traversal Attempt Defense | Tier 2 | GET `/api/leads.php?action=attachment&file=../../includes/config.php` | HTTP 403 Security violation: directory traversal detected. |

### 2.5 Cross-Module Integration & Real-Time Sync

| ID | Test Scenario | Prerequisite | Execution Method / Endpoint | Expected Outcome |
| :--- | :--- | :---: | :--- | :--- |
| **INT-01** | Linked Leads in Customer Profile | Tier 3 | Open `/pages/customer-detail.php?id={id}` | Renders **Active Enquiries & Athletic Team Orders** table showing all leads for that customer. |
| **INT-02** | Customer Profile Deep Link | Tier 3 | Click "+ New Enquiry" on customer profile | Opens `leads.php?customer_id={id}`; customer dropdown pre-selected and locked. |
| **INT-03** | Kanban Card to Customer Nav | Tier 3 | Click customer name on Kanban card | Direct browser navigation to `/pages/customer-detail.php?id={id}`. |
| **INT-04** | Dual-Write Activity Timeline | Tier 3 | Change status of a lead from `Received` to `Analysis` | New entry appears on `lead_activity_log` AND customer's `customer_activity_log` (`lead_event`). |
| **INT-05** | Sales Exec Pipeline Scoping | Tier 3 | Customer profile viewed by Sales Executive | Only leads assigned to that specific executive are shown in the active pipeline table. |

### 2.6 WhatsApp Meta Cloud API & Webhook Security

| ID | Test Scenario | Prerequisite | Execution Method / Endpoint | Expected Outcome |
| :--- | :--- | :---: | :--- | :--- |
| **WA-01** | Meta Webhook Verification (GET) | Tier 2 | GET `/api/whatsapp_webhook.php?hub.mode=subscribe&hub.challenge=CHALLENGE_CODE&hub.verify_token={token}` | HTTP 200 plain text returning `CHALLENGE_CODE`. |
| **WA-02** | Missing App Secret Rejection | Tier 2 | POST `/api/whatsapp_webhook.php` when `WHATSAPP_APP_SECRET` is empty | HTTP 500 error; unverified event dropped immediately (fail-closed). |
| **WA-03** | Forged Webhook Signature Block | Tier 2 | POST `/api/whatsapp_webhook.php` with invalid `X-Hub-Signature-256` | HTTP 401 Unauthorized with `"Invalid webhook signature"`. |
| **WA-04** | Valid Webhook Status Update | Tier 3 | POST `/api/whatsapp_webhook.php` with valid HMAC-SHA256 signature | HTTP 200 `EVENT_RECEIVED`; message status updated to `delivered`/`read` in DB. |
| **WA-05** | Template Dispatch (Live) | Tier 4 | Send automated notification upon stage transition | Message sent via Meta Graph API; provider message ID stored in `lead_whatsapp_messages`. |
| **WA-06** | Safe Fallback (No Credentials) | Tier 3 | Send template notification when credentials missing | Attempt logged with `status = 'requested'` without throwing unhandled exceptions or disrupting lead workflow. |

---

## 3. Automation Scripts for Execution

The PHP runtime is installed in the workspace at `bin/php/php.exe`. Execute the test battery using:

1. **Syntax Linting Across All 35 Files (Tier 1):**
   ```powershell
   python tests/run_php_lint.py
   # Or using native binary:
   .\bin\php\php.exe -l <file>
   ```
2. **Execute Full PHP End-to-End & Module Test Suites (Tier 1):**
   ```powershell
   .\bin\php\php.exe tests/phase1_test.php; .\bin\php\php.exe tests/phase2_test.php; .\bin\php\php.exe tests/phase3_test.php; .\bin\php\php.exe tests/phase4_test.php; .\bin\php\php.exe tests/phase5_test.php; .\bin\php\php.exe tests/phase6_test.php; .\bin\php\php.exe tests/e2e_final_audit_test.php
   ```
3. **Execute Real HTTP Server Runtime Suite (Tier 2):**
   ```powershell
   python tests/run_real_http_suite.py
   ```
4. **Execute Static Verification & Simulation Suites:**
   ```powershell
   python tests/verify_cwfms_system.py; python tests/simulate_integration_workflow.py
   ```

---

## 4. Test Execution Evidence & Pass Matrix

| Test Suite / Tier | Execution Engine | Test Target | Executed | Passed | Failed | Blocked |
| :--- | :--- | :--- | :---: | :---: | :---: | :---: |
| **PHP Syntax Linting (Tier 1)** | `bin/php/php.exe -l` | All 35 repository PHP files | 35 | 35 | 0 | 0 |
| **Phase 1: Customer Association & Priority** | `bin/php/php.exe` | Schema, API validation, Priority logic | 37 | 37 | 0 | 0 |
| **Phase 2: Kanban Indicators & Customer Info** | `bin/php/php.exe` | Visual indicators (📎, 🎨, 📋, 💬, 🔥, ⭐) | 37 | 37 | 0 | 0 |
| **Phase 3: Date Filtering & Bulk Transitions** | `bin/php/php.exe` | Date range query, Bulk status update | 40 | 40 | 0 | 0 |
| **Phase 4: RBAC & Permission Boundaries** | `bin/php/php.exe` | Admin, Manager, Sales Exec guards | 42 | 42 | 0 | 0 |
| **Phase 5: Ira Sportswear Brand System** | `bin/php/php.exe` | Color palette, Logo/Favicon assets, UI | 48 | 48 | 0 | 0 |
| **Phase 6: WhatsApp Automations & Webhooks** | `bin/php/php.exe` | Template registry, Webhook parsing, Safe fallback | 37 | 37 | 0 | 0 |
| **E2E Scenarios A, B, C, D** | `bin/php/php.exe` | Admin, Manager, Exec, WhatsApp workflows | 38 | 38 | 0 | 0 |
| **Real HTTP Server Suite (Tier 2)** | `python` + `php -S` | Real HTTP requests against live server | 38 | 38 | 0 | 0 |
| **System Gate Verification** | `python` | Static AST/regex guards, migrations, runtime | 51 | 48 | 0 | 3 |
| **Specification Simulation** | `python` | In-memory workflow state machine & contracts | 22 | 22 | 0 | 0 |
| **TOTAL TEST ASSERTIONS** | — | — | **425** | **422** | **0** | **3** |

*Note: The 3 blocked checks represent live external credentials pending from client (Supabase `.env` deployment, Meta WhatsApp live dispatch, and Docker daemon).*
