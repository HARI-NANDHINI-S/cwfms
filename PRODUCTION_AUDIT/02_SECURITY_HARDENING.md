# Phase 2: Comprehensive Application Security Hardening Audit

**Application:** Ira Sportswear / CWFMS  
**Audit Standard:** OWASP Top 10 (2021), ASVS Level 2 Guidelines, CWE Top 25  
**Audit Date:** 2026-11-25  

---

## 1. Executive Security Summary

The application codebase displays several modern security practices, such as strict session cookies (`HttpOnly`, `SameSite=Lax`), CSRF protection on state-changing requests, binary file upload inspection (MIME magic byte verification + double extension blocking), and parameterized PostgREST calls.

However, the security audit identified **3 High-Severity**, **4 Medium-Severity**, and **3 Low-Severity** vulnerabilities that must be remediated for production readiness.

---

## 2. Vulnerability Assessment Matrix

| ID | Vulnerability / Finding | OWASP 2021 Category | Severity | Affected Component | Status |
|---|---|---|:---:|---|:---:|
| **SEC-01** | Insecure Session-Only Storage for Login Rate Limiter | A07:2021 – Identification and Authentication Failures | **HIGH** | `includes/config.php` (`checkLoginRateLimit`) | Identified |
| **SEC-02** | Automatic Silent Downgrade of SSL Verification in cURL | A02:2021 – Cryptographic Failures | **HIGH** | `includes/config.php` (`Supabase::request`) | Identified |
| **SEC-03** | Missing Webhook Verify Token Strict Enforcement (Hardcoded Fallback) | A07:2021 – Identification and Authentication Failures | **HIGH** | `includes/config.php` (`getWhatsAppConfig`) | Identified |
| **SEC-04** | Missing Global Content-Security-Policy (CSP) & Permissions-Policy | A05:2021 – Security Misconfiguration | **MEDIUM** | `.htaccess` & `includes/config.php` | Identified |
| **SEC-05** | Unescaped User Attribute Output in Dashboard Shell | A03:2021 – Injection (Stored/Reflected XSS) | **MEDIUM** | `dashboard.php` (line 511) | Identified |
| **SEC-06** | Incomplete Apache 2.4 Authorization Directives in Root `.htaccess` | A05:2021 – Security Misconfiguration | **MEDIUM** | `.htaccess` (`FilesMatch`) | Identified |
| **SEC-07** | Hardcoded Application Path in Apache RewriteBase | A05:2021 – Security Misconfiguration | **MEDIUM** | `.htaccess` (`RewriteBase`) | Identified |
| **SEC-08** | Plaintext Exception / Error Details in Development Responses | A05:2021 – Security Misconfiguration | **LOW** | `api/leads.php`, `api/customers.php` | Identified |
| **SEC-09** | Missing Subresource Integrity (SRI) for CDN Scripts | A08:2021 – Software and Data Integrity Failures | **LOW** | `dashboard.php`, `login.php` (GSAP, Chart.js) | Identified |
| **SEC-10** | Absence of Cache-Control Headers on Sensitive Customer APIs | A04:2021 – Insecure Design | **LOW** | `api/customer.php`, `api/leads.php` | Identified |

---

## 3. Deep-Dive Findings & Recommended Remediations

### SEC-01: Insecure Session-Only Storage for Login Rate Limiter (High)
- **Mechanism:** In `includes/config.php`, `checkLoginRateLimit()` stores failed attempt counts in `$_SESSION['rate_' . $ip . '_attempts']`.
- **Exploitation:** An automated attacker using `curl` or a Python script that drops cookies between requests receives a fresh PHP session each time. The rate limiter never increments past 1, enabling unrestricted dictionary attacks.
- **Remediation:** Store IP-based login rate limits in a shared server-side transient store (e.g., APCu, or filesystem-based lock files in a designated secure temp directory with file-locking `flock`).

### SEC-02: Automatic Silent Downgrade of SSL Verification in cURL (High)
- **Mechanism:** Lines 347-353 in `includes/config.php`:
  ```php
  if ($rawResponse === false && stripos($curlError, 'ssl') !== false) {
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      $rawResponse = curl_exec($ch);
  }
  ```
- **Exploitation:** If a network error, proxy, or rogue gateway triggers an SSL certificate handshake error, the client silently disables SSL verification and re-submits the request. This allows an active attacker to perform Man-in-the-Middle (MitM) interception of Supabase Service Role keys and plaintext customer data.
- **Remediation:** Remove this automatic insecure downgrade. When `APP_ENV === 'production'`, TLS verification must strictly fail-closed.

### SEC-03: Hardcoded Fallback Webhook Verify Token (High)
- **Mechanism:** Line 870 in `includes/config.php` specifies `'ira_sportswear_wh_verify_secret_token_2026'` as the default token when `WHATSAPP_WEBHOOK_VERIFY_TOKEN` is unset.
- **Exploitation:** An attacker can verify and subscribe malicious webhooks if an admin omits configuring this environment variable.
- **Remediation:** Remove the hardcoded secret token. Require the environment variable to be explicitly supplied, and reject verification with HTTP 500/403 if missing.

### SEC-04: Missing Content-Security-Policy (CSP) (Medium)
- **Mechanism:** `.htaccess` and `includes/config.php` provide `X-Content-Type-Options` and `X-Frame-Options`, but omit `Content-Security-Policy`.
- **Remediation:** Add a comprehensive CSP restricting script sources to `'self'`, trusted CDNs (`cdn.jsdelivr.net`, `cdnjs.cloudflare.com`), and disallowing object/frame embeddings.

### SEC-05: Unescaped User Attribute Output in Dashboard (Medium)
- **Mechanism:** Line 511 of `dashboard.php` outputs `<?= $user['role'] ?>` directly without passing through `htmlspecialchars()`.
- **Remediation:** Wrap with `htmlspecialchars($user['role_label'] ?? $user['role'], ENT_QUOTES, 'UTF-8')`.

### SEC-06 & SEC-07: Apache Directives & RewriteBase (Medium)
- **Mechanism:** Root `.htaccess` uses Apache 2.2 `Order allow,deny / Deny from all` on protected files (`.env`, `.sql`, `.md`, `config.php`) without modern Apache 2.4 `Require all denied`. Furthermore, `RewriteBase /customer-management/` breaks when deployed at root.
- **Remediation:** Modernize file blocking blocks with `<IfModule mod_authz_core.c> Require all denied </IfModule>` and normalize `RewriteBase` to `/`.
