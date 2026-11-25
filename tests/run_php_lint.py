import os
import glob
import subprocess
import sys

PHP_EXE = os.path.abspath("bin/php/php.exe")

print("=" * 70)
print("  PHASE 2: REAL PHP SYNTAX VALIDATION (php -l)")
print(f"  PHP Runtime: {PHP_EXE}")
print("=" * 70 + "\n")

php_files = sorted(glob.glob("**/*.php", recursive=True))

passed = []
failed = []

for pf in php_files:
    if pf.startswith("bin" + os.sep) or ".git" in pf or ".cache" in pf:
        continue
    
    cmd = [PHP_EXE, "-l", pf]
    res = subprocess.run(cmd, capture_output=True, text=True)
    
    if res.returncode == 0:
        passed.append(pf)
        print(f"  [\033[32mPASS\033[0m] {pf}: No syntax errors detected")
    else:
        err_msg = res.stderr.strip() or res.stdout.strip()
        failed.append((pf, err_msg))
        print(f"  [\033[31mFAIL\033[0m] {pf}: {err_msg}")

print("\n" + "=" * 70)
print(f"  LINT RESULTS: {len(passed)} PASSED | {len(failed)} FAILED (Total: {len(passed) + len(failed)})")
print("=" * 70)

if failed:
    sys.exit(1)
