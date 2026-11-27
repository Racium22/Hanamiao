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


/**
 * Tổng hợp danh sách thông báo vận hành thực tế cho khu vực Admin từ 15 bảng CSDL hiện có.
 * Khi nghiệp vụ gốc được xử lý xong (duyệt đơn, nhập thêm hoa, bổ sung giá/BOM),
 * thông báo tương ứng tự động biến mất khỏi danh sách.
 */
function getAdminNotifications(PDO $pdo, array $readKeys = []): array
{
    $readLookup = array_flip($readKeys);
    $notifications = [];

    // 1. Đơn hàng mới chờ xác nhận
    $pendingOrders = $pdo->query(
        "SELECT ID_DH, TenKhach, TongTien, NgayLapDon
         FROM DonHang
         WHERE TrangThaiDon = 'ChoXacNhan'
         ORDER BY NgayLapDon DESC, ID_DH DESC
         LIMIT 20"
    )->fetchAll();

    foreach ($pendingOrders as $o) {
        $id = (int)$o['ID_DH'];
        $code = '#DH' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
        $key = 'order_pending_' . $id;
        $notifications[] = [
            'key' => $key,
            'category' => 'orders',
            'category_label' => 'Đơn hàng',
            'severity' => 'warning',
            'icon' => 'shopping-bag',
            'title' => 'Đơn hàng mới ' . $code . ' chờ xác nhận',
            'message' => 'Khách đặt: ' . $o['TenKhach'] . ' · Tổng tiền: ' . formatMoney($o['TongTien']) . '.',
            'time' => formatDate((string)$o['NgayLapDon'], 'd/m/Y H:i'),
            'url' => 'index.php?page=orders&view=' . $id,
            'action_label' => 'Duyệt đơn',
            'is_read' => isset($readLookup[$key]),
            'priority' => 1,
        ];
    }

    // 2. Đơn hàng sắp đến hạn (trong 24 giờ tới) hoặc quá giờ giao nhưng chưa hoàn thành
    $urgentOrders = $pdo->query(
        "SELECT ID_DH, TenNguoiNhan, NgayGiao, TrangThaiDon,
                (NgayGiao < NOW()) AS IsOverdue
         FROM DonHang
         WHERE TrangThaiDon IN ('ChoXacNhan', 'DangChuanBi', 'DangGiao')
           AND NgayGiao <= DATE_ADD(NOW(), INTERVAL 24 HOUR)
         ORDER BY NgayGiao ASC
         LIMIT 15"
    )->fetchAll();

    foreach ($urgentOrders as $o) {
        $id = (int)$o['ID_DH'];
        $code = '#DH' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
        $isOverdue = (int)$o['IsOverdue'] === 1;
        $key = 'order_deadline_' . $id . '_' . ($isOverdue ? 'late' : 'soon');
        $notifications[] = [
            'key' => $key,
            'category' => 'orders',
            'category_label' => 'Đơn hàng',
            'severity' => $isOverdue ? 'error' : 'warning',
            'icon' => 'clock',
            'title' => $isOverdue
                ? ('Đơn ' . $code . ' đã quá giờ giao dự kiến')
                : ('Đơn ' . $code . ' sắp đến giờ giao'),
            'message' => 'Người nhận: ' . $o['TenNguoiNhan'] . ' · Hẹn giao: ' . formatDate((string)$o['NgayGiao'], 'd/m/Y H:i') . '.',
            'time' => formatDate((string)$o['NgayGiao'], 'd/m/Y H:i'),
            'url' => 'index.php?page=orders&view=' . $id,
            'action_label' => 'Xem đơn',
            'is_read' => isset($readLookup[$key]),
            'priority' => $isOverdue ? 0 : 2,
        ];
    }

    // 3. Hoa nguyên liệu hết hàng hoặc tồn thấp (<= 20 bông)
    $lowFlowers = $pdo->query(
        "SELECT h.ID_H, h.TenHoa, h.SoLuongTon, lh.TenLH
         FROM Hoa h
         INNER JOIN LoaiHoa lh ON lh.ID_LH = h.ID_LH
         WHERE h.TrangThai = 1 AND h.SoLuongTon <= 20
         ORDER BY h.SoLuongTon ASC, h.ID_H ASC
         LIMIT 20"
    )->fetchAll();

    foreach ($lowFlowers as $f) {
        $idH = (int)$f['ID_H'];
        $ton = (int)$f['SoLuongTon'];
        $isOos = ($ton === 0);
        $key = 'flower_stock_' . $idH . '_' . ($isOos ? 'oos' : 'low');
        $notifications[] = [
            'key' => $key,
            'category' => 'inventory',
            'category_label' => 'Kho hoa',
            'severity' => $isOos ? 'error' : 'warning',
            'icon' => 'flower-2',
            'title' => $isOos
                ? ($f['TenHoa'] . ' đã hết hàng trong kho')
                : ($f['TenHoa'] . ' sắp hết (còn ' . $ton . ' bông)'),
            'message' => 'Nhóm: ' . $f['TenLH'] . '. Cần lập phiếu nhập kho để tránh gián đoạn kết bó.',
            'time' => 'Tồn kho hiện tại',
            'url' => 'index.php?page=imports',
            'action_label' => 'Nhập kho',
            'is_read' => isset($readLookup[$key]),
            'priority' => $isOos ? 1 : 3,
        ];
    }

    // 4. Sản phẩm đang bán nhưng chưa có giá hiệu lực hoặc chưa có thành phần bó
    $incompleteProducts = $pdo->query(
        "SELECT sp.ID_SP, sp.TenSanPham,
                (
                    SELECT COUNT(*) FROM LichSuGia g
                    WHERE g.ID_SP = sp.ID_SP
                      AND g.NgayBatDau <= NOW()
                      AND (g.NgayKetThuc IS NULL OR g.NgayKetThuc > NOW())
                ) AS HasPrice,
                (SELECT COUNT(*) FROM ThanhPhanBo t WHERE t.ID_SP = sp.ID_SP) AS HasBom
         FROM SanPham sp
         WHERE sp.TrangThai = 1
         HAVING HasPrice = 0 OR HasBom = 0
         ORDER BY sp.ID_SP DESC
         LIMIT 15"
    )->fetchAll();

    foreach ($incompleteProducts as $p) {
        $idSp = (int)$p['ID_SP'];
        if ((int)$p['HasPrice'] === 0) {
            $key = 'product_noprice_' . $idSp;
            $notifications[] = [
                'key' => $key,
                'category' => 'products',
                'category_label' => 'Sản phẩm',
                'severity' => 'warning',
                'icon' => 'tag',
                'title' => 'Sản phẩm "' . $p['TenSanPham'] . '" chưa có giá bán',
                'message' => 'Khách chưa thể đặt mua cho đến khi thiết lập mức giá hiệu lực.',
                'time' => 'Cần cấu hình',
                'url' => 'index.php?page=product_prices&sp=' . $idSp,
                'action_label' => 'Đặt giá',
                'is_read' => isset($readLookup[$key]),
                'priority' => 4,
            ];
        }
        if ((int)$p['HasBom'] === 0) {
            $key = 'product_nobom_' . $idSp;
            $notifications[] = [
                'key' => $key,
                'category' => 'products',
                'category_label' => 'Sản phẩm',
                'severity' => 'warning',
                'icon' => 'layers',
                'title' => 'Sản phẩm "' . $p['TenSanPham'] . '" chưa có thành phần bó',
                'message' => 'Cần khai báo định mức số bông để tính khả dụng và trừ kho tự động.',
                'time' => 'Cần cấu hình',
                'url' => 'index.php?page=product_bom&sp=' . $idSp,
                'action_label' => 'Cấu hình bó',
                'is_read' => isset($readLookup[$key]),
                'priority' => 4,
            ];
        }
    }

    // Ưu tiên thông báo chưa đọc & mức độ khẩn cấp lên trước
    usort($notifications, static function (array $a, array $b): int {
        if ($a['is_read'] !== $b['is_read']) {
            return $a['is_read'] ? 1 : -1;
        }
        return $a['priority'] <=> $b['priority'];
    });

    return $notifications;
}
