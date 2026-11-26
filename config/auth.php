<?php
declare(strict_types=1);

/**
 * FILE: config/auth.php
 * Nhiệm vụ: Quản lý Session an toàn, CSRF Token và phân quyền khu vực Admin.
 */

function initSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function generateCsrfToken(): string
{
    initSession();
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfInput(): string
{
    $token = htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function verifyCsrfToken(?string $token): bool
{
    initSession();
    if (!is_string($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function getCurrentAdmin(): ?array
{
    initSession();
    return isset($_SESSION['admin_user']) && is_array($_SESSION['admin_user'])
        ? $_SESSION['admin_user']
        : null;
}

function isAdminLoggedIn(): bool
{
    return getCurrentAdmin() !== null;
}

function loginAdmin(array $user): void
{
    initSession();
    session_regenerate_id(true);
    $_SESSION['admin_user'] = [
        'ID_ND' => (int)$user['ID_ND'],
        'TenND' => (string)$user['TenND'],
        'TenDangNhap' => (string)$user['TenDangNhap'],
        'Quyen' => (string)$user['Quyen'],
    ];
}

function logoutAdmin(): void
{
    initSession();
    unset($_SESSION['admin_user']);
    session_regenerate_id(true);
}

function requireAdminLogin(string $loginUrl = 'login.php'): void
{
    if (!isAdminLoggedIn()) {
        header('Location: ' . $loginUrl);
        exit;
    }
}

function requireRole(array $allowedRoles, string $fallbackUrl = 'index.php?page=dashboard'): void
{
    requireAdminLogin();
    $admin = getCurrentAdmin();
    $role = $admin['Quyen'] ?? '';
    if (!in_array($role, $allowedRoles, true)) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Bạn không có quyền truy cập chức năng này.',
        ];
        header('Location: ' . $fallbackUrl);
        exit;
    }
}

