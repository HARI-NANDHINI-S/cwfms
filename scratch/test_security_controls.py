import urllib.request
import urllib.parse
import http.cookiejar
import re

cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

login_url = "http://127.0.0.1:8000/login.php"

print("=== TESTING HTTP LOGIN SECURITY CONTROLS ===")

# Test A: Invalid CSRF Token
post_data_bad_csrf = urllib.parse.urlencode({
    'csrf_token': 'bad_or_tampered_csrf_token',
    'email': 'admin@studio3.com',
    'password': 'AnyPassword123!'
}).encode('utf-8')

req = urllib.request.Request(login_url, data=post_data_bad_csrf, headers={'Content-Type': 'application/x-www-form-urlencoded'})
try:
    with opener.open(req) as resp:
        body = resp.read().decode('utf-8')
        if "Security validation failed" in body or "CSRF" in body:
            print("A. Invalid CSRF Token: REJECTED AS EXPECTED (Security validation failed)")
        else:
            print(f"A. Unexpected response for bad CSRF")
except Exception as e:
    print(f"A. Failed: {e}")

# Test B: Inactive / Nonexistent user
with opener.open(login_url) as resp:
    html = resp.read().decode('utf-8')
    match = re.search(r'name="csrf_token"\s+value="([a-f0-9]+)"', html)
    csrf_token = match.group(1) if match else None

post_data_nonexistent = urllib.parse.urlencode({
    'csrf_token': csrf_token,
    'email': 'nonexistent_user_999@example.com',
    'password': 'AnyPassword123!'
}).encode('utf-8')

req = urllib.request.Request(login_url, data=post_data_nonexistent, headers={'Content-Type': 'application/x-www-form-urlencoded'})
try:
    with opener.open(req) as resp:
        body = resp.read().decode('utf-8')
        if "Invalid email or password" in body:
            print("B. Non-existent User: REJECTED AS EXPECTED (Invalid email or password)")
        else:
            print("B. Output preview:", body[:200])
except Exception as e:
    print(f"B. Failed: {e}")

# Test C: Unauthenticated access to /dashboard.php
dashboard_url = "http://127.0.0.1:8000/dashboard.php"
# Clean cookie jar (unauthenticated)
clean_opener = urllib.request.build_opener()
try:
    # Need to handle redirect manually or inspect final URL
    req = urllib.request.Request(dashboard_url)
    with clean_opener.open(req) as resp:
        print(f"C. Unauthenticated /dashboard.php: redirected to {resp.geturl()}")
        if "login.php" in resp.geturl():
            print("   PROTECTED: Unauthenticated user redirected to login.php")
except Exception as e:
    print(f"C. Failed: {e}")
