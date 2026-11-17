<?php
$checks = [
    ['file_exists', 'api/leads.php', 'Lead API endpoint missing'],
    ['file_exists', 'pages/leads.php', 'Lead management page missing'],
    ['file_exists', 'supabase_setup.sql', 'Database setup file missing'],
];

$failed = false;
foreach ($checks as [$type, $path, $message]) {
    if ($type === 'file_exists' && !file_exists(__DIR__ . '/../' . $path)) {
        fwrite(STDERR, $message . PHP_EOL);
        $failed = true;
    }
}

if ($failed) {
    exit(1);
}

echo "Lead module files present." . PHP_EOL;
exit(0);
