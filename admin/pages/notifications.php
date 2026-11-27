<?php
declare(strict_types=1);

/**
 * FILE: admin/pages/notifications.php
 * Trung tâm Thông báo & Xử lý Thông báo phía Admin (REQ-NOTI-01).
 */

$filter = isset($_GET['filter']) && is_string($_GET['filter']) ? trim($_GET['filter']) : 'all';
$allowedFilters = ['all', 'unread', 'orders', 'inventory', 'products'];
if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}

$allNotis = $adminNotifications ?? [];
$counts = [
    'all' => count($allNotis),
    'unread' => 0,
    'orders' => 0,
    'inventory' => 0,
    'products' => 0,
];

foreach ($allNotis as $n) {
    if (!$n['is_read']) {
        $counts['unread']++;
    }
    $cat = (string)$n['category'];
    if (isset($counts[$cat])) {
        $counts[$cat]++;
    }
}

$filteredNotis = array_values(array_filter($allNotis, static function (array $n) use ($filter): bool {
    if ($filter === 'all') {
        return true;
    }
    if ($filter === 'unread') {
        return !$n['is_read'];
    }
    return $n['category'] === $filter;
}));

$hasReadItems = ($counts['all'] > $counts['unread']);

$filterTabs = [
    'all' => 'Tất cả',
    'unread' => 'Chưa đọc',
    'orders' => 'Đơn hàng',
    'inventory' => 'Kho hoa',
    'products' => 'Sản phẩm',
];
?>

<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Trung tâm thông báo</h1>
        <p class="admin-page-subtitle">Theo dõi và xử lý kịp thời đơn hàng chờ duyệt, cảnh báo tồn kho hoa thấp và sản phẩm thiếu cấu hình.</p>
    </div>

    <div class="admin-page-actions">
        <?php if ($hasReadItems): ?>
            <form action="actions/notification_action.php" method="post">
                <?php echo csrfInput(); ?>
                <input type="hidden" name="action" value="reset_read">
                <input type="hidden" name="redirect" value="notifications">
                <button type="submit" class="admin-btn admin-btn-outline">Hiện lại đã đọc</button>
            </form>
        <?php endif; ?>

        <?php if ($counts['unread'] > 0): ?>
            <form action="actions/notification_action.php" method="post">
                <?php echo csrfInput(); ?>
                <input type="hidden" name="action" value="mark_all_read">
                <input type="hidden" name="redirect" value="notifications">
                <button type="submit" class="admin-btn admin-btn-primary">Đánh dấu tất cả đã đọc</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<section class="admin-card">
    <div class="admin-toolbar">
        <div class="admin-tab-bar" role="tablist" aria-label="Lọc thông báo">
            <?php foreach ($filterTabs as $key => $label): ?>
                <a class="admin-tab<?php echo $filter === $key ? ' active' : ''; ?>" href="index.php?page=notifications&amp;filter=<?php echo h($key); ?>">
                    <span><?php echo h($label); ?></span>
                    <span class="admin-tab-count tabular-nums"><?php echo $counts[$key]; ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (count($filteredNotis) === 0): ?>
        <div class="admin-empty-inline">
            <p class="admin-muted">
                <?php echo $filter === 'unread' ? 'Bạn đã đọc hết tất cả thông báo.' : 'Không có thông báo nào trong nhóm này.'; ?>
            </p>
        </div>
    <?php else: ?>
        <ul class="admin-noti-page-list">
            <?php foreach ($filteredNotis as $noti): ?>
                <?php
                $sep = str_contains($noti['url'], '?') ? '&' : '?';
                $handleUrl = $noti['url'] . $sep . 'read_noti=' . urlencode($noti['key']);
                $sevBadge = $noti['severity'] === 'error' ? 'badge-error' : ($noti['severity'] === 'warning' ? 'badge-warning' : 'badge-neutral');
                $sevLabel = $noti['severity'] === 'error' ? 'Khẩn cấp' : 'Cần chú ý';
                ?>
                <li class="admin-noti-page-row<?php echo $noti['is_read'] ? ' is-read' : ''; ?>">
                    <span class="admin-noti-icon-box admin-noti-tone-<?php echo h($noti['severity']); ?>">
                        <i data-lucide="<?php echo h($noti['icon']); ?>" class="admin-btn-icon" aria-hidden="true"></i>
                    </span>

                    <div class="admin-noti-page-main">
                        <div class="admin-noti-page-top">
                            <span class="admin-badge badge-neutral"><?php echo h($noti['category_label']); ?></span>
                            <span class="admin-badge <?php echo $sevBadge; ?>"><?php echo h($sevLabel); ?></span>
                            <span class="cell-sub tabular-nums"><?php echo h($noti['time']); ?></span>
                            <?php if (!$noti['is_read']): ?>
                                <span class="admin-noti-dot" title="Chưa đọc"></span>
                            <?php endif; ?>
                        </div>
                        <div class="admin-noti-page-title"><?php echo h($noti['title']); ?></div>
                        <div class="cell-sub"><?php echo h($noti['message']); ?></div>
                    </div>

                    <div class="admin-row-actions">
                        <a class="admin-btn admin-btn-primary admin-btn-sm" href="<?php echo h($handleUrl); ?>">
                            <?php echo h($noti['action_label']); ?>
                        </a>
                        <?php if (!$noti['is_read']): ?>
                            <form action="actions/notification_action.php" method="post">
                                <?php echo csrfInput(); ?>
                                <input type="hidden" name="action" value="mark_read">
                                <input type="hidden" name="noti_key" value="<?php echo h($noti['key']); ?>">
                                <input type="hidden" name="redirect" value="notifications">
                                <input type="hidden" name="filter" value="<?php echo h($filter); ?>">
                                <button type="submit" class="admin-btn admin-btn-outline admin-btn-sm">Đã đọc</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

