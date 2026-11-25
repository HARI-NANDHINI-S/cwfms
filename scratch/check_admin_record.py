import urllib.request
import json

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

# Query user_roles without password_hash
req = urllib.request.Request(
    f"{supabase_url}/rest/v1/user_roles?user_email=eq.admin@studio3.com",
    headers={'apikey': service_key, 'Authorization': f'Bearer {service_key}', 'Content-Type': 'application/json'}
)

with urllib.request.urlopen(req) as resp:
    data = json.loads(resp.read().decode('utf-8'))
    print("ADMIN RECORD CHECK:")
    if data:
        u = data[0]
        print(f"UUID:       {u.get('id')}")
        print(f"Name:       {u.get('user_name')}")
        print(f"Email:      {u.get('user_email')}")
        print(f"Role:       {u.get('role')}")
        print(f"Is Active:  {u.get('is_active')}")
        print(f"Columns:    {list(u.keys())}")
    else:
        print("Admin user not found!")
