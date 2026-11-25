import urllib.request
import urllib.parse
import http.cookiejar
import re

cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

print("=== TESTING REAL HTTP LOGIN ATTEMPT AGAINST LOCAL SERVER (PORT 8000) ===")
# 1. GET /login.php to fetch CSRF token and establish session
login_url = "http://127.0.0.1:8000/login.php"
try:
    with opener.open(login_url) as resp:
        html = resp.read().decode('utf-8')
        match = re.search(r'name="csrf_token"\s+value="([a-f0-9]+)"', html)
        csrf_token = match.group(1) if match else None
        print(f"1. GET /login.php status: {resp.status}")
        print(f"   CSRF token extracted: {csrf_token[:10]}... (length {len(csrf_token) if csrf_token else 0})")
except Exception as e:
    print(f"Failed to connect to local server: {e}")
    exit(1)

# 2. POST /login.php with admin@studio3.com
post_data = urllib.parse.urlencode({
    'csrf_token': csrf_token,
    'email': 'admin@studio3.com',
    'password': 'AttemptPassword2026!'
}).encode('utf-8')

req = urllib.request.Request(login_url, data=post_data, headers={'Content-Type': 'application/x-www-form-urlencoded'})

try:
    with opener.open(req) as resp:
        body = resp.read().decode('utf-8')
        print(f"2. POST /login.php response status: {resp.status}")
        print(f"   Final URL: {resp.geturl()}")
        
        # Check for error message
        err_match = re.search(r'<div class="login-alert[^"]*">([^<]+)</div>', body)
        if not err_match:
            err_match = re.search(r'<div class="alert[^"]*">([^<]+)</div>', body)
        if err_match:
            print(f"   Error message displayed: '{err_match.group(1).strip()}'")
        else:
            # Look for general error indicator
            if "Authentication unavailable" in body:
                print("   Found expected message: 'Authentication unavailable: this account has not been provisioned with a cryptographic password...'")
            elif "Invalid email or password" in body:
                print("   Found: 'Invalid email or password'")
            else:
                print(f"   Output preview: {body[:300]}")
except Exception as e:
    print(f"POST request failed: {e}")
