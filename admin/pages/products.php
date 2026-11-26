<?php
declare(strict_types=1);

/**
 * FILE: admin/pages/products.php
 * Quản lý Sản phẩm thành phẩm (SanPham) kèm ảnh thật, giá hiện hành & liên kết nhanh BOM/Giá.
 */

$products = [];
$productTypes = [];
$editProduct = null;
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

if ($pdo instanceof PDO) {
    try {
        $productTypes = $pdo->query('SELECT ID_LSP, TenLoaiSanPham FROM LoaiSanPham WHERE TrangThai = 1 ORDER BY TenLoaiSanPham ASC')->fetchAll();
        $products = $pdo->query(
            'SELECT sp.ID_SP, sp.TenSanPham, sp.ID_LSP, sp.HinhAnh, sp.MoTa, sp.TrangThai,
                    lsp.TenLoaiSanPham,
                    (
                        SELECT g.GiaBan
                        FROM LichSuGia g
                        WHERE g.ID_SP = sp.ID_SP
                          AND g.NgayBatDau <= NOW()
                          AND (g.NgayKetThuc IS NULL OR g.NgayKetThuc > NOW())
                        ORDER BY (g.NgayKetThuc IS NULL) DESC, g.NgayBatDau DESC, g.ID_GB DESC
                        LIMIT 1
                    ) AS GiaBanHienTai,
                    (SELECT COUNT(*) FROM ThanhPhanBo t WHERE t.ID_SP = sp.ID_SP) AS SoThanhPhan
             FROM SanPham sp
             INNER JOIN LoaiSanPham lsp ON lsp.ID_LSP = sp.ID_LSP
             ORDER BY sp.ID_SP DESC'
        )->fetchAll();

        if ($editId > 0) {
            $stmt = $pdo->prepare('SELECT ID_SP, TenSanPham, ID_LSP, HinhAnh, MoTa, TrangThai FROM SanPham WHERE ID_SP = ?');
            $stmt->execute([$editId]);
            $editProduct = $stmt->fetch() ?: null;
        }
    } catch (PDOException $exception) {
        error_log('[Hanamiao Admin Products] ' . $exception->getMessage());
        $dbError = 'Không thể tải danh sách sản phẩm.';
    }
}
?>

<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Sản phẩm hoa</h1>
        <p class="admin-page-subtitle">Quản lý các mẫu bó hoa, giỏ hoa bán cho khách; cấu hình công thức bó và giá bán ở từng dòng.</p>
    </div>
    <?php if ($editProduct): ?>
        <a class="admin-btn admin-btn-outline" href="index.php?page=products">Thêm sản phẩm mới</a>
    <?php endif; ?>
</div>

<div class="admin-split-grid">
    <section class="admin-card">
        <div class="admin-card-head">
            <h2 class="admin-card-title"><?php echo $editProduct ? 'Cập nhật sản phẩm #' . (int)$editProduct['ID_SP'] : 'Thêm sản phẩm mới'; ?></h2>
        </div>

        <form action="actions/product_save.php" method="post" enctype="multipart/form-data" class="admin-form">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id_sp" value="<?php echo (int)($editProduct['ID_SP'] ?? 0); ?>">

            <div class="admin-field">
                <label for="ten_sp" class="admin-label">Tên sản phẩm <span class="required-mark">*</span></label>
                <input id="ten_sp" name="ten_sp" type="text" class="admin-input" maxlength="180" value="<?php echo h($editProduct['TenSanPham'] ?? ''); ?>" required>
            </div>

            <div class="admin-form-row">
                <div class="admin-field">
                    <label for="id_lsp" class="admin-label">Loại sản phẩm <span class="required-mark">*</span></label>
                    <?php $selLsp = (int)($editProduct['ID_LSP'] ?? 0); ?>
                    <select id="id_lsp" name="id_lsp" class="admin-input" required>
                        <option value="">-- Chọn loại --</option>
                        <?php foreach ($productTypes as $lsp): ?>
                            <?php $idL = (int)$lsp['ID_LSP']; ?>
                            <option value="<?php echo $idL; ?>"<?php echo $selLsp === $idL ? ' selected' : ''; ?>>
                                <?php echo h($lsp['TenLoaiSanPham']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="admin-field">
                    <label for="trang_thai" class="admin-label">Trạng thái</label>
                    <?php $st = (int)($editProduct['TrangThai'] ?? 1); ?>
                    <select id="trang_thai" name="trang_thai" class="admin-input">
                        <option value="1"<?php echo $st === 1 ? ' selected' : ''; ?>>Đang bán</option>
                        <option value="0"<?php echo $st === 0 ? ' selected' : ''; ?>>Ngừng bán</option>
                    </select>
                </div>
            </div>

            <div class="admin-field">
                <label for="mo_ta" class="admin-label">Mô tả sản phẩm</label>
                <textarea id="mo_ta" name="mo_ta" rows="3" class="admin-input admin-textarea"><?php echo h($editProduct['MoTa'] ?? ''); ?></textarea>
            </div>

            <div class="admin-field">
                <label for="hinh_anh" class="admin-label">Ảnh sản phẩm thật (JPG, PNG, WebP &le; 3MB)</label>
                <?php if ($editProduct && !empty($editProduct['HinhAnh'])): ?>
                    <div class="admin-thumb-preview">
                        <img src="<?php echo h(getImageUrl($editProduct['HinhAnh'], '../')); ?>" alt="<?php echo h($editProduct['TenSanPham']); ?>">
                        <span class="cell-sub"><?php echo h($editProduct['HinhAnh']); ?></span>
                    </div>
                <?php endif; ?>
                <input id="hinh_anh" name="hinh_anh" type="file" accept="image/jpeg,image/png,image/webp" class="admin-input admin-file-input">
            </div>

            <div class="admin-form-actions">
                <?php if ($editProduct): ?>
                    <a class="admin-btn admin-btn-outline" href="index.php?page=products">Hủy</a>
                <?php endif; ?>
                <button type="submit" class="admin-btn admin-btn-primary">
                    <?php echo $editProduct ? 'Lưu thay đổi' : 'Thêm sản phẩm'; ?>
                </button>
            </div>
        </form>
    </section>

    <section class="admin-card">
        <div class="admin-card-head">
            <h2 class="admin-card-title">Danh sách sản phẩm (<?php echo count($products); ?>)</h2>
        </div>

        <?php if (count($products) === 0): ?>
            <div class="admin-empty-inline"><p class="admin-muted">Chưa có sản phẩm nào.</p></div>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th class="text-right">Giá hiện hành</th>
                            <th class="text-right">Thành phần</th>
                            <th>Trạng thái</th>
                            <th class="text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $sp): ?>
                            <?php $id = (int)$sp['ID_SP']; $active = (int)$sp['TrangThai'] === 1; ?>
                            <tr>
                                <td>
                                    <div class="admin-item-cell">
                                        <img class="admin-thumb-sm" src="<?php echo h(getImageUrl($sp['HinhAnh'], '../')); ?>" alt="<?php echo h($sp['TenSanPham']); ?>">
                                        <div>
                                            <div class="cell-primary"><?php echo h($sp['TenSanPham']); ?></div>
                                            <div class="cell-sub"><?php echo h($sp['TenLoaiSanPham']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-right tabular-nums font-medium">
                                    <?php echo h(formatMoney($sp['GiaBanHienTai'])); ?>
                                </td>
                                <td class="text-right tabular-nums">
                                    <?php echo (int)$sp['SoThanhPhan']; ?> loại hoa
                                </td>
                                <td>
                                    <span class="admin-badge <?php echo $active ? 'badge-success' : 'badge-neutral'; ?>">
                                        <?php echo $active ? 'Đang bán' : 'Ngừng'; ?>
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="admin-row-actions">
                                        <a class="admin-btn admin-btn-outline admin-btn-sm" href="index.php?page=product_bom&amp;sp=<?php echo $id; ?>">Bó hoa</a>
                                        <a class="admin-btn admin-btn-outline admin-btn-sm" href="index.php?page=product_prices&amp;sp=<?php echo $id; ?>">Giá</a>
                                        <a class="admin-btn admin-btn-outline admin-btn-sm" href="index.php?page=products&amp;edit=<?php echo $id; ?>">Sửa</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

