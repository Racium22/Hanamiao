<?php
declare(strict_types=1);

/**
 * FILE: admin/actions/product_save.php
 * Xử lý thêm/sửa/đổi trạng thái SanPham (kèm upload ảnh thật vào images/sanpham/).
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/helpers.php';

requireAdminLogin('../login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php?page=products');
    exit;
}

if (!verifyCsrfToken(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
    setFlash('error', 'Phiên biểu mẫu không hợp lệ.');
    header('Location: ../index.php?page=products');
    exit;
}

$action = trim((string)($_POST['action'] ?? 'save'));
$idSp = filter_input(INPUT_POST, 'id_sp', FILTER_VALIDATE_INT) ?: 0;

try {
    $pdo = getConnection();

    if ($action === 'toggle') {
        if ($idSp > 0) {
            $stmt = $pdo->prepare('UPDATE SanPham SET TrangThai = IF(TrangThai = 1, 0, 1) WHERE ID_SP = ?');
            $stmt->execute([$idSp]);
            setFlash('success', 'Đã cập nhật trạng thái sản phẩm.');
        }
        header('Location: ../index.php?page=products');
        exit;
    }

    $tenSp = trim((string)($_POST['ten_sp'] ?? ''));
    $idLsp = filter_input(INPUT_POST, 'id_lsp', FILTER_VALIDATE_INT) ?: 0;
    $moTa = trim((string)($_POST['mo_ta'] ?? ''));
    $trangThai = ((int)($_POST['trang_thai'] ?? 1)) === 1 ? 1 : 0;

    if ($tenSp === '' || $idLsp <= 0) {
        setFlash('error', 'Vui lòng nhập Tên sản phẩm và chọn Loại sản phẩm.');
        header('Location: ../index.php?page=products' . ($idSp > 0 ? '&edit=' . $idSp : ''));
        exit;
    }

    $currentImage = null;
    if ($idSp > 0) {
        $oldStmt = $pdo->prepare('SELECT HinhAnh FROM SanPham WHERE ID_SP = ?');
        $oldStmt->execute([$idSp]);
        $currentImage = $oldStmt->fetchColumn() ?: null;
    }

    $upload = handleImageUpload($_FILES['hinh_anh'] ?? null, 'sanpham', is_string($currentImage) ? $currentImage : null);
    if (!$upload['ok']) {
        setFlash('error', $upload['error']);
        header('Location: ../index.php?page=products' . ($idSp > 0 ? '&edit=' . $idSp : ''));
        exit;
    }

    if ($idSp === 0) {
        $stmt = $pdo->prepare(
            'INSERT INTO SanPham (TenSanPham, ID_LSP, HinhAnh, MoTa, TrangThai) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$tenSp, $idLsp, $upload['path'], $moTa === '' ? null : $moTa, $trangThai]);
        $newId = (int)$pdo->lastInsertId();
        setFlash('success', 'Đã thêm sản phẩm "' . $tenSp . '". Hãy tiếp tục thiết lập thành phần bó và giá bán.');
        header('Location: ../index.php?page=product_bom&sp=' . $newId);
        exit;
    } else {
        $stmt = $pdo->prepare(
            'UPDATE SanPham SET TenSanPham = ?, ID_LSP = ?, HinhAnh = ?, MoTa = ?, TrangThai = ? WHERE ID_SP = ?'
        );
        $stmt->execute([$tenSp, $idLsp, $upload['path'], $moTa === '' ? null : $moTa, $trangThai, $idSp]);
        setFlash('success', 'Đã cập nhật sản phẩm.');
    }
} catch (PDOException $exception) {
    error_log('[Hanamiao Product Save] ' . $exception->getMessage());
    setFlash('error', 'Không thể lưu sản phẩm.');
}

header('Location: ../index.php?page=products');
exit;

