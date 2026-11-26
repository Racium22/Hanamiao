<?php
declare(strict_types=1);

/**
 * FILE: admin/actions/bom_save.php
 * Thêm/cập nhật/xóa định mức hoa trong bảng ThanhPhanBo.
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/helpers.php';

requireAdminLogin('../login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php?page=product_bom');
    exit;
}

$idSp = filter_input(INPUT_POST, 'id_sp', FILTER_VALIDATE_INT) ?: 0;
$backUrl = '../index.php?page=product_bom' . ($idSp > 0 ? '&sp=' . $idSp : '');

if (!verifyCsrfToken(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
    setFlash('error', 'Phiên biểu mẫu không hợp lệ.');
    header('Location: ' . $backUrl);
    exit;
}

$action = trim((string)($_POST['action'] ?? 'save'));

try {
    $pdo = getConnection();

    if ($action === 'delete') {
        $idTpb = filter_input(INPUT_POST, 'id_tpb', FILTER_VALIDATE_INT) ?: 0;
        if ($idTpb > 0 && $idSp > 0) {
            $stmt = $pdo->prepare('DELETE FROM ThanhPhanBo WHERE ID_TPB = ? AND ID_SP = ?');
            $stmt->execute([$idTpb, $idSp]);
            setFlash('success', 'Đã xóa thành phần khỏi công thức bó hoa.');
        }
        header('Location: ' . $backUrl);
        exit;
    }

    $idH = filter_input(INPUT_POST, 'id_h', FILTER_VALIDATE_INT) ?: 0;
    $soBong = filter_input(INPUT_POST, 'so_bong', FILTER_VALIDATE_INT) ?: 0;

    if ($idSp <= 0 || $idH <= 0 || $soBong <= 0) {
        setFlash('error', 'Vui lòng chọn hoa và nhập số bông lớn hơn 0.');
        header('Location: ' . $backUrl);
        exit;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO ThanhPhanBo (ID_SP, ID_H, SoBong) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE SoBong = VALUES(SoBong)'
    );
    $stmt->execute([$idSp, $idH, $soBong]);
    setFlash('success', 'Đã lưu định mức thành phần bó hoa.');
} catch (PDOException $exception) {
    error_log('[Hanamiao BOM Save] ' . $exception->getMessage());
    setFlash('error', 'Không thể lưu thành phần bó hoa.');
}

header('Location: ' . $backUrl);
exit;

