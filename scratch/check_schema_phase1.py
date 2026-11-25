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

print("=== PHASE 1: VERIFY DATABASE SCHEMA LIVE ===")
print("Project Base URL:", supabase_url)

# 1. Test selecting password_hash directly on user_roles
req = urllib.request.Request(
    f"{supabase_url}/rest/v1/user_roles?select=id,user_name,user_email,role,is_active,password_hash&user_email=eq.admin@studio3.com",
    headers={'apikey': service_key, 'Authorization': f'Bearer {service_key}', 'Content-Type': 'application/json'}
)

try:
    with urllib.request.urlopen(req) as resp:
        data = json.loads(resp.read().decode('utf-8'))
        print("HTTP Status: 200 OK")
        if data:
            admin_row = data[0]
            print("Administrator Record Found:")
            print(f"  UUID:          {admin_row.get('id')}")
            print(f"  Name:          {admin_row.get('user_name')}")
            print(f"  Email:         {admin_row.get('user_email')}")
            print(f"  Role:          {admin_row.get('role')}")
            print(f"  Is Active:     {admin_row.get('is_active')}")
            print(f"  password_hash: {'EXISTS (Value: ' + str(admin_row.get('password_hash'))[:10] + '...)' if admin_row.get('password_hash') else 'EXISTS (NULL / Empty - Ready for provisioning)'}")
            print("\nRESULT: password_hash column is CONFIRMED PRESENT in the live database!")
        else:
            print("ERROR: Administrator account admin@studio3.com not found!")
except urllib.error.HTTPError as e:
    err_body = e.read().decode('utf-8')
    print(f"HTTP Status: {e.code}")
    print(f"Response: {err_body}")
    if "password_hash" in err_body and ("does not exist" in err_body or "42703" in err_body):
        print("\nRESULT: password_hash column is STILL MISSING.")
except Exception as e:
    print(f"Connection error: {e}")
