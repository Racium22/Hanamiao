<?php
declare(strict_types=1);

/**
 * FILE: pages/cart.php
 * Giỏ hàng Storefront: hiển thị danh sách sản phẩm đã chọn, kiểm tra tồn kho hoa & tổng tiền.
 */

$sessionCart = isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? $_SESSION['cart'] : [];
$cartData = ['items' => [], 'total' => 0.0, 'has_issue' => false];

if ($pdo instanceof PDO && count($sessionCart) > 0) {
    try {
        $cartData = getCartDetailedItems($pdo, $sessionCart);
    } catch (PDOException $exception) {
        error_log('[Hanamiao Cart Page] ' . $exception->getMessage());
        $dbError = 'Không thể tải thông tin giỏ hàng.';
    }
}

$items = $cartData['items'];
$totalAmount = (float)$cartData['total'];
$hasIssue = (bool)$cartData['has_issue'];
?>

<section class="content-section">
    <div class="section-header">
        <div>
            <h1 class="page-title">Giỏ hàng của bạn</h1>
            <p class="section-subtitle tabular-nums">
                <?php echo count($items) > 0 ? ('Đang có ' . getCartCount() . ' sản phẩm trong giỏ.') : 'Chưa có sản phẩm nào được chọn.'; ?>
            </p>
        </div>
        <?php if (count($items) > 0): ?>
            <form action="actions/cart_action.php" method="post" data-confirm="Bạn có chắc muốn xóa toàn bộ sản phẩm khỏi giỏ hàng?">
                <?php echo csrfInput(); ?>
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="btn btn-danger">Xóa tất cả</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (count($items) === 0): ?>
        <div class="panel empty-state">
            <h2 class="empty-title">Giỏ hàng đang trống</h2>
            <p class="text-muted">Hãy chọn những bó hoa hoặc giỏ hoa ưng ý từ danh mục Hanamiao.</p>
            <a class="btn btn-primary" href="index.php?page=products">Khám phá sản phẩm</a>
        </div>
    <?php else: ?>
        <?php if ($hasIssue): ?>
            <div class="alert alert-error" role="alert">
                Một số sản phẩm trong giỏ vượt quá số lượng hoa tồn kho hoặc đã ngừng bán. Vui lòng điều chỉnh trước khi đặt hàng.
            </div>
        <?php endif; ?>

        <div class="panel cart-panel">
            <div class="table-wrap">
                <table class="store-table cart-table">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th class="text-right">Đơn giá</th>
                            <th>Số lượng</th>
                            <th class="text-right">Thành tiền</th>
                            <th class="text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <?php
                            $idSp = (int)$item['ID_SP'];
                            $maxQty = max(1, (int)$item['SoLuongKhaDung']);
                            ?>
                            <tr>
                                <td>
                                    <div class="cart-item-info">
                                        <a class="cart-thumb" href="index.php?page=product_detail&amp;id=<?php echo $idSp; ?>">
                                            <img src="<?php echo h(getImageUrl($item['HinhAnh'])); ?>" alt="<?php echo h($item['TenSanPham']); ?>">
                                        </a>
                                        <div class="cart-item-text">
                                            <a class="cart-item-title" href="index.php?page=product_detail&amp;id=<?php echo $idSp; ?>">
                                                <?php echo h($item['TenSanPham']); ?>
                                            </a>
                                            <span class="text-xs text-muted"><?php echo h($item['TenLoaiSanPham']); ?></span>
                                            <?php if ($item['Issue'] !== ''): ?>
                                                <span class="cart-item-error"><?php echo h($item['Issue']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-right tabular-nums"><?php echo h(formatMoney($item['GiaBan'])); ?></td>
                                <td>
                                    <form class="cart-qty-form" action="actions/cart_action.php" method="post">
                                        <?php echo csrfInput(); ?>
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="id_sp" value="<?php echo $idSp; ?>">
                                        <input
                                            type="number"
                                            name="qty"
                                            class="input-control cart-qty-input tabular-nums"
                                            value="<?php echo (int)$item['SoLuong']; ?>"
                                            min="1"
                                            max="<?php echo $maxQty; ?>"
                                            aria-label="Số lượng <?php echo h($item['TenSanPham']); ?>"
                                        >
                                        <button type="submit" class="btn btn-outline btn-sm">Cập nhật</button>
                                    </form>
                                </td>
                                <td class="text-right tabular-nums font-medium">
                                    <?php echo h(formatMoney($item['ThanhTien'])); ?>
                                </td>
                                <td class="text-right">
                                    <form action="actions/cart_action.php" method="post">
                                        <?php echo csrfInput(); ?>
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="id_sp" value="<?php echo $idSp; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="cart-summary-bar">
                <a class="btn btn-outline" href="index.php?page=products">&larr; Tiếp tục chọn hoa</a>
                <div class="cart-summary-right">
                    <div class="cart-total-box">
                        <span class="text-muted">Tổng thanh toán:</span>
                        <strong class="cart-total-value tabular-nums"><?php echo h(formatMoney($totalAmount)); ?></strong>
                    </div>
                    <?php if ($hasIssue): ?>
                        <button type="button" class="btn btn-primary" disabled>Tiến hành đặt hàng</button>
                    <?php else: ?>
                        <a class="btn btn-primary" href="index.php?page=checkout">Tiến hành đặt hàng</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>

