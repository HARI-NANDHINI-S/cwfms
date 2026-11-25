import urllib.request
import json
import os

env_vars = {}
with open('.env', 'r', encoding='utf-8') as f:
    for line in f:
        line = line.strip()
        if not line or line.startswith('#') or '=' not in line:
            continue
        k, v = line.split('=', 1)
        env_vars[k.strip()] = v.strip().strip("'\"")

supabase_url = env_vars.get('SUPABASE_URL', '')
anon_key = env_vars.get('SUPABASE_ANON_KEY', '')
service_key = env_vars.get('SUPABASE_SERVICE_KEY', '')

print('=== PHASE 1: REVALIDATE LIVE SUPABASE CONNECTION ===')
# 1. Project Ref
print('1. Supabase Base URL:', supabase_url)
ref = supabase_url.replace('https://', '').split('.')[0]
print('   Project ref:', ref)
expected_ref = 'fjahjldhflcmnargcood'
print(f'   Matches expected ({expected_ref})?:', ref == expected_ref)

# 2. Check admin user in user_roles
req = urllib.request.Request(
    f"{supabase_url}/rest/v1/user_roles?user_email=eq.admin@studio3.com",
    headers={'apikey': service_key, 'Authorization': f'Bearer {service_key}'}
)
with urllib.request.urlopen(req) as resp:
    users = json.loads(resp.read().decode('utf-8'))
    print('\n2. Administrator Account Verification:')
    if users:
        u = users[0]
        print(f"   UUID:          {u.get('id')}")
        print(f"   Name:          {u.get('user_name')}")
        print(f"   Email:         {u.get('user_email')}")
        print(f"   Role:          {u.get('role')}")
        print(f"   Active:        {u.get('is_active')}")
        print(f"   password_hash: {'PRESENT' if 'password_hash' in u else 'ABSENT (not yet added)'}")
        print(f"   Columns present: {list(u.keys())}")
    else:
        print('   Admin user NOT found!')

# 3. Tables check
print('\n3. Target Database Tables Check:')
for t in ['activity_logs', 'customers', 'import_logs', 'leads', 'user_roles']:
    r = urllib.request.Request(
        f"{supabase_url}/rest/v1/{t}?limit=1",
        headers={'apikey': service_key, 'Authorization': f'Bearer {service_key}'}
    )
    try:
        with urllib.request.urlopen(r) as res:
            print(f"   Table '{t}': LIVE ACCESSIBLE (Status {res.status})")
    except Exception as e:
        print(f"   Table '{t}': FAILED ({e})")

# 4. Check that 1st database tables are absent
print('\n4. Disconnected First Database Tables (Should NOT exist in this project):')
for t in ['projects', 'tasks', 'analyzers', 'conflicts']:
    r = urllib.request.Request(
        f"{supabase_url}/rest/v1/{t}?limit=1",
        headers={'apikey': service_key, 'Authorization': f'Bearer {service_key}'}
    )
    try:
        with urllib.request.urlopen(r) as res:
            print(f"   Table '{t}': UNEXPECTEDLY FOUND (Status {res.status})")
    except urllib.error.HTTPError as e:
        if e.code == 404:
            print(f"   Table '{t}': CONFIRMED ABSENT (404 Not Found - Correct)")
        else:
            print(f"   Table '{t}': Error {e.code}")
    except Exception as e:
        print(f"   Table '{t}': {e}")
