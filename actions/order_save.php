<?php
declare(strict_types=1);

/**
 * FILE: actions/order_save.php
 * Nhiệm vụ: Lưu đơn hàng mới (DonHang + ChiTietDonHang) bằng PDO Transaction.
 * Giá bán và tổng tiền được tính lại 100% ở phía server từ LichSuGia.
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/business_functions.php';

initSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php?page=checkout');
    exit;
}

$token = $_POST['csrf_token'] ?? null;
if (!verifyCsrfToken(is_string($token) ? $token : null)) {
    setFlash('error', 'Phiên biểu mẫu không hợp lệ. Vui lòng thử lại.');
    header('Location: ../index.php?page=checkout');
    exit;
}

$sessionCart = isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? $_SESSION['cart'] : [];
if (count($sessionCart) === 0) {
    setFlash('error', 'Giỏ hàng của bạn đang trống.');
    header('Location: ../index.php?page=cart');
    exit;
}

$old = [
    'ten_khach' => trim((string)($_POST['ten_khach'] ?? '')),
    'sdt_khach' => preg_replace('/\s+/', '', trim((string)($_POST['sdt_khach'] ?? ''))),
    'ten_nguoi_nhan' => trim((string)($_POST['ten_nguoi_nhan'] ?? '')),
    'sdt_nguoi_nhan' => preg_replace('/\s+/', '', trim((string)($_POST['sdt_nguoi_nhan'] ?? ''))),
    'dia_chi_giao' => trim((string)($_POST['dia_chi_giao'] ?? '')),
    'ngay_giao' => trim((string)($_POST['ngay_giao'] ?? '')),
    'ghi_chu' => trim((string)($_POST['ghi_chu'] ?? '')),
];

$errors = [];

if ($old['ten_khach'] === '' || mb_strlen($old['ten_khach']) > 120) {
    $errors['ten_khach'] = 'Vui lòng nhập họ tên người đặt (tối đa 120 ký tự).';
}
if (!preg_match('/^0\d{8,10}$/', $old['sdt_khach'])) {
    $errors['sdt_khach'] = 'Số điện thoại người đặt không hợp lệ (9–11 số, bắt đầu bằng 0).';
}
if ($old['ten_nguoi_nhan'] === '' || mb_strlen($old['ten_nguoi_nhan']) > 120) {
    $errors['ten_nguoi_nhan'] = 'Vui lòng nhập họ tên người nhận (tối đa 120 ký tự).';
}
if (!preg_match('/^0\d{8,10}$/', $old['sdt_nguoi_nhan'])) {
    $errors['sdt_nguoi_nhan'] = 'Số điện thoại người nhận không hợp lệ (9–11 số, bắt đầu bằng 0).';
}
if ($old['dia_chi_giao'] === '' || mb_strlen($old['dia_chi_giao']) > 255) {
    $errors['dia_chi_giao'] = 'Vui lòng nhập địa chỉ giao hoa đầy đủ.';
}

$deliveryTimestamp = strtotime($old['ngay_giao']);
if ($old['ngay_giao'] === '' || $deliveryTimestamp === false) {
    $errors['ngay_giao'] = 'Vui lòng chọn thời gian giao hợp lệ.';
} elseif ($deliveryTimestamp < (time() - 300)) {
    $errors['ngay_giao'] = 'Thời gian giao hoa không được ở trong quá khứ.';
}

if (count($errors) > 0) {
    $_SESSION['checkout_old'] = $old;
    $_SESSION['checkout_errors'] = $errors;
    setFlash('error', 'Vui lòng kiểm tra lại các thông tin được đánh dấu đỏ.');
    header('Location: ../index.php?page=checkout');
    exit;
}

$ngayGiaoSql = date('Y-m-d H:i:s', $deliveryTimestamp);
$ghiChuSql = $old['ghi_chu'] !== '' ? mb_substr($old['ghi_chu'], 0, 255) : null;

$pdo = null;
try {
    $pdo = getConnection();
    $pdo->beginTransaction();

    $cartData = getCartDetailedItems($pdo, $sessionCart);
    if (count($cartData['items']) === 0 || $cartData['has_issue']) {
        $pdo->rollBack();
        setFlash('error', 'Một số sản phẩm trong giỏ không đủ nguyên liệu hoặc đã đổi trạng thái. Vui lòng kiểm tra lại giỏ hàng.');
        header('Location: ../index.php?page=cart');
        exit;
    }

    $tongTien = 0.0;
    foreach ($cartData['items'] as $item) {
        $tongTien += (float)$item['ThanhTien'];
    }

    $insertOrderSql = "INSERT INTO DonHang (
                           ID_ND, NgayLapDon, TenKhach, SDTKhach, DiaChiGiao,
                           NgayGiao, TrangThaiDon, TenNguoiNhan, SDTNguoiNhan, GhiChu, TongTien
                       ) VALUES (NULL, NOW(), ?, ?, ?, ?, 'ChoXacNhan', ?, ?, ?, ?)";
    $orderStmt = $pdo->prepare($insertOrderSql);
    $orderStmt->execute([
        $old['ten_khach'],
        $old['sdt_khach'],
        $old['dia_chi_giao'],
        $ngayGiaoSql,
        $old['ten_nguoi_nhan'],
        $old['sdt_nguoi_nhan'],
        $ghiChuSql,
        $tongTien,
    ]);

    $newOrderId = (int)$pdo->lastInsertId();

    $insertDetailSql = "INSERT INTO ChiTietDonHang (ID_DH, ID_SP, SoLuong, DonGia, ThanhTien)
                        VALUES (?, ?, ?, ?, ?)";
    $detailStmt = $pdo->prepare($insertDetailSql);

    foreach ($cartData['items'] as $item) {
        $detailStmt->execute([
            $newOrderId,
            (int)$item['ID_SP'],
            (int)$item['SoLuong'],
            (float)$item['GiaBan'],
            (float)$item['ThanhTien'],
        ]);
    }

    $pdo->commit();

    // Xóa giỏ hàng sau khi đặt đơn thành công
    unset($_SESSION['cart'], $_SESSION['checkout_old'], $_SESSION['checkout_errors']);

    $orderCode = '#DH' . str_pad((string)$newOrderId, 4, '0', STR_PAD_LEFT);
    setFlash('success', 'Đặt hàng thành công! Mã đơn hàng của bạn là ' . $orderCode . '.');

    $redirectParams = http_build_query([
        'page' => 'order_lookup',
        'id' => $newOrderId,
        'sdt' => $old['sdt_khach'],
        'placed' => 1,
    ]);
    header('Location: ../index.php?' . $redirectParams);
    exit;
} catch (PDOException $exception) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[Hanamiao Order Save] ' . $exception->getMessage());
    $_SESSION['checkout_old'] = $old;
    setFlash('error', 'Có lỗi xảy ra khi lưu đơn hàng. Vui lòng thử lại sau.');
    header('Location: ../index.php?page=checkout');
    exit;
}

