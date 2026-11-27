<?php
declare(strict_types=1);
/**
 * FILE: admin/components/sidebar.php
 * Thanh điều hướng bên trái Admin với gói icon Lucide chạy local (không dùng emoji bàn phím).
 */
$isAdminRole = ($currentAdmin['Quyen'] ?? '') === 'admin';

$navGroups = [
    'Vận hành' => [
        'dashboard' => ['label' => 'Tổng quan', 'icon' => 'layout-dashboard'],
        'orders' => ['label' => 'Đơn hàng', 'icon' => 'shopping-bag'],
        'notifications' => ['label' => 'Thông báo', 'icon' => 'bell', 'badge' => (int)($unreadNotiCount ?? 0)],
    ],
    'Sản phẩm & Giá' => [
        'product_types' => ['label' => 'Loại sản phẩm', 'icon' => 'folder-tree'],
        'products' => ['label' => 'Sản phẩm hoa', 'icon' => 'gift'],
        'product_bom' => ['label' => 'Thành phần bó', 'icon' => 'layers'],
        'product_prices' => ['label' => 'Lịch sử giá', 'icon' => 'tag'],
    ],
    'Kho hoa tươi' => [
        'flower_types' => ['label' => 'Loại hoa', 'icon' => 'sprout'],
        'flowers' => ['label' => 'Tồn kho hoa', 'icon' => 'flower-2'],
        'suppliers' => ['label' => 'Nhà cung cấp', 'icon' => 'truck'],
        'imports' => ['label' => 'Phiếu nhập kho', 'icon' => 'package-plus'],
        'wastage' => ['label' => 'Hao hụt hoa', 'icon' => 'alert-triangle'],
    ],
];

if ($isAdminRole) {
    $navGroups['Hệ thống'] = [
        'menus' => ['label' => 'Menu đa cấp', 'icon' => 'list-tree'],
        'users' => ['label' => 'Người dùng', 'icon' => 'users'],
    ];
}
?>
<div class="admin-sidebar-backdrop" id="adminSidebarBackdrop"></div>
<aside class="admin-sidebar" id="adminSidebar" aria-label="Menu quản trị">
    <nav class="admin-nav">
        <?php foreach ($navGroups as $groupName => $items): ?>
            <div class="admin-nav-group">
                <p class="admin-nav-heading"><?php echo h($groupName); ?></p>
                <ul class="admin-nav-list">
                    <?php foreach ($items as $key => $item): ?>
                        <li>
                            <a
                                class="admin-nav-link<?php echo $page === $key ? ' active' : ''; ?>"
                                href="index.php?page=<?php echo h($key); ?>"
                            >
                                <i data-lucide="<?php echo h($item['icon']); ?>" class="admin-nav-icon" aria-hidden="true"></i>
                                <span class="admin-nav-text"><?php echo h($item['label']); ?></span>
                                <?php if (!empty($item['badge'])): ?>
                                    <span class="admin-nav-count tabular-nums"><?php echo (int)$item['badge']; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </nav>

    <div class="admin-sidebar-footer">
        <form action="logout.php" method="post">
            <?php echo csrfInput(); ?>
            <button type="submit" class="admin-btn admin-btn-danger admin-btn-block">
                <i data-lucide="log-out" class="admin-btn-icon" aria-hidden="true"></i>
                <span>Đăng xuất</span>
            </button>
        </form>
    </div>
</aside>
