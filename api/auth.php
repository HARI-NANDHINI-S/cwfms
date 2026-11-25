<?php
// ============================================================
// api/auth.php - Authentication, Password Management & RBAC
// Ira Sportswear / Studio 3 Interactive - CWFMS
// ============================================================
require_once '../includes/config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Endpoints exempt from active session requirement
if ($method === 'GET' && ($action === 'csrf' || $action === 'health')) {
    if ($action === 'csrf') {
        jsonResponse(true, 'CSRF token', ['csrf_token' => getCsrfToken()]);
    }
    if ($action === 'health') {
        jsonResponse(true, 'CWFMS Authentication API operational', [
            'status' => 'healthy',
            'timestamp' => time()
        ]);
    }
}

if ($method !== 'GET' || $action !== 'me') {
    requireApiAuth();
}

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_GET['action'] ?? '';

// ============================================================
// GET: Current session info
// ============================================================
if ($method === 'GET' && $action === 'me') {
    if (!isset($_SESSION['user'])) {
        jsonResponse(false, 'Not authenticated', null, 401);
    }
    $user = getCurrentUser();
    $user['csrf_token'] = getCsrfToken();
    jsonResponse(true, 'Session active', $user);
}

// ============================================================
// GET: List all users (Admin only)
// ============================================================
if ($method === 'GET' && $action === 'users') {
    $user = getCurrentUser();
    if (!canManageUsers($user)) {
        jsonResponse(false, 'Permission denied. Only Admin can access user management.', null, 403);
    }

    $db = new Supabase(true);
    $result = $db->select('user_roles', [
        'select' => 'id,user_email,user_name,role,is_active,created_at',
        'order'  => 'created_at.asc'
    ]);

    if ($result['status'] === 200) {
        $users = $result['data'] ?? [];
        foreach ($users as &$u) {
            $u['role_label'] = getRoleLabel($u['role'] ?? 'staff');
            $u['business_role'] = normalizeRole($u['role'] ?? 'staff');
        }
        unset($u);
        jsonResponse(true, 'Users fetched', $users);
    } else {
        jsonResponse(false, 'Failed to fetch users', null, 500);
    }
}

// ============================================================
// POST: Update user role (Admin only)
// ============================================================
if ($method === 'POST' && $action === 'update_role') {
    $currentUser = getCurrentUser();
    if (!canManageUsers($currentUser)) {
        jsonResponse(false, 'Permission denied. Only Admin can update user roles.', null, 403);
    }

    $userId = sanitize($input['user_id'] ?? '');
    $role   = strtolower(sanitize($input['role'] ?? ''));

    if ($userId === ($currentUser['id'] ?? '')) {
        jsonResponse(false, 'Administrators cannot modify their own role.', null, 403);
    }

    $validRoles = ['admin', 'manager', 'staff', 'sales_manager', 'sales_executive'];
    if (!$userId || !in_array($role, $validRoles, true)) {
        jsonResponse(false, 'Invalid input. Allowed roles: admin, manager, staff, sales_manager, sales_executive.', null, 400);
    }

    $db = new Supabase(true);
    $result = $db->update('user_roles', ['role' => $role], ['id' => 'eq.' . $userId]);

    if ($result['status'] >= 200 && $result['status'] < 300) {
        jsonResponse(true, 'Role updated successfully');
    } else {
        jsonResponse(false, 'Failed to update role', null, 500);
    }
}

// ============================================================
// POST: Create new user (Admin only)
// ============================================================
if ($method === 'POST' && $action === 'create_user') {
    $currentUser = getCurrentUser();
    if (!canManageUsers($currentUser)) {
        jsonResponse(false, 'Permission denied. Only Admin can create users.', null, 403);
    }

    $email = strtolower(sanitize($input['email'] ?? ''));
    $name  = sanitize($input['name'] ?? '');
    $role  = strtolower(sanitize($input['role'] ?? 'staff'));
    $rawPassword = (string)($input['password'] ?? '');

    if (!$email || !$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'Valid name and email required', null, 400);
    }

    $validRoles = ['admin', 'manager', 'staff', 'sales_manager', 'sales_executive'];
    if (!in_array($role, $validRoles, true)) {
        jsonResponse(false, 'Invalid role. Allowed roles: admin, manager, staff, sales_manager, sales_executive.', null, 400);
    }

    // Default password policy: minimum 8 characters
    if (empty($rawPassword)) {
        $rawPassword = bin2hex(random_bytes(6)); // Generated 12-char temporary password
    } elseif (strlen($rawPassword) < 8) {
        jsonResponse(false, 'Password must be at least 8 characters long.', null, 400);
    }

    $passwordHash = password_hash($rawPassword, PASSWORD_DEFAULT);

    $db = new Supabase(true);
    $userData = [
        'user_email'    => $email,
        'user_name'     => $name,
        'role'          => $role,
        'is_active'     => true,
        'password_hash' => $passwordHash,
    ];

    $result = $db->insert('user_roles', $userData);

    if ($result['status'] >= 200 && $result['status'] < 300) {
        $created = $result['data'][0] ?? null;
        if ($created && isset($created['password_hash'])) {
            unset($created['password_hash']);
        }
        jsonResponse(true, 'User created successfully', [
            'user' => $created,
            'initial_password' => $rawPassword
        ]);
    } else {
        jsonResponse(false, 'Failed to create user (email may already exist)', null, 500);
    }
}

// ============================================================
// POST: Reset / Update User Password
// ============================================================
if ($method === 'POST' && ($action === 'reset_password' || $action === 'update_password')) {
    $currentUser = getCurrentUser();
    $userId = sanitize($input['user_id'] ?? ($currentUser['id'] ?? ''));
    $newPassword = (string)($input['new_password'] ?? '');

    if (empty($userId) || empty($newPassword)) {
        jsonResponse(false, 'User ID and new password are required.', null, 400);
    }

    if (strlen($newPassword) < 8) {
        jsonResponse(false, 'New password must be at least 8 characters long.', null, 400);
    }

    $db = new Supabase(true);

    // If updating own password, verify old password unless admin resetting another user
    if ($action === 'update_password' || ($userId === ($currentUser['id'] ?? '') && !canManageUsers($currentUser))) {
        $oldPassword = (string)($input['old_password'] ?? '');
        $userCheck = $db->select('user_roles', ['id' => 'eq.' . $userId, 'select' => 'password_hash']);
        if (!$db->isSuccess($userCheck) || empty($userCheck['data'])) {
            jsonResponse(false, 'User not found.', null, 404);
        }
        $currentHash = $userCheck['data'][0]['password_hash'] ?? '';
        if (!password_verify($oldPassword, $currentHash)) {
            jsonResponse(false, 'Current password is incorrect.', null, 400);
        }
    } else {
        // Administrative password reset
        if (!canManageUsers($currentUser)) {
            jsonResponse(false, 'Permission denied. Only Admin can reset another user\'s password.', null, 403);
        }
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $updateResult = $db->update('user_roles', ['password_hash' => $newHash], ['id' => 'eq.' . $userId]);

    if ($db->isSuccess($updateResult)) {
        jsonResponse(true, 'Password updated successfully.');
    } else {
        jsonResponse(false, 'Failed to update password.', null, 500);
    }
}

// ============================================================
// POST: Toggle user active status
// ============================================================
if ($method === 'POST' && $action === 'toggle_user') {
    $currentUser = getCurrentUser();
    if (!canManageUsers($currentUser)) {
        jsonResponse(false, 'Permission denied. Only Admin can toggle user status.', null, 403);
    }

    $userId   = sanitize($input['user_id'] ?? '');
    $isActive = (bool)($input['is_active'] ?? false);

    if (!$userId) jsonResponse(false, 'User ID required', null, 400);

    if ($userId === ($currentUser['id'] ?? '') && !$isActive) {
        jsonResponse(false, 'Administrators cannot deactivate their own account.', null, 403);
    }

    $db = new Supabase(true);
    $result = $db->update('user_roles', ['is_active' => $isActive], ['id' => 'eq.' . $userId]);

    if ($result['status'] >= 200 && $result['status'] < 300) {
        jsonResponse(true, 'User status updated');
    } else {
        jsonResponse(false, 'Failed to update status', null, 500);
    }
}

jsonResponse(false, 'Invalid request', null, 405);
