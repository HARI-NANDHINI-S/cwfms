# CWFMS Security Configuration & Hardening Guide

**Application:** Customer & Workflow Management System (CWFMS)  
**Client:** Studio 3 Interactive / Ira Sportswear  
**Document Version:** 1.0.0  
**Date:** November 2026

---

## 1. Executive Summary

This document details the security architecture and defensive controls implemented across CWFMS to resolve all vulnerabilities discovered during the system audit. The application enforces defense-in-depth across the web layer, API endpoints, file system, and database tiers.

---

## 2. Authentication & Password Hardening

### 2.1 Cryptographic Password Storage
The legacy demo authentication check (`password123`) has been completely eliminated. All user accounts authenticate against cryptographic hashes stored in the `user_roles.password_hash` column:

- **Hashing Algorithm:** Native PHP `PASSWORD_DEFAULT` (Bcrypt with cost factor 12).
- **Verification:** Native `password_verify($password, $hash)` resisting timing attacks.
- **Rehashing:** `password_needs_rehash()` check automatically updates hashes if PHP security algorithms advance.
- **Creation/Update Validation:** Minimum password length of 8 characters strictly enforced in `api/auth.php`.

### 2.2 Brute-Force & Rate-Limiting Protection
To prevent credential stuffing and brute-force dictionary attacks:
- **Rate Limit Window:** 5 failed attempts per IP address within 900 seconds (15 minutes).
- **Lockout Enforcement:** Handled via `checkLoginRateLimit($ip)` in `includes/config.php`.
- **Anti-Enumeration Error Handling:** Login failures return generic messages (`"Invalid email or password."`) regardless of whether the email exists.
- **State Cleanup:** `clearFailedLogins($ip)` resets the counter immediately upon successful authentication.

---

## 3. Session Management & Fixation Defenses

### 3.1 Session Configuration
PHP sessions are configured with strict security parameters in `includes/config.php`:

```php
ini_set('session.cookie_httponly', 1);     // Prevents JavaScript access (mitigates XSS cookie theft)
ini_set('session.use_only_cookies', 1);    // Disallows session IDs in URLs
ini_set('session.cookie_samesite', 'Lax'); // Protects against CSRF cross-origin leakage
if (isHttps()) {
    ini_set('session.cookie_secure', 1);   // Transmitted only over encrypted SSL/TLS
}
```

### 3.2 Anti-Fixation & Expiration Controls
- **Session ID Regeneration:** Upon successful credential validation, `session_regenerate_id(true)` is invoked to invalidate old session IDs and eliminate session fixation.
- **Idle Expiration:** Inactivity threshold enforced at 7200 seconds (2 hours) via `SESSION_TIMEOUT_SECONDS`.
- **Secure Destruction:** `logout.php` clears `$_SESSION`, destroys the session record, and expires the session cookie.

---

## 4. Cross-Site Request Forgery (CSRF) Protection

### 4.1 Token Lifecycle
CWFMS implements the Synchronizer Token Pattern:
- **Token Generation:** Generated via `random_bytes(32)` stored in `$_SESSION['csrf_token']`.
- **Injection:** Rendered on all HTML pages in `<meta name="csrf-token" content="...">`.
- **Transmission:** Sent via HTTP header `X-CSRF-Token` or form body `csrf_token`.
- **Timing-Safe Verification:** Validated via `hash_equals($_SESSION['csrf_token'], $token)` in `verifyCsrfToken()`.

### 4.2 Automated Client Interceptor
In `assets/js/app.js`, a native `window.fetch` interceptor hooks all outgoing AJAX requests:
- Inspects HTTP method: `POST`, `PUT`, `PATCH`, `DELETE`.
- Automatically retrieves CSRF token from the DOM meta tag.
- Injects `X-CSRF-Token` header without requiring manual payload modification across individual UI buttons.

### 4.3 API Enforcement
Every mutating API handler (`api/customers.php`, `api/leads.php`, `api/whatsapp.php`, `api/auth.php`) begins with:
```php
requireApiAuth();
```
which verifies that:
1. `$_SESSION['user']` exists (HTTP 401 on failure).
2. `verifyCsrfToken()` succeeds for state-mutating methods (HTTP 403 on failure).

---

## 5. Role-Based Access Control (RBAC) Matrix

CWFMS enforces a 3-role canonical hierarchy with strict permission gating:

| Capability / Action | Admin (`admin`) | Sales Manager (`sales_manager`) | Sales Executive (`sales_executive`) |
|:---|:---:|:---:|:---:|
| **View Dashboard & Customers** | Full Access | Full Access | Full Access |
| **Create Customer** | Allowed | Allowed | Allowed |
| **Edit Customer Profile** | Allowed | Allowed | Forbidden (HTTP 403) |
| **Toggle Customer Status** | Allowed | Allowed | Forbidden (HTTP 403) |
| **Soft Delete Customer** | Allowed (Exclusive) | Forbidden (HTTP 403) | Forbidden (HTTP 403) |
| **View Leads Pipeline** | All Leads | All Leads | Assigned Leads Only |
| **Create Lead** | Allowed | Allowed | Allowed |
| **Assign / Reassign Lead** | Any Representative | Any Representative | Forbidden |
| **Update Lead Status** | Any Status | Any Status | Assigned Leads Only (Limited) |
| **Delete Lead** | Allowed (Exclusive) | Forbidden (HTTP 403) | Forbidden (HTTP 403) |
| **Bulk Actions** | Full Access | Reassign & Status Only | Forbidden |
| **Send WhatsApp Message** | Any Lead | Any Lead | Assigned Leads Only |
| **User Administration** | Full Access (Exclusive) | Forbidden (HTTP 403) | Forbidden (HTTP 403) |
| **CSV Export** | Allowed | Allowed | Forbidden |

---

## 6. File Upload Hardening & Magic Byte Validation

Lead design files, logo artwork, and team rosters uploaded via `api/leads.php` undergo multi-tiered validation:

1. **Extension Whitelisting:**
   - Allowed: `pdf`, `ai`, `eps`, `png`, `jpg`, `jpeg`, `zip`, `xlsx`, `xls`, `csv`.
   - Explicitly rejects `.php`, `.phtml`, `.exe`, `.sh`, `.bat`, `.js`, etc.
2. **File Size Limit:**
   - Strictly capped at 10 MB (`10 * 1024 * 1024` bytes).
3. **Magic Byte Binary MIME Inspection:**
   - Evaluated using PHP `finfo_open(FILEINFO_MIME_TYPE)`.
   - Compares actual binary signature against allowed MIME type map.
   - Immediately aborts if MIME type is executable (`application/x-executable`, `application/x-php`, etc.) even if disguised with `.png` or `.pdf` extension.
4. **Filename Sanitization & Randomization:**
   - Strips non-alphanumeric characters: `preg_replace('/[^A-Za-z0-9_.-]/', '_', ...)`.
   - Generates unpredictable storage names: `time() . '_' . bin2hex(random_bytes(6)) . '_' . $safeName`.
5. **Execution Prevention:**
   - `uploads/.htaccess` disables PHP engines (`php_flag engine off`) and denies all script execution requests.

---

## 7. Database Row Level Security (RLS) Policies

All PostgreSQL tables in Supabase have Row Level Security enabled (`migration_v7_rls_hardening.sql`):

- **Service Role Access:**
  The server-side PHP client communicates with Supabase using `SUPABASE_SERVICE_KEY`. Dedicated policies grant `FOR ALL TO service_role USING (true) WITH CHECK (true)` on all tables.
- **Authenticated Access:**
  Direct Supabase client queries require valid authentication tokens and are restricted to non-deleted records (`USING (is_deleted = false)`).
- **Public / Anonymous Access:**
  All anonymous write policies (`USING (true)`) from earlier prototypes have been dropped. Direct unauthenticated access is completely blocked.

---

## 8. HTTP Security Headers

The following defensive headers are sent by default via `includes/config.php` and Apache configuration:

| Header | Value | Purpose |
|:---|:---|:---|
| `X-Content-Type-Options` | `nosniff` | Prevents MIME-sniffing attacks |
| `X-Frame-Options` | `SAMEORIGIN` | Mitigates UI clickjacking attacks |
| `X-XSS-Protection` | `1; mode=block` | Enables browser cross-site scripting filters |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Protects referral privacy |
| `Content-Security-Policy` | Customized per app requirements | Restricts unauthorized script injection |
