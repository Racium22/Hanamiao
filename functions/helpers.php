<?php
declare(strict_types=1);

/**
 * FILE: functions/helpers.php
 * Nhiệm vụ: Các hàm tiện ích dùng chung (chống XSS, định dạng tiền/ngày, flash, ảnh fallback).
 */

function h(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatMoney(float|int|string|null $amount): string
{
    if ($amount === null || $amount === '') {
        return 'Liên hệ';
    }
    return number_format((float)$amount, 0, ',', '.') . ' đ';
}

function formatDate(?string $datetime, string $format = 'd/m/Y H:i'): string
{
    if ($datetime === null || trim($datetime) === '') {
        return '—';
    }
    $timestamp = strtotime($datetime);
    return $timestamp === false ? h($datetime) : date($format, $timestamp);
}

function setFlash(string $type, string $message): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function getFlash(): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function getImageUrl(?string $relativePath, string $prefix = ''): string
{
    $fallback = $prefix . 'images/placeholder.svg';
    $clean = trim((string)($relativePath ?? ''));
    if ($clean === '' || str_contains($clean, '..')) {
        return $fallback;
    }

    $normalized = ltrim(str_replace('\\', '/', $clean), '/');
    $absolutePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);

    if (!is_file($absolutePath)) {
        return $fallback;
    }

    return $prefix . $normalized;
}

function getOrderStatusMeta(string $status): array
{
    return match ($status) {
        'ChoXacNhan' => ['label' => 'Chờ xác nhận', 'class' => 'badge-warning'],
        'DangChuanBi' => ['label' => 'Đang chuẩn bị', 'class' => 'badge-primary'],
        'DangGiao' => ['label' => 'Đang giao', 'class' => 'badge-primary'],
        'HoanThanh' => ['label' => 'Hoàn thành', 'class' => 'badge-success'],
        'DaHuy' => ['label' => 'Đã hủy', 'class' => 'badge-error'],
        default => ['label' => $status, 'class' => 'badge-neutral'],
    };
}

function getCartCount(): int
{
    if (session_status() === PHP_SESSION_NONE) {
        return 0;
    }
    $cart = $_SESSION['cart'] ?? [];
    if (!is_array($cart)) {
        return 0;
    }
    $total = 0;
    foreach ($cart as $qty) {
        $total += max(0, (int)$qty);
    }
    return $total;
}



function handleImageUpload(?array $file, string $subFolder, ?string $currentPath = null): array
{
    if ($file === null || !isset($file['error']) || (int)$file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'path' => $currentPath, 'error' => ''];
    }

    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'path' => $currentPath, 'error' => 'Tải ảnh lên thất bại (mã lỗi ' . (int)$file['error'] . ').'];
    }

    $maxBytes = 3 * 1024 * 1024; // 3MB
    if ((int)($file['size'] ?? 0) > $maxBytes) {
        return ['ok' => false, 'path' => $currentPath, 'error' => 'Ảnh vượt quá giới hạn 3MB.'];
    }

    $tmpPath = (string)($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        return ['ok' => false, 'path' => $currentPath, 'error' => 'Tệp tải lên không hợp lệ.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $tmpPath) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowedMimes[$mime])) {
        return ['ok' => false, 'path' => $currentPath, 'error' => 'Chỉ chấp nhận ảnh định dạng JPG, PNG hoặc WebP.'];
    }

    $safeFolder = in_array($subFolder, ['hoa', 'sanpham', 'banner'], true) ? $subFolder : 'hoa';
    $targetDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $safeFolder;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0775, true);
    }

    $fileName = $safeFolder . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $allowedMimes[$mime];
    $destPath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($tmpPath, $destPath)) {
        return ['ok' => false, 'path' => $currentPath, 'error' => 'Không thể lưu tệp ảnh vào thư mục images/' . $safeFolder . '.'];
    }

    return ['ok' => true, 'path' => 'images/' . $safeFolder . '/' . $fileName, 'error' => ''];
}
