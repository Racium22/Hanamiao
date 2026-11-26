<?php
declare(strict_types=1);

/**
 * FILE: admin/logout.php
 * Đăng xuất phiên quản trị an toàn (chỉ chấp nhận POST có CSRF token).
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../functions/helpers.php';

initSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?page=dashboard');
    exit;
}

$token = $_POST['csrf_token'] ?? null;
if (!verifyCsrfToken(is_string($token) ? $token : null)) {
    setFlash('error', 'Yêu cầu đăng xuất không hợp lệ.');
    header('Location: index.php?page=dashboard');
    exit;
}

logoutAdmin();
header('Location: login.php');
exit;

