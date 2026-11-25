import urllib.request
import urllib.parse
import http.cookiejar
import json
import re

BASE_URL = "http://127.0.0.1:8000"

print("======================================================================")
print("  CWFMS POST-RECONNECTION SECURITY AUDIT & HARDENING TEST SUITE")
print("======================================================================")

# 1. VERIFY LOGIN FAILS CLOSED (NO HARDCODED DEFAULT PASSWORDS)
print("\n[TEST 1] VERIFY HARDCODED DEFAULT PASSWORD REMOVAL")
cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

# Fetch login page and CSRF
resp = opener.open(f"{BASE_URL}/login.php")
html = resp.read().decode('utf-8')
csrf = re.search(r'name="csrf_token"\s+value="([^"]+)"', html).group(1)

# Ensure no demo pills with password123 exist in HTML
has_demo_pill = 'password123' in html
print(f"  > Demo helper pills containing password123 in HTML: {'FOUND (VULNERABLE)' if has_demo_pill else 'NONE (SECURED)'}")

# Submit login with password123
data = urllib.parse.urlencode({'csrf_token': csrf, 'email': 'admin@studio3.com', 'password': 'password123'}).encode('utf-8')
req = urllib.request.Request(f"{BASE_URL}/login.php", data=data, method='POST')
login_resp = opener.open(req)
login_html = login_resp.read().decode('utf-8')

# It MUST fail closed: no session established, error message displayed
is_logged_in = "dashboard.php" in login_resp.url
print(f"  > Attempting login with shared password123: {'ALLOWED (CRITICAL FAIL)' if is_logged_in else 'REJECTED (PASSED)'}")

has_secure_msg = "not been provisioned with a cryptographic password" in login_html or "migration_v5_auth.sql" in login_html
print(f"  > Secure fail-closed guidance message displayed: {'YES (PASSED)' if has_secure_msg else 'NO'}")

# 2. VERIFY CSRF PROTECTION ON STATE-CHANGING ENDPOINTS
print("\n[TEST 2] VERIFY CSRF PROTECTION ON MUTATING REQUESTS")
endpoints = [
    ('/api/customers.php', json.dumps({'action': 'create', 'name': 'Fake'}).encode()),
    ('/api/leads.php?action=create', urllib.parse.urlencode({'notes': 'Test'}).encode()),
    ('/api/auth.php', json.dumps({'action': 'create_user', 'email': 'fake@test.com'}).encode()),
]

for ep, post_data in endpoints:
    req = urllib.request.Request(f"{BASE_URL}{ep}", data=post_data, method='POST', headers={'Content-Type': 'application/json'})
    try:
        r = opener.open(req)
        print(f"  > {ep} without session/CSRF: HTTP {r.status} (FAILED - should be 401/403)")
    except urllib.error.HTTPError as e:
        print(f"  > {ep} without session/CSRF: HTTP {e.code} (REJECTED AS EXPECTED)")

# 3. VERIFY BRUTE-FORCE RATE LIMITING
print("\n[TEST 3] VERIFY LOGIN BRUTE-FORCE RATE LIMITING")
rate_triggered = False
for i in range(8):
    data = urllib.parse.urlencode({'csrf_token': csrf, 'email': 'admin@studio3.com', 'password': f'badpass{i}'}).encode('utf-8')
    req = urllib.request.Request(f"{BASE_URL}/login.php", data=data, method='POST')
    r = opener.open(req)
    h = r.read().decode('utf-8')
    if "Too many failed login attempts" in h:
        rate_triggered = True
        print(f"  > Rate limiting triggered at attempt #{i+1}: 'Too many failed login attempts'")
        break

print(f"  > Brute-force rate limiting active: {'YES (PASSED)' if rate_triggered else 'NO (CHECK POLICY)'}")

# 4. VERIFY REST ENDPOINTS FAIL-CLOSED FOR UNAUTHENTICATED CALLS
print("\n[TEST 4] VERIFY API AUTHENTICATION ENFORCEMENT")
api_probes = [
    '/api/customers.php?action=list',
    '/api/leads.php',
    '/api/health.php',
    '/api/activity.php',
    '/api/chart-data.php',
    '/api/auth.php?action=users'
]

clean_opener = urllib.request.build_opener()
all_401 = True
for path in api_probes:
    try:
        clean_opener.open(f"{BASE_URL}{path}")
        print(f"  > {path}: UNEXPECTED 200 (AUTH BYPASS)")
        all_401 = False
    except urllib.error.HTTPError as e:
        if e.code != 401:
            all_401 = False
        print(f"  > {path}: HTTP {e.code} (Secure)")

print(f"  > All protected API endpoints enforce HTTP 401: {'YES (PASSED)' if all_401 else 'NO'}")

# 5. VERIFY SERVICE-ROLE KEY IS ABSENT FROM FRONTEND ASSETS
print("\n[TEST 5] VERIFY SERVICE KEY CONTAINMENT IN CLIENT CODE")
import os
import glob

service_key_found = False
client_files = glob.glob('assets/js/**/*.js', recursive=True) + glob.glob('assets/css/**/*.css', recursive=True)
for cf in client_files:
    with open(cf, 'r', encoding='utf-8', errors='ignore') as f:
        content = f.read()
        if "sb_secret_" in content or "SERVICE_KEY" in content:
            print(f"  > Found secret reference in {cf}!")
            service_key_found = True

print(f"  > Client assets clean of service keys: {'YES (PASSED)' if not service_key_found else 'LEAK DETECTED'}")

print("\n======================================================================")
print("  SECURITY AUDIT TEST EXECUTION COMPLETE")
print("======================================================================")
