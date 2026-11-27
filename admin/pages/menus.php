<?php
declare(strict_types=1);

/**
 * FILE: admin/pages/menus.php
 * Quản lý Menu đa cấp (kế thừa Lab 08 / TX04 — chỉ dành cho quyền admin).
 */

$menus = [];
$editMenu = null;
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

if ($pdo instanceof PDO) {
    try {
        $menus = getAllMenus($pdo);
        if ($editId > 0) {
            $stmt = $pdo->prepare('SELECT ID, ID_Cha, TenMenu, MoTa, url FROM Menu WHERE ID = ?');
            $stmt->execute([$editId]);
            $editMenu = $stmt->fetch() ?: null;
        }
    } catch (PDOException $exception) {
        error_log('[Hanamiao Admin Menus] ' . $exception->getMessage());
        $dbError = 'Không thể tải danh sách menu.';
    }
}

$menuNamesById = [0 => 'Menu gốc'];
foreach ($menus as $m) {
    $menuNamesById[(int)$m['ID']] = (string)$m['TenMenu'];
}
?>

<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Quản lý menu đa cấp</h1>
        <p class="admin-page-subtitle">Cấu hình cây điều hướng hiển thị động trên Storefront (hỗ trợ đa cấp cha – con theo chuẩn Lab 08).</p>
    </div>
    <?php if ($editMenu): ?>
        <a class="admin-btn admin-btn-outline" href="index.php?page=menus">Thêm menu mới</a>
    <?php endif; ?>
</div>

<div class="admin-split-grid">
    <div class="admin-stack-col">
        <section class="admin-card">
            <div class="admin-card-head">
                <h2 class="admin-card-title"><?php echo $editMenu ? 'Cập nhật menu #' . (int)$editMenu['ID'] : 'Thêm danh mục menu'; ?></h2>
            </div>

            <form action="actions/menu_save.php" method="post" class="admin-form">
                <?php echo csrfInput(); ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?php echo (int)($editMenu['ID'] ?? 0); ?>">

                <div class="admin-field">
                    <label for="ten_menu" class="admin-label">Tên danh mục <span class="required-mark">*</span></label>
                    <input id="ten_menu" name="ten_menu" type="text" class="admin-input" maxlength="150" value="<?php echo h($editMenu['TenMenu'] ?? ''); ?>" required>
                </div>

                <div class="admin-field">
                    <label for="id_cha" class="admin-label">Danh mục cha <span class="required-mark">*</span></label>
                    <select id="id_cha" name="id_cha" class="admin-input" required>
                        <option value="0">-- Menu gốc --</option>
                        <?php inTuyChonCha($menus, 0, 0, (int)($editMenu['ID_Cha'] ?? 0), (int)($editMenu['ID'] ?? 0)); ?>
                    </select>
                </div>

                <div class="admin-field">
                    <label for="mo_ta" class="admin-label">Mô tả</label>
                    <input id="mo_ta" name="mo_ta" type="text" class="admin-input" maxlength="255" value="<?php echo h($editMenu['MoTa'] ?? ''); ?>">
                </div>

                <div class="admin-field">
                    <label for="url" class="admin-label">Đường dẫn nội bộ (tùy chọn)</label>
                    <input
                        id="url"
                        name="url"
                        type="text"
                        class="admin-input"
                        maxlength="255"
                        placeholder="index.php?page=products&lsp=1"
                        value="<?php echo h($editMenu['url'] ?? ''); ?>"
                    >
                    <span class="cell-sub">Chỉ chấp nhận đường dẫn nội bộ dạng <code>index.php?page=...</code> hoặc để trống.</span>
                </div>

                <div class="admin-form-actions">
                    <?php if ($editMenu): ?>
                        <a class="admin-btn admin-btn-outline" href="index.php?page=menus">Hủy</a>
                    <?php endif; ?>
                    <button type="submit" class="admin-btn admin-btn-primary">
                        <?php echo $editMenu ? 'Lưu thay đổi' : 'Thêm danh mục'; ?>
                    </button>
                </div>
            </form>
        </section>

        <section class="admin-card">
            <div class="admin-card-head">
                <h2 class="admin-card-title">Xem trước cây menu</h2>
            </div>
            <?php if (count($menus) === 0): ?>
                <p class="admin-muted">Chưa có mục menu nào.</p>
            <?php else: ?>
                <div class="admin-menu-preview">
                    <?php inMenu($menus, 0, ''); ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <section class="admin-card">
        <div class="admin-card-head">
            <h2 class="admin-card-title">Danh sách menu (<?php echo count($menus); ?>)</h2>
        </div>

        <?php if (count($menus) === 0): ?>
            <div class="admin-empty-inline"><p class="admin-muted">Chưa có dữ liệu menu.</p></div>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên menu</th>
                            <th>Menu cha</th>
                            <th>URL</th>
                            <th class="text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($menus as $m): ?>
                            <?php $mid = (int)$m['ID']; $pid = (int)$m['ID_Cha']; ?>
                            <tr>
                                <td class="tabular-nums">#<?php echo $mid; ?></td>
                                <td>
                                    <div class="cell-primary"><?php echo h($m['TenMenu']); ?></div>
                                    <?php if (!empty($m['MoTa'])): ?>
                                        <div class="cell-sub"><?php echo h($m['MoTa']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="admin-badge <?php echo $pid === 0 ? 'badge-primary' : 'badge-neutral'; ?>">
                                        <?php echo h($menuNamesById[$pid] ?? ('#' . $pid)); ?>
                                    </span>
                                </td>
                                <td class="cell-sub"><?php echo !empty($m['url']) ? h($m['url']) : '—'; ?></td>
                                <td class="text-right">
                                    <div class="admin-row-actions">
                                        <a class="admin-btn admin-btn-outline admin-btn-sm" href="index.php?page=menus&amp;edit=<?php echo $mid; ?>">Sửa</a>
                                        <form action="actions/menu_save.php" method="post" data-confirm="Xóa danh mục menu này?">
                                            <?php echo csrfInput(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $mid; ?>">
                                            <button type="submit" class="admin-btn admin-btn-danger admin-btn-sm">Xóa</button>
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

