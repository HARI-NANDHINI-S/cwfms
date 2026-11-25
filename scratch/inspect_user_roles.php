<?php
require_once __DIR__ . '/../includes/config.php';

$db = new Supabase(true);

echo "=== USER ROLES STRUCTURAL INSPECTION ===\n";
$users = $db->request('GET', 'user_roles', null, ['limit' => 10]);
if ($db->isSuccess($users)) {
    echo "Found " . count($users['data']) . " user records:\n";
    foreach ($users['data'] as $idx => $u) {
        $cols = array_keys($u);
        $role = $u['role'] ?? 'N/A';
        $active = isset($u['is_active']) ? ($u['is_active'] ? 'true' : 'false') : 'N/A';
        $hasHash = isset($u['password_hash']) ? 'YES' : 'NO';
        // Mask email: a***@domain
        $email = $u['user_email'] ?? '';
        $maskedEmail = preg_replace('/(?<=.).(?=.*@)/', '*', $email);
        echo "User #$idx: Role='$role', Active=$active, HasPasswordHash=$hasHash, EmailMasked='$maskedEmail'\n";
    }
} else {
    echo "Failed to query user_roles: " . ($users['message'] ?? 'error') . "\n";
}
