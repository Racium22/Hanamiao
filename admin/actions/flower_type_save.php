<?php
declare(strict_types=1);

/**
 * FILE: admin/actions/flower_type_save.php
 * Xử lý thêm/sửa/đổi trạng thái LoaiHoa.
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/helpers.php';

requireAdminLogin('../login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php?page=flower_types');
    exit;
}

if (!verifyCsrfToken(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
    setFlash('error', 'Phiên biểu mẫu không hợp lệ.');
    header('Location: ../index.php?page=flower_types');
    exit;
}

$action = trim((string)($_POST['action'] ?? 'save'));
$idLh = filter_input(INPUT_POST, 'id_lh', FILTER_VALIDATE_INT) ?: 0;

try {
    $pdo = getConnection();

    if ($action === 'toggle') {
        if ($idLh > 0) {
            $stmt = $pdo->prepare('UPDATE LoaiHoa SET TrangThai = IF(TrangThai = 1, 0, 1) WHERE ID_LH = ?');
            $stmt->execute([$idLh]);
            setFlash('success', 'Đã cập nhật trạng thái loại hoa.');
        }
        header('Location: ../index.php?page=flower_types');
        exit;
    }

    $tenLh = trim((string)($_POST['ten_lh'] ?? ''));
    $trangThai = ((int)($_POST['trang_thai'] ?? 1)) === 1 ? 1 : 0;

    if ($tenLh === '') {
        setFlash('error', 'Tên loại hoa không được để trống.');
        header('Location: ../index.php?page=flower_types' . ($idLh > 0 ? '&edit=' . $idLh : ''));
        exit;
    }

    if ($idLh === 0) {
        $stmt = $pdo->prepare('INSERT INTO LoaiHoa (TenLH, TrangThai) VALUES (?, ?)');
        $stmt->execute([$tenLh, $trangThai]);
        setFlash('success', 'Đã thêm loại hoa "' . $tenLh . '".');
    } else {
        $stmt = $pdo->prepare('UPDATE LoaiHoa SET TenLH = ?, TrangThai = ? WHERE ID_LH = ?');
        $stmt->execute([$tenLh, $trangThai, $idLh]);
        setFlash('success', 'Đã cập nhật loại hoa.');
    }
} catch (PDOException $exception) {
    error_log('[Hanamiao Flower Type Save] ' . $exception->getMessage());
    setFlash('error', 'Không thể lưu loại hoa.');
}

header('Location: ../index.php?page=flower_types');
exit;

