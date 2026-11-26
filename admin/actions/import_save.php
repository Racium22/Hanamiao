<?php
declare(strict_types=1);

/**
 * FILE: admin/actions/import_save.php
 * Lập PhieuNhap + ChiTietPhieuNhap và cộng Hoa.SoLuongTon trong PDO Transaction.
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/helpers.php';

requireAdminLogin('../login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php?page=imports');
    exit;
}

if (!verifyCsrfToken(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
    setFlash('error', 'Phiên biểu mẫu không hợp lệ.');
    header('Location: ../index.php?page=imports');
    exit;
}

$currentAdmin = getCurrentAdmin();
$idNcc = filter_input(INPUT_POST, 'id_ncc', FILTER_VALIDATE_INT) ?: 0;
$idHoaArr = $_POST['id_h'] ?? [];
$qtyArr = $_POST['so_luong'] ?? [];
$priceArr = $_POST['gia_nhap'] ?? [];

if ($idNcc <= 0 || !is_array($idHoaArr) || !is_array($qtyArr) || !is_array($priceArr)) {
    setFlash('error', 'Vui lòng chọn nhà cung cấp và ít nhất 1 dòng hoa nhập.');
    header('Location: ../index.php?page=imports');
    exit;
}

$lines = [];
$count = count($idHoaArr);
for ($i = 0; $i < $count; $i++) {
    $idH = (int)($idHoaArr[$i] ?? 0);
    $qty = (int)($qtyArr[$i] ?? 0);
    $gia = (float)($priceArr[$i] ?? -1);
    if ($idH <= 0 || $qty <= 0 || $gia < 0) {
        continue;
    }
    $lines[] = [
        'ID_H' => $idH,
        'SoLuong' => $qty,
        'GiaNhap' => $gia,
        'ThanhTien' => $qty * $gia,
    ];
}

if (count($lines) === 0) {
    setFlash('error', 'Không có dòng hoa hợp lệ (số lượng phải > 0 và giá nhập >= 0).');
    header('Location: ../index.php?page=imports');
    exit;
}

$pdo = null;
try {
    $pdo = getConnection();
    $pdo->beginTransaction();

    $nccCheck = $pdo->prepare('SELECT COUNT(*) FROM NhaCungCap WHERE ID_NCC = ?');
    $nccCheck->execute([$idNcc]);
    if ((int)$nccCheck->fetchColumn() === 0) {
        $pdo->rollBack();
        setFlash('error', 'Nhà cung cấp không tồn tại.');
        header('Location: ../index.php?page=imports');
        exit;
    }

    $pnStmt = $pdo->prepare('INSERT INTO PhieuNhap (ID_ND, ID_NCC, NgayLapPhieu) VALUES (?, ?, NOW())');
    $pnStmt->execute([(int)$currentAdmin['ID_ND'], $idNcc]);
    $newPnId = (int)$pdo->lastInsertId();

    $hoaCheck = $pdo->prepare('SELECT ID_H FROM Hoa WHERE ID_H = ? FOR UPDATE');
    $ctStmt = $pdo->prepare(
        'INSERT INTO ChiTietPhieuNhap (ID_PN, ID_H, SoLuong, GiaNhap, ThanhTien) VALUES (?, ?, ?, ?, ?)'
    );
    $updateStockStmt = $pdo->prepare('UPDATE Hoa SET SoLuongTon = SoLuongTon + ? WHERE ID_H = ?');

    foreach ($lines as $line) {
        $hoaCheck->execute([$line['ID_H']]);
        if (!$hoaCheck->fetch()) {
            $pdo->rollBack();
            setFlash('error', 'Loại hoa #' . $line['ID_H'] . ' không tồn tại trong kho.');
            header('Location: ../index.php?page=imports');
            exit;
        }

        $ctStmt->execute([
            $newPnId,
            $line['ID_H'],
            $line['SoLuong'],
            $line['GiaNhap'],
            $line['ThanhTien'],
        ]);
        $updateStockStmt->execute([$line['SoLuong'], $line['ID_H']]);
    }

    $pdo->commit();
    setFlash('success', 'Đã lưu Phiếu nhập #PN' . str_pad((string)$newPnId, 4, '0', STR_PAD_LEFT) . ' và cộng tồn kho thành công.');
    header('Location: ../index.php?page=imports&view=' . $newPnId);
    exit;
} catch (PDOException $exception) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[Hanamiao Import Save] ' . $exception->getMessage());
    setFlash('error', 'Có lỗi xảy ra khi lưu phiếu nhập kho.');
    header('Location: ../index.php?page=imports');
    exit;
}

