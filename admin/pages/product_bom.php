<?php
declare(strict_types=1);

/**
 * FILE: admin/pages/product_bom.php
 * Quản lý Thành phần bó hoa (ThanhPhanBo): định mức số bông của từng loại hoa trong mỗi sản phẩm.
 */

$products = [];
$flowers = [];
$bomItems = [];
$selectedSp = isset($_GET['sp']) ? (int)$_GET['sp'] : 0;
$selectedProduct = null;

if ($pdo instanceof PDO) {
    try {
        $products = $pdo->query('SELECT ID_SP, TenSanPham FROM SanPham ORDER BY ID_SP ASC')->fetchAll();
        $flowers = $pdo->query('SELECT ID_H, TenHoa, SoLuongTon FROM Hoa WHERE TrangThai = 1 ORDER BY TenHoa ASC')->fetchAll();

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
            $bomItems = getProductBom($pdo, $selectedSp);
        }
    } catch (PDOException $exception) {
        error_log('[Hanamiao Admin BOM] ' . $exception->getMessage());
        $dbError = 'Không thể tải dữ liệu thành phần bó hoa.';
    }
}
?>

<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Thành phần bó hoa (Định mức)</h1>
        <p class="admin-page-subtitle">Cấu hình mỗi sản phẩm cần những loại hoa nào và bao nhiêu bông để tự động tính tồn kho &amp; trừ kho khi duyệt đơn.</p>
    </div>
    <form method="get" action="index.php" class="admin-inline-select">
        <input type="hidden" name="page" value="product_bom">
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
        <p class="admin-muted">Chưa có sản phẩm nào để cấu hình thành phần.</p>
        <a class="admin-btn admin-btn-primary" href="index.php?page=products">Tạo sản phẩm đầu tiên</a>
    </section>
<?php else: ?>
    <div class="admin-split-grid">
        <section class="admin-card">
            <div class="admin-card-head">
                <h2 class="admin-card-title">Thêm / Cập nhật hoa trong bó</h2>
            </div>

            <form action="actions/bom_save.php" method="post" class="admin-form">
                <?php echo csrfInput(); ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id_sp" value="<?php echo $selectedSp; ?>">

                <div class="admin-field">
                    <label for="id_h" class="admin-label">Hoa nguyên liệu <span class="required-mark">*</span></label>
                    <select id="id_h" name="id_h" class="admin-input" required>
                        <option value="">-- Chọn loại hoa --</option>
                        <?php foreach ($flowers as $hItem): ?>
                            <option value="<?php echo (int)$hItem['ID_H']; ?>">
                                <?php echo h($hItem['TenHoa']); ?> (tồn: <?php echo (int)$hItem['SoLuongTon']; ?> bông)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="admin-field">
                    <label for="so_bong" class="admin-label">Số bông / 1 sản phẩm <span class="required-mark">*</span></label>
                    <input id="so_bong" name="so_bong" type="number" min="1" value="5" class="admin-input tabular-nums" required>
                    <span class="cell-sub">Nếu hoa đã có trong bó, hệ thống sẽ tự cập nhật lại số bông mới.</span>
                </div>

                <div class="admin-form-actions">
                    <button type="submit" class="admin-btn admin-btn-primary">Lưu thành phần</button>
                </div>
            </form>
        </section>

        <section class="admin-card">
            <div class="admin-card-head">
                <div>
                    <h2 class="admin-card-title"><?php echo h($selectedProduct['TenSanPham']); ?></h2>
                    <p class="cell-sub">
                        Sức chứa kho hiện tại: <strong class="tabular-nums"><?php echo getMaxAvailableQty($pdo, $selectedSp); ?></strong> sản phẩm.
                    </p>
                </div>
                <a class="admin-btn admin-btn-outline admin-btn-sm" href="index.php?page=product_prices&amp;sp=<?php echo $selectedSp; ?>">Thiết lập giá</a>
            </div>

            <?php if (count($bomItems) === 0): ?>
                <div class="admin-empty-inline"><p class="admin-muted">Sản phẩm này chưa có thành phần hoa nào.</p></div>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Hoa nguyên liệu</th>
                                <th class="text-right">Định mức</th>
                                <th class="text-right">Tồn kho hiện có</th>
                                <th class="text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bomItems as $item): ?>
                                <tr>
                                    <td>
                                        <div class="admin-item-cell">
                                            <img class="admin-thumb-sm" src="<?php echo h(getImageUrl($item['HinhAnh'], '../')); ?>" alt="<?php echo h($item['TenHoa']); ?>">
                                            <div>
                                                <div class="cell-primary"><?php echo h($item['TenHoa']); ?></div>
                                                <div class="cell-sub"><?php echo h($item['TenLH']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-right tabular-nums font-medium"><?php echo (int)$item['SoBong']; ?> bông</td>
                                    <td class="text-right tabular-nums"><?php echo (int)$item['SoLuongTon']; ?> bông</td>
                                    <td class="text-right">
                                        <form action="actions/bom_save.php" method="post" data-confirm="Xóa loại hoa này khỏi công thức bó?">
                                            <?php echo csrfInput(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id_sp" value="<?php echo $selectedSp; ?>">
                                            <input type="hidden" name="id_tpb" value="<?php echo (int)$item['ID_TPB']; ?>">
                                            <button type="submit" class="admin-btn admin-btn-danger admin-btn-sm">Xóa</button>
                                        </form>
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

