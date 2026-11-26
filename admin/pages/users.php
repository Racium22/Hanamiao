<?php
declare(strict_types=1);

/**
 * FILE: admin/pages/users.php
 * Quản lý tài khoản NguoiDung (chỉ dành cho Quyen = 'admin').
 */

$users = [];
$editUser = null;
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

if ($pdo instanceof PDO) {
    try {
        $users = $pdo->query(
            'SELECT ID_ND, TenND, TenDangNhap, Quyen, TrangThai FROM NguoiDung ORDER BY ID_ND ASC'
        )->fetchAll();

        if ($editId > 0) {
            $stmt = $pdo->prepare('SELECT ID_ND, TenND, TenDangNhap, Quyen, TrangThai FROM NguoiDung WHERE ID_ND = ?');
            $stmt->execute([$editId]);
            $editUser = $stmt->fetch() ?: null;
        }
    } catch (PDOException $exception) {
        error_log('[Hanamiao Admin Users] ' . $exception->getMessage());
        $dbError = 'Không thể tải danh sách người dùng.';
    }
}
?>

<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Quản lý người dùng</h1>
        <p class="admin-page-subtitle">Tạo tài khoản nhân viên, phân quyền và khóa/mở khóa truy cập khu vực quản trị.</p>
    </div>
    <?php if ($editUser !== null): ?>
        <a class="admin-btn admin-btn-outline" href="index.php?page=users">Thêm tài khoản mới</a>
    <?php endif; ?>
</div>

<div class="admin-split-grid">
    <section class="admin-card">
        <div class="admin-card-head">
            <h2 class="admin-card-title"><?php echo $editUser ? 'Cập nhật tài khoản #' . (int)$editUser['ID_ND'] : 'Thêm tài khoản mới'; ?></h2>
        </div>

        <form action="actions/user_save.php" method="post" class="admin-form">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id_nd" value="<?php echo (int)($editUser['ID_ND'] ?? 0); ?>">

            <div class="admin-field">
                <label for="ten_nd" class="admin-label">Họ và tên <span class="required-mark">*</span></label>
                <input id="ten_nd" name="ten_nd" type="text" class="admin-input" maxlength="120" value="<?php echo h($editUser['TenND'] ?? ''); ?>" required>
            </div>

            <div class="admin-field">
                <label for="ten_dang_nhap" class="admin-label">Tên đăng nhập <span class="required-mark">*</span></label>
                <input id="ten_dang_nhap" name="ten_dang_nhap" type="text" class="admin-input" maxlength="60" value="<?php echo h($editUser['TenDangNhap'] ?? ''); ?>" required>
            </div>

            <div class="admin-field">
                <label for="mat_khau" class="admin-label">
                    Mật khẩu <?php echo $editUser ? '(để trống nếu giữ nguyên)' : '<span class="required-mark">*</span>'; ?>
                </label>
                <input id="mat_khau" name="mat_khau" type="password" class="admin-input" minlength="6" <?php echo $editUser ? '' : 'required'; ?>>
            </div>

            <div class="admin-form-row">
                <div class="admin-field">
                    <label for="quyen" class="admin-label">Quyền hạn <span class="required-mark">*</span></label>
                    <?php $selRole = (string)($editUser['Quyen'] ?? 'nhanvien'); ?>
                    <select id="quyen" name="quyen" class="admin-input">
                        <option value="nhanvien"<?php echo $selRole === 'nhanvien' ? ' selected' : ''; ?>>Nhân viên</option>
                        <option value="admin"<?php echo $selRole === 'admin' ? ' selected' : ''; ?>>Quản trị viên</option>
                    </select>
                </div>

                <div class="admin-field">
                    <label for="trang_thai" class="admin-label">Trạng thái</label>
                    <?php $selStatus = (int)($editUser['TrangThai'] ?? 1); ?>
                    <select id="trang_thai" name="trang_thai" class="admin-input">
                        <option value="1"<?php echo $selStatus === 1 ? ' selected' : ''; ?>>Đang hoạt động</option>
                        <option value="0"<?php echo $selStatus === 0 ? ' selected' : ''; ?>>Đã khóa</option>
                    </select>
                </div>
            </div>

            <div class="admin-form-actions">
                <?php if ($editUser): ?>
                    <a class="admin-btn admin-btn-outline" href="index.php?page=users">Hủy</a>
                <?php endif; ?>
                <button type="submit" class="admin-btn admin-btn-primary">
                    <?php echo $editUser ? 'Lưu thay đổi' : 'Tạo tài khoản'; ?>
                </button>
            </div>
        </form>
    </section>

    <section class="admin-card">
        <div class="admin-card-head">
            <h2 class="admin-card-title">Danh sách tài khoản (<?php echo count($users); ?>)</h2>
        </div>

        <?php if (count($users) === 0): ?>
            <div class="admin-empty-inline"><p class="admin-muted">Chưa có tài khoản nào.</p></div>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Người dùng</th>
                            <th>Quyền</th>
                            <th>Trạng thái</th>
                            <th class="text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <?php
                            $uid = (int)$u['ID_ND'];
                            $isSelf = $uid === (int)$currentAdmin['ID_ND'];
                            $active = (int)$u['TrangThai'] === 1;
                            ?>
                            <tr>
                                <td class="tabular-nums">#<?php echo $uid; ?></td>
                                <td>
                                    <div class="cell-primary"><?php echo h($u['TenND']); ?></div>
                                    <div class="cell-sub"><?php echo h($u['TenDangNhap']); ?></div>
                                </td>
                                <td>
                                    <span class="admin-badge <?php echo $u['Quyen'] === 'admin' ? 'badge-primary' : 'badge-neutral'; ?>">
                                        <?php echo $u['Quyen'] === 'admin' ? 'Quản trị viên' : 'Nhân viên'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="admin-badge <?php echo $active ? 'badge-success' : 'badge-error'; ?>">
                                        <?php echo $active ? 'Hoạt động' : 'Đã khóa'; ?>
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="admin-row-actions">
                                        <a class="admin-btn admin-btn-outline admin-btn-sm" href="index.php?page=users&amp;edit=<?php echo $uid; ?>">Sửa</a>
                                        <?php if (!$isSelf): ?>
                                            <form action="actions/user_save.php" method="post" data-confirm="Xác nhận thay đổi trạng thái tài khoản này?">
                                                <?php echo csrfInput(); ?>
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="id_nd" value="<?php echo $uid; ?>">
                                                <button type="submit" class="admin-btn <?php echo $active ? 'admin-btn-danger' : 'admin-btn-outline'; ?> admin-btn-sm">
                                                    <?php echo $active ? 'Khóa' : 'Mở khóa'; ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
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

