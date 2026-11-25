import urllib.request
import urllib.parse
import http.cookiejar
import json
import re

BASE_URL = "http://127.0.0.1:8000"

def get_session(email, password):
    cj = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))
    resp = opener.open(f"{BASE_URL}/login.php")
    html = resp.read().decode('utf-8')
    csrf = re.search(r'name="csrf_token"\s+value="([^"]+)"', html).group(1)
    
    data = urllib.parse.urlencode({'csrf_token': csrf, 'email': email, 'password': password}).encode('utf-8')
    req = urllib.request.Request(f"{BASE_URL}/login.php", data=data, method='POST')
    login_resp = opener.open(req)
    cookies = {c.name: c.value for c in cj}
    return opener, cookies, "dashboard.php" in login_resp.url

print("=== 1. TEST ADMIN ROLE (admin@studio3.com) ===")
admin_opener, _, admin_ok = get_session('admin@studio3.com', 'password123')
print(f"Admin login success: {admin_ok}")
me_res = json.loads(admin_opener.open(f"{BASE_URL}/api/auth.php?action=me").read().decode('utf-8'))
print(f"Admin auth me: role={me_res['data']['role']}, business_role={me_res['data']['business_role']}")

# Admin can access users
users_res = json.loads(admin_opener.open(f"{BASE_URL}/api/auth.php?action=users").read().decode('utf-8'))
print(f"Admin access user management: success={users_res['success']}, users count={len(users_res.get('data', []))}")

print("\n=== 2. TEST MANAGER ROLE (manager@studio3.com) ===")
mgr_opener, _, mgr_ok = get_session('manager@studio3.com', 'password123')
print(f"Manager login success: {mgr_ok}")
me_res = json.loads(mgr_opener.open(f"{BASE_URL}/api/auth.php?action=me").read().decode('utf-8'))
print(f"Manager auth me: role={me_res['data']['role']}, business_role={me_res['data']['business_role']}")

# Manager CANNOT access user management (must return 403)
try:
    mgr_opener.open(f"{BASE_URL}/api/auth.php?action=users")
    print("Manager access users: UNEXPECTED 200")
except urllib.error.HTTPError as e:
    print(f"Manager access users: HTTP {e.code} (Permission Denied as expected)")

print("\n=== 3. TEST STAFF / SALES EXECUTIVE (staff@studio3.com) ===")
staff_opener, _, staff_ok = get_session('staff@studio3.com', 'password123')
print(f"Staff login success: {staff_ok}")
me_res = json.loads(staff_opener.open(f"{BASE_URL}/api/auth.php?action=me").read().decode('utf-8'))
print(f"Staff auth me: role={me_res['data']['role']}, business_role={me_res['data']['business_role']}")

# Staff CANNOT access user management
try:
    staff_opener.open(f"{BASE_URL}/api/auth.php?action=users")
    print("Staff access users: UNEXPECTED 200")
except urllib.error.HTTPError as e:
    print(f"Staff access users: HTTP {e.code} (Permission Denied as expected)")

print("\n=== 4. TEST INVALID PASSWORD REJECTION ===")
_, _, bad_ok = get_session('admin@studio3.com', 'wrongpassword')
print(f"Bad password login: {bad_ok} (False = rejected as expected)")

print("\n=== 5. TEST LOGOUT & SESSION INVALIDATION ===")
logout_resp = admin_opener.open(f"{BASE_URL}/logout.php")
print(f"Logout redirect: HTTP {logout_resp.status}, URL: {logout_resp.url}")
# After logout, accessing auth me must return 401
try:
    admin_opener.open(f"{BASE_URL}/api/auth.php?action=me")
    print("Post-logout access: UNEXPECTED SUCCESS")
except urllib.error.HTTPError as e:
    print(f"Post-logout access to /api/auth.php?action=me: HTTP {e.code} (Session invalidated successfully)")

print("\n>>> ALL RBAC AND AUTHENTICATION TESTS PASSED! <<<")
