<?php
declare(strict_types=1);

/**
 * FILE: admin/pages/imports.php
 * Quản lý Phiếu nhập kho hoa nguyên liệu (PhieuNhap + ChiTietPhieuNhap).
 */

$suppliers = [];
$flowers = [];
$imports = [];
$viewImport = null;
$viewDetails = [];
$viewId = isset($_GET['view']) ? (int)$_GET['view'] : 0;

if ($pdo instanceof PDO) {
    try {
        $suppliers = $pdo->query('SELECT ID_NCC, TenNCC FROM NhaCungCap WHERE TrangThai = 1 ORDER BY TenNCC ASC')->fetchAll();
        $flowers = $pdo->query('SELECT ID_H, TenHoa, SoLuongTon FROM Hoa WHERE TrangThai = 1 ORDER BY TenHoa ASC')->fetchAll();

        $imports = $pdo->query(
            'SELECT pn.ID_PN, pn.NgayLapPhieu, ncc.TenNCC, nd.TenND,
                    COUNT(ct.ID_CTPN) AS SoDong,
                    COALESCE(SUM(ct.SoLuong), 0) AS TongBong,
                    COALESCE(SUM(ct.ThanhTien), 0) AS TongTien
             FROM PhieuNhap pn
             INNER JOIN NhaCungCap ncc ON ncc.ID_NCC = pn.ID_NCC
             INNER JOIN NguoiDung nd ON nd.ID_ND = pn.ID_ND
             LEFT JOIN ChiTietPhieuNhap ct ON ct.ID_PN = pn.ID_PN
             GROUP BY pn.ID_PN
             ORDER BY pn.NgayLapPhieu DESC, pn.ID_PN DESC
             LIMIT 30'
        )->fetchAll();

        if ($viewId > 0) {
            $stmt = $pdo->prepare(
                'SELECT pn.ID_PN, pn.NgayLapPhieu, ncc.TenNCC, ncc.SDT, nd.TenND
                 FROM PhieuNhap pn
                 INNER JOIN NhaCungCap ncc ON ncc.ID_NCC = pn.ID_NCC
                 INNER JOIN NguoiDung nd ON nd.ID_ND = pn.ID_ND
                 WHERE pn.ID_PN = ?'
            );
            $stmt->execute([$viewId]);
            $viewImport = $stmt->fetch() ?: null;

            if ($viewImport) {
                $dStmt = $pdo->prepare(
                    'SELECT ct.ID_CTPN, ct.SoLuong, ct.GiaNhap, ct.ThanhTien, h.TenHoa
                     FROM ChiTietPhieuNhap ct
                     INNER JOIN Hoa h ON h.ID_H = ct.ID_H
                     WHERE ct.ID_PN = ?
                     ORDER BY ct.ID_CTPN ASC'
                );
                $dStmt->execute([$viewId]);
                $viewDetails = $dStmt->fetchAll();
            }
        }
    } catch (PDOException $exception) {
        error_log('[Hanamiao Admin Imports] ' . $exception->getMessage());
        $dbError = 'Không thể tải dữ liệu phiếu nhập kho.';
    }
}
?>

<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Phiếu nhập kho hoa</h1>
        <p class="admin-page-subtitle">Lập phiếu nhập từ nhà cung cấp; hệ thống tự động cộng vào tồn kho hoa trong Transaction.</p>
    </div>
    <?php if ($viewImport): ?>
        <a class="admin-btn admin-btn-outline" href="index.php?page=imports">Đóng chi tiết</a>
    <?php endif; ?>
</div>

<?php if ($viewImport): ?>
    <section class="admin-card admin-section-gap">
        <div class="admin-card-head">
            <div>
                <h2 class="admin-card-title tabular-nums">Chi tiết Phiếu nhập #PN<?php echo str_pad((string)$viewImport['ID_PN'], 4, '0', STR_PAD_LEFT); ?></h2>
                <p class="cell-sub">
                    NCC: <strong><?php echo h($viewImport['TenNCC']); ?></strong> (<?php echo h($viewImport['SDT']); ?>)
                    &middot; Người lập: <?php echo h($viewImport['TenND']); ?>
                    &middot; Ngày: <?php echo h(formatDate((string)$viewImport['NgayLapPhieu'])); ?>
                </p>
            </div>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Hoa nguyên liệu</th>
                        <th class="text-right">Số lượng nhập</th>
                        <th class="text-right">Giá nhập / bông</th>
                        <th class="text-right">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $sumView = 0.0; foreach ($viewDetails as $d): $sumView += (float)$d['ThanhTien']; ?>
                        <tr>
                            <td class="font-medium"><?php echo h($d['TenHoa']); ?></td>
                            <td class="text-right tabular-nums"><?php echo (int)$d['SoLuong']; ?> bông</td>
                            <td class="text-right tabular-nums"><?php echo h(formatMoney($d['GiaNhap'])); ?></td>
                            <td class="text-right tabular-nums font-medium"><?php echo h(formatMoney($d['ThanhTien'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-right">Tổng giá trị phiếu nhập</th>
                        <th class="text-right tabular-nums"><?php echo h(formatMoney($sumView)); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>
<?php endif; ?>

<section class="admin-card admin-section-gap" data-dynamic-rows>
    <div class="admin-card-head">
        <h2 class="admin-card-title">Lập phiếu nhập mới</h2>
        <button type="button" class="admin-btn admin-btn-outline admin-btn-sm" data-add-row>
            <i data-lucide="plus" class="admin-btn-icon" aria-hidden="true"></i>
            <span>Thêm dòng hoa</span>
        </button>
    </div>

    <form action="actions/import_save.php" method="post" class="admin-form">
        <?php echo csrfInput(); ?>

        <div class="admin-form-row">
            <div class="admin-field">
                <label for="id_ncc" class="admin-label">Nhà cung cấp <span class="required-mark">*</span></label>
                <select id="id_ncc" name="id_ncc" class="admin-input" required>
                    <option value="">-- Chọn nhà cung cấp --</option>
                    <?php foreach ($suppliers as $ncc): ?>
                        <option value="<?php echo (int)$ncc['ID_NCC']; ?>"><?php echo h($ncc['TenNCC']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-field">
                <label class="admin-label">Người lập phiếu</label>
                <input type="text" class="admin-input" value="<?php echo h($currentAdmin['TenND']); ?>" readonly>
            </div>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Hoa nguyên liệu <span class="required-mark">*</span></th>
                        <th style="width: 150px;">Số lượng (bông) <span class="required-mark">*</span></th>
                        <th style="width: 180px;">Giá nhập (VNĐ) <span class="required-mark">*</span></th>
                        <th class="text-right" style="width: 160px;">Thành tiền</th>
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
                                        <?php echo h($hItem['TenHoa']); ?> (tồn: <?php echo (int)$hItem['SoLuongTon']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input name="so_luong[]" type="number" min="1" value="10" class="admin-input tabular-nums" data-calc-qty required></td>
                        <td><input name="gia_nhap[]" type="number" min="0" step="500" value="10000" class="admin-input tabular-nums" data-calc-price required></td>
                        <td class="text-right tabular-nums font-medium" data-line-total>100.000 đ</td>
                        <td class="text-right">
                            <button type="button" class="admin-btn admin-btn-danger admin-btn-sm" data-remove-row aria-label="Xóa dòng">Xóa</button>
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
                                <?php echo h($hItem['TenHoa']); ?> (tồn: <?php echo (int)$hItem['SoLuongTon']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input name="so_luong[]" type="number" min="1" value="10" class="admin-input tabular-nums" data-calc-qty required></td>
                <td><input name="gia_nhap[]" type="number" min="0" step="500" value="10000" class="admin-input tabular-nums" data-calc-price required></td>
                <td class="text-right tabular-nums font-medium" data-line-total>100.000 đ</td>
                <td class="text-right">
                    <button type="button" class="admin-btn admin-btn-danger admin-btn-sm" data-remove-row aria-label="Xóa dòng">Xóa</button>
                </td>
            </tr>
        </template>

        <div class="admin-form-footer-bar">
            <div class="admin-total-preview">
                <span class="cell-sub">Tổng giá trị tạm tính:</span>
                <strong class="tabular-nums" data-grand-total>100.000 đ</strong>
            </div>
            <button type="submit" class="admin-btn admin-btn-primary">Lưu phiếu nhập &amp; Cộng kho</button>
        </div>
    </form>
</section>

<section class="admin-card">
    <div class="admin-card-head">
        <h2 class="admin-card-title">Lịch sử phiếu nhập gần đây (<?php echo count($imports); ?>)</h2>
    </div>
    <?php if (count($imports) === 0): ?>
        <div class="admin-empty-inline"><p class="admin-muted">Chưa có phiếu nhập nào.</p></div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Mã phiếu</th>
                        <th>Ngày lập</th>
                        <th>Nhà cung cấp</th>
                        <th>Người lập</th>
                        <th class="text-right">Tổng bông</th>
                        <th class="text-right">Tổng tiền</th>
                        <th class="text-right">Chi tiết</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($imports as $pn): ?>
                        <tr>
                            <td class="tabular-nums font-medium">#PN<?php echo str_pad((string)$pn['ID_PN'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td class="tabular-nums"><?php echo h(formatDate((string)$pn['NgayLapPhieu'])); ?></td>
                            <td><?php echo h($pn['TenNCC']); ?></td>
                            <td><?php echo h($pn['TenND']); ?></td>
                            <td class="text-right tabular-nums"><?php echo (int)$pn['TongBong']; ?></td>
                            <td class="text-right tabular-nums font-medium"><?php echo h(formatMoney($pn['TongTien'])); ?></td>
                            <td class="text-right">
                                <a class="admin-btn admin-btn-outline admin-btn-sm" href="index.php?page=imports&amp;view=<?php echo (int)$pn['ID_PN']; ?>">Xem</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

