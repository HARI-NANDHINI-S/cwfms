# Phase 5: Backend Production Hardening Audit

**Application:** Ira Sportswear / CWFMS  
**Audit Standard:** Backend Architecture, API Standards, Error Handling & Reliability  
**Audit Date:** 2026-11-25  

---

## 1. Backend Architecture & Route Organization

The backend uses a modular PHP service structure:
- **Core Engine:** `includes/config.php` (Session management, RBAC, input sanitization, HTTP Supabase abstraction).
- **Communication Service:** `includes/whatsapp.php` (Meta Cloud API client, template registration, payload generation).
- **API Controllers:** Flat endpoints in `/api/`:
  - `api/auth.php`: Authentication states, user management, password hash generation.
  - `api/customers.php`: Customer CRUD, scoring, activation, and linked lead relationships.
  - `api/customer.php`: Single customer retrieval and CSV streaming export.
  - `api/leads.php`: Pipeline management, search/filter, multi-file attachments, and role-based status transitions.
  - `api/stats.php`: Dashboard metrics aggregation.
  - `api/chart-data.php`: Graph datasets for monthly registrations and country counts.
  - `api/health.php`: Diagnostic endpoint probing database latency and table schemas.
  - `api/whatsapp.php` & `api/whatsapp_webhook.php`: Customer messaging and Meta webhook receiver.

---

## 2. API Response Consistency & HTTP Status Codes

All API endpoints strictly enforce standard JSON responses:
```json
{
  "success": true,
  "message": "Operation description",
  "data": { ... },
  "timestamp": "YYYY-MM-DD HH:MM:SS"
}
```

### HTTP Status Code Usage:
- `200 OK`: Successful reads, updates, and soft deletes.
- `201 Created`: Successful creation of customers and leads.
- `400 Bad Request`: Validation failure on input parameters or missing fields.
- `401 Unauthorized`: Missing or expired user session.
- `403 Forbidden`: Insufficient role permissions or CSRF token failure.
- `404 Not Found`: Entity (customer, lead, or file) does not exist or has been soft-deleted.
- `409 Conflict`: Duplicate mobile number or email violation.
- `422 Unprocessable Entity`: Data schema validation violation (e.g. invalid phone number format).
- `500 Internal Server Error`: Downstream database failure or execution exception.
- `503 Service Unavailable`: Database configuration missing or unreachable.

---

## 3. Backend Hardening Findings & Recommendations

### BKD-01: Undefined Function Call in `pages/import.php` (Critical)
- **Location:** `pages/import.php:83`
- **Issue:** Calls `hasRole('admin')`, which is not declared in `includes/config.php`.
- **Fix:** Declare helper function `hasRole($role, $user = null): bool` in `includes/config.php` and update the navigation guard in `pages/import.php` to use `canManageUsers($user)`.

### BKD-02: Rate Limiting Persistence & Fail-Safe File Storage
- **Location:** `includes/config.php` (`checkLoginRateLimit`)
- **Issue:** Session-based storage fails to stop automated attacks.
- **Fix:** Implement file-based persistent locking rate limiter using `sys_get_temp_dir() . '/cwfms_locks/'` with atomic file locking (`flock`).

### BKD-03: Production Error Masking & Sanitization
- **Location:** `includes/config.php`
- **Issue:** While `APP_ENV === 'production'` sets `display_errors = 0`, certain database response messages in `parseDbError` may leak internal column or table names if Supabase errors out.
- **Fix:** Sanitize error messages in production mode to display generic, user-friendly notices while logging detailed technical errors to the server PHP error log.

### BKD-04: API Request Size Limits & Memory Constraints
- **Location:** Root `.htaccess` and `api/leads.php`
- **Issue:** Large multi-file uploads could exhaust PHP memory if request payload limits are not defined.
- **Fix:** Define explicit `LimitRequestBody 20971520` (20 MB) in `.htaccess` and enforce per-file limits in PHP.
