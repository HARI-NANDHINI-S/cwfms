import os, sys, shutil

print("=== TOOL AVAILABILITY ON PATH ===")
tools = ['php', 'composer', 'docker', 'git', 'httpd', 'apache2', 'nginx', 'node', 'python']
for t in tools:
    p = shutil.which(t)
    print(f"{t:10}: {p}")

print("\n=== SCANNING COMMON DIRECTORIES ===")
common_paths = [
    r"C:\xampp",
    r"C:\wamp64",
    r"C:\laragon",
    r"C:\tools",
    r"C:\Program Files\PHP",
    r"C:\Program Files (x86)\PHP",
    r"C:\Users\HARI NANDHINI\AppData\Local\Programs",
    r"C:\scoop",
    r"C:\ProgramData\chocolatey"
]

found_php = []
for cp in common_paths:
    if os.path.exists(cp):
        print(f"Directory exists: {cp}")
        try:
            for root, dirs, files in os.walk(cp):
                if "php.exe" in files:
                    full_p = os.path.join(root, "php.exe")
                    found_php.append(full_p)
                    print(f"  -> Found php.exe: {full_p}")
        except Exception as e:
            print(f"  -> Error scanning {cp}: {e}")
    else:
        print(f"Not found: {cp}")

print("\n=== SUMMARY OF PHP EXECUTABLES FOUND ===")
if found_php:
    for p in found_php:
        print(f"PHP Executable: {p}")
else:
    print("NO php.exe found in PATH or standard installation directories.")
