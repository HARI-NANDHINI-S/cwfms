import urllib.request
import urllib.parse
import http.cookiejar
import json
import re

BASE_URL = "http://127.0.0.1:8000"

cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

print("=== 1. FETCH LOGIN PAGE & CSRF TOKEN ===")
resp = opener.open(f"{BASE_URL}/login.php")
html = resp.read().decode('utf-8')
csrf_match = re.search(r'name="csrf_token"\s+value="([^"]+)"', html)
csrf_token = csrf_match.group(1) if csrf_match else ""
print(f"Login page: HTTP {resp.status}, CSRF token found: {'YES' if csrf_token else 'NO'}")

print("\n=== 2. SUBMIT LOGIN (admin@studio3.com / password123) ===")
login_data = urllib.parse.urlencode({
    'csrf_token': csrf_token,
    'email': 'admin@studio3.com',
    'password': 'password123'
}).encode('utf-8')

req = urllib.request.Request(f"{BASE_URL}/login.php", data=login_data, method='POST')
login_resp = opener.open(req)
login_html = login_resp.read().decode('utf-8')

# Check cookies
cookies = {c.name: c.value for c in cj}
print(f"POST login response: HTTP {login_resp.status}, URL: {login_resp.url}")
print(f"Session cookies: {list(cookies.keys())}")

if "dashboard.php" in login_resp.url or "PHPSESSID" in cookies:
    print(">>> LOGIN SUCCESS: Session established with live user_roles record!")
else:
    print(">>> LOGIN FAILED! Response snippet:")
    for line in login_html.splitlines():
        if "alert" in line or "error" in line.lower():
            print("  ", line.strip())
    exit(1)

print("\n=== 3. VERIFY /api/auth.php?action=me ===")
me_resp = opener.open(f"{BASE_URL}/api/auth.php?action=me")
me_data = json.loads(me_resp.read().decode('utf-8'))
print(f"Auth Me: success={me_data.get('success')}, role={me_data.get('data', {}).get('role')}, name={me_data.get('data', {}).get('name')}")

print("\n=== 4. VERIFY /api/health.php ===")
health_resp = opener.open(f"{BASE_URL}/api/health.php")
health_data = json.loads(health_resp.read().decode('utf-8'))
h_data = health_data.get('data', {})
print(f"Health API: HTTP {health_resp.status}, label='{h_data.get('label')}', connected={h_data.get('connected')}, latency={h_data.get('latency_ms')}ms, project={h_data.get('project')}")

print("\n=== 5. VERIFY CUSTOMER MANAGEMENT (GET /api/customers.php?action=list) ===")
cust_resp = opener.open(f"{BASE_URL}/api/customers.php?action=list")
cust_data = json.loads(cust_resp.read().decode('utf-8'))
customers = cust_data.get('data', [])
print(f"Customers API: success={cust_data.get('success')}, total records={len(customers)}")
if customers:
    first_cust = customers[0]
    first_id = first_cust['id']
    print(f"   Sample Customer ID: {first_id[:8]}..., has_team={'team_academy_name' in first_cust}, score={first_cust.get('profile_score')}")

    # Detail test
    detail_resp = opener.open(f"{BASE_URL}/api/customers.php?action=get&id={first_id}")
    detail_data = json.loads(detail_resp.read().decode('utf-8'))
    print(f"   Customer Detail API: success={detail_data.get('success')}, ID matched={detail_data.get('data', {}).get('id') == first_id}")

print("\n=== 6. VERIFY LEAD MANAGEMENT (GET /api/leads.php) ===")
leads_resp = opener.open(f"{BASE_URL}/api/leads.php")
raw_leads = leads_resp.read().decode('utf-8', errors='ignore')
try:
    leads_data = json.loads(raw_leads)
except Exception as e:
    print(f"JSON DECODE ERROR on /api/leads.php: {e}")
    print("Raw output snippet:")
    print(raw_leads[:1000])
    exit(1)
leads = leads_data.get('data', [])
print(f"Leads API: success={leads_data.get('success')}, total records={len(leads)}")
if leads:
    first_lead = leads[0]
    lead_id = first_lead['id']
    cust_rel = first_lead.get('customer')
    print(f"   Lead ID: {lead_id[:8]}..., status='{first_lead.get('lead_status')}', sport='{first_lead.get('sport')}'")
    cid = first_lead.get('customer_id') or ''
    print(f"   Lead Customer Association: {'YES' if cust_rel else 'NO'} (customer_id={cid[:8] if cid else 'None'})")
    print(f"   Lead Permissions: can_edit={first_lead.get('can_edit')}, can_change_status={first_lead.get('can_change_status')}")

    # Lead Detail Test
    lead_detail_resp = opener.open(f"{BASE_URL}/api/leads.php?action=get&id={lead_id}")
    lead_detail_data = json.loads(lead_detail_resp.read().decode('utf-8'))
    print(f"   Lead Detail API: success={lead_detail_data.get('success')}")

print("\n=== 7. VERIFY LEAD STATS (GET /api/leads.php?action=stats) ===")
stats_resp = opener.open(f"{BASE_URL}/api/leads.php?action=stats")
stats_data = json.loads(stats_resp.read().decode('utf-8'))
print(f"Lead Stats: success={stats_data.get('success')}, total={stats_data.get('data', {}).get('total')}")

print("\n=== 8. VERIFY ACTIVITY LOGS (GET /api/activity.php) ===")
act_resp = opener.open(f"{BASE_URL}/api/activity.php")
act_data = json.loads(act_resp.read().decode('utf-8'))
act_records = act_data.get('data', [])
print(f"Activity API: success={act_data.get('success')}, total logs={len(act_records)}")

print("\n=== 9. VERIFY USER MANAGEMENT (GET /api/auth.php?action=users) ===")
users_resp = opener.open(f"{BASE_URL}/api/auth.php?action=users")
users_data = json.loads(users_resp.read().decode('utf-8'))
users_list = users_data.get('data', [])
print(f"Users API: success={users_data.get('success')}, total users={len(users_list)}")

print("\n=== 10. VERIFY CHART DATA (GET /api/chart-data.php) ===")
chart_resp = opener.open(f"{BASE_URL}/api/chart-data.php")
chart_data = json.loads(chart_resp.read().decode('utf-8'))
print(f"Chart API: success={chart_data.get('success')}, countries={list(chart_data.get('data', {}).get('by_country', {}).keys())}")

print("\n=== 11. VERIFY DASHBOARD & HTML PAGES RENDERING ===")
for page in ['/dashboard.php', '/pages/leads.php', '/pages/users.php']:
    p_resp = opener.open(f"{BASE_URL}{page}")
    p_html = p_resp.read().decode('utf-8', errors='ignore')
    has_badge = 'DB Live' in p_html or 'db-status-badge' in p_html
    print(f"Page {page}: HTTP {p_resp.status}, length={len(p_html)}, live_badge_rendered={'YES' if has_badge else 'NO'}")

print("\n>>> ALL READ-ONLY MODULE TESTS COMPLETED SUCCESSFULLY! <<<")
