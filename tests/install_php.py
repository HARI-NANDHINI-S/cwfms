import os
import sys
import urllib.request
import re
import zipfile
import shutil

INSTALL_DIR = os.path.expanduser(r"~\php")
print(f"Target PHP installation directory: {INSTALL_DIR}")

os.makedirs(INSTALL_DIR, exist_ok=True)

RELEASES_URL = "https://windows.php.net/downloads/releases/"
print(f"Fetching release listing from {RELEASES_URL}...")

headers = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)"}
req = urllib.request.Request(RELEASES_URL, headers=headers)

try:
    with urllib.request.urlopen(req, timeout=30) as resp:
        html = resp.read().decode("utf-8", errors="ignore")
except Exception as e:
    print(f"Failed to fetch releases list: {e}")
    sys.exit(1)

# Find php-8.2.*-Win32-vs16-x64.zip or php-8.3.*-Win32-vs16-x64.zip
# Thread-safe x64 build
matches = re.findall(r'href=["\']?(php-8\.[23]\.[0-9]+-Win32-vs16-x64\.zip)["\']?', html, re.I)
if not matches:
    # Try case-insensitive search
    matches = re.findall(r'(php-8\.[23]\.[0-9]+-Win32-vs16-x64\.zip)', html, re.I)

if not matches:
    print("Could not find PHP 8.2/8.3 x64 zip in releases. Looking for archives...")
    # Try archives URL
    ARCHIVES_URL = "https://windows.php.net/downloads/releases/archives/"
    req_arch = urllib.request.Request(ARCHIVES_URL, headers=headers)
    with urllib.request.urlopen(req_arch, timeout=30) as resp:
        html_arch = resp.read().decode("utf-8", errors="ignore")
    matches = re.findall(r'(php-8\.[23]\.[0-9]+-Win32-vs16-x64\.zip)', html_arch, re.I)
    if matches:
        RELEASES_URL = ARCHIVES_URL

if not matches:
    print("No matching PHP 8.2 or 8.3 x64 release found.")
    sys.exit(1)

# Pick the latest matching version
filename = sorted(list(set(matches)))[-1]
download_url = RELEASES_URL.rstrip('/') + '/' + filename
print(f"Selected PHP release: {filename}")
print(f"Downloading from: {download_url}")

zip_path = os.path.join(INSTALL_DIR, "php.zip")

def report_hook(block_num, block_size, total_size):
    downloaded = block_num * block_size
    if total_size > 0:
        percent = min(100, downloaded * 100 // total_size)
        if block_num % 500 == 0 or percent == 100:
            print(f"  Downloading: {downloaded // (1024*1024)}MB / {total_size // (1024*1024)}MB ({percent}%)")

urllib.request.urlretrieve(download_url, zip_path, reporthook=report_hook)
print("Download complete. Extracting archive...")

with zipfile.ZipFile(zip_path, 'r') as zip_ref:
    zip_ref.extractall(INSTALL_DIR)

print(f"Extracted to {INSTALL_DIR}")
if os.path.exists(zip_path):
    os.remove(zip_path)

# Configure php.ini
php_ini_dev = os.path.join(INSTALL_DIR, "php.ini-development")
php_ini = os.path.join(INSTALL_DIR, "php.ini")

if os.path.exists(php_ini_dev):
    with open(php_ini_dev, "r", encoding="utf-8") as f:
        ini_content = f.read()

    # Enable extension_dir = "ext"
    ini_content = re.sub(r';\s*extension_dir\s*=\s*"ext"', 'extension_dir = "ext"', ini_content)
    
    # Extensions to enable
    required_exts = ["curl", "fileinfo", "mbstring", "openssl", "pdo_sqlite"]
    for ext in required_exts:
        # Replace ;extension=ext with extension=ext
        ini_content = re.sub(rf';\s*extension\s*=\s*{ext}\b', f'extension={ext}', ini_content)

    # Error reporting and memory
    ini_content += "\nmemory_limit = 256M\n"
    ini_content += "date.timezone = Asia/Kolkata\n"

    with open(php_ini, "w", encoding="utf-8") as f:
        f.write(ini_content)
    print("Created and configured php.ini successfully.")

php_exe = os.path.join(INSTALL_DIR, "php.exe")
print(f"PHP Executable verified at: {php_exe}")
print(f"Exists on disk: {os.path.exists(php_exe)}")
