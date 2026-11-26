<?php
declare(strict_types=1);

/**
 * FILE: admin/pages/flower_types.php
 * Quản lý Loại hoa nguyên liệu (LoaiHoa).
 */

$flowerTypes = [];
$editType = null;
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

if ($pdo instanceof PDO) {
    try {
        $flowerTypes = $pdo->query(
            'SELECT lh.ID_LH, lh.TenLH, lh.TrangThai, COUNT(h.ID_H) AS SoLoaiHoa
             FROM LoaiHoa lh
             LEFT JOIN Hoa h ON h.ID_LH = lh.ID_LH
             GROUP BY lh.ID_LH
             ORDER BY lh.ID_LH DESC'
        )->fetchAll();

        if ($editId > 0) {
            $stmt = $pdo->prepare('SELECT ID_LH, TenLH, TrangThai FROM LoaiHoa WHERE ID_LH = ?');
            $stmt->execute([$editId]);
            $editType = $stmt->fetch() ?: null;
        }
    } catch (PDOException $exception) {
        error_log('[Hanamiao Admin Flower Types] ' . $exception->getMessage());
        $dbError = 'Không thể tải danh sách loại hoa.';
    }
}
?>

<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Loại hoa nguyên liệu</h1>
        <p class="admin-page-subtitle">Phân nhóm các dòng hoa tươi nhập kho để dễ theo dõi tồn kho và định mức bó.</p>
    </div>
    <?php if ($editType): ?>
        <a class="admin-btn admin-btn-outline" href="index.php?page=flower_types">Thêm loại hoa</a>
    <?php endif; ?>
</div>

<div class="admin-split-grid">
    <section class="admin-card">
        <div class="admin-card-head">
            <h2 class="admin-card-title"><?php echo $editType ? 'Cập nhật loại hoa #' . (int)$editType['ID_LH'] : 'Thêm loại hoa mới'; ?></h2>
        </div>

        <form action="actions/flower_type_save.php" method="post" class="admin-form">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id_lh" value="<?php echo (int)($editType['ID_LH'] ?? 0); ?>">

            <div class="admin-field">
                <label for="ten_lh" class="admin-label">Tên loại hoa <span class="required-mark">*</span></label>
                <input id="ten_lh" name="ten_lh" type="text" class="admin-input" maxlength="100" value="<?php echo h($editType['TenLH'] ?? ''); ?>" required>
            </div>

            <div class="admin-field">
                <label for="trang_thai" class="admin-label">Trạng thái</label>
                <?php $st = (int)($editType['TrangThai'] ?? 1); ?>
                <select id="trang_thai" name="trang_thai" class="admin-input">
                    <option value="1"<?php echo $st === 1 ? ' selected' : ''; ?>>Đang sử dụng</option>
                    <option value="0"<?php echo $st === 0 ? ' selected' : ''; ?>>Tạm ẩn</option>
                </select>
            </div>

            <div class="admin-form-actions">
                <?php if ($editType): ?>
                    <a class="admin-btn admin-btn-outline" href="index.php?page=flower_types">Hủy</a>
                <?php endif; ?>
                <button type="submit" class="admin-btn admin-btn-primary">
                    <?php echo $editType ? 'Lưu thay đổi' : 'Thêm loại hoa'; ?>
                </button>
            </div>
        </form>
    </section>

    <section class="admin-card">
        <div class="admin-card-head">
            <h2 class="admin-card-title">Danh sách loại hoa (<?php echo count($flowerTypes); ?>)</h2>
        </div>

        <?php if (count($flowerTypes) === 0): ?>
            <div class="admin-empty-inline"><p class="admin-muted">Chưa có loại hoa nào.</p></div>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên loại hoa</th>
                            <th class="text-right">Số dòng hoa</th>
                            <th>Trạng thái</th>
                            <th class="text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($flowerTypes as $lh): ?>
                            <?php $id = (int)$lh['ID_LH']; $active = (int)$lh['TrangThai'] === 1; ?>
                            <tr>
                                <td class="tabular-nums">#<?php echo $id; ?></td>
                                <td class="font-medium"><?php echo h($lh['TenLH']); ?></td>
                                <td class="text-right tabular-nums"><?php echo (int)$lh['SoLoaiHoa']; ?></td>
                                <td>
                                    <span class="admin-badge <?php echo $active ? 'badge-success' : 'badge-neutral'; ?>">
                                        <?php echo $active ? 'Hoạt động' : 'Tạm ẩn'; ?>
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="admin-row-actions">
                                        <a class="admin-btn admin-btn-outline admin-btn-sm" href="index.php?page=flower_types&amp;edit=<?php echo $id; ?>">Sửa</a>
                                        <form action="actions/flower_type_save.php" method="post">
                                            <?php echo csrfInput(); ?>
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id_lh" value="<?php echo $id; ?>">
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

