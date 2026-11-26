<?php
declare(strict_types=1);

/**
 * FILE: admin/index.php
 * Bộ định tuyến trung tâm khu vực Quản trị Hanamiao (độc lập giao diện & có bảo vệ đăng nhập).
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/menu_functions.php';
require_once __DIR__ . '/../functions/business_functions.php';

requireAdminLogin('login.php');

$currentAdmin = getCurrentAdmin();

// Whitelist các trang quản trị
$adminPages = [
    'dashboard' => ['file' => 'pages/dashboard.php', 'title' => 'Tổng quan', 'roles' => ['admin', 'nhanvien']],
    'orders' => ['file' => 'pages/orders.php', 'title' => 'Quản lý đơn hàng', 'roles' => ['admin', 'nhanvien']],
    'product_types' => ['file' => 'pages/product_types.php', 'title' => 'Loại sản phẩm', 'roles' => ['admin', 'nhanvien']],
    'products' => ['file' => 'pages/products.php', 'title' => 'Sản phẩm hoa', 'roles' => ['admin', 'nhanvien']],
    'product_bom' => ['file' => 'pages/product_bom.php', 'title' => 'Thành phần bó hoa', 'roles' => ['admin', 'nhanvien']],
    'product_prices' => ['file' => 'pages/product_prices.php', 'title' => 'Lịch sử giá bán', 'roles' => ['admin', 'nhanvien']],
    'flower_types' => ['file' => 'pages/flower_types.php', 'title' => 'Loại hoa nguyên liệu', 'roles' => ['admin', 'nhanvien']],
    'flowers' => ['file' => 'pages/flowers.php', 'title' => 'Kho hoa tươi', 'roles' => ['admin', 'nhanvien']],
    'suppliers' => ['file' => 'pages/suppliers.php', 'title' => 'Nhà cung cấp', 'roles' => ['admin', 'nhanvien']],
    'imports' => ['file' => 'pages/imports.php', 'title' => 'Phiếu nhập kho', 'roles' => ['admin', 'nhanvien']],
    'wastage' => ['file' => 'pages/wastage.php', 'title' => 'Ghi nhận hao hụt', 'roles' => ['admin', 'nhanvien']],
    'menus' => ['file' => 'pages/menus.php', 'title' => 'Quản lý menu đa cấp', 'roles' => ['admin']],
    'users' => ['file' => 'pages/users.php', 'title' => 'Quản lý người dùng', 'roles' => ['admin']],
];

$page = $_GET['page'] ?? 'dashboard';
if (!is_string($page) || !isset($adminPages[$page])) {
    http_response_code(404);
    $page = 'not_found';
} elseif (!in_array($currentAdmin['Quyen'], $adminPages[$page]['roles'], true)) {
    http_response_code(403);
    $page = 'forbidden';
}

$pageTitle = $page === 'not_found'
    ? 'Không tìm thấy trang'
    : ($page === 'forbidden' ? 'Không có quyền truy cập' : $adminPages[$page]['title']);

$pdo = null;
$dbError = '';
try {
    $pdo = getConnection();
} catch (PDOException $exception) {
    error_log('[Hanamiao Admin DB] ' . $exception->getMessage());
    $dbError = 'Không thể kết nối cơ sở dữ liệu hanamiao_db.';
}

$flash = getFlash();

require __DIR__ . '/components/header.php';
?>
<div class="admin-workspace">
    <?php require __DIR__ . '/components/sidebar.php'; ?>

    <main class="admin-main" id="adminMain">
        <?php if ($flash !== null): ?>
            <div class="admin-alert admin-alert-<?php echo h($flash['type']); ?>" role="status">
                <?php echo h($flash['message']); ?>
            </div>
        <?php endif; ?>

        <?php if ($dbError !== ''): ?>
            <div class="admin-alert admin-alert-error" role="alert">
                <?php echo h($dbError); ?>
            </div>
        <?php endif; ?>

        <?php if ($page === 'not_found'): ?>
            <section class="admin-card admin-empty">
                <h1 class="admin-page-title">404 — Không tìm thấy chức năng</h1>
                <p class="admin-muted">Đường dẫn quản trị không tồn tại trong hệ thống.</p>
                <a class="admin-btn admin-btn-outline" href="index.php?page=dashboard">Về tổng quan</a>
            </section>
        <?php elseif ($page === 'forbidden'): ?>
            <section class="admin-card admin-empty">
                <h1 class="admin-page-title">403 — Không có quyền truy cập</h1>
                <p class="admin-muted">Tài khoản của bạn không được cấp quyền truy cập màn hình này.</p>
                <a class="admin-btn admin-btn-outline" href="index.php?page=dashboard">Về tổng quan</a>
            </section>
        <?php else: ?>
            <?php
            $targetFile = __DIR__ . '/' . $adminPages[$page]['file'];
            if (is_file($targetFile)) {
                require $targetFile;
            } else {
                ?>
                <section class="admin-card admin-empty">
                    <h1 class="admin-page-title"><?php echo h($pageTitle); ?></h1>
                    <p class="admin-muted">Phân hệ này đã được khai báo trong lộ trình Phase 3 / Phase 4 và sẽ được triển khai ở bước tiếp theo.</p>
                    <a class="admin-btn admin-btn-outline" href="index.php?page=dashboard">Quay lại tổng quan</a>
                </section>
                <?php
            }
            ?>
        <?php endif; ?>
    </main>
</div>
<?php require __DIR__ . '/components/footer.php'; ?>

