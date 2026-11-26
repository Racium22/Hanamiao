<?php
declare(strict_types=1);

/**
 * FILE: admin/actions/flower_save.php
 * Xử lý thêm/sửa/đổi trạng thái bảng Hoa (kèm upload ảnh thật an toàn vào images/hoa/).
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/helpers.php';

requireAdminLogin('../login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php?page=flowers');
    exit;
}

if (!verifyCsrfToken(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
    setFlash('error', 'Phiên biểu mẫu không hợp lệ.');
    header('Location: ../index.php?page=flowers');
    exit;
}

$action = trim((string)($_POST['action'] ?? 'save'));
$idH = filter_input(INPUT_POST, 'id_h', FILTER_VALIDATE_INT) ?: 0;

try {
    $pdo = getConnection();

    if ($action === 'toggle') {
        if ($idH > 0) {
            $stmt = $pdo->prepare('UPDATE Hoa SET TrangThai = IF(TrangThai = 1, 0, 1) WHERE ID_H = ?');
            $stmt->execute([$idH]);
            setFlash('success', 'Đã cập nhật trạng thái hoa nguyên liệu.');
        }
        header('Location: ../index.php?page=flowers');
        exit;
    }

    $tenHoa = trim((string)($_POST['ten_hoa'] ?? ''));
    $idLh = filter_input(INPUT_POST, 'id_lh', FILTER_VALIDATE_INT) ?: 0;
    $soLuongTon = filter_input(INPUT_POST, 'so_luong_ton', FILTER_VALIDATE_INT);
    $trangThai = ((int)($_POST['trang_thai'] ?? 1)) === 1 ? 1 : 0;

    if ($tenHoa === '' || $idLh <= 0) {
        setFlash('error', 'Vui lòng nhập Tên hoa và chọn Loại hoa.');
        header('Location: ../index.php?page=flowers' . ($idH > 0 ? '&edit=' . $idH : ''));
        exit;
    }

    $currentImage = null;
    if ($idH > 0) {
        $oldStmt = $pdo->prepare('SELECT HinhAnh FROM Hoa WHERE ID_H = ?');
        $oldStmt->execute([$idH]);
        $currentImage = $oldStmt->fetchColumn() ?: null;
    }

    $upload = handleImageUpload($_FILES['hinh_anh'] ?? null, 'hoa', is_string($currentImage) ? $currentImage : null);
    if (!$upload['ok']) {
        setFlash('error', $upload['error']);
        header('Location: ../index.php?page=flowers' . ($idH > 0 ? '&edit=' . $idH : ''));
        exit;
    }

    if ($idH === 0) {
        $initStock = ($soLuongTon === false || $soLuongTon === null || $soLuongTon < 0) ? 0 : $soLuongTon;
        $stmt = $pdo->prepare(
            'INSERT INTO Hoa (TenHoa, ID_LH, SoLuongTon, HinhAnh, TrangThai) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$tenHoa, $idLh, $initStock, $upload['path'], $trangThai]);
        setFlash('success', 'Đã thêm "' . $tenHoa . '" vào kho hoa.');
    } else {
        $stmt = $pdo->prepare(
            'UPDATE Hoa SET TenHoa = ?, ID_LH = ?, HinhAnh = ?, TrangThai = ? WHERE ID_H = ?'
        );
        $stmt->execute([$tenHoa, $idLh, $upload['path'], $trangThai, $idH]);
        setFlash('success', 'Đã cập nhật thông tin hoa nguyên liệu.');
    }
} catch (PDOException $exception) {
    error_log('[Hanamiao Flower Save] ' . $exception->getMessage());
    setFlash('error', 'Không thể lưu thông tin hoa nguyên liệu.');
}

header('Location: ../index.php?page=flowers');
exit;

