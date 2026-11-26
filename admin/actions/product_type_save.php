<?php
declare(strict_types=1);

/**
 * FILE: admin/actions/product_type_save.php
 * Xử lý thêm/sửa/đổi trạng thái LoaiSanPham.
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/helpers.php';

requireAdminLogin('../login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php?page=product_types');
    exit;
}

if (!verifyCsrfToken(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
    setFlash('error', 'Phiên biểu mẫu không hợp lệ.');
    header('Location: ../index.php?page=product_types');
    exit;
}

$action = trim((string)($_POST['action'] ?? 'save'));
$idLsp = filter_input(INPUT_POST, 'id_lsp', FILTER_VALIDATE_INT) ?: 0;

try {
    $pdo = getConnection();

    if ($action === 'toggle') {
        if ($idLsp > 0) {
            $stmt = $pdo->prepare('UPDATE LoaiSanPham SET TrangThai = IF(TrangThai = 1, 0, 1) WHERE ID_LSP = ?');
            $stmt->execute([$idLsp]);
            setFlash('success', 'Đã cập nhật trạng thái loại sản phẩm.');
        }
        header('Location: ../index.php?page=product_types');
        exit;
    }

    $tenLsp = trim((string)($_POST['ten_lsp'] ?? ''));
    $trangThai = ((int)($_POST['trang_thai'] ?? 1)) === 1 ? 1 : 0;

    if ($tenLsp === '') {
        setFlash('error', 'Tên loại sản phẩm không được để trống.');
        header('Location: ../index.php?page=product_types' . ($idLsp > 0 ? '&edit=' . $idLsp : ''));
        exit;
    }

    if ($idLsp === 0) {
        $stmt = $pdo->prepare('INSERT INTO LoaiSanPham (TenLoaiSanPham, TrangThai) VALUES (?, ?)');
        $stmt->execute([$tenLsp, $trangThai]);
        setFlash('success', 'Đã thêm loại sản phẩm "' . $tenLsp . '".');
    } else {
        $stmt = $pdo->prepare('UPDATE LoaiSanPham SET TenLoaiSanPham = ?, TrangThai = ? WHERE ID_LSP = ?');
        $stmt->execute([$tenLsp, $trangThai, $idLsp]);
        setFlash('success', 'Đã cập nhật loại sản phẩm.');
    }
} catch (PDOException $exception) {
    error_log('[Hanamiao Product Type Save] ' . $exception->getMessage());
    setFlash('error', 'Không thể lưu loại sản phẩm.');
}

header('Location: ../index.php?page=product_types');
exit;

