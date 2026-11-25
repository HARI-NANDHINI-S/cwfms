import subprocess
import sys

php_exe = r".\bin\php\php.exe"

def run_test(name, args):
    cmd = [php_exe, "tools/provision_user_password.php"] + args
    res = subprocess.run(cmd, capture_output=True, text=True)
    print(f"=== {name} ===")
    print(f"Command:   {' '.join(cmd)}")
    print(f"Exit code: {res.returncode}")
    print(f"Stdout:\n{res.stdout.strip()}")
    if res.stderr.strip():
        print(f"Stderr:\n{res.stderr.strip()}")
    print("-" * 50)
    return res.returncode

# Test 1: Help flag
rc1 = run_test("TEST 1: --help", ["--help"])
assert rc1 == 0, f"Expected 0, got {rc1}"

# Test 2: Missing argument
rc2 = run_test("TEST 2: No argument", [])
assert rc2 == 1, f"Expected 1, got {rc2}"

# Test 3: Invalid email
rc3 = run_test("TEST 3: Invalid email format", ["invalid-email"])
assert rc3 == 1, f"Expected 1, got {rc3}"

print("ALL INITIAL TOOL TESTS PASSED!")
