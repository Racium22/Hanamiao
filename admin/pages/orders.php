<?php
declare(strict_types=1);

/**
 * FILE: admin/pages/orders.php
 * Quản lý Đơn hàng (DonHang + ChiTietDonHang): lọc trạng thái, tìm kiếm, xem chi tiết & định mức hoa cần dùng.
 */

$statusFilter = isset($_GET['status']) && is_string($_GET['status']) ? trim($_GET['status']) : '';
$keyword = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$viewId = isset($_GET['view']) ? (int)$_GET['view'] : 0;

$allowedStatuses = ['', 'ChoXacNhan', 'DangChuanBi', 'DangGiao', 'HoanThanh', 'DaHuy'];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}

$orders = [];
$statusCounts = [
    '' => 0,
    'ChoXacNhan' => 0,
    'DangChuanBi' => 0,
    'DangGiao' => 0,
    'HoanThanh' => 0,
    'DaHuy' => 0,
];
$viewOrder = null;
$viewItems = [];
$viewRequiredFlowers = [];

if ($pdo instanceof PDO) {
    try {
        $countRows = $pdo->query('SELECT TrangThaiDon, COUNT(*) AS cnt FROM DonHang GROUP BY TrangThaiDon')->fetchAll();
        foreach ($countRows as $r) {
            $st = (string)$r['TrangThaiDon'];
            $cnt = (int)$r['cnt'];
            $statusCounts[''] += $cnt;
            if (isset($statusCounts[$st])) {
                $statusCounts[$st] = $cnt;
            }
        }

        $where = [];
        $params = [];
        if ($statusFilter !== '') {
            $where[] = 'dh.TrangThaiDon = ?';
            $params[] = $statusFilter;
        }
        if ($keyword !== '') {
            $digits = preg_replace('/\D+/', '', $keyword);
            $like = '%' . $keyword . '%';
            if ($digits !== '') {
                $where[] = '(dh.ID_DH = ? OR dh.TenKhach LIKE ? OR dh.SDTKhach LIKE ? OR dh.TenNguoiNhan LIKE ?)';
                $params[] = (int)$digits;
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            } else {
                $where[] = '(dh.TenKhach LIKE ? OR dh.SDTKhach LIKE ? OR dh.TenNguoiNhan LIKE ?)';
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }
        }

        $whereSql = count($where) > 0 ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT dh.ID_DH, dh.NgayLapDon, dh.TenKhach, dh.SDTKhach, dh.TenNguoiNhan,
                       dh.NgayGiao, dh.TrangThaiDon, dh.TongTien, nd.TenND AS NguoiXuLy
                FROM DonHang dh
                LEFT JOIN NguoiDung nd ON nd.ID_ND = dh.ID_ND
                {$whereSql}
                ORDER BY dh.NgayLapDon DESC, dh.ID_DH DESC
                LIMIT 100";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();

        if ($viewId > 0) {
            $oStmt = $pdo->prepare(
                'SELECT dh.*, nd.TenND AS NguoiXuLy
                 FROM DonHang dh
                 LEFT JOIN NguoiDung nd ON nd.ID_ND = dh.ID_ND
                 WHERE dh.ID_DH = ?'
            );
            $oStmt->execute([$viewId]);
            $viewOrder = $oStmt->fetch() ?: null;

            if ($viewOrder !== null) {
                $iStmt = $pdo->prepare(
                    'SELECT ct.ID_CTDH, ct.ID_SP, ct.SoLuong, ct.DonGia, ct.ThanhTien,
                            sp.TenSanPham, sp.HinhAnh
                     FROM ChiTietDonHang ct
                     INNER JOIN SanPham sp ON sp.ID_SP = ct.ID_SP
                     WHERE ct.ID_DH = ?
                     ORDER BY ct.ID_CTDH ASC'
                );
                $iStmt->execute([$viewId]);
                $viewItems = $iStmt->fetchAll();

                $bStmt = $pdo->prepare(
                    'SELECT h.ID_H, h.TenHoa, h.SoLuongTon, h.TrangThai,
                            SUM(ct.SoLuong * t.SoBong) AS TongBongCan
                     FROM ChiTietDonHang ct
                     INNER JOIN ThanhPhanBo t ON t.ID_SP = ct.ID_SP
                     INNER JOIN Hoa h ON h.ID_H = t.ID_H
                     WHERE ct.ID_DH = ?
                     GROUP BY h.ID_H, h.TenHoa, h.SoLuongTon, h.TrangThai
                     ORDER BY h.TenHoa ASC'
                );
                $bStmt->execute([$viewId]);
                $viewRequiredFlowers = $bStmt->fetchAll();
            }
        }
    } catch (PDOException $exception) {
        error_log('[Hanamiao Admin Orders] ' . $exception->getMessage());
        $dbError = 'Không thể tải danh sách đơn hàng.';
    }
}

$statusTabs = [
    '' => 'Tất cả',
    'ChoXacNhan' => 'Chờ xác nhận',
    'DangChuanBi' => 'Đang chuẩn bị',
    'DangGiao' => 'Đang giao',
    'HoanThanh' => 'Hoàn thành',
    'DaHuy' => 'Đã hủy',
];
?>

<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Quản lý đơn hàng</h1>
        <p class="admin-page-subtitle">Duyệt đơn sẽ tự động kiểm tra và trừ tồn kho hoa theo thành phần bó; hủy đơn đang chuẩn bị sẽ tự hoàn kho.</p>
    </div>
    <?php if ($viewOrder !== null): ?>
        <a class="admin-btn admin-btn-outline" href="index.php?page=orders<?php echo $statusFilter !== '' ? '&amp;status=' . h($statusFilter) : ''; ?>">Đóng chi tiết</a>
    <?php endif; ?>
</div>

<?php if ($viewOrder !== null): ?>
    <?php
    $stCode = (string)$viewOrder['TrangThaiDon'];
    $stMeta = getOrderStatusMeta($stCode);
    $orderCode = '#DH' . str_pad((string)$viewOrder['ID_DH'], 4, '0', STR_PAD_LEFT);
    ?>
    <section class="admin-card admin-section-gap">
        <div class="admin-card-head">
            <div>
                <h2 class="admin-card-title tabular-nums">Chi tiết đơn hàng <?php echo h($orderCode); ?></h2>
                <p class="cell-sub tabular-nums">
                    Lập lúc: <?php echo h(formatDate((string)$viewOrder['NgayLapDon'])); ?>
                    &middot; Nhân viên xử lý: <strong><?php echo h($viewOrder['NguoiXuLy'] ?? 'Chưa phân công'); ?></strong>
                </p>
            </div>
            <span class="admin-badge <?php echo h($stMeta['class']); ?>"><?php echo h($stMeta['label']); ?></span>
        </div>

        <div class="admin-order-meta-grid">
            <div class="admin-order-meta-box">
                <p class="admin-nav-heading">Người đặt hàng</p>
                <div class="cell-primary"><?php echo h($viewOrder['TenKhach']); ?></div>
                <div class="cell-sub tabular-nums"><?php echo h($viewOrder['SDTKhach']); ?></div>
            </div>
            <div class="admin-order-meta-box">
                <p class="admin-nav-heading">Người nhận &amp; Giao hoa</p>
                <div class="cell-primary"><?php echo h($viewOrder['TenNguoiNhan']); ?> &middot; <span class="tabular-nums"><?php echo h($viewOrder['SDTNguoiNhan']); ?></span></div>
                <div class="cell-sub"><?php echo h($viewOrder['DiaChiGiao']); ?></div>
                <div class="cell-sub tabular-nums">Giao dự kiến: <strong><?php echo h(formatDate((string)$viewOrder['NgayGiao'])); ?></strong></div>
                <?php if (!empty($viewOrder['GhiChu'])): ?>
                    <div class="cell-sub">Ghi chú / Thiệp: <?php echo h($viewOrder['GhiChu']); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="admin-table-wrap admin-section-gap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th class="text-right">Số lượng</th>
                        <th class="text-right">Đơn giá</th>
                        <th class="text-right">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($viewItems as $line): ?>
                        <tr>
                            <td>
                                <div class="admin-item-cell">
                                    <img class="admin-thumb-sm" src="<?php echo h(getImageUrl($line['HinhAnh'], '../')); ?>" alt="<?php echo h($line['TenSanPham']); ?>">
                                    <span class="cell-primary"><?php echo h($line['TenSanPham']); ?></span>
                                </div>
                            </td>
                            <td class="text-right tabular-nums"><?php echo (int)$line['SoLuong']; ?></td>
                            <td class="text-right tabular-nums"><?php echo h(formatMoney($line['DonGia'])); ?></td>
                            <td class="text-right tabular-nums font-medium"><?php echo h(formatMoney($line['ThanhTien'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-right">Tổng tiền đơn hàng</th>
                        <th class="text-right tabular-nums"><?php echo h(formatMoney($viewOrder['TongTien'])); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php if (count($viewRequiredFlowers) > 0): ?>
            <div class="admin-info-note admin-section-gap">
                <strong>Định mức nguyên liệu hoa cho đơn này:</strong>
                <ul class="admin-bom-inline-list">
                    <?php foreach ($viewRequiredFlowers as $rf): ?>
                        <?php
                        $need = (int)$rf['TongBongCan'];
                        $ton = (int)$rf['SoLuongTon'];
                        $enough = ((int)$rf['TrangThai'] === 1 && $ton >= $need);
                        ?>
                        <li>
                            <span><?php echo h($rf['TenHoa']); ?>: cần <strong class="tabular-nums"><?php echo $need; ?> bông</strong></span>
                            <span class="admin-badge <?php echo ($stCode !== 'ChoXacNhan' || $enough) ? 'badge-neutral' : 'badge-error'; ?> tabular-nums">
                                Tồn hiện tại: <?php echo $ton; ?> bông
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="admin-form-footer-bar">
            <span class="cell-sub">
                <?php if ($stCode === 'ChoXacNhan'): ?>
                    Xác nhận chuẩn bị sẽ trừ trực tiếp số bông theo định mức vào kho hoa tươi.
                <?php elseif ($stCode === 'DangChuanBi'): ?>
                    Đã trừ kho hoa. Nếu hủy đơn ở bước này, hệ thống sẽ hoàn trả toàn bộ số bông vào kho.
                <?php elseif ($stCode === 'DangGiao'): ?>
                    Đơn hàng đang trên đường giao đến người nhận.
                <?php else: ?>
                    Đơn hàng đã ở trạng thái kết thúc, không thể thay đổi thêm.
                <?php endif; ?>
            </span>

            <div class="admin-row-actions">
                <?php if ($stCode === 'ChoXacNhan'): ?>
                    <form action="actions/order_status_save.php" method="post" data-confirm="Hủy đơn hàng <?php echo h($orderCode); ?>?">
                        <?php echo csrfInput(); ?>
                        <input type="hidden" name="id_dh" value="<?php echo (int)$viewOrder['ID_DH']; ?>">
                        <input type="hidden" name="next_status" value="DaHuy">
                        <button type="submit" class="admin-btn admin-btn-danger">Hủy đơn</button>
                    </form>
                    <form action="actions/order_status_save.php" method="post">
                        <?php echo csrfInput(); ?>
                        <input type="hidden" name="id_dh" value="<?php echo (int)$viewOrder['ID_DH']; ?>">
                        <input type="hidden" name="next_status" value="DangChuanBi">
                        <button type="submit" class="admin-btn admin-btn-primary">Xác nhận &amp; Trừ kho</button>
                    </form>
                <?php elseif ($stCode === 'DangChuanBi'): ?>
                    <form action="actions/order_status_save.php" method="post" data-confirm="Hủy đơn đang chuẩn bị và hoàn trả hoa về kho?">
                        <?php echo csrfInput(); ?>
                        <input type="hidden" name="id_dh" value="<?php echo (int)$viewOrder['ID_DH']; ?>">
                        <input type="hidden" name="next_status" value="DaHuy">
                        <button type="submit" class="admin-btn admin-btn-danger">Hủy đơn &amp; Hoàn kho</button>
                    </form>
                    <form action="actions/order_status_save.php" method="post">
                        <?php echo csrfInput(); ?>
                        <input type="hidden" name="id_dh" value="<?php echo (int)$viewOrder['ID_DH']; ?>">
                        <input type="hidden" name="next_status" value="DangGiao">
                        <button type="submit" class="admin-btn admin-btn-primary">Chuyển sang Đang giao</button>
                    </form>
                <?php elseif ($stCode === 'DangGiao'): ?>
                    <form action="actions/order_status_save.php" method="post">
                        <?php echo csrfInput(); ?>
                        <input type="hidden" name="id_dh" value="<?php echo (int)$viewOrder['ID_DH']; ?>">
                        <input type="hidden" name="next_status" value="HoanThanh">
                        <button type="submit" class="admin-btn admin-btn-primary">Đánh dấu Hoàn thành</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<section class="admin-card">
    <div class="admin-toolbar">
        <div class="admin-tab-bar" role="tablist" aria-label="Lọc theo trạng thái đơn hàng">
            <?php foreach ($statusTabs as $key => $label): ?>
                <?php
                $href = 'index.php?page=orders' . ($key !== '' ? '&status=' . urlencode($key) : '') . ($keyword !== '' ? '&q=' . urlencode($keyword) : '');
                $isActive = ($statusFilter === $key);
                ?>
                <a class="admin-tab<?php echo $isActive ? ' active' : ''; ?>" href="<?php echo h($href); ?>">
                    <span><?php echo h($label); ?></span>
                    <span class="admin-tab-count tabular-nums"><?php echo $statusCounts[$key] ?? 0; ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <form class="admin-search-inline" action="index.php" method="get">
            <input type="hidden" name="page" value="orders">
            <?php if ($statusFilter !== ''): ?>
                <input type="hidden" name="status" value="<?php echo h($statusFilter); ?>">
            <?php endif; ?>
            <input
                type="search"
                name="q"
                class="admin-input"
                placeholder="Tìm mã đơn, tên khách, SĐT..."
                value="<?php echo h($keyword); ?>"
            >
            <button type="submit" class="admin-btn admin-btn-outline">Tìm</button>
        </form>
    </div>

    <?php if (count($orders) === 0): ?>
        <div class="admin-empty-inline">
            <p class="admin-muted">Không có đơn hàng nào khớp bộ lọc hiện tại.</p>
        </div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Khách đặt</th>
                        <th>Người nhận &amp; Ngày giao</th>
                        <th>Trạng thái</th>
                        <th class="text-right">Tổng tiền</th>
                        <th class="text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <?php
                        $oid = (int)$o['ID_DH'];
                        $meta = getOrderStatusMeta((string)$o['TrangThaiDon']);
                        ?>
                        <tr>
                            <td>
                                <div class="cell-primary tabular-nums">#DH<?php echo str_pad((string)$oid, 4, '0', STR_PAD_LEFT); ?></div>
                                <div class="cell-sub tabular-nums"><?php echo h(formatDate((string)$o['NgayLapDon'], 'd/m/Y H:i')); ?></div>
                            </td>
                            <td>
                                <div class="cell-primary"><?php echo h($o['TenKhach']); ?></div>
                                <div class="cell-sub tabular-nums"><?php echo h($o['SDTKhach']); ?></div>
                            </td>
                            <td>
                                <div class="cell-primary"><?php echo h($o['TenNguoiNhan']); ?></div>
                                <div class="cell-sub tabular-nums">Giao: <?php echo h(formatDate((string)$o['NgayGiao'], 'd/m/Y H:i')); ?></div>
                            </td>
                            <td>
                                <span class="admin-badge <?php echo h($meta['class']); ?>">
                                    <?php echo h($meta['label']); ?>
                                </span>
                            </td>
                            <td class="text-right tabular-nums font-medium"><?php echo h(formatMoney($o['TongTien'])); ?></td>
                            <td class="text-right">
                                <a class="admin-btn admin-btn-outline admin-btn-sm" href="index.php?page=orders&amp;view=<?php echo $oid; ?><?php echo $statusFilter !== '' ? '&amp;status=' . h($statusFilter) : ''; ?>">
                                    Chi tiết
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

