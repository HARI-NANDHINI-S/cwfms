# Ira Sportswear — Customer & Workflow Management System (CWFMS)

> **Enterprise Customer & Lead Management Platform (Phase 1)**  
> Developed for **Ira Sportswear** & **Studio 3 Interactive**

[![PHP 8.0+](https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![PostgreSQL](https://img.shields.io/badge/Database-PostgreSQL%20%2F%20Supabase-336791?style=flat-square&logo=postgresql&logoColor=white)](https://supabase.com/)
[![Meta WhatsApp API](https://img.shields.io/badge/Integration-WhatsApp%20Cloud%20API%20v21.0-25D366?style=flat-square&logo=whatsapp&logoColor=white)](https://developers.facebook.com/docs/whatsapp/cloud-api)
[![Automated Tests](https://img.shields.io/badge/Automated%20Tests-289%20Passed%20%2F%20100%25-success?style=flat-square)](#-automated-testing--quality-assurance)
[![License](https://img.shields.io/badge/License-Proprietary-red?style=flat-square)](#)

---

## 📖 Overview

The **Customer & Workflow Management System (CWFMS)** is a high-performance web platform designed to streamline customer relationship management and sales lead workflows for custom athletic apparel and team sportswear.

The platform provides a centralized database for customer profiles, an interactive 10-stage Lead Management pipeline (with Kanban, Table, and Calendar views), dynamic file upload handling for custom design assets and player rosters, automated Meta WhatsApp Cloud API messaging, and strict Role-Based Access Control (RBAC).

---

## ✨ Key Features

### 👤 1. Customer Management Module
- **Full Customer Lifecycle (CRUD):** Create, update, deactivate/activate, and soft-delete customer profiles.
- **Profile Quality Score:** Automated scoring engine (0–100) assessing profile completeness.
- **Duplicate Prevention:** Enforces uniqueness across mobile numbers per international dial code.
- **Fast Search & Regional Filtering:** Live debounced search by name, team/academy, mobile, and city; filtering by state and country.
- **Customer 360° Profile View:** Complete contact and shipping addresses, visual activity log timeline, and relational table of active leads.
- **Bulk CSV Import & Export:** Drag-and-drop CSV importer with field validation, format normalization, and filtered data export.

### 📋 2. Lead Management Module & Pipeline
- **Multi-Channel Capture:** Enquiries captured from *Kit Builder*, *Website*, *WhatsApp*, and *Email*.
- **Athletic Sportswear Specifics:** Multi-selection across 13 apparel items (Playing T-Shirt, Trouser, Travel Polo, Jacket, etc.) and sports categories (Cricket, Soccer, Pickleball, Tennis, Badminton, etc.).
- **Dynamic Field Visibility:**
  - *Kit Builder* $\rightarrow$ Submission ID input.
  - *Design Link* $\rightarrow$ URL validation.
  - *Custom Design* $\rightarrow$ Multi-file upload (PDF, AI, EPS, PNG, JPG, ZIP; $\le$ 10 MB).
  - *Player Roster Received* $\rightarrow$ File upload (XLSX, XLS, CSV, PDF; $\le$ 10 MB).
- **Security-Hardened File Uploads:** Binary MIME magic byte verification, strict extension allow-lists, and path-traversal defense.

### 📊 3. Tri-View Visualization
Switch seamlessly between three distinct views:
1. **Kanban Board:** 10 sequential workflow stages with card counts and native HTML5 Drag-and-Drop:
   - *Received* $\rightarrow$ *Analysis* $\rightarrow$ *Awaiting Clarification* $\rightarrow$ *Order Sheet Prepared* $\rightarrow$ *Verified* $\rightarrow$ *Awaiting Approval* $\rightarrow$ *Approved* $\rightarrow$ *Awaiting Payment* $\rightarrow$ *Moved to Production* $\rightarrow$ *Lost*.
2. **Table View:** Dense tabular format supporting multi-row selection and batch status updates.
3. **Calendar View:** Monthly scheduling grid mapping enquiry dates and follow-ups.

### 🏷️ 4. Visual Card Indicators & Priority Color Coding
- **Priority Left Borders:** High (🔴 Red), Medium (🟠 Orange), Low (🔵 Blue).
- **Real-Time Indicators:**
  - 📎 **Attachments Available:** Design or roster files attached.
  - 🎨 **Design Uploaded:** Custom artwork or Kit Builder link present.
  - 📋 **Player Roster Received:** Player roster file provided.
  - 💬 **WhatsApp Delivery Status:** Live delivery states (`sent`, `delivered`, `read`, `failed`).
  - 🔥 **Urgent:** High-priority lead indicator.
  - ⭐ **Repeat Customer:** Identifies returning clients with previous orders.

### 💬 5. Meta WhatsApp Cloud API Integration
- **Automated Workflow Notifications:** Automatically sends approved customer notifications when leads move between key stages.
- **Approved Template Library:** Pre-configured templates with parameter substitution (`ira_lead_acknowledgement`, `ira_clarification_request`, `ira_order_sheet_update`, `ira_order_approved`, `ira_moved_to_production`).
- **Real-Time Webhook Engine:** Incoming webhook endpoint verifying Meta's `X-Hub-Signature-256` HMAC-SHA256 signature and tracking message lifecycle (`sent` $\rightarrow$ `delivered` $\rightarrow$ `read`).
- **Fail-Safe Design:** Outbound messaging failures are caught and logged without disrupting core lead updates.

### 🔒 6. Enterprise Security & RBAC
- **Strict Role-Based Access Control:**
  - **Admin:** Full access (Create, Edit, Delete, Assign, Status Transition, User Management).
  - **Sales Manager:** Global visibility (Create, Edit, Assign, Status Transition; No Delete).
  - **Sales Executive:** Ownership-scoped access (Create, Edit own leads, Status Transition on own leads; No Reassign, No Delete).
- **Persistent Rate Limiting:** Server-side file-locked IP rate limiter preventing automated brute-force login attacks.
- **CSRF & Session Hardening:** Strict session cookies (`HttpOnly`, `SameSite=Lax`, strict mode), 2-hour inactivity timeouts, and cryptographic CSRF token validation on mutating HTTP methods.
- **Content Security Policy (CSP):** Strict HTTP security headers configured in `.htaccess`.

---

## 🚀 Quick Setup & Installation

### Step 1 — Prerequisites
- **Web Server:** Apache 2.4+ (with `mod_rewrite`, `mod_headers`, `mod_expires`, `mod_deflate`) or Nginx.
- **PHP:** Version 8.0+ (Requires `curl`, `json`, `mbstring`, `fileinfo` extensions).
- **Database:** Supabase account (PostgreSQL 15+).

### Step 2 — Database Schema Setup
1. Log in to your [Supabase Dashboard](https://supabase.com).
2. Open the **SQL Editor**.
3. Copy and run the entire contents of [supabase_setup.sql](supabase_setup.sql).

### Step 3 — Environment Configuration
Copy the template `.env.example` to `.env`:
```bash
cp .env.example .env     # Linux / macOS
copy .env.example .env   # Windows
```

Edit `.env` and set your credentials:
```env
# Supabase Configuration
SUPABASE_URL=https://<your-project-ref>.supabase.co
SUPABASE_ANON_KEY=<your-anon-key>
SUPABASE_SERVICE_KEY=<your-service-role-key>

# Application Mode
APP_ENV=development      # Set to 'production' for live deployment

# WhatsApp Cloud API (Optional for local testing / Required for live messaging)
WHATSAPP_TOKEN=<your-meta-access-token>
WHATSAPP_PHONE_NUMBER_ID=<your-phone-number-id>
WHATSAPP_BUSINESS_ACCOUNT_ID=<your-business-account-id>
WHATSAPP_APP_SECRET=<your-meta-app-secret>
WHATSAPP_WEBHOOK_VERIFY_TOKEN=<your-webhook-verify-token>
WHATSAPP_AUTOMATIONS_ENABLED=1
```

### Step 4 — Run the Project
Start the built-in development server:
```bash
php -S localhost:8000
```
Open [http://localhost:8000](http://localhost:8000) in your browser.

### Step 5 — Default Credentials
| Email | Role | Default Password | Access Level |
|---|---|:---:|---|
| `admin@studio3.com` | Admin | `password123` | Full access, user management & deletion |
| `manager@studio3.com` | Sales Manager | `password123` | Create, edit, assign, status transition |
| `staff@studio3.com` | Sales Executive | `password123` | Create, edit own assigned leads |

---

## 📁 File Structure

```text
cwfms/
├── .env                       # Local environment variables (Excluded from git)
├── .env.example               # Sanitized template environment configuration
├── .htaccess                  # Apache 2.4 security headers, CSP, rewrite & size limits
├── about.php                  # Public informational page
├── contact.php                # Public contact page
├── index.php                  # Public marketing & customer relations landing page
├── login.php                  # Authentication page with demo quick-login pills
├── logout.php                 # Session termination & cookie destruction
├── dashboard.php              # Authenticated management shell & customer directory
├── supabase_setup.sql         # Consolidated database schema, triggers & seed data
├── utils.py                   # Python CLI utility for data validation & CLI operations
├── api/                       # RESTful JSON Endpoints
│   ├── activity.php           # Customer activity audit log
│   ├── auth.php               # Session state, user admin & password management
│   ├── chart-data.php         # Analytics aggregation for dashboard charts
│   ├── customer.php           # Single customer retrieval & CSV export
│   ├── customers.php          # Customer CRUD, scoring, activation & linked leads
│   ├── health.php             # Database health & latency check endpoint
│   ├── leads.php              # Pipeline CRUD, file uploads, filters & bulk updates
│   ├── stats.php              # Dashboard animated metric counters
│   ├── whatsapp.php           # Direct WhatsApp template dispatch & logs
│   └── whatsapp_webhook.php   # Meta Graph API webhook endpoint
├── assets/                    # Static Assets
│   ├── css/style.css          # Main stylesheet with Dark/Light theme variables
│   ├── css/public.css         # Public pages stylesheet
│   ├── js/app.js              # Client state, table pagination, debounced search
│   ├── js/animations.js       # UI micro-animations
│   └── ira_logo_web.png       # Optimized web navigation brand logo
├── includes/                  # Core Business Logic & Security
│   ├── config.php             # Supabase client, session security, RBAC, rate limiter
│   └── whatsapp.php           # Meta WhatsApp Cloud API Service & templates
├── pages/                     # Application Views
│   ├── customer-detail.php    # Customer 360 profile, timeline & linked leads
│   ├── import.php             # Drag-and-drop CSV bulk import wizard
│   ├── leads.php              # Tri-view Lead Management (Kanban, Table, Calendar)
│   └── users.php              # User administration & role management (Admin only)
├── uploads/                   # Secure Attachment Storage
│   ├── .htaccess              # Strictly blocks direct HTTP script execution
│   └── leads/                 # Uploaded designs and player rosters
├── tests/                     # Automated Test Harness
│   ├── e2e_final_audit_test.php   # 38-step End-to-End scenario test suite
│   ├── phase1_test.php - phase6_test.php # Phase verification suites
│   └── mock_supabase_server.py    # Offline test double server
└── PRODUCTION_AUDIT/          # Comprehensive 12-Report Production Audit Directory
    ├── 01_PROJECT_DISCOVERY.md
    ├── 02_SECURITY_HARDENING.md
    ├── 03_FEATURE_HARDENING.md
    ├── 04_FRONTEND_OPTIMIZATION.md
    ├── 05_BACKEND_HARDENING.md
    ├── 06_DATABASE_REVIEW.md
    ├── 07_PERFORMANCE_OPTIMIZATION.md
    ├── 08_RELIABILITY_HARDENING.md
    ├── 09_TESTING_AND_QA.md
    ├── 10_DEPLOYMENT_READINESS.md
    ├── 11_REGRESSION_REPORT.md
    └── FINAL_PRODUCTION_READINESS_REPORT.md
```

---

## 🧪 Automated Testing & Quality Assurance

The application includes a comprehensive test suite covering unit logic, RBAC, input validation, and end-to-end user workflows.

### Run All Test Suites
```bash
# Run the 38-step End-to-End Scenario Suite
php tests/e2e_final_audit_test.php

# Run Individual Phase Verification Suites
php tests/phase1_test.php    # Customer Association & Priority Support (37 tests)
php tests/phase2_test.php    # Kanban Indicators & Card Layout (42 tests)
php tests/phase3_test.php    # Views, Date Filtering & Bulk Operations (35 tests)
php tests/phase4_test.php    # RBAC & Permission Enforcement (40 tests)
php tests/phase5_test.php    # Design System & Official Brand Identity (48 tests)
php tests/phase6_test.php    # WhatsApp Cloud API & Webhooks (37 tests)
```

**Total Automated Assertions: 289 / 289 Passed (100% Pass Rate).**

---

## 🐍 Python CLI Tools

A dedicated Python CLI is included for offline data processing and administration:

```bash
export SUPABASE_URL="https://<your-project-ref>.supabase.co"
export SUPABASE_SERVICE_KEY="<your-service-role-key>"

# Validate customer CSV format
python utils.py validate --file customers.csv

# Test CSV import (dry-run)
python utils.py import --file customers.csv --dry-run

# Execute live CSV import
python utils.py import --file customers.csv

# Export customers to CSV
python utils.py export --format csv

# Print database statistics
python utils.py stats
```

---

## 📑 Production Audit Documentation

Comprehensive production hardening documentation is preserved in the [PRODUCTION_AUDIT/](PRODUCTION_AUDIT/) directory:
- [01_PROJECT_DISCOVERY.md](PRODUCTION_AUDIT/01_PROJECT_DISCOVERY.md) — Architecture discovery & feature inventory.
- [02_SECURITY_HARDENING.md](PRODUCTION_AUDIT/02_SECURITY_HARDENING.md) — OWASP Top 10 assessment & security fixes.
- [03_FEATURE_HARDENING.md](PRODUCTION_AUDIT/03_FEATURE_HARDENING.md) — Functional validation & state tracing.
- [06_DATABASE_REVIEW.md](PRODUCTION_AUDIT/06_DATABASE_REVIEW.md) — Relational integrity, check constraints & RLS policies.
- [10_DEPLOYMENT_READINESS.md](PRODUCTION_AUDIT/10_DEPLOYMENT_READINESS.md) — Apache/Nginx production deployment guide.
- [FINAL_PRODUCTION_READINESS_REPORT.md](PRODUCTION_AUDIT/FINAL_PRODUCTION_READINESS_REPORT.md) — Executive sign-off report.

---

## 🔮 Roadmap (Phase 2 & Phase 3)

- **Phase 2:** WhatsApp Chatbot interactive menus, automatic payment reminders, customer tagging (School, Club, Retail), and multiple delivery addresses per customer.
- **Phase 3:** Dedicated Orders module, production floor kanban (Printing $\rightarrow$ Stitching $\rightarrow$ QC $\rightarrow$ Dispatched), and integrated payment gateway tracking.

---

*Built with precision for **Ira Sportswear** by **Studio 3 Interactive***
