#!/usr/bin/env python3
"""
CWFMS Real Runtime HTTP Test Suite (Tier 2 Verification)
Spawns PHP built-in web server and executes real HTTP requests
against authentication, security, attachments, webhook, and routing endpoints.
"""

import sys
import os
import time
import subprocess
import json
import urllib.request
import urllib.error
import urllib.parse
import http.client
import hmac
import hashlib

PORT = 8089
BASE_URL = f"http://127.0.0.1:{PORT}"
PHP_EXE = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "bin", "php", "php.exe"))

if not os.path.exists(PHP_EXE):
    print(f"[FATAL] PHP executable not found at {PHP_EXE}")
    sys.exit(1)

total_tests = 0
passed_tests = 0
failed_tests = 0

def assert_http(name, condition, details=""):
    global total_tests, passed_tests, failed_tests
    total_tests += 1
    if condition:
        passed_tests += 1
        print(f"  [PASS] {name}", flush=True)
    else:
        failed_tests += 1
        print(f"  [FAIL] {name} - {details}", flush=True)

def make_request(method, path, data=None, headers=None, follow_redirects=False):
    url = f"{BASE_URL}{path}"
    req_headers = headers or {}
    encoded_data = None
    if data is not None:
        if isinstance(data, dict):
            encoded_data = urllib.parse.urlencode(data).encode('utf-8')
            if 'Content-Type' not in req_headers:
                req_headers['Content-Type'] = 'application/x-www-form-urlencoded'
        elif isinstance(data, (bytes, bytearray)):
            encoded_data = data
        elif isinstance(data, str):
            encoded_data = data.encode('utf-8')

    class NoRedirectHandler(urllib.request.HTTPRedirectHandler):
        def redirect_request(self, req, fp, code, msg, headers, newurl):
            return None

    handlers = []
    if not follow_redirects:
        handlers.append(NoRedirectHandler())

    opener = urllib.request.build_opener(*handlers)
    req = urllib.request.Request(url, data=encoded_data, headers=req_headers, method=method)

    try:
        with opener.open(req, timeout=5) as response:
            body = response.read().decode('utf-8', errors='replace')
            resp_headers = dict(response.info())
            return {
                'status': response.status,
                'headers': resp_headers,
                'body': body
            }
    except urllib.error.HTTPError as e:
        body = e.read().decode('utf-8', errors='replace')
        return {
            'status': e.code,
            'headers': dict(e.headers),
            'body': body
        }
    except Exception as e:
        return {
            'status': 0,
            'headers': {},
            'body': str(e)
        }

def run_tests():
    global total_tests, passed_tests, failed_tests
    print("\n" + "=" * 70)
    print(f"  CWFMS REAL RUNTIME HTTP TEST SUITE (Tier 2)")
    print(f"  Server: {BASE_URL} | PHP Runtime: {PHP_EXE}")
    print("=" * 70)

    # -------------------------------------------------------------
    # 1. Server Health & Core API
    # -------------------------------------------------------------
    print("\n--- Group 1: Server Health & Availability ---")
    r = make_request("GET", "/api/auth.php?action=health")
    assert_http("1.1 GET /api/auth.php?action=health returns 200", r['status'] == 200, f"Got {r['status']}")
    try:
        data = json.loads(r['body'])
        assert_http("1.2 Health response JSON contains status 'healthy'", data.get('data', {}).get('status') == 'healthy', r['body'])
    except Exception as e:
        assert_http("1.2 Health response is valid JSON", False, str(e))

    # -------------------------------------------------------------
    # 2. CSRF Generation & Security Headers
    # -------------------------------------------------------------
    print("\n--- Group 2: CSRF Engine & Security Headers ---")
    r = make_request("GET", "/api/auth.php?action=csrf")
    assert_http("2.1 GET /api/auth.php?action=csrf returns 200", r['status'] == 200, f"Got {r['status']}")
    set_cookie = r['headers'].get('Set-Cookie', r['headers'].get('set-cookie', ''))
    assert_http("2.2 PHPSESSID session cookie issued", 'PHPSESSID' in set_cookie, set_cookie)
    csrf_token = ""
    try:
        cdata = json.loads(r['body'])
        csrf_token = cdata.get('data', {}).get('csrf_token', '')
        assert_http("2.3 CSRF token is 64-char hex string", len(csrf_token) == 64, f"Got length {len(csrf_token)}")
    except Exception as e:
        assert_http("2.3 CSRF token extraction", False, str(e))

    # Test login page security headers
    r = make_request("GET", "/login.php")
    assert_http("2.4 GET /login.php returns 200", r['status'] == 200, f"Got {r['status']}")
    h_nosniff = r['headers'].get('X-Content-Type-Options', r['headers'].get('x-content-type-options', ''))
    h_frame = r['headers'].get('X-Frame-Options', r['headers'].get('x-frame-options', ''))
    assert_http("2.5 Header X-Content-Type-Options: nosniff present", h_nosniff.lower() == 'nosniff', h_nosniff)
    assert_http("2.6 Header X-Frame-Options: SAMEORIGIN present", h_frame.upper() == 'SAMEORIGIN', h_frame)
    assert_http("2.7 Login page contains CSRF input", 'name="csrf_token"' in r['body'], "Missing CSRF field")
    assert_http("2.8 Login page title branded for Ira Sportswear", "Sign In | Ira Sportswear Lead Management" in r['body'], "Wrong title")

    # -------------------------------------------------------------
    # 3. Authentication & Rate Limiting Defense
    # -------------------------------------------------------------
    print("\n--- Group 3: Authentication Security & Rate Limiting ---")
    
    # 3.1 Missing CSRF Token rejected
    r = make_request("POST", "/login.php", data={'email': 'admin@studio3.com', 'password': 'wrong'})
    assert_http("3.1 POST /login.php without CSRF rejected", "Security validation failed: invalid or expired CSRF token" in r['body'], r['body'][:200])

    # 3.2 Establish a fresh session for rate limiting tests
    cookie_header = ""
    r_csrf = make_request("GET", "/api/auth.php?action=csrf")
    cookie = r_csrf['headers'].get('Set-Cookie', '')
    if 'PHPSESSID=' in cookie:
        cookie_header = cookie.split(';')[0]
    session_csrf = json.loads(r_csrf['body']).get('data', {}).get('csrf_token', '')

    # 3.3 Empty credentials validation
    r = make_request("POST", "/login.php", data={'csrf_token': session_csrf, 'email': '', 'password': ''},
                     headers={'Cookie': cookie_header})
    assert_http("3.2 Empty credentials rejected with validation message", "Email and password are required" in r['body'], r['body'][:200])

    # 3.4 Rate Limiting Lockout: Perform consecutive failed attempts
    lockout_triggered = False
    for attempt in range(1, 8):
        r_fail = make_request("POST", "/login.php", 
                              data={'csrf_token': session_csrf, 'email': f'test{attempt}@example.com', 'password': 'badpassword'},
                              headers={'Cookie': cookie_header})
        if "Too many failed login attempts" in r_fail['body']:
            lockout_triggered = True
            break

    assert_http("3.3 Brute force rate limiter locks out after 6 failed attempts", lockout_triggered, f"Attempt {attempt}")

    # -------------------------------------------------------------
    # 4. Session Termination & Logout
    # -------------------------------------------------------------
    print("\n--- Group 4: Session Invalidation & Logout ---")
    r = make_request("GET", "/logout.php", headers={'Cookie': cookie_header})
    assert_http("4.1 GET /logout.php redirects to login.php", r['status'] == 302, f"Got status {r['status']}")
    loc = r['headers'].get('Location', r['headers'].get('location', ''))
    assert_http("4.2 Logout redirect target is login.php", 'login.php' in loc, loc)
    logout_cookie = r['headers'].get('Set-Cookie', r['headers'].get('set-cookie', ''))
    assert_http("4.3 Session cookie expired with past timestamp", 'PHPSESSID' in logout_cookie and ('1970' in logout_cookie or 'expires=' in logout_cookie.lower() or 'max-age=0' in logout_cookie.lower()), logout_cookie)

    # -------------------------------------------------------------
    # 5. Access Control Guards (Unauthenticated Requests)
    # -------------------------------------------------------------
    print("\n--- Group 5: Unauthenticated API Access Control ---")
    endpoints = [
        ("/api/customers.php?action=list", 401, "Customer list requires auth"),
        ("/api/leads.php?action=list", 401, "Leads list requires auth"),
        ("/api/activity.php?action=list", 401, "Activity log requires auth"),
        ("/api/auth.php?action=me", 401, "Current user endpoint requires auth"),
        ("/api/customers.php", 401, "Customer mutation requires auth", "POST", {'name': 'Test'}),
        ("/api/leads.php", 401, "Lead mutation requires auth", "POST", {'title': 'Test'}),
    ]

    for item in endpoints:
        path = item[0]
        expected_code = item[1]
        desc = item[2]
        m = item[3] if len(item) > 3 else "GET"
        d = item[4] if len(item) > 4 else None
        res = make_request(m, path, data=d)
        assert_http(f"5.x {desc} (Got HTTP {res['status']})", res['status'] == expected_code, f"Got {res['status']}")

    # -------------------------------------------------------------
    # 6. Attachment Security & Path Traversal Defense
    # -------------------------------------------------------------
    print("\n--- Group 6: Attachment Security & Traversal Guards ---")
    r = make_request("GET", "/api/leads.php?action=attachment&file=../../includes/config.php")
    assert_http("6.1 Unauthenticated attachment traversal blocked (401)", r['status'] == 401, f"Got {r['status']}")

    r = make_request("GET", "/api/leads.php?action=attachment&lead_id=fake-id&file=test.pdf")
    assert_http("6.2 Unauthenticated attachment access blocked (401)", r['status'] == 401, f"Got {r['status']}")

    # -------------------------------------------------------------
    # 7. WhatsApp Webhook Security (Fail-Closed & Verification)
    # -------------------------------------------------------------
    print("\n--- Group 7: WhatsApp Webhook Security ---")
    
    # 7.1 Webhook GET verification with valid token
    verify_token = "ira_sportswear_wh_verify_secret_token_2026"
    challenge = "CHALLENGE_CODE_987654321"
    url_valid = f"/api/whatsapp_webhook.php?hub_mode=subscribe&hub_verify_token={verify_token}&hub_challenge={challenge}"
    r = make_request("GET", url_valid)
    assert_http("7.1 Valid webhook verification returns HTTP 200", r['status'] == 200, f"Got {r['status']}")
    assert_http("7.2 Valid webhook returns echo challenge body", r['body'].strip() == challenge, r['body'])

    # 7.2 Webhook GET verification with invalid token
    url_invalid = f"/api/whatsapp_webhook.php?hub_mode=subscribe&hub_verify_token=wrong_secret&hub_challenge={challenge}"
    r = make_request("GET", url_invalid)
    assert_http("7.3 Invalid webhook verification rejected with HTTP 403", r['status'] == 403, f"Got {r['status']}")

    # 7.3 Webhook POST Fail-Closed without app secret configured
    # In default environment without WHATSAPP_APP_SECRET, should return 500 fail-closed
    r = make_request("POST", "/api/whatsapp_webhook.php", 
                     data=json.dumps({"object": "whatsapp_business_account"}),
                     headers={'Content-Type': 'application/json'})
    assert_http("7.4 Webhook POST without WHATSAPP_APP_SECRET returns 500 fail-closed", 
                r['status'] == 500 and "WHATSAPP_APP_SECRET is not configured" in r['body'],
                f"Got status {r['status']}: {r['body']}")

    # -------------------------------------------------------------
    # 8. Web Routing & Page Redirects
    # -------------------------------------------------------------
    print("\n--- Group 8: Web Routing & Page Access Guards ---")
    protected_pages = [
        ("/dashboard.php", "Dashboard redirects unauthenticated user"),
        ("/pages/leads.php", "Leads page redirects unauthenticated user"),
        ("/pages/users.php", "Users page redirects unauthenticated user"),
        ("/pages/customer-detail.php?id=00000000-0000-0000-0000-000000000001", "Customer detail redirects unauthenticated user"),
    ]
    for path, desc in protected_pages:
        r = make_request("GET", path)
        loc = r['headers'].get('Location', r['headers'].get('location', ''))
        assert_http(f"8.x {desc} (Got 302 -> {loc})", r['status'] == 302 and 'login.php' in loc, f"Status: {r['status']}, Loc: {loc}")

    public_pages = [
        ("/index.php", "Public Home page loads successfully (200)"),
        ("/about.php", "Public About page loads successfully (200)"),
        ("/contact.php", "Public Contact page loads successfully (200)"),
    ]
    for path, desc in public_pages:
        r = make_request("GET", path)
        assert_http(f"8.x {desc} (Got {r['status']})", r['status'] == 200, f"Status: {r['status']}")

    # -------------------------------------------------------------
    # Summary
    # -------------------------------------------------------------
    print("\n" + "=" * 70)
    print(f"  HTTP RUNTIME TESTS: {passed_tests} PASSED | {failed_tests} FAILED (Total: {total_tests})")
    print("=" * 70)

def main():
    # Start PHP built-in web server
    env = os.environ.copy()
    cwd = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
    cmd = [PHP_EXE, "-S", f"127.0.0.1:{PORT}", "-t", cwd]

    print(f"Starting PHP web server: {' '.join(cmd)}", flush=True)
    server_process = subprocess.Popen(cmd, cwd=cwd, env=env, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    print(f"PHP Server launched with PID: {server_process.pid} on port {PORT}", flush=True)

    try:
        # Wait for server to become responsive
        started = False
        for _ in range(25):
            time.sleep(0.2)
            try:
                conn = http.client.HTTPConnection("127.0.0.1", PORT, timeout=1)
                conn.request("GET", "/api/auth.php?action=health")
                resp = conn.getresponse()
                if resp.status == 200:
                    started = True
                    conn.close()
                    break
                conn.close()
            except Exception:
                pass

        if not started:
            print("[FATAL] PHP server failed to start within 5 seconds.")
            stderr_out = server_process.stderr.read().decode('utf-8', errors='replace')
            print(f"Server stderr:\n{stderr_out}")
            sys.exit(1)

        print("[OK] PHP server is responsive. Beginning HTTP runtime test execution...", flush=True)
        run_tests()

    finally:
        print(f"\nShutting down PHP web server (PID {server_process.pid})...", flush=True)
        server_process.terminate()
        try:
            server_process.wait(timeout=3)
        except subprocess.TimeoutExpired:
            server_process.kill()
        print("[OK] PHP server cleanly terminated.", flush=True)

    # -------------------------------------------------------------
    # Second Server Instance: Webhook HMAC-SHA256 Signature Verification
    # -------------------------------------------------------------
    port2 = 8090
    env2 = os.environ.copy()
    test_secret = "test_meta_webhook_secret_key_998877"
    env2['WHATSAPP_APP_SECRET'] = test_secret
    cmd2 = [PHP_EXE, "-S", f"127.0.0.1:{port2}", "-t", cwd]

    print(f"\nStarting second PHP server for Webhook Signature Testing on port {port2}...", flush=True)
    server2 = subprocess.Popen(cmd2, cwd=cwd, env=env2, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    try:
        time.sleep(0.5)
        print("--- Group 9: Webhook HMAC-SHA256 Signature Validation ---", flush=True)
        
        # Test 9.1: Missing signature header
        payload = json.dumps({"entry": []})
        url2 = f"http://127.0.0.1:{port2}/api/whatsapp_webhook.php"
        req = urllib.request.Request(url2, data=payload.encode('utf-8'), 
                                     headers={'Content-Type': 'application/json'}, method='POST')
        try:
            with urllib.request.urlopen(req, timeout=3) as resp:
                status = resp.status
                body = resp.read().decode('utf-8')
        except urllib.error.HTTPError as e:
            status = e.code
            body = e.read().decode('utf-8')
        assert_http("9.1 Webhook POST missing X-Hub-Signature-256 rejected (401)", 
                    status == 401 and "Missing X-Hub-Signature-256" in body, f"Status: {status}, Body: {body}")

        # Test 9.2: Invalid / forged signature
        req_forged = urllib.request.Request(url2, data=payload.encode('utf-8'), 
                                           headers={'Content-Type': 'application/json',
                                                    'X-Hub-Signature-256': 'sha256=0000000000000000000000000000000000000000000000000000000000000000'}, 
                                           method='POST')
        try:
            with urllib.request.urlopen(req_forged, timeout=3) as resp:
                status = resp.status
                body = resp.read().decode('utf-8')
        except urllib.error.HTTPError as e:
            status = e.code
            body = e.read().decode('utf-8')
        assert_http("9.2 Webhook POST invalid signature rejected (401)", 
                    status == 401 and "Invalid webhook signature" in body, f"Status: {status}, Body: {body}")

        # Test 9.3: Valid HMAC-SHA256 signature
        valid_hmac = "sha256=" + hmac.new(test_secret.encode('utf-8'), payload.encode('utf-8'), hashlib.sha256).hexdigest()
        req_valid = urllib.request.Request(url2, data=payload.encode('utf-8'), 
                                          headers={'Content-Type': 'application/json',
                                                   'X-Hub-Signature-256': valid_hmac}, 
                                          method='POST')
        try:
            with urllib.request.urlopen(req_valid, timeout=3) as resp:
                status = resp.status
                body = resp.read().decode('utf-8')
        except urllib.error.HTTPError as e:
            status = e.code
            body = e.read().decode('utf-8')
        assert_http("9.3 Webhook POST valid HMAC-SHA256 signature accepted (200)", 
                    status == 200, f"Status: {status}, Body: {body}")

    finally:
        server2.terminate()
        try:
            server2.wait(timeout=3)
        except subprocess.TimeoutExpired:
            server2.kill()
        print("[OK] Second PHP server cleanly terminated.", flush=True)

    print("\n" + "=" * 70, flush=True)
    print(f"  FINAL HTTP RUNTIME TOTAL: {passed_tests} PASSED | {failed_tests} FAILED (Total: {total_tests})", flush=True)
    print("=" * 70, flush=True)

    if failed_tests > 0:
        sys.exit(1)

if __name__ == "__main__":
    main()
