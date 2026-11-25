import os
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

supabase_url = env_vars.get("SUPABASE_URL", "")
service_key = env_vars.get("SUPABASE_SERVICE_KEY", "")

req = urllib.request.Request(
    f"{supabase_url}/rest/v1/user_roles?limit=1",
    headers={
        "apikey": service_key,
        "Authorization": f"Bearer {service_key}",
        "Content-Type": "application/json"
    }
)

try:
    with urllib.request.urlopen(req) as resp:
        data = json.loads(resp.read().decode('utf-8'))
        print("user_roles row 0 keys:")
        if data:
            print(list(data[0].keys()))
            print("Full row (masked):", {k: (v if k != 'password_hash' else '***') for k, v in data[0].items()})
        else:
            print("Table empty")
except urllib.error.HTTPError as e:
    err_body = e.read().decode('utf-8')
    print(f"HTTPError {e.code}: {err_body}")
except Exception as e:
    print(f"Error: {e}")
