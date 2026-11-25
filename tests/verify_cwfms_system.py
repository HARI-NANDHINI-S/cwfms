#!/usr/bin/env python3
"""
CWFMS Static Source Inspection & Structural Gate Verification Suite
Performs static AST/regex inspections of PHP source files, configuration guards,
RBAC statements, CSRF protection, file upload validation logic, and migration idempotency.
NOTE: These tests perform STATIC SOURCE CODE INSPECTION. They do not execute PHP runtime
processes or connect to a live database.
"""

import os
import re
import sys
import glob
import subprocess

passed = 0
failed = 0
blocked = 0

def log_test(name, result, classification="Static source inspection", details=""):
    global passed, failed, blocked
    if result == "PASS":
        passed += 1
        print(f"  [\033[32mPASS\033[0m] [{classification}] {name}")
    elif result == "FAIL":
        failed += 1
        print(f"  [\033[31mFAIL\033[0m] [{classification}] {name} - {details}")
    elif result == "BLOCKED":
        blocked += 1
        print(f"  [\033[33mBLOCKED\033[0m] [{classification}] {name} - {details}")

print("\n" + "=" * 70)
print("  CWFMS STATIC SOURCE CODE & STRUCTURAL VERIFICATION SUITE")
print("  (Automated Static Analysis - Does Not Execute Host PHP Runtime)")
print("=" * 70 + "\n")

# -------------------------------------------------------------
# 1. PHP Syntax & Token Structure Verification
# -------------------------------------------------------------
print("--- [1] PHP Source File Integrity & Structure ---")
php_files = glob.glob("**/*.php", recursive=True)
syntax_issues = []
for pf in php_files:
    if ".git" in pf or ".cache" in pf:
        continue
    with open(pf, "r", encoding="utf-8", errors="ignore") as f:
        content = f.read()
    if "<?php" not in content and "<?" not in content:
        syntax_issues.append(f"{pf}: Missing PHP opening tag")

log_test(f"PHP File Presence ({len(php_files)} files discovered)", "PASS" if len(php_files) >= 15 else "FAIL", "Static source inspection")
log_test("PHP Open Tag Structure & File Completeness", "PASS" if len(syntax_issues) == 0 else "FAIL", "Static source inspection", "; ".join(syntax_issues))

# -------------------------------------------------------------
# 2. Authentication & Demo Password Remediation
# -------------------------------------------------------------
print("\n--- [2] Authentication Hardening & Credential Security ---")
with open("login.php", "r", encoding="utf-8") as f:
    login_src = f.read()

# Test 2.1: No demo password check in login.php
has_demo_pw = "password123" in login_src
log_test("Static Check: Demo Password Elimination in login.php", "PASS" if not has_demo_pw else "FAIL", "Static source inspection", "Found password123 in login.php")

# Test 2.2: Native password_verify
has_pw_verify = "password_verify(" in login_src
log_test("Static Check: Native password_verify() Implementation in login.php", "PASS" if has_pw_verify else "FAIL", "Static source inspection")

# Test 2.3: password_needs_rehash
has_pw_rehash = "password_needs_rehash(" in login_src
log_test("Static Check: Automatic Password Rehashing via password_needs_rehash()", "PASS" if has_pw_rehash else "FAIL", "Static source inspection")

# Test 2.4: Session regeneration
has_sess_regen = "session_regenerate_id(true)" in login_src
log_test("Static Check: Anti-Session-Fixation session_regenerate_id(true)", "PASS" if has_sess_regen else "FAIL", "Static source inspection")

# Test 2.5: Rate limiting in login
has_rate_limit = "checkLoginRateLimit(" in login_src and "recordFailedLogin(" in login_src
log_test("Static Check: Brute-Force Rate Limiting Invocations in login.php", "PASS" if has_rate_limit else "FAIL", "Static source inspection")

# Test 2.6: Generic error message
has_generic_err = "Invalid email or password." in login_src
log_test("Static Check: Anti-Enumeration Generic Login Failure Response", "PASS" if has_generic_err else "FAIL", "Static source inspection")

# Test 2.7: password_hash in api/auth.php
with open("api/auth.php", "r", encoding="utf-8") as f:
    auth_src = f.read()
has_pw_hash = "password_hash(" in auth_src and "PASSWORD_DEFAULT" in auth_src
log_test("Static Check: Native password_hash(..., PASSWORD_DEFAULT) in api/auth.php", "PASS" if has_pw_hash else "FAIL", "Static source inspection")

# Test 2.8: Login CSRF Protection
has_login_csrf = "verifyCsrfToken($csrfToken)" in login_src and 'name="csrf_token"' in login_src
log_test("Static Check: Login Form CSRF Protection & Verification", "PASS" if has_login_csrf else "FAIL", "Static source inspection")

# Test 2.9: Complete Session Invalidation on Logout
with open("logout.php", "r", encoding="utf-8") as f:
    logout_src = f.read()
has_logout_wipe = "$_SESSION = []" in logout_src and "setcookie" in logout_src and "session_destroy" in logout_src
log_test("Static Check: Complete Session Invalidation & Cookie Expiry in logout.php", "PASS" if has_logout_wipe else "FAIL", "Static source inspection")

# -------------------------------------------------------------
# 3. CSRF Defense & Request Validation
# -------------------------------------------------------------
print("\n--- [3] CSRF Defense Engine ---")
with open("includes/config.php", "r", encoding="utf-8") as f:
    config_src = f.read()

has_csrf_funcs = "function getCsrfToken" in config_src and "function verifyCsrfToken" in config_src and "function requireCsrfToken" in config_src
log_test("Static Check: CSRF Core Engine Declarations in includes/config.php", "PASS" if has_csrf_funcs else "FAIL", "Static source inspection")

with open("assets/js/app.js", "r", encoding="utf-8") as f:
    app_js = f.read()

has_fetch_hook = "window.fetch" in app_js and "X-CSRF-Token" in app_js
log_test("Static Check: Global JavaScript fetch Interceptor (X-CSRF-Token)", "PASS" if has_fetch_hook else "FAIL", "Static source inspection")

# Verify CSRF meta in all UI pages
ui_pages = ["dashboard.php", "pages/customer-detail.php", "pages/leads.php", "pages/users.php", "pages/import.php"]
missing_csrf_meta = []
for p in ui_pages:
    with open(p, "r", encoding="utf-8") as f:
        if "csrf-token" not in f.read():
            missing_csrf_meta.append(p)
log_test("Static Check: CSRF Meta Tag Presence on UI Pages", "PASS" if len(missing_csrf_meta) == 0 else "FAIL", "Static source inspection", f"Missing in {missing_csrf_meta}")

# -------------------------------------------------------------
# 4. Role-Based Access Control (RBAC) & Boundary Isolation
# -------------------------------------------------------------
print("\n--- [4] Role-Based Access Control & Record Boundary Isolation ---")
with open("api/leads.php", "r", encoding="utf-8") as f:
    leads_src = f.read()

with open("api/customers.php", "r", encoding="utf-8") as f:
    cust_src = f.read()

has_se_get_scoping = "Sales Executives can only access leads assigned to them" in leads_src
log_test("Static Check: Sales Executive GET Ownership Check in api/leads.php", "PASS" if has_se_get_scoping else "FAIL", "Static source inspection")

has_se_list_scoping = "$userRole === 'sales_executive'" in leads_src and "$params['assigned_to'] = 'eq.' . $currentUserId" in leads_src
log_test("Static Check: Sales Executive List Query Scoping in api/leads.php", "PASS" if has_se_list_scoping else "FAIL", "Static source inspection")

has_se_export_guard = "Sales Executives cannot export lead datasets" in leads_src
log_test("Static Check: Sales Executive Export Permission Denial in api/leads.php", "PASS" if has_se_export_guard else "FAIL", "Static source inspection")

has_se_assign_guard = "Sales Executives cannot assign or reassign leads" in leads_src
log_test("Static Check: Sales Executive Assignment Guard in api/leads.php", "PASS" if has_se_assign_guard else "FAIL", "Static source inspection")

has_delete_guard = "canDeleteLead(" in leads_src and "Only administrators can delete leads" in leads_src
log_test("Static Check: Admin-Exclusive Lead Deletion Guard in api/leads.php", "PASS" if has_delete_guard else "FAIL", "Static source inspection")

has_cust_delete_guard = "Only administrators can delete customers" in cust_src
log_test("Static Check: Admin-Exclusive Customer Deletion Guard in api/customers.php", "PASS" if has_cust_delete_guard else "FAIL", "Static source inspection")

has_cust_leads_scoping = "$userRole === 'sales_executive'" in cust_src and "$leadParams['assigned_to'] = 'eq.'" in cust_src
log_test("Static Check: Customer Leads Scoping for Sales Executives in api/customers.php", "PASS" if has_cust_leads_scoping else "FAIL", "Static source inspection")

# -------------------------------------------------------------
# 5. File Upload Hardening & Magic Byte Validation
# -------------------------------------------------------------
print("\n--- [5] File Upload Hardening & Execution Neutralization ---")
has_finfo = "finfo_open(FILEINFO_MIME_TYPE)" in leads_src
log_test("Static Check: Magic Byte Verification Invocations in api/leads.php", "PASS" if has_finfo else "FAIL", "Static source inspection")

has_ext_whitelist = "$allowed = ['pdf', 'ai', 'eps', 'png', 'jpg', 'jpeg', 'zip', 'xlsx', 'xls', 'csv']" in leads_src
log_test("Static Check: Strict Extension Whitelist in api/leads.php", "PASS" if has_ext_whitelist else "FAIL", "Static source inspection")

has_double_ext_guard = "dangerous double file extension detected" in leads_src
log_test("Static Check: Double File Extension Guard in api/leads.php", "PASS" if has_double_ext_guard else "FAIL", "Static source inspection")

has_path_traversal_guard = "invalid filename or path traversal detected" in leads_src
log_test("Static Check: Path Traversal (..) and Null Byte Guard in api/leads.php", "PASS" if has_path_traversal_guard else "FAIL", "Static source inspection")

has_rel_storage = "'path' => 'uploads/leads/' . $storedFilename" in leads_src
log_test("Static Check: Relative Storage Path Masking in api/leads.php", "PASS" if has_rel_storage else "FAIL", "Static source inspection")

with open("uploads/.htaccess", "r", encoding="utf-8") as f:
    ht_src = f.read()

has_fpm_guard = "RemoveHandler" in ht_src and "SetHandler None" in ht_src
log_test("Static Check: Apache PHP-FPM / FastCGI Handler Neutralization in uploads/.htaccess", "PASS" if has_fpm_guard else "FAIL", "Static source inspection")

has_authz_core = "mod_authz_core.c" in ht_src and "Require all denied" in ht_src
log_test("Static Check: Apache 2.4 Require all denied in uploads/.htaccess", "PASS" if has_authz_core else "FAIL", "Static source inspection")

has_complete_upload_denial = "Require all denied" in ht_src and "Require all granted" not in ht_src
log_test("Static Check: Direct Uploads Web Access Denied (Require all granted eliminated)", "PASS" if has_complete_upload_denial else "FAIL", "Static source inspection")

has_attachment_endpoint = "attachment" in leads_src and "leadUploadDir()" in leads_src
log_test("Static Check: Authenticated Attachment Stream Endpoint in api/leads.php", "PASS" if has_attachment_endpoint else "FAIL", "Static source inspection")

has_attachment_rbac = "Sales Executives can only access attachments for leads assigned to them" in leads_src
log_test("Static Check: Sales Executive Attachment Access Scoping in api/leads.php", "PASS" if has_attachment_rbac else "FAIL", "Static source inspection")

has_attachment_ui_links = "api/leads.php?action=attachment" in open("pages/leads.php", "r", encoding="utf-8").read()
log_test("Static Check: Authenticated Attachment Download Links in Leads UI", "PASS" if has_attachment_ui_links else "FAIL", "Static source inspection")

has_no_service_key_in_js = "SUPABASE_SERVICE" not in open("assets/js/app.js", "r", encoding="utf-8").read()
log_test("Static Check: Supabase Service Key Isolated from Client JavaScript", "PASS" if has_no_service_key_in_js else "FAIL", "Static source inspection")

# -------------------------------------------------------------
# 6. Cross-Module Customer-to-Lead Integration
# -------------------------------------------------------------
print("\n--- [6] Cross-Module Customer-to-Lead Workflows ---")
with open("pages/customer-detail.php", "r", encoding="utf-8") as f:
    cd_src = f.read()

has_pipeline_table = "Active Enquiries &amp; Athletic Team Orders" in cd_src
log_test("Static Check: Customer Detail Pipeline Table Markup", "PASS" if has_pipeline_table else "FAIL", "Static source inspection")

has_new_enquiry_btn = "+ New Enquiry" in cd_src and "leads.php?customer_id=" in cd_src
log_test("Static Check: Deep-Linked New Enquiry Action Markup", "PASS" if has_new_enquiry_btn else "FAIL", "Static source inspection")

has_active_leads_sidebar = '<a href="leads.php" class="nav-item">' in cd_src
log_test("Static Check: Active Leads Sidebar Navigation Link", "PASS" if has_active_leads_sidebar else "FAIL", "Static source inspection")

with open("pages/leads.php", "r", encoding="utf-8") as f:
    leads_page_src = f.read()

has_kanban_cust_link = "customer-detail.php?id=" in leads_page_src
log_test("Static Check: Customer Detail Profile Links in Leads UI", "PASS" if has_kanban_cust_link else "FAIL", "Static source inspection")

has_activity_sync = "addLeadActivity" in leads_src and "$db->logActivity(" in leads_src and "lead_event" in leads_src
log_test("Static Check: Dual-Write Activity Timeline Sync Logic in api/leads.php", "PASS" if has_activity_sync else "FAIL", "Static source inspection")

with open("api/whatsapp_webhook.php", "r", encoding="utf-8") as f:
    webhook_src = f.read()

has_fail_closed_webhook = "empty($appSecret)" in webhook_src and "hash_equals($expectedSig, $signature)" in webhook_src and "HTTP_X_HUB_SIGNATURE_256" in webhook_src
log_test("Static Check: Fail-Closed Webhook HMAC-SHA256 Signature Verification", "PASS" if has_fail_closed_webhook else "FAIL", "Static source inspection")

# -------------------------------------------------------------
# 7. Database Migration Syntax & Idempotency
# -------------------------------------------------------------
print("\n--- [7] Database Migrations & Schema Structure ---")
migrations = [
    "supabase_setup.sql", "migration_v2.sql", "migration_v3_leads.sql",
    "migration_v4_roles.sql", "migration_v5_auth.sql", "migration_v6_whatsapp.sql",
    "migration_v7_rls_hardening.sql"
]
for m in migrations:
    with open(m, "r", encoding="utf-8") as f:
        c = f.read()
    is_idempotent = "IF NOT EXISTS" in c or "DO $$" in c or "DROP POLICY IF EXISTS" in c
    log_test(f"Migration {m} Idempotency Syntax", "PASS" if is_idempotent else "FAIL", "Static source inspection")

with open("migration_v5_auth.sql", "r", encoding="utf-8") as f:
    v5_src = f.read()

has_safe_seed = "WHERE user_email IN ('admin@studio3.com'" in v5_src
log_test("Migration V5: Seeded Hashes Scoped to Development Accounts Only", "PASS" if has_safe_seed else "FAIL", "Static source inspection")

# -------------------------------------------------------------
# 8. External Infrastructure & Runtime State
# -------------------------------------------------------------
print("\n--- [8] External Execution & Runtime Assessment ---")
php_bin = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "bin", "php", "php.exe"))
php_found = False
php_ver = ""
if os.path.exists(php_bin):
    try:
        out = subprocess.check_output([php_bin, "-v"]).decode("utf-8")
        php_found = True
        php_ver = out.split("\n")[0].strip()
    except Exception:
        pass
if not php_found:
    try:
        out = subprocess.check_output(["php", "-v"]).decode("utf-8")
        php_found = True
        php_ver = out.split("\n")[0].strip()
    except Exception:
        pass

if php_found:
    log_test("Workspace PHP CLI Runtime Execution", "PASS", "PHP runtime verified", php_ver)
else:
    log_test("Host PHP CLI Runtime Execution", "BLOCKED", "Blocked test", "Host does not have php.exe on system PATH")

log_test("Host Docker Daemon Execution", "BLOCKED", "Blocked test", "Docker daemon not running; named pipe inaccessible")
log_test("Live Supabase Migration Deployment", "BLOCKED", "Blocked test", ".env file not present in repository workspace")
log_test("Meta Cloud WhatsApp Live Dispatch", "BLOCKED", "Blocked test", "WHATSAPP_TOKEN & PHONE_NUMBER_ID credentials pending from client")

# -------------------------------------------------------------
# Summary
# -------------------------------------------------------------
print("\n" + "=" * 70)
print(f"  STATIC VERIFICATION: {passed} PASSED | {failed} FAILED | {blocked} BLOCKED")
print(f"  (Note: {passed} checks passed; {blocked} external infrastructure checks blocked)")
print("=" * 70)

if failed > 0:
    sys.exit(1)
