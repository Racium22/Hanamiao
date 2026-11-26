<?php
declare(strict_types=1);

/**
 * FILE: admin/pages/product_types.php
 * Quản lý Loại sản phẩm bán (LoaiSanPham).
 */

$productTypes = [];
$editType = null;
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

if ($pdo instanceof PDO) {
    try {
        $productTypes = $pdo->query(
            'SELECT lsp.ID_LSP, lsp.TenLoaiSanPham, lsp.TrangThai, COUNT(sp.ID_SP) AS SoSanPham
             FROM LoaiSanPham lsp
             LEFT JOIN SanPham sp ON sp.ID_LSP = lsp.ID_LSP
             GROUP BY lsp.ID_LSP
             ORDER BY lsp.ID_LSP DESC'
        )->fetchAll();

        if ($editId > 0) {
            $stmt = $pdo->prepare('SELECT ID_LSP, TenLoaiSanPham, TrangThai FROM LoaiSanPham WHERE ID_LSP = ?');
            $stmt->execute([$editId]);
            $editType = $stmt->fetch() ?: null;
        }
    } catch (PDOException $exception) {
        error_log('[Hanamiao Admin Product Types] ' . $exception->getMessage());
        $dbError = 'Không thể tải danh sách loại sản phẩm.';
    }
}
?>

<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Loại sản phẩm</h1>
        <p class="admin-page-subtitle">Phân loại các nhóm bó hoa, giỏ hoa, hoa cưới hiển thị trên cửa hàng.</p>
    </div>
    <?php if ($editType): ?>
        <a class="admin-btn admin-btn-outline" href="index.php?page=product_types">Thêm loại sản phẩm</a>
    <?php endif; ?>
</div>

<div class="admin-split-grid">
    <section class="admin-card">
        <div class="admin-card-head">
            <h2 class="admin-card-title"><?php echo $editType ? 'Cập nhật loại #' . (int)$editType['ID_LSP'] : 'Thêm loại sản phẩm mới'; ?></h2>
        </div>

        <form action="actions/product_type_save.php" method="post" class="admin-form">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id_lsp" value="<?php echo (int)($editType['ID_LSP'] ?? 0); ?>">

            <div class="admin-field">
                <label for="ten_lsp" class="admin-label">Tên loại sản phẩm <span class="required-mark">*</span></label>
                <input id="ten_lsp" name="ten_lsp" type="text" class="admin-input" maxlength="120" value="<?php echo h($editType['TenLoaiSanPham'] ?? ''); ?>" required>
            </div>

            <div class="admin-field">
                <label for="trang_thai" class="admin-label">Trạng thái</label>
                <?php $st = (int)($editType['TrangThai'] ?? 1); ?>
                <select id="trang_thai" name="trang_thai" class="admin-input">
                    <option value="1"<?php echo $st === 1 ? ' selected' : ''; ?>>Đang hiển thị</option>
                    <option value="0"<?php echo $st === 0 ? ' selected' : ''; ?>>Tạm ẩn</option>
                </select>
            </div>

            <div class="admin-form-actions">
                <?php if ($editType): ?>
                    <a class="admin-btn admin-btn-outline" href="index.php?page=product_types">Hủy</a>
                <?php endif; ?>
                <button type="submit" class="admin-btn admin-btn-primary">
                    <?php echo $editType ? 'Lưu thay đổi' : 'Thêm loại sản phẩm'; ?>
                </button>
            </div>
        </form>
    </section>

    <section class="admin-card">
        <div class="admin-card-head">
            <h2 class="admin-card-title">Danh sách loại sản phẩm (<?php echo count($productTypes); ?>)</h2>
        </div>

        <?php if (count($productTypes) === 0): ?>
            <div class="admin-empty-inline"><p class="admin-muted">Chưa có loại sản phẩm nào.</p></div>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên loại sản phẩm</th>
                            <th class="text-right">Số sản phẩm</th>
                            <th>Trạng thái</th>
                            <th class="text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productTypes as $lsp): ?>
                            <?php $id = (int)$lsp['ID_LSP']; $active = (int)$lsp['TrangThai'] === 1; ?>
                            <tr>
                                <td class="tabular-nums">#<?php echo $id; ?></td>
                                <td class="font-medium"><?php echo h($lsp['TenLoaiSanPham']); ?></td>
                                <td class="text-right tabular-nums"><?php echo (int)$lsp['SoSanPham']; ?></td>
                                <td>
                                    <span class="admin-badge <?php echo $active ? 'badge-success' : 'badge-neutral'; ?>">
                                        <?php echo $active ? 'Hiển thị' : 'Tạm ẩn'; ?>
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="admin-row-actions">
                                        <a class="admin-btn admin-btn-outline admin-btn-sm" href="index.php?page=product_types&amp;edit=<?php echo $id; ?>">Sửa</a>
                                        <form action="actions/product_type_save.php" method="post">
                                            <?php echo csrfInput(); ?>
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id_lsp" value="<?php echo $id; ?>">
                                            <button type="submit" class="admin-btn <?php echo $active ? 'admin-btn-danger' : 'admin-btn-outline'; ?> admin-btn-sm">
                                                <?php echo $active ? 'Ẩn' : 'Hiện'; ?>
                                            </button>
                                        </form>
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

