<?php
declare(strict_types=1);

/**
 * FILE: admin/pages/flowers.php
 * Quản lý Kho hoa tươi nguyên liệu (Hoa) kèm tải ảnh thật vào images/hoa/.
 */

$flowers = [];
$flowerTypes = [];
$editFlower = null;
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

if ($pdo instanceof PDO) {
    try {
        $flowerTypes = $pdo->query('SELECT ID_LH, TenLH FROM LoaiHoa WHERE TrangThai = 1 ORDER BY TenLH ASC')->fetchAll();
        $flowers = $pdo->query(
            'SELECT h.ID_H, h.TenHoa, h.ID_LH, h.SoLuongTon, h.HinhAnh, h.TrangThai, lh.TenLH
             FROM Hoa h
             INNER JOIN LoaiHoa lh ON lh.ID_LH = h.ID_LH
             ORDER BY h.SoLuongTon ASC, h.ID_H DESC'
        )->fetchAll();

        if ($editId > 0) {
            $stmt = $pdo->prepare('SELECT ID_H, TenHoa, ID_LH, SoLuongTon, HinhAnh, TrangThai FROM Hoa WHERE ID_H = ?');
            $stmt->execute([$editId]);
            $editFlower = $stmt->fetch() ?: null;
        }
    } catch (PDOException $exception) {
        error_log('[Hanamiao Admin Flowers] ' . $exception->getMessage());
        $dbError = 'Không thể tải danh sách kho hoa tươi.';
    }
}
?>

<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Kho hoa tươi nguyên liệu</h1>
        <p class="admin-page-subtitle">Theo dõi số lượng bông tồn kho; nhập thêm hoặc ghi hao hụt ở phân hệ tương ứng để đồng bộ tồn.</p>
    </div>
    <div class="admin-page-actions">
        <a class="admin-btn admin-btn-outline" href="index.php?page=imports">Nhập thêm hoa</a>
        <a class="admin-btn admin-btn-outline" href="index.php?page=wastage">Ghi hao hụt</a>
    </div>
</div>

<div class="admin-split-grid">
    <section class="admin-card">
        <div class="admin-card-head">
            <h2 class="admin-card-title"><?php echo $editFlower ? 'Cập nhật hoa #' . (int)$editFlower['ID_H'] : 'Thêm loại hoa vào kho'; ?></h2>
        </div>

        <form action="actions/flower_save.php" method="post" enctype="multipart/form-data" class="admin-form">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id_h" value="<?php echo (int)($editFlower['ID_H'] ?? 0); ?>">

            <div class="admin-field">
                <label for="ten_hoa" class="admin-label">Tên hoa nguyên liệu <span class="required-mark">*</span></label>
                <input id="ten_hoa" name="ten_hoa" type="text" class="admin-input" maxlength="150" value="<?php echo h($editFlower['TenHoa'] ?? ''); ?>" required>
            </div>

            <div class="admin-field">
                <label for="id_lh" class="admin-label">Nhóm loại hoa <span class="required-mark">*</span></label>
                <?php $selLh = (int)($editFlower['ID_LH'] ?? 0); ?>
                <select id="id_lh" name="id_lh" class="admin-input" required>
                    <option value="">-- Chọn loại hoa --</option>
                    <?php foreach ($flowerTypes as $lh): ?>
                        <?php $lhid = (int)$lh['ID_LH']; ?>
                        <option value="<?php echo $lhid; ?>"<?php echo $selLh === $lhid ? ' selected' : ''; ?>>
                            <?php echo h($lh['TenLH']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="admin-form-row">
                <div class="admin-field">
                    <label for="so_luong_ton" class="admin-label">
                        <?php echo $editFlower ? 'Tồn kho hiện tại (bông)' : 'Tồn kho ban đầu (bông)'; ?>
                    </label>
                    <input
                        id="so_luong_ton"
                        name="so_luong_ton"
                        type="number"
                        min="0"
                        class="admin-input tabular-nums"
                        value="<?php echo (int)($editFlower['SoLuongTon'] ?? 0); ?>"
                        <?php echo $editFlower ? 'readonly' : ''; ?>
                    >
                    <?php if ($editFlower): ?>
                        <span class="cell-sub">Tồn kho thay đổi tự động qua Phiếu nhập, Hao hụt &amp; Đơn hàng.</span>
                    <?php endif; ?>
                </div>

                <div class="admin-field">
                    <label for="trang_thai" class="admin-label">Trạng thái</label>
                    <?php $st = (int)($editFlower['TrangThai'] ?? 1); ?>
                    <select id="trang_thai" name="trang_thai" class="admin-input">
                        <option value="1"<?php echo $st === 1 ? ' selected' : ''; ?>>Đang dùng</option>
                        <option value="0"<?php echo $st === 0 ? ' selected' : ''; ?>>Ngừng dùng</option>
                    </select>
                </div>
            </div>

            <div class="admin-field">
                <label for="hinh_anh" class="admin-label">Ảnh hoa thật (JPG, PNG, WebP &le; 3MB)</label>
                <?php if ($editFlower && !empty($editFlower['HinhAnh'])): ?>
                    <div class="admin-thumb-preview">
                        <img src="<?php echo h(getImageUrl($editFlower['HinhAnh'], '../')); ?>" alt="<?php echo h($editFlower['TenHoa']); ?>">
                        <span class="cell-sub"><?php echo h($editFlower['HinhAnh']); ?></span>
                    </div>
                <?php endif; ?>
                <input id="hinh_anh" name="hinh_anh" type="file" accept="image/jpeg,image/png,image/webp" class="admin-input admin-file-input">
            </div>

            <div class="admin-form-actions">
                <?php if ($editFlower): ?>
                    <a class="admin-btn admin-btn-outline" href="index.php?page=flowers">Hủy</a>
                <?php endif; ?>
                <button type="submit" class="admin-btn admin-btn-primary">
                    <?php echo $editFlower ? 'Lưu thay đổi' : 'Thêm vào kho'; ?>
                </button>
            </div>
        </form>
    </section>

    <section class="admin-card">
        <div class="admin-card-head">
            <h2 class="admin-card-title">Danh mục hoa trong kho (<?php echo count($flowers); ?>)</h2>
        </div>

        <?php if (count($flowers) === 0): ?>
            <div class="admin-empty-inline"><p class="admin-muted">Chưa có hoa nguyên liệu nào trong kho.</p></div>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Hoa nguyên liệu</th>
                            <th>Loại hoa</th>
                            <th class="text-right">Tồn kho</th>
                            <th>Trạng thái</th>
                            <th class="text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($flowers as $hoa): ?>
                            <?php
                            $id = (int)$hoa['ID_H'];
                            $ton = (int)$hoa['SoLuongTon'];
                            $active = (int)$hoa['TrangThai'] === 1;
                            $tonClass = $ton === 0 ? 'badge-error' : ($ton <= 20 ? 'badge-warning' : 'badge-success');
                            ?>
                            <tr>
                                <td>
                                    <div class="admin-item-cell">
                                        <img class="admin-thumb-sm" src="<?php echo h(getImageUrl($hoa['HinhAnh'], '../')); ?>" alt="<?php echo h($hoa['TenHoa']); ?>">
                                        <div>
                                            <div class="cell-primary"><?php echo h($hoa['TenHoa']); ?></div>
                                            <div class="cell-sub tabular-nums">Mã #H<?php echo $id; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo h($hoa['TenLH']); ?></td>
                                <td class="text-right">
                                    <span class="admin-badge <?php echo $tonClass; ?> tabular-nums">
                                        <?php echo $ton; ?> bông
                                    </span>
                                </td>
                                <td>
                                    <span class="admin-badge <?php echo $active ? 'badge-success' : 'badge-neutral'; ?>">
                                        <?php echo $active ? 'Đang dùng' : 'Ngừng'; ?>
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="admin-row-actions">
                                        <a class="admin-btn admin-btn-outline admin-btn-sm" href="index.php?page=flowers&amp;edit=<?php echo $id; ?>">Sửa</a>
                                        <form action="actions/flower_save.php" method="post">
                                            <?php echo csrfInput(); ?>
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id_h" value="<?php echo $id; ?>">
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

