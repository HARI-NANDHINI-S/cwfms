<?php
// ============================================================
// tools/provision_user_password.php - Secure CLI Password Provisioning
// Ira Sportswear / Studio 3 Interactive - CWFMS
// ============================================================
// Description:
//   Securely provisions or resets an individual user's password hash
//   in Supabase using modern PHP password_hash() (Bcrypt).
//
// Security Constraints:
//   - Passwords are NEVER accepted as CLI arguments ($argv).
//   - Supports interactive masked input on Windows or automated secure bootstrap (--bootstrap).
//   - Never logs, echoes, or writes plaintext passwords or hashes to disk.
//   - Verifies target user exists and is active (is_active = true).
//   - Checks that user_roles.password_hash column exists before attempting update.
//   - Updates ONLY the specified user record.
// ============================================================

if (php_sapi_name() !== 'cli') {
    die("This tool can only be executed via the command line interface.\n");
}

// Help flag handling (exit 0)
$firstArg = strtolower(trim($argv[1] ?? ''));
if (in_array($firstArg, ['--help', '-h', 'help', '/?'], true)) {
    echo "======================================================================\n";
    echo "  CWFMS SECURE PASSWORD PROVISIONING TOOL\n";
    echo "======================================================================\n";
    echo "Usage:\n";
    echo "  php tools/provision_user_password.php <user_email> [--bootstrap]\n\n";
    echo "Options:\n";
    echo "  <user_email>   Target email of the active user to provision.\n";
    echo "  --bootstrap    Automatically generates a cryptographically strong\n";
    echo "                 random password (random_bytes), hashes it, and\n";
    echo "                 displays the generated credential exactly once.\n\n";
    echo "Description:\n";
    echo "  In standard mode, prompts interactively for a secure password (hidden input).\n";
    echo "  Generates a Bcrypt hash using PHP password_hash(), and updates\n";
    echo "  only the targeted active user record in Supabase.\n\n";
    echo "Security Notes:\n";
    echo "  - Passwords are NEVER accepted as command-line arguments to prevent\n";
    echo "    exposure in process tables, shell history, and logs.\n";
    echo "  - Requires 'migration_v5_auth.sql' to be applied in Supabase first.\n";
    echo "======================================================================\n";
    exit(0);
}

if ($argc < 2) {
    echo "[ERROR] Missing target user email address.\n";
    echo "Usage: php tools/provision_user_password.php <user_email> [--bootstrap]\n";
    echo "Run with --help for detailed documentation.\n";
    exit(1);
}

$email = strtolower(trim($argv[1]));
$isBootstrap = in_array('--bootstrap', array_map('strtolower', $argv), true);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "[ERROR] Invalid email address format: '{$email}'\n";
    exit(1);
}

require_once __DIR__ . '/../includes/config.php';

if (!isSupabaseConfigured()) {
    echo "[ERROR] Supabase database configuration missing in .env.\n";
    exit(1);
}

$db = new Supabase(true);

// 1. Check if user_roles has the password_hash column
if (!dbHasColumn('user_roles', 'password_hash')) {
    echo "\n[BLOCKED] Column Safety Guard Active:\n";
    echo "          The 'password_hash' column does not exist in table 'user_roles'.\n";
    echo "          To preserve database integrity, no updates can be made until the\n";
    echo "          non-destructive migration (migration_v5_auth.sql) is executed in Supabase:\n\n";
    echo "          ALTER TABLE user_roles ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255);\n\n";
    exit(1);
}

// 2. Query target user record
$res = $db->select('user_roles', ['user_email' => 'eq.' . $email]);
if (!$db->isSuccess($res) || empty($res['data'])) {
    echo "[ERROR] User not found in user_roles with email: {$email}\n";
    exit(1);
}

$user = $res['data'][0];
$userId = $user['id'];
$userName = $user['user_name'] ?? 'User';
$role = $user['role'] ?? 'staff';
$isActive = !empty($user['is_active']);

// 3. Verify user account is active
if (!$isActive) {
    echo "[ERROR] Account '{$email}' is currently marked INACTIVE (is_active = false).\n";
    echo "        Passwords cannot be provisioned for inactive accounts.\n";
    exit(1);
}

// 4. Secure Password Acquisition
function promptSecurePassword(string $prompt): string {
    echo $prompt;

    // Check if input is piped or redirected (e.g. automated test / CI)
    if (function_exists('stream_isatty') && !stream_isatty(STDIN)) {
        $line = fgets(STDIN);
        echo "\n";
        return preg_replace('/[^\x20-\x7E]/', '', (string)$line);
    }

    if (PHP_OS_FAMILY === 'Windows') {
        // Use PowerShell SecureString to mask keystrokes and safely decrypt in memory
        $psCmd = 'powershell.exe -NoProfile -Command "$sec = Read-Host -AsSecureString; [System.Runtime.InteropServices.Marshal]::PtrToStringAuto([System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($sec))"';
        $out = shell_exec($psCmd);
        return preg_replace('/[^\x20-\x7E]/', '', (string)$out);
    }

    // Unix / macOS fallback
    $out = shell_exec('/usr/bin/env bash -c "read -s -p \"\" p && echo \$p"');
    echo "\n";
    return preg_replace('/[^\x20-\x7E]/', '', (string)$out);
}

echo "Target Account: {$userName} ({$email})\n";
echo "Role:           {$role}\n";
echo "Account Status: Active\n\n";

$generatedPlaintext = null;

if ($isBootstrap) {
    // Generate a cryptographically strong, 24-character random password
    $bytes = random_bytes(18);
    $generatedPlaintext = rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    $passwordToHash = $generatedPlaintext;
} else {
    $pass1 = promptSecurePassword("Enter new password (input hidden): ");
    if (strlen($pass1) < 8) {
        echo "[ERROR] Password must be at least 8 characters long.\n";
        exit(1);
    }

    $pass2 = promptSecurePassword("Confirm new password (input hidden): ");
    if ($pass1 !== $pass2) {
        echo "[ERROR] Passwords do not match. Aborting.\n";
        exit(1);
    }
    $passwordToHash = $pass1;
    unset($pass1);
    unset($pass2);
}

// 5. Generate Bcrypt Hash
$hash = password_hash($passwordToHash, PASSWORD_DEFAULT);

// Verify hash integrity and format
if (!str_starts_with($hash, '$2y$') && !str_starts_with($hash, '$2b$') && !str_starts_with($hash, '$2a$')) {
    echo "[ERROR] Generated password hash failed format verification.\n";
    exit(1);
}

// 6. Update targeted database record in Supabase
$updateRes = $db->update('user_roles', [
    'password_hash' => $hash,
    'updated_at'    => date('Y-m-d H:i:s'),
], ['id' => 'eq.' . $userId]);

unset($hash); // Clear hash from memory

if ($db->isSuccess($updateRes)) {
    if ($isBootstrap && $generatedPlaintext !== null) {
        echo "======================================================================\n";
        echo "  ADMINISTRATOR CREDENTIAL BOOTSTRAP SUCCESSFUL\n";
        echo "======================================================================\n";
        echo "  Target Account:    {$userName} ({$email})\n";
        echo "  Role:              {$role}\n";
        echo "  One-Time Password: {$generatedPlaintext}\n\n";
        echo "  SECURITY NOTICE:\n";
        echo "  - Save this password securely immediately.\n";
        echo "  - It is displayed exactly once and is never stored in plaintext or logged.\n";
        echo "======================================================================\n";
        unset($generatedPlaintext);
    } else {
        echo "\n[SUCCESS] Password successfully provisioned for {$userName} ({$email}).\n";
        echo "          Account can now authenticate securely via login.php.\n";
        echo "          Password was never logged, echoed, or written to temporary files.\n";
    }
    exit(0);
} else {
    echo "\n[ERROR] Failed to update password in database: " . ($updateRes['message'] ?? 'Database error') . "\n";
    exit(1);
}
