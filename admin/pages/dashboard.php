<?php
declare(strict_types=1);

/**
 * FILE: admin/pages/dashboard.php
 * Màn hình Tổng quan Admin: Hàng số liệu thực từ MySQL + Đơn hàng gần đây + Cảnh báo tồn kho thấp.
 */

$stats = [
    'pending_orders' => 0,
    'completed_revenue' => 0.0,
    'active_products' => 0,
    'low_stock_flowers' => 0,
];
$recentOrders = [];
$lowStockFlowers = [];

if ($pdo instanceof PDO) {
    try {
        $stats['pending_orders'] = (int)$pdo->query(
            "SELECT COUNT(*) FROM DonHang WHERE TrangThaiDon = 'ChoXacNhan'"
        )->fetchColumn();

        $stats['completed_revenue'] = (float)$pdo->query(
            "SELECT COALESCE(SUM(TongTien), 0) FROM DonHang WHERE TrangThaiDon = 'HoanThanh'"
        )->fetchColumn();

        $stats['active_products'] = (int)$pdo->query(
            "SELECT COUNT(*) FROM SanPham WHERE TrangThai = 1"
        )->fetchColumn();

        $stats['low_stock_flowers'] = (int)$pdo->query(
            "SELECT COUNT(*) FROM Hoa WHERE TrangThai = 1 AND SoLuongTon <= 20"
        )->fetchColumn();

        $recentOrders = $pdo->query(
            "SELECT ID_DH, TenKhach, SDTKhach, NgayGiao, TrangThaiDon, TongTien
             FROM DonHang
             ORDER BY NgayLapDon DESC, ID_DH DESC
             LIMIT 6"
        )->fetchAll();

        $lowStockFlowers = $pdo->query(
            "SELECT h.ID_H, h.TenHoa, h.SoLuongTon, lh.TenLH
             FROM Hoa h
             INNER JOIN LoaiHoa lh ON lh.ID_LH = h.ID_LH
             WHERE h.TrangThai = 1 AND h.SoLuongTon <= 20
             ORDER BY h.SoLuongTon ASC, h.ID_H ASC
             LIMIT 6"
        )->fetchAll();
    } catch (PDOException $exception) {
        error_log('[Hanamiao Dashboard] ' . $exception->getMessage());
        $dbError = 'Không thể tải số liệu tổng quan từ cơ sở dữ liệu.';
    }
}
?>

<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Tổng quan hệ thống</h1>
        <p class="admin-page-subtitle">Dữ liệu vận hành thực tế từ cơ sở dữ liệu hanamiao_db.</p>
    </div>
    <div class="admin-page-actions">
        <a class="admin-btn admin-btn-outline" href="index.php?page=imports">Lập phiếu nhập</a>
        <a class="admin-btn admin-btn-primary" href="index.php?page=orders">Xem đơn hàng</a>
    </div>
</div>

<!-- Hàng số liệu: 1 khối chia 4 cột kẻ mảnh (chuẩn Dashboard A) -->
<section class="admin-stat-strip" aria-label="Chỉ số tổng quan">
    <div class="admin-stat-cell">
        <span class="admin-stat-label">Đơn chờ xác nhận</span>
        <strong class="admin-stat-value tabular-nums"><?php echo $stats['pending_orders']; ?></strong>
        <span class="admin-stat-note">Cần kiểm tra kho &amp; duyệt</span>
    </div>
    <div class="admin-stat-cell">
        <span class="admin-stat-label">Doanh thu hoàn thành</span>
        <strong class="admin-stat-value tabular-nums"><?php echo h(formatMoney($stats['completed_revenue'])); ?></strong>
        <span class="admin-stat-note">Tổng các đơn đã giao</span>
    </div>
    <div class="admin-stat-cell">
        <span class="admin-stat-label">Sản phẩm đang bán</span>
        <strong class="admin-stat-value tabular-nums"><?php echo $stats['active_products']; ?></strong>
        <span class="admin-stat-note">Bó hoa &amp; giỏ hoa hoạt động</span>
    </div>
    <div class="admin-stat-cell">
        <span class="admin-stat-label">Hoa tồn thấp (&le; 20 bông)</span>
        <strong class="admin-stat-value tabular-nums"><?php echo $stats['low_stock_flowers']; ?></strong>
        <span class="admin-stat-note">Cần lưu ý nhập bổ sung</span>
    </div>
</section>

<!-- Lưới widget 2 cột: Widget chính chiếm 2/3, Widget phụ chiếm 1/3 -->
<div class="admin-dashboard-grid">
    <section class="admin-card">
        <div class="admin-card-head">
            <h2 class="admin-card-title">Đơn hàng gần đây</h2>
            <?php if (count($recentOrders) > 0): ?>
                <a class="admin-link" href="index.php?page=orders">Xem tất cả</a>
            <?php endif; ?>
        </div>

        <?php if (count($recentOrders) === 0): ?>
            <div class="admin-empty-inline">
                <p class="admin-muted">Chưa có đơn hàng nào trong hệ thống.</p>
            </div>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách đặt</th>
                            <th>Ngày giao</th>
                            <th>Trạng thái</th>
                            <th class="text-right">Tổng tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <?php $statusMeta = getOrderStatusMeta((string)$order['TrangThaiDon']); ?>
                            <tr>
                                <td class="tabular-nums font-medium">
                                    <a class="admin-link" href="index.php?page=orders&amp;view=<?php echo (int)$order['ID_DH']; ?>">
                                        #DH<?php echo str_pad((string)$order['ID_DH'], 4, '0', STR_PAD_LEFT); ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="cell-primary"><?php echo h($order['TenKhach']); ?></div>
                                    <div class="cell-sub tabular-nums"><?php echo h($order['SDTKhach']); ?></div>
                                </td>
                                <td class="tabular-nums"><?php echo h(formatDate((string)$order['NgayGiao'])); ?></td>
                                <td>
                                    <span class="admin-badge <?php echo h($statusMeta['class']); ?>">
                                        <?php echo h($statusMeta['label']); ?>
                                    </span>
                                </td>
                                <td class="text-right tabular-nums font-medium"><?php echo h(formatMoney($order['TongTien'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-card">
        <div class="admin-card-head">
            <h2 class="admin-card-title">Cảnh báo hoa tồn thấp</h2>
            <?php if (count($lowStockFlowers) > 0): ?>
                <a class="admin-link" href="index.php?page=flowers">Xem kho</a>
            <?php endif; ?>
        </div>

        <?php if (count($lowStockFlowers) === 0): ?>
            <div class="admin-empty-inline">
                <p class="admin-muted">Tất cả nguyên liệu hoa đang ở mức tồn an toàn.</p>
            </div>
        <?php else: ?>
            <ul class="admin-list">
                <?php foreach ($lowStockFlowers as $flower): ?>
                    <?php
                    $qty = (int)$flower['SoLuongTon'];
                    $badgeClass = $qty === 0 ? 'badge-error' : 'badge-warning';
                    $badgeText = $qty === 0 ? 'Hết hàng' : ('Còn ' . $qty . ' bông');
                    ?>
                    <li class="admin-list-row">
                        <div class="admin-list-main">
                            <span class="cell-primary"><?php echo h($flower['TenHoa']); ?></span>
                            <span class="cell-sub"><?php echo h($flower['TenLH']); ?></span>
                        </div>
                        <span class="admin-badge <?php echo $badgeClass; ?> tabular-nums">
                            <?php echo h($badgeText); ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

