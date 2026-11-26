<?php
declare(strict_types=1);
/**
 * FILE: components/header.php
 * Mở tài liệu HTML, nạp font Inter, tokens.css, style.css và hiển thị Header + Navbar.
 */
$cartCount = getCartCount();
$rootMenus = layMenuCon($menus, 0);
$searchKeyword = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($pageTitle ?? 'Tiệm hoa Hanamiao'); ?></title>
    <link rel="stylesheet" href="css/fonts.css">
    <link rel="stylesheet" href="css/tokens.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="site-shell">
    <header class="site-header">
        <div class="header-top">
            <div class="site-container header-top-inner">
                <a class="brand" href="index.php?page=home">
                    <span class="brand-mark" aria-hidden="true">H</span>
                    <span class="brand-text">
                        <strong>Hanamiao</strong>
                        <small>Tiệm hoa tươi mỗi ngày</small>
                    </span>
                </a>

                <form class="header-search" action="index.php" method="get" role="search">
                    <input type="hidden" name="page" value="products">
                    <label class="sr-only" for="header-search-input">Tìm kiếm bó hoa</label>
                    <input
                        id="header-search-input"
                        type="search"
                        name="q"
                        class="input-control"
                        placeholder="Tìm bó hoa, giỏ hoa, dịp tặng..."
                        value="<?php echo h($searchKeyword); ?>"
                    >
                    <button type="submit" class="btn btn-outline">Tìm</button>
                </form>

                <div class="header-actions">
                    <a class="btn btn-outline btn-cart-icon" href="index.php?page=cart" aria-label="Giỏ hàng" title="Giỏ hàng">
                        <svg class="cart-svg-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="8" cy="21" r="1"></circle>
                            <circle cx="19" cy="21" r="1"></circle>
                            <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path>
                        </svg>
                        <span class="count-pill tabular-nums"><?php echo $cartCount; ?></span>
                    </a>
                    <a class="btn btn-ghost" href="admin/index.php">Quản trị</a>
                </div>
            </div>
        </div>

        <nav class="site-navbar" aria-label="Điều hướng chính">
            <div class="site-container">
                <ul class="nav-list">
                    <?php if (count($rootMenus) === 0): ?>
                        <li><a class="nav-link active" href="index.php?page=home">Trang chủ</a></li>
                    <?php else: ?>
                        <?php foreach ($rootMenus as $item): ?>
                            <?php
                            $url = trim((string)($item['url'] ?? ''));
                            $isSafe = isSafeInternalUrl($url);
                            $isActive = ($isSafe && str_contains($url, 'page=' . $page));
                            ?>
                            <li>
                                <?php if ($isSafe): ?>
                                    <a class="nav-link<?php echo $isActive ? ' active' : ''; ?>" href="<?php echo h($url); ?>">
                                        <?php echo h($item['TenMenu']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="nav-link nav-link-static"><?php echo h($item['TenMenu']); ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>

