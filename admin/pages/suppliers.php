<?php
declare(strict_types=1);

/**
 * FILE: admin/pages/suppliers.php
 * Quản lý Nhà cung cấp hoa tươi (NhaCungCap).
 */

$suppliers = [];
$editSupplier = null;
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

if ($pdo instanceof PDO) {
    try {
        $suppliers = $pdo->query(
            'SELECT ncc.ID_NCC, ncc.TenNCC, ncc.SDT, ncc.DiaChi, ncc.TrangThai,
                    COUNT(pn.ID_PN) AS SoPhieuNhap
             FROM NhaCungCap ncc
             LEFT JOIN PhieuNhap pn ON pn.ID_NCC = ncc.ID_NCC
             GROUP BY ncc.ID_NCC
             ORDER BY ncc.ID_NCC DESC'
        )->fetchAll();

        if ($editId > 0) {
            $stmt = $pdo->prepare('SELECT ID_NCC, TenNCC, SDT, DiaChi, TrangThai FROM NhaCungCap WHERE ID_NCC = ?');
            $stmt->execute([$editId]);
            $editSupplier = $stmt->fetch() ?: null;
        }
    } catch (PDOException $exception) {
        error_log('[Hanamiao Admin Suppliers] ' . $exception->getMessage());
        $dbError = 'Không thể tải danh sách nhà cung cấp.';
    }
}
?>

<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Nhà cung cấp</h1>
        <p class="admin-page-subtitle">Quản lý danh sách nhà vườn và đối tác cung cấp hoa tươi cho Hanamiao.</p>
    </div>
    <?php if ($editSupplier): ?>
        <a class="admin-btn admin-btn-outline" href="index.php?page=suppliers">Thêm nhà cung cấp</a>
    <?php endif; ?>
</div>

<div class="admin-split-grid">
    <section class="admin-card">
        <div class="admin-card-head">
            <h2 class="admin-card-title"><?php echo $editSupplier ? 'Cập nhật nhà cung cấp #' . (int)$editSupplier['ID_NCC'] : 'Thêm nhà cung cấp mới'; ?></h2>
        </div>

        <form action="actions/supplier_save.php" method="post" class="admin-form">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id_ncc" value="<?php echo (int)($editSupplier['ID_NCC'] ?? 0); ?>">

            <div class="admin-field">
                <label for="ten_ncc" class="admin-label">Tên nhà cung cấp <span class="required-mark">*</span></label>
                <input id="ten_ncc" name="ten_ncc" type="text" class="admin-input" maxlength="150" value="<?php echo h($editSupplier['TenNCC'] ?? ''); ?>" required>
            </div>

            <div class="admin-field">
                <label for="sdt" class="admin-label">Số điện thoại <span class="required-mark">*</span></label>
                <input id="sdt" name="sdt" type="tel" class="admin-input tabular-nums" maxlength="20" value="<?php echo h($editSupplier['SDT'] ?? ''); ?>" required>
            </div>

            <div class="admin-field">
                <label for="dia_chi" class="admin-label">Địa chỉ <span class="required-mark">*</span></label>
                <input id="dia_chi" name="dia_chi" type="text" class="admin-input" maxlength="255" value="<?php echo h($editSupplier['DiaChi'] ?? ''); ?>" required>
            </div>

            <div class="admin-field">
                <label for="trang_thai" class="admin-label">Trạng thái</label>
                <?php $st = (int)($editSupplier['TrangThai'] ?? 1); ?>
                <select id="trang_thai" name="trang_thai" class="admin-input">
                    <option value="1"<?php echo $st === 1 ? ' selected' : ''; ?>>Đang hợp tác</option>
                    <option value="0"<?php echo $st === 0 ? ' selected' : ''; ?>>Ngừng hợp tác</option>
                </select>
            </div>

            <div class="admin-form-actions">
                <?php if ($editSupplier): ?>
                    <a class="admin-btn admin-btn-outline" href="index.php?page=suppliers">Hủy</a>
                <?php endif; ?>
                <button type="submit" class="admin-btn admin-btn-primary">
                    <?php echo $editSupplier ? 'Lưu thay đổi' : 'Thêm nhà cung cấp'; ?>
                </button>
            </div>
        </form>
    </section>

    <section class="admin-card">
        <div class="admin-card-head">
            <h2 class="admin-card-title">Danh sách nhà cung cấp (<?php echo count($suppliers); ?>)</h2>
        </div>

        <?php if (count($suppliers) === 0): ?>
            <div class="admin-empty-inline"><p class="admin-muted">Chưa có nhà cung cấp nào.</p></div>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Nhà cung cấp</th>
                            <th>Liên hệ</th>
                            <th class="text-right">Phiếu nhập</th>
                            <th>Trạng thái</th>
                            <th class="text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suppliers as $ncc): ?>
                            <?php $id = (int)$ncc['ID_NCC']; $active = (int)$ncc['TrangThai'] === 1; ?>
                            <tr>
                                <td>
                                    <div class="cell-primary"><?php echo h($ncc['TenNCC']); ?></div>
                                    <div class="cell-sub"><?php echo h($ncc['DiaChi']); ?></div>
                                </td>
                                <td class="tabular-nums"><?php echo h($ncc['SDT']); ?></td>
                                <td class="text-right tabular-nums"><?php echo (int)$ncc['SoPhieuNhap']; ?></td>
                                <td>
                                    <span class="admin-badge <?php echo $active ? 'badge-success' : 'badge-neutral'; ?>">
                                        <?php echo $active ? 'Hợp tác' : 'Ngừng'; ?>
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="admin-row-actions">
                                        <a class="admin-btn admin-btn-outline admin-btn-sm" href="index.php?page=suppliers&amp;edit=<?php echo $id; ?>">Sửa</a>
                                        <form action="actions/supplier_save.php" method="post">
                                            <?php echo csrfInput(); ?>
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id_ncc" value="<?php echo $id; ?>">
                                            <button type="submit" class="admin-btn <?php echo $active ? 'admin-btn-danger' : 'admin-btn-outline'; ?> admin-btn-sm">
                                                <?php echo $active ? 'Ngừng' : 'Mở lại'; ?>
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

