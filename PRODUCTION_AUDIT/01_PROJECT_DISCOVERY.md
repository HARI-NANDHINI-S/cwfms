# Phase 1: Complete Project Discovery Audit

**Project:** Ira Sportswear / Studio 3 Interactive - Customer & Lead Management System (CWFMS)  
**Audit Date:** 2026-11-25  
**Auditor:** Senior Software Architect & Security Engineering Team  
**Git Commit / Branch:** `5208aecad95d5d8ed50600681d73a19b92741a37` on `main`  
**Execution Environment:** Windows (PowerShell), PHP 8.2.12 CLI / Built-in Server, Python 3.13.5  

---

## 1. Project Directory Structure

```text
cwfms/
├── .env                       # Local environment file (Ignored in git, contains project credentials)
├── .env.example               # Template environment configuration file
├── .gitignore                 # Excludes .env, backups, logs, dumps
├── .htaccess                  # Apache web server security headers, caching, rewrite rules
├── about.php                  # Public informational page: about Ira Sportswear
├── contact.php                # Public contact form & details page
├── index.php                  # Public marketing & customer relations landing page
├── login.php                  # User authentication interface & session initialization
├── logout.php                 # Session destruction & cookie cleanup
├── dashboard.php              # Primary authenticated application shell & customer management view
├── README.md                  # Quick-start instructions & project summary
├── DELIVERY_SUMMARY.md        # Delivery documentation of feature milestones
├── DEPLOYMENT_CHECKLIST.md    # Staging/production deployment checklist
├── DEVELOPER_REFERENCE.md     # Engineering reference guide & schemas
├── FINAL_PROJECT_DOCUMENTATION.md # Architecture & system documentation
├── FINAL_VERIFICATION_REPORT.md   # Functional verification report
├── IMPLEMENTATION_GUIDE.md    # Multi-phase setup guide
├── WHATSAPP_SETUP.md          # Meta WhatsApp Cloud API integration guide
├── SETUP.sh                   # Unix server provisioning script
├── sample_import.csv          # Sample CSV structure for customer bulk import
├── utils.py                   # Python CLI tool for import/export/stats validation
├── check_tables.py            # Diagnostic script for schema verification
├── test_roles.py              # RBAC verification script
├── test_trigger.py            # PostgreSQL trigger tester
├── test.php                   # Basic runtime verification probe
├── api/                       # RESTful API Endpoints
│   ├── activity.php           # Customer activity log endpoint
│   ├── auth.php               # Session state, user management & RBAC endpoint
│   ├── chart-data.php         # Analytics aggregation for dashboard charts
│   ├── customer.php           # Single customer fetch, search, and CSV export
│   ├── customers.php          # Customer CRUD, scoring, activation, and linked leads
│   ├── health.php             # Supabase database health probe
│   ├── leads.php              # Comprehensive lead management, filtering, upload & RBAC
│   ├── stats.php              # Dashboard metric counters
│   ├── whatsapp.php           # WhatsApp direct template messaging & log probe
│   └── whatsapp_webhook.php   # Meta Graph API status delivery webhook
├── assets/                    # Static Assets (Images, Favicons, Styles, Scripts)
│   ├── css/
│   │   ├── style.css          # Main application stylesheet (Dark/Light themes, components)
│   │   └── public.css         # Public pages aesthetic stylesheet
│   ├── js/
│   │   ├── app.js             # Core client application logic, customers table, search
│   │   └── animations.js      # UI animation triggers
│   ├── favicon.ico / favicon.png # Application icons
│   ├── ira_logo.png           # High-resolution brand logo
│   └── ira_logo_web.png       # Optimized web navbar brand logo
├── includes/                  # Core Business Logic & Platform Libraries
│   ├── config.php             # Supabase client, session hardening, RBAC, CSRF, sanitization
│   ├── whatsapp.php           # Meta WhatsApp Cloud API Service & template constructor
│   ├── public_header.php      # Reusable public layout header
│   └── public_footer.php      # Reusable public layout footer
├── pages/                     # Authenticated Sub-Pages & Dedicated Views
│   ├── customer-detail.php    # Customer 360 profile, activity timeline & linked leads
│   ├── import.php             # Drag-and-drop CSV bulk import wizard
│   ├── leads.php              # Lead management pipeline (Kanban, Table, Calendar views)
│   └── users.php              # User management & role assignment console (Admin only)
├── uploads/                   # Secured Local Storage for Uploaded Attachments
│   ├── .htaccess              # Strictly forbids direct HTTP execution & browsing
│   └── leads/                 # Custom design files and player roster files
├── tests/                     # Automated Test Suites & Previews
│   ├── e2e_final_audit_test.php   # Comprehensive 38-step end-to-end scenario test
│   ├── phase1_test.php - phase6_test.php # Phase verification test suites
│   ├── REAL_RUNTIME_TEST_PLAN.md  # Test strategy documentation
│   └── mock_supabase_server.py    # Mock server for offline test doubles
└── tools/                     # Operational Utilities
    ├── migrate.mjs            # Node-based schema migrator
    └── provision_user_password.php # Admin password hasher CLI
```

---

## 2. Technology Stack & Dependencies

| Layer | Component | Version / Implementation | Notes |
|---|---|---|---|
| **Backend Runtime** | PHP | 8.0+ (Tested on PHP 8.2.12 ZTS) | Native PHP without heavy frameworks (minimal memory footprint). Requires `curl`, `json`, `mbstring`. |
| **Database & Auth Engine** | Supabase / PostgreSQL | PostgreSQL 15+ via PostgREST | Managed PostgreSQL backend; REST communication via secure cURL queries. |
| **Frontend Foundation** | Vanilla HTML5, CSS3, ES6+ JS | Native DOM APIs | Fast, lightweight; zero build step required. |
| **CSS System** | Custom Athletic Design System | `style.css` | CSS Custom Properties, Dark/Light modes via `data-theme`. |
| **Client Libraries (CDN)** | Chart.js | 4.4.0 (UMD) | Interactive charts for monthly registrations and regional distribution. |
| **Animation Libraries (CDN)**| GSAP | 3.12.2 | Micro-interactions and entrance transitions on login/public views. |
| **CLI / Tooling** | Python | 3.10+ (Tested on 3.13.5) | Data migration, validation, and CLI administration utilities. |
| **Web Server Compatibility** | Apache / Nginx / PHP CLI | Apache 2.4+ (`.htaccess`) | Mod_headers, mod_rewrite, mod_deflate, mod_expires supported. |

---

## 3. Architecture & Data Flow

### 3.1 Frontend Architecture
- **Rendering Model:** Server-rendered PHP layout templates combined with client-side reactive DOM rendering (`fetch()` consuming JSON endpoints).
- **Navigation:** Multi-page architecture (MPA) with unified sidebar and theme toggle persistence (`localStorage`).
- **State Management:** Local JavaScript application state (`allLeads`, `allCustomers`, `filteredData`, `selectedLeadIds`, `currentPage`) managed without heavy external stores.
- **Views in Lead Module:** Tri-view switcher supporting:
  1. *Kanban Board:* 10 status columns with HTML5 Drag-and-Drop.
  2. *Table View:* Filterable tabular view with multi-item selection and bulk action toolbar.
  3. *Calendar View:* Monthly grid mapping enquiry dates and scheduled follow-ups.

### 3.2 Backend Architecture
- **API Style:** REST-like JSON endpoints under `/api/` returning standardized payloads:
  ```json
  {
    "success": true,
    "message": "Status description",
    "data": { ... },
    "timestamp": "2026-11-25 20:45:00"
  }
  ```
- **Authentication & Security Engine (`includes/config.php`):**
  - Session cookie security (`HttpOnly`, `SameSite=Lax`, `Strict_Mode=1`, conditional `Secure`).
  - Session inactivity timeout enforced at 7,200 seconds (2 hours).
  - CSRF token engine with validation on mutating HTTP methods (`POST`, `PUT`, `PATCH`, `DELETE`).
  - Input sanitization (`htmlspecialchars`, `strip_tags`, `normalizeUuidValue`).
- **Database Abstraction:** Direct HTTPS REST integration to Supabase using service-role or anon keys, implementing parameter sanitization, content-range pagination, and error translation.

---

## 4. Complete Feature Inventory & Current Status

| Feature ID | Feature Name | Implementation Files | Backend Endpoints | External / DB Deps | Current Status | Notes |
|---|---|---|---|---|:---:|---|
| **FEAT-01** | Landing & Public Pages | `index.php`, `about.php`, `contact.php` | N/A | None | **PASS** | Fully functional responsive marketing pages. |
| **FEAT-02** | User Authentication | `login.php`, `logout.php` | Direct DB via `config.php` | Supabase `user_roles` | **PASS** | Rate limited; password hash verified via `password_verify()`. |
| **FEAT-03** | User Management & RBAC | `pages/users.php` | `api/auth.php` | Supabase `user_roles` | **PASS** | Admin-exclusive role allocation and account provisioning. |
| **FEAT-04** | Customer Directory & Stats | `dashboard.php` | `api/customers.php`, `api/stats.php` | Supabase `customers` | **PASS** | Live search, State/Country filters, animated counters. |
| **FEAT-05** | Customer 360 Detail View | `pages/customer-detail.php` | `api/customer.php`, `api/customers.php` | Supabase `customers`, `leads` | **PASS** | Shows complete customer details, activity logs, and linked active leads. |
| **FEAT-06** | CSV Customer Bulk Import | `pages/import.php` | `api/customers.php` | Supabase `customers` | **PARTIAL** | UI and client parsing complete; **Defect**: line 83 calls undefined `hasRole('admin')`. |
| **FEAT-07** | Lead Capture & Dynamic Forms | `pages/leads.php` | `api/leads.php` | Supabase `leads`, `customers` | **PASS** | Dynamic fields based on Design Source and Player Roster. |
| **FEAT-08** | Lead Kanban Pipeline | `pages/leads.php` | `api/leads.php` | Supabase `leads` | **PASS** | 10 status stages, HTML5 drag-and-drop, card indicators. |
| **FEAT-09** | Lead Multi-View Switcher | `pages/leads.php` | `api/leads.php` | Supabase `leads` | **PASS** | Instant toggle between Kanban, Table, and Calendar views. |
| **FEAT-10** | Lead Bulk Operations | `pages/leads.php` | `api/leads.php?action=bulk_update` | Supabase `leads` | **PASS** | Bulk status updates across filtered selections. |
| **FEAT-11** | File Upload Handling | `pages/leads.php`, `api/leads.php` | `api/leads.php` | Local disk `/uploads/leads` | **PASS** | Multi-file upload, 10MB limit, strict MIME/extension validation. |
| **FEAT-12** | WhatsApp Customer Communication | `includes/whatsapp.php`, `api/whatsapp.php` | `api/whatsapp.php` | Meta Graph API | **PASS** | Template messaging with parameter substitution and fallback logging. |
| **FEAT-13** | WhatsApp Delivery Webhooks | `api/whatsapp_webhook.php` | `api/whatsapp_webhook.php` | Meta Graph API | **PASS** | HMAC-SHA256 signature verification and delivery status tracking. |
| **FEAT-14** | Database Health Monitoring | `api/health.php` | `api/health.php` | Supabase REST | **PASS** | Detailed latency and connection state diagnostic response. |
| **FEAT-15** | Orders & Payments Modules | `dashboard.php`, `customer-detail.php` | N/A | None (Phase 3 scope) | **NOT IMPLEMENTED** | Intentionally marked as Phase 3 placeholders per PDF spec. |

---

## 5. Critical Initial Findings & Vulnerabilities Discovered

1. **Undefined Function Crash in `pages/import.php` (`P0 - Critical`):**
   - Line 83 invokes `hasRole('admin')`.
   - `hasRole()` is not defined anywhere in the codebase. Any user accessing `pages/import.php` triggers an uncaught Fatal Error.
2. **Insecure Session-Based Rate Limiting (`P1 - High`):**
   - `checkLoginRateLimit()` in `includes/config.php` stores attempts exclusively in `$_SESSION`.
   - Automated brute-force attacks omitting session cookies bypass rate limiting completely.
3. **Insecure SSL Verification Downgrade in cURL (`P1 - High`):**
   - `includes/config.php` lines 347-353: on SSL failure, cURL automatically switches `CURLOPT_SSL_VERIFYPEER` to `false` and `CURLOPT_SSL_VERIFYHOST` to `0`.
   - In production, this introduces Man-in-the-Middle (MitM) vulnerabilities.
4. **Hardcoded Fallback Webhook Secret (`P2 - Medium`):**
   - `includes/config.php` line 870 specifies a hardcoded fallback string for `WHATSAPP_WEBHOOK_VERIFY_TOKEN`.
5. **Apache RewriteBase Configuration (`P2 - Medium`):**
   - `.htaccess` hardcodes `RewriteBase /customer-management/`, which conflicts with deployments at virtual host roots or different folder names.
6. **Missing Strict Content Security Policy (CSP) Headers (`P2 - Medium`):**
   - While `.htaccess` sets `X-Frame-Options` and `X-Content-Type-Options`, it lacks a standard `Content-Security-Policy` header.
