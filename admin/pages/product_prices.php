<?php
declare(strict_types=1);

/**
 * FILE: admin/pages/product_prices.php
 * Quản lý Lịch sử giá bán (LichSuGia):
 * - Nhập Ngày bắt đầu (hoặc để trống để tự lấy thời điểm hiện tại NOW()).
 * - Ngày kết thúc trong CSDL mặc định là NULL.
 * - Khi tạo đợt giá mới, hệ thống tự động lấy thời điểm hiện tại (NOW()) để đóng đợt giá cũ
 *   và mở đợt giá mới với NgayKetThuc = NULL trong Transaction.
 */

$products = [];
$priceHistory = [];
$selectedSp = isset($_GET['sp']) ? (int)$_GET['sp'] : 0;
$selectedProduct = null;
$hasOpenPrice = false;

if ($pdo instanceof PDO) {
    try {
        $products = $pdo->query('SELECT ID_SP, TenSanPham FROM SanPham ORDER BY ID_SP ASC')->fetchAll();

        if ($selectedSp === 0 && count($products) > 0) {
            $selectedSp = (int)$products[0]['ID_SP'];
        }

        foreach ($products as $p) {
            if ((int)$p['ID_SP'] === $selectedSp) {
                $selectedProduct = $p;
                break;
            }
        }

        if ($selectedProduct !== null) {
            $stmt = $pdo->prepare(
                'SELECT ID_GB, ID_SP, GiaBan, NgayBatDau, NgayKetThuc
                 FROM LichSuGia
                 WHERE ID_SP = ?
                 ORDER BY (NgayKetThuc IS NULL) DESC, NgayBatDau DESC, ID_GB DESC'
            );
            $stmt->execute([$selectedSp]);
            $priceHistory = $stmt->fetchAll();

            foreach ($priceHistory as $row) {
                if ($row['NgayKetThuc'] === null) {
                    $hasOpenPrice = true;
                    break;
                }
            }
        }
    } catch (PDOException $exception) {
        error_log('[Hanamiao Admin Prices] ' . $exception->getMessage());
        $dbError = 'Không thể tải lịch sử giá bán.';
    }
}
?>

<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Lịch sử giá bán</h1>
        <p class="admin-page-subtitle">Ngày kết thúc mặc định NULL; khi áp dụng đợt giá mới, hệ thống tự động kết thúc giá cũ theo thời điểm hiện tại và bắt đầu giá mới.</p>
    </div>
    <form method="get" action="index.php" class="admin-inline-select">
        <input type="hidden" name="page" value="product_prices">
        <select name="sp" class="admin-input" onchange="this.form.submit()" aria-label="Chọn sản phẩm">
            <?php foreach ($products as $p): ?>
                <?php $pid = (int)$p['ID_SP']; ?>
                <option value="<?php echo $pid; ?>"<?php echo $selectedSp === $pid ? ' selected' : ''; ?>>
                    #<?php echo $pid; ?> — <?php echo h($p['TenSanPham']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if ($selectedProduct === null): ?>
    <section class="admin-card admin-empty">
        <p class="admin-muted">Chưa có sản phẩm nào để thiết lập giá bán.</p>
        <a class="admin-btn admin-btn-primary" href="index.php?page=products">Tạo sản phẩm đầu tiên</a>
    </section>
<?php else: ?>
    <div class="admin-split-grid">
        <section class="admin-card">
            <div class="admin-card-head">
                <h2 class="admin-card-title"><?php echo $hasOpenPrice ? 'Áp dụng đợt giá mới' : 'Thiết lập giá bán ban đầu'; ?></h2>
            </div>

            <form action="actions/price_save.php" method="post" class="admin-form">
                <?php echo csrfInput(); ?>
                <input type="hidden" name="id_sp" value="<?php echo $selectedSp; ?>">

                <div class="admin-field">
                    <label for="gia_ban" class="admin-label">Giá bán mới (VNĐ) <span class="required-mark">*</span></label>
                    <input id="gia_ban" name="gia_ban" type="number" min="0" step="1000" class="admin-input tabular-nums" placeholder="Ví dụ: 480000" required>
                </div>

                <?php if ($hasOpenPrice): ?>
                    <div class="admin-info-note">
                        Sản phẩm đang có mức giá hiệu lực. Khi lưu đợt giá mới, hệ thống sẽ tự lấy thời điểm hiện tại (<code>NOW()</code>) để <strong>kết thúc mức giá cũ</strong> và <strong>bắt đầu mức giá mới</strong> (<code>NgayKetThuc = NULL</code>).
                    </div>
                <?php else: ?>
                    <div class="admin-field">
                        <label for="ngay_bat_dau" class="admin-label">Ngày bắt đầu áp dụng</label>
                        <input
                            id="ngay_bat_dau"
                            name="ngay_bat_dau"
                            type="datetime-local"
                            class="admin-input tabular-nums"
                            value="<?php echo h(date('Y-m-d\TH:i')); ?>"
                        >
                        <span class="cell-sub">Ngày kết thúc mặc định để trống (NULL) cho đến khi có đợt giá mới.</span>
                    </div>
                <?php endif; ?>

                <div class="admin-form-actions">
                    <button type="submit" class="admin-btn admin-btn-primary">
                        <?php echo $hasOpenPrice ? 'Đóng giá cũ & Áp dụng giá mới' : 'Lưu giá bán'; ?>
                    </button>
                </div>
            </form>
        </section>

        <section class="admin-card">
            <div class="admin-card-head">
                <div>
                    <h2 class="admin-card-title"><?php echo h($selectedProduct['TenSanPham']); ?></h2>
                    <p class="cell-sub">Tổng <?php echo count($priceHistory); ?> đợt giá đã ghi nhận.</p>
                </div>
                <a class="admin-btn admin-btn-outline admin-btn-sm" href="index.php?page=product_bom&amp;sp=<?php echo $selectedSp; ?>">Thành phần bó</a>
            </div>

            <?php if (count($priceHistory) === 0): ?>
                <div class="admin-empty-inline"><p class="admin-muted">Sản phẩm chưa có lịch sử giá bán.</p></div>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Đợt</th>
                                <th class="text-right">Giá bán</th>
                                <th>Ngày bắt đầu</th>
                                <th>Ngày kết thúc</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($priceHistory as $row): ?>
                                <?php $isCurrent = ($row['NgayKetThuc'] === null); ?>
                                <tr>
                                    <td class="tabular-nums">#<?php echo (int)$row['ID_GB']; ?></td>
                                    <td class="text-right tabular-nums font-medium"><?php echo h(formatMoney($row['GiaBan'])); ?></td>
                                    <td class="tabular-nums"><?php echo h(formatDate((string)$row['NgayBatDau'], 'd/m/Y H:i:s')); ?></td>
                                    <td class="tabular-nums">
                                        <?php echo $isCurrent ? '<span class="cell-sub">Đang áp dụng (NULL)</span>' : h(formatDate((string)$row['NgayKetThuc'], 'd/m/Y H:i:s')); ?>
                                    </td>
                                    <td>
                                        <span class="admin-badge <?php echo $isCurrent ? 'badge-success' : 'badge-neutral'; ?>">
                                            <?php echo $isCurrent ? 'Hiệu lực' : 'Đã kết thúc'; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
<?php endif; ?>

