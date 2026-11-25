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

print("=== VERIFYING CWFMS MODULES & LIVE DATA (READ-ONLY) ===")

# 1. Customers
req_cust = urllib.request.Request(f"{supabase_url}/rest/v1/customers?select=id,name,email,mobile_number,city,state&order=created_at.desc", headers=headers)
with urllib.request.urlopen(req_cust) as resp:
    custs = json.loads(resp.read().decode('utf-8'))
    print(f"\n1. CUSTOMERS MODULE:")
    print(f"   Total live customer records: {len(custs)}")
    for i, c in enumerate(custs[:5]):
        print(f"   [{i+1}] ID: {c.get('id')} | Name: {c.get('name')} | City: {c.get('city')}")

# 2. Leads
req_leads = urllib.request.Request(f"{supabase_url}/rest/v1/leads?select=id,submission_id,customer_id,lead_status,lead_source,sport,assigned_to&order=created_at.desc", headers=headers)
with urllib.request.urlopen(req_leads) as resp:
    leads = json.loads(resp.read().decode('utf-8'))
    print(f"\n2. LEADS MODULE:")
    print(f"   Total live lead records: {len(leads)}")
    for i, l in enumerate(leads):
        cust_match = [c for c in custs if c.get('id') == l.get('customer_id')]
        cust_name = cust_match[0].get('name') if cust_match else 'Unlinked'
        print(f"   [{i+1}] Lead ID: {l.get('id')} | Submission: {l.get('submission_id')} | Status: {l.get('lead_status')} | Sport: {l.get('sport')} | Customer: {cust_name}")

# 3. Activity Logs
req_act = urllib.request.Request(f"{supabase_url}/rest/v1/activity_logs?select=id,action,description,created_at&limit=5&order=created_at.desc", headers=headers)
with urllib.request.urlopen(req_act) as resp:
    activities = json.loads(resp.read().decode('utf-8'))
    print(f"\n3. ACTIVITY LOGS MODULE:")
    print(f"   Total recent activities retrieved: {len(activities)}")
    for a in activities[:3]:
        print(f"   - Action: {a.get('action')} | Description: {a.get('description')} | Created: {a.get('created_at')}")

# 4. Import Logs
req_imp = urllib.request.Request(f"{supabase_url}/rest/v1/import_logs?limit=5", headers=headers)
with urllib.request.urlopen(req_imp) as resp:
    imports = json.loads(resp.read().decode('utf-8'))
    print(f"\n4. IMPORT LOGS MODULE:")
    print(f"   Total import logs records: {len(imports)}")
