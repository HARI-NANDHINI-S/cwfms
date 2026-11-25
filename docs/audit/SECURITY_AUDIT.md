# CWFMS Security Audit & Vulnerability Assessment

**System Audited:** Ira Sportswear / Studio 3 Interactive — Customer Workflow Management System (CWFMS)  
**Audit Scope:** Authentication, Authorization, Session Management, API Endpoints, File Uploads, Database Access Policies, Secret Management  
**Audit Date:** November 20, 2026  
**Auditor:** Antigravity AI Assistant  

---

## 1. Executive Summary

A comprehensive security audit of the CWFMS application codebase was conducted across frontend interfaces, PHP backend controllers, session handling, file upload pipelines, and database access layers. 

While the application features strong role normalization guards (`canAssignLead`, `canDeleteLead`, `canEditLeadRecord`) and hardened upload script execution denial (`uploads/.htaccess`), **two critical security vulnerabilities** were identified that must be remediated prior to production deployment:
1. **Critical:** Hardcoded demo password check (`password123`) in `login.php` allowing unauthenticated access to any user account.
2. **Critical:** Supabase Service Role Key committed in plaintext within source code (`includes/config.php` and test utilities), allowing complete database bypass.

---

## 2. Vulnerability Findings Matrix

| Vulnerability ID | Vulnerability Title | Affected File(s) | Severity | CVSS v3.1 | Status |
|---|---|---|:---:|:---:|:---:|
| **VULN-01** | Hardcoded Demo Authentication Bypass | `login.php` | **CRITICAL** | **9.8** (Critical) | Open / Defect |
| **VULN-02** | Hardcoded Service Role Secret Key in Repository | `includes/config.php`, `check_tables.py`, `test_roles.py` | **CRITICAL** | **9.1** (Critical) | Open / Defect |
| **VULN-03** | Permissive Database Row Level Security (RLS) Policies | `supabase_setup.sql` | **HIGH** | **7.5** (High) | Open / Defect |
| **VULN-04** | Missing Binary Magic-Byte Verification on File Uploads | `api/leads.php` | **MEDIUM** | **6.5** (Medium) | Open / Partial |
| **VULN-05** | Missing Anti-CSRF Synchronizer Tokens on Mutating APIs | `api/customers.php`, `api/leads.php`, `api/auth.php` | **MEDIUM** | **5.4** (Medium) | Mitigated by SameSite=Lax |
| **VULN-06** | Information Disclosure via Web Server Error Logging | `includes/config.php` | **LOW** | **3.7** (Low) | Resolved on feature branch |
| **VULN-07** | Direct Public Access to SQL and Env Files | `.htaccess` | **LOW** | **3.3** (Low) | Resolved on feature branch |

---

## 3. Detailed Vulnerability Analyses & Remediation Specifications

### VULN-01: Hardcoded Demo Authentication Bypass
- **Severity:** **CRITICAL** (CVSS: 9.8)
- **Vector:** `CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H`
- **Location:** `login.php` (lines 28–42)
- **Vulnerability Description:**
  Authentication logic in `login.php` validates credentials as follows:
  ```php
  $userRecord = null;
  if ($result['status'] === 200 && !empty($result['data'])) {
      $userRecord = $result['data'][0];
  } elseif (isset($demoUsers[strtolower($email)])) {
      $userRecord = $demoUsers[strtolower($email)];
  }

  if ($userRecord) {
      $validPassword = ($password === 'password123'); // Demo logic
      if ($validPassword) {
          $_SESSION['user'] = [...];
          header('Location: dashboard.php');
          exit;
      }
  }
  ```
  Additionally, built-in demo accounts for `admin@studio3.com`, `manager@studio3.com`, and `staff@studio3.com` are hardcoded in PHP with static mock UUIDs.
- **Exploitation Impact:**
  Any unauthenticated person on the public internet who enters any registered email along with `password123` can gain immediate full administrative access.
- **Remediation Specification:**
  1. Add a `password_hash VARCHAR(255)` column to `user_roles` table.
  2. In `login.php`, verify user input with native PHP `password_verify($password, $userRecord['password_hash'])`.
  3. Strip all hardcoded `$demoUsers` mock dictionaries from `login.php`.
  4. Implement an account creation/password reset script using `password_hash($rawPassword, PASSWORD_DEFAULT)`.

---

### VULN-02: Hardcoded Supabase Service Role Secret Key in Repository
- **Severity:** **CRITICAL** (CVSS: 9.1)
- **Vector:** `CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:N`
- **Location:** `includes/config.php` (lines 6–10) and root scripts `check_tables.py`, `test_roles.py`, `test_trigger.py`
- **Vulnerability Description:**
  The `SUPABASE_SERVICE_KEY` JWT token is directly hardcoded into git-tracked PHP and Python files:
  ```php
  define('SUPABASE_SERVICE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZqYWhqbGRoZmxjbW5hcmdjb29kIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc3NDcwNzIwNSwiZXhwIjoyMDkwMjgzMjA1fQ...');
  ```
- **Exploitation Impact:**
  The Supabase Service Role key has full superuser administrative privileges. Anyone with access to the repository can use this key to bypass Row Level Security completely, read all customer data, extract uploaded files, drop tables, or modify database records via PostgREST.
- **Remediation Specification:**
  1. Rotate the compromised Service Role Key immediately in the Supabase Dashboard.
  2. Ensure `.env` is listed in `.gitignore` and never committed.
  3. Enforce reading `SUPABASE_SERVICE_KEY` exclusively from environment variables or secure `.env` files via `loadEnvFile()`.
  4. Purge hardcoded strings from all Python test utilities.

---

### VULN-03: Permissive Database Row Level Security (RLS) Policies
- **Severity:** **HIGH** (CVSS: 7.5)
- **Vector:** `CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:N`
- **Location:** `supabase_setup.sql` (lines 80–92)
- **Vulnerability Description:**
  Policies on `customers`, `customer_activity_log`, and `user_roles` are defined as:
  ```sql
  CREATE POLICY "Allow all operations on customers" ON customers
      FOR ALL USING (true) WITH CHECK (true);
  ```
- **Exploitation Impact:**
  Any anonymous client possessing the public `SUPABASE_ANON_KEY` can perform unauthenticated CRUD queries directly against the Supabase REST API endpoint (`/rest/v1/customers`).
- **Remediation Specification:**
  In Supabase SQL Editor, update the policies so that anonymous public access is restricted:
  ```sql
  DROP POLICY IF EXISTS "Allow all operations on customers" ON customers;
  CREATE POLICY "Service role full access on customers" ON customers
      FOR ALL TO service_role USING (true) WITH CHECK (true);
  CREATE POLICY "Authenticated users view customers" ON customers
      FOR SELECT TO authenticated USING (true);
  ```

---

### VULN-04: Missing Binary Magic-Byte Verification on File Uploads
- **Severity:** **MEDIUM** (CVSS: 6.5)
- **Vector:** `CVSS:3.1/AV:N/AC:L/PR:L/UI:N/S:U/C:L/I:H/A:N`
- **Location:** `api/leads.php` in `savedFileMeta()`
- **Vulnerability Description:**
  File validation relies strictly on checking file extensions:
  ```php
  $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, $allowed, true)) { ... }
  ```
  While `uploads/.htaccess` denies script execution, binary inspection is not performed.
- **Remediation Specification:**
  In `savedFileMeta()`, use PHP Fileinfo to verify real MIME types:
  ```php
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime = finfo_file($finfo, $file['tmp_name']);
  finfo_close($finfo);

  $validMimes = [
      'pdf'  => ['application/pdf'],
      'png'  => ['image/png'],
      'jpg'  => ['image/jpeg'],
      'jpeg' => ['image/jpeg'],
      'zip'  => ['application/zip', 'application/x-zip-compressed'],
      'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
      'xls'  => ['application/vnd.ms-excel'],
      'csv'  => ['text/csv', 'text/plain'],
  ];
  ```

---

### VULN-05: Missing Anti-CSRF Synchronizer Tokens
- **Severity:** **MEDIUM** (CVSS: 5.4)
- **Location:** `api/customers.php`, `api/leads.php`
- **Current State:**
  Session cookies are protected by `@ini_set('session.cookie_samesite', 'Lax')` and `HttpOnly=1`. However, state-changing POST/PATCH/DELETE API requests do not require an anti-CSRF synchronizer token in request headers.
- **Remediation Specification:**
  Generate a cryptographically secure token on login `$_SESSION['csrf_token'] = bin2hex(random_bytes(32))`, deliver it in HTML meta tag `<meta name="csrf-token" content="...">`, and validate `X-CSRF-Token` header in `requireApiAuth()`.

---

## 4. Role-Based Access Control (RBAC) Enforcement Evaluation

| Business Role | UI Access | Customer Operations | Lead Operations | User Administration | Evaluation |
|---|---|---|---|---|:---:|
| **Admin** | Full system | Create, Edit, Delete, Toggle Status, View | Create, Edit All, Assign All, Status All, Delete | Full User CRUD & Role Reassignment | ✅ Secure |
| **Sales Manager** | Full operations | Create, Edit, Toggle Status, View; Delete Blocked (403) | Create, Edit All, Assign All, Status All; Delete Blocked (403) | View Only; Modifications Blocked | ✅ Secure |
| **Sales Executive** | Operations limited | Create, View; Edit & Delete Blocked (403) | Create; Edit Assigned Only; Status Assigned Only; Assign Blocked (403); Delete Blocked (403) | Denied (403) | ✅ Secure |

**RBAC Verdict:**
The capability-based enforcement logic in `includes/config.php` (`canDeleteLead`, `canAssignLead`, `canEditLeadRecord`, `canChangeLeadStatus`, `canManageUsers`) is robustly implemented on the backend and rejects unauthorized operations with HTTP 403 Forbidden.
