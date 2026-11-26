<?php
declare(strict_types=1);

/**
 * FILE: actions/cart_action.php
 * Nhiệm vụ: Xử lý thêm / cập nhật / xóa sản phẩm trong giỏ hàng Session (POST - Redirect - GET).
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/business_functions.php';

initSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php?page=cart');
    exit;
}

$token = $_POST['csrf_token'] ?? null;
if (!verifyCsrfToken(is_string($token) ? $token : null)) {
    setFlash('error', 'Phiên biểu mẫu không hợp lệ. Vui lòng thử lại.');
    header('Location: ../index.php?page=cart');
    exit;
}

$action = trim((string)($_POST['action'] ?? ''));
$idSp = filter_input(INPUT_POST, 'id_sp', FILTER_VALIDATE_INT);
$qty = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT);
$redirect = trim((string)($_POST['redirect'] ?? 'cart'));

$allowedRedirects = [
    'cart' => '../index.php?page=cart',
    'products' => '../index.php?page=products',
    'home' => '../index.php?page=home',
];
$redirectUrl = $allowedRedirects[$redirect] ?? '../index.php?page=cart';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($action === 'clear') {
    $_SESSION['cart'] = [];
    setFlash('success', 'Đã làm trống giỏ hàng.');
    header('Location: ../index.php?page=cart');
    exit;
}

if ($action === 'remove') {
    if ($idSp !== false && $idSp !== null && isset($_SESSION['cart'][$idSp])) {
        unset($_SESSION['cart'][$idSp]);
        setFlash('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    }
    header('Location: ../index.php?page=cart');
    exit;
}

if ($idSp === false || $idSp === null || $idSp <= 0) {
    setFlash('error', 'Sản phẩm không hợp lệ.');
    header('Location: ' . $redirectUrl);
    exit;
}

try {
    $pdo = getConnection();
    $product = getProductDetailById($pdo, $idSp);

    if ($product === null) {
        setFlash('error', 'Sản phẩm không tồn tại hoặc đã ngừng kinh doanh.');
        header('Location: ' . $redirectUrl);
        exit;
    }

    if ($product['GiaBanHienTai'] === null) {
        setFlash('error', 'Sản phẩm chưa có giá bán hiệu lực, chưa thể đặt mua.');
        header('Location: ' . $redirectUrl);
        exit;
    }

    $maxQty = (int)$product['SoLuongKhaDung'];
    if ($maxQty <= 0) {
        setFlash('error', 'Sản phẩm "' . $product['TenSanPham'] . '" hiện tạm hết nguyên liệu hoa.');
        header('Location: ' . $redirectUrl);
        exit;
    }

    if ($action === 'add') {
        $addQty = ($qty === false || $qty === null || $qty < 1) ? 1 : $qty;
        $currentQty = (int)($_SESSION['cart'][$idSp] ?? 0);
        $newQty = $currentQty + $addQty;

        if ($newQty > $maxQty) {
            $_SESSION['cart'][$idSp] = $maxQty;
            setFlash(
                'error',
                'Kho hoa chỉ đủ đáp ứng tối đa ' . $maxQty . ' sản phẩm "' . $product['TenSanPham'] . '". Đã cập nhật số lượng tối đa.'
            );
        } else {
            $_SESSION['cart'][$idSp] = $newQty;
            setFlash('success', 'Đã thêm "' . $product['TenSanPham'] . '" vào giỏ hàng.');
        }
    } elseif ($action === 'update') {
        if ($qty === false || $qty === null || $qty <= 0) {
            unset($_SESSION['cart'][$idSp]);
            setFlash('success', 'Đã xóa "' . $product['TenSanPham'] . '" khỏi giỏ hàng.');
        } elseif ($qty > $maxQty) {
            $_SESSION['cart'][$idSp] = $maxQty;
            setFlash(
                'error',
                'Số lượng vượt tồn kho hoa tươi. Đã điều chỉnh "' . $product['TenSanPham'] . '" về tối đa ' . $maxQty . '.'
            );
        } else {
            $_SESSION['cart'][$idSp] = $qty;
            setFlash('success', 'Đã cập nhật số lượng cho "' . $product['TenSanPham'] . '".');
        }
    }
} catch (PDOException $exception) {
    error_log('[Hanamiao Cart Action] ' . $exception->getMessage());
    setFlash('error', 'Không thể cập nhật giỏ hàng lúc này. Vui lòng thử lại.');
}

header('Location: ' . $redirectUrl);
exit;

