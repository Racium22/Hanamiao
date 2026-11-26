<?php
declare(strict_types=1);

/**
 * FILE: functions/menu_functions.php
 * Nhiệm vụ: Xử lý và hiển thị danh mục Menu đa cấp đệ quy từ bảng Menu (kế thừa Lab 08).
 */

function layMenuCon(array $menus, int $idCha): array
{
    $ketQua = [];
    foreach ($menus as $menu) {
        if ((int)$menu['ID_Cha'] === $idCha) {
            $ketQua[] = $menu;
        }
    }
    return $ketQua;
}

function isSafeInternalUrl(string $url): bool
{
    if ($url === '') {
        return false;
    }
    return (bool)preg_match(
        '/^(?:index\.php)?\?page=[A-Za-z0-9_]+(?:&[A-Za-z0-9_]+=[^<>"\'\s]+)*$/u',
        $url
    );
}

function inMenu(array $menus, int $idCha = 0, string $currentQuery = '', int $depth = 0): void
{
    $menuCon = layMenuCon($menus, $idCha);
    if (count($menuCon) === 0 || $depth > 5) {
        return;
    }

    $ulClass = $depth === 0 ? 'menu-tree menu-root' : 'menu-tree menu-sub level-' . $depth;
    echo '<ul class="' . $ulClass . '">';

    foreach ($menuCon as $menu) {
        $id = (int)$menu['ID'];
        $ten = htmlspecialchars((string)$menu['TenMenu'], ENT_QUOTES, 'UTF-8');
        $url = trim((string)($menu['url'] ?? ''));
        $isActive = ($url !== '' && $currentQuery !== '' && str_ends_with($url, $currentQuery));

        echo '<li class="menu-item">';
        if (isSafeInternalUrl($url)) {
            $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
            $activeClass = $isActive ? ' active' : '';
            echo '<a class="menu-link' . $activeClass . '" href="' . $safeUrl . '">' . $ten . '</a>';
        } else {
            echo '<span class="menu-label">' . $ten . '</span>';
        }

        inMenu($menus, $id, $currentQuery, $depth + 1);
        echo '</li>';
    }

    echo '</ul>';
}

function inTuyChonCha(
    array $menus,
    int $idCha = 0,
    int $cap = 0,
    int $selectedId = 0,
    int $excludeId = 0
): void {
    if ($cap > 5) {
        return;
    }
    $menuCon = layMenuCon($menus, $idCha);
    foreach ($menuCon as $menu) {
        $id = (int)$menu['ID'];
        if ($excludeId > 0 && $id === $excludeId) {
            continue;
        }
        $prefix = str_repeat('-- ', $cap);
        $label = htmlspecialchars($prefix . (string)$menu['TenMenu'], ENT_QUOTES, 'UTF-8');
        $selectedAttr = $selectedId === $id ? ' selected' : '';
        echo '<option value="' . $id . '"' . $selectedAttr . '>' . $label . '</option>';
        inTuyChonCha($menus, $id, $cap + 1, $selectedId, $excludeId);
    }
}

