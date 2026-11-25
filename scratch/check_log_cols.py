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

headers = {
    'apikey': service_key,
    'Authorization': f'Bearer {service_key}',
    'Content-Type': 'application/json'
}

for tbl in ['activity_logs', 'import_logs']:
    req = urllib.request.Request(f"{supabase_url}/rest/v1/{tbl}?limit=1", headers=headers)
    try:
        with urllib.request.urlopen(req) as resp:
            data = json.loads(resp.read().decode('utf-8'))
            print(f"Table '{tbl}' columns:")
            if data:
                print(list(data[0].keys()))
            else:
                print("(empty table)")
    except urllib.error.HTTPError as e:
        print(f"Error {tbl}: {e.code} {e.read().decode('utf-8')}")
