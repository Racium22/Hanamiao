<?php
declare(strict_types=1);
/**
 * FILE: pages/home.php
 * Trang chủ Storefront: Banner chào mừng và danh sách sản phẩm nổi bật đọc trực tiếp từ MySQL.
 */
$featuredProducts = [];
if ($pdo instanceof PDO) {
    try {
        $featuredProducts = getActiveProductsWithMeta($pdo, 6);
    } catch (PDOException $exception) {
        error_log('[Hanamiao Home Products] ' . $exception->getMessage());
        $dbError = 'Không thể tải danh sách sản phẩm từ cơ sở dữ liệu.';
    }
}
?>
<section class="hero-banner">
    <img src="images/banner/hero-hanamiao.jpg" alt="Tiệm hoa tươi Hanamiao - Gửi trọn cảm xúc trong từng bó hoa">
    <div class="hero-overlay">
        <span class="hero-kicker">TIỆM HOA TƯƠI HANAMIAO</span>
        <h2 class="hero-heading">Gửi trọn cảm xúc trong từng bó hoa</h2>
        <p class="hero-copy">Hoa tươi nhập mới mỗi sáng &middot; Đúng định mức số bông &middot; Giao nhanh đúng hẹn</p>
        <a class="btn btn-primary" href="index.php?page=products">Khám phá mẫu hoa</a>
    </div>
</section>

<section class="content-section">
    <div class="section-header">
        <div>
            <h1 class="page-title">Sản phẩm nổi bật</h1>
            <p class="section-subtitle">Giá bán cập nhật theo lịch sử giá hiện hành và kiểm tra nguyên liệu trực tiếp từ kho hoa.</p>
        </div>
        <a class="link-inline" href="index.php?page=products">Xem tất cả</a>
    </div>

    <?php if (count($featuredProducts) === 0): ?>
        <div class="panel empty-state">
            <h2 class="empty-title">Chưa có sản phẩm nào đang mở bán</h2>
            <p class="text-muted">Hãy kiểm tra dữ liệu bảng SanPham hoặc nhập thêm sản phẩm trong khu vực Quản trị.</p>
            <a class="btn btn-outline" href="admin/index.php">Mở trang quản trị</a>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($featuredProducts as $product): ?>
                <?php
                $idSp = (int)$product['ID_SP'];
                $imgSrc = getImageUrl($product['HinhAnh']);
                $availableQty = (int)$product['SoLuongKhaDung'];
                $isInStock = $availableQty > 0;
                ?>
                <article class="product-card">
                    <a class="product-thumb" href="index.php?page=product_detail&amp;id=<?php echo $idSp; ?>">
                        <img src="<?php echo h($imgSrc); ?>" alt="<?php echo h($product['TenSanPham']); ?>" loading="lazy">
                    </a>
                    <div class="product-body">
                        <div class="product-meta">
                            <span class="product-category"><?php echo h($product['TenLoaiSanPham']); ?></span>
                            <span class="badge <?php echo $isInStock ? 'badge-success' : 'badge-error'; ?>">
                                <?php echo $isInStock ? 'Sẵn hoa' : 'Tạm hết'; ?>
                            </span>
                        </div>
                        <h2 class="product-title">
                            <a href="index.php?page=product_detail&amp;id=<?php echo $idSp; ?>">
                                <?php echo h($product['TenSanPham']); ?>
                            </a>
                        </h2>
                        <p class="product-price tabular-nums"><?php echo h(formatMoney($product['GiaBanHienTai'])); ?></p>
                        <div class="product-footer">
                            <a class="btn btn-outline btn-block" href="index.php?page=product_detail&amp;id=<?php echo $idSp; ?>">
                                Xem chi tiết
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

