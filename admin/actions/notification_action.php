<?php
declare(strict_types=1);

/**
 * FILE: admin/actions/notification_action.php
 * Xử lý trạng thái thông báo Admin (đánh dấu đã đọc từng mục, đánh dấu tất cả, hiện lại đã đọc).
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/business_functions.php';

requireAdminLogin('../login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php?page=notifications');
    exit;
}

$redirectPage = trim((string)($_POST['redirect'] ?? 'notifications'));
$allowedRedirects = [
    'dashboard', 'notifications', 'orders', 'products', 'product_types',
    'product_bom', 'product_prices', 'flowers', 'flower_types',
    'suppliers', 'imports', 'wastage', 'menus', 'users',
];
if (!in_array($redirectPage, $allowedRedirects, true)) {
    $redirectPage = 'notifications';
}

$filter = trim((string)($_POST['filter'] ?? ''));
$backUrl = '../index.php?page=' . urlencode($redirectPage);
if ($redirectPage === 'notifications' && in_array($filter, ['all', 'unread', 'orders', 'inventory', 'products'], true)) {
    $backUrl .= '&filter=' . urlencode($filter);
}

if (!verifyCsrfToken(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
    setFlash('error', 'Phiên biểu mẫu không hợp lệ.');
    header('Location: ' . $backUrl);
    exit;
}

if (!isset($_SESSION['admin_read_notis']) || !is_array($_SESSION['admin_read_notis'])) {
    $_SESSION['admin_read_notis'] = [];
}

$action = trim((string)($_POST['action'] ?? ''));

if ($action === 'mark_read') {
    $key = trim((string)($_POST['noti_key'] ?? ''));
    if ($key !== '' && preg_match('/^[a-z0-9_]+$/i', $key)) {
        if (!in_array($key, $_SESSION['admin_read_notis'], true)) {
            $_SESSION['admin_read_notis'][] = $key;
        }
        setFlash('success', 'Đã đánh dấu thông báo là đã đọc.');
    }
} elseif ($action === 'mark_all_read') {
    try {
        $pdo = getConnection();
        $all = getAdminNotifications($pdo, []);
        $keys = [];
        foreach ($all as $item) {
            $keys[] = $item['key'];
        }
        $_SESSION['admin_read_notis'] = array_values(array_unique(array_merge($_SESSION['admin_read_notis'], $keys)));
        setFlash('success', 'Đã đánh dấu tất cả thông báo là đã đọc.');
    } catch (PDOException $exception) {
        error_log('[Hanamiao Noti MarkAll] ' . $exception->getMessage());
        setFlash('error', 'Không thể cập nhật trạng thái thông báo.');
    }
} elseif ($action === 'reset_read') {
    $_SESSION['admin_read_notis'] = [];
    setFlash('success', 'Đã hiển thị lại toàn bộ thông báo chưa xử lý.');
}

header('Location: ' . $backUrl);
exit;

