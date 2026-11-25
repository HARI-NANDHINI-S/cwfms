import os
import json
import base64
import re

env_path = ".env"
if not os.path.exists(env_path):
    print("ERROR: .env file does not exist!")
    exit(1)

with open(env_path, "r", encoding="utf-8", errors="ignore") as f:
    lines = f.readlines()

env_vars = {}
for line in lines:
    line = line.strip()
    if not line or line.startswith("#") or "=" not in line:
        continue
    k, v = line.split("=", 1)
    k = k.strip()
    v = v.strip().strip("'\"")
    env_vars[k] = v

url = env_vars.get("SUPABASE_URL", "")
anon = env_vars.get("SUPABASE_ANON_KEY", "")
service = env_vars.get("SUPABASE_SERVICE_KEY", "")
ref = env_vars.get("SUPABASE_PROJECT_REF", "")

print(f"1. .env file loaded: YES ({len(lines)} lines)")
print(f"2. SUPABASE_URL present: {'YES' if url else 'NO'}")

# Extract project ref from URL
m = re.search(r'https://([^.]+)\.supabase\.co', url)
url_project = m.group(1) if m else "UNKNOWN_URL_FORMAT"
print(f"3. URL Project Host Ref: {url_project}")

def parse_key_info(token_name, token):
    if not token:
        print(f"   {token_name}: MISSING")
        return None
    if token.startswith("sb_publishable_"):
        print(f"   {token_name}: NEW FORMAT (publishable, length={len(token)}, prefix=sb_publishable_...)")
        return "sb_publishable", "publishable"
    if token.startswith("sb_secret_"):
        print(f"   {token_name}: NEW FORMAT (secret, length={len(token)}, prefix=sb_secret_...)")
        return "sb_secret", "secret"
    parts = token.split(".")
    if len(parts) == 3:
        payload_b64 = parts[1]
        rem = len(payload_b64) % 4
        if rem:
            payload_b64 += "=" * (4 - rem)
        try:
            decoded = base64.urlsafe_b64decode(payload_b64.encode('utf-8'))
            payload = json.loads(decoded)
            jwt_ref = payload.get("ref", "NO_REF")
            jwt_role = payload.get("role", "NO_ROLE")
            print(f"   {token_name}: LEGACY JWT (role={jwt_role}, ref={jwt_ref}, length={len(token)})")
            return jwt_ref, jwt_role
        except Exception as e:
            print(f"   {token_name}: JWT DECODE ERROR ({e})")
            return None
    print(f"   {token_name}: OPAQUE / OTHER FORMAT (length={len(token)})")
    return "opaque", "unknown"

print("4. Inspecting Keys (Security: Values Redacted):")
anon_info = parse_key_info("ANON_KEY", anon)
service_info = parse_key_info("SERVICE_KEY", service)

print(f"5. Project Ref in env: {ref}")
print(f"6. APP_ENV: {env_vars.get('APP_ENV', 'NOT_SET')}")

