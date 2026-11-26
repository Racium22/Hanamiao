<?php
declare(strict_types=1);

/**
 * FILE: admin/actions/wastage_save.php
 * Ghi nhận HaoHut + ChiTietHaoHut và trừ Hoa.SoLuongTon trong Transaction (chặn âm kho).
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/helpers.php';

requireAdminLogin('../login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php?page=wastage');
    exit;
}

if (!verifyCsrfToken(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
    setFlash('error', 'Phiên biểu mẫu không hợp lệ.');
    header('Location: ../index.php?page=wastage');
    exit;
}

$currentAdmin = getCurrentAdmin();
$lyDo = trim((string)($_POST['ly_do'] ?? ''));
$idHoaArr = $_POST['id_h'] ?? [];
$qtyArr = $_POST['so_luong'] ?? [];

if ($lyDo === '' || !is_array($idHoaArr) || !is_array($qtyArr)) {
    setFlash('error', 'Vui lòng nhập Lý do hao hụt và chọn ít nhất 1 loại hoa.');
    header('Location: ../index.php?page=wastage');
    exit;
}

// Gom tổng số lượng hao hụt theo từng ID_H (tránh chọn trùng dòng vượt tồn kho)
$grouped = [];
$count = count($idHoaArr);
for ($i = 0; $i < $count; $i++) {
    $idH = (int)($idHoaArr[$i] ?? 0);
    $qty = (int)($qtyArr[$i] ?? 0);
    if ($idH <= 0 || $qty <= 0) {
        continue;
    }
    $grouped[$idH] = ($grouped[$idH] ?? 0) + $qty;
}

if (count($grouped) === 0) {
    setFlash('error', 'Vui lòng nhập số lượng hao hụt lớn hơn 0.');
    header('Location: ../index.php?page=wastage');
    exit;
}

$pdo = null;
try {
    $pdo = getConnection();
    $pdo->beginTransaction();

    $lockStmt = $pdo->prepare('SELECT ID_H, TenHoa, SoLuongTon FROM Hoa WHERE ID_H = ? FOR UPDATE');
    foreach ($grouped as $idH => $qty) {
        $lockStmt->execute([$idH]);
        $flower = $lockStmt->fetch();
        if (!$flower) {
            $pdo->rollBack();
            setFlash('error', 'Loại hoa #' . $idH . ' không tồn tại.');
            header('Location: ../index.php?page=wastage');
            exit;
        }
        if ((int)$flower['SoLuongTon'] < $qty) {
            $pdo->rollBack();
            setFlash(
                'error',
                'Không thể ghi hao hụt ' . $qty . ' bông "' . $flower['TenHoa'] . '" vì tồn kho chỉ còn ' . (int)$flower['SoLuongTon'] . ' bông.'
            );
            header('Location: ../index.php?page=wastage');
            exit;
        }
    }

    $hhStmt = $pdo->prepare('INSERT INTO HaoHut (ID_ND, NgayGhiNhan, LyDo) VALUES (?, NOW(), ?)');
    $hhStmt->execute([(int)$currentAdmin['ID_ND'], mb_substr($lyDo, 0, 255)]);
    $newHhId = (int)$pdo->lastInsertId();

    $ctStmt = $pdo->prepare('INSERT INTO ChiTietHaoHut (ID_HH, ID_H, SoLuong) VALUES (?, ?, ?)');
    $subStmt = $pdo->prepare('UPDATE Hoa SET SoLuongTon = SoLuongTon - ? WHERE ID_H = ?');

    foreach ($grouped as $idH => $qty) {
        $ctStmt->execute([$newHhId, $idH, $qty]);
        $subStmt->execute([$qty, $idH]);
    }

    $pdo->commit();
    setFlash('success', 'Đã ghi nhận phiếu hao hụt #HH' . str_pad((string)$newHhId, 4, '0', STR_PAD_LEFT) . ' và trừ tồn kho.');
    header('Location: ../index.php?page=wastage&view=' . $newHhId);
    exit;
} catch (PDOException $exception) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[Hanamiao Wastage Save] ' . $exception->getMessage());
    setFlash('error', 'Không thể lưu phiếu hao hụt.');
    header('Location: ../index.php?page=wastage');
    exit;
}

