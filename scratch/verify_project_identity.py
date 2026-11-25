import urllib.request
import json
import re
import os

env_vars = {}
with open('.env', 'r', encoding='utf-8') as f:
    for line in f:
        line = line.strip()
        if not line or line.startswith('#') or '=' not in line:
            continue
        k, v = line.split('=', 1)
        env_vars[k.strip()] = v.strip().strip("'\"")

url = env_vars.get('SUPABASE_URL', '')
ref = env_vars.get('SUPABASE_PROJECT_REF', '')
key = env_vars.get('SUPABASE_SERVICE_KEY', '')

# Extract project ref from host
m = re.search(r'https://([^.]+)\.supabase\.co', url)
host_ref = m.group(1) if m else 'UNKNOWN'

print("=== 1. SUPABASE PROJECT IDENTITY VERIFICATION ===")
print(f"Loaded SUPABASE_URL Host:       https://{host_ref}.supabase.co")
print(f"Extracted Project Reference:    {host_ref}")
print(f"Configured SUPABASE_PROJECT_REF: {ref}")
print(f"Ref Match:                       {'CONFIRMED' if host_ref == ref else 'MISMATCH'}")
print(f"Key Type Detected:              {'sb_secret_ (Modern)' if key.startswith('sb_secret_') else 'Legacy JWT'}")
print(f"Key Length:                     {len(key)} chars (REDACTED)")

# Query live OpenAPI definitions to inspect actual tables in this project
req = urllib.request.Request(f"{url}/rest/v1/", headers={'apikey': key, 'Accept': 'application/openapi+json, application/json'})
with urllib.request.urlopen(req) as resp:
    spec = json.loads(resp.read().decode('utf-8'))
    tables = sorted(list(spec.get('definitions', {}).keys()))
    print(f"\nTables present in connected project: {tables}")
    
    # Check for CWFMS tables
    cwfms_tables = ['customers', 'leads', 'user_roles', 'activity_logs', 'import_logs']
    has_cwfms = all(t in tables for t in cwfms_tables)
    print(f"Contains all CWFMS tables:       {'YES (CONFIRMED)' if has_cwfms else 'NO'}")
    
    # Check for First Database tables (projects, tasks, analyzers, conflicts)
    other_app_tables = ['projects', 'tasks', 'analyzers', 'conflicts']
    has_other = any(t in tables for t in other_app_tables)
    print(f"Contains 1st Database tables:   {'NO (CONFIRMED ISOLATED)' if not has_other else 'YES (WARNING - WRONG DB)'}")
    
    if has_cwfms and not has_other:
        print("\n>>> RESULT: POSITIVELY VERIFIED AS THE INTENDED CWFMS SECOND DATABASE <<<")
