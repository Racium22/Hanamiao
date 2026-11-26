<?php
declare(strict_types=1);

/**
 * FILE: pages/checkout.php
 * Form đặt hoa: tách rõ Thông tin người đặt và Thông tin người nhận / giao hàng theo bảng DonHang.
 */

$sessionCart = isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? $_SESSION['cart'] : [];
$cartData = ['items' => [], 'total' => 0.0, 'has_issue' => false];

if ($pdo instanceof PDO && count($sessionCart) > 0) {
    try {
        $cartData = getCartDetailedItems($pdo, $sessionCart);
    } catch (PDOException $exception) {
        error_log('[Hanamiao Checkout] ' . $exception->getMessage());
        $dbError = 'Không thể tải dữ liệu đặt hàng.';
    }
}

$items = $cartData['items'];
$totalAmount = (float)$cartData['total'];
$hasIssue = (bool)$cartData['has_issue'];

$old = isset($_SESSION['checkout_old']) && is_array($_SESSION['checkout_old']) ? $_SESSION['checkout_old'] : [];
$errors = isset($_SESSION['checkout_errors']) && is_array($_SESSION['checkout_errors']) ? $_SESSION['checkout_errors'] : [];
unset($_SESSION['checkout_old'], $_SESSION['checkout_errors']);

$defaultDelivery = date('Y-m-d\TH:i', strtotime('+3 hours'));
?>

<section class="content-section">
    <div class="section-header">
        <div>
            <h1 class="page-title">Đặt hàng giao hoa</h1>
            <p class="section-subtitle">Điền thông tin người đặt và người nhận để Hanamiao chuẩn bị hoa và thiệp đúng hẹn.</p>
        </div>
        <a class="link-inline" href="index.php?page=cart">&larr; Quay lại giỏ hàng</a>
    </div>

    <?php if (count($items) === 0 || $hasIssue): ?>
        <div class="panel empty-state">
            <h2 class="empty-title">
                <?php echo count($items) === 0 ? 'Chưa có sản phẩm để đặt hàng' : 'Giỏ hàng cần được điều chỉnh trước khi đặt'; ?>
            </h2>
            <p class="text-muted">Vui lòng kiểm tra lại giỏ hàng và số lượng tồn kho hoa tươi.</p>
            <a class="btn btn-primary" href="index.php?page=cart">Về giỏ hàng</a>
        </div>
    <?php else: ?>
        <div class="checkout-grid">
            <form class="panel checkout-form" action="actions/order_save.php" method="post" novalidate>
                <?php echo csrfInput(); ?>

                <fieldset class="form-section">
                    <legend class="form-section-title">1. Thông tin người đặt</legend>
                    <div class="form-row-2">
                        <div class="form-field">
                            <label for="ten_khach" class="field-label">
                                Họ tên người đặt <span class="required-mark" aria-hidden="true">*</span>
                            </label>
                            <input
                                id="ten_khach"
                                name="ten_khach"
                                type="text"
                                class="input-control<?php echo !empty($errors['ten_khach']) ? ' is-error' : ''; ?>"
                                value="<?php echo h($old['ten_khach'] ?? ''); ?>"
                                required
                            >
                            <p class="field-error"><?php echo h($errors['ten_khach'] ?? ''); ?></p>
                        </div>

                        <div class="form-field">
                            <label for="sdt_khach" class="field-label">
                                Số điện thoại người đặt <span class="required-mark" aria-hidden="true">*</span>
                            </label>
                            <input
                                id="sdt_khach"
                                name="sdt_khach"
                                type="tel"
                                class="input-control tabular-nums<?php echo !empty($errors['sdt_khach']) ? ' is-error' : ''; ?>"
                                placeholder="Ví dụ: 0909123456"
                                value="<?php echo h($old['sdt_khach'] ?? ''); ?>"
                                required
                            >
                            <p class="field-error"><?php echo h($errors['sdt_khach'] ?? ''); ?></p>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="form-section">
                    <div class="form-section-head">
                        <legend class="form-section-title">2. Thông tin người nhận &amp; giao hàng</legend>
                        <label class="checkbox-inline">
                            <input type="checkbox" id="sameAsBuyer">
                            <span>Người nhận giống người đặt</span>
                        </label>
                    </div>

                    <div class="form-row-2">
                        <div class="form-field">
                            <label for="ten_nguoi_nhan" class="field-label">
                                Họ tên người nhận <span class="required-mark" aria-hidden="true">*</span>
                            </label>
                            <input
                                id="ten_nguoi_nhan"
                                name="ten_nguoi_nhan"
                                type="text"
                                class="input-control<?php echo !empty($errors['ten_nguoi_nhan']) ? ' is-error' : ''; ?>"
                                value="<?php echo h($old['ten_nguoi_nhan'] ?? ''); ?>"
                                required
                            >
                            <p class="field-error"><?php echo h($errors['ten_nguoi_nhan'] ?? ''); ?></p>
                        </div>

                        <div class="form-field">
                            <label for="sdt_nguoi_nhan" class="field-label">
                                SĐT người nhận <span class="required-mark" aria-hidden="true">*</span>
                            </label>
                            <input
                                id="sdt_nguoi_nhan"
                                name="sdt_nguoi_nhan"
                                type="tel"
                                class="input-control tabular-nums<?php echo !empty($errors['sdt_nguoi_nhan']) ? ' is-error' : ''; ?>"
                                value="<?php echo h($old['sdt_nguoi_nhan'] ?? ''); ?>"
                                required
                            >
                            <p class="field-error"><?php echo h($errors['sdt_nguoi_nhan'] ?? ''); ?></p>
                        </div>
                    </div>

                    <div class="form-field">
                        <label for="dia_chi_giao" class="field-label">
                            Địa chỉ giao hoa <span class="required-mark" aria-hidden="true">*</span>
                        </label>
                        <input
                            id="dia_chi_giao"
                            name="dia_chi_giao"
                            type="text"
                            class="input-control<?php echo !empty($errors['dia_chi_giao']) ? ' is-error' : ''; ?>"
                            placeholder="Số nhà, tên đường, phường/xã, quận/huyện"
                            value="<?php echo h($old['dia_chi_giao'] ?? ''); ?>"
                            required
                        >
                        <p class="field-error"><?php echo h($errors['dia_chi_giao'] ?? ''); ?></p>
                    </div>

                    <div class="form-row-2">
                        <div class="form-field">
                            <label for="ngay_giao" class="field-label">
                                Thời gian giao dự kiến <span class="required-mark" aria-hidden="true">*</span>
                            </label>
                            <input
                                id="ngay_giao"
                                name="ngay_giao"
                                type="datetime-local"
                                class="input-control tabular-nums<?php echo !empty($errors['ngay_giao']) ? ' is-error' : ''; ?>"
                                value="<?php echo h($old['ngay_giao'] ?? $defaultDelivery); ?>"
                                required
                            >
                            <p class="field-error"><?php echo h($errors['ngay_giao'] ?? ''); ?></p>
                        </div>

                        <div class="form-field">
                            <label for="ghi_chu" class="field-label">Lời chúc ghi thiệp / Ghi chú</label>
                            <input
                                id="ghi_chu"
                                name="ghi_chu"
                                type="text"
                                class="input-control"
                                maxlength="255"
                                placeholder="Ví dụ: Chúc mừng sinh nhật!"
                                value="<?php echo h($old['ghi_chu'] ?? ''); ?>"
                            >
                            <p class="field-error"></p>
                        </div>
                    </div>
                </fieldset>

                <div class="form-actions">
                    <a class="btn btn-outline" href="index.php?page=cart">Hủy</a>
                    <button type="submit" class="btn btn-primary">Xác nhận đặt hoa</button>
                </div>
            </form>

            <aside class="panel checkout-summary">
                <h2 class="widget-title">Tóm tắt đơn hàng</h2>
                <ul class="summary-list">
                    <?php foreach ($items as $item): ?>
                        <li class="summary-row">
                            <div class="summary-item-info">
                                <span class="summary-item-name"><?php echo h($item['TenSanPham']); ?></span>
                                <span class="text-xs text-muted tabular-nums">
                                    <?php echo (int)$item['SoLuong']; ?> &times; <?php echo h(formatMoney($item['GiaBan'])); ?>
                                </span>
                            </div>
                            <strong class="tabular-nums"><?php echo h(formatMoney($item['ThanhTien'])); ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="summary-total">
                    <span>Tổng cộng</span>
                    <strong class="cart-total-value tabular-nums"><?php echo h(formatMoney($totalAmount)); ?></strong>
                </div>

                <p class="text-xs text-muted summary-note">
                    Giá được tính theo bảng lịch sử giá hiện hành tại thời điểm đặt hàng. Nhân viên sẽ liên hệ xác nhận trước khi kết hoa.
                </p>
            </aside>
        </div>
    <?php endif; ?>
</section>

