<?php
declare(strict_types=1);

/**
 * FILE: index.php
 * Nhiệm vụ: Bộ định tuyến trung tâm (Front-Controller) của khu vực Storefront Hanamiao.
 */

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/functions/menu_functions.php';
require_once __DIR__ . '/functions/business_functions.php';

initSession();

// Whitelist các trang hợp lệ phía Storefront (chống LFI / Path Traversal)
$pages = [
    'home' => 'pages/home.php',
    'products' => 'pages/products.php',
    'product_detail' => 'pages/product_detail.php',
    'cart' => 'pages/cart.php',
    'checkout' => 'pages/checkout.php',
    'order_lookup' => 'pages/order_lookup.php',
];

$page = $_GET['page'] ?? 'home';
if (!is_string($page) || !isset($pages[$page])) {
    http_response_code(404);
    $page = 'not_found';
}

$pageTitles = [
    'home' => 'Trang chủ',
    'products' => 'Sản phẩm hoa',
    'product_detail' => 'Chi tiết sản phẩm',
    'cart' => 'Giỏ hàng',
    'checkout' => 'Đặt hàng',
    'order_lookup' => 'Tra cứu đơn hàng',
    'not_found' => 'Không tìm thấy trang',
];
$pageTitle = ($pageTitles[$page] ?? 'Trang chủ') . ' — Tiệm hoa Hanamiao';

$pdo = null;
$dbError = '';
$menus = [];
$productTypes = [];

try {
    $pdo = getConnection();
    $menus = getAllMenus($pdo);
    $productTypes = getActiveProductTypes($pdo);
} catch (PDOException $exception) {
    error_log('[Hanamiao Storefront DB] ' . $exception->getMessage());
    $dbError = 'Không thể kết nối cơ sở dữ liệu. Vui lòng kiểm tra MySQL và cấu hình trong config/database.php.';
}

$currentQuery = $_SERVER['QUERY_STRING'] ?? '';
$currentQuery = $currentQuery !== '' ? '?' . $currentQuery : '?page=home';
$flash = getFlash();

require __DIR__ . '/components/header.php';
?>

<div class="site-container">
    <?php if ($flash !== null): ?>
        <div class="alert alert-<?php echo h($flash['type']); ?>" role="status">
            <?php echo h($flash['message']); ?>
        </div>
    <?php endif; ?>

    <?php if ($dbError !== ''): ?>
        <div class="alert alert-error" role="alert">
            <?php echo h($dbError); ?>
        </div>
    <?php endif; ?>

    <?php $showRightSidebar = in_array($page, ['home', 'products'], true); ?>
    <div class="store-layout<?php echo $showRightSidebar ? '' : ' store-layout-wide'; ?>">
        <aside class="sidebar-left" aria-label="Danh mục cửa hàng">
            <?php require __DIR__ . '/components/left.php'; ?>
        </aside>

        <main class="main-content">
            <?php if ($page === 'not_found'): ?>
                <section class="panel empty-state">
                    <h1 class="page-title">404 — Không tìm thấy trang</h1>
                    <p class="text-muted">Đường dẫn bạn truy cập không tồn tại hoặc đã thay đổi.</p>
                    <a class="btn btn-primary" href="index.php?page=home">Về trang chủ</a>
                </section>
            <?php else: ?>
                <?php
                $targetFile = __DIR__ . '/' . $pages[$page];
                if (is_file($targetFile)) {
                    require $targetFile;
                } else {
                    ?>
                    <section class="panel empty-state">
                        <h1 class="page-title"><?php echo h($pageTitles[$page] ?? 'Đang phát triển'); ?></h1>
                        <p class="text-muted">Màn hình này đang được cập nhật.</p>
                        <a class="btn btn-outline" href="index.php?page=home">Quay lại trang chủ</a>
                    </section>
                    <?php
                }
                ?>
            <?php endif; ?>
        </main>

        <?php if ($showRightSidebar): ?>
            <aside class="sidebar-right" aria-label="Thông tin hỗ trợ">
                <?php require __DIR__ . '/components/right.php'; ?>
            </aside>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/components/footer.php'; ?>

