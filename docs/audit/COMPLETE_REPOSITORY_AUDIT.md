# Complete Repository & Architectural Technical Audit

**Project:** CWFMS — Customer and Workflow Management System  
**Organization:** Studio 3 Interactive / Ira Sportswear  
**Repository:** `https://github.com/HARI-NANDHINI-S/cwfms`  
**Audit Date:** November 20, 2026  
**Auditor:** Antigravity AI Assistant  

---

## 1. System Overview & Technology Stack

The **Customer Workflow Management System (CWFMS)** is a custom CRM and manufacturing pipeline solution developed for Ira Sportswear. It is designed to manage team inquiries, customer profiles, custom apparel requirements, vector art assets, player rosters, and sales pipeline workflows from initial enquiry to factory dispatch.

### Technology Stack Inventory
- **Backend Language:** PHP 8.1+ (Procedural and OOP service architecture)
- **Web Server:** Apache 2.4+ (configured with `.htaccess`, URL rewrites, and security headers)
- **Database Backend:** PostgreSQL 15+ hosted on Supabase (accessed via PostgREST REST API v1 using cURL)
- **External Communications:** Meta WhatsApp Cloud API (Graph API v21.0)
- **Frontend Architecture:** Native server-rendered PHP templates, vanilla ECMAScript 6+ JavaScript, CSS3 variables, flexbox and CSS grid
- **Visual Assets:** Ira Sportswear brand identity, GSAP 3.12+ animations, Chart.js 4.4+
- **Test Framework:** Standalone PHP CLI regression test suites and visual HTML preview suites

---

## 2. Complete File & Directory Structure

```
cwfms/
├── .htaccess                       # Apache URL routing, security headers & extension deny rules
├── .env.example                    # Environment configuration template (feature branch)
├── .gitignore                      # Git exclusion rules for .env and uploads (feature branch)
├── README.md                       # High-level project overview
├── DELIVERY_SUMMARY.md             # Customer management module delivery notes
├── DEPLOYMENT_CHECKLIST.md         # Deployment verification steps
├── DEVELOPER_REFERENCE.md          # Technical documentation & cURL references
├── IMPLEMENTATION_GUIDE.md         # Setup and configuration walkthrough
├── FINAL_PROJECT_DOCUMENTATION.md  # Comprehensive architectural docs (feature branch)
├── FINAL_VERIFICATION_REPORT.md    # Phase 1-6 testing and audit report (feature branch)
├── WHATSAPP_SETUP.md               # Meta Cloud API integration instructions (feature branch)
├── SETUP.sh                        # Bash setup and directory preparation script
├── check_tables.py                 # Python test script for Supabase tables
├── test_roles.py                   # Python test script for user roles
├── test_trigger.py                 # Python test script for updated_at trigger
├── utils.py                        # Python CLI utility for CSV import/export
├── index.php                       # Public home landing page
├── about.php                       # Public about page
├── contact.php                     # Public contact page
├── login.php                       # Authentication portal
├── logout.php                      # Session destruction script
├── dashboard.php                   # Main customer management dashboard
├── test.php                        # Diagnostic test file
├── test_user_insert.php            # User insert diagnostic script
├── test_user_signup.php            # User signup diagnostic script
├── sample_import.csv               # Sample customer import template
├── supabase_setup.sql              # Base database schema (customers, user_roles, leads)
├── migration_v2.sql                # Profile scoring trigger & is_active flags
├── migration_v3_leads.sql          # Lead priority column and relational indexes
├── migration_v4_roles.sql          # Role constraints expansion
├── migration_v6_whatsapp.sql       # WhatsApp audit table & message tracking
├── api/
│   ├── activity.php                # Customer activity audit log API
│   ├── auth.php                    # Session verification and user administration API
│   ├── chart-data.php              # Dashboard analytics chart data API
│   ├── customer.php                # Single customer fetch, search, and delete API
│   ├── customers.php               # Customer list, create, update, and toggle status API
│   ├── health.php                  # Database health and latency probe API
│   ├── stats.php                   # High-level dashboard KPI statistics API
│   ├── leads.php                   # Complete Lead CRUD, Kanban, table, calendar & bulk API
│   ├── whatsapp.php                # Manual WhatsApp message dispatch API
│   └── whatsapp_webhook.php        # Meta WhatsApp delivery callback webhook
├── assets/
│   ├── css/
│   │   ├── style.css               # Core application stylesheet, themes, and Kanban styles
│   │   ├── modern-theme.css        # Additional typography and theme variables
│   │   └── public.css              # Landing page and login visual design
│   ├── js/
│   │   ├── animations.js           # Public UI GSAP animations
│   │   └── app.js                  # Main customer module frontend controller
│   ├── favicon.ico                 # Brand favicon
│   ├── favicon.png                 # PNG brand favicon
│   ├── ira_logo.png                # High-resolution brand logo
│   └── ira_logo_web.png            # Web-optimized brand logo
├── includes/
│   ├── config.php                  # Supabase helper class, RBAC guards, session init
│   ├── public_header.php           # Public header component
│   ├── public_footer.php           # Public footer component
│   └── whatsapp.php                # WhatsAppService class & template registry
├── pages/
│   ├── customer-detail.php         # Customer 360-degree profile view and edit interface
│   ├── import.php                  # CSV bulk customer import workflow
│   ├── leads.php                   # Lead management module (Kanban, Table, Calendar)
│   └── users.php                   # System user administration interface
├── tests/                          # Automated PHP test suites & visual preview pages
│   ├── e2e_final_audit_test.php    # Final end-to-end audit test suite
│   ├── kanban_visual_preview.html  # Standalone visual preview for Kanban
│   ├── lead_module_test.php        # Lead module basic test
│   ├── phase1_test.php             # Phase 1 unit test suite
│   ├── phase2_test.php             # Phase 2 unit test suite
│   ├── phase3_test.php             # Phase 3 unit test suite
│   ├── phase3_visual_preview.html  # Visual preview for table bulk actions
│   ├── phase4_test.php             # Phase 4 RBAC test suite
│   ├── phase5_branded_showcase.html# Visual preview for branding & typography
│   ├── phase5_test.php             # Phase 5 branding test suite
│   ├── phase6_test.php             # Phase 6 WhatsApp test suite
│   └── system_audit_visual_showcase.html # Comprehensive visual preview showcase
├── tools/
│   └── migrate.mjs                 # Node.js automated migration utility
└── uploads/
    ├── .htaccess                   # Hardened security: disables PHP execution
    └── leads/                      # Storage directory for custom art and rosters
```

---

## 3. Branch Analysis & Commit History

1. **`main` (`origin/main`)**:
   - Commit: `e35117ad4a52a2faa1de8dcd9a633042f0006ffb` ("Initial commit", June 16, 2026).
   - Scope: Customer Management Module, Supabase integration, CSV import, basic roles. Completely lacks Lead Management module.
2. **`feature/lead-management-module` (`origin/feature/lead-management-module`)**:
   - Commit: `67b31bb00a1650525639e3807c28f27f11f01d33` ("Complete Ira Sportswear Lead Management Module (Phases 1-6)", Nov 17, 2026).
   - Scope: Adds `pages/leads.php`, `api/leads.php`, WhatsApp service, migrations v3–v6, test suites.
   - Status: Ahead of `main` by 1 commit.

---

## 4. Frontend Architecture Audit

### 4.1 Customer Management Interface (`dashboard.php`, `pages/customer-detail.php`)
- **Dashboard:** Features KPI summary cards (Total, Active, Inactive, Added Today), Chart.js registration analytics, quick customer search, country/state filters, customer data table with profile score progress bars, and modal dialogs for customer creation and status toggling.
- **Customer Detail View:** Renders complete contact card, shipping address card, real-time profile completeness score (0–100%), and recent activity log.
- **Defects:**
  - `pages/customer-detail.php` has no section displaying the customer's linked leads.
  - Sidebar navigation in `customer-detail.php` lists "Leads" as `Coming Soon`.

### 4.2 Lead Management Interface (`pages/leads.php`)
- **Kanban Board:** 10 workflow columns with dynamic card counters, horizontal scrolling, HTML5 drag-and-drop between columns, and error rollback.
- **Lead Cards:** Header with Team/Academy name and Contact name; body with Sport, Products, Enquiry Date, and Assigned Rep; priority left border (Red/Orange/Blue); 6 visual indicator badges (📎, 🎨, 📋, 💬, 🔥, ⭐).
- **Table View:** Multi-select checkboxes for bulk status, priority, and sales rep updates; CSV export streaming.
- **Calendar View:** Monthly scheduling grid displaying enquiries on their enquiry date.
- **Lead Modal:** Comprehensive modal supporting all conditional fields (Kit Builder $\to$ Submission ID, Design Link $\to$ URL, Custom Design $\to$ Multi-upload, Player Roster $\to$ File upload).

---

## 5. Backend Architecture & API Audit

### 5.1 Supabase HTTP Client (`includes/config.php`)
- Implements a resilient cURL wrapper connecting to Supabase PostgREST v1.
- Features automatic filter sanitization, response header extraction, SSL fallback handling, and database error translation.

### 5.2 API Controllers
- **`api/customers.php`:** Supports `GET ?action=list` (with active/inactive filters), `POST action=create` (with duplicate mobile prevention), `POST action=update`, and `POST action=toggle_status`.
- **`api/customer.php`:** Supports `GET ?action=get&id=...`, `GET ?action=search&q=...`, and `DELETE ?action=delete&id=...` (soft delete with activity log).
- **`api/leads.php`:** Supports full CRUD, search, filtering, calendar grouping, bulk updates, and CSV streaming.
- **`api/auth.php`:** Supports session check (`GET action=me`), user listing (`GET action=users`), user creation, role assignment, and active status toggle.
- **`api/whatsapp.php` & `api/whatsapp_webhook.php`:** Manual template dispatch, delivery status history, and Meta webhook verification/callback.

---

## 6. Security & Vulnerability Summary

1. **Critical:** `login.php` relies on hardcoded password check `($password === 'password123')`.
2. **Critical:** `includes/config.php` contains the plaintext Supabase Service Role Key.
3. **High:** Row Level Security policies on Supabase use `USING (true) WITH CHECK (true)`, allowing unauthenticated modifications if anon key is discovered.
4. **Medium:** File uploads validate extensions only; missing binary MIME type verification.
5. **Mitigated:** CSRF protected by `SameSite=Lax` cookies; uploads protected by `uploads/.htaccess` disabling script execution.

---

## 7. Technical Debt & Code Quality Assessment

1. **Redundant Customer Endpoints:** Customer operations are split across `api/customers.php` (list, create, update, toggle) and `api/customer.php` (get, search, delete). These should be consolidated into a single unified REST controller.
2. **Missing Customer Detail Integration:** The feature branch added `pages/leads.php`, but neglected to update `pages/customer-detail.php` to query and display leads belonging to that customer.
3. **Test Infrastructure Independence:** Test files in `tests/` are completely self-contained with mock assertions, allowing full validation without external network calls.
