import urllib.request
import json

health_url = "http://127.0.0.1:8000/api/health.php"
print("=== TESTING /api/health.php ===")
try:
    with urllib.request.urlopen(health_url) as resp:
        data = json.loads(resp.read().decode('utf-8'))
        print("Status code:", resp.status)
        print("Response JSON:")
        print(json.dumps(data, indent=2))
except Exception as e:
    print(f"Error testing health.php: {e}")
