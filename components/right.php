<?php
declare(strict_types=1);
/**
 * FILE: components/right.php
 * Cột phải Storefront: Tóm tắt giỏ hàng, cam kết dịch vụ và thông tin hỗ trợ khách hàng.
 */
$cartItemsCount = getCartCount();
?>
<section class="widget">
    <h2 class="widget-title">Giỏ hàng của bạn</h2>
    <p class="widget-desc">
        Hiện có <strong class="tabular-nums"><?php echo $cartItemsCount; ?></strong> sản phẩm trong giỏ.
    </p>
    <div class="widget-actions">
        <a class="btn btn-primary btn-block" href="index.php?page=cart">Xem giỏ hàng</a>
        <a class="btn btn-outline btn-block" href="index.php?page=order_lookup">Tra cứu đơn hàng</a>
    </div>
</section>

<section class="widget">
    <h2 class="widget-title">Hỗ trợ đặt hoa</h2>
    <ul class="info-list">
        <li><span>Hotline:</span> <strong class="tabular-nums">0909 123 456</strong></li>
        <li><span>Giờ mở cửa:</span> <strong class="tabular-nums">07:00 – 21:00</strong></li>
        <li><span>Giao nhanh:</span> <strong>Nội thành trong 2 giờ</strong></li>
        <li><span>Tặng kèm:</span> <strong>Thiệp viết tay miễn phí</strong></li>
    </ul>
</section>

<section class="widget">
    <h2 class="widget-title">Cam kết Hanamiao</h2>
    <p class="widget-desc">
        Hoa tươi nhập mới từ Đà Lạt mỗi sáng, đúng định mức số bông theo công thức bó và kiểm tra tươi trước khi giao.
    </p>
</section>

