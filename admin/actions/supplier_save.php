<?php
declare(strict_types=1);

/**
 * FILE: admin/actions/supplier_save.php
 * Xử lý thêm/sửa/đổi trạng thái NhaCungCap.
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/helpers.php';

requireAdminLogin('../login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php?page=suppliers');
    exit;
}

if (!verifyCsrfToken(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
    setFlash('error', 'Phiên biểu mẫu không hợp lệ.');
    header('Location: ../index.php?page=suppliers');
    exit;
}

$action = trim((string)($_POST['action'] ?? 'save'));
$idNcc = filter_input(INPUT_POST, 'id_ncc', FILTER_VALIDATE_INT) ?: 0;

try {
    $pdo = getConnection();

    if ($action === 'toggle') {
        if ($idNcc > 0) {
            $stmt = $pdo->prepare('UPDATE NhaCungCap SET TrangThai = IF(TrangThai = 1, 0, 1) WHERE ID_NCC = ?');
            $stmt->execute([$idNcc]);
            setFlash('success', 'Đã cập nhật trạng thái nhà cung cấp.');
        }
        header('Location: ../index.php?page=suppliers');
        exit;
    }

    $tenNcc = trim((string)($_POST['ten_ncc'] ?? ''));
    $sdt = preg_replace('/\s+/', '', trim((string)($_POST['sdt'] ?? '')));
    $diaChi = trim((string)($_POST['dia_chi'] ?? ''));
    $trangThai = ((int)($_POST['trang_thai'] ?? 1)) === 1 ? 1 : 0;

    if ($tenNcc === '' || $diaChi === '' || !preg_match('/^0\d{8,10}$/', $sdt)) {
        setFlash('error', 'Vui lòng nhập đầy đủ Tên, Địa chỉ và Số điện thoại hợp lệ (9–11 số bắt đầu bằng 0).');
        header('Location: ../index.php?page=suppliers' . ($idNcc > 0 ? '&edit=' . $idNcc : ''));
        exit;
    }

    if ($idNcc === 0) {
        $stmt = $pdo->prepare('INSERT INTO NhaCungCap (TenNCC, SDT, DiaChi, TrangThai) VALUES (?, ?, ?, ?)');
        $stmt->execute([$tenNcc, $sdt, $diaChi, $trangThai]);
        setFlash('success', 'Đã thêm nhà cung cấp "' . $tenNcc . '".');
    } else {
        $stmt = $pdo->prepare('UPDATE NhaCungCap SET TenNCC = ?, SDT = ?, DiaChi = ?, TrangThai = ? WHERE ID_NCC = ?');
        $stmt->execute([$tenNcc, $sdt, $diaChi, $trangThai, $idNcc]);
        setFlash('success', 'Đã cập nhật nhà cung cấp.');
    }
} catch (PDOException $exception) {
    error_log('[Hanamiao Supplier Save] ' . $exception->getMessage());
    setFlash('error', 'Không thể lưu thông tin nhà cung cấp.');
}

header('Location: ../index.php?page=suppliers');
exit;

