<?php
require_once __DIR__.'/includes/config.php';

// Initialize Supabase client with service key for admin operations
$supabase = new Supabase(true);

// Prepare test user data
$user = [
    'id' => uniqid('', true),
    'email' => 'test.user@example.com',
    // Store a bcrypt hash; Supabase expects hashed password in 'encrypted_password'
    'encrypted_password' => password_hash('TestPassword123!', PASSWORD_BCRYPT),
    'email_confirmed_at' => date('Y-m-d\TH:i:sZ'),
    'created_at' => date('Y-m-d\TH:i:sZ')
];

$result = $supabase->insert('auth.users', $user);

header('Content-Type: application/json');
echo json_encode($result);
?>
