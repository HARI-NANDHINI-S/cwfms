<?php
require_once __DIR__ . '/../includes/config.php';

$db = new Supabase(true);

echo "=== 1. POSTGREST SCHEMA INTROSPECTION ===\n";

$tables = ['customers', 'leads', 'user_roles', 'activity_log', 'activity_logs', 'import_logs'];

foreach ($tables as $t) {
    // Probe table with limit 1
    $res = $db->request('GET', $t, null, ['limit' => 1]);
    if ($db->isSuccess($res)) {
        $countRes = $db->count($t);
        $total = $countRes['count'] ?? 0;
        $cols = !empty($res['data'][0]) ? array_keys($res['data'][0]) : [];
        echo "[EXISTS] Table: $t (Rows: $total)\n";
        if (!empty($cols)) {
            echo "   Columns: " . implode(', ', $cols) . "\n";
        } else {
            echo "   Columns: (Table is empty, probing via OpenAPI definitions)\n";
        }
    } else {
        echo "[NOT FOUND / ERROR] Table: $t (Status: " . ($res['status'] ?? 'unknown') . ", Msg: " . ($res['message'] ?? $res['error'] ?? '') . ")\n";
    }
}

echo "\n=== 2. OPENAPI SPEC INSPECTION ===\n";
$openApi = $db->request('GET', '', null, [], ['endpoint_raw' => true]);
// Let's do a raw curl to /rest/v1/
$ch = curl_init(SUPABASE_URL . '/rest/v1/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . SUPABASE_SERVICE_KEY,
    'Accept: application/openapi+json, application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$specRaw = curl_exec($ch);
$specCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($specCode === 200 && $specRaw) {
    $spec = json_decode($specRaw, true);
    if (!empty($spec['definitions'])) {
        echo "Found " . count($spec['definitions']) . " definitions in OpenAPI schema:\n";
        foreach (['customers', 'leads', 'user_roles', 'activity_log', 'import_logs'] as $tbl) {
            if (isset($spec['definitions'][$tbl])) {
                $def = $spec['definitions'][$tbl];
                echo "\n--- Schema for $tbl ---\n";
                $properties = $def['properties'] ?? [];
                $required = $def['required'] ?? [];
                foreach ($properties as $prop => $details) {
                    $type = $details['type'] ?? ($details['format'] ?? 'string');
                    $reqStr = in_array($prop, $required, true) ? 'REQUIRED' : 'nullable';
                    $default = isset($details['default']) ? " default=" . json_encode($details['default']) : "";
                    $desc = isset($details['description']) ? " (" . substr($details['description'], 0, 40) . ")" : "";
                    echo "   - $prop: $type [$reqStr]$default$desc\n";
                }
            } else {
                echo "\n--- Schema for $tbl: NOT DEFINED in OpenAPI definitions ---\n";
            }
        }
    }
} else {
    echo "OpenAPI fetch status: $specCode\n";
}
