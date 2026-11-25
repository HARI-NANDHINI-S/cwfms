import os
import sys
import subprocess
import winreg

PHP_DIR = os.path.expanduser(r"~\php")
PHP_EXE = os.path.join(PHP_DIR, "php.exe")

print("=== VERIFYING PHP EXECUTABLE ===")
print("PHP Directory:", PHP_DIR)
print("PHP Executable:", PHP_EXE)
print("Exists:", os.path.exists(PHP_EXE))

# Set User PATH in Windows Registry
try:
    with winreg.OpenKey(winreg.HKEY_CURRENT_USER, r"Environment", 0, winreg.KEY_READ | winreg.KEY_WRITE) as key:
        try:
            current_path, _ = winreg.QueryValueEx(key, "Path")
        except FileNotFoundError:
            current_path = ""
        
        if PHP_DIR.lower() not in current_path.lower():
            new_path = f"{PHP_DIR};{current_path}"
            winreg.SetValueEx(key, "Path", 0, winreg.REG_EXPAND_SZ, new_path)
            print("Successfully added PHP to User PATH in Windows Registry!")
        else:
            print("PHP is already present in User PATH.")
except Exception as e:
    print("Notice on setting registry PATH:", e)

# Add to current process PATH
os.environ["PATH"] = f"{PHP_DIR};" + os.environ.get("PATH", "")

print("\n=== PHP VERSION (php -v) ===")
res_v = subprocess.run([PHP_EXE, "-v"], capture_output=True, text=True)
print(res_v.stdout)

print("=== PHP MODULES (php -m) ===")
res_m = subprocess.run([PHP_EXE, "-m"], capture_output=True, text=True)
print(res_m.stdout)

print("=== PHP INI (php --ini) ===")
res_ini = subprocess.run([PHP_EXE, "--ini"], capture_output=True, text=True)
print(res_ini.stdout)
