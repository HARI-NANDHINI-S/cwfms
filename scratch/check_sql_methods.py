import os
import shutil
import urllib.request
import json

print("=== CHECKING AUTHENTICATED SQL EXECUTION METHODS ===")

# Method 1: Supabase CLI
supabase_cli = shutil.which("supabase")
print(f"1. Supabase CLI binary in PATH: {'FOUND (' + supabase_cli + ')' if supabase_cli else 'NOT FOUND'}")

# Method 2: PostgreSQL direct connection (psql / DATABASE_URL)
psql_bin = shutil.which("psql")
print(f"2. psql binary in PATH:         {'FOUND (' + psql_bin + ')' if psql_bin else 'NOT FOUND'}")

env_vars = {}
with open('.env', 'r', encoding='utf-8') as f:
    for line in f:
        line = line.strip()
        if not line or line.startswith('#') or '=' not in line:
            continue
        k, v = line.split('=', 1)
        env_vars[k.strip()] = v.strip().strip("'\"")

db_url = env_vars.get("DATABASE_URL") or os.environ.get("DATABASE_URL")
db_pass = env_vars.get("SUPABASE_DB_PASSWORD") or os.environ.get("SUPABASE_DB_PASSWORD")
print(f"   DATABASE_URL configured:     {'YES' if db_url else 'NO'}")
print(f"   SUPABASE_DB_PASSWORD:        {'YES' if db_pass else 'NO'}")

# Method 3: Supabase Management API (Access Token)
sb_access_token = env_vars.get("SUPABASE_ACCESS_TOKEN") or os.environ.get("SUPABASE_ACCESS_TOKEN")
print(f"3. SUPABASE_ACCESS_TOKEN:       {'YES' if sb_access_token else 'NO'}")

# Test if service-role key or management API has a SQL execution endpoint (api.supabase.com)
service_key = env_vars.get("SUPABASE_SERVICE_KEY", "")
ref = env_vars.get("SUPABASE_PROJECT_REF", "")
if service_key:
    # Test if PostgREST /rpc has an exec or sql function
    pgrst_url = f"{env_vars.get('SUPABASE_URL')}/rest/v1/rpc/"
    req = urllib.request.Request(pgrst_url, headers={'apikey': service_key})
    try:
        urllib.request.urlopen(req)
        print("   PostgREST RPC endpoint:      REACHABLE")
    except urllib.error.HTTPError as e:
        print(f"   PostgREST RPC endpoint:      HTTP {e.code} (No generic SQL RPC declared)")
    except Exception as e:
        print(f"   PostgREST RPC endpoint:      {e}")

# Check Management API on api.supabase.com/v1/projects/{ref}/database/query with service_key
mgmt_url = f"https://api.supabase.com/v1/projects/{ref}/database/query"
req2 = urllib.request.Request(mgmt_url, headers={'Authorization': f'Bearer {service_key}'}, data=json.dumps({'query': 'SELECT 1;'}).encode('utf-8'), method='POST')
try:
    with urllib.request.urlopen(req2) as resp:
        print(f"   Supabase Management API with key: HTTP {resp.status} (SUCCESS)")
except urllib.error.HTTPError as e:
    print(f"   Supabase Management API with key: HTTP {e.code} ({e.read().decode('utf-8')[:150]})")
except Exception as e:
    print(f"   Supabase Management API with key: {e}")
