-- ============================================================================
-- FILE: database/hanamiao_schema.sql
-- Dự án: Xây dựng phần mềm quản lý tiệm hoa - Hanamiao
-- CSDL: hanamiao_db (15 bảng chuẩn theo CSDLHanamiao.docx)
-- Engine: InnoDB | Charset: utf8mb4 / utf8mb4_unicode_ci
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `hanamiao_db`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `hanamiao_db`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Người dùng (Quản trị viên, Nhân viên)
CREATE TABLE IF NOT EXISTS `NguoiDung` (
    `ID_ND` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `TenND` VARCHAR(120) NOT NULL,
    `TenDangNhap` VARCHAR(60) NOT NULL,
    `MatKhau` VARCHAR(255) NOT NULL,
    `Quyen` VARCHAR(30) NOT NULL DEFAULT 'nhanvien',
    `TrangThai` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`ID_ND`),
    UNIQUE KEY `uq_nguoidung_tendangnhap` (`TenDangNhap`),
    KEY `idx_nguoidung_quyen` (`Quyen`, `TrangThai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Nhà cung cấp hoa tươi / phụ liệu
CREATE TABLE IF NOT EXISTS `NhaCungCap` (
    `ID_NCC` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `TenNCC` VARCHAR(150) NOT NULL,
    `SDT` VARCHAR(20) NOT NULL,
    `DiaChi` VARCHAR(255) NOT NULL,
    `TrangThai` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`ID_NCC`),
    KEY `idx_ncc_trangthai` (`TrangThai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Loại hoa nguyên liệu
CREATE TABLE IF NOT EXISTS `LoaiHoa` (
    `ID_LH` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `TenLH` VARCHAR(100) NOT NULL,
    `TrangThai` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`ID_LH`),
    KEY `idx_loaihoa_trangthai` (`TrangThai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Hoa nguyên liệu & Tồn kho theo bông/cành
CREATE TABLE IF NOT EXISTS `Hoa` (
    `ID_H` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `TenHoa` VARCHAR(150) NOT NULL,
    `ID_LH` INT UNSIGNED NOT NULL,
    `SoLuongTon` INT NOT NULL DEFAULT 0,
    `HinhAnh` VARCHAR(255) DEFAULT NULL,
    `TrangThai` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`ID_H`),
    KEY `idx_hoa_loai` (`ID_LH`),
    KEY `idx_hoa_ton` (`SoLuongTon`),
    CONSTRAINT `fk_hoa_loaihoa` FOREIGN KEY (`ID_LH`)
        REFERENCES `LoaiHoa` (`ID_LH`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Loại sản phẩm bán (Bó hoa, Giỏ hoa, Lẵng hoa...)
CREATE TABLE IF NOT EXISTS `LoaiSanPham` (
    `ID_LSP` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `TenLoaiSanPham` VARCHAR(120) NOT NULL,
    `TrangThai` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`ID_LSP`),
    KEY `idx_lsp_trangthai` (`TrangThai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Sản phẩm thành phẩm bán cho khách
CREATE TABLE IF NOT EXISTS `SanPham` (
    `ID_SP` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `TenSanPham` VARCHAR(180) NOT NULL,
    `ID_LSP` INT UNSIGNED NOT NULL,
    `HinhAnh` VARCHAR(255) DEFAULT NULL,
    `MoTa` TEXT DEFAULT NULL,
    `TrangThai` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`ID_SP`),
    KEY `idx_sp_lsp` (`ID_LSP`),
    KEY `idx_sp_trangthai` (`TrangThai`),
    CONSTRAINT `fk_sanpham_loaisanpham` FOREIGN KEY (`ID_LSP`)
        REFERENCES `LoaiSanPham` (`ID_LSP`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Thành phần bó hoa (BOM: 1 sản phẩm gồm những hoa nào, bao nhiêu bông)
CREATE TABLE IF NOT EXISTS `ThanhPhanBo` (
    `ID_TPB` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ID_SP` INT UNSIGNED NOT NULL,
    `ID_H` INT UNSIGNED NOT NULL,
    `SoBong` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`ID_TPB`),
    UNIQUE KEY `uq_tpb_sp_hoa` (`ID_SP`, `ID_H`),
    KEY `idx_tpb_hoa` (`ID_H`),
    CONSTRAINT `fk_tpb_sanpham` FOREIGN KEY (`ID_SP`)
        REFERENCES `SanPham` (`ID_SP`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_tpb_hoa` FOREIGN KEY (`ID_H`)
        REFERENCES `Hoa` (`ID_H`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Lịch sử giá bán theo khoảng thời gian
CREATE TABLE IF NOT EXISTS `LichSuGia` (
    `ID_GB` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ID_SP` INT UNSIGNED NOT NULL,
    `GiaBan` DECIMAL(12, 0) NOT NULL,
    `NgayBatDau` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `NgayKetThuc` DATETIME DEFAULT NULL,
    PRIMARY KEY (`ID_GB`),
    KEY `idx_lsg_sp_ngay` (`ID_SP`, `NgayBatDau`, `NgayKetThuc`),
    CONSTRAINT `fk_lsg_sanpham` FOREIGN KEY (`ID_SP`)
        REFERENCES `SanPham` (`ID_SP`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Phiếu ghi nhận hao hụt hoa tươi
CREATE TABLE IF NOT EXISTS `HaoHut` (
    `ID_HH` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ID_ND` INT UNSIGNED NOT NULL,
    `NgayGhiNhan` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `LyDo` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`ID_HH`),
    KEY `idx_haohut_nd` (`ID_ND`),
    KEY `idx_haohut_ngay` (`NgayGhiNhan`),
    CONSTRAINT `fk_haohut_nguoidung` FOREIGN KEY (`ID_ND`)
        REFERENCES `NguoiDung` (`ID_ND`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Chi tiết hao hụt
CREATE TABLE IF NOT EXISTS `ChiTietHaoHut` (
    `ID_CTHH` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ID_HH` INT UNSIGNED NOT NULL,
    `ID_H` INT UNSIGNED NOT NULL,
    `SoLuong` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`ID_CTHH`),
    KEY `idx_cthh_hh` (`ID_HH`),
    KEY `idx_cthh_hoa` (`ID_H`),
    CONSTRAINT `fk_cthh_haohut` FOREIGN KEY (`ID_HH`)
        REFERENCES `HaoHut` (`ID_HH`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_cthh_hoa` FOREIGN KEY (`ID_H`)
        REFERENCES `Hoa` (`ID_H`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Phiếu nhập kho hoa nguyên liệu
CREATE TABLE IF NOT EXISTS `PhieuNhap` (
    `ID_PN` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ID_ND` INT UNSIGNED NOT NULL,
    `ID_NCC` INT UNSIGNED NOT NULL,
    `NgayLapPhieu` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID_PN`),
    KEY `idx_pn_nd` (`ID_ND`),
    KEY `idx_pn_ncc` (`ID_NCC`),
    KEY `idx_pn_ngay` (`NgayLapPhieu`),
    CONSTRAINT `fk_pn_nguoidung` FOREIGN KEY (`ID_ND`)
        REFERENCES `NguoiDung` (`ID_ND`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_pn_nhacungcap` FOREIGN KEY (`ID_NCC`)
        REFERENCES `NhaCungCap` (`ID_NCC`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Chi tiết phiếu nhập
CREATE TABLE IF NOT EXISTS `ChiTietPhieuNhap` (
    `ID_CTPN` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ID_PN` INT UNSIGNED NOT NULL,
    `ID_H` INT UNSIGNED NOT NULL,
    `SoLuong` INT UNSIGNED NOT NULL,
    `GiaNhap` DECIMAL(12, 0) NOT NULL,
    `ThanhTien` DECIMAL(14, 0) NOT NULL,
    PRIMARY KEY (`ID_CTPN`),
    KEY `idx_ctpn_pn` (`ID_PN`),
    KEY `idx_ctpn_hoa` (`ID_H`),
    CONSTRAINT `fk_ctpn_phieunhap` FOREIGN KEY (`ID_PN`)
        REFERENCES `PhieuNhap` (`ID_PN`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_ctpn_hoa` FOREIGN KEY (`ID_H`)
        REFERENCES `Hoa` (`ID_H`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Đơn hàng (Hỗ trợ đặt tặng: tách thông tin người đặt và người nhận)
CREATE TABLE IF NOT EXISTS `DonHang` (
    `ID_DH` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ID_ND` INT UNSIGNED DEFAULT NULL,
    `NgayLapDon` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `TenKhach` VARCHAR(120) NOT NULL,
    `SDTKhach` VARCHAR(20) NOT NULL,
    `DiaChiGiao` VARCHAR(255) NOT NULL,
    `NgayGiao` DATETIME NOT NULL,
    `TrangThaiDon` VARCHAR(30) NOT NULL DEFAULT 'ChoXacNhan',
    `TenNguoiNhan` VARCHAR(120) NOT NULL,
    `SDTNguoiNhan` VARCHAR(20) NOT NULL,
    `GhiChu` VARCHAR(255) DEFAULT NULL,
    `TongTien` DECIMAL(14, 0) NOT NULL DEFAULT 0,
    PRIMARY KEY (`ID_DH`),
    KEY `idx_dh_nd` (`ID_ND`),
    KEY `idx_dh_trangthai` (`TrangThaiDon`),
    KEY `idx_dh_sdt` (`SDTKhach`),
    KEY `idx_dh_ngaylap` (`NgayLapDon`),
    CONSTRAINT `fk_dh_nguoidung` FOREIGN KEY (`ID_ND`)
        REFERENCES `NguoiDung` (`ID_ND`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Chi tiết đơn hàng
CREATE TABLE IF NOT EXISTS `ChiTietDonHang` (
    `ID_CTDH` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ID_DH` INT UNSIGNED NOT NULL,
    `ID_SP` INT UNSIGNED NOT NULL,
    `SoLuong` INT UNSIGNED NOT NULL,
    `DonGia` DECIMAL(12, 0) NOT NULL,
    `ThanhTien` DECIMAL(14, 0) NOT NULL,
    PRIMARY KEY (`ID_CTDH`),
    KEY `idx_ctdh_dh` (`ID_DH`),
    KEY `idx_ctdh_sp` (`ID_SP`),
    CONSTRAINT `fk_ctdh_donhang` FOREIGN KEY (`ID_DH`)
        REFERENCES `DonHang` (`ID_DH`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_ctdh_sanpham` FOREIGN KEY (`ID_SP`)
        REFERENCES `SanPham` (`ID_SP`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Menu đa cấp (kế thừa cấu trúc cha - con Lab 08)
CREATE TABLE IF NOT EXISTS `Menu` (
    `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ID_Cha` INT UNSIGNED NOT NULL DEFAULT 0,
    `TenMenu` VARCHAR(150) NOT NULL,
    `MoTa` VARCHAR(255) DEFAULT NULL,
    `url` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`ID`),
    KEY `idx_menu_cha` (`ID_Cha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

