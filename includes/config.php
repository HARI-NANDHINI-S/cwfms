<?php
// ============================================================
// config.php - Supabase Configuration
// ============================================================

// Helper to parse .env file
if (!function_exists('loadEnvFile')) {
    function loadEnvFile(): array {
        static $cached = null;
        if ($cached !== null) return $cached;
        $cached = [];
        $envPath = __DIR__ . '/../.env';
        if (file_exists($envPath)) {
            $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines !== false) {
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#') || strpos($line, '=') === false) continue;
                    [$key, $val] = explode('=', $line, 2);
                    $cached[trim($key)] = trim($val, " \t\n\r\0\x0B\"'");
                }
            }
        }
        return $cached;
    }
}

$localEnv = loadEnvFile();
$getEnvVal = function(string $key, string $default = '') use ($localEnv): string {
    $val = getenv($key);
    if ($val !== false && $val !== '') return $val;
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
    $fromEnv = $localEnv[$key] ?? '';
    if ($fromEnv !== '' && !preg_match('/^<.*>$/', $fromEnv)) {
        return $fromEnv;
    }
    return $default;
};

if (!defined('SUPABASE_URL')) {
    define('SUPABASE_URL', $getEnvVal('SUPABASE_URL', 'https://fjahjldhflcmnargcood.supabase.co'));
}
if (!defined('SUPABASE_ANON_KEY')) {
    define('SUPABASE_ANON_KEY', $getEnvVal('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZqYWhqbGRoZmxjbW5hcmdjb29kIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzQ3MDcyMDUsImV4cCI6MjA5MDI4MzIwNX0.TlculnZmN9U6SzLr3ifEIcUGQAujfbrI37XdkNRsUP0'));
}
if (!defined('SUPABASE_SERVICE_KEY')) {
    define('SUPABASE_SERVICE_KEY', $getEnvVal('SUPABASE_SERVICE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZqYWhqbGRoZmxjbW5hcmdjb29kIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc3NDcwNzIwNSwiZXhwIjoyMDkwMjgzMjA1fQ.mcZo3hhmvIEjVd7bippKoRZ6pFfCse3KToKndXL_0tA'));
}
define('APP_NAME', 'Ira Sportswear Lead Management');
define('APP_VERSION', '1.0.0');

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        @ini_set('session.cookie_httponly', 1);
        @ini_set('session.use_strict_mode', 1);
        @ini_set('session.cookie_samesite', 'Lax');
    }
    @session_start();
}

// Error reporting (controlled by APP_ENV)
$appEnv = getenv('APP_ENV') ?: (defined('APP_ENV') ? APP_ENV : 'development');
if ($appEnv === 'production') {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    @ini_set('display_errors', 0);
    @ini_set('log_errors', 1);
} else {
    error_reporting(E_ALL);
    @ini_set('display_errors', 1);
}

// Timezone
date_default_timezone_set('Asia/Kolkata');

// ============================================================
// Supabase HTTP Helper Class
// ============================================================
class Supabase {
    private $url;
    private $key;
    private $lastResponseHeaders = [];

    public function __construct($useServiceKey = false) {
        $this->url = SUPABASE_URL;
        $this->key = $useServiceKey ? SUPABASE_SERVICE_KEY : SUPABASE_ANON_KEY;
    }

    private function sanitizeFilters(array $params): array {
        $clean = [];
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $clean[$key] = $value;
                continue;
            }

            $text = trim((string)$value);
            if ($text === '') {
                continue;
            }

            if (preg_match('/^eq\.(.*)$/i', $text, $m) && trim($m[1]) === '') {
                continue;
            }

            if (preg_match('/^in\.\((.*)\)$/i', $text, $m) && trim($m[1]) === '') {
                continue;
            }

            $clean[$key] = $value;
        }
        return $clean;
    }

    public function request($method, $endpoint, $data = null, $params = [], $options = []) {
        $url = $this->url . '/rest/v1/' . $endpoint;
        $params = $this->sanitizeFilters($params);

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $prefer = $options['prefer'] ?? 'return=representation';
        $headers = [
            'Content-Type: application/json',
            'apikey: ' . $this->key,
            'Authorization: Bearer ' . $this->key,
            'Prefer: ' . $prefer,
        ];

        if (!empty($options['range'])) {
            $headers[] = 'Range: ' . $options['range'];
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);

        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            default:
                break;
        }

        $rawResponse = curl_exec($ch);
        $curlError = curl_error($ch);

        if ($rawResponse === false && stripos($curlError, 'ssl') !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $rawResponse = curl_exec($ch);
            $curlError = curl_error($ch);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error = $curlError;
        curl_close($ch);

        if ($error) {
            return [
                'error'   => $error,
                'message' => 'Database connection failed: ' . $error,
                'status'  => 0,
            ];
        }

        $rawHeaders = substr($rawResponse, 0, $headerSize);
        $body = substr($rawResponse, $headerSize);
        $this->lastResponseHeaders = $this->parseHeaders($rawHeaders);

        $decoded = json_decode($body, true);
        $result = [
            'data'    => $decoded,
            'status'  => $httpCode,
            'headers' => $this->lastResponseHeaders,
        ];

        if ($httpCode < 200 || $httpCode >= 300) {
            $result['message'] = parseDbError($decoded, $httpCode);
            $result['error'] = $result['message'];
        }

        return $result;
    }

    private function parseHeaders($raw) {
        $headers = [];
        foreach (explode("\r\n", $raw) as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($key))] = trim($value);
            }
        }
        return $headers;
    }

    public function isSuccess($result) {
        return isset($result['status']) && $result['status'] >= 200 && $result['status'] < 300;
    }

    public function select($table, $params = [], $options = []) {
        return $this->request('GET', $table, null, $params, $options);
    }

    public function count($table, $params = []) {
        $params['select'] = $params['select'] ?? 'id';
        $result = $this->select($table, $params, [
            'prefer' => 'count=exact',
            'range'  => '0-0',
        ]);

        $count = 0;
        if (!empty($result['headers']['content-range'])) {
            $parts = explode('/', $result['headers']['content-range']);
            $count = (int)($parts[1] ?? 0);
        } elseif ($this->isSuccess($result) && is_array($result['data'])) {
            $count = count($result['data']);
        }

        $result['count'] = $count;
        return $result;
    }

    public function insert($table, $data) {
        return $this->request('POST', $table, $data);
    }

    public function update($table, $data, $params = []) {
        return $this->request('PATCH', $table, $data, $params);
    }

    public function delete($table, $params = []) {
        return $this->request('DELETE', $table, null, $params);
    }

    public function logActivity($customerId, $action, $changedFields = null, $performedBy = 'system', $notes = '') {
        $description = $notes ?: $action;
        if ($changedFields) {
            $description .= ' — ' . json_encode($changedFields);
        }
        if ($performedBy && $performedBy !== 'system') {
            $description = $performedBy . ': ' . $description;
        }

        $logData = [
            'customer_id' => $customerId,
            'action'      => $action,
            'description' => $description,
            'user_id'     => $_SESSION['user']['id'] ?? null,
        ];
        return $this->insert('activity_logs', $logData);
    }

    public static function testConnection() {
        $db = new self(true);
        $started = microtime(true);

        $customers = $db->select('customers', ['select' => 'id', 'limit' => '1']);
        if (!$db->isSuccess($customers)) {
            return [
                'connected' => false,
                'message'   => $customers['message'] ?? $customers['error'] ?? 'Cannot reach customers table',
                'latency_ms'=> null,
            ];
        }

        $users = $db->select('user_roles', ['select' => 'id', 'limit' => '1']);
        if (!$db->isSuccess($users)) {
            return [
                'connected' => false,
                'message'   => $users['message'] ?? $users['error'] ?? 'Cannot reach user_roles table',
                'latency_ms'=> null,
            ];
        }

        $latency = round((microtime(true) - $started) * 1000);

        return [
            'connected'  => true,
            'message'    => 'Database connection healthy',
            'latency_ms' => $latency,
            'project'    => explode('.', parse_url(SUPABASE_URL, PHP_URL_HOST))[0],
        ];
    }
}

// ============================================================
// Helper Functions
// ============================================================
function jsonResponse($success, $message = '', $data = null, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

function sanitize($value) {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

function normalizeUuidValue($value) {
    if ($value === null) {
        return null;
    }

    $text = trim((string)$value);
    if ($text === '') {
        return null;
    }

    if (preg_match('/^eq\.(.*)$/i', $text, $m) && trim($m[1]) === '') {
        return null;
    }

    return $text;
}

function validateEmail($email) {
    return empty($email) || filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validateMobile($mobile) {
    return preg_match('/^[0-9]{7,15}$/', $mobile);
}

function validatePincode($pincode) {
    return preg_match('/^[0-9]+$/', $pincode);
}

/**
 * Role Normalization & Permission System (Ira Sportswear Lead Management)
 * Business Roles:
 * 1. Admin ('admin')
 * 2. Sales Manager ('manager', 'sales_manager')
 * 3. Sales Executive ('staff', 'sales_executive')
 */
function normalizeRole(?string $role): string {
    $role = strtolower(trim((string)$role));
    if ($role === 'admin') {
        return 'admin';
    }
    if (in_array($role, ['manager', 'sales_manager'], true)) {
        return 'sales_manager';
    }
    return 'sales_executive';
}

function getRoleLabel(?string $role): string {
    $norm = normalizeRole($role);
    switch ($norm) {
        case 'admin':
            return 'Admin';
        case 'sales_manager':
            return 'Sales Manager';
        case 'sales_executive':
        default:
            return 'Sales Executive';
    }
}

function isAdmin($user = null): bool {
    $user = $user ?? getCurrentUser();
    return normalizeRole($user['role'] ?? 'staff') === 'admin';
}

function isSalesManager($user = null): bool {
    $user = $user ?? getCurrentUser();
    return normalizeRole($user['role'] ?? 'staff') === 'sales_manager';
}

function isSalesExecutive($user = null): bool {
    $user = $user ?? getCurrentUser();
    return normalizeRole($user['role'] ?? 'staff') === 'sales_executive';
}

function canManageUsers($user = null): bool {
    return isAdmin($user);
}

function canDeleteLead($user = null): bool {
    return isAdmin($user);
}

function canAssignLead($user = null): bool {
    $user = $user ?? getCurrentUser();
    $norm = normalizeRole($user['role'] ?? 'staff');
    return in_array($norm, ['admin', 'sales_manager'], true);
}

function canChangeLeadStatus($lead, $user = null): bool {
    $user = $user ?? getCurrentUser();
    $norm = normalizeRole($user['role'] ?? 'staff');
    if (in_array($norm, ['admin', 'sales_manager'], true)) {
        return true;
    }
    $userId = $user['id'] ?? ($_SESSION['user']['id'] ?? null);
    return !empty($lead['assigned_to']) && $lead['assigned_to'] === $userId;
}

function canEditLeadRecord($lead, $user = null): bool {
    $user = $user ?? getCurrentUser();
    $norm = normalizeRole($user['role'] ?? 'staff');
    if (in_array($norm, ['admin', 'sales_manager'], true)) {
        return true;
    }
    $userId = $user['id'] ?? ($_SESSION['user']['id'] ?? null);
    return !empty($lead['assigned_to']) && $lead['assigned_to'] === $userId;
}

function getCurrentUser() {
    $user = $_SESSION['user'] ?? ['name' => 'Guest', 'role' => 'staff', 'email' => ''];
    $user['role_label'] = getRoleLabel($user['role'] ?? 'staff');
    $user['business_role'] = normalizeRole($user['role'] ?? 'staff');
    return $user;
}

function hasPermission($action) {
    $user = getCurrentUser();
    $role = normalizeRole($user['role'] ?? 'staff');
    $permissions = [
        'admin'           => ['create', 'edit', 'toggle_status', 'view', 'delete', 'manage_users', 'export', 'assign'],
        'sales_manager'   => ['create', 'edit', 'toggle_status', 'view', 'export', 'assign'],
        'sales_executive' => ['create', 'view', 'export'],
    ];
    return in_array($action, $permissions[$role] ?? []);
}

function requireApiAuth() {
    if (!isset($_SESSION['user'])) {
        jsonResponse(false, 'Authentication required. Please log in.', null, 401);
    }
}

function dbHasColumn($table, $column) {
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $db = new Supabase(true);
    $result = $db->select($table, ['select' => $column, 'limit' => '1']);
    $cache[$key] = $db->isSuccess($result);
    return $cache[$key];
}

function getCustomerActiveStatus($db, $customerId, $dbValue = null) {
    if (dbHasColumn('customers', 'is_active') && $dbValue !== null) {
        return (bool)$dbValue;
    }

    $result = $db->select('activity_logs', [
        'customer_id' => 'eq.' . $customerId,
        'action'      => 'in.(activated,deactivated)',
        'order'       => 'created_at.desc',
        'limit'       => '1',
        'select'      => 'action',
    ]);

    if (!$db->isSuccess($result) || empty($result['data'])) {
        return true;
    }

    return $result['data'][0]['action'] === 'activated';
}

function enrichCustomers($db, $customers) {
    foreach ($customers as &$customer) {
        $customer['is_active'] = getCustomerActiveStatus(
            $db,
            $customer['id'],
            $customer['is_active'] ?? null
        );
        if (!isset($customer['profile_score'])) {
            $customer['profile_score'] = dbHasColumn('customers', 'profile_score')
                ? 0
                : calculateProfileScore($customer, $customer['is_active']);
        }
    }
    unset($customer);
    return $customers;
}

function calculateProfileScore($data, $isActive = true) {
    $score = 0;
    if (!empty($data['name']) && strlen(trim($data['name'])) >= 3) $score += 15;
    if (!empty($data['team_academy_name'])) $score += 10;
    if (!empty($data['email'])) $score += 15;
    if (!empty($data['mobile_number']) && strlen(trim($data['mobile_number'])) >= 7) $score += 15;
    if (!empty($data['address_line_1'])) $score += 10;
    if (!empty($data['address_line_2'])) $score += 5;
    if (!empty($data['city'])) $score += 10;
    if (!empty($data['state'])) $score += 5;
    if (!empty($data['pincode'])) $score += 5;
    if (!empty($data['country'])) $score += 5;
    if ($isActive) $score += 5;
    return $score;
}

function parseDbError($decoded, $httpCode = 500) {
    if (is_array($decoded)) {
        if (!empty($decoded['message'])) {
            return $decoded['message'];
        }
        if (!empty($decoded['error'])) {
            return $decoded['error'];
        }
        if (!empty($decoded['hint'])) {
            return $decoded['hint'];
        }
        if (isset($decoded['code'])) {
            return 'Database error (' . $decoded['code'] . '): ' . ($decoded['details'] ?? 'Unknown error');
        }
    }

    $messages = [
        401 => 'Database authentication failed. Check API keys.',
        404 => 'Database table or resource not found.',
        409 => 'A record with this data already exists.',
        422 => 'Invalid data sent to database.',
        500 => 'Database server error.',
        503 => 'Database service unavailable.',
    ];

    return $messages[$httpCode] ?? 'Database request failed (HTTP ' . $httpCode . ')';
}

// ============================================================
// WhatsApp Cloud API Configuration Helpers
// ============================================================
function getWhatsAppConfig(): array {
    static $config = null;
    if ($config !== null) return $config;

    // Load .env if present and environment variables not yet set
    $envPath = __DIR__ . '/../.env';
    $envVars = [];
    if (file_exists($envPath)) {
        $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines !== false) {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) continue;
                if (strpos($line, '=') !== false) {
                    [$key, $val] = explode('=', $line, 2);
                    $envVars[trim($key)] = trim($val, " \t\n\r\0\x0B\"'");
                }
            }
        }
    }

    $getVal = function(string $key, string $default = '') use ($envVars): string {
        $val = getenv($key);
        if ($val !== false && $val !== '') return $val;
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
        return $envVars[$key] ?? $default;
    };

    $config = [
        'token'                => $getVal('WHATSAPP_TOKEN', ''),
        'phone_number_id'      => $getVal('WHATSAPP_PHONE_NUMBER_ID', ''),
        'business_account_id'  => $getVal('WHATSAPP_BUSINESS_ACCOUNT_ID', ''),
        'webhook_verify_token' => $getVal('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'ira_sportswear_wh_verify_secret_token_2026'),
        'app_secret'           => $getVal('WHATSAPP_APP_SECRET', ''),
        'api_version'          => $getVal('WHATSAPP_API_VERSION', 'v21.0'),
        'automations_enabled'  => ($getVal('WHATSAPP_AUTOMATIONS_ENABLED', '1') === '1'),
        'automation_stages'    => array_map('trim', explode(',', $getVal('WHATSAPP_AUTOMATION_STAGES', 'Received,Awaiting Clarification,Approved,Moved to Production'))),
    ];

    return $config;
}

function isWhatsAppConfigured(): bool {
    $cfg = getWhatsAppConfig();
    return !empty($cfg['token']) && 
           !empty($cfg['phone_number_id']) && 
           !str_contains($cfg['token'], 'your_permanent_or_system_user_token_here');
}
