import urllib.request
import json
import ssl
import time

context = ssl._create_unverified_context()
headers = {
    'apikey': 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZqYWhqbGRoZmxjbW5hcmdjb29kIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc3NDcwNzIwNSwiZXhwIjoyMDkwMjgzMjA1fQ.mcZo3hhmvIEjVd7bippKoRZ6pFfCse3KToKndXL_0tA',
    'Authorization': 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZqYWhqbGRoZmxjbW5hcmdjb29kIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc3NDcwNzIwNSwiZXhwIjoyMDkwMjgzMjA1fQ.mcZo3hhmvIEjVd7bippKoRZ6pFfCse3KToKndXL_0tA',
    'Content-Type': 'application/json',
    'Prefer': 'return=representation'
}

# Fetch first customer
url = 'https://fjahjldhflcmnargcood.supabase.co/rest/v1/customers?select=*&limit=1'
req = urllib.request.Request(url, headers=headers)

try:
    with urllib.request.urlopen(req, context=context) as r:
        customer = json.loads(r.read())[0]
        cust_id = customer['id']
        old_name = customer['name']
        old_updated_at = customer['updated_at']
        print(f"Customer: {old_name}, ID: {cust_id}, Updated At: {old_updated_at}")
        
        # Perform update
        update_url = f'https://fjahjldhflcmnargcood.supabase.co/rest/v1/customers?id=eq.{cust_id}'
        update_data = json.dumps({'name': old_name + ' Updated'}).encode('utf-8')
        update_req = urllib.request.Request(update_url, headers=headers, data=update_data, method='PATCH')
        
        with urllib.request.urlopen(update_req, context=context) as ur:
            updated_cust = json.loads(ur.read())[0]
            new_name = updated_cust['name']
            new_updated_at = updated_cust['updated_at']
            print(f"Updated Customer: {new_name}, New Updated At: {new_updated_at}")
            
            if old_updated_at != new_updated_at:
                print("TRIGGER VERIFIED: updated_at changed successfully!")
            else:
                print("TRIGGER FAILED: updated_at did not change!")
                
        # Revert changes
        revert_data = json.dumps({'name': old_name}).encode('utf-8')
        revert_req = urllib.request.Request(update_url, headers=headers, data=revert_data, method='PATCH')
        with urllib.request.urlopen(revert_req, context=context) as rr:
            print("Reverted customer name to original.")
except Exception as e:
    print("FAILED:", e)
