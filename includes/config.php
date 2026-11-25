<?php
// ============================================================
// config.php - Supabase Configuration & Core Security Engine
// Ira Sportswear / Studio 3 Interactive - CWFMS
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
                    // Strip UTF-8 Byte Order Mark (BOM) if saved via Windows Notepad
                    $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);
                    $line = trim($line);
                    // Strip optional leading 'export '
                    if (str_starts_with($line, 'export ')) {
                        $line = trim(substr($line, 7));
                    }
                    if ($line === '' || str_starts_with($line, '#') || strpos($line, '=') === false) continue;
                    [$key, $val] = explode('=', $line, 2);
                    $key = trim($key);
                    $val = trim($val);
                    // Strip inline comment if value is unquoted
                    if (!empty($val) && !str_starts_with($val, '"') && !str_starts_with($val, "'")) {
                        if (str_contains($val, '#')) {
                            $parts = explode('#', $val, 2);
                            $val = trim($parts[0]);
                        }
                    }
                    $val = trim($val, " \t\n\r\0\x0B\"'");
                    $cached[$key] = $val;
                    if (!isset($_ENV[$key])) {
                        $_ENV[$key] = $val;
                    }
                    putenv("{$key}={$val}");
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

// Database credentials loaded exclusively from environment or .env
// No misleading fallback project URL: if not configured, left empty so app reports Configuration Missing.
if (!defined('SUPABASE_URL')) {
    $rawUrl = $getEnvVal('SUPABASE_URL', '');
    $cleanUrl = preg_replace('#/rest/v1/?$#i', '', rtrim($rawUrl, '/'));
    define('SUPABASE_URL', $cleanUrl);
}
if (!defined('SUPABASE_ANON_KEY')) {
    define('SUPABASE_ANON_KEY', $getEnvVal('SUPABASE_ANON_KEY', ''));
}
if (!defined('SUPABASE_SERVICE_KEY')) {
    define('SUPABASE_SERVICE_KEY', $getEnvVal('SUPABASE_SERVICE_KEY', ''));
}
define('APP_NAME', 'Ira Sportswear Lead Management');
define('APP_VERSION', '1.0.0');

/**
 * Checks whether Supabase connection parameters are configured.
 */
function isSupabaseConfigured(): bool {
    $url = trim((string)SUPABASE_URL);
    $key = trim((string)SUPABASE_SERVICE_KEY);
    if (empty($url) || empty($key)) {
        return false;
    }
    // Reject placeholder values like <your-project-ref> or https://<your-project-ref>.supabase.co
    if (str_contains($url, '<') || str_contains($url, '>') || str_contains($key, '<') || str_contains($key, '>')) {
        return false;
    }
    return true;
}

// Session configuration & hardening
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        @ini_set('session.cookie_httponly', 1);
        @ini_set('session.use_strict_mode', 1);
        @ini_set('session.cookie_samesite', 'Lax');
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            @ini_set('session.cookie_secure', 1);
        }
    }
    @session_start();
}

// Session expiration handling (2 hours inactivity = 7200s)
define('SESSION_TIMEOUT_SECONDS', 7200);
if (isset($_SESSION['user']) && isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT_SECONDS) {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        @session_destroy();
        if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
            exit;
        } else {
            $prefix = str_contains($_SERVER['REQUEST_URI'] ?? '', '/pages/') ? '../' : '';
            header('Location: ' . $prefix . 'login.php?session=expired');
            exit;
        }
    }
}
$_SESSION['last_activity'] = time();

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

// Default security response headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// ============================================================
// CSRF Protection Engine
// ============================================================
function getCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], (string)$token);
}

function requireCsrfToken(): void {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        // Exempt public webhook and unauthenticated login from CSRF token check
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (str_contains($uri, 'whatsapp_webhook.php')) {
            return;
        }

        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $headerToken = $headers['X-CSRF-Token'] ?? ($headers['x-csrf-token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));

        $inputToken = '';
        if (!empty($headerToken)) {
            $inputToken = $headerToken;
        } elseif (!empty($_POST['csrf_token'])) {
            $inputToken = $_POST['csrf_token'];
        } else {
            $rawBody = @file_get_contents('php://input');
            if ($rawBody) {
                $decoded = @json_decode($rawBody, true);
                if (is_array($decoded) && !empty($decoded['csrf_token'])) {
                    $inputToken = $decoded['csrf_token'];
                }
            }
        }

        if (isset($_SESSION['user']) && !verifyCsrfToken($inputToken)) {
            jsonResponse(false, 'CSRF token validation failed. Please refresh the page.', null, 403);
        }
    }
}

// ============================================================
// Login Rate Limiting Engine
// ============================================================
function checkLoginRateLimit(string $identifier = ''): bool {
    $key = 'rate_' . ($identifier ?: ($_SERVER['REMOTE_ADDR'] ?? 'client'));
    $attempts = $_SESSION[$key . '_attempts'] ?? 0;
    $lastAttempt = $_SESSION[$key . '_time'] ?? 0;

    // Window: 15 minutes (900s). Max failed attempts: 6
    if (time() - $lastAttempt > 900) {
        $_SESSION[$key . '_attempts'] = 0;
        return true;
    }

    return $attempts < 6;
}

function recordFailedLogin(string $identifier = ''): void {
    $key = 'rate_' . ($identifier ?: ($_SERVER['REMOTE_ADDR'] ?? 'client'));
    $_SESSION[$key . '_attempts'] = ($_SESSION[$key . '_attempts'] ?? 0) + 1;
    $_SESSION[$key . '_time'] = time();
}

function clearFailedLogins(string $identifier = ''): void {
    $key = 'rate_' . ($identifier ?: ($_SERVER['REMOTE_ADDR'] ?? 'client'));
    unset($_SESSION[$key . '_attempts'], $_SESSION[$key . '_time']);
}

// ============================================================
// Supabase HTTP Helper Class
// ============================================================
class Supabase {
    private $url;
    private $key;
    private $lastResponseHeaders = [];

    public function __construct($useServiceKey = false) {
        $rawUrl = rtrim((string)SUPABASE_URL, '/');
        $this->url = preg_replace('#/rest/v1/?$#i', '', $rawUrl);
        $this->key = (string)($useServiceKey ? SUPABASE_SERVICE_KEY : SUPABASE_ANON_KEY);
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
        if (!isSupabaseConfigured()) {
            return [
                'error'       => 'configuration_missing',
                'message'     => 'Supabase database configuration missing. Please configure .env with valid credentials.',
                'status'      => 0,
                'status_code' => 'config_missing',
            ];
        }

        $url = $this->url . '/rest/v1/' . $endpoint;
        $params = $this->sanitizeFilters($params);

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $prefer = $options['prefer'] ?? 'return=representation';
        $headers = [
            'Content-Type: application/json',
            'apikey: ' . $this->key,
            'Prefer: ' . $prefer,
        ];

        // Authorization header handling:
        // 1. If explicit auth_token provided (e.g. user session JWT), send it as Bearer token.
        // 2. If the key is a legacy Supabase JWT (starts with 'eyJ' and has 3 parts), send as Bearer token for backward compatibility.
        // 3. For new format Supabase keys (sb_secret_... and sb_publishable_...), do NOT send Authorization: Bearer <key>
        //    because they are not JWTs and sending them as Bearer causes PostgREST to fail with 401 Invalid JWT.
        if (!empty($options['auth_token'])) {
            $headers[] = 'Authorization: Bearer ' . $options['auth_token'];
        } elseif (str_starts_with($this->key, 'eyJ') && count(explode('.', $this->key)) === 3) {
            $headers[] = 'Authorization: Bearer ' . $this->key;
        }

        if (!empty($options['range'])) {
            $headers[] = 'Range: ' . $options['range'];
        }

        $timeout = isset($options['timeout']) ? (int)$options['timeout'] : 30;
        $connectTimeout = isset($options['connect_timeout']) ? (int)$options['connect_timeout'] : 10;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
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
                $headers[trim($key)] = trim($value);
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

        if (isset($result['headers']['content-range'])) {
            $parts = explode('/', $result['headers']['content-range']);
            if (isset($parts[1]) && is_numeric($parts[1])) {
                return [
                    'status' => 200,
                    'count'  => (int)$parts[1],
                ];
            }
        }

        return [
            'status' => $result['status'],
            'count'  => is_array($result['data']) ? count($result['data']) : 0,
        ];
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
        $res = $this->insert('activity_log', $logData);
        if (!$this->isSuccess($res)) {
            $res = $this->insert('activity_logs', $logData);
        }
        return $res;
    }

    public static function testConnection() {
        if (!isSupabaseConfigured()) {
            return [
                'connected'   => false,
                'status_code' => 'config_missing',
                'label'       => 'Configuration Missing',
                'message'     => 'Database configuration missing. Please configure SUPABASE_URL and SUPABASE_SERVICE_KEY in .env',
                'latency_ms'  => null,
                'project'     => null,
                'http_status' => 503,
            ];
        }

        $db = new self(true);
        $started = microtime(true);
        $host = parse_url(SUPABASE_URL, PHP_URL_HOST) ?? '';
        $project = explode('.', $host)[0] ?? 'supabase';

        // 1. Probe customers table with 5s timeout
        $customers = $db->request('GET', 'customers', null, ['select' => 'id', 'limit' => '1'], [
            'timeout' => 5,
            'connect_timeout' => 5,
        ]);
        $latency = round((microtime(true) - $started) * 1000);

        if (!$db->isSuccess($customers)) {
            $status = $customers['status'] ?? 0;
            $err = strtolower($customers['error'] ?? $customers['message'] ?? '');

            if ($status === 0) {
                if (str_contains($err, 'resolve') || str_contains($err, 'dns') || str_contains($err, 'could not resolve host')) {
                    return [
                        'connected'   => false,
                        'status_code' => 'dns_error',
                        'label'       => 'Connection Error',
                        'message'     => 'Cannot resolve Supabase host: check your SUPABASE_URL (' . htmlspecialchars($host) . ')',
                        'latency_ms'  => null,
                        'project'     => $project,
                        'http_status' => 503,
                    ];
                }
                if (str_contains($err, 'timed out') || str_contains($err, 'timeout')) {
                    return [
                        'connected'   => false,
                        'status_code' => 'timeout',
                        'label'       => 'Connection Error',
                        'message'     => 'Connection to Supabase timed out',
                        'latency_ms'  => null,
                        'project'     => $project,
                        'http_status' => 504,
                    ];
                }
                return [
                    'connected'   => false,
                    'status_code' => 'network_error',
                    'label'       => 'Connection Error',
                    'message'     => 'Cannot connect to Supabase database (' . htmlspecialchars($host) . ')',
                    'latency_ms'  => null,
                    'project'     => $project,
                    'http_status' => 503,
                ];
            }

            if ($status === 401 || $status === 403) {
                return [
                    'connected'   => false,
                    'status_code' => 'auth_error',
                    'label'       => 'Auth Error',
                    'message'     => 'Database authentication failed: verify SUPABASE_SERVICE_KEY in .env',
                    'latency_ms'  => $latency,
                    'project'     => $project,
                    'http_status' => 503,
                ];
            }

            if ($status === 404) {
                return [
                    'connected'   => false,
                    'status_code' => 'schema_missing',
                    'label'       => 'Schema Incomplete',
                    'message'     => 'Database connected, but `customers` table not found. Please run migrations.',
                    'latency_ms'  => $latency,
                    'project'     => $project,
                    'http_status' => 503,
                ];
            }

            return [
                'connected'   => false,
                'status_code' => 'db_error',
                'label'       => 'DB Offline',
                'message'     => 'Database query error: ' . ($customers['message'] ?? 'Unable to reach customers table'),
                'latency_ms'  => $latency,
                'project'     => $project,
                'http_status' => 503,
            ];
        }

        // 2. Probe user_roles table
        $users = $db->request('GET', 'user_roles', null, ['select' => 'id', 'limit' => '1'], [
            'timeout' => 5,
            'connect_timeout' => 5,
        ]);
        if (!$db->isSuccess($users)) {
            $status = $users['status'] ?? 0;
            if ($status === 404) {
                return [
                    'connected'   => false,
                    'status_code' => 'schema_missing',
                    'label'       => 'Schema Incomplete',
                    'message'     => 'Database connected, but `user_roles` table not found. Please run migrations.',
                    'latency_ms'  => $latency,
                    'project'     => $project,
                    'http_status' => 503,
                ];
            }
            return [
                'connected'   => false,
                'status_code' => 'db_error',
                'label'       => 'DB Offline',
                'message'     => 'Database reachable but user_roles query failed: ' . ($users['message'] ?? ''),
                'latency_ms'  => $latency,
                'project'     => $project,
                'http_status' => 503,
            ];
        }

        return [
            'connected'   => true,
            'status_code' => 'connected',
            'label'       => 'DB Live',
            'message'     => 'Database connection healthy',
            'latency_ms'  => $latency,
            'project'     => $project,
            'http_status' => 200,
        ];
    }
}

// ============================================================
// Helper Functions & Input Sanitization
// ============================================================
function jsonResponse($success, $message = '', $data = null, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'success'   => $success,
        'message'   => $message,
        'data'      => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

function sanitize($value) {
    if (is_array($value)) {
        return array_map('sanitize', $value);
    }
    return htmlspecialchars(strip_tags(trim((string)$value)), ENT_QUOTES, 'UTF-8');
}

/**
 * Normalizes and validates a UUID string (RFC 4122).
 * Returns the lowercase trimmed UUID string if valid, or empty string if invalid or empty.
 */
function normalizeUuidValue($value): string {
    if (!is_string($value) && !is_numeric($value)) {
        return '';
    }
    $trimmed = strtolower(trim((string)$value));
    if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $trimmed)) {
        return $trimmed;
    }
    return '';
}

function validateEmail($email) {
    return empty($email) || filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validateMobile($mobile) {
    return preg_match('/^[0-9]{7,15}$/', (string)$mobile);
}

function validatePincode($pincode) {
    return preg_match('/^[0-9]+$/', (string)$pincode);
}

/**
 * Role Normalization & RBAC System (Ira Sportswear CWFMS)
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
        'sales_executive' => ['create', 'view'],
    ];
    return in_array($action, $permissions[$role] ?? [], true);
}

function requireApiAuth() {
    if (!isset($_SESSION['user'])) {
        jsonResponse(false, 'Authentication required. Please log in.', null, 401);
    }
    // Also enforce CSRF for mutating requests
    requireCsrfToken();
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

    $envVars = loadEnvFile();
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
           !str_contains($cfg['token'], '<your') &&
           !str_contains($cfg['token'], 'your_permanent');
}
