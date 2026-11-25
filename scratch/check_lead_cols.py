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

req_lead = urllib.request.Request(f"{supabase_url}/rest/v1/leads?limit=1", headers=headers)
try:
    with urllib.request.urlopen(req_lead) as resp:
        leads = json.loads(resp.read().decode('utf-8'))
        print("Lead columns:")
        if leads:
            print(list(leads[0].keys()))
            print("Lead sample:", leads[0])
        else:
            print("Leads table is empty")
except urllib.error.HTTPError as e:
    print(e.code, e.read().decode('utf-8'))
