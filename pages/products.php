<?php
declare(strict_types=1);

/**
 * FILE: pages/products.php
 * Danh sách sản phẩm Storefront: hỗ trợ tìm kiếm, lọc theo LoaiSanPham, khoảng giá và sắp xếp.
 */

$selectedLsp = isset($_GET['lsp']) ? max(0, (int)$_GET['lsp']) : 0;
$keyword = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$priceRange = isset($_GET['price']) && is_string($_GET['price']) ? trim($_GET['price']) : '';
$sort = isset($_GET['sort']) && is_string($_GET['sort']) ? trim($_GET['sort']) : 'default';

$allowedPrices = ['', 'under400', '400to600', 'over600'];
if (!in_array($priceRange, $allowedPrices, true)) {
    $priceRange = '';
}

$allowedSorts = ['default', 'price_asc', 'price_desc', 'newest'];
if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'default';
}

$products = [];
if ($pdo instanceof PDO) {
    try {
        $products = searchProductsWithMeta($pdo, [
            'lsp' => $selectedLsp,
            'q' => $keyword,
            'price' => $priceRange,
            'sort' => $sort,
            'limit' => 48,
        ]);
    } catch (PDOException $exception) {
        error_log('[Hanamiao Products List] ' . $exception->getMessage());
        $dbError = 'Không thể tải danh sách sản phẩm lúc này.';
    }
}

$hasActiveFilters = ($selectedLsp > 0 || $keyword !== '' || $priceRange !== '' || $sort !== 'default');
$selectedLspName = '';
foreach ($productTypes as $type) {
    if ((int)$type['ID_LSP'] === $selectedLsp) {
        $selectedLspName = (string)$type['TenLoaiSanPham'];
        break;
    }
}
?>

<section class="content-section">
    <div class="section-header">
        <div>
            <h1 class="page-title">
                <?php echo $selectedLspName !== '' ? h($selectedLspName) : 'Tất cả sản phẩm hoa'; ?>
            </h1>
            <p class="section-subtitle tabular-nums">
                Tìm thấy <strong><?php echo count($products); ?></strong> mẫu hoa đang mở bán.
            </p>
        </div>
        <?php if ($hasActiveFilters): ?>
            <a class="link-inline" href="index.php?page=products">Xóa bộ lọc</a>
        <?php endif; ?>
    </div>

    <form class="filter-bar panel" action="index.php" method="get">
        <input type="hidden" name="page" value="products">

        <div class="filter-group">
            <label for="filter-q" class="field-label">Từ khóa</label>
            <input
                id="filter-q"
                type="search"
                name="q"
                class="input-control"
                placeholder="Tên bó hoa, loại hoa..."
                value="<?php echo h($keyword); ?>"
            >
        </div>

        <div class="filter-group">
            <label for="filter-lsp" class="field-label">Loại sản phẩm</label>
            <select id="filter-lsp" name="lsp" class="input-control">
                <option value="0">Tất cả loại</option>
                <?php foreach ($productTypes as $type): ?>
                    <?php $idLsp = (int)$type['ID_LSP']; ?>
                    <option value="<?php echo $idLsp; ?>"<?php echo $selectedLsp === $idLsp ? ' selected' : ''; ?>>
                        <?php echo h($type['TenLoaiSanPham']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label for="filter-price" class="field-label">Khoảng giá</label>
            <select id="filter-price" name="price" class="input-control">
                <option value="">Tất cả mức giá</option>
                <option value="under400"<?php echo $priceRange === 'under400' ? ' selected' : ''; ?>>Dưới 400.000 đ</option>
                <option value="400to600"<?php echo $priceRange === '400to600' ? ' selected' : ''; ?>>400.000 – 600.000 đ</option>
                <option value="over600"<?php echo $priceRange === 'over600' ? ' selected' : ''; ?>>Trên 600.000 đ</option>
            </select>
        </div>

        <div class="filter-group">
            <label for="filter-sort" class="field-label">Sắp xếp</label>
            <select id="filter-sort" name="sort" class="input-control">
                <option value="default"<?php echo $sort === 'default' ? ' selected' : ''; ?>>Mặc định</option>
                <option value="price_asc"<?php echo $sort === 'price_asc' ? ' selected' : ''; ?>>Giá thấp đến cao</option>
                <option value="price_desc"<?php echo $sort === 'price_desc' ? ' selected' : ''; ?>>Giá cao đến thấp</option>
                <option value="newest"<?php echo $sort === 'newest' ? ' selected' : ''; ?>>Mới nhất</option>
            </select>
        </div>

        <div class="filter-submit">
            <button type="submit" class="btn btn-primary btn-block">Lọc</button>
        </div>
    </form>

    <?php if (count($products) === 0): ?>
        <div class="panel empty-state">
            <h2 class="empty-title">Không tìm thấy sản phẩm phù hợp</h2>
            <p class="text-muted">Thử thay đổi từ khóa, chọn lại loại sản phẩm hoặc mở rộng khoảng giá.</p>
            <a class="btn btn-outline" href="index.php?page=products">Xem tất cả sản phẩm</a>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <?php
                $idSp = (int)$product['ID_SP'];
                $imgSrc = getImageUrl($product['HinhAnh']);
                $availableQty = (int)$product['SoLuongKhaDung'];
                $hasPrice = $product['GiaBanHienTai'] !== null;
                $canBuy = ($availableQty > 0 && $hasPrice);
                ?>
                <article class="product-card">
                    <a class="product-thumb" href="index.php?page=product_detail&amp;id=<?php echo $idSp; ?>">
                        <img src="<?php echo h($imgSrc); ?>" alt="<?php echo h($product['TenSanPham']); ?>" loading="lazy">
                    </a>
                    <div class="product-body">
                        <div class="product-meta">
                            <span class="product-category"><?php echo h($product['TenLoaiSanPham']); ?></span>
                            <span class="badge <?php echo $canBuy ? 'badge-success' : 'badge-error'; ?>">
                                <?php echo $canBuy ? ('Sẵn ' . $availableQty . ' bó') : 'Tạm hết'; ?>
                            </span>
                        </div>
                        <h2 class="product-title">
                            <a href="index.php?page=product_detail&amp;id=<?php echo $idSp; ?>">
                                <?php echo h($product['TenSanPham']); ?>
                            </a>
                        </h2>
                        <p class="product-price tabular-nums"><?php echo h(formatMoney($product['GiaBanHienTai'])); ?></p>
                        <div class="product-footer product-footer-dual">
                            <a class="btn btn-outline" href="index.php?page=product_detail&amp;id=<?php echo $idSp; ?>">
                                Chi tiết
                            </a>
                            <?php if ($canBuy): ?>
                                <form action="actions/cart_action.php" method="post">
                                    <?php echo csrfInput(); ?>
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="id_sp" value="<?php echo $idSp; ?>">
                                    <input type="hidden" name="qty" value="1">
                                    <input type="hidden" name="redirect" value="products">
                                    <button type="submit" class="btn btn-primary btn-block">Thêm giỏ</button>
                                </form>
                            <?php else: ?>
                                <button type="button" class="btn btn-outline btn-block" disabled>Tạm hết</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

