<?php
declare(strict_types=1);
/**
 * FILE: admin/components/header.php
 * Đầu trang Admin độc lập + nút chuông & panel thông báo theo chuẩn overlay.md.
 */
$adminName = $currentAdmin['TenND'] ?? 'Nhân viên';
$adminRoleLabel = ($currentAdmin['Quyen'] ?? '') === 'admin' ? 'Quản trị viên' : 'Nhân viên';
$notiPreview = array_slice($adminNotifications ?? [], 0, 6);
$unreadCount = (int)($unreadNotiCount ?? 0);
$bellAria = $unreadCount > 0 ? ('Thông báo, ' . $unreadCount . ' chưa đọc') : 'Thông báo';
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
                <i data-lucide="menu" class="admin-btn-icon" aria-hidden="true"></i>
            </button>
            <a class="admin-brand" href="index.php?page=dashboard">
                <span class="admin-brand-mark" aria-hidden="true">H</span>
                <span class="admin-brand-name">Hanamiao Admin</span>
            </a>
        </div>

        <div class="admin-header-right">
            <div class="admin-noti-wrap">
                <button
                    type="button"
                    class="admin-icon-btn<?php echo $unreadCount > 0 ? ' has-unread' : ''; ?>"
                    id="adminNotiToggle"
                    aria-expanded="false"
                    aria-controls="adminNotiPanel"
                    aria-label="<?php echo h($bellAria); ?>"
                    title="<?php echo h($bellAria); ?>"
                >
                    <i data-lucide="<?php echo $unreadCount > 0 ? 'bell-dot' : 'bell'; ?>" class="admin-bell-icon" aria-hidden="true"></i>
                    <?php if ($unreadCount > 0): ?>
                        <span class="admin-noti-count tabular-nums"><?php echo $unreadCount > 99 ? '99+' : $unreadCount; ?></span>
                    <?php endif; ?>
                </button>

                <div class="admin-noti-panel" id="adminNotiPanel" role="dialog" aria-label="Thông báo quản trị" hidden>
                    <div class="admin-noti-head">
                        <h2 class="admin-noti-title">Thông báo</h2>
                        <?php if ($unreadCount > 0): ?>
                            <form action="actions/notification_action.php" method="post">
                                <?php echo csrfInput(); ?>
                                <input type="hidden" name="action" value="mark_all_read">
                                <input type="hidden" name="redirect" value="<?php echo h($page); ?>">
                                <button type="submit" class="admin-noti-mark-btn">Đánh dấu đã đọc</button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php if (count($notiPreview) === 0): ?>
                        <div class="admin-noti-empty">
                            <p class="admin-muted">Chưa có thông báo cần xử lý.</p>
                        </div>
                    <?php else: ?>
                        <div class="admin-noti-list">
                            <?php foreach ($notiPreview as $noti): ?>
                                <?php
                                $sep = str_contains($noti['url'], '?') ? '&' : '?';
                                $targetUrl = $noti['url'] . $sep . 'read_noti=' . urlencode($noti['key']);
                                ?>
                                <a class="admin-noti-item<?php echo $noti['is_read'] ? ' is-read' : ''; ?>" href="<?php echo h($targetUrl); ?>">
                                    <span class="admin-noti-icon-box admin-noti-tone-<?php echo h($noti['severity']); ?>">
                                        <i data-lucide="<?php echo h($noti['icon']); ?>" class="admin-btn-icon" aria-hidden="true"></i>
                                    </span>
                                    <span class="admin-noti-body">
                                        <span class="admin-noti-item-title"><?php echo h($noti['title']); ?></span>
                                        <span class="admin-noti-item-desc"><?php echo h($noti['message']); ?></span>
                                        <span class="admin-noti-item-time tabular-nums"><?php echo h($noti['time']); ?></span>
                                    </span>
                                    <?php if (!$noti['is_read']): ?>
                                        <span class="admin-noti-dot" aria-label="Chưa đọc"></span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="admin-noti-foot">
                        <a class="admin-link" href="index.php?page=notifications">
                            Xem trung tâm thông báo (<?php echo count($adminNotifications ?? []); ?>)
                        </a>
                    </div>
                </div>
            </div>

            <a class="admin-btn admin-btn-outline admin-btn-sm" href="../index.php?page=home">Xem cửa hàng</a>
            <div class="admin-user-chip">
                <span class="admin-user-name"><?php echo h($adminName); ?></span>
                <span class="admin-user-role"><?php echo h($adminRoleLabel); ?></span>
            </div>
        </div>
    </header>
