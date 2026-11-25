<?php
function promptPassword(string $prompt = 'Enter password: '): string {
    echo $prompt;
    
    // Check if input is redirected / piped (non-interactive or automated test)
    if (function_exists('stream_isatty') && !stream_isatty(STDIN)) {
        $line = fgets(STDIN);
        echo "\n";
        // Strip PowerShell pipe encoding artifacts (BOM, null bytes, CR, LF)
        return preg_replace('/[^\x20-\x7E]/', '', (string)$line);
    }

    if (PHP_OS_FAMILY === 'Windows') {
        // Use PowerShell command to securely read without echo
        $cmd = 'powershell.exe -NoProfile -Command "$sec = Read-Host -AsSecureString; [System.Runtime.InteropServices.Marshal]::PtrToStringAuto([System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($sec))"';
        $p = shell_exec($cmd);
        return trim((string)$p);
    }

    // Unix / macOS fallback
    $p = shell_exec('/usr/bin/env bash -c "read -s -p \"\" p && echo \$p"');
    echo "\n";
    return trim((string)$p);
}

// Test with piped input
$pass = promptPassword('Testing prompt: ');
echo "Received password of length: " . strlen($pass) . "\n";
echo "Hash test: " . (password_verify($pass, password_hash($pass, PASSWORD_DEFAULT)) ? "VERIFIED" : "FAILED") . "\n";
