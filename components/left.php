<?php
declare(strict_types=1);
/**
 * FILE: components/left.php
 * Cột trái Storefront: Cây Menu đa cấp động (từ bảng Menu), danh mục LoaiSanPham & lọc giá nhanh.
 */
$activeLsp = isset($_GET['lsp']) ? (int)$_GET['lsp'] : 0;
$activePrice = isset($_GET['price']) && is_string($_GET['price']) ? $_GET['price'] : '';
?>
<section class="widget">
    <h2 class="widget-title">Danh mục menu</h2>
    <?php if (count($menus) === 0): ?>
        <p class="text-muted text-xs">Chưa có dữ liệu menu.</p>
    <?php else: ?>
        <nav aria-label="Menu đa cấp">
            <?php inMenu($menus, 0, $currentQuery); ?>
        </nav>
    <?php endif; ?>
</section>

<section class="widget">
    <h2 class="widget-title">Loại sản phẩm</h2>
    <?php if (count($productTypes) === 0): ?>
        <p class="text-muted text-xs">Chưa có loại sản phẩm đang bán.</p>
    <?php else: ?>
        <ul class="category-list">
            <li>
                <a class="category-link<?php echo ($page === 'products' && $activeLsp === 0 && $activePrice === '') ? ' active' : ''; ?>" href="index.php?page=products">
                    Tất cả sản phẩm
                </a>
            </li>
            <?php foreach ($productTypes as $type): ?>
                <?php $lspId = (int)$type['ID_LSP']; ?>
                <li>
                    <a class="category-link<?php echo ($page === 'products' && $activeLsp === $lspId) ? ' active' : ''; ?>" href="index.php?page=products&amp;lsp=<?php echo $lspId; ?>">
                        <?php echo h($type['TenLoaiSanPham']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="widget">
    <h2 class="widget-title">Lọc theo mức giá</h2>
    <ul class="category-list">
        <li>
            <a class="category-link<?php echo $activePrice === 'under400' ? ' active' : ''; ?>" href="index.php?page=products&amp;price=under400">
                Dưới 400.000 đ
            </a>
        </li>
        <li>
            <a class="category-link<?php echo $activePrice === '400to600' ? ' active' : ''; ?>" href="index.php?page=products&amp;price=400to600">
                400.000 – 600.000 đ
            </a>
        </li>
        <li>
            <a class="category-link<?php echo $activePrice === 'over600' ? ' active' : ''; ?>" href="index.php?page=products&amp;price=over600">
                Trên 600.000 đ
            </a>
        </li>
    </ul>
</section>

