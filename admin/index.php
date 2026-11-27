<?php
declare(strict_types=1);

/**
 * FILE: admin/index.php
 * Bộ định tuyến trung tâm khu vực Quản trị Hanamiao (độc lập giao diện, bảo vệ đăng nhập & tích hợp Thông báo).
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/menu_functions.php';
require_once __DIR__ . '/../functions/business_functions.php';

requireAdminLogin('login.php');

$currentAdmin = getCurrentAdmin();

// Đánh dấu đã đọc khi mở thông báo từ panel chuông trên Header
if (isset($_GET['read_noti']) && is_string($_GET['read_noti'])) {
    $notiKey = trim($_GET['read_noti']);
    if ($notiKey !== '' && preg_match('/^[a-z0-9_]+$/i', $notiKey)) {
        if (!isset($_SESSION['admin_read_notis']) || !is_array($_SESSION['admin_read_notis'])) {
            $_SESSION['admin_read_notis'] = [];
        }
        if (!in_array($notiKey, $_SESSION['admin_read_notis'], true)) {
            $_SESSION['admin_read_notis'][] = $notiKey;
        }
    }
}

// Whitelist các trang quản trị
$adminPages = [
    'dashboard' => ['file' => 'pages/dashboard.php', 'title' => 'Tổng quan', 'roles' => ['admin', 'nhanvien']],
    'notifications' => ['file' => 'pages/notifications.php', 'title' => 'Trung tâm thông báo', 'roles' => ['admin', 'nhanvien']],
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
$adminNotifications = [];
$unreadNotiCount = 0;

$readNotiKeys = isset($_SESSION['admin_read_notis']) && is_array($_SESSION['admin_read_notis'])
    ? $_SESSION['admin_read_notis']
    : [];

try {
    $pdo = getConnection();
    $adminNotifications = getAdminNotifications($pdo, $readNotiKeys);
    foreach ($adminNotifications as $n) {
        if (!$n['is_read']) {
            $unreadNotiCount++;
        }
    }
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
        <div class="admin-toast-region" aria-live="polite">
            <?php if ($flash !== null): ?>
                <div class="admin-alert admin-alert-dismissible admin-alert-<?php echo h($flash['type']); ?>" data-toast-auto="<?php echo $flash['type'] === 'success' ? '1' : '0'; ?>">
                    <span class="admin-alert-text"><?php echo h($flash['message']); ?></span>
                    <button type="button" class="admin-alert-close" data-dismiss-alert aria-label="Đóng thông báo">
                        <i data-lucide="x" class="admin-btn-icon" aria-hidden="true"></i>
                    </button>
                </div>
            <?php endif; ?>
        </div>

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
                    <p class="admin-muted">Phân hệ này đang được cập nhật.</p>
                    <a class="admin-btn admin-btn-outline" href="index.php?page=dashboard">Quay lại tổng quan</a>
                </section>
                <?php
            }
            ?>
        <?php endif; ?>
    </main>
</div>
<?php require __DIR__ . '/components/footer.php'; ?>
