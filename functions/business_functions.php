<?php
declare(strict_types=1);

/**
 * FILE: functions/business_functions.php
 * Nhiệm vụ: Các truy vấn nghiệp vụ dùng chung (LichSuGia hiện hành, sức chứa theo ThanhPhanBo, giỏ hàng, đơn hàng).
 */

function getCurrentPrice(PDO $pdo, int $idSP): ?float
{
    $sql = "SELECT GiaBan
            FROM LichSuGia
            WHERE ID_SP = ?
              AND NgayBatDau <= NOW()
              AND (NgayKetThuc IS NULL OR NgayKetThuc > NOW())
            ORDER BY (NgayKetThuc IS NULL) DESC, NgayBatDau DESC, ID_GB DESC
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idSP]);
    $row = $stmt->fetch();
    return $row ? (float)$row['GiaBan'] : null;
}

function getMaxAvailableQty(PDO $pdo, int $idSP): int
{
    $sql = "SELECT COUNT(*) AS total_parts,
                   MIN(
                       CASE
                           WHEN h.TrangThai = 1 AND t.SoBong > 0 THEN FLOOR(h.SoLuongTon / t.SoBong)
                           ELSE 0
                       END
                   ) AS max_qty
            FROM ThanhPhanBo t
            INNER JOIN Hoa h ON h.ID_H = t.ID_H
            WHERE t.ID_SP = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idSP]);
    $row = $stmt->fetch();

    if (!$row || (int)$row['total_parts'] === 0) {
        return 0;
    }

    return max(0, (int)($row['max_qty'] ?? 0));
}

function getActiveProductsWithMeta(PDO $pdo, int $limit = 6, ?int $idLSP = null): array
{
    return searchProductsWithMeta($pdo, [
        'lsp' => $idLSP ?? 0,
        'limit' => $limit,
    ]);
}

function searchProductsWithMeta(PDO $pdo, array $filters = []): array
{
    $params = [];
    $where = ['sp.TrangThai = 1', 'lsp.TrangThai = 1'];

    $idLSP = (int)($filters['lsp'] ?? 0);
    if ($idLSP > 0) {
        $where[] = 'sp.ID_LSP = ?';
        $params[] = $idLSP;
    }

    $keyword = trim((string)($filters['q'] ?? ''));
    if ($keyword !== '') {
        $where[] = '(sp.TenSanPham LIKE ? OR sp.MoTa LIKE ?)';
        $like = '%' . $keyword . '%';
        $params[] = $like;
        $params[] = $like;
    }

    $excludeId = (int)($filters['exclude_id'] ?? 0);
    if ($excludeId > 0) {
        $where[] = 'sp.ID_SP <> ?';
        $params[] = $excludeId;
    }

    $whereSql = implode(' AND ', $where);

    $having = [];
    $priceRange = (string)($filters['price'] ?? '');
    if ($priceRange === 'under400') {
        $having[] = 'GiaBanHienTai IS NOT NULL AND GiaBanHienTai < 400000';
    } elseif ($priceRange === '400to600') {
        $having[] = 'GiaBanHienTai IS NOT NULL AND GiaBanHienTai BETWEEN 400000 AND 600000';
    } elseif ($priceRange === 'over600') {
        $having[] = 'GiaBanHienTai IS NOT NULL AND GiaBanHienTai > 600000';
    }
    $havingSql = count($having) > 0 ? ('HAVING ' . implode(' AND ', $having)) : '';

    $sort = (string)($filters['sort'] ?? 'default');
    $orderSql = match ($sort) {
        'price_asc' => 'ORDER BY (GiaBanHienTai IS NULL) ASC, GiaBanHienTai ASC, sp.ID_SP ASC',
        'price_desc' => 'ORDER BY (GiaBanHienTai IS NULL) ASC, GiaBanHienTai DESC, sp.ID_SP ASC',
        'newest' => 'ORDER BY sp.ID_SP DESC',
        default => 'ORDER BY sp.ID_SP ASC',
    };

    $limit = (int)($filters['limit'] ?? 48);
    $limit = max(1, min(100, $limit));

    $sql = "SELECT
                sp.ID_SP,
                sp.TenSanPham,
                sp.ID_LSP,
                sp.HinhAnh,
                sp.MoTa,
                lsp.TenLoaiSanPham,
                (
                    SELECT g.GiaBan
                    FROM LichSuGia g
                    WHERE g.ID_SP = sp.ID_SP
                      AND g.NgayBatDau <= NOW()
                      AND (g.NgayKetThuc IS NULL OR g.NgayKetThuc > NOW())
                    ORDER BY (g.NgayKetThuc IS NULL) DESC, g.NgayBatDau DESC, g.ID_GB DESC
                    LIMIT 1
                ) AS GiaBanHienTai,
                COALESCE(
                    (
                        SELECT MIN(
                            CASE
                                WHEN h.TrangThai = 1 AND t.SoBong > 0 THEN FLOOR(h.SoLuongTon / t.SoBong)
                                ELSE 0
                            END
                        )
                        FROM ThanhPhanBo t
                        INNER JOIN Hoa h ON h.ID_H = t.ID_H
                        WHERE t.ID_SP = sp.ID_SP
                    ),
                    0
                ) AS SoLuongKhaDung
            FROM SanPham sp
            INNER JOIN LoaiSanPham lsp ON lsp.ID_LSP = sp.ID_LSP
            WHERE {$whereSql}
            {$havingSql}
            {$orderSql}
            LIMIT {$limit}";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getProductDetailById(PDO $pdo, int $idSP): ?array
{
    if ($idSP <= 0) {
        return null;
    }

    $sql = "SELECT
                sp.ID_SP,
                sp.TenSanPham,
                sp.ID_LSP,
                sp.HinhAnh,
                sp.MoTa,
                sp.TrangThai,
                lsp.TenLoaiSanPham,
                (
                    SELECT g.GiaBan
                    FROM LichSuGia g
                    WHERE g.ID_SP = sp.ID_SP
                      AND g.NgayBatDau <= NOW()
                      AND (g.NgayKetThuc IS NULL OR g.NgayKetThuc > NOW())
                    ORDER BY (g.NgayKetThuc IS NULL) DESC, g.NgayBatDau DESC, g.ID_GB DESC
                    LIMIT 1
                ) AS GiaBanHienTai,
                COALESCE(
                    (
                        SELECT MIN(
                            CASE
                                WHEN h.TrangThai = 1 AND t.SoBong > 0 THEN FLOOR(h.SoLuongTon / t.SoBong)
                                ELSE 0
                            END
                        )
                        FROM ThanhPhanBo t
                        INNER JOIN Hoa h ON h.ID_H = t.ID_H
                        WHERE t.ID_SP = sp.ID_SP
                    ),
                    0
                ) AS SoLuongKhaDung
            FROM SanPham sp
            INNER JOIN LoaiSanPham lsp ON lsp.ID_LSP = sp.ID_LSP
            WHERE sp.ID_SP = ? AND sp.TrangThai = 1 AND lsp.TrangThai = 1
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idSP]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getProductBom(PDO $pdo, int $idSP): array
{
    $sql = "SELECT
                t.ID_TPB,
                t.ID_H,
                t.SoBong,
                h.TenHoa,
                h.SoLuongTon,
                h.HinhAnh,
                h.TrangThai,
                lh.TenLH
            FROM ThanhPhanBo t
            INNER JOIN Hoa h ON h.ID_H = t.ID_H
            INNER JOIN LoaiHoa lh ON lh.ID_LH = h.ID_LH
            WHERE t.ID_SP = ?
            ORDER BY t.ID_TPB ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idSP]);
    return $stmt->fetchAll();
}

function getCartDetailedItems(PDO $pdo, array $cart): array
{
    $cleanCart = [];
    foreach ($cart as $id => $qty) {
        $intId = (int)$id;
        $intQty = (int)$qty;
        if ($intId > 0 && $intQty > 0) {
            $cleanCart[$intId] = $intQty;
        }
    }

    if (count($cleanCart) === 0) {
        return ['items' => [], 'total' => 0.0, 'has_issue' => false];
    }

    $ids = array_keys($cleanCart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $sql = "SELECT
                sp.ID_SP,
                sp.TenSanPham,
                sp.HinhAnh,
                sp.TrangThai,
                lsp.TenLoaiSanPham,
                (
                    SELECT g.GiaBan
                    FROM LichSuGia g
                    WHERE g.ID_SP = sp.ID_SP
                      AND g.NgayBatDau <= NOW()
                      AND (g.NgayKetThuc IS NULL OR g.NgayKetThuc > NOW())
                    ORDER BY (g.NgayKetThuc IS NULL) DESC, g.NgayBatDau DESC, g.ID_GB DESC
                    LIMIT 1
                ) AS GiaBanHienTai,
                COALESCE(
                    (
                        SELECT MIN(
                            CASE
                                WHEN h.TrangThai = 1 AND t.SoBong > 0 THEN FLOOR(h.SoLuongTon / t.SoBong)
                                ELSE 0
                            END
                        )
                        FROM ThanhPhanBo t
                        INNER JOIN Hoa h ON h.ID_H = t.ID_H
                        WHERE t.ID_SP = sp.ID_SP
                    ),
                    0
                ) AS SoLuongKhaDung
            FROM SanPham sp
            INNER JOIN LoaiSanPham lsp ON lsp.ID_LSP = sp.ID_LSP
            WHERE sp.ID_SP IN ({$placeholders})";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();

    $byId = [];
    foreach ($rows as $row) {
        $byId[(int)$row['ID_SP']] = $row;
    }

    $items = [];
    $total = 0.0;
    $hasIssue = false;

    foreach ($cleanCart as $idSP => $qty) {
        if (!isset($byId[$idSP])) {
            $hasIssue = true;
            continue;
        }
        $product = $byId[$idSP];
        $price = $product['GiaBanHienTai'] !== null ? (float)$product['GiaBanHienTai'] : null;
        $maxQty = (int)$product['SoLuongKhaDung'];
        $isActive = (int)$product['TrangThai'] === 1;

        $issue = '';
        if (!$isActive) {
            $issue = 'Sản phẩm đã ngừng kinh doanh.';
        } elseif ($price === null) {
            $issue = 'Sản phẩm chưa có giá bán hiện hành.';
        } elseif ($maxQty <= 0) {
            $issue = 'Nguyên liệu hoa hiện đã hết.';
        } elseif ($qty > $maxQty) {
            $issue = 'Kho hoa chỉ đủ làm tối đa ' . $maxQty . ' sản phẩm.';
        }

        if ($issue !== '') {
            $hasIssue = true;
        }

        $subtotal = $price !== null ? ($price * $qty) : 0.0;
        $total += $subtotal;

        $items[] = [
            'ID_SP' => $idSP,
            'TenSanPham' => (string)$product['TenSanPham'],
            'TenLoaiSanPham' => (string)$product['TenLoaiSanPham'],
            'HinhAnh' => $product['HinhAnh'],
            'GiaBan' => $price,
            'SoLuong' => $qty,
            'SoLuongKhaDung' => $maxQty,
            'ThanhTien' => $subtotal,
            'Issue' => $issue,
        ];
    }

    return [
        'items' => $items,
        'total' => $total,
        'has_issue' => $hasIssue,
    ];
}

function lookupOrder(PDO $pdo, int $idDH, string $sdtKhach): ?array
{
    if ($idDH <= 0 || trim($sdtKhach) === '') {
        return null;
    }

    $sql = "SELECT ID_DH, ID_ND, NgayLapDon, TenKhach, SDTKhach, DiaChiGiao,
                   NgayGiao, TrangThaiDon, TenNguoiNhan, SDTNguoiNhan, GhiChu, TongTien
            FROM DonHang
            WHERE ID_DH = ? AND SDTKhach = ?
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idDH, trim($sdtKhach)]);
    $order = $stmt->fetch();

    if (!$order) {
        return null;
    }

    $detailSql = "SELECT ct.ID_CTDH, ct.ID_SP, ct.SoLuong, ct.DonGia, ct.ThanhTien,
                         sp.TenSanPham, sp.HinhAnh
                  FROM ChiTietDonHang ct
                  INNER JOIN SanPham sp ON sp.ID_SP = ct.ID_SP
                  WHERE ct.ID_DH = ?
                  ORDER BY ct.ID_CTDH ASC";
    $detailStmt = $pdo->prepare($detailSql);
    $detailStmt->execute([$idDH]);
    $order['items'] = $detailStmt->fetchAll();

    return $order;
}

function getAllMenus(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT ID, ID_Cha, TenMenu, MoTa, url FROM Menu ORDER BY ID ASC");
    return $stmt->fetchAll();
}

function getActiveProductTypes(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT ID_LSP, TenLoaiSanPham FROM LoaiSanPham WHERE TrangThai = 1 ORDER BY ID_LSP ASC");
    return $stmt->fetchAll();
}
