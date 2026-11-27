<?php
declare(strict_types=1);

/**
 * FILE: admin/actions/order_status_save.php
 * Xử lý chuyển trạng thái DonHang theo State Machine chặt chẽ + Transaction trừ/hoàn kho Hoa theo ThanhPhanBo.
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/helpers.php';

requireAdminLogin('../login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php?page=orders');
    exit;
}

$idDh = filter_input(INPUT_POST, 'id_dh', FILTER_VALIDATE_INT) ?: 0;
$nextStatus = trim((string)($_POST['next_status'] ?? ''));
$backUrl = '../index.php?page=orders' . ($idDh > 0 ? '&view=' . $idDh : '');

if (!verifyCsrfToken(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
    setFlash('error', 'Phiên biểu mẫu không hợp lệ.');
    header('Location: ' . $backUrl);
    exit;
}

if ($idDh <= 0 || $nextStatus === '') {
    setFlash('error', 'Thông tin đơn hàng hoặc trạng thái chuyển đổi không hợp lệ.');
    header('Location: ../index.php?page=orders');
    exit;
}

// State Machine chuẩn — chống nhảy cóc hoặc trừ/hoàn kho 2 lần
$allowedTransitions = [
    'ChoXacNhan' => ['DangChuanBi', 'DaHuy'],
    'DangChuanBi' => ['DangGiao', 'DaHuy'],
    'DangGiao' => ['HoanThanh'],
    'HoanThanh' => [],
    'DaHuy' => [],
];

$currentAdmin = getCurrentAdmin();
$pdo = null;

try {
    $pdo = getConnection();
    $pdo->beginTransaction();

    $orderStmt = $pdo->prepare('SELECT ID_DH, TrangThaiDon FROM DonHang WHERE ID_DH = ? FOR UPDATE');
    $orderStmt->execute([$idDh]);
    $order = $orderStmt->fetch();

    if (!$order) {
        $pdo->rollBack();
        setFlash('error', 'Không tìm thấy đơn hàng #' . $idDh . '.');
        header('Location: ../index.php?page=orders');
        exit;
    }

    $currentStatus = (string)$order['TrangThaiDon'];
    $validNext = $allowedTransitions[$currentStatus] ?? [];
    if (!in_array($nextStatus, $validNext, true)) {
        $pdo->rollBack();
        setFlash('error', 'Không thể chuyển trạng thái từ "' . $currentStatus . '" sang "' . $nextStatus . '".');
        header('Location: ' . $backUrl);
        exit;
    }

    // 1. Chuyển ChoXacNhan -> DangChuanBi: KIỂM TRA & TRỪ KHO HOA theo ThanhPhanBo
    if ($currentStatus === 'ChoXacNhan' && $nextStatus === 'DangChuanBi') {
        // Kiểm tra tất cả sản phẩm trong đơn đều đã có công thức ThanhPhanBo
        $noBomStmt = $pdo->prepare(
            'SELECT sp.TenSanPham
             FROM ChiTietDonHang ct
             INNER JOIN SanPham sp ON sp.ID_SP = ct.ID_SP
             LEFT JOIN ThanhPhanBo t ON t.ID_SP = ct.ID_SP
             WHERE ct.ID_DH = ?
             GROUP BY ct.ID_SP, sp.TenSanPham
             HAVING COUNT(t.ID_TPB) = 0'
        );
        $noBomStmt->execute([$idDh]);
        $missingBomProducts = $noBomStmt->fetchAll(PDO::FETCH_COLUMN);
        if (count($missingBomProducts) > 0) {
            $pdo->rollBack();
            setFlash(
                'error',
                'Không thể duyệt đơn vì sản phẩm chưa có thành phần bó hoa: ' . implode(', ', $missingBomProducts) . '.'
            );
            header('Location: ' . $backUrl);
            exit;
        }

        // Tính tổng số bông cần dùng theo từng ID_H
        $reqStmt = $pdo->prepare(
            'SELECT t.ID_H, SUM(ct.SoLuong * t.SoBong) AS TongBongCan
             FROM ChiTietDonHang ct
             INNER JOIN ThanhPhanBo t ON t.ID_SP = ct.ID_SP
             WHERE ct.ID_DH = ?
             GROUP BY t.ID_H'
        );
        $reqStmt->execute([$idDh]);
        $requiredMap = $reqStmt->fetchAll();

        $lockHoaStmt = $pdo->prepare('SELECT ID_H, TenHoa, SoLuongTon, TrangThai FROM Hoa WHERE ID_H = ? FOR UPDATE');
        $shortages = [];
        foreach ($requiredMap as $req) {
            $idH = (int)$req['ID_H'];
            $need = (int)$req['TongBongCan'];
            $lockHoaStmt->execute([$idH]);
            $hoa = $lockHoaStmt->fetch();
            if (!$hoa || (int)$hoa['TrangThai'] !== 1 || (int)$hoa['SoLuongTon'] < $need) {
                $tenHoa = $hoa ? (string)$hoa['TenHoa'] : ('Hoa #' . $idH);
                $ton = $hoa ? (int)$hoa['SoLuongTon'] : 0;
                $shortages[] = $tenHoa . ' (cần ' . $need . ', còn ' . $ton . ')';
            }
        }

        if (count($shortages) > 0) {
            $pdo->rollBack();
            setFlash('error', 'Không đủ hoa tồn kho để duyệt đơn: ' . implode('; ', $shortages) . '.');
            header('Location: ' . $backUrl);
            exit;
        }

        $deductStmt = $pdo->prepare('UPDATE Hoa SET SoLuongTon = SoLuongTon - ? WHERE ID_H = ?');
        foreach ($requiredMap as $req) {
            $deductStmt->execute([(int)$req['TongBongCan'], (int)$req['ID_H']]);
        }
    }

    // 2. Chuyển DangChuanBi -> DaHuy: HOÀN TRẢ KHO HOA đã trừ trước đó
    if ($currentStatus === 'DangChuanBi' && $nextStatus === 'DaHuy') {
        $restoreReqStmt = $pdo->prepare(
            'SELECT t.ID_H, SUM(ct.SoLuong * t.SoBong) AS TongBongHoan
             FROM ChiTietDonHang ct
             INNER JOIN ThanhPhanBo t ON t.ID_SP = ct.ID_SP
             WHERE ct.ID_DH = ?
             GROUP BY t.ID_H'
        );
        $restoreReqStmt->execute([$idDh]);
        $restoreList = $restoreReqStmt->fetchAll();

        $restoreStmt = $pdo->prepare('UPDATE Hoa SET SoLuongTon = SoLuongTon + ? WHERE ID_H = ?');
        foreach ($restoreList as $item) {
            $restoreStmt->execute([(int)$item['TongBongHoan'], (int)$item['ID_H']]);
        }
    }

    $updStmt = $pdo->prepare('UPDATE DonHang SET TrangThaiDon = ?, ID_ND = ? WHERE ID_DH = ?');
    $updStmt->execute([$nextStatus, (int)$currentAdmin['ID_ND'], $idDh]);

    $pdo->commit();

    $meta = getOrderStatusMeta($nextStatus);
    $extra = '';
    if ($currentStatus === 'ChoXacNhan' && $nextStatus === 'DangChuanBi') {
        $extra = ' Đã tự động trừ tồn kho hoa tươi theo thành phần bó.';
    } elseif ($currentStatus === 'DangChuanBi' && $nextStatus === 'DaHuy') {
        $extra = ' Đã tự động hoàn trả số bông hoa về kho.';
    }

    setFlash('success', 'Đã chuyển đơn hàng sang trạng thái "' . $meta['label'] . '".' . $extra);
} catch (PDOException $exception) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[Hanamiao Order Status Save] ' . $exception->getMessage());
    setFlash('error', 'Không thể cập nhật trạng thái đơn hàng.');
}

header('Location: ' . $backUrl);
exit;

