<?php
declare(strict_types=1);

/**
 * FILE: admin/actions/price_save.php
 * Xử lý lưu Lịch sử giá bán (LichSuGia) trong PDO Transaction:
 * - Nếu sản phẩm đã có giá đang áp dụng (NgayKetThuc IS NULL): tự động lấy NOW()
 *   để kết thúc mức giá cũ và mở mức giá mới với NgayBatDau = NOW(), NgayKetThuc = NULL.
 * - Nếu chưa có mức giá đang mở: nhận NgayBatDau người dùng nhập (mặc định NOW()), NgayKetThuc = NULL.
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/helpers.php';

requireAdminLogin('../login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php?page=product_prices');
    exit;
}

$idSp = filter_input(INPUT_POST, 'id_sp', FILTER_VALIDATE_INT) ?: 0;
$backUrl = '../index.php?page=product_prices' . ($idSp > 0 ? '&sp=' . $idSp : '');

if (!verifyCsrfToken(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
    setFlash('error', 'Phiên biểu mẫu không hợp lệ.');
    header('Location: ' . $backUrl);
    exit;
}

$rawGia = trim((string)($_POST['gia_ban'] ?? ''));
if ($idSp <= 0 || $rawGia === '' || !is_numeric($rawGia) || (float)$rawGia < 0) {
    setFlash('error', 'Vui lòng nhập mức giá bán hợp lệ (>= 0).');
    header('Location: ' . $backUrl);
    exit;
}
$giaBan = round((float)$rawGia);
$rawStart = trim((string)($_POST['ngay_bat_dau'] ?? ''));

$pdo = null;
try {
    $pdo = getConnection();
    $pdo->beginTransaction();

    $spCheck = $pdo->prepare('SELECT TenSanPham FROM SanPham WHERE ID_SP = ?');
    $spCheck->execute([$idSp]);
    $spRow = $spCheck->fetch();
    if (!$spRow) {
        $pdo->rollBack();
        setFlash('error', 'Sản phẩm không tồn tại.');
        header('Location: ../index.php?page=product_prices');
        exit;
    }

    // Khóa các dòng giá đang mở (NgayKetThuc IS NULL) của sản phẩm này
    $openStmt = $pdo->prepare('SELECT ID_GB FROM LichSuGia WHERE ID_SP = ? AND NgayKetThuc IS NULL FOR UPDATE');
    $openStmt->execute([$idSp]);
    $openRows = $openStmt->fetchAll();

    if (count($openRows) > 0) {
        // Có đợt giá cũ đang mở -> dùng chính thời điểm hiện tại NOW() để đóng cũ và mở mới
        $closeStmt = $pdo->prepare('UPDATE LichSuGia SET NgayKetThuc = NOW() WHERE ID_SP = ? AND NgayKetThuc IS NULL');
        $closeStmt->execute([$idSp]);

        $insertStmt = $pdo->prepare(
            'INSERT INTO LichSuGia (ID_SP, GiaBan, NgayBatDau, NgayKetThuc) VALUES (?, ?, NOW(), NULL)'
        );
        $insertStmt->execute([$idSp, $giaBan]);
        $msg = 'Đã đóng mức giá cũ theo thời điểm hiện tại và áp dụng giá mới ' . formatMoney($giaBan) . '.';
    } else {
        // Đợt giá đầu tiên -> cho phép nhập NgayBatDau (mặc định NOW()), NgayKetThuc = NULL
        $startSql = null;
        if ($rawStart !== '') {
            $ts = strtotime($rawStart);
            if ($ts === false) {
                $pdo->rollBack();
                setFlash('error', 'Ngày bắt đầu không hợp lệ.');
                header('Location: ' . $backUrl);
                exit;
            }
            $startSql = date('Y-m-d H:i:s', $ts);
        }

        if ($startSql !== null) {
            $insertStmt = $pdo->prepare(
                'INSERT INTO LichSuGia (ID_SP, GiaBan, NgayBatDau, NgayKetThuc) VALUES (?, ?, ?, NULL)'
            );
            $insertStmt->execute([$idSp, $giaBan, $startSql]);
        } else {
            $insertStmt = $pdo->prepare(
                'INSERT INTO LichSuGia (ID_SP, GiaBan, NgayBatDau, NgayKetThuc) VALUES (?, ?, NOW(), NULL)'
            );
            $insertStmt->execute([$idSp, $giaBan]);
        }
        $msg = 'Đã thiết lập giá bán ' . formatMoney($giaBan) . ' cho "' . $spRow['TenSanPham'] . '".';
    }

    $pdo->commit();
    setFlash('success', $msg);
} catch (PDOException $exception) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[Hanamiao Price Save] ' . $exception->getMessage());
    setFlash('error', 'Không thể lưu lịch sử giá bán.');
}

header('Location: ' . $backUrl);
exit;

