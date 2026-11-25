import os
import re

print("=== CHECKING SCRATCH FILES FOR EXPOSED CREDENTIALS ===")
for root, dirs, files in os.walk("scratch"):
    for file in files:
        if file.endswith((".py", ".php", ".txt")):
            path = os.path.join(root, file)
            with open(path, "r", encoding="utf-8", errors="ignore") as f:
                content = f.read()
                # Check for raw supabase keys or passwords
                if "eyJhbGci" in content:
                    print(f"[ALERT] Possible JWT in {path}")
                if "sb_secret_" in content:
                    print(f"[ALERT] Supabase service key in {path}")
                if "sb_anon_" in content:
                    print(f"[ALERT] Supabase anon key in {path}")

print("Scratch credential scan completed.")
