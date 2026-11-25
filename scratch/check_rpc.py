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

req = urllib.request.Request(
    f"{supabase_url}/rest/v1/?apikey={service_key}",
    headers={'Authorization': f'Bearer {service_key}', 'Accept': 'application/openapi+json'}
)

try:
    with urllib.request.urlopen(req) as resp:
        spec = json.loads(resp.read().decode('utf-8'))
        paths = list(spec.get('paths', {}).keys())
        rpc_paths = [p for p in paths if p.startswith('/rpc/')]
        print(f"Total endpoints: {len(paths)}")
        print(f"RPC endpoints: {rpc_paths}")
        table_paths = [p for p in paths if not p.startswith('/rpc/') and p != '/']
        print(f"Tables: {table_paths}")
except Exception as e:
    print(f"Error fetching schema: {e}")
