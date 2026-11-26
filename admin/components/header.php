<?php
declare(strict_types=1);
/**
 * FILE: admin/components/header.php
 * Đầu trang Admin độc lập; chỉ nạp ../css/tokens.css và admin/css/style.css.
 */
$adminName = $currentAdmin['TenND'] ?? 'Nhân viên';
$adminRoleLabel = ($currentAdmin['Quyen'] ?? '') === 'admin' ? 'Quản trị viên' : 'Nhân viên';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($pageTitle); ?> — Hanamiao Admin</title>
    <link rel="stylesheet" href="../css/fonts.css">
    <link rel="stylesheet" href="../css/tokens.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="admin-body">
<div class="admin-app">
    <header class="admin-header">
        <div class="admin-header-left">
            <button type="button" class="admin-menu-toggle" id="adminSidebarToggle" aria-label="Mở menu quản trị">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="4" x2="20" y1="6" y2="6"/>
                    <line x1="4" x2="20" y1="12" y2="12"/>
                    <line x1="4" x2="20" y1="18" y2="18"/>
                </svg>
            </button>
            <a class="admin-brand" href="index.php?page=dashboard">
                <span class="admin-brand-mark" aria-hidden="true">H</span>
                <span class="admin-brand-name">Hanamiao Admin</span>
            </a>
        </div>

        <div class="admin-header-right">
            <a class="admin-btn admin-btn-outline admin-btn-sm" href="../index.php?page=home">Xem cửa hàng</a>
            <div class="admin-user-chip">
                <span class="admin-user-name"><?php echo h($adminName); ?></span>
                <span class="admin-user-role"><?php echo h($adminRoleLabel); ?></span>
            </div>
        </div>
    </header>

