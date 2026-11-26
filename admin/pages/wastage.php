<?php
declare(strict_types=1);

/**
 * FILE: admin/pages/wastage.php
 * Ghi nhận Hoa hao hụt / hỏng héo (HaoHut + ChiTietHaoHut), trừ tồn kho có kiểm tra không âm.
 */

$flowers = [];
$wastages = [];
$viewWastage = null;
$viewDetails = [];
$viewId = isset($_GET['view']) ? (int)$_GET['view'] : 0;

if ($pdo instanceof PDO) {
    try {
        $flowers = $pdo->query('SELECT ID_H, TenHoa, SoLuongTon FROM Hoa WHERE TrangThai = 1 ORDER BY TenHoa ASC')->fetchAll();

        $wastages = $pdo->query(
            'SELECT hh.ID_HH, hh.NgayGhiNhan, hh.LyDo, nd.TenND,
                    COALESCE(SUM(ct.SoLuong), 0) AS TongBongHong
             FROM HaoHut hh
             INNER JOIN NguoiDung nd ON nd.ID_ND = hh.ID_ND
             LEFT JOIN ChiTietHaoHut ct ON ct.ID_HH = hh.ID_HH
             GROUP BY hh.ID_HH
             ORDER BY hh.NgayGhiNhan DESC, hh.ID_HH DESC
             LIMIT 30'
        )->fetchAll();

        if ($viewId > 0) {
            $stmt = $pdo->prepare(
                'SELECT hh.ID_HH, hh.NgayGhiNhan, hh.LyDo, nd.TenND
                 FROM HaoHut hh
                 INNER JOIN NguoiDung nd ON nd.ID_ND = hh.ID_ND
                 WHERE hh.ID_HH = ?'
            );
            $stmt->execute([$viewId]);
            $viewWastage = $stmt->fetch() ?: null;

            if ($viewWastage) {
                $dStmt = $pdo->prepare(
                    'SELECT ct.SoLuong, h.TenHoa
                     FROM ChiTietHaoHut ct
                     INNER JOIN Hoa h ON h.ID_H = ct.ID_H
                     WHERE ct.ID_HH = ?
                     ORDER BY ct.ID_CTHH ASC'
                );
                $dStmt->execute([$viewId]);
                $viewDetails = $dStmt->fetchAll();
            }
        }
    } catch (PDOException $exception) {
        error_log('[Hanamiao Admin Wastage] ' . $exception->getMessage());
        $dbError = 'Không thể tải danh sách phiếu hao hụt.';
    }
}
?>

<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Ghi nhận hao hụt hoa</h1>
        <p class="admin-page-subtitle">Ghi nhận số bông héo, dập cánh hoặc quá hạn; tự động trừ tồn kho và chặn số âm.</p>
    </div>
    <?php if ($viewWastage): ?>
        <a class="admin-btn admin-btn-outline" href="index.php?page=wastage">Đóng chi tiết</a>
    <?php endif; ?>
</div>

<?php if ($viewWastage): ?>
    <section class="admin-card admin-section-gap">
        <div class="admin-card-head">
            <div>
                <h2 class="admin-card-title tabular-nums">Chi tiết Hao hụt #HH<?php echo str_pad((string)$viewWastage['ID_HH'], 4, '0', STR_PAD_LEFT); ?></h2>
                <p class="cell-sub">
                    Người ghi nhận: <?php echo h($viewWastage['TenND']); ?>
                    &middot; Thời gian: <?php echo h(formatDate((string)$viewWastage['NgayGhiNhan'])); ?>
                    &middot; Lý do: <strong><?php echo h($viewWastage['LyDo']); ?></strong>
                </p>
            </div>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Hoa nguyên liệu</th>
                        <th class="text-right">Số lượng hao hụt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($viewDetails as $d): ?>
                        <tr>
                            <td class="font-medium"><?php echo h($d['TenHoa']); ?></td>
                            <td class="text-right tabular-nums"><span class="admin-badge badge-error">-<?php echo (int)$d['SoLuong']; ?> bông</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<section class="admin-card admin-section-gap" data-dynamic-rows>
    <div class="admin-card-head">
        <h2 class="admin-card-title">Lập phiếu hao hụt mới</h2>
        <button type="button" class="admin-btn admin-btn-outline admin-btn-sm" data-add-row>
            <i data-lucide="plus" class="admin-btn-icon" aria-hidden="true"></i>
            <span>Thêm dòng hoa</span>
        </button>
    </div>

    <form action="actions/wastage_save.php" method="post" class="admin-form">
        <?php echo csrfInput(); ?>

        <div class="admin-field">
            <label for="ly_do" class="admin-label">Lý do hao hụt <span class="required-mark">*</span></label>
            <input id="ly_do" name="ly_do" type="text" class="admin-input" maxlength="255" placeholder="Ví dụ: Hoa héo cuối ngày, gãy cành khi bảo quản..." required>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Hoa nguyên liệu <span class="required-mark">*</span></th>
                        <th style="width: 180px;">Số lượng hỏng (bông) <span class="required-mark">*</span></th>
                        <th style="width: 72px;"></th>
                    </tr>
                </thead>
                <tbody data-rows-body>
                    <tr>
                        <td>
                            <select name="id_h[]" class="admin-input" required>
                                <option value="">-- Chọn hoa --</option>
                                <?php foreach ($flowers as $hItem): ?>
                                    <option value="<?php echo (int)$hItem['ID_H']; ?>">
                                        <?php echo h($hItem['TenHoa']); ?> (còn: <?php echo (int)$hItem['SoLuongTon']; ?> bông)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input name="so_luong[]" type="number" min="1" value="1" class="admin-input tabular-nums" required></td>
                        <td class="text-right">
                            <button type="button" class="admin-btn admin-btn-danger admin-btn-sm" data-remove-row>Xóa</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <template data-row-template>
            <tr>
                <td>
                    <select name="id_h[]" class="admin-input" required>
                        <option value="">-- Chọn hoa --</option>
                        <?php foreach ($flowers as $hItem): ?>
                            <option value="<?php echo (int)$hItem['ID_H']; ?>">
                                <?php echo h($hItem['TenHoa']); ?> (còn: <?php echo (int)$hItem['SoLuongTon']; ?> bông)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input name="so_luong[]" type="number" min="1" value="1" class="admin-input tabular-nums" required></td>
                <td class="text-right">
                    <button type="button" class="admin-btn admin-btn-danger admin-btn-sm" data-remove-row>Xóa</button>
                </td>
            </tr>
        </template>

        <div class="admin-form-actions">
            <button type="submit" class="admin-btn admin-btn-primary">Ghi nhận hao hụt &amp; Trừ kho</button>
        </div>
    </form>
</section>

<section class="admin-card">
    <div class="admin-card-head">
        <h2 class="admin-card-title">Lịch sử hao hụt gần đây (<?php echo count($wastages); ?>)</h2>
    </div>
    <?php if (count($wastages) === 0): ?>
        <div class="admin-empty-inline"><p class="admin-muted">Chưa có phiếu hao hụt nào.</p></div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Mã phiếu</th>
                        <th>Ngày ghi nhận</th>
                        <th>Người ghi nhận</th>
                        <th>Lý do</th>
                        <th class="text-right">Tổng bông hỏng</th>
                        <th class="text-right">Chi tiết</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($wastages as $hh): ?>
                        <tr>
                            <td class="tabular-nums font-medium">#HH<?php echo str_pad((string)$hh['ID_HH'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td class="tabular-nums"><?php echo h(formatDate((string)$hh['NgayGhiNhan'])); ?></td>
                            <td><?php echo h($hh['TenND']); ?></td>
                            <td><?php echo h($hh['LyDo']); ?></td>
                            <td class="text-right tabular-nums"><span class="admin-badge badge-error">-<?php echo (int)$hh['TongBongHong']; ?></span></td>
                            <td class="text-right">
                                <a class="admin-btn admin-btn-outline admin-btn-sm" href="index.php?page=wastage&amp;view=<?php echo (int)$hh['ID_HH']; ?>">Xem</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

