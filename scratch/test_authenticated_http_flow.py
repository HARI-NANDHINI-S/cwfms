import subprocess
import urllib.request
import json
import os

session_id = "cwfms_test_admin_session_8877"

# 1. Create authenticated session via PHP CLI
php_script = f"""
session_id('{session_id}');
session_start();
$_SESSION['user'] = [
    'id' => '71c5032a-ede6-4791-8fb7-fdb41cd59f31',
    'name' => 'Admin User',
    'email' => 'admin@studio3.com',
    'role' => 'admin',
    'role_label' => 'Administrator',
    'business_role' => 'admin'
];
$_SESSION['login_time'] = time();
$_SESSION['last_activity'] = time();
$_SESSION['csrf_token'] = 'test_csrf_token_value_12345';
session_write_close();
echo 'Session created successfully';
"""

cmd = [r".\bin\php\php.exe", "-r", php_script]
res = subprocess.run(cmd, capture_output=True, text=True)
print("Session setup output:", res.stdout.strip())

headers = {
    "Cookie": f"PHPSESSID={session_id}",
    "Accept": "application/json"
}

# 2. Test /api/health.php
print("\n--- 1. Testing /api/health.php with session ---")
try:
    req = urllib.request.Request("http://127.0.0.1:8000/api/health.php", headers=headers)
    with urllib.request.urlopen(req) as resp:
        data = json.loads(resp.read().decode('utf-8'))
        print(f"Status: {resp.status}")
        print(f"Data: connected={data.get('data', {}).get('connected')}, label={data.get('data', {}).get('label')}, project={data.get('data', {}).get('project')}")
except Exception as e:
    print("Health check error:", e)

# 3. Test /api/customers.php
print("\n--- 2. Testing /api/customers.php (read-only) ---")
try:
    req = urllib.request.Request("http://127.0.0.1:8000/api/customers.php", headers=headers)
    with urllib.request.urlopen(req) as resp:
        data = json.loads(resp.read().decode('utf-8'))
        customers = data.get('data', [])
        print(f"Status: {resp.status}")
        print(f"Customer records count: {len(customers)}")
        if customers:
            print(f"Sample customer: ID={customers[0].get('id')}, Name={customers[0].get('name')}")
except Exception as e:
    print("Customers check error:", e)

# 4. Test /api/leads.php
print("\n--- 3. Testing /api/leads.php (read-only) ---")
try:
    req = urllib.request.Request("http://127.0.0.1:8000/api/leads.php", headers=headers)
    with urllib.request.urlopen(req) as resp:
        data = json.loads(resp.read().decode('utf-8'))
        leads = data.get('data', [])
        print(f"Status: {resp.status}")
        print(f"Lead records count: {len(leads)}")
        if leads:
            print(f"Sample lead: ID={leads[0].get('id')}, Title={leads[0].get('title')}, Customer ID={leads[0].get('customer_id')}")
except Exception as e:
    print("Leads check error:", e)

# 5. Test /api/auth.php?action=users
print("\n--- 4. Testing /api/auth.php?action=users (password_hash audit) ---")
try:
    req = urllib.request.Request("http://127.0.0.1:8000/api/auth.php?action=users", headers=headers)
    with urllib.request.urlopen(req) as resp:
        data = json.loads(resp.read().decode('utf-8'))
        users = data.get('data', [])
        print(f"Status: {resp.status}")
        print(f"User count returned: {len(users)}")
        has_hash = any("password_hash" in u for u in users)
        print(f"Is 'password_hash' exposed anywhere in user list?: {has_hash}")
        if users:
            print(f"Returned user keys: {list(users[0].keys())}")
except Exception as e:
    print("Users check error:", e)

# 6. Test /dashboard.php
print("\n--- 5. Testing /dashboard.php (HTML view) ---")
try:
    req = urllib.request.Request("http://127.0.0.1:8000/dashboard.php", headers={"Cookie": f"PHPSESSID={session_id}"})
    with urllib.request.urlopen(req) as resp:
        html = resp.read().decode('utf-8')
        print(f"Status: {resp.status}")
        print(f"Final URL: {resp.geturl()}")
        print(f"Contains 'Ira Sportswear'?: {'Ira Sportswear' in html}")
        print(f"Contains 'Dashboard'?: {'Dashboard' in html}")
except Exception as e:
    print("Dashboard check error:", e)

# 7. Cleanup session file
clean_script = f"""
session_id('{session_id}');
session_start();
session_destroy();
"""
subprocess.run([r".\bin\php\php.exe", "-r", clean_script])
print("\nTest session destroyed cleanly.")
