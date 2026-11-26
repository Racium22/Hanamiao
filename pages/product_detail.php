<?php
declare(strict_types=1);

/**
 * FILE: pages/product_detail.php
 * Chi tiết sản phẩm: giá hiện hành (LichSuGia), thành phần bó hoa (ThanhPhanBo), tồn kho khả dụng & thêm giỏ.
 */

$idSp = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;
$bomList = [];
$relatedProducts = [];

if ($pdo instanceof PDO && $idSp > 0) {
    try {
        $product = getProductDetailById($pdo, $idSp);
        if ($product !== null) {
            $bomList = getProductBom($pdo, $idSp);
            $relatedProducts = searchProductsWithMeta($pdo, [
                'lsp' => (int)$product['ID_LSP'],
                'exclude_id' => $idSp,
                'limit' => 3,
            ]);
        }
    } catch (PDOException $exception) {
        error_log('[Hanamiao Product Detail] ' . $exception->getMessage());
        $dbError = 'Không thể tải chi tiết sản phẩm.';
    }
}
?>

<?php if ($product === null): ?>
    <section class="panel empty-state">
        <h1 class="page-title">Không tìm thấy sản phẩm</h1>
        <p class="text-muted">Mẫu hoa bạn tìm kiếm không tồn tại hoặc hiện đang tạm ngừng kinh doanh.</p>
        <a class="btn btn-primary" href="index.php?page=products">Quay lại danh sách sản phẩm</a>
    </section>
<?php else: ?>
    <?php
    $imgSrc = getImageUrl($product['HinhAnh']);
    $availableQty = (int)$product['SoLuongKhaDung'];
    $hasPrice = $product['GiaBanHienTai'] !== null;
    $canBuy = ($availableQty > 0 && $hasPrice);
    ?>
    <nav class="breadcrumb" aria-label="Đường dẫn">
        <a href="index.php?page=home">Trang chủ</a>
        <span aria-hidden="true">/</span>
        <a href="index.php?page=products&amp;lsp=<?php echo (int)$product['ID_LSP']; ?>">
            <?php echo h($product['TenLoaiSanPham']); ?>
        </a>
        <span aria-hidden="true">/</span>
        <span class="breadcrumb-current"><?php echo h($product['TenSanPham']); ?></span>
    </nav>

    <section class="panel detail-card">
        <div class="detail-grid">
            <div class="detail-media">
                <img src="<?php echo h($imgSrc); ?>" alt="<?php echo h($product['TenSanPham']); ?>">
            </div>

            <div class="detail-info">
                <div class="detail-badges">
                    <span class="badge badge-neutral"><?php echo h($product['TenLoaiSanPham']); ?></span>
                    <span class="badge <?php echo $canBuy ? 'badge-success' : 'badge-error'; ?> tabular-nums">
                        <?php echo $canBuy ? ('Đủ hoa cho ' . $availableQty . ' sản phẩm') : 'Tạm hết nguyên liệu'; ?>
                    </span>
                </div>

                <h1 class="detail-title"><?php echo h($product['TenSanPham']); ?></h1>
                <p class="detail-price tabular-nums"><?php echo h(formatMoney($product['GiaBanHienTai'])); ?></p>

                <p class="detail-desc">
                    <?php
                    $moTa = trim((string)($product['MoTa'] ?? ''));
                    echo $moTa !== '' ? nl2br(h($moTa)) : 'Mẫu hoa tươi được kết thủ công tại tiệm hoa Hanamiao.';
                    ?>
                </p>

                <?php if ($canBuy): ?>
                    <form class="add-cart-form" action="actions/cart_action.php" method="post">
                        <?php echo csrfInput(); ?>
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="id_sp" value="<?php echo (int)$product['ID_SP']; ?>">
                        <input type="hidden" name="redirect" value="cart">

                        <div class="qty-field">
                            <label for="detail-qty" class="field-label">Số lượng</label>
                            <div class="qty-stepper">
                                <button type="button" class="qty-btn" data-qty-step="-1" aria-label="Giảm số lượng">&minus;</button>
                                <input
                                    id="detail-qty"
                                    type="number"
                                    name="qty"
                                    class="input-control qty-input tabular-nums"
                                    value="1"
                                    min="1"
                                    max="<?php echo $availableQty; ?>"
                                    required
                                >
                                <button type="button" class="qty-btn" data-qty-step="1" aria-label="Tăng số lượng">+</button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-add-cart">Thêm vào giỏ hàng</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-error detail-unavailable">
                        <?php echo !$hasPrice
                            ? 'Sản phẩm hiện chưa có mức giá hiệu lực. Vui lòng liên hệ tiệm hoa để được báo giá.'
                            : 'Một hoặc nhiều loại hoa trong công thức bó hiện không đủ số lượng tồn kho.'; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="section-header section-header-compact">
            <div>
                <h2 class="section-title">Thành phần bó hoa (Định mức chuẩn)</h2>
                <p class="section-subtitle">Danh sách các loại hoa tươi và số bông dùng để kết 1 sản phẩm hoàn chỉnh.</p>
            </div>
        </div>

        <?php if (count($bomList) === 0): ?>
            <p class="text-muted">Chưa cập nhật định mức thành phần hoa cho sản phẩm này.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="store-table">
                    <thead>
                        <tr>
                            <th>Nguyên liệu hoa</th>
                            <th>Loại hoa</th>
                            <th class="text-right">Định mức / bó</th>
                            <th class="text-right">Tình trạng kho</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bomList as $part): ?>
                            <?php
                            $soBong = (int)$part['SoBong'];
                            $ton = (int)$part['SoLuongTon'];
                            $enough = ((int)$part['TrangThai'] === 1 && $ton >= $soBong);
                            ?>
                            <tr>
                                <td class="font-medium"><?php echo h($part['TenHoa']); ?></td>
                                <td class="text-muted"><?php echo h($part['TenLH']); ?></td>
                                <td class="text-right tabular-nums"><?php echo $soBong; ?> bông</td>
                                <td class="text-right">
                                    <span class="badge <?php echo $enough ? 'badge-success' : 'badge-error'; ?> tabular-nums">
                                        <?php echo $enough ? ('Còn ' . $ton . ' bông') : ('Thiếu (' . $ton . ' bông)'); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <?php if (count($relatedProducts) > 0): ?>
        <section class="content-section">
            <div class="section-header">
                <h2 class="section-title">Sản phẩm cùng danh mục</h2>
                <a class="link-inline" href="index.php?page=products&amp;lsp=<?php echo (int)$product['ID_LSP']; ?>">Xem thêm</a>
            </div>
            <div class="product-grid">
                <?php foreach ($relatedProducts as $rel): ?>
                    <?php
                    $relId = (int)$rel['ID_SP'];
                    $relStock = (int)$rel['SoLuongKhaDung'] > 0;
                    ?>
                    <article class="product-card">
                        <a class="product-thumb" href="index.php?page=product_detail&amp;id=<?php echo $relId; ?>">
                            <img src="<?php echo h(getImageUrl($rel['HinhAnh'])); ?>" alt="<?php echo h($rel['TenSanPham']); ?>" loading="lazy">
                        </a>
                        <div class="product-body">
                            <div class="product-meta">
                                <span class="product-category"><?php echo h($rel['TenLoaiSanPham']); ?></span>
                                <span class="badge <?php echo $relStock ? 'badge-success' : 'badge-error'; ?>">
                                    <?php echo $relStock ? 'Sẵn hoa' : 'Tạm hết'; ?>
                                </span>
                            </div>
                            <h3 class="product-title">
                                <a href="index.php?page=product_detail&amp;id=<?php echo $relId; ?>">
                                    <?php echo h($rel['TenSanPham']); ?>
                                </a>
                            </h3>
                            <p class="product-price tabular-nums"><?php echo h(formatMoney($rel['GiaBanHienTai'])); ?></p>
                            <div class="product-footer">
                                <a class="btn btn-outline btn-block" href="index.php?page=product_detail&amp;id=<?php echo $relId; ?>">Xem chi tiết</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
<?php endif; ?>

