<?php
declare(strict_types=1);

/**
 * FILE: admin/actions/user_save.php
 * Xử lý thêm/sửa/khóa mở tài khoản NguoiDung (chỉ quyền admin).
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/helpers.php';

requireRole(['admin'], '../index.php?page=dashboard');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php?page=users');
    exit;
}

if (!verifyCsrfToken(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
    setFlash('error', 'Phiên biểu mẫu không hợp lệ.');
    header('Location: ../index.php?page=users');
    exit;
}

$currentAdmin = getCurrentAdmin();
$action = trim((string)($_POST['action'] ?? 'save'));
$idNd = filter_input(INPUT_POST, 'id_nd', FILTER_VALIDATE_INT) ?: 0;

try {
    $pdo = getConnection();

    if ($action === 'toggle') {
        if ($idNd <= 0) {
            setFlash('error', 'Mã người dùng không hợp lệ.');
        } elseif ($idNd === (int)$currentAdmin['ID_ND']) {
            setFlash('error', 'Không thể tự khóa tài khoản đang đăng nhập.');
        } else {
            $stmt = $pdo->prepare('UPDATE NguoiDung SET TrangThai = IF(TrangThai = 1, 0, 1) WHERE ID_ND = ?');
            $stmt->execute([$idNd]);
            setFlash('success', 'Đã cập nhật trạng thái tài khoản.');
        }
        header('Location: ../index.php?page=users');
        exit;
    }

    $tenNd = trim((string)($_POST['ten_nd'] ?? ''));
    $tenDangNhap = trim((string)($_POST['ten_dang_nhap'] ?? ''));
    $matKhau = (string)($_POST['mat_khau'] ?? '');
    $quyen = trim((string)($_POST['quyen'] ?? 'nhanvien'));
    $trangThai = ((int)($_POST['trang_thai'] ?? 1)) === 1 ? 1 : 0;

    if ($tenNd === '' || $tenDangNhap === '') {
        setFlash('error', 'Vui lòng nhập đầy đủ Họ tên và Tên đăng nhập.');
        header('Location: ../index.php?page=users' . ($idNd > 0 ? '&edit=' . $idNd : ''));
        exit;
    }

    if (!in_array($quyen, ['admin', 'nhanvien'], true)) {
        $quyen = 'nhanvien';
    }

    if ($idNd === (int)$currentAdmin['ID_ND'] && ($trangThai === 0 || $quyen !== 'admin')) {
        setFlash('error', 'Không thể tự khóa hoặc hạ quyền tài khoản đang đăng nhập.');
        header('Location: ../index.php?page=users&edit=' . $idNd);
        exit;
    }

    // Kiểm tra trùng tên đăng nhập
    $dupStmt = $pdo->prepare('SELECT COUNT(*) FROM NguoiDung WHERE TenDangNhap = ? AND ID_ND <> ?');
    $dupStmt->execute([$tenDangNhap, $idNd]);
    if ((int)$dupStmt->fetchColumn() > 0) {
        setFlash('error', 'Tên đăng nhập "' . $tenDangNhap . '" đã tồn tại.');
        header('Location: ../index.php?page=users' . ($idNd > 0 ? '&edit=' . $idNd : ''));
        exit;
    }

    if ($idNd === 0) {
        if (mb_strlen($matKhau) < 6) {
            setFlash('error', 'Mật khẩu tạo mới phải có ít nhất 6 ký tự.');
            header('Location: ../index.php?page=users');
            exit;
        }
        $hash = password_hash($matKhau, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            'INSERT INTO NguoiDung (TenND, TenDangNhap, MatKhau, Quyen, TrangThai) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$tenNd, $tenDangNhap, $hash, $quyen, $trangThai]);
        setFlash('success', 'Đã tạo tài khoản "' . $tenDangNhap . '" thành công.');
    } else {
        if ($matKhau !== '') {
            if (mb_strlen($matKhau) < 6) {
                setFlash('error', 'Mật khẩu mới phải có ít nhất 6 ký tự.');
                header('Location: ../index.php?page=users&edit=' . $idNd);
                exit;
            }
            $hash = password_hash($matKhau, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                'UPDATE NguoiDung SET TenND = ?, TenDangNhap = ?, MatKhau = ?, Quyen = ?, TrangThai = ? WHERE ID_ND = ?'
            );
            $stmt->execute([$tenNd, $tenDangNhap, $hash, $quyen, $trangThai, $idNd]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE NguoiDung SET TenND = ?, TenDangNhap = ?, Quyen = ?, TrangThai = ? WHERE ID_ND = ?'
            );
            $stmt->execute([$tenNd, $tenDangNhap, $quyen, $trangThai, $idNd]);
        }
        setFlash('success', 'Đã cập nhật thông tin tài khoản.');
    }
} catch (PDOException $exception) {
    error_log('[Hanamiao User Save] ' . $exception->getMessage());
    setFlash('error', 'Không thể lưu thông tin người dùng.');
}

header('Location: ../index.php?page=users');
exit;

