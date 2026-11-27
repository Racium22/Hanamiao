<?php
declare(strict_types=1);

/**
 * FILE: admin/actions/menu_save.php
 * Xử lý thêm / sửa / xóa bảng Menu đa cấp (chỉ quyền admin, kiểm tra menu con & URL nội bộ).
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/helpers.php';
require_once __DIR__ . '/../../functions/menu_functions.php';

requireRole(['admin'], '../index.php?page=dashboard');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php?page=menus');
    exit;
}

if (!verifyCsrfToken(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
    setFlash('error', 'Phiên biểu mẫu không hợp lệ.');
    header('Location: ../index.php?page=menus');
    exit;
}

$action = trim((string)($_POST['action'] ?? 'save'));
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;

function isDescendantMenu(array $menus, int $candidateParentId, int $nodeId): bool
{
    if ($candidateParentId === 0 || $nodeId <= 0) {
        return false;
    }
    $byId = [];
    foreach ($menus as $m) {
        $byId[(int)$m['ID']] = (int)$m['ID_Cha'];
    }
    $cur = $candidateParentId;
    $guard = 0;
    while ($cur > 0 && $guard < 20) {
        if ($cur === $nodeId) {
            return true;
        }
        $cur = $byId[$cur] ?? 0;
        $guard++;
    }
    return false;
}

try {
    $pdo = getConnection();

    if ($action === 'delete') {
        if ($id <= 0) {
            setFlash('error', 'Mã menu không hợp lệ.');
            header('Location: ../index.php?page=menus');
            exit;
        }

        $childStmt = $pdo->prepare('SELECT COUNT(*) FROM Menu WHERE ID_Cha = ?');
        $childStmt->execute([$id]);
        if ((int)$childStmt->fetchColumn() > 0) {
            setFlash('error', 'Không thể xóa menu này vì vẫn còn menu con bên dưới. Hãy xóa hoặc chuyển menu con trước.');
            header('Location: ../index.php?page=menus');
            exit;
        }

        $delStmt = $pdo->prepare('DELETE FROM Menu WHERE ID = ?');
        $delStmt->execute([$id]);
        setFlash('success', 'Đã xóa danh mục menu.');
        header('Location: ../index.php?page=menus');
        exit;
    }

    $tenMenu = trim((string)($_POST['ten_menu'] ?? ''));
    $idCha = filter_input(INPUT_POST, 'id_cha', FILTER_VALIDATE_INT);
    $moTa = trim((string)($_POST['mo_ta'] ?? ''));
    $url = trim((string)($_POST['url'] ?? ''));

    if ($tenMenu === '') {
        setFlash('error', 'Tên danh mục menu không được để trống.');
        header('Location: ../index.php?page=menus' . ($id > 0 ? '&edit=' . $id : ''));
        exit;
    }

    if ($idCha === false || $idCha === null || $idCha < 0) {
        setFlash('error', 'Danh mục cha không hợp lệ.');
        header('Location: ../index.php?page=menus' . ($id > 0 ? '&edit=' . $id : ''));
        exit;
    }

    if ($url !== '' && !isSafeInternalUrl($url)) {
        setFlash('error', 'URL phải có dạng nội bộ hợp lệ (ví dụ: index.php?page=products&lsp=1).');
        header('Location: ../index.php?page=menus' . ($id > 0 ? '&edit=' . $id : ''));
        exit;
    }

    if ($idCha > 0) {
        $parentStmt = $pdo->prepare('SELECT COUNT(*) FROM Menu WHERE ID = ?');
        $parentStmt->execute([$idCha]);
        if ((int)$parentStmt->fetchColumn() === 0) {
            setFlash('error', 'Danh mục cha không tồn tại.');
            header('Location: ../index.php?page=menus' . ($id > 0 ? '&edit=' . $id : ''));
            exit;
        }
    }

    if ($id > 0) {
        $allMenus = $pdo->query('SELECT ID, ID_Cha FROM Menu')->fetchAll();
        if ($idCha === $id || isDescendantMenu($allMenus, $idCha, $id)) {
            setFlash('error', 'Không thể chọn chính nó hoặc menu con làm danh mục cha.');
            header('Location: ../index.php?page=menus&edit=' . $id);
            exit;
        }

        $updStmt = $pdo->prepare('UPDATE Menu SET ID_Cha = ?, TenMenu = ?, MoTa = ?, url = ? WHERE ID = ?');
        $updStmt->execute([
            $idCha,
            $tenMenu,
            $moTa === '' ? null : $moTa,
            $url === '' ? null : $url,
            $id,
        ]);
        setFlash('success', 'Đã cập nhật danh mục menu.');
    } else {
        $insStmt = $pdo->prepare('INSERT INTO Menu (ID_Cha, TenMenu, MoTa, url) VALUES (?, ?, ?, ?)');
        $insStmt->execute([
            $idCha,
            $tenMenu,
            $moTa === '' ? null : $moTa,
            $url === '' ? null : $url,
        ]);
        setFlash('success', 'Đã thêm danh mục menu "' . $tenMenu . '".');
    }
} catch (PDOException $exception) {
    error_log('[Hanamiao Menu Save] ' . $exception->getMessage());
    setFlash('error', 'Không thể lưu danh mục menu.');
}

header('Location: ../index.php?page=menus');
exit;

