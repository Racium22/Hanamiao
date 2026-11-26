<?php
declare(strict_types=1);

/**
 * FILE: pages/order_lookup.php
 * Tra cứu đơn hàng theo Mã đơn (ID_DH) và Số điện thoại người đặt (SDTKhach).
 */

$rawId = isset($_GET['id']) && is_string($_GET['id']) ? trim($_GET['id']) : '';
$sdt = isset($_GET['sdt']) && is_string($_GET['sdt']) ? preg_replace('/\s+/', '', trim($_GET['sdt'])) : '';
$hasQuery = ($rawId !== '' || $sdt !== '');

$parsedId = 0;
if ($rawId !== '') {
    $digitsOnly = preg_replace('/\D+/', '', $rawId);
    $parsedId = $digitsOnly !== '' ? (int)$digitsOnly : 0;
}

$order = null;
$lookupError = '';

if ($hasQuery) {
    if ($parsedId <= 0 || $sdt === '') {
        $lookupError = 'Vui lòng nhập đầy đủ Mã đơn hàng (ví dụ: 2 hoặc DH0002) và Số điện thoại người đặt.';
    } elseif ($pdo instanceof PDO) {
        try {
            $order = lookupOrder($pdo, $parsedId, $sdt);
        } catch (PDOException $exception) {
            error_log('[Hanamiao Order Lookup] ' . $exception->getMessage());
            $lookupError = 'Không thể tra cứu đơn hàng lúc này.';
        }
    }
}
?>

<section class="content-section">
    <div class="section-header">
        <div>
            <h1 class="page-title">Tra cứu đơn hàng</h1>
            <p class="section-subtitle">Nhập mã đơn hàng và số điện thoại người đặt để theo dõi trạng thái chuẩn bị và giao hoa.</p>
        </div>
    </div>

    <form class="panel lookup-form" action="index.php" method="get">
        <input type="hidden" name="page" value="order_lookup">

        <div class="form-field">
            <label for="lookup-id" class="field-label">
                Mã đơn hàng <span class="required-mark" aria-hidden="true">*</span>
            </label>
            <input
                id="lookup-id"
                name="id"
                type="text"
                class="input-control tabular-nums"
                placeholder="Ví dụ: 2 hoặc DH0002"
                value="<?php echo h($rawId); ?>"
                required
            >
        </div>

        <div class="form-field">
            <label for="lookup-sdt" class="field-label">
                Số điện thoại người đặt <span class="required-mark" aria-hidden="true">*</span>
            </label>
            <input
                id="lookup-sdt"
                name="sdt"
                type="tel"
                class="input-control tabular-nums"
                placeholder="Ví dụ: 0938112233"
                value="<?php echo h($sdt); ?>"
                required
            >
        </div>

        <div class="lookup-submit">
            <button type="submit" class="btn btn-primary btn-block">Tra cứu</button>
        </div>
    </form>

    <?php if ($lookupError !== ''): ?>
        <div class="alert alert-error" role="alert"><?php echo h($lookupError); ?></div>
    <?php elseif ($hasQuery && $order === null): ?>
        <div class="panel empty-state">
            <h2 class="empty-title">Không tìm thấy đơn hàng khớp thông tin</h2>
            <p class="text-muted">Vui lòng kiểm tra lại đúng Mã đơn và Số điện thoại người đặt hàng.</p>
        </div>
    <?php elseif ($order !== null): ?>
        <?php $statusMeta = getOrderStatusMeta((string)$order['TrangThaiDon']); ?>
        <section class="panel order-result-card">
            <div class="order-result-head">
                <div>
                    <h2 class="section-title tabular-nums">
                        Đơn hàng #DH<?php echo str_pad((string)$order['ID_DH'], 4, '0', STR_PAD_LEFT); ?>
                    </h2>
                    <p class="text-xs text-muted tabular-nums">
                        Ngày lập đơn: <?php echo h(formatDate((string)$order['NgayLapDon'])); ?>
                    </p>
                </div>
                <span class="badge <?php echo h($statusMeta['class']); ?>">
                    <?php echo h($statusMeta['label']); ?>
                </span>
            </div>

            <div class="order-meta-grid">
                <div class="order-meta-box">
                    <h3 class="order-meta-title">Người đặt</h3>
                    <p class="order-meta-line"><strong><?php echo h($order['TenKhach']); ?></strong></p>
                    <p class="order-meta-line tabular-nums text-muted"><?php echo h($order['SDTKhach']); ?></p>
                </div>

                <div class="order-meta-box">
                    <h3 class="order-meta-title">Người nhận &amp; Giao hàng</h3>
                    <p class="order-meta-line">
                        <strong><?php echo h($order['TenNguoiNhan']); ?></strong>
                        <span class="text-muted tabular-nums">&middot; <?php echo h($order['SDTNguoiNhan']); ?></span>
                    </p>
                    <p class="order-meta-line"><?php echo h($order['DiaChiGiao']); ?></p>
                    <p class="order-meta-line text-xs text-muted tabular-nums">
                        Giao dự kiến: <?php echo h(formatDate((string)$order['NgayGiao'])); ?>
                    </p>
                    <?php if (!empty($order['GhiChu'])): ?>
                        <p class="order-meta-line text-xs text-muted">
                            Ghi chú / Thiệp: <?php echo h($order['GhiChu']); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="table-wrap">
                <table class="store-table">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th class="text-right">Số lượng</th>
                            <th class="text-right">Đơn giá</th>
                            <th class="text-right">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order['items'] as $line): ?>
                            <tr>
                                <td class="font-medium"><?php echo h($line['TenSanPham']); ?></td>
                                <td class="text-right tabular-nums"><?php echo (int)$line['SoLuong']; ?></td>
                                <td class="text-right tabular-nums"><?php echo h(formatMoney($line['DonGia'])); ?></td>
                                <td class="text-right tabular-nums font-medium"><?php echo h(formatMoney($line['ThanhTien'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-right">Tổng tiền đơn hàng</th>
                            <th class="text-right tabular-nums order-grand-total">
                                <?php echo h(formatMoney($order['TongTien'])); ?>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    <?php endif; ?>
</section>

