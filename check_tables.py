import urllib.request
import json
import ssl

context = ssl._create_unverified_context()
headers = {
    'apikey': 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZqYWhqbGRoZmxjbW5hcmdjb29kIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc3NDcwNzIwNSwiZXhwIjoyMDkwMjgzMjA1fQ.mcZo3hhmvIEjVd7bippKoRZ6pFfCse3KToKndXL_0tA',
    'Authorization': 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZqYWhqbGRoZmxjbW5hcmdjb29kIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc3NDcwNzIwNSwiZXhwIjoyMDkwMjgzMjA1fQ.mcZo3hhmvIEjVd7bippKoRZ6pFfCse3KToKndXL_0tA',
}

# We can query postgrest schema info (root endpoint /)
url = 'https://fjahjldhflcmnargcood.supabase.co/rest/v1/'
req = urllib.request.Request(url, headers=headers)

try:
    with urllib.request.urlopen(req, context=context) as response:
        info = json.loads(response.read())
        print("TABLES:")
        for table in info.get('definitions', {}).keys():
            print(f"- {table}")
except Exception as e:
    print("FAILED:", e)
