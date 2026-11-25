import urllib.request
import json
import os
import sys

# Load env variables safely
env_vars = {}
with open('.env', 'r', encoding='utf-8') as f:
    for line in f:
        line = line.strip()
        if not line or line.startswith('#') or '=' not in line:
            continue
        k, v = line.split('=', 1)
        env_vars[k.strip()] = v.strip().strip("'\"")

supabase_url = env_vars.get('SUPABASE_URL', '')
service_key = env_vars.get('SUPABASE_SERVICE_KEY', '')

# Test 1: Query Supabase Auth GoTrue admin/users
url = f"{supabase_url}/auth/v1/admin/users"
headers = {
    'apikey': service_key,
    'Authorization': f'Bearer {service_key}'
}
req = urllib.request.Request(url, headers=headers)
try:
    with urllib.request.urlopen(req) as resp:
        data = json.loads(resp.read().decode('utf-8'))
        users = data.get('users', [])
        print(f"Supabase GoTrue (auth.users) total users: {len(users)}")
        for u in users:
            email = u.get('email', '')
            masked = email[:2] + '***@' + email.split('@')[-1] if '@' in email else email
            uid = u.get('id', '')
            print(f"  User: id={uid[:8]}..., email={masked}, created_at={u.get('created_at')}")
except urllib.error.HTTPError as e:
    print(f"GoTrue Admin Users HTTP Error {e.code}: {e.read().decode('utf-8')[:300]}")
except Exception as e:
    print(f"GoTrue Admin Users Error: {e}")
