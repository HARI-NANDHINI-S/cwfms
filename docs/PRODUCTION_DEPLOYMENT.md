# CWFMS Production Deployment Guide

**Application:** Customer & Workflow Management System (CWFMS)  
**Client:** Ira Sportswear / Studio 3 Interactive  
**Target Environment:** Linux / Windows Apache HTTP Server with PHP 8.1+ & Supabase PostgreSQL  
**Version:** 1.0.0 (Production Release)  
**Date:** November 2026

---

## 1. System Requirements & Prerequisites

### 1.1 Web Server & PHP Runtime
- **PHP Version:** PHP 8.1.0 or higher (PHP 8.2+ recommended)
- **Required PHP Extensions:**
  - `curl` (for Supabase REST and Meta Cloud API communications)
  - `fileinfo` (for binary MIME type magic byte verification on uploads)
  - `json` (for API payloads and configuration parsing)
  - `openssl` (for CSRF token generation and cryptographic operations)
  - `session` (for secure server-side session management)
  - `mbstring` (for multilingual customer text and sanitization)
- **Web Server:**
  - Apache HTTP Server 2.4+ (or Nginx with PHP-FPM)
  - Modules required: `mod_rewrite`, `mod_headers`, `mod_mime`

### 1.2 Database Runtime
- **Platform:** Supabase Cloud or Self-Hosted Supabase (PostgreSQL 15+)
- **Extensions:** `pgcrypto` / `uuid-ossp` enabled by default in Supabase

---

## 2. Directory Structure & Permissions

Verify the following production directory structure:

```
cwfms/
├── .env                          # Production environment variables (chmod 600, not in git)
├── .env.example                  # Template configuration file
├── .gitignore                    # Ensures .env and backups are excluded
├── dashboard.php                 # Customer Management Dashboard
├── login.php                     # Bcrypt authentication portal
├── logout.php                    # Session invalidation & cleanup
├── api/
│   ├── auth.php                  # User administration & password management
│   ├── customers.php             # Customer CRUD & linked leads
│   ├── leads.php                 # Lead pipeline, Kanban & file upload API
│   └── whatsapp.php              # Meta Cloud API dispatch & audit logs
├── assets/
│   ├── css/style.css             # Unified application stylesheet
│   └── js/
│       ├── app.js                # Core JS & automatic CSRF interceptor
│       └── animations.js         # Interactive UI transitions
├── includes/
│   ├── config.php                # Security foundation, DB client & CSRF engine
│   └── whatsapp.php              # WhatsApp Cloud API service & audit engine
├── pages/
│   ├── customer-detail.php       # Full customer profile with integrated leads pipeline
│   ├── import.php                # Customer CSV bulk importer
│   ├── leads.php                 # Leads Kanban, Table & Calendar pipeline
│   └── users.php                 # User management & RBAC administration
├── uploads/
│   ├── .htaccess                 # Disables PHP script execution in uploads
│   └── leads/                    # Lead design briefs & order sheets (chmod 775)
└── docs/                         # Audit reports & production manuals
```

### 2.1 File System Permissions (Linux / Apache)
```bash
# Set ownership to web server user (e.g., www-data or apache)
sudo chown -R www-data:www-data /var/www/html/cwfms

# Standard file & directory permissions
find /var/www/html/cwfms -type d -exec chmod 755 {} \;
find /var/www/html/cwfms -type f -exec chmod 644 {} \;

# Secure uploads directory (writable by web server, but scripts disabled via .htaccess)
chmod -R 775 /var/www/html/cwfms/uploads/leads

# Secure environment configuration file
chmod 600 /var/www/html/cwfms/.env
```

---

## 3. Database Migration Sequence

All database migrations are idempotent (`IF NOT EXISTS`, `DO $$` blocks) and must be executed in the following sequential order via the Supabase SQL Editor:

| Step | Script File | Description | Impact |
|:---:|:---|:---|:---|
| **1** | `supabase_setup.sql` | Baseline tables (`customers`, `activity_logs`, `user_roles`) | Establishes core relational foundation |
| **2** | `migration_v2.sql` | Profile completeness score and customer schema extensions | Adds customer scoring and state tracking |
| **3** | `migration_v3_leads.sql` | Leads core table (`leads`) and lead activity timeline | Establishes the Athletic Team Orders pipeline |
| **4** | `migration_v4_roles.sql` | Canonical roles (`admin`, `sales_manager`, `sales_executive`) | Enforces business role hierarchy |
| **5** | `migration_v5_auth.sql` | Password hashing column (`password_hash`) & seed accounts | Eliminates demo password check; seeds bcrypt hashes |
| **6** | `migration_v6_whatsapp.sql` | WhatsApp message audit log (`lead_whatsapp_messages`) | Tracks template delivery, message IDs & timestamps |
| **7** | `migration_v7_rls_hardening.sql` | Hardens Row Level Security (RLS) across all tables | Enforces `service_role` and `authenticated` policies |

### Verification Command:
In the Supabase SQL Editor, verify all tables exist:
```sql
SELECT table_name 
FROM information_schema.tables 
WHERE table_schema = 'public' 
ORDER BY table_name;
```
Expected output includes:
- `activity_logs`
- `customers`
- `lead_activity_log`
- `lead_whatsapp_messages`
- `leads`
- `user_roles`

---

## 4. Environment Configuration (`.env`)

Copy `.env.example` to `.env` in the project root:

```bash
cp .env.example .env
```

Populate the configuration values:

```ini
# ============================================================
# Supabase Configuration
# ============================================================
SUPABASE_URL=https://<your-project-ref>.supabase.co
SUPABASE_PROJECT_REF=<your-project-ref>
SUPABASE_ANON_KEY=<your-public-anon-key>
SUPABASE_SERVICE_KEY=<your-confidential-service-role-key>
SUPABASE_DB_PASSWORD=<your-database-password>

# ============================================================
# Application Mode
# ============================================================
APP_ENV=production

# ============================================================
# WhatsApp Cloud API Configuration
# ============================================================
WHATSAPP_API_VERSION=v21.0
WHATSAPP_TOKEN=<your-meta-system-user-access-token>
WHATSAPP_PHONE_NUMBER_ID=<your-meta-phone-number-id>
WHATSAPP_BUSINESS_ACCOUNT_ID=<your-meta-waba-id>
WHATSAPP_WEBHOOK_VERIFY_TOKEN=ira_sportswear_wh_verify_secret_token_2026
WHATSAPP_APP_SECRET=<your-meta-app-secret>
WHATSAPP_AUTOMATIONS_ENABLED=1
WHATSAPP_AUTOMATION_STAGES=Received,Awaiting Clarification,Approved,Moved to Production
```

> [!IMPORTANT]
> The `SUPABASE_SERVICE_KEY` has full administrative access and bypasses RLS. Never commit `.env` to GitHub or expose it to client-side browsers.

---

## 5. Web Server Configuration (Apache)

Configure the Apache VirtualHost (e.g. `/etc/apache2/sites-available/cwfms.conf`):

```apache
<VirtualHost *:80>
    ServerName cwfms.irasportswear.com
    Redirect permanent / https://cwfms.irasportswear.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName cwfms.irasportswear.com
    DocumentRoot /var/www/html/cwfms

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/cwfms.irasportswear.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/cwfms.irasportswear.com/privkey.pem

    # Security Headers
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self';"

    <Directory /var/www/html/cwfms>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Block access to hidden files and sensitive directories
    <FilesMatch "^\.">
        Require all denied
    </FilesMatch>

    # Block access to test scripts in production
    <FilesMatch "^(test.*\.php|seed.*\.php)">
        Require all denied
    </FilesMatch>

    ErrorLog ${APACHE_LOG_DIR}/cwfms_error.log
    CustomLog ${APACHE_LOG_DIR}/cwfms_access.log combined
</VirtualHost>
```

Enable site and restart Apache:
```bash
sudo a2enmod rewrite headers ssl
sudo a2ensite cwfms.conf
sudo systemctl restart apache2
```

---

## 6. WhatsApp Cloud API Live Activation Steps

Live WhatsApp dispatch is currently blocked pending Meta Cloud API account credentials from the client. To activate live messaging:

1. **Meta Business Manager Setup:**
   - Create or link a Meta Business Account.
   - Navigate to **Developers -> My Apps -> WhatsApp -> API Setup**.
   - Generate a permanent **System User Access Token** with `whatsapp_business_messaging` permissions.
   - Obtain the **Phone Number ID** and **WhatsApp Business Account ID (WABA ID)**.
2. **Submit Message Templates for Meta Approval:**
   Submit the following 5 templates in Meta Business Manager matching the PDF specifications:
   - `ira_lead_acknowledgement` (Header, 3 body variables: Customer Name, Ref ID, Sales Rep Name)
   - `ira_clarification_request` (Body variables: Customer Name, Ref ID, Clarification Details)
   - `ira_order_sheet_update` (Body variables: Customer Name, Sport, Status)
   - `ira_order_approved` (Body variables: Customer Name, Ref ID)
   - `ira_moved_to_production` (Body variables: Customer Name, Sport, Team Academy Name)
3. **Configure Webhook:**
   - Set Callback URL: `https://cwfms.irasportswear.com/api/whatsapp.php?action=webhook`
   - Set Verify Token: value matching `WHATSAPP_WEBHOOK_VERIFY_TOKEN` in `.env`.
   - Subscribe to `messages` event.
4. **Update `.env`:**
   - Insert `WHATSAPP_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`, and `WHATSAPP_BUSINESS_ACCOUNT_ID`.
   - Set `WHATSAPP_AUTOMATIONS_ENABLED=1`.

---

## 7. Post-Deployment Verification Checklist

- [ ] HTTPS connection succeeds with valid SSL certificate.
- [ ] Login screen displays with generic error messages and rate limiting active.
- [ ] Default administrator logs in with temporary password and is prompted to change credentials.
- [ ] Session cookie flags verified (`HttpOnly`, `SameSite=Lax`, `Secure` when HTTPS).
- [ ] Cross-module navigation: clicking a customer on Kanban opens `customer-detail.php`.
- [ ] Customer profile displays real count of leads and the dedicated Leads Pipeline table.
- [ ] "+ New Enquiry" button from Customer profile pre-populates customer in lead creation modal.
- [ ] File upload with valid PDF/PNG succeeds; file upload with spoofed `.php` or executable is blocked with HTTP 422.
- [ ] Soft delete hides records from UI while preserving relational history.
