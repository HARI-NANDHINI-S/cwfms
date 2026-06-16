import urllib.request
import json
import ssl

url = 'https://fjahjldhflcmnargcood.supabase.co/rest/v1/user_roles?select=*&limit=5'
headers = {
    'apikey': 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZqYWhqbGRoZmxjbW5hcmdjb29kIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc3NDcwNzIwNSwiZXhwIjoyMDkwMjgzMjA1fQ.mcZo3hhmvIEjVd7bippKoRZ6pFfCse3KToKndXL_0tA',
    'Authorization': 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZqYWhqbGRoZmxjbW5hcmdjb29kIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc3NDcwNzIwNSwiZXhwIjoyMDkwMjgzMjA1fQ.mcZo3hhmvIEjVd7bippKoRZ6pFfCse3KToKndXL_0tA',
    'Content-Type': 'application/json'
}

req = urllib.request.Request(url, headers=headers)
context = ssl._create_unverified_context()

try:
    with urllib.request.urlopen(req, context=context) as response:
        html = response.read()
        data = json.loads(html)
        print("SUCCESS! User roles retrieved:")
        print(json.dumps(data, indent=2))
except Exception as e:
    print("FAILED:", e)
